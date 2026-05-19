---
title: "PRD — Costing Workflow Suite, Phase C: Parameter Entry, Validation & Calculation Engine"
version: "1.0"
status: "Draft"
phase: "C"
last_updated: "2026-05"
author: "[IT Leader]"
related:
  - "PRD_PhaseA.md"
  - "PRD_PhaseB.md"
  - "ERD_Master.md"
  - "GLOSSARY.md"
  - "INTEGRATION_CrossPhase.md"
---

# PRD — Phase C: Parameter Entry, Validation & Calculation Engine
## Costing Workflow Suite

> *Form workflow per departemen + Calculation Engine berbasis Go*
> Version 1.0 — Draft | May 2026

---

## 1. Executive Summary

Phase C menyelesaikan rangkaian Costing Workflow Suite dengan dua tujuan utama: pertama, menyediakan form entry untuk parameter cost per departemen dengan workflow validasi yang jelas; kedua, menjadi engine kalkulasi cost yang cepat dan akurat untuk seluruh product master di sistem. Engine ini akan menggantikan sistem Oracle package legacy yang lambat (menit hingga jam) dengan implementasi Go + PostgreSQL yang ditargetkan selesai dalam 1-2 menit untuk 12.000 product.

Phase C terhubung erat dengan Phase B (yang menyediakan product master, parameter master, dan static parameter values) dan Phase A (yang menampilkan progres parameter completion dan cost result kepada Marketing). Phase C menambahkan dimensi period: setiap parameter yang `is_period_dependent = true` punya value berbeda per bulan, dan calculation engine menghasilkan cost result per period yang di-track lengkap dengan audit trail.

## 2. Background & Problem Statement

### 2.1. Masalah Sistem Saat Ini

Calculation engine eksisting di Oracle menggunakan stored package dimana setiap parameter dihitung satu per satu per product. Dengan 12.000 product × 125 parameter, satu run calculation period bisa memakan waktu puluhan menit hingga beberapa jam. Selain lambat, beberapa masalah lain:

- Tidak ada visibility tentang status calculation (sukses/gagal per product).
- Tidak ada audit trail per period — sulit menjawab "kenapa cost product X di Mei beda dengan April?".
- Tidak ada handling cascade failure — kalau intermediate product gagal hitung, downstream product cost-nya tidak jelas.
- Update master data atau parameter membutuhkan investigation manual untuk tahu impact-nya.

### 2.2. Tujuan Phase C

1. Form entry parameter per departemen dengan UX yang mudah dan tracking completion otomatis.
2. Calculation engine Go yang cepat (target <2 menit untuk full run).
3. Period management dengan freeze capability (CLOSED) dan multiple runs per OPEN period.
4. Comprehensive audit trail — setiap calculation result punya snapshot 125 parameter values.
5. Cascade failure handling yang transparent — Marketing tahu kalau cost mereka PARTIAL.
6. Integrasi seamless dengan Phase A (activity timeline) dan Phase B (parameter master, BOM).

## 3. Goals & Non-Goals

### 3.1. Goals (In-Scope)

1. Period management dengan lifecycle OPEN → CLOSED, hanya 1 period OPEN sekaligus.
2. Calculation run management dengan multiple runs per OPEN period dan is_active flag.
3. Trigger calculation: scheduled daily (current period only) + manual via Admin button.
4. Parameter value entry form per departemen — dynamic (period-based) values.
5. Auto-detection completion: ketika semua required parameter terisi, Phase A request auto-transition ke PARAMETER_COMPLETE.
6. Calculation engine berbasis Go dengan two-source approach: stored value (ENTRY/JSONB) atau hardcoded Go function (CALCULATION/LOOKUP).
7. Cascade failure handling: failed dependency → RM cost contribution = 0, downstream marked PARTIAL.
8. Cost history visibility: 3 period terakhir untuk setiap product dalam chain BOM-nya.
9. Audit trail lengkap atas semua parameter changes dan master changes.
10. Dashboard calculation status: per period, per run, per product.
11. Cross-phase integration: surface parameter events di Phase A activity timeline.

