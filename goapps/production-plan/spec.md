# PPC Phase 1 — Technical Specification

> Draft dari PRD. Akan diupdate setelah gap.md selesai (Claude Code baca codebase).
> Ini adalah spec yang langsung bisa dikode oleh developer.

---

## 1. Database Schema

### Migration Naming Convention

```
Cek nomor migration terakhir di migrations/ sebelum mulai:
  ls migrations/ | sort | tail -1

Format: 000XXX_create_ppc_yyy.up.sql
```

### DDL Lengkap

Refer ke `/docs/goapps/production-plan/12-schema.md` untuk DDL lengkap.

**Index tambahan yang perlu dibuat:**

```sql
-- wo_production_actual: query by date range
CREATE INDEX idx_wpa_wo_date ON wo_production_actual(wpa_wo_id, wpa_date);

-- production_demand: query by month + status (sering dipakai di morning review)
CREATE INDEX idx_pd_month_status ON production_demand(pd_month, pd_status);

-- sales_order_staging: query unpulled SO
CREATE INDEX idx_sos_unpulled ON sales_order_staging(sos_pulled_to_demand_id)
    WHERE sos_pulled_to_demand_id IS NULL;

-- etl_watermark: lookup by table name
CREATE TABLE etl_watermark (
    ewm_table_name   VARCHAR(50) PRIMARY KEY,
    ewm_last_run     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    ewm_updated_at   TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

---

## 2. Oracle ETL Spec

### Table: PPC_TXT_PRODUCTION → wo_production_actual

**Query Oracle:**
```sql
SELECT
    LOT_NO, MACHINE_NO, AREA,
    TRN_DATE, TRN_SHIFT, PROD_DATE, DOFF_NO,
    TOTAL_BOBBINS, FULL_BOBBINS, UNFULL_BOBBINS,
    NORMAL_BOBS, DOWNGRADE_BOBS, PENDING_BOBS, PACK_CEK_BOBS,
    LAST_UPDATED
FROM MGTDAT.PPC_TXT_PRODUCTION
WHERE LAST_UPDATED > :watermark
ORDER BY LAST_UPDATED ASC
```

**Field mapping ke wo_production_actual:**

| Oracle | PostgreSQL | Type | Catatan |
|---|---|---|---|
| LOT_NO | - | - | Match ke wo.wo_lot_no |
| MACHINE_NO | - | - | Match ke machine_master.machine_no |
| TRN_DATE | wpa_date | DATE | Tanggal produksi |
| TRN_SHIFT | wpa_shift | CHAR(1) | "1"/"2"/"3" |
| TOTAL_BOBBINS | wpa_total_bobbins | INT | Dari TYPE=1 |
| FULL_BOBBINS | wpa_full_bobbins | INT | ⚠️ TRN_STS=0 |
| UNFULL_BOBBINS | wpa_unfull_bobbins | INT | ⚠️ TRN_STS=1 |
| NORMAL_BOBS | wpa_normal_bobs | INT | TQM lulus |
| DOWNGRADE_BOBS | wpa_downgrade_bobs | INT | TYPE=7 |
| PENDING_BOBS | wpa_pending_bobs | INT | Di-hold TQM |
| PACK_CEK_BOBS | wpa_pack_cek_bobs | INT | Handover packing |
| LAST_UPDATED | wpa_synced_at | TIMESTAMPTZ | Waktu sync |

**⚠️ KRITIS — TRN_STS TXT/TWT:**
```
TRN_STS = 0 → FULL bobbin  (BUKAN 1)
TRN_STS = 1 → UNFULL bobbin (BUKAN 0)
Ini kebalikan dari DOFF_OPTION SPG (1=Full, 2=Unfull)
```

**Kalkulasi qty:**
```go
calculatedQtyKg = (fullBobbins * stdWeightFull) + (unfullBobbins * stdWeightUnfull)
// stdWeight dari lot_master.lm_std_weight_full/unfull
```

**UPSERT PostgreSQL:**
```sql
INSERT INTO wo_production_actual (
    wpa_wo_id, wpa_date, wpa_shift, wpa_area,
    wpa_total_bobbins, wpa_full_bobbins, wpa_unfull_bobbins,
    wpa_normal_bobs, wpa_downgrade_bobs, wpa_pending_bobs, wpa_pack_cek_bobs,
    wpa_calculated_qty_kg, wpa_qty_source, wpa_sync_status, wpa_synced_at
) VALUES (...)
ON CONFLICT (wpa_wo_id, wpa_date, wpa_shift)
DO UPDATE SET
    wpa_total_bobbins   = EXCLUDED.wpa_total_bobbins,
    wpa_full_bobbins    = EXCLUDED.wpa_full_bobbins,
    wpa_normal_bobs     = EXCLUDED.wpa_normal_bobs,
    wpa_downgrade_bobs  = EXCLUDED.wpa_downgrade_bobs,
    wpa_pending_bobs    = EXCLUDED.wpa_pending_bobs,
    wpa_calculated_qty_kg = EXCLUDED.wpa_calculated_qty_kg,
    wpa_qty_source      = CASE
        WHEN wpa_production_actual.wpa_qty_source = 'MANUAL_OVERRIDE'
        THEN 'MANUAL_OVERRIDE'  -- jangan overwrite manual override
        ELSE 'ETL_SUGGEST'
    END,
    wpa_synced_at       = EXCLUDED.wpa_synced_at
