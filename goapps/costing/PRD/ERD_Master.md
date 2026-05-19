---
title: "ERD Master — Costing Workflow Suite"
version: "1.0"
status: "Draft"
last_updated: "2026-05"
related:
  - "PRD_PhaseA_ProductRequest_v1.1.md"
  - "PRD_PhaseB_ProductOrder_v1.4.md"
  - "PRD_PhaseC_ParameterEntry_v1.0.md"
---

# ERD Master — Costing Workflow Suite

> *Consolidated data model across Phase A, B, and C. 34 tables, zero prefix collision.*

---

## 1. Module Prefix Convention

All tables use module prefix `cost_` + column prefix derived from table initials. Column names globally unique — eliminates need for table alias in JOIN queries.

```
cost_product_request → prefix CPR_ → CPR_request_id, CPR_status, CPR_title, ...
cost_product_master  → prefix CPM_ → CPM_product_sys_id, CPM_product_code, ...
```

Rules:
- Module prefix `cost_` mandatory.
- Column prefix = initial letters of all words in table name.
- Max 5 characters (3-4 ideal).
- All columns prefixed without exception (PK, FK, audit fields).

---

## 2. Complete Prefix Registry

### Phase A — Product Request (14 tables)

| Prefix | Table | Category |
|---|---|---|
| CPR_ | cost_product_request | Core |
| CPS_ | cost_product_spec | Core |
| CRT_ | cost_request_type | Master |
| CRR_ | cost_routing_rule | Config |
| CRD_ | cost_routing_draft | Shadow (B) |
| CRDC_ | cost_routing_draft_component | Shadow (B) |
| CRC_ | cost_request_comment | Communication |
| CCEH_ | cost_comment_edit_history | Audit |
| CRM_ | cost_request_mention | Communication |
| CA_ | cost_attachment | Supporting |
| CURM_ | cost_user_role_mapping | Auth |
| CN_ | cost_notification | Communication |
| CNP_ | cost_notification_preference | Config |
| CAL_ | cost_audit_log | Audit (shared) |

### Phase B — Product Order & BOM (16 tables)

| Prefix | Table | Category |
|---|---|---|
| CPT_ | cost_product_type | Master |
| CPCC_ | cost_product_code_counter | Utility |
| CPM_ | cost_product_master | Core |
| CRMT_ | cost_rm_type | Master |
| CEI_ | cost_erp_item | ERP Replica |
| CEG_ | cost_erp_grade | ERP Replica |
| CES_ | cost_erp_shade | ERP Replica |
| CPO_ | cost_product_order | Core |
| CPOV_ | cost_product_order_version | Core |
| CPOC_ | cost_product_order_component | Core |
| CPOE_ | cost_product_order_exploded | Materialized View |
| CBL_ | cost_bom_layout | Supporting |
| CPRM_ | cost_parameter_master | Master |
| CMD_ | cost_master_definition | Master Registry |
| CMSD_ | cost_master_data | Master Data |
| CPP_ | cost_product_parameter | Static Values |
| CPRD_ | cost_parameter_dependency | Visualization |

### Phase C — Calculation Engine (4 tables)

| Prefix | Table | Category |
|---|---|---|
| CCP_ | cost_calculation_period | Period Mgmt |
| CCR_ | cost_calculation_run | Execution |
| CCRE_ | cost_calculation_result | Output |
| CPPP_ | cost_product_parameter_period | Dynamic Values |

**Total: 34 tables. Zero prefix collision.**

---

## 3. High-Level Relationship Map

```
┌────────────────────────────────────────────────────────────────────────┐
│                          PHASE A — Product Request                      │
│                                                                         │
│  cost_product_request ──┬── cost_product_spec (1:0..1)                  │
│         │                ├── cost_routing_draft (1:N)                   │
│         │                │       └── cost_routing_draft_component (1:N)│
│         │                ├── cost_request_comment (1:N)                 │
│         │                │       ├── cost_comment_edit_history (1:N)    │
│         │                │       └── cost_request_mention (1:N)         │
│         │                ├── cost_attachment (1:N, request-level)       │
│         │                └── cost_notification (1:N)                    │
│         │                                                               │
│         │   CPR_resolved_product_sys_id (denormalized FK to Phase B)   │
│         └────────────────────────────────────┐                          │
└──────────────────────────────────────────────┼──────────────────────────┘
                                               │
                                               ▼
┌──────────────────────────────────────────────┼──────────────────────────┐
│                          PHASE B — Product Order, BOM, Parameter Master │
│                                               │                          │
│  cost_product_master ◄────────────────────────┘                          │
│         │                                                                │
│         ├── cost_product_type (N:1)                                      │
│         ├── cost_product_order (1:0..1)                                  │
│         │       └── cost_product_order_version (1:N)                     │
│         │               └── cost_product_order_component (1:N)           │
│         │                   ├── FK product_master (Captive Cost)         │
│         │                   ├── FK cost_erp_item (Store Rate)            │
│         │                   └── FK cost_rm_type                          │
│         │                                                                │
│         ├── cost_product_parameter (1:N) ← static values per product    │
│         │       └── FK cost_parameter_master                             │
│         │                                                                │
│         └── (used as RM ref in components above)                         │
│                                                                          │
│  cost_parameter_master ─── cost_parameter_dependency (visualization)     │
│                       └─── cost_master_definition (lookup target)        │
│                                       └── cost_master_data               │
│                                                                          │
│  ERP Replica: cost_erp_item / cost_erp_grade / cost_erp_shade            │
│                                                                          │
└──────────────────────────────────────────────┬──────────────────────────┘
                                               │
                                               ▼
┌──────────────────────────────────────────────┼──────────────────────────┐
│                          PHASE C — Calculation Engine                    │
│                                               │                          │
│  cost_calculation_period (CCP) ──── cost_calculation_run (1:N)           │
│                                            └── cost_calculation_result   │
│                                                       │                  │
│                                                       └── FK product_master
│                                                                          │
│  cost_product_parameter_period ← dynamic values per period               │
│       ├── FK cost_product_master                                         │
│       └── FK cost_parameter_master                                       │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘

Shared Across All Phases:
  cost_audit_log ── tracks all mutations
  cost_user_role_mapping ── authentication
```

