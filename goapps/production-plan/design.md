# PPC Phase 1 — Technical Design

> Draft dari PRD. Akan diupdate oleh Claude Code setelah gap analysis vs codebase.
> Refer ke: /docs/goapps/production-plan/ untuk business requirements.

---

## 1. Service Architecture

PPC adalah service baru di `goapps-backend`, mengikuti pattern yang sama dengan
`internal/finance/` dan `internal/iam/`.

```
goapps-backend/
  internal/
    ppc/
      domain/
        entity/          ← business entities
        repository/      ← interfaces (tidak ada dependency ke infra)
        valueobject/     ← enums, constants, value objects
      application/
        usecase/         ← business logic, orchestrate domain + infra
        dto/             ← request/response structs
      infrastructure/
        postgres/        ← pgx v5 repository implementations
        oracle/          ← Oracle ETL client + workers
        grpc/            ← gRPC server setup
      delivery/
        grpc/            ← gRPC handlers (thin layer, delegate ke usecase)
        rest/            ← gRPC-Gateway REST handlers
      CLAUDE.md          ← context file untuk Claude Code
```

---

## 2. Domain Entities

### Core Entities Phase 1

```go
// WorkOrder — instruksi produksi per mesin per lot
type WorkOrder struct {
    ID               int64
    AreaCode         AreaCode       // TXT / SPG / TWT
    TransNo          string
    PlanItemID       int64
    MachineID        int64
    LotNo            string
    QtyTarget        decimal.Decimal
    Deadline         time.Time
    Status           WOStatus
    RevisionNo       int
    RefID            *int64         // FK ke WO sebelumnya (revision)
    PCApprovedAt     *time.Time
    PMApprovedAt     *time.Time
    QtyFinal         *decimal.Decimal
    PlanChangeFlag   bool
    CreatedBy        int64
    CreatedAt        time.Time
    UpdatedAt        time.Time
}

// WO Status machine
type WOStatus string
const (
    WOStatusDraft      WOStatus = "DRAFT"
    WOStatusSubmitted  WOStatus = "SUBMITTED"
    WOStatusApproved   WOStatus = "APPROVED"
    WOStatusScheduled  WOStatus = "SCHEDULED"
    WOStatusChangeover WOStatus = "CHANGEOVER"
    WOStatusRunning    WOStatus = "RUNNING"
    WOStatusCompleted  WOStatus = "COMPLETED"
    WOStatusClosed     WOStatus = "CLOSED"
)

// ProductionDemand — komitmen bisnis ke customer
type ProductionDemand struct {
    ID                 int64
    Type               DemandType     // CONTRACT / MTS / SAMPLE
    SubType            DemandSubType  // CF_EXPORT / NEW_EXPORT / LOCAL / INTERNAL
    Source             DemandSource   // ORION_PULL / MANUAL / CARRY_FORWARD
    CpmProductSysID    int64
    QtyOriginal        decimal.Decimal
    QtyRemaining       decimal.Decimal
    Deadline           time.Time
    GradeRequirement   GradeReq       // AX_ONLY / AX_AM_CLAUSE / NONE
    Status             DemandStatus
    Month              string         // YYYY-MM
    CarryFromID        *int64
    SosRef             *int64
    ConfirmedBy        *int64
    ConfirmedAt        *time.Time
    CreatedBy          int64
    CreatedAt          time.Time
    UpdatedAt          time.Time
}

// WOProductionActual — qty aktual per date+shift, dari ETL Oracle
type WOProductionActual struct {
    ID                int64
    WoID              int64
    Date              time.Time
    Shift             string         // "1" / "2" / "3"
    Area              AreaCode
    // TXT/TWT: ⚠️ TRN_STS 0=Full, 1=Unfull
    TotalBobbins      *int32
    FullBobbins       *int32         // TRN_STS=0
    UnfullBobbins     *int32         // TRN_STS=1
    NormalBobs        *int32
    DowngradeBobs     *int32
    PendingBobs       *int32
    PackCekBobs       *int32
    // SPG
    GrossBobbins      *int32
    TransferredBobs   *int32
    CutBobbins        *int32
    NotTransfer       *int32
    NormalBobsSpg     *int32
    DowngradeBobsSpg  *int32
    NotCheckedBobs    *int32
    WeightPerBob      *decimal.Decimal
    // Calculated
    CalculatedQtyKg   *decimal.Decimal
    QtySource         string         // ETL_SUGGEST / MANUAL_OVERRIDE
    ManualReason      *string
    SyncStatus        string         // OK / SYNC_FAILED / PENDING
    SyncedAt          *time.Time
    LastEditedBy      *int64
    LastEditedAt      *time.Time
}
```

---

## 3. Repository Interfaces

