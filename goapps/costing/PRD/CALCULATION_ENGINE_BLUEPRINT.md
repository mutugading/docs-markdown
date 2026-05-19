---
title: "Calculation Engine Architecture Blueprint"
version: "1.0"
status: "Draft"
phase: "C"
last_updated: "2026-05"
audience: "Backend Engineers, Tech Lead"
related:
  - "PRD_PhaseC_ParameterEntry_v1.0.md"
  - "PRD_PhaseB_ProductOrder_v1.4.md"
---

# Calculation Engine Architecture Blueprint

> *Go-based batch costing engine. 12k products × 125 params → target <2 minutes.*

---

## 1. Overview

Calculation engine adalah komponen kritis Phase C. Menggantikan Oracle stored package legacy yang lambat dengan implementasi Go yang fast-batch dan transparent.

### Design Goals

- **Performance**: 12.000 products × 125 parameters dalam <2 menit.
- **Correctness**: Cascade failure handling, audit trail lengkap.
- **Maintainability**: Hardcoded formulas dengan unit test, configuration via DB.
- **Observability**: Per-product timing, error logs, dashboard-ready metrics.

### Non-Goals

- Real-time / interactive calculation (batch only untuk MVP).
- Custom formula DSL editor untuk end-user.
- Per-product formula override (formula tied to parameter, same for all products).
- Distributed processing across multiple nodes (single-node, multi-goroutine).

---

## 2. High-Level Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                     Trigger Sources                              │
│   [Cron Scheduler]            [Admin Manual Button]              │
└──────────────────────────────┬──────────────────────────────────┘
                               │
                               ▼
                  ┌────────────────────────────┐
                  │   Calculation Run Manager  │
                  │   - Validate period        │
                  │   - Create CCR record      │
                  │   - Acquire advisory lock  │
                  └────────────┬───────────────┘
                               │
                               ▼
                  ┌────────────────────────────┐
                  │      Engine Pipeline       │
                  │                            │
                  │   Stage 1: Data Loader     │
                  │   Stage 2: Dep Resolver    │
                  │   Stage 3: Dispatcher      │
                  │   Stage 4: Calculator      │
                  │   Stage 5: Batch Writer    │
                  │   Stage 6: Audit & Status  │
                  └────────────┬───────────────┘
                               │
                               ▼
                  ┌────────────────────────────┐
                  │   PostgreSQL Storage       │
                  │   - cost_calculation_run   │
                  │   - cost_calculation_result│
                  │   - cost_audit_log         │
                  └────────────────────────────┘
```

---

## 3. Component Detail

### 3.1. Calculation Run Manager

**Responsibility**: Orchestrate the full run lifecycle.

```go
type RunManager struct {
    db          *pgx.Conn
    config      *Config
    logger      *zerolog.Logger
}

func (rm *RunManager) ExecuteRun(period string, triggerType string, triggeredBy string) (*RunResult, error) {
    // 1. Validate period is OPEN
    if !rm.isPeriodOpen(period) {
        return nil, ErrPeriodClosed
    }

    // 2. Acquire PostgreSQL advisory lock (prevent concurrent runs)
    if !rm.tryAdvisoryLock(period) {
        return nil, ErrConcurrentRun
    }
    defer rm.releaseAdvisoryLock(period)

    // 3. Create CCR record (status=PENDING)
    runID, err := rm.createRun(period, triggerType, triggeredBy)
    if err != nil { return nil, err }

    // 4. Execute pipeline
    rm.updateRunStatus(runID, "RUNNING")
    result, err := rm.runPipeline(runID, period)

    // 5. Finalize: update CCR with counters, status, duration
    rm.finalizeRun(runID, result, err)

    // 6. Auto-activate if success/partial
    if result.Status == "SUCCESS" || result.Status == "PARTIAL" {
        rm.setRunActive(runID)
    }

    return result, err
}
```

### 3.2. Stage 1 — Data Loader

**Responsibility**: Bulk load all required data into memory.

```go
type CalcContext struct {
    Period           string
    ParameterMaster  map[int]*ParameterDef           // 125 records
    MasterData       map[string]*MasterCache         // by master_code
    ProductMaster    map[int64]*ProductInfo          // 12k records
    StaticParams     map[int64]map[int]Value         // product_sys_id → param_id → value
    PeriodParams     map[int64]map[int]Value         // product_sys_id → param_id → value
    BOMComponents    map[int64][]*Component          // product_sys_id → components
    ProductOrder     map[int64]int                   // product → topological order
}