---

## 4. Key Foreign Key Relationships

### Phase A → Phase B (Promote Flow)

```
cost_routing_draft.CRD_linked_product_order_id
  → cost_product_order.CPO_order_id

cost_product_request.CPR_resolved_product_sys_id (denormalized)
  → cost_product_master.CPM_product_sys_id
```

### Phase B Internal — Product Master is Hub

```
cost_product_order.CPO_product_sys_id → cost_product_master.CPM_product_sys_id
cost_product_parameter.CPP_product_sys_id → cost_product_master.CPM_product_sys_id
cost_product_order_component.CPOC_rm_product_sys_id → cost_product_master (Captive Cost)
cost_product_order_component.CPOC_rm_master_item_id → cost_erp_item (Store Rate)
```

### Phase B → Parameter Master

```
cost_product_parameter.CPP_param_id → cost_parameter_master.CPRM_param_id
cost_parameter_master.CPRM_lookup_master_code → cost_master_definition.CMD_master_code
cost_master_data.CMSD_master_id → cost_master_definition.CMD_master_id
```

### Phase C → Phase B References

```
cost_calculation_result.CCRE_product_sys_id → cost_product_master.CPM_product_sys_id
cost_product_parameter_period.CPPP_product_sys_id → cost_product_master.CPM_product_sys_id
cost_product_parameter_period.CPPP_param_id → cost_parameter_master.CPRM_param_id
```

### Phase C Internal

```
cost_calculation_period (1) → cost_calculation_run (N)
cost_calculation_run (1) → cost_calculation_result (N)
```

---

## 5. Data Flow Across Phases

### Flow 1: Request → Product Master Creation

```
1. Marketing submit Product Request (Phase A)
   ├── cost_product_request (status: SUBMITTED)
   └── cost_product_spec (jika new product)

2. PIC Engineering review & feasibility (Phase A)
   ├── cost_product_request.feasibility_decision = FEASIBLE
   └── status → ROUTING_DEFINED

3. Engineering create routing draft (Phase A)
   ├── cost_routing_draft (seeded from spec: shade, raw_material)
   └── cost_routing_draft_component (free-text refs)

4. PIC promote routing draft → Product Master + Product Order (Phase A → B)
   ├── Manual select/create cost_product_master
   │     └── Auto-generate CSTPTY2605000001 via cost_product_code_counter
   ├── Create cost_product_order linked to product_master
   ├── Copy components from routing_draft → cost_product_order_component
   ├── Update cost_routing_draft.CRD_linked_product_order_id
   └── Update cost_product_request.CPR_resolved_product_sys_id (denorm)

5. Status → PARAMETER_PENDING (Phase A)
```

### Flow 2: Parameter Entry → Auto-Complete Detection

```
1. Departments fill required parameters
   ├── Static (e.g., Denier) → cost_product_parameter (CPP)
   └── Dynamic (e.g., RM Rate) → cost_product_parameter_period (CPPP, Phase C)

2. Service layer trigger on save:
   ├── Call get_missing_required_params(product_sys_id, period)
   ├── If empty → find Phase A request linked
   └── If request.status = PARAMETER_PENDING:
       └── Auto-transition to PARAMETER_COMPLETE (monotonic)

3. cost_audit_log records every parameter change
   └── Surface di Phase A activity timeline
```

### Flow 3: Daily Scheduled Calculation

