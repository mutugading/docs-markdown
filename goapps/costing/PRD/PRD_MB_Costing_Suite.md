# PRD — Master Batch Costing Suite (v1.2)

| Field | Value |
|---|---|
| **Document ID** | PRD-COSTING-MB-v1.2 |
| **Status** | Draft v1.2 (supersedes v1.1) |
| **Owner** | IT Lead (Indra) |
| **Target repo** | `mutugading/docs-markdown` |
| **Target path** | `goapps/costing/PRD/PRD_MB_Costing_Suite.md` |
| **Target service** | `goapps-backend` — Finance module |
| **Legacy source** | `MGTAPPS.pkg_mb_costing_process`, `MGTAPPS.pkg_cst_mb_consump`, form `CPF_T002` |

**Changes from v1.1**:
- `mst_mb_head` role clarified — MASTER RECIPE (bukan cost cache seperti v1.1 mengasumsikan)
- Cost storage moved to new `cst_mb_cost` table with discriminator column
- `mst_mb_head_cost` (v1.1 addition) — **removed** dari design
- MB parameters (WASTE, EFFICIENCY, dst) di-store langsung sebagai kolom di `mst_mb_head`, di-copy ke `cst_product_parameter` saat validate
- Machine reference Pattern C (FK + snapshot at validate)
- `mst_mb_spin` extended dengan 10 kolom baru dari legacy `CST_MST_BATCH_SPIN`
- Lusture domain: dedicated lookup table `mst_mb_lusture` (54 values)
- Yarn calc downstream — akan develop ulang untuk consume dari `cst_mb_cost`

---

## 1. Executive Summary

Master Batch (MB) adalah resep pewarna yang di-inject saat produksi POY untuk memberikan warna pada benang. Setiap POY berwarna mengonsumsi MB dengan proporsi yang diatur LDR (Let-Down Ratio). MB Cost harus dihitung setiap bulan mengikuti pergerakan RM Cost, dan hasilnya masuk sebagai komponen biaya di Yarn Costing untuk POY.

Modul ini menggantikan legacy `MGTAPPS.pkg_mb_costing_process` (recipe entry) dan `MGTAPPS.pkg_cst_mb_consump` (monthly cost calculation) di aplikasi web PHP lama. Tujuan utama: memindahkan seluruh siklus MB — dari drafting recipe sampai push cost ke Orion — ke `goapps-backend` Finance service, sambil **reuse engine yarn calculation existing** (Phase C) untuk perhitungan bulanan.

### Table Role Clarification

**Existing tables (extended)**:
- **`mst_mb_head`** — **MASTER RECIPE** (bukan cost cache). Menampung identity + workflow state + MB-specific params + machine reference. Populated by R&D entry, extended by Finance validate.
- **`mst_mb_spin`** — **POY LDR mapping**. Extended dengan legacy fields dari `CST_MST_BATCH_SPIN` (status, Orion code, VS number, cross section, lusture, dst). Tidak ada cost columns (LDR sama untuk semua cost type).

**New tables**:
- **`mst_mb_composition`** — Working set composition (child of `mst_mb_head`)
- **`mst_mb_composition_version`** — Immutable snapshot per validation
- **`mst_mb_lusture`** — Lookup table untuk 54 lusture types (SD, DSD, OSD, RSD, dst)
- **`mst_mb_param`** — Default value provider untuk MB params (initial value saat draft)
- **`mst_mb_param_option`** — Picklist values (THROUGHPUT A/B/C/D/T, PROCESS S/D/T)
- **`mst_mb_workflow_log`** — State transition audit
- **`mst_mb_lock_log`** — Escape hatch audit
- **`mst_mb_push_log`** — Push-to-Head audit
- **`cst_mb_cost`** ⭐ — **Final cost cache** dengan discriminator column (ACTUAL/SELLING/FORECAST/BUDGET). Populated by Push-to-Head. Yarn POY downstream pull cost dari sini.

**External reused (no schema change)**:
- `cost_product_master`, `cost_route_head/rms`, `cst_product_parameter` — Auto-generated at validate
- `cst_product_cost` — Full history semua calc result
- `cst_rm_cost`, `cst_rm_group_head` — RM cost source
- `mst_machine` — Machine reference

### Key Design Decisions

1. **Engine reuse** — saat MB Validated, sistem auto-generate `cost_product_master + cost_route_head + cost_route_rms + cst_product_parameter` untuk MB tsb, lalu dikonsumsi engine yarn calc seperti product biasa. Zero duplicated engine code.
2. **Params live in `mst_mb_head`** — 8 param fields (`mbh_param_waste`, `mbh_param_quality_loss`, dst) di-store langsung di master recipe. Initial value dari `mst_mb_param` master saat draft baru. Value di-copy ke `cst_product_parameter` saat validate.
3. **Machine reference Pattern C** — `mbh_machine_id` FK ke `mst_machine` + `mbh_machine_fixed_total` snapshot value saat validate. Runtime calc pakai FK (fresh value), audit pakai snapshot.
4. **Result storage split by concern**:
   - `cst_product_cost` (existing) = **full history** semua calc jobs, immutable audit trail
   - `cst_mb_cost` (new) = **active cost cache** untuk yarn calc downstream, populated by Push-to-Head
5. **Discriminator column pattern untuk `cst_mb_cost`** — 3 rows per (MB, period): `mbc_cost_type ∈ {ACTUAL, SELLING, FORECAST}`, plus extensible untuk BUDGET / STANDARD di masa depan tanpa ALTER TABLE.
6. **Legacy fidelity untuk conversion cost** — waste/fixed/others/conv cost dihitung 1× per period pakai `MB_RM_COST[ACTUAL]` sebagai anchor. Cuma layer akhir `F_MB_FINAL_COST` yang loop 3× (per cost_type). Match behavior legacy `pkg_cst_mb_consump`.
7. **All 3 cost types default** — single calc trigger produces ACTUAL + SELLING + FORECAST sekaligus.
8. **Own Production vs Boughtout MB dipisah alurnya** — Own via full workflow (Draft → Approved → Validated), Boughtout via shortcut (direct validated, RM group auto-linked).
9. **Immutable Snapshot Versioning** untuk recipe — setiap re-validation membuat version baru, historical cost period selalu refer ke composition version yang aktif saat itu (traced via `cst_product_cost.cpc_param_snapshot.MB_COMPOSITION_VERSION`).
10. **Hard lock** pada auto-generated product master/route/parameter — Finance admin yang boleh unlock via escape hatch dengan 24h auto-relock.
11. **`mst_mb_lusture` lookup table** untuk 54 lusture types — extensible, tidak perlu ALTER TABLE kalau ada value baru.
12. **Historical retention `cst_mb_cost`** — preserve forever (row-per-push, `mbc_is_active` untuk soft delete).

### Success Criteria

- Semua legacy MB (~4091) berhasil migrate dengan cost period 202506 ke atas ter-recalc di goapps engine dan cocok dengan legacy dalam toleransi 0.1% untuk ACTUAL track.
- Recipe entry workflow (Draft → Approved → Validated) dan Push-to-Head cycle dapat dijalankan penuh oleh user R&D + Finance.
- Yarn calc engine (Phase C) di-refactor untuk pull MB cost dari `cst_mb_cost` (via `mbh_id` + `cost_type`).

---

## 2. Business Context

### 2.1 Context

PT Mutu Gading Tekstil memproduksi benang POY berwarna. Warna diperoleh dengan meng-inject Master Batch (butiran plastik berpigmen berbahan dasar carrier PBT) ke lelehan chips saat spinning. Formula pewarnaan (resep MB) di-develop oleh divisi R&D, lalu setelah disetujui akan menjadi acuan produksi dan costing. Setiap resep MB tersusun dari beberapa Raw Material (dye/pigmen/optical brightner/lubrikan) dan carrier PBT sebagai balancing agent.

MB Cost per period terdiri dari:

- **Raw Material Cost** — komposisi × harga RM group per periode
- **Conversion Cost** — waste valuation, machine fixed cost, quality loss, development expense, packing

MB Cost bulanan dipakai oleh POY calculation sebagai komponen biaya, di-mediate oleh LDR%. MB Cost dihitung dalam **3 track cost type**:

- **ACTUAL** — actual production cost, source: `cst_rm_cost.cost_val`
- **SELLING** — sales-basis cost, source: `cst_rm_cost.cost_mark`
- **FORECAST** — forecast/simulation cost, source: `cst_rm_cost.cost_sim`

Ketiga track pakai composition, param, dan formula yang **identik** — cuma source RM cost yang beda per type. Conversion cost dihitung sekali menggunakan ACTUAL sebagai anchor (legacy behavior).

### 2.2 Problem dengan Sistem Legacy

1. **Workflow tidak lengkap** — legacy hanya Draft → Submit → Approve, tanpa step Finance Validate.
2. **Coupling lookup by string** — yarn calc lookup MB via nama product string match.
3. **Tidak ada versioning** — update recipe post-approval overwrite data lama.
4. **Engine terpisah** — duplikasi logic dengan yarn calc engine.
5. **Storage terpisah** — `CST_MB_CONSUMP_HEAD` dedicated table, tidak konsisten dengan yarn calc pipeline.
6. **Manual sync ke Orion** — no audit trail.

### 2.3 Solution Direction

- Workflow lengkap dengan role separation (R&D drafter, R&D head, Finance validator, Finance admin)
- Immutable snapshot version setiap validation
- Auto-generate artifact costing saat validate, reuse engine yarn calc
- Result masuk `cst_product_cost` (immutable history) — sama seperti yarn
- Push-to-Head trigger untuk populate `cst_mb_cost` (active cost cache)
- Yarn POY downstream pull cost dari `cst_mb_cost` via `(mbh_id, cost_type)`
- Batch push ke Orion dengan audit log

---

## 3. Scope

### 3.1 In Scope

**Phase 1 — MB Recipe Module (Own Production MB)**
- CRUD MB recipe di `mst_mb_head` + `mst_mb_composition`
- Header fields: identity + params + machine reference (Pattern C)
- Workflow: Draft → Submitted → Approved (R&D head) → Validated (Finance) + Un-Approve + Revoke
- Immutable snapshot versioning per validation (via `mst_mb_composition_version`)
- Copy Composition dari MB lain

**Phase 2 — Cost Calculation Integration**
- Auto-generate cost artifacts saat Validated:
  - `cost_product_master` (product_type='MB', prefix `CSTMB{YYMM}{seq}`)
  - `cost_route_head` + `cost_route_sequences` + `cost_route_rms` (2-level)
  - `cst_product_parameter` (11 rows: 8 MB params dari `mst_mb_head` + IS_BOUGHTOUT + MACHINE_MB_FIXED_TOTAL + optional MB_SP_CODE)
