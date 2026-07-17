# 9. Master Data & Config

## Product Master

Sistem PPC tidak maintain product master sendiri. Reference ke **Costing Module**
(`cost_product_master`, prefix `CPM_`):

```
cpm_product_sys_id    -- PK, dipakai di ppc_cpm_product_sys_id
cpm_product_code      -- kode produk costing (CST-prefixed)
cpm_erp_item_code     -- item code Orion (untuk masuk planning)
cpm_grade_code        -- default 'AX'
cpm_shade_code / cpm_shade_name
```

## Product Route (Costing) — sumber routing & RM

Route milik Costing, dikonsumsi PPC (WO snapshot `crh_head_id` + `crh_version`):

```
cost_route_head   -- crh_head_id, crh_product_sys_id, crh_version,
                  --   crh_routing_status (DRAFT/COMPLETE/LOCKED), fork/lock
cost_route_seq    -- crs_seq_id, crs_head_id, crs_route_level, crs_route_seq
                  --   → urutan proses (routing / next-process, multi-level SPG→TXT→TWT)
cost_route_rm     -- crm_rm_id, crm_seq_id, crm_rm_type (PRODUCT/ITEM/GROUP),
                  --   crm_route_rm_shade_code, crm_route_rm_ratio
                  --   → sumber WO_RM_ALLOCATION. type=PRODUCT ⇒ genealogy antar produk
```

## Product Parameter (Costing) — sumber parameter WO (v1.2)

Modul product-parameter milik Costing (akses termasuk orang produksi). PPC **konsumen**,
tidak maintain. Dua tabel:

```
mst_parameter (definisi global)
  id (UUID), param_code, param_name, data_type (NUMBER/TEXT/BOOLEAN),
  param_category (INPUT/RATE/CALCULATED/MASTER_LOOKUP),
  lookup_master_code (enum combobox, mis. YARN_TYPE), uom_id,
  default_value, min_value, max_value,
  owner_department, is_required_for_costing,
  display_group (Spec/Machine/Grade/Packing/Cost/…), display_order

cost_product_parameter (nilai per PRODUK)
  cpp_product_sys_id, cpp_param_id → mst_parameter,
  cpp_value_numeric / cpp_value_text / cpp_value_flag  (three-column typed by data_type)
```

**Selector parameter WO** = `display_group`: `Machine` → `WO_PARAMETER` (dual PPC/PC untuk 8
param), `Spec` → snapshot, `Packing` → packing instruction, `Grade` → grade req, `Cost` →
diabaikan WO. **Rekomendasi ke costing (Open Item):** tambah `is_for_production` di
`mst_parameter`; sementara pakai `display_group='Machine'`.

**Well-known param codes** (di-pin ke `param_id` spesifik untuk efficiency engine, stabil):
denier, yarn speed (YS), no-of-position, std-weight.

### PRODUCT_PPC_CONFIG (extension PPC)

```sql
PRODUCT_PPC_CONFIG
  ppc_id                   BIGSERIAL PK
  ppc_cpm_product_sys_id   BIGINT        NOT NULL UNIQUE
  ppc_is_commodity_watch   BOOLEAN       DEFAULT FALSE
  ppc_price_sell           DECIMAL(12,2)
  ppc_machine_group_id     BIGINT
  ppc_yield_std            DECIMAL(5,3)
  ppc_buffer_rm_pct        DECIMAL(5,3)
  ppc_ax_yield_pct         DECIMAL(5,3)  -- historical %AX (0.75–0.84)
  ppc_updated_at           TIMESTAMPTZ   DEFAULT NOW()
```

### PRODUCT_MACHINE_PARAMETER — layer nilai per PRODUK+MESIN (v1.2, Opsi A)

`cost_product_parameter` grain-nya per-produk saja. Nilai yang **bervariasi per mesin**
(speed, posisi, draw-ratio) hidup di layer PPC ini, reference `param_id` yang sama —
**satu definisi, dua grain nilai**. Rantai resolusi WO: referensi WO → layer ini →
`cost_product_parameter` → `mst_parameter.default_value`.

```sql
PRODUCT_MACHINE_PARAMETER
  pmp_id                   BIGSERIAL PK
  pmp_cpm_product_sys_id   BIGINT        NOT NULL
  pmp_machine_id           BIGINT        NOT NULL
  pmp_param_id             UUID          NOT NULL     -- FK mst_parameter (costing)
  pmp_value_num            DECIMAL(20,6)
  pmp_value_text           TEXT
  pmp_value_flag           BOOLEAN
  pmp_updated_at           TIMESTAMPTZ   DEFAULT NOW()
  UNIQUE (pmp_cpm_product_sys_id, pmp_machine_id, pmp_param_id)
```

### PRODUCT_MACHINE_CAPACITY — planning-math only

Setelah reframe, PMC **hanya** untuk matematika planning/demand (no-of-machine-needed).
Nilai setup (speed dsb yang dipakai WO) pindah ke `PRODUCT_MACHINE_PARAMETER`.