### 3.2. Non-Goals (Out-of-Scope)

- Custom formula editor untuk admin — semua formula didefinisikan di Go code oleh tim IT.
- Per-product formula override — tidak ada path untuk override formula per product; jika satu product butuh perlakuan berbeda, dibuat sebagai parameter baru atau function baru.
- Approval workflow untuk master data changes — master changes langsung berlaku.
- Multi-currency cost calculation — hanya USD untuk MVP.
- Forecasting / what-if simulation — calculation hanya historical/current.
- Cost variance analysis / budget vs actual — out of scope, ditangani sistem terpisah.
- Integrasi langsung ke ERP untuk push cost result — Phase C menyimpan result di database, integrasi outbound TBD.

## 4. Key Concepts & Terminology

| **Term** | **Definition** |
|---|---|
| Period | Bulan kalender dalam format YYYYMM (e.g., "202605"). Unit of calculation. |
| Calculation Period | Record di `cost_calculation_period` dengan status OPEN atau CLOSED. |
| OPEN Period | Period yang masih bisa di-run calculation. Hanya 1 OPEN sekaligus. |
| CLOSED Period | Period yang sudah final. Tidak bisa di-run ulang. |
| Calculation Run | Eksekusi calculation engine. Satu period bisa punya multiple runs. |
| Active Run | Run yang hasilnya dianggap authoritative untuk period tersebut. Hanya 1 active per period. |
| Scheduled Trigger | Calculation otomatis daily oleh cron job, hanya untuk current OPEN period. |
| Manual Trigger | Calculation dipicu Admin via button (mis. setelah update parameter signifikan). |
| Required Parameter | Parameter yang `is_required_for_costing = true`. Wajib diisi sebelum costing. |
| Static Parameter | Parameter yang `is_period_dependent = false`. Value disimpan di `cost_product_parameter`. |
| Dynamic Parameter | Parameter yang `is_period_dependent = true`. Value disimpan di `cost_product_parameter_period`. |
| Calculation Result | Hasil calculation per product per run. Mengandung snapshot 125 param values di JSONB. |
| Cascade Failure | Failed product affect dependent products downstream — RM cost dari failed dep = 0. |
| Partial Result | Calculation completed tapi ada warning (missing param non-required, atau failed dep). |
| Two-Source Calculation | Engine menggunakan stored value (ENTRY/JSONB) atau memanggil Go function (CALCULATION/LOOKUP). Tidak ada per-product override. |
| Topological Sort | Urutan eksekusi yang menghormati dependency (parent terdalam dulu). |

## 5. Assumptions & Dependencies

1. Phase B sudah live: `cost_product_master`, `cost_parameter_master`, `cost_master_definition`, `cost_master_data`, `cost_product_parameter` semua tersedia dan terisi data.

2. Phase A sudah live: untuk integration parameter completion event dan activity timeline.

3. Calculation engine ditulis di Go (golang) version 1.22+ dan deploy sebagai service terpisah dari API server (atau worker dalam monorepo, TBD architecture).

4. PostgreSQL 14+ — untuk JSONB performance dan recursive CTE.

5. Cron scheduler untuk trigger daily (mekanisme: GitHub Actions, Kubernetes CronJob, atau systemd timer — TBD deployment).

6. Memory available untuk in-memory calculation: minimal 4 GB free (12k products × 125 params × ~64 bytes ≈ 100 MB working set).

7. Audit log retention 5 tahun (konsisten dengan Phase A/B policy).

## 6. Functional Requirements

### 6.1. Period Management

**FR-1: Open New Period**

- Admin dapat open period baru via Admin panel.
- Format input: YYYYMM (e.g., "202605").
- Constraint: hanya 1 OPEN period sekaligus. Jika ada OPEN period yang belum di-close, sistem reject.
- Audit log mencatat siapa yang open dan kapan.

**FR-2: Close Period**