- Manual calc trigger by Finance UI per period — produces **3 rows per MB in `cst_product_cost`** (ACTUAL, SELLING, FORECAST)
- Auto-recalc kalau RM cost aktif diupdate (same period)
- Historical period frozen via `cpc_status='APPROVED'`

**Phase 3 — Push-to-Head + cst_mb_cost**
- Manual bulk trigger Finance untuk push from `cst_product_cost` (CALCULATED) → `cst_mb_cost`
- 3 rows per MB per period per push (discriminator by cost_type)
- Preserve history (grow forever, `mbc_is_active` for soft delete)

**Phase 4 — Spin Management (Own + Boughtout)**
- CRUD `mst_mb_spin` extended dengan legacy fields
- Migration script untuk parse `CMBS_D_F` string dan normalize
- Lusture value from `mst_mb_lusture` lookup

**Phase 5 — Yarn Calc Refactor**
- Refactor yarn calc parameter (TOP 63–73) untuk lookup MB via `mbs_id` (bukan `mgt_name` string)
- MB cost lookup via `cst_mb_cost` (parameterized by cost_type)
- Migration script untuk update existing yarn parameter references

**Cross-cutting**
- Hard lock + escape hatch (Finance admin unlock, 24h auto-relock)
- Orion push (batch, audit log)
- Full migration dari legacy Oracle

### 3.2 Non-Scope

- Yarn calc engine changes untuk formula selain MB context
- MB recipe simulation / what-if analysis dari draft
- Multi-language support beyond Indonesian + English
- MB dispute / cost adjustment workflow (via escape hatch, bukan dedicated flow)
- BOD Dashboard integration
- Multi-plant / multi-company
- Schema change to `cst_product_cost` or `cst_rm_cost`

---

## 4. Domain Model

### 4.1 Terminology

| Term | Definition |
|---|---|
| **MB (Master Batch)** | Butiran plastik pewarna, produk turunan carrier (PBT) + dye/pigmen |
| **MB Head** | Master Recipe — identity + workflow + params + machine ref. Diisi R&D. |
| **MB Spin** | Aplikasi MB Head ke satu POY spec tertentu dengan LDR% aktual |
| **Recipe** | Komposisi raw material yang membentuk MB — `(RM group, composition %)` totalnya 100% |
| **Own Production MB** | MB yang diproduksi internal — punya recipe + conversion cost |
| **Boughtout MB** | MB yang dibeli jadi dari supplier — tidak punya recipe, cost = harga beli |
| **RM Group** | Grouping raw material di `cst_rm_group_head` |
| **LDR (Let-Down Ratio)** | Persentase MB yang di-inject ke chips POY (juga disebut "dozing") |
| **Version** | Snapshot immutable recipe pada saat Validation |
| **Cost Type** | ACTUAL / SELLING / FORECAST / BUDGET (extensible) — tracks yang di-compute parallel |
| **Cost Calc Result** | 3 rows di `cst_product_cost` (immutable history) per MB per period |
| **Push-to-Head** | Manual bulk action Finance untuk populate `cst_mb_cost` (active cache) dari `cst_product_cost` |
| **Active Cost** | Cost value di `cst_mb_cost` yang dipakai yarn calc POY downstream |

### 4.2 Cost Type Mapping

| Cost Type | `cst_product_cost.cpc_calculation_type` | `cst_mb_cost.mbc_cost_type` | Source in `cst_rm_cost` |
|---|---|---|---|
| **ACTUAL** | ACTUAL | ACTUAL | `cost_val` |
| **SELLING** | SELLING | SELLING | `cost_mark` |
| **FORECAST** | FORECAST | FORECAST | `cost_sim` |
| **BUDGET** (future) | BUDGET | BUDGET | (TBD) |

MB engine hanya read `cst_rm_cost.cost_val/cost_mark/cost_sim`. Flag rules di `cst_rm_cost` transparent — engine agnostic.

### 4.3 Actors & Roles

| Actor | Responsibility | Permission |
|---|---|---|
| **R&D User (drafter)** | Create + edit MB recipe draft | CRUD draft, submit for approval |
| **R&D Head (approver)** | Approve technical composition | Approve, revoke back to draft |
| **Finance Validator** | Validate + generate costing artifacts | Validate, un-approve, trigger cost calc, push-to-head |
| **Finance Admin** | Manage master params, unlock escape hatch | Update `mst_mb_param`, unlock product master, manage `mst_mb_lusture` |
| **Migration Admin** | Run one-off migration scripts | Bulk insert legacy data |

### 4.4 Key Business Rules

**BR-01** Total composition per MB recipe must = 100.000% (3 decimal precision).
**BR-02** Carrier (PBT) auto-fills as remainder to 100%.
**BR-03** VS Number must be unique across all MB.
**BR-04** MB name (`mbh_mb_costing`) must be unique per active row.
**BR-05** Only Own Production MB has recipe; Boughtout MB has RM group linkage instead.
**BR-06** Only Validated MB (latest version) can be consumed by yarn calc.
**BR-07** Historical cost period frozen via `cpc_status='APPROVED'`.
**BR-08** Auto-generated product master (from Validation) is hard-locked; edits require Finance admin escape hatch.
**BR-09** MB cost recalc auto-triggered when RM cost active period is updated.
**BR-10** Escape hatch auto-relock after 24 hours.
**BR-11** Un-Approve by Finance moves state APPROVED → UN_APPROVED. R&D head must re-approve.
**BR-12** Revoke by any actor with reason moves state to REVOKED.
**BR-13** Recipe update post-Validation creates draft next version — current version freezes.
**BR-14** Legacy `APPROVE` status maps to `VALIDATED` in new system.
**BR-15** Single calc trigger produces 3 rows in `cst_product_cost` per MB — one per cost_type.
**BR-16** Conversion cost computed once per period using MB_RM_COST[ACTUAL] as anchor (legacy fidelity).
**BR-17** Push-to-Head inserts/upserts 3 rows to `cst_mb_cost` per MB (one per cost_type).
**BR-18** Yarn calc downstream lookup: `SELECT mbc_cost_value FROM cst_mb_cost WHERE mbc_mbh_id=? AND mbc_cost_type=? AND mbc_is_active=TRUE ORDER BY mbc_period DESC LIMIT 1`.
**BR-19** `cst_mb_cost` rows preserved forever — no auto-purge. Correction via new INSERT + soft delete via `mbc_is_active=FALSE`.
**BR-20** MB params (WASTE, EFFICIENCY, dst) di-store per MB di `mst_mb_head` (bukan referenced dari `mst_mb_param` at runtime). Initial value dari `mst_mb_param` saat draft baru.

---
## 5. High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     goapps-frontend                             │
│  MB Recipe UI · MB Spin UI · Finance Ops UI · Lusture Admin     │
└───────────────────────────┬─────────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────────┐
│              goapps-backend Finance Service                     │
│                                                                 │
│  ┌─────────────────┐ ┌────────────────┐ ┌──────────────────┐    │
│  │  MB Recipe      │ │  MB Cost       │ │  MB Spin         │    │
│  │  Domain         │ │  Domain        │ │  Domain          │    │
│  │  - CRUD         │ │  - Calc        │ │  - CRUD          │    │
│  │  - Workflow     │ │  - Push-Head   │ │  - Boughtout     │    │
│  │  - Version      │ │  - History     │ │  - Migration     │    │
│  └────────┬────────┘ └────────┬───────┘ └──────────────────┘    │
│           │                   │                                 │
│           └──────────┬────────┘                                 │
│                      ▼                                          │
│    ┌───────────────────────────────────┐                        │
│    │  Yarn Calc Engine (Phase C)       │                        │
│    │   - MB_STANDARD formula type ⭐   │                        │
│    │   - Cost type dispatcher          │                        │
│    │   - Topological sort              │                        │
│    └────────────────┬──────────────────┘                        │
└─────────────────────┼───────────────────────────────────────────┘
                      │
        ┌─────────────▼──────────────┐
        │   PostgreSQL 18 (goapps)   │
        │   ─────────────────────── │
        │   MB Module (11 tables)   │
        │   Shared Costing (5+ tbl) │
        └─────────────┬──────────────┘
                      │
        ┌─────────────▼──────────────┐
        │   Orion ERP (Oracle 11g)   │
        │   - CMB item code push     │
        │   - Cost push (batch)      │
        └────────────────────────────┘
```

### 5.1 End-to-End Flow

```
1. R&D User creates draft (Draft → Submitted → Approved → Validated)
   ├─ INSERT mst_mb_head (identity + workflow + params + machine_id)
   │      params initial value dari mst_mb_param defaults
   ├─ INSERT mst_mb_composition (child rows)
   └─ Set mbh_entry_status = 'DRAFT'

2. R&D submit → R&D head approve → Finance validate
   ├─ Snapshot to mst_mb_composition_version (immutable v_N)
   ├─ Take machine snapshot: mbh_machine_fixed_total = mst_machine.mach_fixed_total
   ├─ Auto-generate yarn costing artifacts:
   │     - cost_product_master (product_type='MB', cpm_source='MB_RECIPE', locked)
   │     - cost_route_head + cost_route_sequences + cost_route_rms (from composition_version)
   │     - cst_product_parameter (11 rows populated from mst_mb_head columns)
   └─ Set mbh_entry_status = 'VALIDATED'

3. Finance triggers cost calc for period P
   ├─ Yarn calc engine loops all VALIDATED MB
   ├─ Per MB, per cost_type ∈ {ACTUAL, SELLING, FORECAST}:
   │     - F_MB_RM_COST (3×) — dispatches source col by cost_type
   │     - Conv cost intermediate (1× using ACTUAL anchor)
   │     - F_MB_FINAL_COST (3×)
   └─ INSERT 3 rows to cst_product_cost per MB per period (immutable history)

4. Finance reviews period P, executes Push-to-Head
   ├─ Preview: 3 rows × MB from cst_product_cost (CALCULATED)
   ├─ UPSERT to cst_mb_cost (3 rows per MB per period)
   │     mbc_mbh_id = mst_mb_head.mbh_id
   │     mbc_cost_type = ACTUAL/SELLING/FORECAST
   │     mbc_cost_value = cpc_cost_per_unit
   │     mbc_source_cpc_id = cpc_cost_id (audit link)
   ├─ UPDATE cst_product_cost SET cpc_status='APPROVED'
   └─ Log to mst_mb_push_log

5. Yarn calc POY downstream (refactored — Phase 5)
   └─ SELECT mbc_cost_value 
      FROM cst_mb_cost 
      WHERE mbc_mbh_id = ? AND mbc_cost_type = ? AND mbc_is_active = TRUE
      ORDER BY mbc_period DESC LIMIT 1
