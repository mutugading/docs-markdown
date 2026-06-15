# Task List & Status — Costing Workflow Suite
## Per 15 Juni 2026

> Derived dari analisis GitHub commits, migrations, dan domain structure.
> Status didasarkan pada keberadaan kode di repository, bukan dari ClickUp.

---

## Phase B — Product Order & BOM Management

### Backend (goapps-backend / finance service)

| # | Task | Status | Bukti |
|---|------|--------|-------|
| B-BE-01 | Product Type master CRUD | ✅ Done | migration 000100, domain/costproducttype, commit 12 Jun |
| B-BE-02 | Product Code counter (atomic) | ✅ Done | migration 000101 |
| B-BE-03 | RM Type master CRUD | ✅ Done | migration 000102, domain/costrmtype |
| B-BE-04 | ERP Item/Grade/Shade replica tables | ✅ Done | migration 000103-000105 |
| B-BE-05 | Product Master CRUD | ✅ Done | migration 000106, domain/costproductmaster |
| B-BE-06 | Product Order + Version | ✅ Done | migration 000107-000108 |
| B-BE-07 | BOM Component (dual FK) | ✅ Done | migration 000109 |
| B-BE-08 | Route DAG / BOM Graph | ✅ Done | migration 000222, domain/costroute/graph.go |
| B-BE-09 | Parameter Master (125 params) | ✅ Done | migration 000216 + seed 000234 |
| B-BE-10 | Static Parameter per product (CPP_) | ✅ Done | migration 000217, domain/costproductparameter |
| B-BE-11 | Import/Export async (product master, parameter) | ✅ Done | migration 000377-000378, domain/costimportjob |
| B-BE-12 | BOM Explosion API | 🔲 Pending | Tidak ditemukan di kode |
| B-BE-13 | Where-Used API | 🔲 Pending | Tidak ditemukan di kode |
| B-BE-14 | Cycle Detection | ⚠️ Partial | costroute/graph.go ada — belum clear apakah sudah di-wire ke save flow |
| B-BE-15 | Costing Orchestrator (topological sort) | 🚧 In Progress | finance-cost-orchestrator service sudah ada struktur |
| B-BE-16 | Calculation Worker | 🚧 In Progress | finance-cost-worker service sudah ada struktur |

### Frontend (goapps-frontend / finance pages)

| # | Task | Status | Bukti |
|---|------|--------|-------|
| B-FE-01 | Product Type pages | ✅ Done | commit 12-13 Jun (import/export hooks) |
| B-FE-02 | Product Master pages | ✅ Done | import/export toolbar, hooks |
| B-FE-03 | Parameter pages (fill tracking) | ✅ Done | FillTrackingDrawer, commit 11-13 Jun |
| B-FE-04 | BOM Visual Flow Editor (React Flow) | 🔲 Pending | Tidak ditemukan di frontend |
| B-FE-05 | BOM Explosion Report UI | 🔲 Pending | — |
| B-FE-06 | Where-Used Report UI | 🔲 Pending | — |

---

## Phase A — Product Request Module

### Backend

| # | Task | Status | Bukti |
|---|------|--------|-------|
| A-BE-01 | Product Request CRUD + State Machine | ✅ Done | domain/costproductrequest/state_machine.go, entity.go |
| A-BE-02 | Product Spec (conditional new product) | ✅ Done | migration 000203 |
| A-BE-03 | Routing Rule (auto-assign / triage) | ✅ Done | migration 000204, domain/costroutingrule |
| A-BE-04 | Comment + Edit History | ✅ Done | migration 000207-000208 |
| A-BE-05 | Attachment | ✅ Done | migration 000210 |
| A-BE-06 | Notification | ✅ Done | migration 000213, domain/costnotification |
| A-BE-07 | Audit Log | ✅ Done | migration 000215 |
| A-BE-08 | User Role Mapping | ✅ Done | migration 000212 |
| A-BE-09 | Workflow instance integration (wfl_instance_id) | ✅ Done | migration 000359 |
| A-BE-10 | Status History | ✅ Done | migration 000372 |
| A-BE-11 | Fill Task / Parameter Assignment | ✅ Done | migration 000360-000368 |