```go
// WorkOrderRepository
type WorkOrderRepository interface {
    Create(ctx context.Context, wo *WorkOrder) error
    GetByID(ctx context.Context, id int64) (*WorkOrder, error)
    GetByTransNo(ctx context.Context, transNo string) (*WorkOrder, error)
    ListByStatus(ctx context.Context, status WOStatus, area AreaCode) ([]*WorkOrder, error)
    ListByMachine(ctx context.Context, machineID int64) ([]*WorkOrder, error)
    Update(ctx context.Context, wo *WorkOrder) error
    UpdateStatus(ctx context.Context, id int64, status WOStatus) error

    // Parameter
    CreateParameter(ctx context.Context, p *WOParameter) error
    GetParameter(ctx context.Context, woID int64) (*WOParameter, error)
    UpdateParameter(ctx context.Context, p *WOParameter) error

    // Execution
    CreateExecution(ctx context.Context, e *WOExecution) error
    ListExecution(ctx context.Context, woID int64) ([]*WOExecution, error)

    // Production Actual
    UpsertProductionActual(ctx context.Context, a *WOProductionActual) error
    ListProductionActual(ctx context.Context, woID int64) ([]*WOProductionActual, error)

    // Grade Actual
    UpsertGradeActual(ctx context.Context, g *WOGradeActual) error
    ListGradeActual(ctx context.Context, woID int64) ([]*WOGradeActual, error)
}

// DemandRepository
type DemandRepository interface {
    Create(ctx context.Context, d *ProductionDemand) error
    GetByID(ctx context.Context, id int64) (*ProductionDemand, error)
    ListByMonth(ctx context.Context, month string) ([]*ProductionDemand, error)
    ListByStatus(ctx context.Context, status DemandStatus) ([]*ProductionDemand, error)
    Update(ctx context.Context, d *ProductionDemand) error
    UpdateQtyRemaining(ctx context.Context, id int64, qty decimal.Decimal) error
}

// SalesOrderStagingRepository
type SalesOrderStagingRepository interface {
    BulkReplace(ctx context.Context, orders []*SalesOrderStaging) error
    ListUnpulled(ctx context.Context) ([]*SalesOrderStaging, error)
    MarkAsPulled(ctx context.Context, id int64, demandID int64) error
}

// ETLWatermarkRepository
type ETLWatermarkRepository interface {
    GetWatermark(ctx context.Context, tableName string) (time.Time, error)
    SetWatermark(ctx context.Context, tableName string, t time.Time) error
}
```

---

## 4. ETL Architecture

```
Oracle 11g (MGTDAT schema)
  PPC_TXT_PRODUCTION    → ETL setiap 15 menit (incremental, watermark LAST_UPDATED)
  PPC_SPG_PRODUCTION    → ETL setiap 15 menit (Phase 2)
  PPC_GRADE_ACTUAL      → ETL setiap 15 menit (Phase 3)
  MGT_SO_PENDING_WEB    → ETL setiap 30 menit (full replace)
        ↓
  ETL Worker (Go goroutines)
    TxtProductionWorker
    SoStagingWorker
        ↓
  PostgreSQL (goapps_dev)
    wo_production_actual  (UPSERT by wo_id + date + shift)
    sales_order_staging   (TRUNCATE + INSERT)
```

### ETL Worker Pattern

```go
type ETLWorker struct {
    oracle    OracleClient
    postgres  PostgresClient
    watermark ETLWatermarkRepository
    interval  time.Duration
}

func (w *ETLWorker) Start(ctx context.Context) {
    ticker := time.NewTicker(w.interval)
    for {
        select {
        case <-ticker.C:
            if err := w.run(ctx); err != nil {
                log.Error().Err(err).Msg("ETL run failed")
                // tidak crash — log dan lanjut run berikutnya
            }
        case <-ctx.Done():
            return
        }
    }
}
```

### TRN_STS Mapping (KRITIS — jangan tertukar)

```go
// TXT/TWT: TRN_STS 0=Full, 1=Unfull ← KEBALIKAN dari intuisi
// SPG DOFF_OPTION: 1=Full, 2=Unfull

func calcQtyTxt(full, unfull int32, stdFull, stdUnfull decimal.Decimal) decimal.Decimal {
    // full = TRN_STS=0 count
    // unfull = TRN_STS=1 count
    return decimal.NewFromInt32(full).Mul(stdFull).
        Add(decimal.NewFromInt32(unfull).Mul(stdUnfull))
}

func calcQtySpg(transferred int32, weightPerBob decimal.Decimal) decimal.Decimal {
    return decimal.NewFromInt32(transferred).Mul(weightPerBob)
}
```

---

## 5. Approval Flow

### Dual Approval (PC + PM Paralel)

```
WO SUBMITTED
    ├── goroutine 1: PC approval timer (4 jam)
    │     if no response in 4h → auto-approve PC
    └── goroutine 2: PM approval timer (4 jam)
          if no response in 4h → auto-approve PM

WO APPROVED = keduanya selesai (approve atau auto-approve)
```

### Auto-approve Worker

```go
// Background worker cek pending approvals setiap 1 menit
func (w *AutoApproveWorker) run(ctx context.Context) error {
    threshold := time.Now().Add(-4 * time.Hour)

    // Cari WO yang submitted > 4 jam dan PC belum approve
    pendingPC, _ := w.repo.ListPendingPCApproval(ctx, threshold)
    for _, wo := range pendingPC {
        w.repo.AutoApprovePC(ctx, wo.ID)
        w.notify.Send(ctx, wo.CreatedBy, "WO auto-approved by PC timer")
    }

    // Sama untuk PM
    pendingPM, _ := w.repo.ListPendingPMApproval(ctx, threshold)
    // ...
}
```