-- JANGAN update wpa_manual_reason kalau sudah MANUAL_OVERRIDE
```

---

### Table: MGT_SO_PENDING_WEB → sales_order_staging

**Query Oracle:**
```sql
SELECT
    PEND_CUST_CODE, PEND_CUST_NAME, PEND_CONTRACT_NO, PEND_CONTRACT_DT,
    PEND_CTRT_SYS_ID, PEND_ITEM_CODE, PEND_ITEM_NAME,
    PEND_GRADE_CODE_1, PEND_GRADE_CODE_2, PEND_GRADE_NAME,
    PEND_SO_QTY, PEND_DEL_QTY, PEND_QTY,
    PEND_DEL_DT, PEND_SHIP_DT_CHR, PEND_MERGE_NO,
    PEND_TERM, PEND_RATE, PEND_STS, PEND_CURR_CODE,
    PEND_OUTSTANDING, PEND_PALLET, PEND_ENDUSE, PEND_MIX_FLAG,
    PEND_ANOT, PEND_REMARKS
FROM MGTDAT.MGT_SO_PENDING_WEB
ORDER BY PEND_CUST_CODE, PEND_CONTRACT_NO
```

**Mode ETL:** Full replace
```go
// 1. BEGIN TRANSACTION
// 2. TRUNCATE sales_order_staging (preserve pulled records!)
// 3. INSERT semua dari Oracle
// 4. COMMIT

// PENTING: sos_pulled_to_demand_id harus dipreserve
// Sebelum TRUNCATE, simpan map: contract_no → pulled_demand_id
// Setelah INSERT, update kembali pulled_to_demand_id
```

---

## 3. API Spec — gRPC

### CreateDemand

```protobuf
message CreateDemandRequest {
    string type = 1;           // CONTRACT / MTS / SAMPLE
    string sub_type = 2;       // CF_EXPORT / NEW_EXPORT / LOCAL / INTERNAL
    string source = 3;         // ORION_PULL / MANUAL
    int64 cpm_product_sys_id = 4;
    string qty_original = 5;   // decimal as string
    string deadline = 6;       // ISO 8601 date
    string grade_requirement = 7; // AX_ONLY / AX_AM_CLAUSE / NONE
    optional string ax_min_pct = 8;
    optional string am_max_pct = 9;
    optional int64 sos_ref = 10;  // kalau source = ORION_PULL
    optional string contract_no = 11;
    optional int64 customer_id = 12;
}
```

### SubmitWO

```protobuf
message SubmitWORequest {
    int64 wo_id = 1;
    WOParameterInput parameter = 2;  // parameter teknis dari PPC
}