func LoadAll(ctx context.Context, db *pgx.Conn, period string) (*CalcContext, error) {
    cc := &CalcContext{Period: period}

    // 5 bulk queries in parallel
    g, ctx := errgroup.WithContext(ctx)

    g.Go(func() error { return loadParameterMaster(ctx, db, cc) })
    g.Go(func() error { return loadMasterData(ctx, db, cc, period) })
    g.Go(func() error { return loadProductMaster(ctx, db, cc) })
    g.Go(func() error { return loadStaticParams(ctx, db, cc) })
    g.Go(func() error { return loadPeriodParams(ctx, db, cc, period) })
    g.Go(func() error { return loadBOMComponents(ctx, db, cc) })

    if err := g.Wait(); err != nil { return nil, err }
    return cc, nil
}
```

**Query patterns** (no N+1):

```sql
-- Load all static params for all products in 1 query
SELECT CPP_product_sys_id, CPP_param_id,
       CPP_value_numeric, CPP_value_text, CPP_value_flag, CPP_value_json
FROM cost_product_parameter;

-- Load all period params for the target period
SELECT CPPP_product_sys_id, CPPP_param_id,
       CPPP_value_numeric, CPPP_value_text, CPPP_value_flag, CPPP_value_json
FROM cost_product_parameter_period
WHERE CPPP_period = $1;

-- Load all master data (period-aware)
SELECT CMSD_master_id, CMSD_data_code, CMSD_period, CMSD_attributes
FROM cost_master_data
WHERE CMSD_is_active = true
  AND (CMSD_period IS NULL OR CMSD_period = $1);
```

**Memory estimate**:
```
125 params × metadata             →  ~50 KB
8 masters × ~500 rows × JSONB     →  ~10 MB
12k products × metadata           →  ~5 MB
12k products × 125 static values  →  ~80 MB
12k products × 50 period values   →  ~30 MB
BOM components                    →  ~20 MB
                                  ───────────
Total working set                 →  ~150 MB
```

Comfortably fits in 4 GB.

### 3.3. Stage 2 — Dependency Resolver

**Responsibility**: Determine execution order untuk parameters dan products.

#### Parameter dependency (intra-product)

```go
var calcRegistry = []CalcFunc{
    {31, "calcNetBobbinWeight", []int{18,19,20,21,22,23,25,26,27,28,29,30}, calcNetBobbinWeight},
    {34, "calcCaptiveBoxWeight", []int{31, 33}, calcCaptiveBoxWeight},
    {37, "calcCaptivePackCost", []int{35, 33, 36, 34}, calcCaptivePackCost},
    {87, "calcTotalConversion", []int{83, 84, 85, 86}, calcTotalConversion},
    {90, "calcCaptiveBeforeQL", []int{51, 69, 88}, calcCaptiveBeforeQL},
    {101, "calcCaptiveFinal", []int{90, 99}, calcCaptiveFinal},
    {102, "calcDeliveryFinal", []int{91, 100}, calcDeliveryFinal},
    // ... 25 total entries
}

func TopologicalSortParams(registry []CalcFunc) []int {
    // Standard Kahn's algorithm
    // Output: [1, 8, 11, ..., 18, 19, ..., 31, 34, ..., 87, ..., 101, 102]
}
```

#### Product dependency (inter-product)

Derived from BOM:

```sql
-- Build dependency edges from BOM
SELECT
  CPO_product_sys_id AS dependent,
  CPOC_rm_product_sys_id AS dependency
FROM cost_product_order_component
JOIN cost_product_order_version ON CPOC_version_id = CPOV_version_id
JOIN cost_product_order ON CPOV_order_id = CPO_order_id
WHERE CPOC_rm_product_sys_id IS NOT NULL
  AND CPO_current_version_id = CPOV_version_id;
```

In-memory topological sort → list of products in dependency order (terdalam first).

### 3.4. Stage 3 — Work Dispatcher

**Responsibility**: Distribute products to worker pool.

```go
type Worker struct {
    id      int
    ctx     *CalcContext
    results chan<- *ProductResult
}