- Admin dapat close OPEN period dengan opsional reason.
- Setelah CLOSED, tidak bisa lagi:
  - Trigger calculation run untuk period tersebut.
  - Update parameter values untuk period tersebut (untuk yang period-dependent).
- Calculation result untuk period CLOSED tetap visible dan queryable.
- Reopen tidak supported di MVP (escalation manual via DB jika benar-benar diperlukan).

**FR-3: Period Status Display**

- Status panel menampilkan: current OPEN period (jika ada), recent CLOSED periods, calculation run summary per period.

### 6.2. Calculation Engine

**FR-4: Scheduled Calculation Trigger**

- Cron job daily pada jam yang disepakati (default: 02:00 WIB).
- Scope: hanya current OPEN period.
- Behavior: create new `cost_calculation_run` dengan `trigger_type = SCHEDULED`, run engine, set as active jika sukses.

**FR-5: Manual Calculation Trigger**

- Admin dapat trigger calculation via button di Admin panel.
- Konfirmasi dialog: "Trigger calculation for period 202605?" dengan estimated time.
- Run berjalan async; user dapat monitor via status panel.

**FR-6: Calculation Engine Pipeline (Go)**

Engine melakukan 6 stage:

1. **Data Loader** — bulk load: parameter_master, master_data, product_parameter (static), product_parameter_period (target period). 5 queries, semua ke memory.

2. **Dependency Resolver** — build dependency graph dari calc registry (Go), topological sort. Detect inter-product dependency dari `cost_product_order_exploded`.

3. **Work Dispatcher** — distribute products ke goroutine pool (default size = NumCPU).

4. **Calculator** — per product, walk param dalam topological order. ENTRY/JSONB → read stored value; CALCULATION/LOOKUP → call Go function dari registry.

5. **Batch Writer** — buffer results, bulk insert ke `cost_calculation_result` via COPY-style.

6. **Audit & Status** — update `cost_calculation_run` dengan counters, errors, duration, git_commit.

**FR-7: Two-Source Calculation Approach**

Engine resolve nilai parameter dari salah satu dari dua sumber, tergantung `CPRM_function_type`:

- **Source 1 — Stored Value** (ENTRY, JSONB): Nilai diambil dari `cost_product_parameter` (static) atau `cost_product_parameter_period` (dynamic). User mengisi via form parameter entry.

- **Source 2 — Go Function** (CALCULATION, LOOKUP): Engine memanggil Go function yang teregistrasi via `CPRM_calc_function_key`. Function ada di Go code, di-define dan di-maintain oleh tim IT. Setiap perubahan formula = update Go function + deploy.

Tidak ada per-product formula override. Satu param = satu formula = berlaku sama untuk semua product yang menggunakan param tersebut. Jika ada kebutuhan satu product spesifik butuh perhitungan berbeda, dibuat sebagai parameter baru, bukan override.

**FR-8: Cascade Failure Handling**

Saat calculate product X yang punya component dengan reference_target = PRODUCT (Captive Cost):

- Engine cek status calculation result dari product dependency (Z).
- Jika Z status FAILED → X.rm_cost_from_Z = 0, X marked PARTIAL.
- Jika Z status PARTIAL → X juga inherit PARTIAL.
- Jika Z status SUCCESS → X gunakan Z.captive_cost normal.

Result mencatat:
- `CCRE_calc_status` = SUCCESS / PARTIAL / FAILED
- `CCRE_failed_dep_products` = daftar product_sys_id dependency yang gagal
- `CCRE_partial_reasons` = JSONB structured reasons

**FR-9: Run Activation**

- Run baru selesai sukses (atau partial) → otomatis di-set as active.
- Previous active run untuk period yang sama → set is_active = false.
- Atomic transition via `set_run_active()` function.

### 6.3. Parameter Entry Forms

**FR-10: Parameter Entry Form per Departemen**

- Setiap departemen punya landing page yang menampilkan: product list dengan progress completion, filterable.

- Form entry per product menampilkan parameters yang `owner_department` = departemen user dan `function_type` IN (ENTRY, JSONB).