```
1. Cron triggers at 02:00 WIB → Calculation Engine (Go)

2. Engine creates cost_calculation_run (status: PENDING)
   ├── CCR_period = current OPEN period
   └── CCR_trigger_type = SCHEDULED

3. Pipeline 6 stages:
   ├── Load data (5 parallel queries)
   ├── Topological sort (params + products)
   ├── Worker dispatch (goroutines)
   ├── Calculate (per product, walk params in order)
   ├── Batch write to cost_calculation_result
   └── Finalize cost_calculation_run

4. Auto-activate run if SUCCESS/PARTIAL
   ├── set_run_active(new_run_id)
   └── Previous active run → is_active = false

5. Phase A cost result panel auto-refresh with latest values
```

### Flow 4: Master Data Change → Cost Re-Calculation

```
1. Admin update master coefficient (cost_master_data)
   ├── INSERT/UPDATE CMSD with new attributes JSONB
   └── cost_audit_log records change

2. Wait for next scheduled run (daily) OR manual trigger
   └── No bouncing of Phase A request state (status stays at PARAMETER_COMPLETE)

3. New calculation run picks up updated master
   ├── cost_calculation_result.CCRE_param_values JSONB snapshots new value
   └── Audit-perfect: old runs preserve old values
```

---

## 6. Audit Trail Architecture

All write operations across phases logged in `cost_audit_log` (CAL_) using polymorphic pattern:

```sql
CAL_entity_type  → 'cost_product_request' / 'cost_product_master' / 'cost_master_data' / dst
CAL_entity_id    → PK of affected record
CAL_operation    → INSERT / UPDATE / DELETE / STATUS_CHANGE / FEASIBILITY
CAL_before_data  → JSONB snapshot pre-mutation
CAL_after_data   → JSONB snapshot post-mutation
CAL_user_id      → who
CAL_performed_at → when
```

Retention: 5 years.

---

## 7. Cross-Phase Indexes for Performance

### Hot query: "find Phase A request for a product"

```sql
CREATE INDEX idx_cpr_resolved_product
  ON cost_product_request(CPR_resolved_product_sys_id)
  WHERE CPR_resolved_product_sys_id IS NOT NULL;
```

### Hot query: "latest cost for a product"

```sql
-- Via materialized view or function:
CREATE INDEX idx_ccre_product_period_active
  ON cost_calculation_result(CCRE_product_sys_id, CCRE_period DESC)
  INCLUDE (CCRE_captive_cost, CCRE_delivery_cost, CCRE_calc_status);
```

### Hot query: "where-used for a master item"

```sql
CREATE INDEX idx_cpoc_rm_master_item
  ON cost_product_order_component(CPOC_rm_master_item_id)
  WHERE CPOC_rm_master_item_id IS NOT NULL;
```

---

## 8. Naming Convention Examples

```sql
-- ✅ GOOD — zero alias needed
SELECT
  CPR_request_no,
  CPR_status,
  CPM_product_code,
  CCRE_captive_cost,
  CCRE_calc_status
FROM cost_product_request
JOIN cost_product_master ON CPR_resolved_product_sys_id = CPM_product_sys_id
JOIN cost_calculation_result ON CCRE_product_sys_id = CPM_product_sys_id
JOIN cost_calculation_run ON CCRE_run_id = CCR_run_id
WHERE CCR_is_active = true
  AND CCR_period = '202605';
-- Globally unique columns, no ambiguity
```

```sql
-- Cross-phase audit query
SELECT
  CAL_performed_at,
  CAL_user_id,
  CAL_entity_type,
  CAL_operation,
  CAL_before_data,
  CAL_after_data
FROM cost_audit_log
WHERE CAL_entity_type IN ('cost_product_parameter', 'cost_product_parameter_period', 'cost_master_data')
  AND CAL_performed_at >= '2026-05-01'
ORDER BY CAL_performed_at DESC;
```

---

## 9. ERD Visual (Reference)

```
PHASE A                          PHASE B (Product Master Hub)        PHASE C
──────                          ────────────────────────────         ──────

[Request]──┬──[Spec]            [ProductType]                       [Period]
   │       │                          │                                │
   │       └──[Routing Draft]        ▼                                ▼
   │             │             [ProductMaster]◄─────┐         [Calc Run]
   │             │                   │              │                │
   │             └──[Components]     ├──[Order]    │                ▼
   │                                 │   └──[Version]─[Components]  [Calc Result]
   │                                 │                              │ │
   │                                 ├──[Param Static]              │ ▼
   │                                 │                              │ FK Product
   │                                 ▼                              │
   │                          [Parameter Master]                    └─[Param Period]
   │                                 │
   │                          [Master Definition]
   │                                 │
   │                          [Master Data]
   │
   └──[Audit Log] (shared cross-phase)
```

---

## 10. References

- DDL: `phase_a_ddl.sql`, `phase_b_ddl.sql`, `phase_b_addendum_v1.4_ddl.sql`, `phase_c_ddl.sql`
- PRDs: `PRD_PhaseA_v1.1.md`, `PRD_PhaseB_v1.4.md`, `PRD_PhaseC_v1.0.md`
- Engine Blueprint: `CALCULATION_ENGINE_BLUEPRINT.md`
- Glossary: `GLOSSARY.md`
- Integration: `INTEGRATION_CrossPhase.md`