```sql
PRODUCT_MACHINE_CAPACITY
  pmc_id                   BIGSERIAL PK
  pmc_cpm_product_sys_id   BIGINT        NOT NULL
  pmc_machine_id           BIGINT        NOT NULL
  pmc_prod_per_day         DECIMAL(10,3) -- kapasitas planning
  pmc_efficiency_pct       DECIMAL(5,2)  -- target eff planning (bukan actual)
  pmc_updated_at           TIMESTAMPTZ   DEFAULT NOW()
  UNIQUE (pmc_cpm_product_sys_id, pmc_machine_id)
```

> speed/no_of_positions/draw_ratio (v1.1 di PMC) → sekarang `PRODUCT_MACHINE_PARAMETER`.

---

## Machine Master

```sql
MACHINE_MASTER
  machine_id               BIGSERIAL PK
  machine_no               VARCHAR(10)   NOT NULL UNIQUE  -- dari TXTMACH.MACH_NO
  machine_area             CHAR(3)       NOT NULL         -- TXT / SPG / TWT
  machine_line             VARCHAR(20)                    -- SPG: nama line
  machine_kind             VARCHAR(15)                    -- MACHINE / LINE (SPG=LINE)
  machine_group_id         BIGINT
  machine_doff_weight_kg   DECIMAL(8,3)  -- TXT-1:1200, TXT-2:600
  machine_is_active        BOOLEAN       DEFAULT TRUE
  machine_orion_code       VARCHAR(30)

MACHINE_GROUP
  group_id                 BIGSERIAL PK
  group_name               VARCHAR(50)   NOT NULL
  group_area               CHAR(3)       NOT NULL
```

### Model SPG — Line → Position (winder) → bobbin (v1.2)

Di SPG, satu **line** berisi banyak **position = winder** (unit efisiensi). Tiap doffing
satu winder menghasilkan 4/8/10/12 **bobbin**; tiap bobbin = **end/bobbin number** (grain
terhalus, dari bobbin tracking, bukan input). WO SPG = line + lot + posisi-dipakai; beberapa
WO bisa berbagi satu line (analog section A/B di TXT).

```sql
MACHINE_POSITION            -- winder di bawah line (SPG)
  mp_id                    BIGSERIAL PK
  mp_machine_id            BIGINT        NOT NULL   -- FK MACHINE_MASTER (line)
  mp_position_no           VARCHAR(10)   NOT NULL   -- nomor winder
  mp_bobbin_per_doff       INT                      -- 4/8/10/12
  mp_is_active             BOOLEAN       DEFAULT TRUE
  UNIQUE (mp_machine_id, mp_position_no)
```

- **TXT:** machine + section A/B (WO per section+lot).
- **SPG:** line + winder (WO per line+lot+posisi; bobbin/end dari ETL).
- **TWT:** per mesin+lot (tanpa group).

**MACH_DEPT konfirmasi:** `MACH_DEPT='TXT'` = mesin TXT, `MACH_DEPT='TWT'` = mesin TWT.

---

## Lot Master

Lot dibuat PPC saat create WO:

```sql
LOT_MASTER
  lm_lot_no                VARCHAR(30)   PK
  lm_item_code             VARCHAR(30)   NOT NULL
  lm_shade_code            VARCHAR(20)   NOT NULL
  lm_std_weight_full       DECIMAL(8,4)  NOT NULL  -- kg, untuk kalkulasi TRN_STS=0
  lm_std_weight_unfull     DECIMAL(8,4)  NOT NULL  -- kg, untuk kalkulasi TRN_STS=1
  lm_notes                 TEXT
  lm_created_by_ppc        BIGINT        NOT NULL
  lm_created_at            TIMESTAMPTZ   DEFAULT NOW()
  lm_updated_at            TIMESTAMPTZ   DEFAULT NOW()
```

**Lot key:** `item_code + shade_code`. Grade code tidak masuk key lot.
**Ratio validasi:** Full: 78–80%, Unfull: 20–22% dari total bobbin.

> ⚠️ **TRN_STS TXT/TWT:** 0=Full, 1=Unfull (kebalikan dari DOFF_OPTION SPG: 1=Full, 2=Unfull)

---

## Overrun Threshold Config

```sql
OVERRUN_THRESHOLD_CONFIG
  otc_id                   BIGSERIAL PK
  otc_level                VARCHAR(20)   NOT NULL  -- SYSTEM/MACHINE_GROUP/PRODUCT_TYPE/PRODUCT/WO
  otc_ref_id               BIGINT
  otc_threshold_unit       CHAR(4)       NOT NULL  -- PCT / DOFF
  otc_warning_value        DECIMAL(10,3) NOT NULL
  otc_block_value          DECIMAL(10,3) NOT NULL
  otc_notes                TEXT
  otc_is_active            BOOLEAN       DEFAULT TRUE
  otc_updated_at           TIMESTAMPTZ   DEFAULT NOW()
```

**Default:** Warning 3%, Block 6% (PCT). TXT = DOFF unit (1 doff = 1,200 kg TXT-1 / 600 kg TXT-2).

---

## Shift Config

| Shift | Jam |
|---|---|
| Shift 1 | 06.00 – 14.00 |
| Shift 2 | 14.00 – 22.00 |
| Shift 3 | 22.00 – 06.00 (+1) |