message WOParameterInput {
    optional string speed = 1;
    optional string nozzle = 2;
    optional string oil = 3;
    optional string disc = 4;
    optional string bar = 5;
    optional string air = 6;
    optional string opu = 7;
    optional string twist = 8;
    optional string notes = 9;
}
```

### OverrideWOActual

```protobuf
message OverrideWOActualRequest {
    int64 wo_id = 1;
    string date = 2;           // ISO 8601 date
    string shift = 3;          // "1" / "2" / "3"
    string calculated_qty_kg = 4; // nilai baru
    string reason = 5;         // WAJIB diisi
}
```

---

## 4. Validation Rules

### ProductionDemand

| Field | Rule |
|---|---|
| `pd_type` | enum: CONTRACT / MTS / SAMPLE |
| `pd_sub_type` | CF_EXPORT/NEW_EXPORT/LOCAL kalau type=CONTRACT, INTERNAL kalau MTS |
| `pd_qty_original` | > 0 |
| `pd_qty_remaining` | >= 0, <= pd_qty_original |
| `pd_deadline` | tidak boleh di masa lalu |
| `pd_grade_requirement` | enum: AX_ONLY / AX_AM_CLAUSE / NONE |
| `pd_ax_min_pct` | required kalau grade_req = AX_AM_CLAUSE, 0-100 |
| `pd_am_max_pct` | required kalau grade_req = AX_AM_CLAUSE, 0-100 |
| `pd_month` | format YYYY-MM |

### WorkOrder

| Field | Rule |
|---|---|
| `wo_area_code` | enum: TXT / SPG / TWT |
| `wo_qty_target` | > 0 |
| `wo_deadline` | tidak boleh di masa lalu |
| `wo_lot_no` | harus ada di lot_master |
| `wo_machine_id` | harus ada di machine_master, area match wo_area_code |
| `wo_revision_no` | auto-increment dari ref_id |

### CarryForward SPLIT

```
Validasi: SUM(qty demand baru) <= pd_qty_remaining dari demand asal
Error: "ppc: split qty melebihi remaining qty demand"
```

---

## 5. Suggest Logic Spec

```
Priority chain untuk wpa_calculated_qty_kg:

P1: WO_GRADE_ACTUAL ada (packing selesai)
    qty = SUM(wga_total_qty_kg) WHERE wga_wo_id = wo.id
    source = "PACKING_DONE"

P2: wpa_normal_bobs dari ETL TXT (QC released)
    qty = (normal_bobs × lm_std_weight_full) +
          (rel_unfull_bobs × lm_std_weight_unfull)
    source = "QC_RELEASED"
    condition: wpa_normal_bobs > 0

P3: wpa_transferred_bobs dari ETL SPG (Phase 2)
    qty = transferred_bobs × weight_per_bob
    source = "SPG_TRANSFERRED"

P4: wpa_total_bobbins dari ETL TXT (semua transferred)
    qty = (full_bobbins × lm_std_weight_full) +
          (unfull_bobbins × lm_std_weight_unfull)
    source = "TXT_TRANSFERRED"
    condition: wpa_total_bobbins > 0

P5: SPG doff estimate (Phase 2)
    source = "DOFF_ESTIMATE"

Kalau tidak ada data sama sekali:
    return nil, source = "NO_DATA"
```

---

## 6. Over-production Threshold Spec

```
Hierarchy resolusi (paling spesifik menang):

1. WO level: overrun_threshold_config WHERE otc_level='WO' AND otc_ref_id=wo_id
2. Product: otc_level='PRODUCT' AND otc_ref_id=cpm_product_sys_id
3. Product type: otc_level='PRODUCT_TYPE' AND otc_ref_id=product_type_id
4. Machine group: otc_level='MACHINE_GROUP' AND otc_ref_id=machine_group_id
5. System: otc_level='SYSTEM'

Default system: warning=3%, block=6% (PCT)
TXT default: warning=1 doff, block=1 doff (DOFF unit)
  TXT-1 doff = 1200 kg
  TXT-2 doff = 600 kg

Logic:
  actual_qty > target_qty * (1 + block_threshold) → BLOCKED (butuh PM override)
  actual_qty > target_qty * (1 + warning_threshold) → WARNING (notifikasi PPC)
  actual_qty <= target_qty * (1 + warning_threshold) → OK
```

---

## 7. Background Workers

```
Worker 1: TxtProductionETLWorker
  interval: ETL_INTERVAL_MINUTES (default 15)
  action: sync PPC_TXT_PRODUCTION → wo_production_actual