```

---

## 6. Data Architecture

### 6.1 ERD (High-Level)

```
     ┌──────────────────────┐            ┌────────────────────────┐
     │  mst_mb_head         │◀──────────│  mst_mb_composition    │
     │  (Master Recipe)     │  1     N   │  (Working set)         │
     └──────────┬───────────┘            └────────────────────────┘
                │
                │ 1
                │   N
                ▼                          ┌────────────────────────┐
     ┌──────────────────────┐         ─── │  mst_mb_composition_   │
     │  mst_mb_spin         │              │  version               │
     │  (POY LDR mapping)   │              │  (Immutable snapshot)  │
     └──────────────────────┘              └────────────────────────┘

     ┌──────────────────────┐            ┌────────────────────────┐
     │  mst_mb_head         │◀──────────│  cst_mb_cost           │
     │  (identity ref)      │  1     N   │  (Active cost cache)   │
     └──────────────────────┘            │  cost_type discriminator│
                                         └───────────┬────────────┘
                                                     │ N
                                                     │
                                                     │ 1
                                                     ▼
                                         ┌────────────────────────┐
                                         │  cst_product_cost      │
                                         │  (Full history)        │
                                         └────────────────────────┘

     ┌──────────────────────┐            ┌────────────────────────┐
     │  mst_mb_lusture      │◀──────────│  mst_mb_head           │
     │  (54 lusture types)  │  1     N   │  mst_mb_spin           │
     └──────────────────────┘            └────────────────────────┘

     ┌──────────────────────┐            ┌────────────────────────┐
     │  mst_mb_param        │◀──────────│  mst_mb_param_option   │
     │  (Default provider)  │  1     N   │  (Picklist values)     │
     └──────────────────────┘            └────────────────────────┘

     Audit trail (workflow_log, lock_log, push_log) — 1:N from mst_mb_head
```

### 6.2 Table DDL

#### 6.2.1 `mst_mb_head` — EXISTING, Extended (MASTER RECIPE)

```sql
-- Existing columns preserved (from current schema):
--   mbh_id, mbh_oracle_sys_id, mbh_mb_costing, mbh_mgt_name,
--   mbh_denier, mbh_filament, mbh_dozing, mbh_is_active,
--   created_at/by, updated_at/by, deleted_at/by

-- Extension for MB Costing Suite v1.2:
ALTER TABLE mst_mb_head
  -- Workflow state
  ADD COLUMN mbh_entry_status VARCHAR(20) NOT NULL DEFAULT 'DRAFT'
    CHECK (mbh_entry_status IN ('DRAFT','SUBMITTED','APPROVED','VALIDATED','UN_APPROVED','REVOKED')),
  ADD COLUMN mbh_current_version INTEGER NOT NULL DEFAULT 1,
  ADD COLUMN mbh_draft_by VARCHAR(20),
  ADD COLUMN mbh_submitted_at TIMESTAMPTZ,
  ADD COLUMN mbh_submitted_by VARCHAR(20),
  ADD COLUMN mbh_approved_at TIMESTAMPTZ,
  ADD COLUMN mbh_approved_by VARCHAR(20),
  ADD COLUMN mbh_validated_at TIMESTAMPTZ,
  ADD COLUMN mbh_validated_by VARCHAR(20),
  ADD COLUMN mbh_state_reason TEXT,
  
  -- Recipe additional identity
  ADD COLUMN mbh_dev_code VARCHAR(30),
  ADD COLUMN mbh_vs_number VARCHAR(30),
  ADD COLUMN mbh_shade_code VARCHAR(30),
  ADD COLUMN mbh_shade_name VARCHAR(100),
  ADD COLUMN mbh_cross_section VARCHAR(10)
    CHECK (mbh_cross_section IS NULL OR mbh_cross_section IN ('RND','TBL','PLUS','OTL')),
  ADD COLUMN mbh_lusture_code VARCHAR(10),  -- FK-soft ke mst_mb_lusture.mbl_code
  ADD COLUMN mbh_customer_id UUID,
  ADD COLUMN mbh_status VARCHAR(20),
  ADD COLUMN mbh_is_boughtout BOOLEAN NOT NULL DEFAULT FALSE,
  
  -- MB-specific parameters (prefix mbh_param_*)
  -- Initial value dari mst_mb_param defaults saat draft baru
  ADD COLUMN mbh_param_waste NUMERIC(6,3),           -- default 2.000
  ADD COLUMN mbh_param_quality_loss NUMERIC(6,3),    -- default 0.600
  ADD COLUMN mbh_param_efficiency NUMERIC(6,3),      -- default 94.000
  ADD COLUMN mbh_param_dev_expense NUMERIC(6,3),     -- default 3.000
  ADD COLUMN mbh_param_packing NUMERIC(10,4),        -- default 0.100
  ADD COLUMN mbh_param_prod_per_day INTEGER,         -- default 16
  ADD COLUMN mbh_param_throughput_code VARCHAR(2),   -- default 'B' (=40 kg/hr)
  ADD COLUMN mbh_param_no_of_process_code VARCHAR(2), -- default 'S' (=1x)
  
  -- Machine reference (Pattern C: both FK + snapshot)
  ADD COLUMN mbh_machine_id UUID,                    -- FK to mst_machine
  ADD COLUMN mbh_machine_fixed_total NUMERIC(20,4),  -- snapshot at validate
  ADD COLUMN mbh_machine_snapshot_at TIMESTAMPTZ,
  
  -- Orion integration
  ADD COLUMN mbh_orion_item_code VARCHAR(20),
  ADD COLUMN mbh_mb_spg_orion VARCHAR(150),
  ADD COLUMN mbh_orion_pushed_at TIMESTAMPTZ,
  ADD COLUMN mbh_orion_pushed_by VARCHAR(20),
  
  -- Costing artifact linkage
  ADD COLUMN mbh_cost_product_id BIGINT,
  ADD COLUMN mbh_cost_generated_at TIMESTAMPTZ,
  ADD COLUMN mbh_cost_generated_by VARCHAR(20);