func (w *Worker) Run(productIDs <-chan int64) {
    for productID := range productIDs {
        result := w.calculateProduct(productID)
        w.results <- result
    }
}

func Dispatch(ctx *CalcContext, productOrder []int64) chan *ProductResult {
    numWorkers := runtime.NumCPU()
    productCh := make(chan int64, 100)
    resultCh := make(chan *ProductResult, 1000)

    var wg sync.WaitGroup
    for i := 0; i < numWorkers; i++ {
        wg.Add(1)
        worker := &Worker{id: i, ctx: ctx, results: resultCh}
        go func() {
            defer wg.Done()
            worker.Run(productCh)
        }()
    }

    // Send products in topological order
    // (within a "wave" of products with no inter-dependency, order doesn't matter)
    for _, pid := range productOrder {
        productCh <- pid
    }
    close(productCh)

    // Close result channel when workers done
    go func() { wg.Wait(); close(resultCh) }()
    return resultCh
}
```

**Note**: Product topological order matters globally — product Z must be calculated before X if X depends on Z. Within a "level" (no inter-dependency), parallel safe. Implementation can:
- Process products level-by-level (barrier between levels).
- Or use dependency-aware scheduling (defer X until Z completes).

Level-by-level is simpler dan acceptable performance.

### 3.5. Stage 4 — Calculator

**Responsibility**: Per-product, walk params in order, compute values.

```go
type ProductResult struct {
    ProductID        int64
    Status           string                 // SUCCESS / PARTIAL / FAILED
    ParamValues      map[int]interface{}    // 125 entries
    FailedParamIDs   []int
    FailedDepProducts []int64
    PartialReasons   []PartialReason
    DurationMicro    int64
}