- Field di-group berdasarkan `CPRM_display_group` (Spec, Machine, Grade, dll).

- Validation per field berdasarkan `data_type`: NUMERIC, TEXT, FLAG, JSON.

- Required indicator (asterisk) untuk parameter yang `is_required_for_costing = true`.

- Conditional display: param dengan `required_for_yarn_types` filter, hanya tampil jika yarn type product match.

**FR-11: Period-Dependent vs Static Routing**

- Form aware: param `is_period_dependent = false` → save ke `cost_product_parameter` (1 record).

- Param `is_period_dependent = true` → save ke `cost_product_parameter_period` (per period).

- UI menampilkan period selector untuk dynamic params.

**FR-13: Bulk Import Parameter Values**

- Admin dapat import param values via CSV/Excel untuk bulk update.

- Format: product_code, param_code, value, period (optional).

- Preview validation sebelum commit.

### 6.4. Phase A Integration — Parameter Completion

**FR-14: Auto-Transition to PARAMETER_COMPLETE**

- Saat user save parameter value (Phase B/C UI), service layer trigger check:
  - Find Phase A request yang link ke product ini (via `CPR_resolved_product_sys_id`).
  - Jika status request = PARAMETER_PENDING, call `get_missing_required_params(product_sys_id)`.
  - Jika hasil empty → auto-transition status ke PARAMETER_COMPLETE.

- State machine Phase A monotonic: COMPLETE tidak bounce back walau param berubah lagi.

**FR-15: Activity Timeline Surface**

- Setiap parameter change tercatat di `cost_audit_log`.

- Phase A request detail menampilkan event-event yang relevan ke product yang di-link:
  - Parameter value updates (CPP_*, CPPP_*).
  - Master data changes (CMSD_*) yang affect product ini.
  - Calculation runs untuk period yang berkaitan.

### 6.5. Cost Result Visibility (Phase A)

**FR-16: Cost Result Panel di Phase A Detail Request**

Di detail request Phase A, tab "Cost Result" menampilkan:

- Last 3 periods cost untuk product utama.
- Status per period: SUCCESS / PARTIAL / FAILED.
- Click-to-expand: BOM chain breakdown.

**FR-17: BOM Chain Cost Visualization**

- Untuk product utama, tampilkan urutan produk dalam BOM chain (RM → intermediate → FG).
- Untuk setiap product dalam chain: cost-nya, status calc-nya, dan ~80 parameter values yang relevan.
- Highlight node yang FAILED atau PARTIAL.
- Klik node → expand untuk lihat detail param + missing items + owner dept.

### 6.6. Master Data Management

**FR-18: Master Definition CRUD**

- Admin dapat create master type baru di `cost_master_definition`.
- Field: master_code, master_name, is_period_dependent, attributes_schema (optional JSONB schema).
- Master code tidak bisa diubah setelah create (immutable).

**FR-19: Master Data CRUD**

- Admin dapat create/edit/disable rows di `cost_master_data`.
- Form dinamis berdasarkan `attributes_schema` master definition (jika ada).
- Untuk period-dependent master: wajib isi period saat create.

**FR-20: Master Change Audit**

- Setiap UPDATE/INSERT di `cost_master_data` tercatat di `cost_audit_log`.
- Tidak ada approval workflow (langsung berlaku).
- Surface di Phase A timeline jika master change affect product yang di-link.

### 6.7. Dashboard & Monitoring

**FR-21: Calculation Run Dashboard**

- Display per period: total runs, success count, partial count, failed count, average duration.
- Run history: list of runs dengan status, trigger type, triggered by, duration.
- Filter: by period, by status.

**FR-22: Per-Product Calculation Status**

- Search product → lihat status calculation di 3 period terakhir.
- Drill-down ke detail: missing params, failed dependencies, computed values.

**FR-23: Department Completion Dashboard**

- Per departemen, persentase product yang sudah complete (all required params filled).
- Pending product list untuk department.
- Trend chart bulanan.

**FR-24: Master Health Dashboard**