Worker 2: SoStagingETLWorker
  interval: ETL_SO_INTERVAL_MINUTES (default 30)
  action: full replace MGT_SO_PENDING_WEB → sales_order_staging

Worker 3: AutoApproveWorker
  interval: 1 menit
  action: auto-approve WO yang submitted > AUTO_APPROVE_HOURS (default 4)

Semua workers:
  - Jalan di goroutine terpisah
  - Graceful shutdown via context cancellation
  - Log error tapi tidak crash kalau 1 worker gagal
  - Metrics via Prometheus (worker_run_total, worker_error_total, worker_duration_seconds)
```

---

## 8. Dependency yang Perlu Dicek di go.mod

> Claude Code: cek go.mod sebelum mulai, tambahkan yang belum ada

```
Kemungkinan perlu ditambah:
  github.com/godror/godror         ← Oracle driver
  github.com/shopspring/decimal    ← decimal untuk qty calculation

Kemungkinan sudah ada (cek dulu):
  github.com/jackc/pgx/v5          ← PostgreSQL driver (sudah pasti ada)
  go.opentelemetry.io/otel         ← tracing (sudah pasti ada)
  github.com/rs/zerolog            ← logging (sudah pasti ada)
  google.golang.org/grpc           ← gRPC (sudah pasti ada)
  github.com/grpc-ecosystem/grpc-gateway/v2  ← sudah pasti ada
```

---

## 9. File Structure yang Akan Dibuat

```
internal/ppc/
  domain/
    entity/
      work_order.go
      production_demand.go
      plan_item.go
      wo_production_actual.go
      wo_grade_actual.go
      lot_master.go
      machine_master.go
    repository/
      wo_repository.go
      demand_repository.go
      plan_item_repository.go
      so_staging_repository.go
      etl_watermark_repository.go
    valueobject/
      area_code.go         ← TXT / SPG / TWT
      wo_status.go         ← DRAFT / SUBMITTED / ...
      demand_status.go
      grade_req.go         ← AX_ONLY / AX_AM_CLAUSE / NONE
  application/
    usecase/
      create_demand.go
      pull_from_orion.go
      carry_forward.go
      create_plan_item.go
      create_wo.go
      submit_wo.go
      approve_wo.go
      suggest_wo_actual.go
      override_wo_actual.go
      morning_review.go
    dto/
      demand_dto.go
      wo_dto.go
      plan_item_dto.go
  infrastructure/
    postgres/
      wo_repository.go
      demand_repository.go
      plan_item_repository.go
      so_staging_repository.go
      etl_watermark_repository.go
    oracle/
      client.go
      worker/
        txt_worker.go
        so_worker.go
        auto_approve_worker.go
    grpc/
      server.go
  delivery/
    grpc/
      ppc_handler.go
    rest/
      gateway.go
  CLAUDE.md
  TASKS.md
  PREFLIGHT.md

migrations/ppc/
  000XXX_create_sales_order_staging.up.sql
  000XXX_create_sales_order_staging.down.sql
  ... (lihat TASKS.md T001 untuk list lengkap)

proto/ppc/v1/
  ppc.proto
  demand.proto
  work_order.proto
  plan_item.proto
  master.proto

scripts/
  preflight.sh
```

---

## 10. Testing Spec

```go
// Setiap use case harus punya table-driven test
// Contoh:

func TestSuggestWOActual(t *testing.T) {
    tests := []struct {
        name     string
        setup    func(*MockWORepo)
        expected string  // source
    }{
        {
            name: "P1: packing done",
            setup: func(m *MockWORepo) {
                m.On("ListGradeActual", ...).Return([]*WOGradeActual{
                    {WgaTotalQtyKg: decimal.NewFromFloat(500)},
                }, nil)
            },
            expected: "PACKING_DONE",
        },
        {
            name: "P4: txt transferred",
            setup: func(m *MockWORepo) {
                m.On("ListGradeActual", ...).Return(nil, nil)
                m.On("GetProductionActual", ...).Return(&WOProductionActual{
                    TotalBobbins: ptr(int32(100)),
                    FullBobbins:  ptr(int32(80)),
                    UnfullBobbins: ptr(int32(20)),
                }, nil)
            },
            expected: "TXT_TRANSFERRED",
        },
    }
    // ...
}
```
