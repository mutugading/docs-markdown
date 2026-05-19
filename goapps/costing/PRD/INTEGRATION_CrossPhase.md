---
title: "Cross-Phase Integration — Costing Workflow Suite"
version: "1.0"
status: "Draft"
last_updated: "2026-05"
audience: "Backend Engineers, Solution Architects, Tech Lead"
related:
  - "PRD_PhaseA_ProductRequest_v1.1.md"
  - "PRD_PhaseB_ProductOrder_v1.4.md"
  - "PRD_PhaseC_ParameterEntry_v1.0.md"
  - "ERD_Master.md"
---

# Cross-Phase Integration — Costing Workflow Suite

> *Data flows, event triggers, and integration patterns across Phase A → B → C.*

---

## 1. Integration Overview

Costing Workflow Suite terbagi ke 3 phase tapi merupakan satu sistem terintegrasi. Integration terjadi via:

1. **Database references** — denormalized FK untuk fast lookup.
2. **Service layer events** — triggered actions saat data berubah.
3. **Shared audit log** — single source of truth untuk activity timeline.
4. **Cross-phase queries** — direct read across phases (no API call overhead).

Tidak ada message queue / event bus untuk MVP — semua integration synchronous via DB & service layer. Memudahkan operability dan debugging.

---

## 2. Integration Points Matrix

| **#** | **Source** | **Target** | **Trigger** | **Pattern** |
|---|---|---|---|---|
| INT-1 | Phase A (Request submitted) | Phase A (Notification) | New CPR record | DB trigger / service hook |
| INT-2 | Phase A (Feasibility decision) | Phase A (Status change) | UPDATE CPR_feasibility_decision | Service layer |
| INT-3 | Phase A (Promote routing) | Phase B (Product Order create) | Promote button | Service layer transaction |
| INT-4 | Phase A (Promote routing) | Phase A (Resolved FK set) | After Phase B success | Same transaction as INT-3 |
| INT-5 | Phase B/C (Parameter save) | Phase A (Auto-complete check) | INSERT/UPDATE CPP/CPPP | Service layer hook |
| INT-6 | Phase B/C (Parameter save) | Phase A (Activity timeline) | INSERT/UPDATE CPP/CPPP | Audit log + query |
| INT-7 | Phase B (Master data change) | Phase A (Activity timeline) | INSERT/UPDATE CMSD | Audit log + query |
| INT-8 | Phase C (Calculation completed) | Phase A (Status auto-transition) | Run becomes active | Service layer hook |
| INT-9 | Phase C (Cost result) | Phase A (Result panel) | Detail request page load | Direct query |
| INT-10 | ERP (External) | Phase B (Replica tables) | Periodic sync | TBD (CDC/scheduled) |

---

## 3. Critical Integration Flows

### INT-3: Phase A → Phase B Promote Routing

**Trigger**: PIC click "Promote to Product Master" button at Phase A request detail page.

**Pre-conditions**:
- Request status = ROUTING_DEFINED
- Routing draft sudah complete (validated)
- User has Engineering Lead role

**Flow**:

```
1. UI POST /api/requests/{id}/promote
   Body: {
     product_master_action: "CREATE_NEW" | "LINK_EXISTING",
     product_master_id?: int,        // if LINK_EXISTING
     new_product_data?: {...}        // if CREATE_NEW
   }

2. Backend transaction:

   BEGIN;

   -- Step 2a: Get-or-create cost_product_master
   IF action = CREATE_NEW THEN
     -- Generate product code: CST + TYPE + YYMM + AUTO
     SELECT FOR UPDATE next_seq FROM cost_product_code_counter
     WHERE product_type_code = 'PTY' AND year_month = '2605';
     -- INSERT cost_product_master with generated code
   ELSE
     -- Validate product_master exists
   END IF;

   -- Step 2b: Create cost_product_order
   INSERT INTO cost_product_order
   (CPO_product_sys_id, CPO_order_no, CPO_status)
   VALUES (:product_sys_id, :order_no, 'DRAFT');

   -- Step 2c: Create initial version
   INSERT INTO cost_product_order_version
   (CPOV_order_id, CPOV_version_no, CPOV_status)
   VALUES (:order_id, 1, 'ACTIVE');

   -- Step 2d: Copy components from routing_draft → product_order_component
   INSERT INTO cost_product_order_component
   SELECT
     :new_version_id,
     CRDC_sequence_no,
     CRDC_rm_type_id,
     -- Map free-text refs → real FKs
     resolve_product_sys_id(CRDC_rm_text),  -- helper function
     resolve_erp_item_id(CRDC_rm_text),
     CRDC_qty, CRDC_unit
   FROM cost_routing_draft_component
   WHERE CRDC_draft_id = :draft_id;

   -- Step 2e: Update routing_draft linkage
   UPDATE cost_routing_draft
   SET CRD_linked_product_order_id = :order_id,
       CRD_promoted_at = now()
   WHERE CRD_draft_id = :draft_id;

   -- Step 2f: Update Phase A request (denormalized FK + status)
   UPDATE cost_product_request
   SET CPR_resolved_product_sys_id = :product_sys_id,
       CPR_status = 'PARAMETER_PENDING',
       CPR_updated_at = now()
   WHERE CPR_request_id = :request_id;

   -- Step 2g: Audit log
   INSERT INTO cost_audit_log (...) VALUES
     ('cost_product_master', :product_sys_id, 'INSERT', ...),
     ('cost_product_order', :order_id, 'INSERT', ...),
     ('cost_product_request', :request_id, 'STATUS_CHANGE', ...);

   COMMIT;

3. Notify stakeholders (Engineering, Production, Finance):
   - Insert cost_notification records: "Parameter entry required for product X"

4. Return success response with new IDs.
```