- Per master: total rows, active rows, last update.
- Period coverage: untuk period-dependent masters, apakah current period sudah diisi semua.
- Stale data warning: master yang belum di-update di period current.

### 6.8. Audit Trail

**FR-25: Parameter Change Log**

- Semua mutasi pada CPP_, CPPP_, CMSD_ tercatat di cost_audit_log.
- Format: entity_type, entity_id, operation, before_data, after_data, user, timestamp.

**FR-26: Calculation Run Audit**

- Setiap run mencatat: git_commit (formula version), param_master_snapshot (function key mapping), error_summary.

- Memungkinkan investigasi: "kenapa cost April 2.45 tapi May 2.51?" → diff snapshot.

## 7. Data Model

### 7.1. Konvensi Penamaan

Phase C menggunakan column prefix convention yang sama dengan Phase A & B.

**Prefix Registry Phase C:**

| Prefix | Table Name |
|---|---|
| CCP_ | cost_calculation_period |
| CCR_ | cost_calculation_run |
| CCRE_ | cost_calculation_result |
| CPPP_ | cost_product_parameter_period |

### 7.2. Tabel — Detail

#### 7.2.1. cost_calculation_period (CCP_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CCP_period_id | SERIAL | PK | |
| CCP_period | VARCHAR(6) | UNIQUE NOT NULL | "202605" |
| CCP_status | VARCHAR(20) | NOT NULL | OPEN / CLOSED |
| CCP_opened_at | TIMESTAMPTZ | NOT NULL | |
| CCP_opened_by | VARCHAR(64) | NOT NULL | |
| CCP_closed_at | TIMESTAMPTZ | NULL | |
| CCP_closed_by | VARCHAR(64) | NULL | |
| CCP_close_reason | TEXT | NULL | |

Partial unique index: hanya 1 OPEN per database.

#### 7.2.2. cost_calculation_run (CCR_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CCR_run_id | BIGSERIAL | PK | |
| CCR_period | VARCHAR(6) | FK CCP, NOT NULL | |
| CCR_run_seq | INT | NOT NULL | 1, 2, 3, ... per period |
| CCR_is_active | BOOLEAN | DEFAULT false | Max 1 active per period |
| CCR_trigger_type | VARCHAR(20) | NOT NULL | MANUAL / SCHEDULED |
| CCR_triggered_by | VARCHAR(64) | NOT NULL | |
| CCR_status | VARCHAR(20) | NOT NULL | PENDING/RUNNING/SUCCESS/FAILED/PARTIAL/CANCELLED |
| CCR_total_products | INT | NOT NULL | |
| CCR_success_count / partial_count / failed_count | INT | NOT NULL | |
| CCR_started_at / ended_at | TIMESTAMPTZ | NULL | |
| CCR_duration_ms | INT | NULL | |
| CCR_git_commit | VARCHAR(40) | NULL | Code version |
| CCR_param_master_snapshot | JSONB | NULL | param_id → function_key snapshot |
| CCR_error_summary | JSONB | NULL | |

#### 7.2.3. cost_calculation_result (CCRE_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CCRE_result_id | BIGSERIAL | PK | |
| CCRE_run_id | BIGINT | FK CCR, NOT NULL | |
| CCRE_period | VARCHAR(6) | NOT NULL | Denormalized |
| CCRE_product_sys_id | BIGINT | FK CPM, NOT NULL | |
| CCRE_calc_status | VARCHAR(20) | NOT NULL | SUCCESS/PARTIAL/FAILED |
| CCRE_failed_param_ids | JSONB | NULL | [51, 79] |
| CCRE_failed_dep_products | JSONB | NULL | [12345, 67890] |
| CCRE_partial_reasons | JSONB | NULL | Structured |
| CCRE_param_values | JSONB | NOT NULL | Snapshot 125 params |
| CCRE_captive_cost | DECIMAL(18,4) | NULL | PARAM 101 quick access |
| CCRE_delivery_cost | DECIMAL(18,4) | NULL | PARAM 102 quick access |
| CCRE_calculated_at | TIMESTAMPTZ | NOT NULL | |
| CCRE_duration_us | INT | NULL | |