func (w *Worker) calculateProduct(productID int64) *ProductResult {
    start := time.Now()
    result := &ProductResult{
        ProductID:   productID,
        Status:      "SUCCESS",
        ParamValues: make(map[int]interface{}, 125),
    }
    pctx := &ParamCtx{ctx: w.ctx, productID: productID, result: result}

    for _, paramID := range w.ctx.ParamExecOrder {
        paramDef := w.ctx.ParameterMaster[paramID]

        switch paramDef.FunctionType {
        case "ENTRY", "JSONB":
            // Get from static or period table
            val := getEntryValue(pctx, paramID)
            if val == nil && paramDef.IsRequired {
                result.FailedParamIDs = append(result.FailedParamIDs, paramID)
                result.Status = "FAILED"
                continue
            }
            result.ParamValues[paramID] = val

        case "LOOKUP":
            val, err := lookupMaster(pctx, paramDef.LookupMasterCode)
            if err != nil {
                addPartialReason(result, paramID, "lookup_failed", err)
                result.Status = "PARTIAL"
                result.ParamValues[paramID] = 0  // safe default
                continue
            }
            result.ParamValues[paramID] = val

        case "CALCULATION":
            val, err := executeCalculation(pctx, paramDef)
            if err != nil {
                addPartialReason(result, paramID, "calc_failed", err)
                result.Status = "PARTIAL"
                result.ParamValues[paramID] = 0
                continue
            }
            result.ParamValues[paramID] = val
        }
    }

    // Quick-access final costs
    if captive, ok := result.ParamValues[101].(float64); ok {
        result.CaptiveCost = &captive
    }
    if delivery, ok := result.ParamValues[102].(float64); ok {
        result.DeliveryCost = &delivery
    }

    result.DurationMicro = time.Since(start).Microseconds()
    return result
}
```

#### Cascade failure handling

```go
func executeCalculation(pctx *ParamCtx, paramDef *ParameterDef) (interface{}, error) {
    // For params yang reference cost komponen lain (PARAM 51: RM_RATE),
    // check the dependency product's status

    if paramDef.ParamID == 51 {  // RM_RATE special case
        rmProductID := getRMProductSysID(pctx)
        if rmProductID > 0 {
            depResult := pctx.ctx.AlreadyCalculated[rmProductID]
            if depResult == nil {
                return 0.0, fmt.Errorf("dependency product not calculated: %d", rmProductID)
            }
            if depResult.Status == "FAILED" {
                pctx.result.FailedDepProducts = append(pctx.result.FailedDepProducts, rmProductID)
                pctx.result.Status = "PARTIAL"
                return 0.0, nil  // cascade: failed dep → cost = 0
            }
            if depResult.CaptiveCost != nil {
                return *depResult.CaptiveCost, nil
            }
        }
    }

    // Call hardcoded Go function from registry
    // (no per-product override — formula is fixed at parameter level)
    fn := calcRegistry[paramDef.CalcFunctionKey]
    return fn(pctx), nil
}
```

### 3.6. Stage 5 — Batch Writer

**Responsibility**: Persist results efficiently.

```go
func WriteResults(ctx context.Context, db *pgx.Conn, runID int64, period string, results []*ProductResult) error {
    // PostgreSQL COPY for bulk insert (10-100x faster than INSERT batch)
    tx, err := db.Begin(ctx)
    if err != nil { return err }
    defer tx.Rollback(ctx)

    // Prepare COPY source
    rows := make([][]interface{}, len(results))
    for i, r := range results {
        paramValuesJSON, _ := json.Marshal(r.ParamValues)
        rows[i] = []interface{}{
            runID,
            period,
            r.ProductID,
            r.Status,
            jsonOrNil(r.FailedParamIDs),
            jsonOrNil(r.FailedDepProducts),
            jsonOrNil(r.PartialReasons),
            paramValuesJSON,
            r.CaptiveCost,
            r.DeliveryCost,
            time.Now(),
            r.DurationMicro,
        }
    }

    _, err = tx.CopyFrom(ctx,
        pgx.Identifier{"cost_calculation_result"},
        []string{"CCRE_run_id", "CCRE_period", "CCRE_product_sys_id",
                 "CCRE_calc_status", "CCRE_failed_param_ids", "CCRE_failed_dep_products",
                 "CCRE_partial_reasons", "CCRE_param_values",
                 "CCRE_captive_cost", "CCRE_delivery_cost",
                 "CCRE_calculated_at", "CCRE_duration_us"},
        pgx.CopyFromRows(rows))
    if err != nil { return err }

    return tx.Commit(ctx)
}
```

### 3.7. Stage 6 — Audit & Status

**Responsibility**: Update run record dengan final status, counters, dan metadata.

```go
func FinalizeRun(ctx context.Context, db *pgx.Conn, runID int64, results []*ProductResult, runErr error) error {
    var successCount, partialCount, failedCount int
    for _, r := range results {
        switch r.Status {
        case "SUCCESS": successCount++
        case "PARTIAL": partialCount++
        case "FAILED":  failedCount++
        }
    }

    overallStatus := "SUCCESS"
    if runErr != nil { overallStatus = "FAILED" }
    else if failedCount > 0 { overallStatus = "PARTIAL" }

    // Get git commit
    gitCommit := os.Getenv("GIT_COMMIT")

    // Snapshot param master mapping
    paramSnapshot := buildParamSnapshot()

    _, err := db.Exec(ctx, `
        UPDATE cost_calculation_run
        SET CCR_status = $1,
            CCR_total_products = $2,
            CCR_success_count = $3,
            CCR_partial_count = $4,
            CCR_failed_count = $5,
            CCR_ended_at = now(),
            CCR_duration_ms = $6,
            CCR_git_commit = $7,
            CCR_param_master_snapshot = $8,
            CCR_error_summary = $9
        WHERE CCR_run_id = $10
    `, overallStatus, len(results), successCount, partialCount, failedCount,
       durationMs, gitCommit, paramSnapshot, errorSummary, runID)

    return err
}
```

---

## 4. Calculation Function Implementation Pattern

### 4.1. Function Signature

```go
type ParamCtx struct {
    ctx       *CalcContext
    productID int64
    result    *ProductResult
}

// All values stored as float64 internally
func (p *ParamCtx) Get(paramID int) float64 {
    val, ok := p.result.ParamValues[paramID]
    if !ok { return 0 }
    switch v := val.(type) {
    case float64: return v
    case int:     return float64(v)
    case bool:    if v { return 1 } else { return 0 }
    default:      return 0
    }
}

func (p *ParamCtx) GetText(paramID int) string {
    val, ok := p.result.ParamValues[paramID]
    if !ok { return "" }
    if s, ok := val.(string); ok { return s }
    return ""
}