### Frontend

| # | Task | Status | Bukti |
|---|------|--------|-------|
| A-FE-01 | CPR (Product Request) detail page | ✅ Done | two-column bento grid layout, commit 11 Jun |
| A-FE-02 | Comment section dengan avatar | ✅ Done | commit 11 Jun |
| A-FE-03 | Fill Tracking Drawer | ✅ Done | commit 11 Jun |
| A-FE-04 | CPR status history | ✅ Done | proto: GetCostProductRequestHistory, commit 10 Jun |

---

## Phase C — Parameter Entry & Calculation (Beyond PRD Scope)

> **Catatan:** Phase C belum ada PRD formal, tapi implementasi sudah dimulai

| # | Task | Status | Bukti |
|---|------|--------|-------|
| C-BE-01 | Fill Assignment System (Level Config, Fill Task, Approval) | ✅ Done | migration 000360-000368, domain/costfillassignment |
| C-BE-02 | Cost Calculation data model (cst_product_cost) | ✅ Done | migration 000228 |
| C-BE-03 | Calculation Job (cal_job, chunks, products) | ✅ Done | migration 000229-000231 |
| C-BE-04 | Cost History audit | ✅ Done | migration 000232 |
| C-BE-05 | Orchestrator service | 🚧 In Progress | finance-cost-orchestrator structure ada |
| C-BE-06 | Worker service | 🚧 In Progress | finance-cost-worker structure ada |

---

## BI Dashboard Module

> Dashboard BOD diimplementasikan di dalam finance service

| # | Task | Status | Bukti |
|---|------|--------|-------|
| D-BE-01 | BI tables (data source, dashboard, fact metric) | ✅ Done | migration 000300-000310 |
| D-BE-02 | EBITDA dashboard config + KPIs | ✅ Done | migration 000332-000333 |
| D-BE-03 | Net Profit dashboard config + KPIs | ✅ Done | migration 000334 |
| D-BE-04 | Delivery Margin dashboard config + KPIs | ✅ Done | migration 000335 |
| D-BE-05 | Metric Registry | ✅ Done | migration 000321 |
| D-BE-06 | BI Job (ETL scheduler) | ✅ Done | migration 000308 + 000351 |
| D-FE-01 | Dashboard pages | 🚧 In Progress | frontend/app/(dashboard)/dashboard |

---

## IAM Service

| # | Task | Status | Bukti |
|---|------|--------|-------|
| IAM-01 | Auth (login, SSO, refresh) | ✅ Done | services/iam |
| IAM-02 | User management | ✅ Done | |
| IAM-03 | Role & Permission | ✅ Done | |
| IAM-04 | Organization (company/division/dept) | ✅ Done | |
| IAM-05 | Notification service (request notification) | ✅ Done | proto: RequestNotification, commit 9 Jun |

---

## Infrastructure & DevOps

| # | Task | Status | Bukti |
|---|------|--------|-------|
| INF-01 | Docker setup per service | ✅ Done | Dockerfile di setiap service |
| INF-02 | CI/CD GitHub Actions | ✅ Done | .github/workflows |
| INF-03 | RabbitMQ integration (async jobs) | ✅ Done | costing_import routing |
| INF-04 | K8s deployment (staging/production) | ⚠️ Unknown | goapps-infra repo ada tapi belum di-check |

---

## Ringkasan Progress

| Phase | Backend | Frontend | Overall |
|-------|---------|----------|---------|
| Phase A — Product Request | ~90% | ~70% | ~80% |
| Phase B — BOM & Product Order | ~85% | ~50% | ~70% |
| Phase C — Parameter/Calculation | ~60% (beyond PRD) | ~40% | ~50% |
| BI Dashboard | ~90% | ~40% | ~65% |
| IAM | ~95% | ~80% | ~88% |

---

*Dibuat: 15 Juni 2026 | Source: analisis GitHub repos*
*Next update: sprint review berikutnya*