UNIQUE (CCRE_run_id, CCRE_product_sys_id).

#### 7.2.4. cost_product_parameter_period (CPPP_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CPPP_value_id | BIGSERIAL | PK | |
| CPPP_product_sys_id | BIGINT | FK CPM, NOT NULL | |
| CPPP_param_id | INT | FK CPRM, NOT NULL | |
| CPPP_period | VARCHAR(6) | NOT NULL | |
| CPPP_value_numeric / text / flag / json | typed | NULL | One of these filled |
| CPPP_filled_at / by | TIMESTAMPTZ/VARCHAR | NOT NULL | |
| CPPP_updated_at / by | TIMESTAMPTZ/VARCHAR | NOT NULL | |

UNIQUE (CPPP_product_sys_id, CPPP_param_id, CPPP_period).

### 7.3. View & Helper Functions

**View: `v_cost_latest`** — JOIN result × active run, get current cost per product per period.

**Functions**:
- `open_calculation_period(period, user_id)` — create new OPEN period with enforcement.
- `close_calculation_period(period, user_id, reason)` — close period.
- `get_missing_required_params(product_sys_id, period)` — return missing params (used by Phase A integration).
- `set_run_active(run_id)` — atomic activation.

### 7.4. Relationship Summary

```
cost_calculation_period 1:N cost_calculation_run 1:N cost_calculation_result
                                                            │
                                                            FK CPM (Phase B)

cost_product_master (Phase B) 1:N cost_product_parameter_period
                                       │
                                       FK CPRM (Phase B)

Phase A request → CPR_resolved_product_sys_id → CPM → CCRE (latest active run)

cost_audit_log (shared) ← all CPP/CPPP/CMSD changes
```

## 8. Non-Functional Requirements

### 8.1. Performance

- **Full calculation run** (12k products, 125 params): target <2 menit.
- **Per-product calculation**: <50ms (in-memory, no DB roundtrip).
- **Parameter entry save**: <500ms (single product, single param).
- **Cost result query** for Phase A: <1 detik (active run).
- **Master data save**: <500ms.

### 8.2. Scalability

- Mendukung 50.000 product, 200.000 parameter values per period.
- Parallel processing: scale dengan NumCPU (8-16 cores typical).
- Memory: 4 GB working set untuk full data load.

### 8.3. Availability

- Calculation runs idempotent — bisa di-retry.
- Failed run tidak corrupt data (transactional).
- Backup database harian, retention 30 hari.

### 8.4. Security

- SSO authentication.
- RBAC: Admin (manage period, master, run trigger), Dept Lead (lihat semua + manual trigger), User (input own department params).
- Audit log immutable.

### 8.5. Platform

- Backend: Go 1.22+, PostgreSQL 14+.
- Calculation engine: separate service atau worker dalam monorepo (TBD).
- Frontend: React (konsisten Phase A & B).
- Scheduler: TBD (Kubernetes CronJob, GitHub Actions, atau systemd).

### 8.6. Internationalization

- UI Bahasa Indonesia default.
- Timestamp UTC stored, WIB displayed.
- Period format universal: YYYYMM.

## 9. Success Metrics

- Full calculation run completes <2 menit (vs hours di sistem lama).
- 95% products SUCCESS status di first run setelah parameter completion.
- Zero data inconsistency antara Phase A status display dan actual completion status.
- Audit trail: 100% mutation tracked dan queryable.
- Department parameter entry: <5 menit per product (vs jam di sistem lama yang manual).

## 10. Risks & Mitigations