func (p *ParamCtx) GetMaster(masterCode string, dataCode string, attr string) float64 {
    // Lookup from master cache
}

type CalcFunc func(p *ParamCtx) float64
```

### 4.2. Example Implementations

```go
// PARAM 31: Net Bobbin Weight
func calcNetBobbinWeight(p *ParamCtx) float64 {
    return (p.Get(18)*p.Get(25) +
            p.Get(19)*p.Get(26) +
            p.Get(20)*p.Get(27) +
            p.Get(21)*p.Get(28) +
            p.Get(22)*p.Get(29) +
            p.Get(23)*p.Get(30)) / 1000
}

// PARAM 34: Captive Box Weight
func calcCaptiveBoxWeight(p *ParamCtx) float64 {
    return p.Get(31) * p.Get(33)
}

// PARAM 37: Captive Packing Cost
func calcCaptivePackCost(p *ParamCtx) float64 {
    boxWeight := p.Get(34)
    if boxWeight == 0 { return 0 }  // avoid div by zero
    return (p.Get(35)*p.Get(33) + p.Get(36)) / boxWeight
}

// PARAM 49: Heatset Cost per Kg
func calcHeatsetCostKg(p *ParamCtx) float64 {
    heatsetCode := p.GetText(44)
    if heatsetCode == "" { return 0 }  // no heatset = no cost
    batchWeight := p.Get(47)
    if batchWeight == 0 { return 0 }
    return p.Get(48) / batchWeight
}

// PARAM 67: RP-Dozing — complex ELSIF logic
func calcRPDozing(p *ParamCtx) float64 {
    mbCC := p.GetText(63)
    crossSection := p.GetText(12)
    conversionFactor := p.Get(65)
    dozingAdjust := p.Get(122)

    switch {
    case mbCC == "TBL" && crossSection == "TBL":
        return conversionFactor + dozingAdjust
    case mbCC == "RND" && crossSection == "RND":
        return conversionFactor + dozingAdjust
    case mbCC == "TBL" && crossSection == "RND":
        return conversionFactor*0.82 + dozingAdjust
    case mbCC == "RND" && crossSection == "TBL":
        return conversionFactor*0.82 + dozingAdjust
    case mbCC == "PLUS" && crossSection == "PLUS":
        return conversionFactor + dozingAdjust
    default:
        return 0
    }
}

// PARAM 87: Total Conversion
func calcTotalConversion(p *ParamCtx) float64 {
    return p.Get(83) + p.Get(84) + p.Get(85) + p.Get(86)
}

// PARAM 101: Captive Final
func calcCaptiveFinal(p *ParamCtx) float64 {
    return p.Get(90) + p.Get(99)
}
```

---

## 5. Performance Budget

| Stage | Target time | Note |
|---|---|---|
| Stage 1 Data Load | 5-10 sec | 5 parallel queries |
| Stage 2 Dep Resolve | <100 ms | In-memory, O(N) |
| Stage 3 Dispatch overhead | <1 sec | Goroutine spinup |
| Stage 4 Calculate | 30-60 sec | 12k × 125 ÷ 8 cores ÷ 50μs ≈ 38 sec |
| Stage 5 Batch Write | 10-15 sec | COPY 12k rows |
| Stage 6 Audit | <1 sec | Single update |
| **Total** | **<90 sec** | well under 2 min target |

### Optimization Levers

1. **Increase parallelism**: NumCPU × 2 untuk I/O-bound stages.
2. **Connection pool**: pgxpool dengan max 16 connections.
3. **Reduce memory allocations**: pool of ProductResult structs.
4. **Lazy master lookup**: cache hot keys in memory, fallback to map.

---

## 6. Error Handling

### Error categories

| Category | Example | Handling |
|---|---|---|
| Missing required param | PARAM 8 Denier not filled | Mark product FAILED, log, skip |
| Missing master data | LOOKUP returns no match | Mark PARTIAL, value = 0, log warning |
| Division by zero | Net Bobbin Weight = 0 | Guard in calc function, return 0 |
| Cascade failure | Dep product FAILED | RM cost = 0, this product PARTIAL |
| DB connection lost | Network issue mid-run | Retry batch, abort if persistent |

### Retry & Idempotency

- **Run is idempotent**: same run_id won't be re-processed (advisory lock).
- **Failed run can be retried**: create new run_seq for same period.
- **Partial run is acceptable**: results stored, can re-run after fix.

---

## 7. Observability

### Metrics (Prometheus-ready)

```
costing_run_duration_seconds{period, status}
costing_run_products_total{period, status}
costing_calculation_per_product_microseconds{}
costing_master_lookup_total{master_code, hit_miss}
```

### Logs (structured JSON)

```json
{
  "level": "info",
  "service": "costing-engine",
  "run_id": 1234,
  "period": "202605",
  "stage": "calculator",
  "products_done": 5000,
  "products_total": 12000,
  "elapsed_ms": 18432,
  "ts": "2026-05-19T02:15:30Z"
}
```

### Dashboard Queries

```sql
-- Last 10 runs summary
SELECT CCR_run_id, CCR_period, CCR_status,
       CCR_total_products, CCR_success_count, CCR_partial_count, CCR_failed_count,
       CCR_duration_ms / 1000 AS duration_sec,
       CCR_started_at
