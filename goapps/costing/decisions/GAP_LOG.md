# Gap Log — PRD vs Implementasi Aktual
## Costing Workflow Suite — PT Mutu Gading Tekstil

> Analisis berdasarkan PRD (docs-markdown) vs implementasi aktual di GitHub
> per tanggal 15 Juni 2026.
> **Sumber data:** goapps-backend, goapps-frontend, goapps-shared-proto

---

## Summary Status

| Kategori | Jumlah |
|----------|--------|
| Implemented sesuai PRD | 18 item |
| Gap — Simplification/MVP adjustment | 5 item |
| Gap — Scope expansion (beyond PRD) | 4 item |
| Pending (di PRD tapi belum ada di kode) | 6 item |

---

## ✅ Implemented Sesuai PRD

| # | Fitur | PRD Reference | Bukti di GitHub |
|---|-------|---------------|----------------|
| 1 | Product Master (CPM_) dengan kode CST prefix | Phase B Section 7.4 | migration 000106, domain/costproductmaster |
| 2 | Product Type master (CPT_) | Phase B Section 7.2.1 | migration 000100, domain/costproducttype |
| 3 | RM Type master (CRMT_) user-definable | Phase B Section 7.2.3 | migration 000102, domain/costrmtype |
| 4 | ERP Replica tables (CEI_, CEG_, CES_) | Phase B Section 7.3 | migration 000103-000105 |
| 5 | Product Order + Version (CPO_, CPOV_) | Phase B Section 7.5 | migration 000107-000108 |
| 6 | BOM Component (CPOC_) dengan dual FK | Phase B Section 7.5.3 | migration 000109 |
| 7 | Product Request (CPR_) dengan state machine | Phase A Section 5 | domain/costproductrequest/state_machine.go |
| 8 | Product Spec (CPS_) conditional form | Phase A v1.1 | migration 000203 |
| 9 | Routing Rule (CRR_) | Phase A Section FR-3 | migration 000204, domain/costroutingrule |
| 10 | Comment (CRC_) + Edit History (CCEH_) | Phase A Section FR-5 | migration 000207-000208 |
| 11 | Attachment (CA_) | Phase A Section FR-5 | migration 000210 |
| 12 | Notification (CN_) | Phase A Section FR-10 | migration 000213 |
| 13 | Audit Log (CAL_) | Phase A+B shared | migration 000215, domain/costauditlog |
| 14 | Parameter Master (CPRM_) 125 params | Phase B Section 7.9.1 | migration 000216, 000234 |
| 15 | Static Parameter (CPP_) per product | Phase B Section 7.9.4 | migration 000217, domain/costproductparameter |
| 16 | Column prefix convention | Phase A+B konvensi | semua migration konsisten |
| 17 | Route DAG structure | Phase B BOM graph | migration 000222, domain/costroute/graph.go |
| 18 | Product Code counter (CPCC_) | Phase B Section 7.2.2 | migration 000101 |

---

## 🟡 Gap — Simplification / MVP Adjustment

| ID | PRD Section | PRD Intent | Implementasi Aktual | Alasan | Status | Approved |
|----|-------------|-----------|--------------------|---------|---------| ---------|
| G-01 | Phase A FR-7 (Routing Draft) | Routing draft sebagai shadow entity Phase B, locked saat PARAMETER_PENDING | Migration 000224: `drop_routing_draft_add_request_link` — routing draft di-drop dan diganti dengan direct link ke route DAG | Route DAG (BOM graph) di Phase B lebih powerful dan menggantikan fungsi routing draft Phase A | **Accepted** | Perlu konfirmasi IT Leader |
| G-02 | Phase A Section 9 (Notification Preference) | User bisa set preferensi notifikasi per trigger type | Migration 000370: `drop_cost_notification_preference` — tabel preference di-drop | Dianggap over-engineering untuk MVP, semua notifikasi aktif by default | **Accepted** | Perlu konfirmasi IT Leader |
| G-03 | Phase B Section 5 (Flow Editor drag-drop) | Flow Editor berbasis React Flow untuk compose BOM | Frontend: belum ada halaman BOM visual editor di dashboard/costing | Masih dalam development — PRD ini adalah Phase B yang on-going | **Pending** | — |
| G-04 | Phase A FR-2 State Machine | Status: DRAFT → SUBMITTED → UNDER_REVIEW → ... → CLOSED | Migration 000371: tambah status `CONFIRM`, `APPROVE`, `RELEASE` di luar state machine PRD | Bisnis requirement berubah — perlu approval chain yang lebih formal | **Pending** | Perlu IT Leader update PRD |
| G-05 | Phase A FR-9 (Permission Matrix) | `cost_user_role_mapping` sebagai tabel permission | Migration 000212 ada, tapi migration 000359 tambah `wfl_instance_id` ke CPR — ada workflow engine baru | Tim developer integrasikan workflow engine external (Smart Workflow dari HRIS) | **Pending** | Perlu IT Leader konfirmasi scope |

---

## 🔴 Gap — Scope Expansion (Beyond PRD)

Item ini tidak ada di PRD tapi sudah diimplementasikan developer.
**Perlu review dan keputusan IT Leader: accept ke PRD atau revert?**

| ID | Fitur | Bukti di GitHub | Analisis | Status |
|----|-------|-----------------|----------|--------|
| G-06 | **Fill Assignment System** — task assignment untuk parameter entry per departemen | migration 000360-000368, domain/costfillassignment (approval.go, task.go, resolver.go) | Ini adalah fitur Phase C (Parameter Entry) yang sudah diimplementasikan di service Phase B. Sangat signifikan — butuh di-document ke PRD Phase C | **Needs Review** |
| G-07 | **Cost Calculation Engine** — finance-cost-orchestrator + finance-cost-worker services | 2 service baru, migration 000228-000232 (cst_product_cost, cal_job, cal_job_chunk, cal_job_product, aud_cost_history) | Ini adalah scope Phase C (calculation engine) yang sudah mulai diimplementasikan | **Needs Review** |
| G-08 | **BI Dashboard Module** — BOD Executive Dashboard di-merge ke finance service | migration 000300-000359, domain/bi, frontend/dashboard pages | PRD Dashboard ada terpisah di docs-markdown. Implementasinya di-gabung ke finance service. Perlu alignment apakah ini intentional | **Needs Review** |
| G-09 | **Import Job System** — async import/export untuk product master dan parameter | migration 000377-000378, domain/costimportjob, RabbitMQ integration | PRD Phase B mention import dari Excel tapi tidak detail. Implementation sudah full async dengan RabbitMQ | **Needs Review** |

---

## ⏳ Pending — Di PRD Tapi Belum Ada di Kode

| ID | Fitur | PRD Reference | Notes |
|----|-------|---------------|-------|
| P-01 | BOM Visual Flow Editor (React Flow) | Phase B FR-18 | Frontend belum ada halaman costing/bom/flow-editor |
| P-02 | BOM Explosion Report | Phase B FR-11 | Belum ada di frontend |
| P-03 | Where-Used Report | Phase B FR-12 | Belum ada di frontend |
| P-04 | Version Comparison (diff view) | Phase B FR-14 | Belum ada |
| P-05 | Admin UI untuk RM Type Management | Phase B FR-26 | Belum ada |
| P-06 | Auto-Complete Hook Phase A → Phase C | Phase B FR-38 | Partial — tabel fill_task ada tapi hook ke CPR status belum clear |

---

*Source: Analisis GitHub repos per 15 Juni 2026*
*Dibuat oleh: Claude (IT Leader workflow)*
*Next review: Sprint review berikutnya*