**Error handling**:
- All operations in single DB transaction — rollback on any failure.
- If product_code generation conflict (unlikely with SERIAL counter) → retry.
- If routing_draft components can't be resolved → reject promote, ask user to fix references.

**Rollback scenario**: Tidak ada — once promote, tidak bisa undo. Untuk koreksi, harus update product_order_component manually atau create new version.

---

### INT-5: Parameter Save → Auto-Complete Check

**Trigger**: User save parameter value via Phase B/C parameter entry form.

**Flow**:

```
1. UI POST /api/products/{product_sys_id}/parameters
   Body: {
     param_id: 8,                      // PARAM 8 Denier
     value: 150,
     period?: "202605"                 // for period-dependent only
   }

2. Backend:

   BEGIN;

   -- 2a: Save value
   IF param.is_period_dependent THEN
     UPSERT cost_product_parameter_period (...);
   ELSE
     UPSERT cost_product_parameter (...);
   END IF;

   -- 2b: Audit log
   INSERT INTO cost_audit_log (
     CAL_entity_type, CAL_entity_id, CAL_operation,
     CAL_before_data, CAL_after_data, CAL_user_id, CAL_performed_at
   ) VALUES (...);

   -- 2c: Auto-complete check
   -- Find linked Phase A request
   SELECT CPR_request_id, CPR_status
   FROM cost_product_request
   WHERE CPR_resolved_product_sys_id = :product_sys_id;

   IF status = 'PARAMETER_PENDING' THEN
     -- Call helper function from Phase C DDL
     SELECT COUNT(*) AS missing
     FROM get_missing_required_params(:product_sys_id, :current_period);

     IF missing = 0 THEN
       -- Transition to PARAMETER_COMPLETE (monotonic, first-time only)
       UPDATE cost_product_request
       SET CPR_status = 'PARAMETER_COMPLETE',
           CPR_parameter_complete_at = now()
       WHERE CPR_request_id = :id AND CPR_status = 'PARAMETER_PENDING';

       -- Audit log
       INSERT INTO cost_audit_log (...) VALUES
         ('cost_product_request', :id, 'STATUS_CHANGE',
          {"from":"PARAMETER_PENDING","to":"PARAMETER_COMPLETE"}, ...);

       -- Notify Marketing requester
       INSERT INTO cost_notification (...);
     END IF;
   END IF;

   COMMIT;

3. Return: { success: true, status_transitioned: bool }
```

**Idempotency**: Same param value saved twice — UPSERT handles it, no double-trigger.

**Monotonic guarantee**: `UPDATE ... WHERE status = 'PARAMETER_PENDING'` clause ensures transition only happens once. Subsequent param updates don't bounce back.

---

### INT-6 & INT-7: Activity Timeline Surfacing

**Concept**: Phase A request detail page shows event timeline. Events include parameter changes and master changes — even though they happen in Phase B/C, they surface in Phase A UI.

**Query pattern**:

```sql
-- Get activity timeline for Phase A request
WITH request_product AS (
  SELECT CPR_resolved_product_sys_id AS product_sys_id
  FROM cost_product_request WHERE CPR_request_id = :request_id
)
SELECT
  CAL_performed_at,
  CAL_user_id,
  CAL_entity_type,
  CAL_operation,
  CAL_before_data,
  CAL_after_data
FROM cost_audit_log, request_product
WHERE
  -- Request itself
  (CAL_entity_type = 'cost_product_request' AND CAL_entity_id = :request_id)
  OR
  -- Param values for the linked product
  (CAL_entity_type IN ('cost_product_parameter', 'cost_product_parameter_period')
   AND CAL_entity_id IN (
     SELECT CPP_value_id FROM cost_product_parameter
     WHERE CPP_product_sys_id = request_product.product_sys_id
     UNION
     SELECT CPPP_value_id FROM cost_product_parameter_period
     WHERE CPPP_product_sys_id = request_product.product_sys_id
   ))
  OR
  -- Master data changes (broad-but-useful)
  (CAL_entity_type = 'cost_master_data' AND CAL_performed_at >= :request_created_at)
ORDER BY CAL_performed_at DESC
LIMIT 50;
```

**Performance**: Index on `cost_audit_log.CAL_performed_at` + filter on entity_type. Limited to 50 most recent events.

**Note**: Master changes are organization-wide events, not specific to one product. UI filter at presentation: show master changes that "could affect" this product (heuristic).

---

### INT-8: Calculation Completed → Phase A Status Transition

**Trigger**: Calculation run finishes and is set as active.

**Flow**:

```go
// In Go calculation engine, after run finalize:
func (rm *RunManager) postRunHook(runID int64, period string) error {
    // Find all Phase A requests with status = PARAMETER_COMPLETE
    // whose linked product has SUCCESS or PARTIAL result in this run

    sql := `
      UPDATE cost_product_request
      SET CPR_status = 'COSTING_DONE',
          CPR_costing_done_at = now()
      WHERE CPR_status = 'PARAMETER_COMPLETE'
        AND CPR_resolved_product_sys_id IN (
          SELECT CCRE_product_sys_id
          FROM cost_calculation_result
          WHERE CCRE_run_id = $1
            AND CCRE_calc_status IN ('SUCCESS', 'PARTIAL')
        )
      RETURNING CPR_request_id
    `
    rows := db.Query(sql, runID)

    // Notify Marketing for each request transitioned
    for _, row := range rows {
        sendNotification(row.RequestID, "Cost calculation complete for your request")
    }

    return nil
}
```

**Monotonic note**: Once COSTING_DONE, even if next run produces different cost, status doesn't bounce. Cost changes surface as latest result query (INT-9) and audit timeline.

---

### INT-9: Cost Result Panel Query

**UI**: Phase A request detail page, tab "Cost Result".

**Query** (showing 3 latest periods, with full BOM chain breakdown):

```sql
-- Step 1: Get primary product
SELECT CPR_resolved_product_sys_id INTO :main_product_id
FROM cost_product_request WHERE CPR_request_id = :request_id;

-- Step 2: Get BOM chain (recursive)
WITH RECURSIVE bom_chain AS (
  -- Anchor: main product
  SELECT
    :main_product_id AS product_sys_id,
    0 AS depth,
    ARRAY[:main_product_id] AS path

  UNION ALL

  -- Recursive: descendants via BOM
  SELECT
    CPOC_rm_product_sys_id AS product_sys_id,
    bc.depth + 1,
    bc.path || CPOC_rm_product_sys_id
  FROM bom_chain bc
  JOIN cost_product_order ON CPO_product_sys_id = bc.product_sys_id
  JOIN cost_product_order_version ON CPOV_version_id = CPO_current_version_id
  JOIN cost_product_order_component ON CPOC_version_id = CPOV_version_id
  WHERE CPOC_rm_product_sys_id IS NOT NULL    -- only PRODUCT-type RM
    AND NOT (CPOC_rm_product_sys_id = ANY(bc.path))   -- cycle prevention
    AND bc.depth < 10                                  -- max depth safety
)
-- Step 3: Join with latest cost for each product in chain × 3 periods
SELECT
  bc.product_sys_id,
  bc.depth,
  CPM_product_code,
  CCRE_period,
  CCRE_calc_status,
  CCRE_captive_cost,
  CCRE_delivery_cost,
  CCRE_param_values     -- ~80 params displayed in UI
FROM bom_chain bc
JOIN cost_product_master ON CPM_product_sys_id = bc.product_sys_id
LEFT JOIN cost_calculation_result ON CCRE_product_sys_id = bc.product_sys_id
LEFT JOIN cost_calculation_run ON CCRE_run_id = CCR_run_id
WHERE CCR_is_active = true
  AND CCRE_period IN (
    SELECT CCP_period FROM cost_calculation_period
    WHERE CCP_status IN ('OPEN', 'CLOSED')
    ORDER BY CCP_period DESC LIMIT 3
  )
ORDER BY bc.depth, CCRE_period DESC;
```