FROM cost_calculation_run
ORDER BY CCR_started_at DESC
LIMIT 10;

-- Top failing params per run
SELECT jsonb_array_elements_text(CCRE_failed_param_ids) AS param_id,
       COUNT(*) AS failed_products
FROM cost_calculation_result
WHERE CCRE_run_id = :run_id
GROUP BY param_id
ORDER BY failed_products DESC;
```

---

## 8. Deployment & Operations

### Deployment Options

**Option A: Separate Microservice**
- Pros: clean separation, scale independently, dedicated resources.
- Cons: extra service to operate.

**Option B: Worker in API Monorepo**
- Pros: shared code, easier deploy.
- Cons: long-running run may impact API responsiveness.

**Recommendation**: Option B for MVP (simpler), migrate to A if scale demands.

### Scheduler

**Option A: Kubernetes CronJob** — if K8s deployment.
**Option B: GitHub Actions Scheduled** — if no K8s, but adds external dependency.
**Option C: In-app scheduler** — robfig/cron library in Go service.

**Recommendation**: depends on deployment platform (decision pending).

### Config

```yaml
# config.yaml
calculation_engine:
  worker_count: 0          # 0 = NumCPU
  batch_write_size: 1000
  product_load_chunk: 500
  log_level: "info"

  schedule:
    enabled: true
    cron: "0 2 * * *"      # daily at 02:00
    timezone: "Asia/Jakarta"
```

---

## 9. Testing Strategy

### Unit Tests

- Per calc function: 5+ test cases covering happy path, edge cases (zero, negative), error conditions.
- Test target: 80% coverage on `pkg/calc/`.

### Integration Tests

- End-to-end run with seed data: 100 products, verify result vs expected.
- Cascade failure: synthetic scenario, verify status propagation.
- Master change between runs: verify JSONB snapshot integrity.

### Regression Tests

- Snapshot from Oracle legacy: 50 sample products with known cost.
- New engine result must match within tolerance (±0.001 USD).
- Diff report for any deviation.

### Load Tests

- Synthetic 50k products, verify <5 min runtime.
- Stress test concurrent triggers (only one should proceed).

---

## 10. Migration Strategy

### Cutover Plan

1. **Phase 1** (parallel run): Both Oracle legacy + new Go engine run on same period. Compare results.

2. **Phase 2** (verification): 1-2 months parallel, validate diff. Fix any discrepancy.

3. **Phase 3** (cutover): Decommission Oracle package. Go engine becomes single source.

### Rollback

- If new engine produces wrong result post-cutover: emergency fall-back to manual Excel calculation per product (legacy procedure).
- Database rollback: previous run records intact, can re-activate old run.

---

## 11. Future Enhancements (Post-MVP)

1. **Incremental run**: only recalculate products affected by recent param changes.
2. **Distributed processing**: split workload across multiple worker nodes (only if needed beyond 50k products).
3. **Custom formula DSL**: visual editor for non-developer to compose new params.
4. **What-if simulation**: temporary value substitution for forecast scenarios (read-only, not persisted).
5. **Cost variance dashboard**: compare period-over-period.

---

## 12. References

- PRD Phase C: `PRD_PhaseC.md`
- DDL Phase C: `V005__phase_c_initial.sql`
- Param master seed: `V003__phase_b_parameter_master.sql` (PART 14)