ALTER TABLE mst_mb_head
  ADD CONSTRAINT fk_mbh_machine FOREIGN KEY (mbh_machine_id)
    REFERENCES mst_machine (m_id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_mbh_cost_product FOREIGN KEY (mbh_cost_product_id)
    REFERENCES cost_product_master (cpm_product_id) ON DELETE SET NULL;

CREATE INDEX idx_mbh_entry_status ON mst_mb_head (mbh_entry_status);
CREATE INDEX idx_mbh_is_boughtout ON mst_mb_head (mbh_is_boughtout);
CREATE INDEX idx_mbh_orion_item_code ON mst_mb_head (mbh_orion_item_code)
  WHERE mbh_orion_item_code IS NOT NULL;
CREATE UNIQUE INDEX uq_mbh_vs_number ON mst_mb_head (mbh_vs_number)
  WHERE mbh_vs_number IS NOT NULL AND deleted_at IS NULL;
```

**Notes**:
- Existing `mbh_dozing` (LDR default) preserved. Redundant with per-spin `mbs_dozing` but kept for backward compat.
- `mbh_lusture_code` soft FK ke `mst_mb_lusture` — check via app layer (54 values changing potentially).
- `mbh_param_*` initial dari `mst_mb_param` defaults. Bisa di-override per MB oleh user.
- `mbh_machine_id` + `mbh_machine_fixed_total` = Pattern C. Runtime calc pakai `mbh_machine_fixed_total` (snapshot). Fresh value bisa di-fetch via FK kalau butuh.
- Total kolom setelah extend: existing ~12 + new ~30 = ~42 kolom di 1 tabel.

#### 6.2.2 `mst_mb_composition` — NEW (Working set)

```sql
CREATE TABLE mst_mb_composition (
  mbcm_id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  mbcm_mbh_id            UUID NOT NULL,
  mbcm_seq_no            INTEGER NOT NULL,
  mbcm_group_head_id     UUID NOT NULL,
  mbcm_composition_pct   NUMERIC(6,3) NOT NULL,
  mbcm_source_type       VARCHAR(20) NOT NULL DEFAULT 'GROUP'
    CHECK (mbcm_source_type IN ('GROUP','MB','CARRIER')),
  mbcm_mb_ref_mbh_id     UUID,
  mbcm_is_carrier        BOOLEAN NOT NULL DEFAULT FALSE,
  mbcm_legacy_sys_id     VARCHAR(30),
  mbcm_created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  mbcm_created_by        VARCHAR(20) NOT NULL,
  mbcm_updated_at        TIMESTAMPTZ,
  mbcm_updated_by        VARCHAR(20),
  CONSTRAINT fk_mbcm_mbh FOREIGN KEY (mbcm_mbh_id)
    REFERENCES mst_mb_head (mbh_id) ON DELETE CASCADE,
  CONSTRAINT fk_mbcm_group FOREIGN KEY (mbcm_group_head_id)
    REFERENCES cst_rm_group_head (rgh_group_head_id),
  CONSTRAINT fk_mbcm_mb_ref FOREIGN KEY (mbcm_mb_ref_mbh_id)
    REFERENCES mst_mb_head (mbh_id),
  CONSTRAINT chk_mbcm_composition CHECK (mbcm_composition_pct >= 0 AND mbcm_composition_pct <= 100),
  CONSTRAINT uq_mbcm_seq UNIQUE (mbcm_mbh_id, mbcm_seq_no)
);

CREATE INDEX idx_mbcm_mbh_id ON mst_mb_composition (mbcm_mbh_id);
CREATE INDEX idx_mbcm_group_head_id ON mst_mb_composition (mbcm_group_head_id);
```

#### 6.2.3 `mst_mb_composition_version` — NEW (Immutable snapshot)

```sql
CREATE TABLE mst_mb_composition_version (
  mbcv_id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  mbcv_mbh_id            UUID NOT NULL,
  mbcv_version           INTEGER NOT NULL,
  mbcv_validated_at      TIMESTAMPTZ NOT NULL,
  mbcv_validated_by      VARCHAR(20) NOT NULL,
  mbcv_seq_no            INTEGER NOT NULL,
  mbcv_group_head_id     UUID NOT NULL,
  mbcv_composition_pct   NUMERIC(6,3) NOT NULL,
  mbcv_source_type       VARCHAR(20) NOT NULL,
  mbcv_mb_ref_mbh_id     UUID,
  mbcv_is_carrier        BOOLEAN NOT NULL DEFAULT FALSE,
  CONSTRAINT fk_mbcv_mbh FOREIGN KEY (mbcv_mbh_id)
    REFERENCES mst_mb_head (mbh_id) ON DELETE CASCADE,
  CONSTRAINT uq_mbcv_seq UNIQUE (mbcv_mbh_id, mbcv_version, mbcv_seq_no)
);

CREATE INDEX idx_mbcv_mbh_version ON mst_mb_composition_version (mbcv_mbh_id, mbcv_version);
```

#### 6.2.4 `mst_mb_spin` — EXISTING, Extended (POY LDR)

```sql
-- Existing columns preserved:
--   mbs_id, mbs_oracle_sys_id, mbs_mbh_id, mbs_mgt_name,
--   mbs_denier, mbs_filament, mbs_dozing (LDR), mbs_mb_costing, mbs_is_active,
--   created_at/by, updated_at/by, deleted_at/by

-- Extension aligned dengan legacy CST_MST_BATCH_SPIN:
ALTER TABLE mst_mb_spin
  -- Legacy status fields
  ADD COLUMN mbs_status VARCHAR(20)
    CHECK (mbs_status IS NULL OR mbs_status IN ('Spinning','R and D','Boughtout')),
  ADD COLUMN mbs_check_status VARCHAR(20)
    CHECK (mbs_check_status IS NULL OR mbs_check_status IN ('Waiting','Current','Approved','Outdated','Boughtout')),
  
  -- Orion integration
  ADD COLUMN mbs_orion_item_code VARCHAR(20),
  ADD COLUMN mbs_mb_spg_orion VARCHAR(150),
  
  -- Additional identity
  ADD COLUMN mbs_vs_number VARCHAR(30),
  ADD COLUMN mbs_code VARCHAR(30),
  
  -- POY spec (parsed from D_F, or normalized)
  ADD COLUMN mbs_cross_section VARCHAR(10)
    CHECK (mbs_cross_section IS NULL OR mbs_cross_section IN ('RND','TBL','PLUS','OTL')),
  ADD COLUMN mbs_lusture_code VARCHAR(10),  -- FK-soft ke mst_mb_lusture.mbl_code
  ADD COLUMN mbs_num_process INTEGER,       -- parsed from D_F (rare)
  ADD COLUMN mbs_d_f_raw VARCHAR(50),        -- preserve legacy string for audit
  
  -- Free text
  ADD COLUMN mbs_remarks TEXT;

CREATE INDEX idx_mbs_orion_item ON mst_mb_spin (mbs_orion_item_code)
  WHERE mbs_orion_item_code IS NOT NULL;
CREATE INDEX idx_mbs_check_status ON mst_mb_spin (mbs_check_status);
CREATE UNIQUE INDEX uq_mbs_code_active ON mst_mb_spin (mbs_code)
  WHERE mbs_code IS NOT NULL AND mbs_is_active = TRUE AND deleted_at IS NULL;
```

**Notes**:
- No composite unique on POY spec — legacy data shows multiple variants per POY spec (different LDR% or `-01`/`-02` suffix). Uniqueness enforced via `mbs_oracle_sys_id` (bridge) atau optional `mbs_code`.
- `mbs_lusture_code` soft FK ke `mst_mb_lusture` (54 values).
- `mbs_dozing` (existing) = LDR% (values 1.0-5.0 range confirmed = ratio, not weight).
- `mbs_num_process` — kalau ada value, cross-check dengan parent `mst_mb_head.mbh_param_no_of_process_code`. Kalau berbeda → flagged inconsistency.

#### 6.2.5 `mst_mb_lusture` — NEW ⭐ (Lusture reference lookup)

```sql
CREATE TABLE mst_mb_lusture (
  mbl_id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  mbl_code              VARCHAR(10) NOT NULL UNIQUE,
  mbl_display_name      VARCHAR(50),
  mbl_full_description  VARCHAR(200),
  mbl_category          VARCHAR(30),
  mbl_is_active         BOOLEAN NOT NULL DEFAULT TRUE,
  mbl_display_order     INTEGER,
  mbl_created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  mbl_created_by        VARCHAR(20) NOT NULL,
  mbl_updated_at        TIMESTAMPTZ,
  mbl_updated_by        VARCHAR(20)
);

CREATE INDEX idx_mbl_active_order ON mst_mb_lusture (mbl_display_order)
  WHERE mbl_is_active = TRUE;
```

**Seed data**: 54 rows (see Appendix 20.5 for full list). Category examples:
- **Standard**: SD, BRT, FD, OSD, DSD, dst
- **Dope Dyed**: DSD, DSDA, DFSD, DFD, DBRT
- **Recycle**: RSD, RFD, RBR, RDSD, RBSD, RBBR, ROFD, ROSD, RDFD
- **Eco**: ESD, ERSD, EDSD
- **Cationic**: CSD, CBRT, CFSD, SCDP, SDCD
- **Optical**: OSD, OBRT
- **Black**: BSD, BBRT, BFD, BSDB, BSDP, SBSD, BSFR
- **Polyurethane**: PU, BSDP, SCDP, BRTP, DSDP
- **Flame Retardant**: FR, SDFR, RSFR, BSFR
- **Specialty**: HDSD, SBSD, HTSD, MSD, NSD, PBSD, dst

**Usage**:
- `mst_mb_head.mbh_lusture_code` references `mbl_code`
- `mst_mb_spin.mbs_lusture_code` references `mbl_code`
- Soft FK (app-layer validation) — allows referring codes across active/deprecated states

#### 6.2.6 `mst_mb_param` + `mst_mb_param_option` — NEW (Default provider only)

```sql
CREATE TABLE mst_mb_param (
  mbp_id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  mbp_code              VARCHAR(30) NOT NULL UNIQUE,
  mbp_name              VARCHAR(100) NOT NULL,
  mbp_description       TEXT,
  mbp_type              VARCHAR(20) NOT NULL
    CHECK (mbp_type IN ('SCALAR','PICKLIST')),
  mbp_default_value     NUMERIC(20,6),
  mbp_default_option    VARCHAR(10),
  mbp_unit              VARCHAR(20),
  mbp_display_order     INTEGER,
  mbp_is_active         BOOLEAN NOT NULL DEFAULT TRUE,
  mbp_created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  mbp_created_by        VARCHAR(20) NOT NULL,
  mbp_updated_at        TIMESTAMPTZ,
  mbp_updated_by        VARCHAR(20)
);

CREATE TABLE mst_mb_param_option (
  mbpo_id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  mbpo_mbp_code          VARCHAR(30) NOT NULL,
  mbpo_code              VARCHAR(10) NOT NULL,
  mbpo_numeric_value     NUMERIC(20,6) NOT NULL,
  mbpo_description       TEXT,
  mbpo_display_order     INTEGER,
  mbpo_is_active         BOOLEAN NOT NULL DEFAULT TRUE,
  CONSTRAINT fk_mbpo_mbp FOREIGN KEY (mbpo_mbp_code)
    REFERENCES mst_mb_param (mbp_code) ON UPDATE CASCADE,
  CONSTRAINT uq_mbpo_mbp_code UNIQUE (mbpo_mbp_code, mbpo_code)
);
```

**Role clarification**: 
- `mst_mb_param` = **default value provider only** untuk MB baru saat draft entry
- Value tersimpan di `mst_mb_head.mbh_param_*` columns
- Update `mst_mb_param.mbp_default_value` affects future MB only (existing MB retain their frozen values)

**Seed data**: 8 params (WASTE, QUALITY_LOSS, EFFICIENCY, DEV_EXPENSE, PACKING, MB_PROD_PER_DAY, THROUGHPUT_PER_HOUR, NO_OF_PROCESS) + 8 options (A/B/C/D/T + S/D/T).

#### 6.2.7 `cst_mb_cost` — NEW ⭐ (Active cost cache)

```sql
CREATE TABLE cst_mb_cost (
  mbc_id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  mbc_mbh_id            UUID NOT NULL,
  mbc_period            VARCHAR(6) NOT NULL 
    CHECK (mbc_period ~ '^[0-9]{6}$'),
  mbc_cost_type         VARCHAR(20) NOT NULL
    CHECK (mbc_cost_type IN ('ACTUAL','SELLING','FORECAST','BUDGET')),
  mbc_cost_value        NUMERIC(20,6) NOT NULL,
  mbc_source_cpc_id     BIGINT,
  mbc_pushed_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  mbc_pushed_by         VARCHAR(20) NOT NULL,
  mbc_is_active         BOOLEAN NOT NULL DEFAULT TRUE,
  mbc_created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  mbc_updated_at        TIMESTAMPTZ,
  mbc_updated_by        VARCHAR(20),
  CONSTRAINT fk_mbc_mbh FOREIGN KEY (mbc_mbh_id)
    REFERENCES mst_mb_head (mbh_id) ON DELETE CASCADE,
  CONSTRAINT fk_mbc_cpc FOREIGN KEY (mbc_source_cpc_id)
    REFERENCES cst_product_cost (cpc_cost_id) ON DELETE SET NULL,
  CONSTRAINT uq_mbc_period_type UNIQUE (mbc_mbh_id, mbc_period, mbc_cost_type)
);

-- Primary lookup index for yarn calc downstream
CREATE INDEX idx_mbc_lookup 
  ON cst_mb_cost (mbc_mbh_id, mbc_cost_type, mbc_period DESC) 
  WHERE mbc_is_active = TRUE;

CREATE INDEX idx_mbc_period ON cst_mb_cost (mbc_period);
CREATE INDEX idx_mbc_pushed_at ON cst_mb_cost (mbc_pushed_at DESC);
```

**Role**: **Final cost cache** untuk yarn calc downstream. 3 rows per MB per period per push.

**Retention**: Preserve forever. Row per (MB, period, cost_type). Correction via new INSERT + soft delete via `mbc_is_active=FALSE` on old row.

**Yarn calc lookup pattern**:
```sql
SELECT mbc_cost_value
FROM cst_mb_cost
WHERE mbc_mbh_id = :mbh_id
  AND mbc_cost_type = :cost_type   -- parameterized runtime
  AND mbc_is_active = TRUE
ORDER BY mbc_period DESC
LIMIT 1;
```

Single-table read, indexed, parameterized. Zero branching di engine code.

#### 6.2.8 `mst_mb_workflow_log`, `mst_mb_lock_log`, `mst_mb_push_log`

```sql
-- mst_mb_workflow_log
CREATE TABLE mst_mb_workflow_log (
  mbwl_id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  mbwl_mbh_id       UUID NOT NULL,
  mbwl_from_state   VARCHAR(20),
  mbwl_to_state     VARCHAR(20) NOT NULL,
  mbwl_actor_user_id VARCHAR(20) NOT NULL,
  mbwl_actor_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  mbwl_reason       TEXT,
  mbwl_version      INTEGER,
  mbwl_meta         JSONB,
  CONSTRAINT fk_mbwl_mbh FOREIGN KEY (mbwl_mbh_id)
    REFERENCES mst_mb_head (mbh_id) ON DELETE CASCADE
);

CREATE INDEX idx_mbwl_mbh_at ON mst_mb_workflow_log (mbwl_mbh_id, mbwl_actor_at DESC);

-- mst_mb_lock_log
CREATE TABLE mst_mb_lock_log (
  mbll_id                 UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  mbll_cost_product_id    BIGINT NOT NULL,
  mbll_unlocked_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  mbll_unlocked_by        VARCHAR(20) NOT NULL,
  mbll_reason             TEXT NOT NULL,
  mbll_auto_relock_at     TIMESTAMPTZ NOT NULL,
  mbll_relocked_at        TIMESTAMPTZ,
  mbll_relocked_by        VARCHAR(20),
  mbll_manual_edits       JSONB
);

-- mst_mb_push_log
CREATE TABLE mst_mb_push_log (
  mbpl_id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  mbpl_period       VARCHAR(6) NOT NULL,
  mbpl_pushed_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  mbpl_pushed_by    VARCHAR(20) NOT NULL,
  mbpl_mb_count     INTEGER NOT NULL,      -- unique MB pushed
  mbpl_row_count    INTEGER NOT NULL,      -- 3 × mb_count typically
  mbpl_cost_types   VARCHAR(50) NOT NULL,  -- comma-separated
  mbpl_previous_period VARCHAR(6),
  mbpl_snapshot     JSONB,
  mbpl_notes        TEXT
);

CREATE INDEX idx_mbpl_period ON mst_mb_push_log (mbpl_period);
CREATE INDEX idx_mbpl_pushed_at ON mst_mb_push_log (mbpl_pushed_at DESC);
```

### 6.3 Relationships Summary

| From | To | Cardinality |
|---|---|---|
| `mst_mb_head` | `mst_mb_composition` | 1:N (working set) |
| `mst_mb_head` | `mst_mb_composition_version` | 1:N (immutable snapshots) |
| `mst_mb_head` | `mst_mb_spin` | 1:N (POY applications) |
| `mst_mb_head` | `cst_mb_cost` | 1:3N (3 rows × N periods) |
| `mst_mb_head` | `mst_mb_workflow_log` | 1:N |
| `mst_mb_head` | `mst_machine` | N:1 (via mbh_machine_id) |
| `mst_mb_head` | `mst_mb_lusture` | N:1 (soft FK via mbh_lusture_code) |
| `mst_mb_head` | `cost_product_master` | 1:1 (via mbh_cost_product_id) |
| `mst_mb_composition` | `cst_rm_group_head` | N:1 |
| `mst_mb_composition` | `mst_mb_head` (self) | N:1 (MB-from-MB recursive) |
| `mst_mb_spin` | `mst_mb_head` | N:1 |
| `mst_mb_spin` | `mst_mb_lusture` | N:1 (soft FK) |
| `cst_mb_cost` | `cst_product_cost` | N:1 (via mbc_source_cpc_id, audit link) |

---
## 7. Workflow & Versioning

### 7.1 State Machine

```
                    ┌──────────┐
                    │  (start) │
                    └────┬─────┘
                         ▼
                    ┌──────────┐◀──────────┐
                    │  DRAFT   │           │
                    └────┬─────┘           │
                         │ submit          │
                    ┌────▼──────┐          │
                    │ SUBMITTED │          │
                    └─┬────┬────┘          │
             approve  │    │  revoke       │
                      ▼    ▼               │
              ┌──────────┐ ┌───────────┐   │
              │ APPROVED │ │  REVOKED  │───┘
              └────┬─────┘ └───────────┘
        validate  │     un_approve
          (Finance)     ▲
                   ▼    │
              ┌──────────┴───┐
              │  VALIDATED   │  ← Auto-gen artifacts, snapshot version
              └──────┬───────┘
                     │ edit → create v_(N+1) → back to DRAFT
                     ▼
              (working set editable)
```

### 7.2 Transition Rules

Same as v1.1:
- **DRAFT → SUBMITTED**: R&D user submit
- **SUBMITTED → APPROVED**: R&D head approve
- **APPROVED → VALIDATED**: Finance validate, triggers artifact gen + snapshot
- **VALIDATED → UN_APPROVED**: Finance un-approve (rollback, DOES NOT delete artifacts, marks stale)
- **UN_APPROVED → APPROVED**: R&D head re-approve
- **Any → REVOKED**: Requires reason, final state, no further transitions

### 7.3 Versioning Rules

- **V-01**: `mbh_current_version` monotonic increment per MB, no reuse.
- **V-02**: Working set (`mst_mb_composition`) hanya menyimpan komposisi untuk version aktif.
- **V-03**: `mst_mb_composition_version` immutable — snapshot semua composition items pada saat validation. `cst_product_cost.cpc_param_snapshot.MB_COMPOSITION_VERSION` traces which version was used.
- **V-04**: Update recipe post-VALIDATED creates draft v_(N+1) → state back to DRAFT.
- **V-05**: Auto-gen `cost_product_master` gets same product code across versions — route + params regenerated, product master persists.

---

## 8. Cost Calculation Engine

### 8.1 Formula Specification — Legacy Fidelity + Cost Type Dispatch

MB Cost dihitung per periode dengan 7 formulas MB_STANDARD. **Legacy fidelity**: conversion cost dihitung sekali per period pakai `MB_RM_COST[ACTUAL]` sebagai anchor. Hanya `F_MB_RM_COST` dan `F_MB_FINAL_COST` yang loop per cost type.

```
── Runtime Context ─────────────────────────────
COST_TYPE ∈ {ACTUAL, SELLING, FORECAST, BUDGET (future)}

Source column dispatch:
  ACTUAL   → cst_rm_cost.cost_val
  SELLING  → cst_rm_cost.cost_mark
  FORECAST → cst_rm_cost.cost_sim
  BUDGET   → (TBD, future)

── Formula 1: F_MB_RM_COST (per cost_type, runs 3×+) ──
For each composition item i:
  IF mbcm_source_type = 'MB' (recursive):
    RM_Cost[i] = cst_product_cost.cpc_cost_per_unit
                  [MB_ref → cost_product, period=P, calc_type=COST_TYPE]
                × Composition[i]/100
  ELSE:
    RM_Cost[i] = cst_rm_cost.<source_col_for(COST_TYPE)>
                  [group_head_id, period=P]
                × Composition[i]/100

MB_RM_COST[COST_TYPE] = Σ RM_Cost[i]

── Formula 2-6: SHARED intermediate (runs 1×) ──
Anchor: MB_RM_COST[ACTUAL]

F_MB_WASTE_VAL:
  MB_WASTE_VALUATION = (MB_RM_COST[ACTUAL] / (1 - WASTE/100)) × (WASTE/100)

F_MB_NET_PROD:
  MB_NET_PROD_PER_DAY = THROUGHPUT_PER_HOUR × (EFFICIENCY/100) × MB_PROD_PER_DAY

F_MB_FIXED_COST:
  MB_FIXED_COST = IF(MB_NET_PROD_PER_DAY = 0, 0,
                     MACHINE_MB_FIXED_TOTAL / MB_NET_PROD_PER_DAY)

F_MB_COST_OTHERS:
  MB_COST_OTHERS = ((MB_RM_COST[ACTUAL] + MB_WASTE_VALUATION + MB_FIXED_COST)
                    × ((QUALITY_LOSS + DEV_EXPENSE)/100)) + PACKING

F_MB_CONV_COST:
  MB_CONV_COST = (NO_OF_PROCESS × MB_FIXED_COST) + MB_COST_OTHERS + MB_WASTE_VALUATION

── Formula 7: F_MB_FINAL_COST (per cost_type, runs 3×+) ──
IF IS_BOUGHTOUT = TRUE:
  MB_COST_FINAL[COST_TYPE] = MB_RM_COST[COST_TYPE]
ELSE:
  MB_COST_FINAL[COST_TYPE] = MB_RM_COST[COST_TYPE] + MB_CONV_COST

── Result — 3 rows to cst_product_cost per MB per period ──
Row 1: (product, period, ACTUAL,   cost_per_unit=MB_COST_FINAL[ACTUAL],   ...)
Row 2: (product, period, SELLING,  cost_per_unit=MB_COST_FINAL[SELLING],  ...)
Row 3: (product, period, FORECAST, cost_per_unit=MB_COST_FINAL[FORECAST], ...)

Shared across 3 rows:
  cpc_total_conversion  (MB_CONV_COST — single value)
  cpc_param_snapshot    (same JSONB)
  cpc_job_id            (same batch)
  cpc_calculated_at     (same timestamp)

Different per row:
  cpc_calculation_type
  cpc_cost_per_unit     (MB_COST_FINAL[type])
  cpc_total_rm_cost     (MB_RM_COST[type])
```

### 8.2 Auto-Generated Cost Artifacts at Validate

Saat MB Validated, sistem generate:

**Product Master** (auto):
```sql
INSERT INTO cost_product_master (
  cpm_product_code,   -- CSTMB{YYMM}{seq}
  cpm_product_type,   -- 'MB'
  cpm_product_name,   -- mbh_mb_costing
  cpm_source,         -- 'MB_RECIPE' (lock flag)
  cpm_is_locked,      -- TRUE
  cpm_flex_01,        -- mbh_mb_spg_orion
  cpm_flex_02,        -- mbh_id (bridge back to MB Head)
  ...
) VALUES (...);
```

**Route** (auto):
```sql
INSERT INTO cost_route_head (crh_product_id, ...) VALUES (mb_product_id);
INSERT INTO cost_route_sequences (...) VALUES (route_id, 1, mb_product_id);
INSERT INTO cost_route_rms (...)
  SELECT route_id, 'GROUP', mbcv_group_head_id, mbcv_composition_pct/100
  FROM mst_mb_composition_version
  WHERE mbcv_mbh_id = ? AND mbcv_version = <current>;
```

**Product Parameters** (11 rows, populated from `mst_mb_head`):
```sql
INSERT INTO cst_product_parameter (cpp_product_id, cpp_param_code, cpp_value) VALUES
  (mb_product_id, 'WASTE',                  mbh.mbh_param_waste),
  (mb_product_id, 'QUALITY_LOSS',           mbh.mbh_param_quality_loss),
  (mb_product_id, 'EFFICIENCY',             mbh.mbh_param_efficiency),
  (mb_product_id, 'DEV_EXPENSE',            mbh.mbh_param_dev_expense),
  (mb_product_id, 'PACKING',                mbh.mbh_param_packing),
  (mb_product_id, 'MB_PROD_PER_DAY',        mbh.mbh_param_prod_per_day),
  (mb_product_id, 'THROUGHPUT_PER_HOUR',    <resolved from throughput_code via mst_mb_param_option>),
  (mb_product_id, 'NO_OF_PROCESS',          <resolved from no_of_process_code via mst_mb_param_option>),
  (mb_product_id, 'IS_BOUGHTOUT',           mbh.mbh_is_boughtout::int),
  (mb_product_id, 'MACHINE_MB_FIXED_TOTAL', mbh.mbh_machine_fixed_total),
  (mb_product_id, 'MB_COMPOSITION_VERSION', mbh.mbh_current_version);
```

**Params source clarity**:
- Value diambil dari `mst_mb_head.mbh_param_*` columns (bukan runtime lookup ke `mst_mb_param`)
- `mst_mb_head.mbh_param_*` values di-set awal dari `mst_mb_param` defaults saat draft baru
- User bisa override params per MB di draft edit sebelum validate
- Post-validate params jadi frozen di `cst_product_parameter`

### 8.3 Engine Extension: MB_STANDARD Formula Type

7 formulas total (5 SHARED + 2 PER_TYPE):

| # | Formula code | Output param | Scope |
|---|---|---|---|
| 1 | F_MB_RM_COST | MB_RM_COST[type] | PER_TYPE (3×+ per period) |
| 2 | F_MB_WASTE_VAL | MB_WASTE_VALUATION | SHARED (1× per period) |
| 3 | F_MB_NET_PROD | MB_NET_PROD_PER_DAY | SHARED (1×) |
| 4 | F_MB_FIXED_COST | MB_FIXED_COST | SHARED (1×) |
| 5 | F_MB_COST_OTHERS | MB_COST_OTHERS | SHARED (1×) |
| 6 | F_MB_CONV_COST | MB_CONV_COST | SHARED (1×) |
| 7 | F_MB_FINAL_COST | MB_COST_FINAL[type] | PER_TYPE (3×+) |

Total: 3 + 5 + 3 = **11 executions per MB per period** (assuming 3 cost types).

### 8.4 Cost Calc Trigger Flow

```
POST /api/v1/mb/cost/calc
  { period: "202607", cost_types: ["ACTUAL","SELLING","FORECAST"], mode: "ALL" }

1. Create cal_job (job_type='MB_BATCH', period, cost_types)

2. Validation gates:
   - RM cost exists for period
   - At least 1 VALIDATED MB
   - No conflicting concurrent job (advisory lock)

3. Topological sort:
   - DAG from mst_mb_composition_version (source_type='MB')
   - Cycle detection
   - Order MB by dependency

4. For each MB in topological order:
   a. Load composition_version (current)
   b. Load params from cst_product_parameter (11 rows — auto-gen at validate)
   c. For cost_type = ACTUAL: compute MB_RM_COST[ACTUAL]
   d. Compute SHARED intermediate (waste_val, net_prod, fixed_cost, cost_others, conv_cost)
      using MB_RM_COST[ACTUAL] as anchor
   e. For cost_type ∈ {SELLING, FORECAST}: compute MB_RM_COST[type]
   f. For each cost_type: compute MB_COST_FINAL[type]
   g. Mark existing CALCULATED rows (product, period, type) as SUPERSEDED
   h. INSERT 3 rows to cst_product_cost:
        cpc_calculation_type = ACTUAL / SELLING / FORECAST
        cpc_cost_per_unit    = MB_COST_FINAL[type]
        cpc_total_rm_cost    = MB_RM_COST[type]
        cpc_total_conversion = MB_CONV_COST (shared)
        cpc_param_snapshot   = {version, all_11_params, MB_RM_COST_ALL_TYPES}
        cpc_formula_trace    = {intermediates}
        cpc_rm_cost_detail   = [per composition item breakdown]
        cpc_status           = 'CALCULATED'
        cpc_job_id           = <batch job>

5. Update cal_job status, return response

Response: {
  job_id, period, cost_types,
  total_mb, success, failed,
  rows_inserted (3 × success typically),
  duration_ms, errors: [...]
}
```

### 8.5 Auto-Recalc

Trigger listens on `cst_rm_cost` UPDATE (any of cost_val / cost_mark / cost_sim):
- Enqueue Redis: `recalc-mb-{period}`
- Background worker consumes → find affected MB (composition includes affected group_head_id) → re-trigger calc

Deduplication via Redis SETNX per period-key.

---

## 9. Push-to-Head Mechanism

### 9.1 Purpose

Setelah `cst_product_cost` populated (status CALCULATED, 3 rows per MB per period), Finance execute Push-to-Head untuk:
- INSERT/UPSERT 3 rows to `cst_mb_cost` per MB
- UPDATE `cst_product_cost.cpc_status` to APPROVED
- Yarn calc downstream reads from `cst_mb_cost` — fast lookup

### 9.2 Flow

```
GET /api/v1/mb/cost/push/preview?period=202607

Preview response: {
  period: "202607",
  cost_types: ["ACTUAL","SELLING","FORECAST"],
  mb_count: 4081,           // MB with all 3 CALCULATED rows in period
  mb_missing: 4,            // MB with incomplete rows (skipped)
  cost_change_summary: {
    ACTUAL:   { avg_change_pct, top_movers },
    SELLING:  { ... },
    FORECAST: { ... }
  },
  preview_rows: 12243       // 4081 × 3
}

User reviews → Confirm

POST /api/v1/mb/cost/push/execute
  { period, cost_types, confirm: true }

Transaction (per MB × cost_type):
  1. INSERT to cst_mb_cost:
       INSERT INTO cst_mb_cost (
         mbc_mbh_id, mbc_period, mbc_cost_type, mbc_cost_value,
         mbc_source_cpc_id, mbc_pushed_at, mbc_pushed_by
       )
       SELECT
         mbh.mbh_id, cpc.cpc_period, cpc.cpc_calculation_type,
         cpc.cpc_cost_per_unit, cpc.cpc_cost_id,
         NOW(), :actor
       FROM cst_product_cost cpc
       JOIN cost_product_master cpm ON cpc.cpc_product_sys_id = cpm.cpm_product_id
       JOIN mst_mb_head mbh ON mbh.mbh_cost_product_id = cpm.cpm_product_id
       WHERE cpc.cpc_period = '202607'
         AND cpc.cpc_calculation_type IN ('ACTUAL','SELLING','FORECAST')
         AND cpc.cpc_status = 'CALCULATED'
       ON CONFLICT (mbc_mbh_id, mbc_period, mbc_cost_type)
       DO UPDATE SET
         mbc_cost_value    = EXCLUDED.mbc_cost_value,
         mbc_source_cpc_id = EXCLUDED.mbc_source_cpc_id,
         mbc_pushed_at     = EXCLUDED.mbc_pushed_at,
         mbc_pushed_by     = EXCLUDED.mbc_pushed_by,
         mbc_is_active     = TRUE,
         mbc_updated_at    = NOW();

  2. UPDATE cst_product_cost SET cpc_status = 'APPROVED'
       WHERE the pushed rows;

  3. INSERT into mst_mb_push_log;

COMMIT.

Response: { push_id, pushed_row_count, pushed_mb_count, skipped_mb_count, duration_ms }
```

### 9.3 Push Rules

- **PR-01**: Push scope: bulk only, no per-MB manual push.
- **PR-02**: Push requires all 3 cost_types complete per MB. Incomplete MB flagged, skipped.
- **PR-03**: Push idempotent — re-push overwrites same period row (UPSERT via unique constraint on `(mbh_id, period, cost_type)`).
- **PR-04**: Push not reversible via UI — correction requires recalc + re-push.
- **PR-05**: Push preserves history — old row's `mbc_is_active` remains TRUE unless soft-deleted separately.
- **PR-06**: Advisory lock per period prevents concurrent push.

### 9.4 History Retention

`cst_mb_cost` grows forever. Every push adds/overwrites rows per (MB, period, cost_type). To manage growth:
- No auto-purge
- Soft delete via `mbc_is_active=FALSE` for known-incorrect rows
- Yarn calc always filters `WHERE mbc_is_active=TRUE`
- Latest row per (MB, cost_type) selected via `ORDER BY mbc_period DESC LIMIT 1`

---

## 10. Locking & Escape Hatch

Same as v1.1. Auto-generated `cost_product_master` (+ route + params) hard-locked via `cpm_source='MB_RECIPE'` + `cpm_is_locked=TRUE`. Finance admin unlock via escape hatch, 24h auto-relock.

See v1.1 for full details (Section 10). Behavior unchanged.

---

## 11. MB Spin Management

### 11.1 Extended Fields (from CST_MST_BATCH_SPIN)

`mst_mb_spin` sekarang cover semua legacy fields:
- Identity: `mbs_code`, `mbs_vs_number`, `mbs_mgt_name`, `mbs_mb_costing`
- Spec: `mbs_denier`, `mbs_filament`, `mbs_cross_section`, `mbs_lusture_code`, `mbs_num_process`
- LDR: `mbs_dozing` (confirmed = LDR%)
- Status: `mbs_status`, `mbs_check_status`
- Orion: `mbs_orion_item_code`, `mbs_mb_spg_orion`
- Legacy audit: `mbs_d_f_raw` (raw string), `mbs_oracle_sys_id`
- Free text: `mbs_remarks`

### 11.2 Migration Parser for `CMBS_D_F`

Legacy `CST_MST_BATCH_SPIN.CMBS_D_F` format inconsistent:
```
250/48/RND/DSD          — 4-part standard
250/48 RND/DSD          — space typo
POY 310/288/RND/SD      — prefix
380/108/2/TBL/DBR       — 5-part with num_process
250/36/2 RND/DSD        — 5-part with typo
```

Parser strategy:
1. Strip "POY " prefix
2. Replace all whitespace with "/"
3. Split by "/"
4. 4 parts → (denier, filament, cross_section, lusture)
5. 5 parts → (denier, filament, num_process, cross_section, lusture)
6. Store raw string in `mbs_d_f_raw` regardless

**Lusture validation**: cross-check against `mst_mb_lusture.mbl_code`. Unknown → log to migration errors, `mbs_lusture_code = NULL`, `mbs_d_f_raw` preserved.

### 11.3 Multiple Spin Variants per POY Spec

Confirmed by user data (MGT SMORES WT 1093 in 250/144/RND/OSD dengan LDR 1.5% AND separately dengan LDR 1.7%). No composite unique on POY spec.

---

## 12. Boughtout MB Registration

Same as v1.1 — direct VALIDATED, auto-linked to 1-item RM group in `cst_rm_group_head`. Skip recipe workflow. Cost calc yields `MB_CONV_COST = 0` (via IS_BOUGHTOUT flag).

See v1.1 Section 12 for detail.

---

## 13. Yarn Calc Refactor (Phase 5)

### 13.1 Current Legacy Coupling

Yarn calc (Phase C) currently uses string match:
```sql
WHERE mst_mb_spin.CMBS_MGT_NAME = yarn_product.CYL_SHADE_NAME
```

### 13.2 New Lookup Pattern

**Change 1**: Yarn calc parameter TOP 63 (`MB_SP_CODE`) return `mbs_id` (UUID).

**Change 2**: Yarn calc parameter TOP 71 (`MB_COST_PER_KG`) new query:
```sql
SELECT mbc_cost_value
FROM cst_mb_cost mbc
JOIN mst_mb_spin mbs ON mbs.mbs_mbh_id = mbc.mbc_mbh_id
WHERE mbs.mbs_id = :spin_id
  AND mbc.mbc_cost_type = :yarn_calc_cost_type
  AND mbc.mbc_is_active = TRUE
  AND mbs.mbs_check_status = 'Current'
ORDER BY mbc.mbc_period DESC
LIMIT 1;
```

**Change 3**: LDR from `mbs.mbs_dozing` directly.

### 13.3 Cost Type Propagation

Yarn calc engine harus pass `cost_type` context ke MB lookup. Kalau yarn calc jalankan ACTUAL calculation, MB lookup dispatch ACTUAL cost. Kalau SELLING, dispatch SELLING.

### 13.4 Rollback Strategy

Feature flag `USE_CST_MB_COST_LOOKUP` (default OFF, flip per env). Rollback = flip OFF, engine reverts to legacy string match temporarily.

---
## 14. Data Migration

### 14.1 Source & Target Mapping

| Legacy Oracle | New goapps table | Notes |
|---|---|---|
| `CST_MST_BATCH_HEAD` (CMBH) | `mst_mb_head` (extend existing) | ~4091 rows, add workflow + params + machine |
| `CST_MST_BATCH_ITEM` (CMBI) | `mst_mb_composition` + `mst_mb_composition_version` | ~15K composition items |
| `CST_MST_BATCH_SPIN` (CMBS) | `mst_mb_spin` (extend existing) | ~2713 rows, parse `CMBS_D_F` |
| `CST_MST_BATCH_CALC` (CMBCALC) | `mst_mb_head.mbh_param_*` per MB | 14 legacy params → 8 goapps params |
| `CST_MB_CONSUMP_HEAD` | `cst_product_cost` (filtered MB) + `cst_mb_cost` (last period) | Historical cost |
| N/A (new) | `mst_mb_lusture` | 54 seed values |
| N/A (new) | `mst_mb_param` + `mst_mb_param_option` | 8 params + 8 options seed |

### 14.2 Migration Sequence

```
Step 0: Backup Oracle source, snapshot goapps DB
Step 1: Load mst_mb_lusture seed (54 rows) — see Appendix 20.5
Step 2: Load mst_mb_param + mst_mb_param_option seed
Step 3: Extend mst_mb_head schema (ALTER TABLE) + mst_mb_spin (ALTER TABLE)
Step 4: Populate mst_mb_head.mbh_param_* from CMBCALC per-MB values
        Fallback to mst_mb_param.mbp_default_value if per-MB CMBCALC null
Step 5: Bulk load mst_mb_composition (from CMBI)
Step 6: For each VALIDATED MB, auto-generate cost artifacts:
        - Snapshot to mst_mb_composition_version v1
        - cost_product_master row
        - cost_route_head + sequences + rms
        - cst_product_parameter (11 rows populated from mst_mb_head columns)
Step 7: Parse CMBS_D_F string via parse_cmbs_d_f() script (Section 11.2)
        Bulk load mst_mb_spin with parsed cross_section, lusture_code, num_process
Step 8: For each period in CMB_CONSUMP_HEAD (last 24 months):
        - Trigger MB batch calc via engine
        - 3 rows per MB per period inserted to cst_product_cost
        - Verify reconciliation vs legacy CMBCO_COST_VALUATION
Step 9: Push-to-Head for latest period per MB → populate cst_mb_cost (3 rows × 4091 MB = ~12K rows)
Step 10: Phase 5 yarn calc refactor (Section 13)
Step 11: Validation report — count reconciliation, cost variance
```

### 14.3 Reconciliation Rules

- **RC-01**: MB count match legacy
- **RC-02**: Composition sum per MB = 100.000% (±0.001)
- **RC-03**: For historical periods, ACTUAL cost variance vs legacy CMBCO_COST_VALUATION < 0.1% for 95% MB
- **RC-04**: Spin count match legacy CST_MST_BATCH_SPIN
- **RC-05**: Every VALIDATED MB has `mbh_cost_product_id` populated
- **RC-06**: Every active MB has 3 rows in `cst_mb_cost` after Push-to-Head Step 9

---

## 15. API Specification

### 15.1 Endpoints

```
── MB Recipe ─────────────────────────────────
POST   /api/v1/mb/recipe                     Create draft
GET    /api/v1/mb/recipe                     List
GET    /api/v1/mb/recipe/{id}                Detail
PATCH  /api/v1/mb/recipe/{id}                Update draft (including params)
POST   /api/v1/mb/recipe/{id}/composition    Add/update composition
DELETE /api/v1/mb/recipe/{id}/composition/{seq}
POST   /api/v1/mb/recipe/{id}/calc-pbt       Auto-fill PBT
POST   /api/v1/mb/recipe/{id}/submit
POST   /api/v1/mb/recipe/{id}/approve
POST   /api/v1/mb/recipe/{id}/validate       + generate artifacts
POST   /api/v1/mb/recipe/{id}/un-approve
POST   /api/v1/mb/recipe/{id}/revoke
POST   /api/v1/mb/recipe/{id}/copy-composition

── MB Spin ────────────────────────────────
POST   /api/v1/mb/spin
GET    /api/v1/mb/spin?mbh_id={id}
PATCH  /api/v1/mb/spin/{id}
DELETE /api/v1/mb/spin/{id}
POST   /api/v1/mb/spin/bulk-import

── Boughtout ──────────────────────────────
POST   /api/v1/mb/boughtout

── MB Cost ────────────────────────────────
POST   /api/v1/mb/cost/calc                  Trigger period calc (all 3 types default)
GET    /api/v1/mb/cost/calc/{job_id}
POST   /api/v1/mb/cost/push/preview
POST   /api/v1/mb/cost/push/execute
GET    /api/v1/mb/cost/history?mbh_id={id}   From cst_product_cost
GET    /api/v1/mb/cost/active?mbh_id={id}    From cst_mb_cost

── Lookup Admin ───────────────────────────
GET    /api/v1/mb/lusture                    List 54 lusture types
POST   /api/v1/mb/lusture                    Add new lusture (admin)
PATCH  /api/v1/mb/lusture/{code}
GET    /api/v1/mb/param                      List param defaults
PATCH  /api/v1/mb/param/{code}
GET    /api/v1/mb/param-option/{param_code}

── Lock Console ───────────────────────────
POST   /api/v1/mb/lock/unlock
POST   /api/v1/mb/lock/relock
GET    /api/v1/mb/lock/currently-unlocked
```

### 15.2 Sample Responses

**GET `/api/v1/mb/recipe/{id}`**:
```json
{
  "mbh_id": "uuid",
  "mb_costing": "MGT VIOLET MGTS 5017-D-00812",
  "entry_status": "VALIDATED",
  "current_version": 3,
  "denier": 250, "filament": 48,
  "cross_section": "RND", "lusture_code": "DSD",
  "ldr_default": 3.000,
  "is_boughtout": false,
  "params": {
    "waste": 2.000, "quality_loss": 0.600, "efficiency": 94.000,
    "dev_expense": 3.000, "packing": 0.100, "prod_per_day": 16,
    "throughput_code": "B", "no_of_process_code": "S"
  },
  "machine": {
    "machine_id": "uuid",
    "fixed_total": 384000.00,
    "snapshot_at": "2026-07-06T09:00:00Z"
  },
  "composition": [...],
  "active_cost": {
    "actual":   { "value": 25898.18, "period": "202607", "pushed_at": "..." },
    "selling":  { "value": 26150.00, "period": "202607", "pushed_at": "..." },
    "forecast": { "value": 25100.50, "period": "202607", "pushed_at": "..." }
  },
  "cost_product_id": 42
}
```

**GET `/api/v1/mb/cost/active?mbh_id={id}`** (fast lookup from cst_mb_cost):
```json
{
  "mbh_id": "uuid",
  "costs": [
    { "cost_type": "ACTUAL",   "value": 25898.18, "period": "202607", "is_active": true },
    { "cost_type": "SELLING",  "value": 26150.00, "period": "202607", "is_active": true },
    { "cost_type": "FORECAST", "value": 25100.50, "period": "202607", "is_active": true }
  ]
}
```

---

## 16. UI Requirements

Full screen inventory (11 screens):

1. **Recipe list** — filter by status, pagination
2. **Recipe editor** — 2-column form:
   - Header identity (name, denier, filament, cross_section, lusture dropdown from `mst_mb_lusture`)
   - **Params section (new)**: 8 param fields editable in-form (waste, quality_loss, dst)
   - **Machine section (new)**: dropdown machine + snapshot value display
   - Composition table (as before)
3. **Validate dialog** — modal preview generated artifacts + param snapshot
4. **Cost calc console** — period + cost_types multi-select (default all 3), progress bar
5. **Push-to-head console** — preview per cost_type, top movers tabs
6. **Cost history** — from `cst_product_cost` (immutable history) — full audit trail
7. **Active cost view (new)** — from `cst_mb_cost` — current active per (MB, type)
8. **Spin management** — extended dengan legacy fields display
9. **Boughtout registration** — shortcut form
10. **Params master** — role: default provider (Finance admin)
11. **Lusture master (new)** — CRUD 54 lusture types
12. **Lock console** — currently unlocked + history

---

## 17. Non-Functional Requirements

**Performance**:
- Cost calc 4091 MB × 3 cost_types × 11 executions < 90 seconds
- Push-to-Head 4081 × 3 rows UPSERT < 8 seconds
- Yarn calc downstream lookup `cst_mb_cost` < 1ms (indexed)

**Availability**: 99.5% uptime; calc job resumable via `cal_job` state

**Scalability**: Horizontal scale workers; Redis dedup per period

**Security**: Role-based via existing IAM; audit trail for every state transition + calc + push

**Data integrity**: Composition sum CHECK; VS number unique; immutable snapshots preserved

**Observability**: OpenTelemetry traces for cost calc; Prometheus metrics; Jaeger UI

---

## 18. Rollout Plan

**Week 1-2**: Foundation
- DDL migration (11 MB tables + extensions)
- Seed data (`mst_mb_lusture`, `mst_mb_param`, `mst_mb_param_option`)
- API scaffold

**Week 3-4**: Recipe workflow
- Full state machine
- UI screens 1-3
- Composition version logic

**Week 5-6**: Cost engine
- MB_STANDARD formula extension
- Auto-gen artifacts pipeline
- Calc console (screen 4)

**Week 7**: Push-to-Head + downstream
- `cst_mb_cost` upsert flow
- UI screens 5, 7
- Phase 5 yarn refactor start

**Week 8**: Spin + Boughtout + Admin
- Spin CRUD (screen 8)
- Boughtout flow (screen 9)
- Params + Lusture admin (screens 10-11)
- Lock console (screen 12)

**Week 9-10**: Migration
- Scripts execution
- Reconciliation validation
- Backfill history

**Week 11-12**: UAT + rollout
- UAT R&D + Finance
- Legacy deprecated

---

## 19. Risks & Mitigations

| Risk | Impact | Likelihood | Mitigation |
|---|---|---|---|
| Migration variance > 0.1% | Med | Med | Parallel run 1 month, manual review flagged MB |
| Yarn calc breaks post Phase 5 | High | Low | Feature flag `USE_CST_MB_COST_LOOKUP`, gradual rollout |
| Lusture code mismatch in migration | Low | Med | Pre-load 54 seed, log unknowns to migration errors, allow `mbs_lusture_code = NULL` |
| Params in mst_mb_head diverge from mst_mb_param defaults | Med | Med | Documentation + admin UI awareness ("default provider only") |
| Recursive MB-from-MB cycle at scale | Med | Low | Cycle detection pre-run |
| Machine snapshot stale | Low | High (over time) | Re-validate refreshes snapshot; UI displays snapshot age |
| cst_mb_cost grow forever | Low | High | Preserve intentional; index tuning; archive strategy if needed post-2028 |

---

## 20. Appendix

### 20.1 Sample Calculation — MGT SMORES WT 1093-CS

**Input** (from `mst_mb_head`):
- Composition (4 items): Violet MGTS 0.071%, Optical Brightner 6.545%, Lubricon 2.000%, PBT 91.384%
- `mbh_param_*`: WASTE=2, QUALITY_LOSS=0.6, EFFICIENCY=94, DEV_EXPENSE=3, PACKING=0.1, MB_PROD_PER_DAY=16
- Picklist: THROUGHPUT_PER_HOUR=40 (from code 'B'), NO_OF_PROCESS=1 (from code 'S')
- `mbh_machine_fixed_total`: 384,000 IDR/day (snapshot at validate)
- `mbh_is_boughtout`: FALSE
- `mbh_lusture_code`: 'OSD'
- `mbh_cross_section`: 'RND'

**RM Cost per Track**:

| Component | cost_val (ACT) | cost_mark (SEL) | cost_sim (FOR) |
|---|---|---|---|
| Violet MGTS 0.071% | 202.35 | 210.42 | 195.30 |
| Optical Brightner 6.545% | 2,748.90 | 2,850.00 | 2,650.00 |
| Lubricon 2.000% | 360.00 | 372.00 | 348.00 |
| PBT 91.384% | 20,561.40 | 20,692.05 | 19,881.67 |
| **MB_RM_COST[type]** | **23,872.65** | **24,124.47** | **23,074.97** |

**Intermediate (1× using ACTUAL anchor)**:
- MB_NET_PROD_PER_DAY = 40 × 0.94 × 16 = **601.6 kg/day**
- MB_WASTE_VALUATION = (23,872.65 / 0.98) × 0.02 = **487.20**
- MB_FIXED_COST = 384,000 / 601.6 = **638.30**
- MB_COST_OTHERS = ((23,872.65 + 487.20 + 638.30) × 0.036) + 0.100 = **900.03**
- MB_CONV_COST = (1 × 638.30) + 900.03 + 487.20 = **2,025.53**

**Final (3 rows to `cst_product_cost`)**:

| Cost Type | RM Cost | Conv Cost | **Final** |
|---|---|---|---|
| ACTUAL | 23,872.65 | 2,025.53 | **25,898.18** |
| SELLING | 24,124.47 | 2,025.53 | **26,150.00** |
| FORECAST | 23,074.97 | 2,025.53 | **25,100.50** |

**Push-to-Head result** (3 rows in `cst_mb_cost`):
```
(mbh_id, 202607, ACTUAL,   25898.18, source_cpc_id, ...)
(mbh_id, 202607, SELLING,  26150.00, source_cpc_id, ...)
(mbh_id, 202607, FORECAST, 25100.50, source_cpc_id, ...)
```

### 20.2 Prefix Registry (updated v1.2)

| Prefix | Table |
|---|---|
| `mbh_` | `mst_mb_head` (extended — master recipe) |
| `mbs_` | `mst_mb_spin` (extended — POY LDR) |
| `mbcm_` | `mst_mb_composition` (working set) |
| `mbcv_` | `mst_mb_composition_version` (immutable snapshot) |
| `mbl_` | `mst_mb_lusture` (54 lusture lookup) ⭐ v1.2 |
| `mbp_` | `mst_mb_param` (default provider) |
| `mbpo_` | `mst_mb_param_option` |
| `mbwl_` | `mst_mb_workflow_log` |
| `mbpl_` | `mst_mb_push_log` |
| `mbll_` | `mst_mb_lock_log` |
| `mbc_` | `cst_mb_cost` (active cost cache) ⭐ v1.2 |
| ~~`mbhc_`~~ | ~~`mst_mb_head_cost`~~ (REMOVED — was v1.1 addition) |
| ~~`mbcp_`~~ | ~~`mst_mb_cost_period`~~ (REMOVED — was v1.0 addition) |

### 20.3 Legacy Field Mapping

| Legacy | New location |
|---|---|
| `CMBH_SYS_ID` | `mst_mb_head.mbh_oracle_sys_id` |
| `CMBH_MGT_NAME` | `mst_mb_head.mbh_mgt_name` |
| `CMBH_ENTRY_STATUS='APPROVE'` | `mst_mb_head.mbh_entry_status='VALIDATED'` |
| `CMBI_CGH_SYS_ID` | `mst_mb_composition.mbcm_group_head_id` (resolved) |
| `CMBS_D_F` (parsed) | `mst_mb_spin.mbs_denier/filament/cross_section/lusture_code` + `mbs_d_f_raw` (preserve raw) |
| `CMBS_LESTURE` (mislabeled) | `mst_mb_spin.mbs_cross_section` (actual meaning) |
| `CMBS_RUN_LDR_PRSN` | `mst_mb_spin.mbs_dozing` |
| `CMBCALC_*` per MB | `mst_mb_head.mbh_param_*` |
| `CMB_CONSUMP_HEAD.CMBCO_COST_VALUATION` | `cst_product_cost.cpc_cost_per_unit` (cpc_calculation_type=ACTUAL) |
| Similar for MARKETING/SIMULATION | mapped to SELLING/FORECAST |

### 20.4 Change Log

**v1.2 (2026-07-07)** — This document
- `mst_mb_head` role clarified: MASTER RECIPE (not cost cache)
- Params moved into `mst_mb_head.mbh_param_*` columns
- Machine ref Pattern C (FK + snapshot)
- New `cst_mb_cost` with discriminator column
- New `mst_mb_lusture` lookup (54 values)
- `mst_mb_head_cost` REMOVED (was in v1.1)
- `mst_mb_spin` extended dengan 10 legacy fields
- Yarn calc downstream develop ulang (Phase 5)

**v1.1** — Introduced `mst_mb_head_cost` child table (rolled back in v1.2)

**v1.0** — Introduced `mst_mb_cost_period` (rolled back in v1.1)

### 20.5 Lusture Master Seed Data (54 rows)

| Code | Display Name | Full Description | Category |
|---|---|---|---|
| SD | SD | Semi Dull | Standard |
| DSD | DSD | Dope Dyed Colored Semi Dull | Dope Dyed |
| OSD | OSD | Optical Semidull | Optical |
| BRT | BRT | Bright | Standard |
| FD | FD | Full Dull | Standard |
| BSD | BSD | Black Semi Dull | Black |
| BBRT | BBRT | Black Bright | Black |
| BFD | BFD | Black Full Dull | Black |
| SBSD | SBSD | Super Dyed Black Semi Dull | Black |
| BSFR | BSDFR | Black Semi Dull Flame Retardant | Black + FR |
| OBRT | OBRT | Optical Bright | Optical |
| DBRT | DBRT | Dope Dyed Bright Colored | Dope Dyed |
| DFD | DFD | Dope Dyed Full Dull | Dope Dyed |
| DFSD | DFSD | Dope Dyed Colored Full Dull | Dope Dyed |
| DSDA | DSD AS | Dope Dyed Colour Semidull Anti Static | Dope Dyed |
| EDSD | EDSD | Eco Dope Dyed Colored Semi Dull | Eco |
| ESD | ESD | Eco Semi Dull | Eco |
| ERSD | ERSD | Eco Recycle Semi Dull | Eco Recycle |
| RSD | RSD | Recycle Semi Dull | Recycle |
| RFD | RFD | Recycle Full Dull | Recycle |
| RBR | RBR | Recycle Bright | Recycle |
| RDSD | RDSD | Recycle Dyed Semi Dull | Recycle |
| RBSD | RBSD | Recycle Black Semi Dull | Recycle Black |
| RBBR | RBBR | Recycle Black Bright | Recycle Black |
| ROFD | ROFD | Recycle Optical Full Dull | Recycle Optical |
| ROSD | ROSD | Recycle Optical Semidull | Recycle Optical |
| RDFD | RDFD | Recycle Dope Dyed Full Dull | Recycle |
| RSFR | RSDFR | Recycle Semi Dull Flame Retardant | Recycle + FR |
| CSD | CSD | Cationic Semi Dull | Cationic |
| CBRT | CBRT | Cationic Bright | Cationic |
| CFSD | CFSD | Cationic Full Semi Dull | Cationic |
| SCDP | SDCD PU | Semi Dull Cationic Dyeable Polyurethane | Cationic + PU |
| SDCD | SDCD | Semi Dull Cationic Dyeable | Cationic |
| PU | PU | Polyurethane | PU |
| BSDP | BSD PU | BSD Polyurethane | PU + Black |
| BSDB | BSD BPU | BSD Black Polyurethane | PU + Black |
| BRTP | BRT PU | BRT Polyurethane | PU |
| DSDP | DSD PU | DSD Polyurethane | PU + Dope Dyed |
| FR | FR | Flame Retardant | FR |
| SDFR | SDFR | Semi Dull Flame Retardant | FR |
| SBR | SBR | Super Bright | Bright |
| EBR | EBR | Extra Bright | Bright |
| HBR | HBR | Health Bright | Specialty |
| HSD | HSD | Health Semi Dull | Specialty |
| HDSD | HDSD | Hank Dyeing Semi Dull | Specialty |
| GSD | GSD | Glow Semi Dull | Specialty |
| SSD | SSD | Super Dyed Semi Dull | Specialty |
| MSD | MSD | Moisture Management Semi Dull | Specialty |
| NSD | NSD | Nylon Semi Dull | Nylon |
| PBSD | PBSD | PBT Semi Dull | PBT |
| HTSD | HTSD | High Tenacity Semi Dull | Specialty |
| SDBR | SDBR | Super Dyed Bright | Bright |
| SPBR | SPBR | Space Bright | Space |
| SPSD | SPSD | Space Semi Dull | Space |
| SPFB | SPFBR | Space Full Dull Bright | Space |
| FBR | FBR | Full Dull Bright | Full Dull |

Total: 54 values. Full-text search across code + display_name + full_description untuk UI dropdown filter.

---

*End of PRD v1.2*