---

## 6. Suggest Logic — WO Production Actual

```go
func (uc *SuggestWOActual) Execute(ctx context.Context, woID int64, date time.Time, shift string) (*WOProductionActual, error) {

    // P1: packing done
    gradeActuals, _ := uc.woRepo.ListGradeActual(ctx, woID)
    if len(gradeActuals) > 0 {
        return buildFromGradeActual(gradeActuals), nil
    }

    // P2: QC released (normal_bobs dari TXT)
    actual, _ := uc.woRepo.GetProductionActual(ctx, woID, date, shift)
    if actual != nil && actual.NormalBobs != nil && *actual.NormalBobs > 0 {
        return buildFromQCRelease(actual), nil
    }

    // P4: total transferred dari TXT
    if actual != nil && actual.TotalBobbins != nil && *actual.TotalBobbins > 0 {
        return buildFromTransferred(actual), nil
    }

    // Tidak ada data
    return nil, ErrNoSuggestAvailable
}
```

---

## 7. gRPC Service Design

```protobuf
// proto/ppc/v1/ppc.proto

service PPCService {
    // Demand
    rpc CreateDemand(CreateDemandRequest) returns (Demand);
    rpc ListDemands(ListDemandsRequest) returns (ListDemandsResponse);
    rpc PullFromOrion(PullFromOrionRequest) returns (Demand);
    rpc ProcessCarryForward(CarryForwardRequest) returns (CarryForwardResponse);

    // Plan Item
    rpc CreatePlanItem(CreatePlanItemRequest) returns (PlanItem);
    rpc ListPlanItems(ListPlanItemsRequest) returns (ListPlanItemsResponse);
    rpc UpdatePlanItem(UpdatePlanItemRequest) returns (PlanItem);

    // Work Order
    rpc CreateWO(CreateWORequest) returns (WorkOrder);
    rpc SubmitWO(SubmitWORequest) returns (WorkOrder);
    rpc ApproveWOParameter(ApproveWOParameterRequest) returns (WorkOrder);
    rpc ApproveWO(ApproveWORequest) returns (WorkOrder);
    rpc RejectWO(RejectWORequest) returns (WorkOrder);
    rpc GetWOProductionActual(GetWOActualRequest) returns (WOProductionActualResponse);
    rpc OverrideWOActual(OverrideWOActualRequest) returns (WOProductionActual);

    // Master Data
    rpc CreateLot(CreateLotRequest) returns (LotMaster);
    rpc ListMachines(ListMachinesRequest) returns (ListMachinesResponse);

    // Dashboard
    rpc GetMorningReview(MorningReviewRequest) returns (MorningReviewResponse);
}
```

---

## 8. Configuration

```go
// config/ppc.go — extend existing AppConfig
type PPCConfig struct {
    ETL struct {
        IntervalMinutes        int `yaml:"interval_minutes" default:"15"`
        WatermarkBufferMinutes int `yaml:"watermark_buffer_minutes" default:"30"`
        SOIntervalMinutes      int `yaml:"so_interval_minutes" default:"30"`
    } `yaml:"etl"`
    Oracle struct {
        DSN string `yaml:"dsn" env:"ORACLE_DSN"`
    } `yaml:"oracle"`
    Approval struct {
        AutoApproveHours int `yaml:"auto_approve_hours" default:"4"`
    } `yaml:"approval"`
}
```

---

## 9. Error Handling Strategy

Ikuti pattern yang sudah ada di `internal/finance/`:

```go
// Domain errors — bisa di-handle business logic
var (
    ErrWONotFound          = errors.New("ppc: work order not found")
    ErrWOInvalidStatus     = errors.New("ppc: invalid WO status transition")
    ErrDemandQtyExceeded   = errors.New("ppc: demand qty exceeded")
    ErrRMFenceExceeded     = errors.New("ppc: RM fence exceeded")
    ErrNoSuggestAvailable  = errors.New("ppc: no suggest data available")
)

// Wrapping untuk tracing
func (uc *CreateWOUseCase) Execute(ctx context.Context, req *CreateWORequest) (*WorkOrder, error) {
    wo, err := uc.repo.Create(ctx, req.toEntity())
    if err != nil {
        return nil, fmt.Errorf("ppc.CreateWO: %w", err)
    }
    return wo, nil
}
```

---

## 10. Notes untuk Claude Code

Sebelum mulai coding, baca:
1. `internal/finance/` — ikuti pattern yang sama persis
2. `migrations/` — cek nomor migration terakhir, lanjutkan dari situ
3. `go.mod` — cek dependency yang sudah ada, tambahkan yang kurang
4. `.golangci.yml` — ikuti lint rules yang sudah dikonfigurasi

**Yang perlu ditambah ke go.mod (kemungkinan):**
- `github.com/godror/godror` — Oracle driver
- `github.com/shopspring/decimal` — kalau belum ada (untuk qty calculation)

**Update file ini** setelah gap analysis vs codebase selesai.