| **Risk** | **Severity** | **Mitigation** |
|---|---|---|
| Calculation engine bug → wrong cost result | High | Unit test untuk setiap calc function; regression test dengan snapshot dari Oracle sistem lama |
| Memory exhaustion saat full load | Medium | Streaming option untuk dataset >50k product; configurable batch size |
| Cascade failure tidak terdeteksi | Medium | Explicit status propagation; integration test untuk BOM chain |
| Master data inconsistency antar period | Medium | Effective dating + JSONB snapshot di calculation result (defense-in-depth) |
| Period accidentally closed | Low | Reopen requires DB admin escalation (intentional friction) |
| Concurrent manual trigger | Low | DB advisory lock; UI disable button selama run RUNNING |
| Formula change drift dari dependency table | Medium | Auto-sync from Go registry on deploy; CI check |

## 11. Implementation Phasing

**Phase C1 — Foundation**
- Schema DDL: CCP, CCR, CCRE, CPPP.
- Period management CRUD (FR-1, FR-2, FR-3).
- Parameter entry form skeleton (FR-10, FR-11).

**Phase C2 — Calculation Engine Core**
- Go engine pipeline 6-stage (FR-6).
- Hardcoded calc functions untuk 25 calculation params.
- Cascade failure handling (FR-8).
- Manual trigger (FR-5).
- Run activation (FR-9).

**Phase C3 — Scheduled & Phase A Integration**
- Scheduled trigger daily (FR-4).
- Phase A auto-transition (FR-14).
- Activity timeline integration (FR-15).
- Cost result panel (FR-16).

**Phase C4 — Visualization & Dashboards**
- BOM chain cost visualization (FR-17).
- Calculation run dashboard (FR-21).
- Department completion dashboard (FR-23).

**Phase C5 — Advanced & Admin**
- Master data CRUD (FR-18, FR-19, FR-20).
- Master health dashboard (FR-24).
- Bulk import (FR-13).

**Phase C6 — Hardening & Optimization**
- Performance tuning untuk full run <2 menit.
- Error handling & retry logic.
- UAT, security review, prod rollout.

## 12. Open Questions

1. **Scheduler implementation** — Kubernetes CronJob, GitHub Actions Scheduled, atau systemd timer? Depends on deployment platform decision.

2. **Calculation engine deployment** — separate microservice atau worker dalam monorepo backend?

3. **Period reopen capability** — manual via DB SQL acceptable, atau perlu UI flow (with stricter audit)?

4. **Notification on calc completion** — email Admin saat full run selesai? Slack? Atau cukup dashboard?

5. **Master data versioning** — selain JSONB snapshot di calc result, perlu juga `effective_from/to` di CMSD untuk audit trail? (Defense-in-depth.)

6. **Bulk import format** — CSV simple atau Excel template dengan validation?

7. **Param "Ask"-flagged** — kapan revisit definisi formula untuk PARAM 14, 24, 57, 65, 67?

## 13. Appendix

### 13.1. Calculation Function Naming Convention

```
calc{ParamCamelCase}

Examples:
PARAM 31 Net Bobbin Weight     → calcNetBobbinWeight
PARAM 34 Captive Box Weight    → calcCaptiveBoxWeight
PARAM 87 Total Conversion      → calcTotalConversion
PARAM 101 Captive Final        → calcCaptiveFinal
PARAM 102 Delivery Final       → calcDeliveryFinal
```

Naming registered di `cost_parameter_master.CPRM_calc_function_key`.

### 13.2. Go Engine Architecture

Detail di dokumen terpisah: `architecture/CALCULATION_ENGINE_BLUEPRINT.md`

### 13.3. Glossary Singkatan

- BOM — Bill of Materials
- CDC — Change Data Capture
- CRUD — Create, Read, Update, Delete
- CTE — Common Table Expression
- ERD — Entity Relationship Diagram
- FG — Finished Goods
- FK — Foreign Key
- JSONB — Binary JSON (PostgreSQL)
- MVP — Minimum Viable Product
- PK — Primary Key
- PRD — Product Requirements Document
- RBAC — Role-Based Access Control
- RM — Raw Material
- SSO — Single Sign-On
- TBD — To Be Determined

### 13.4. Document Revision History

| **Version** | **Date** | **Description** | **Author** |
|---|---|---|---|
| 1.0 | May 2026 | Initial draft Phase C — Parameter Entry, Validation & Calculation Engine | — |