**UI rendering**:
- Tree view: main product (depth 0) on top, dependencies expanding below.
- Each node shows: product_code, status badge (SUCCESS/PARTIAL/FAILED), cost (3 periods).
- Click node → expand to show ~80 param values (key spec + cost breakdown).
- Visual cue: red border untuk FAILED, yellow untuk PARTIAL.

**Performance**: With proper indexes (`idx_ccre_product_period`, `idx_cpoc_rm_product`), <500ms for typical 5-level BOM.

---

### INT-10: ERP Sync (External)

**Source**: Oracle ERP (item master, grade master, shade master).

**Target**: PostgreSQL replica tables (`cost_erp_item`, `cost_erp_grade`, `cost_erp_shade`).

**Pattern options (TBD decision)**:

| Option | Pros | Cons |
|---|---|---|
| Oracle DB Link | Real-time read | Tight coupling, network dependency |
| Scheduled batch | Simple, reliable | Lag up to schedule interval |
| CDC (Debezium-like) | Near-real-time | Complex infra |
| ERP outbound API | Decoupled | Requires ERP modification |

**Recommendation**: Start with scheduled batch (hourly), evaluate CDC if real-time needed.

**Sync table structure**:
```sql
CREATE TABLE cost_erp_sync_log (
  -- track each sync run: started_at, ended_at, rows_synced, errors
);
```

---

## 4. Service Layer Event Hooks (Summary)

Single service backend with hook pattern:

```
On INSERT cost_product_request:
  → notify PIC

On UPDATE cost_product_request.feasibility_decision:
  → audit log
  → if NOT_FEASIBLE → notify Marketing
  → if FEASIBLE → notify Engineering

On UPDATE cost_product_request.status (any):
  → audit log
  → notify involved parties

On INSERT/UPDATE cost_product_parameter:
  → audit log
  → check auto-complete (INT-5)

On INSERT/UPDATE cost_product_parameter_period:
  → audit log
  → check auto-complete (INT-5)

On INSERT/UPDATE cost_master_data:
  → audit log
  → (no auto-bounce of any request state)

On calculation run finalize (active=true):
  → post-run hook (INT-8)
  → notify dashboard

On INSERT/UPDATE cost_calculation_period (status=CLOSED):
  → audit log
  → notify Admin & dept leads
```

All hooks run within service layer transaction. Tidak ada async queue di MVP.

---

## 5. API Contract Examples

### POST /api/requests/{id}/promote

Request:
```json
{
  "product_master_action": "CREATE_NEW",
  "new_product_data": {
    "product_type_code": "PTY",
    "erp_item_code": "PTY150D48",
    "erp_grade_code_1": "AX",
    "shade_description": "natural"
  },
  "feasibility_note": "Standard production capable"
}
```

Response:
```json
{
  "success": true,
  "new_product_sys_id": 18234,
  "new_product_code": "CSTPTY2605000017",
  "new_order_id": 9876,
  "request_status": "PARAMETER_PENDING"
}
```

### POST /api/products/{product_sys_id}/parameters

Request:
```json
{
  "param_id": 8,
  "value": 150,
  "period": null
}
```

Response:
```json
{
  "success": true,
  "value_id": 543210,
  "auto_complete_triggered": false,
  "missing_required_count": 23
}
```

### POST /api/calculations/runs

Request:
```json
{
  "period": "202605",
  "trigger_type": "MANUAL"
}
```

Response (async):
```json
{
  "run_id": 1234,
  "status": "PENDING",
  "estimated_duration_seconds": 90
}
```

### GET /api/requests/{id}/cost-result

Response:
```json
{
  "main_product": {
    "product_sys_id": 18234,
    "product_code": "CSTPTY2605000017"
  },
  "bom_chain": [
    {
      "depth": 0,
      "product_sys_id": 18234,
      "product_code": "CSTPTY2605000017",
      "periods": [
        {"period": "202605", "status": "SUCCESS", "captive": 2.45, "delivery": 2.51},
        {"period": "202604", "status": "SUCCESS", "captive": 2.43, "delivery": 2.48},
        {"period": "202603", "status": "PARTIAL", "captive": 2.50, "delivery": 2.55}
      ],
      "param_values": { "1": "PTY", "8": 150, "31": 1.6, ... }
    },
    {
      "depth": 1,
      "product_sys_id": 9876,
      "product_code": "CSTPOY2601000023",
      ...
    }
  ]
}
```

---

## 6. Error Handling & Edge Cases

### Edge case 1: Routing draft references product not yet in master

User reference `"POY 150D/48F"` di routing_draft, tapi POY 150D/48F belum ada di product_master.

**Resolution**: User harus create product master untuk POY tersebut dulu (separate Phase A request atau direct master creation by Engineering). Promote button disabled / error message.

### Edge case 2: Parameter saved for product without active product_order

Scenario: Phase B user input param untuk product yang dibuat manual (bukan dari Phase A promote).

**Resolution**: Tidak ada Phase A request linked → no auto-complete trigger. Parameter tetap tersimpan, calculation engine tetap pickup.

### Edge case 3: Calculation completed but Phase A request was REJECTED

Scenario: Request rejected, tapi product master sudah ada (link via promote sebelumnya). Calculation tetap jalan untuk product master tersebut.

**Resolution**: COSTING_DONE auto-transition (INT-8) tidak terjadi karena status REJECTED tidak match PARAMETER_COMPLETE filter. Cost result tetap tersimpan di DB.

### Edge case 4: Master data changed mid-calculation run

Scenario: Calculation run RUNNING, Admin update cost_master_data.

**Resolution**: Engine sudah load master data ke memory di Stage 1. Run pakai snapshot lama. Next run akan pickup nilai baru. JSONB snapshot di calc result preserve nilai yang dipakai.

### Edge case 5: Multiple concurrent manual triggers

**Resolution**: PostgreSQL advisory lock per period. Second trigger gets ErrConcurrentRun, returns 409. UI disable button while run RUNNING.

### Edge case 6: BOM cycle detected at promote time

**Resolution**: Pre-promote validation — recursive CTE check. If cycle detected → reject promote with clear error: "Component X creates cycle: X → Y → X".

---

## 7. Monitoring & Alerts

### Key metrics

| Metric | Source | Alert threshold |
|---|---|---|
| Calculation run duration | CCR_duration_ms | > 5 minutes |
| Failed run count | CCR_status = FAILED | > 0 per day |
| Partial result rate | CCRE_calc_status = PARTIAL / total | > 10% |
| Required param fill rate | CPP / required_params | < 95% per dept |
| ERP sync lag | sync_log latest | > 2 hours |
| Audit log size | cost_audit_log row count | > 100M rows |

### Dashboards

- **Operations**: Active runs, recent failures, pending requests.
- **Quality**: Param fill rate per dept, partial rate per period.
- **Performance**: Calc duration trend, query latency.

---

## 8. Deployment Considerations

### Service deployment unit options

```
Option A: Monolith Backend
  ├── HTTP API (Phase A, B, C UI backend)
  └── Calc Engine (worker mode, in-process)

Option B: Separated
  ├── HTTP API Service
  └── Calc Engine Service (separate process)
```

**Recommendation**: Option A for MVP, refactor to B if scaling needs.

### Database schema migration

Schema split per phase logically tapi shared physical database.

```
Migrations:
  v1.0_phase_a_initial.sql           ← Phase A tables
  v1.1_phase_a_feasibility.sql       ← Phase A v1.1 updates
  v1.2_phase_b_initial.sql           ← Phase B tables
  v1.3_phase_b_addendum.sql          ← Phase B addendum (param master, master pattern)
  v1.4_phase_c_calculation.sql       ← Phase C tables
```

Use migration tool: golang-migrate, Liquibase, atau Flyway.

---

## 9. Open Integration Questions

1. **ERP sync mechanism** — CDC, scheduled, atau DB link? Depends on infrastructure team capability.

2. **Notification delivery** — in-app only, atau juga email/Slack/MS Teams? Per user preference (CNP table).

3. **Reporting integration** — export ke BI tool (Power BI, Tableau, Looker)? Atau in-app report only?

4. **Outbound to ERP** — push cost result back ke ERP setelah COSTING_DONE? Out-of-scope MVP but plausible future.

5. **Audit retention policy** — 5 tahun, atau archive ke cold storage setelah 1 tahun?

---

## 10. References

- ERD Master: `ERD_Master.md`
- Glossary: `GLOSSARY.md`
- PRD Phase A: `PRD_PhaseA_ProductRequest_v1.1.md`
- PRD Phase B: `PRD_PhaseB_ProductOrder_v1.4.md`
- PRD Phase C: `PRD_PhaseC_ParameterEntry_v1.0.md`
- Engine Blueprint: `CALCULATION_ENGINE_BLUEPRINT.md`
