---
title: "Glossary — Costing Workflow Suite"
version: "1.0"
status: "Draft"
last_updated: "2026-05"
audience: "All stakeholders — Marketing, Engineering, Production, Finance, IT"
---

# Glossary — Costing Workflow Suite

> *Consolidated terminology across Phase A, B, C — domain, business, and technical terms.*

---

## A. Business Domain Terms (Yarn Manufacturing)

| **Term** | **Definition** |
|---|---|
| **ATY** | Air Textured Yarn — yarn dengan tekstur dari proses air-jet, biasanya untuk fabric tertentu. |
| **Batch Weight** | Total berat satu batch heatset (PARAM 47): `trolley × bobbin/trolley × bobbin_weight / 0.95`. |
| **BC Grade** | Grade B dan C — quality lebih rendah, dijual sebagai by-product. |
| **Bobbin** | Tabung kosong tempat yarn digulung. Berbagai ukuran: paper tube, plastic, dll. |
| **BOM (Bill of Materials)** | Daftar struktural komponen pembentuk satu produk. Multi-level karena product bisa jadi RM untuk product lain. |
| **Box** | Kemasan luar berisi multiple bobbin. JUMBO/NORMAL/PALLET. |
| **Captive Cost** | Cost product dihitung dari komponennya (Captive Cost = sum of bottom-up). Lawannya Store Rate. |
| **Changeover Loss** | KGS yarn yang hilang karena pergantian setting mesin. Configured per machine. |
| **Chips (Polyester Chips)** | Raw material polyester dalam bentuk chips/pellets. Variants: SD (Semi-Dull), BRT (Bright), Recycle. |
| **Cross Section** | Bentuk filament: RND (Round), TBL (Trilobal), PLUS (Plus-shape), dll. |
| **Cyl Type** | Cylinder type pada mesin texturizing/twisting. Tertentu per produk. |
| **Denier** | Unit ukuran ketebalan filament. Standar yarn industry. Higher denier = thicker. |
| **Dozing** | Persentase masterbatch (dye) yang di-dose ke yarn. Affect color & cost. |
| **FG (Finished Goods)** | Product akhir yang siap dijual. |
| **Filament** | Untai panjang penyusun yarn. Multi-filament = banyak filament dipilin jadi satu yarn. |
| **Grade (AX, AE, A9, A, B, C)** | Quality classification yarn. AX paling tinggi, C paling rendah. Dijual dengan harga berbeda. |
| **Heatset** | Proses thermal treatment yarn — set twist, stabilize structure. |
| **Intermingling** | Proses entangle filament. Cost component terpisah. |
| **ITY** | Intermingled Textured Yarn — yarn textured with intermingling process. |
| **Landed Cost** | RM cost termasuk biaya import (duty, freight, dll). Bukan ex-works price. |
| **Masterbatch (MB)** | Concentrated pigment/dye pellets yang di-blend ke polymer untuk pewarnaan. |
| **MB / SP** | Masterbatch / Spin Pack — combined dye + filter pack. |
| **Mother Yarn** | Untuk product seperti Multi Yarn (TTM): yarn dasar yang nanti di-combine. |
| **Multi Yarn** | Product yang dibentuk dari menggabungkan multiple yarn berbeda (mis. TTM). |
| **Net Bobbin Weight** | Berat yarn bersih per bobbin, weighted average semua grade (PARAM 31). |
| **OPU** | Oil Pick-Up — persentase oil yang menempel pada yarn. Affect oil cost & physical properties. |
| **Packing Cost** | Cost dari bobbin + box per kg yarn. Captive (internal) vs Delivery (untuk dijual). |
| **PIC** | Person-In-Charge — assignee yang punya tanggung jawab atas suatu task atau request. |
| **POY** | Partially Oriented Yarn — intermediate product, biasanya jadi raw material untuk PTY/TTY/dll. |
| **PTY** | Polyester Textured Yarn — POY yang sudah di-texture. Salah satu FG utama. |
| **Raw Material (RM)** | Material input untuk produksi. Bisa berupa chips (level 1), POY (level 2), atau intermediate product lain. |
| **Routing** | Definisi step produksi: machine apa, settings apa, urutannya bagaimana. Setiap product punya routing. |
| **RWDH** | Salah satu special process flag yang affect cost calculation (PARAM 71). |
| **Shade** | Warna yarn. Bisa standard (FK ke master) atau custom (free-text seperti "natural", "biru langit"). |
| **Softner Cost** | Cost dari proses pelunakan yarn (chemical treatment). |
| **Spares Cost** | Cost spare part mesin per kg yarn. |
| **Steam Cost (CNG)** | Cost steam (uap) per kg yarn, biasanya untuk superba steaming. |
| **Superba** | Steam processing line untuk yarn. Specific equipment & cost. |
| **TCH** | Variant yarn lain (singkatan internal). |
| **TCY** | Yarn type variant. Specific to internal taxonomy. |
| **TPM (Twist Per Meter)** | Jumlah twist per meter yarn. Higher TPM = more torque. |
| **Trolley** | Container untuk transport multiple bobbin dalam proses heatset. |
| **TTM** | Twisted Tape Multi-yarn — yarn jenis tertentu (composite). |
| **TTS** | Twisted Tape Stretch — variant. |
| **TTY** | Twisted Textured Yarn — yarn textured & twisted. |
| **Value Loss** | Cost differential antara grade A dengan grade lebih rendah. Quality penalty. |
| **Volume Bucket** | Tier of volume per order. Affect cost via changeover loss allocation. |
| **Washing Cost** | Cost untuk proses pencucian yarn. |
| **Yarn Type** | Klasifikasi utama yarn: POY, PTY, TTY, ATY, ITY, TCH, TTM, TTS. |

---

## B. Application & Workflow Terms

| **Term** | **Definition** |
|---|---|
| **Active Run** | Calculation run yang hasilnya dianggap authoritative untuk satu period. Max 1 per period. |
| **Activity Timeline** | Cronological event log per request di Phase A — termasuk parameter changes & master changes. |
| **Admin Panel** | Dashboard untuk Admin: manage period, master data, trigger run, view all. |
| **Auto-Transition** | Sistem otomatis ubah status request berdasarkan kondisi (e.g., PARAMETER_PENDING → COMPLETE). |
| **Cascade Failure** | Saat failed product (Z) affect dependent product (X) — X.rm_cost dari Z = 0, X marked PARTIAL. |
| **Calculation Period** | Bulan kalender unit untuk costing, format YYYYMM (e.g., "202605"). Lifecycle: OPEN → CLOSED. |
| **Calculation Run** | Eksekusi calculation engine. Multiple runs per OPEN period. Each run produces full result set. |
| **Captive Pack Code** | Kode pack code untuk internal/captive consumption (PARAM 32). |
| **Calc Function Key** | String identifier yang map ke Go calculation function. Stored di `cost_parameter_master.CPRM_calc_function_key`. |
| **Delivery Pack Code** | Kode pack code untuk delivery/external sale (PARAM 38). |
| **Department Completion Dashboard** | Dashboard per departemen menampilkan progress parameter filling. |
| **Display Group** | UI grouping untuk parameter (Spec, Machine, Grade, dll). |
| **Display Order** | Urutan parameter ditampilkan di form. |
| **Dynamic Parameter** | Parameter dengan `is_period_dependent = true`, value berubah per period. |
| **Feasibility Gate** | Gate decision di UNDER_REVIEW state Phase A: FEASIBLE / NOT_FEASIBLE. |
| **First-Time Auto Complete** | Auto-transition ke PARAMETER_COMPLETE terjadi sekali pertama kali semua required terisi; tidak bounce back. |
| **JSONB Parameter** | Parameter dengan complex structure disimpan sebagai JSON (e.g., Raw Material JSONB). |
| **Manual Trigger** | Calculation dipicu user via tombol (vs scheduled). |
| **Monotonic State Machine** | State machine yang hanya bergerak maju, tidak bounce back. Phase A request: setelah COMPLETE, tidak kembali PENDING walau param berubah. |
| **OPEN Period** | Period yang masih bisa di-run calculation. Hanya 1 OPEN sekaligus. |
| **CLOSED Period** | Period yang sudah final. Tidak bisa di-run ulang atau update param. |
| **Partial Result** | Calculation berhasil tapi dengan warning (missing non-required param, atau dep cost = 0 karena cascade). |
| **PARAMETER_PENDING** | State Phase A: routing sudah defined, sedang menunggu departemen isi parameter. |
| **PARAMETER_COMPLETE** | State Phase A: semua required parameter sudah terisi. Cost calculation siap berjalan. |
| **Product Spec** | Section di Phase A form untuk product baru: raw material, shade, paper tube, box, dll. |
| **Promote Routing Draft** | Aksi Phase A → Phase B: routing_draft dipromote menjadi cost_product_order resmi. |
| **Required Parameter** | Parameter dengan `is_required_for_costing = true`. Wajib diisi untuk dapat cost. |
| **Resolved Product Sys ID** | Denormalized FK di `cost_product_request` ke product_master setelah promote. Untuk fast lookup. |
| **Routing Draft** | Phase A shadow object untuk siapkan BOM sebelum promote ke real cost_product_order. |
| **Scheduled Trigger** | Calculation otomatis daily oleh cron job, hanya untuk current OPEN period. |
| **Static Parameter** | Parameter dengan `is_period_dependent = false`, value tetap (tidak per period). |
| **Two-Source Calculation** | Engine resolve param value dari dua sumber: stored value (ENTRY/JSONB) atau Go function (CALCULATION/LOOKUP). Tidak ada per-product override. |
| **Topological Order** | Urutan eksekusi yang menghormati dependency. Parameter dengan dependency dihitung dulu. |

---

## C. Master Data Terms

| **Term** | **Definition** |
|---|---|
| **Generic Master Pattern** | Single pair of tables (`cost_master_definition` + `cost_master_data`) untuk semua master types. Schema-less via JSONB attributes. |
| **Master Code** | Unique identifier dari master type (e.g., "MACHINE", "BOX_BOBBIN_COST"). |
| **Master Data Row** | Single record di `cost_master_data` representing one master entity (e.g., one machine, one box code). |
| **Master Definition** | Type registry record yang declare master type (e.g., MACHINE has attributes_schema). |
| **Period-Dependent Master** | Master type dengan `is_period_dependent = true`. Value berubah per period (mis. RM rate). |
| **Box & Bobbin Cost (BOX_BOBBIN_COST)** | Master period-dependent: cost box & bobbin per pack code, per period. |
| **Machine Master (MACHINE)** | Non-period master: atribut mesin (power, manpower, overhead, spares per day). |
| **Intermingling Cost** | Period-dependent master: cost intermingling per type per period. |
| **Param Data (PARAM_DATA)** | Generic period-dependent master untuk steam, softner, washing cost. |
| **Product Grade Loss (PRODUCT_GRADE)** | Master config untuk grade loss percentages. |
| **Volume Bucket (VOLUME_BUCKET)** | Master config untuk volume tier per machine. |
| **Changeover Loss (CHANGEOVER_LOSS)** | Master config KGS lost per machine due to changeover. |
| **Yarn Type Master (YARN_TYPE)** | Master config yarn types (POY, PTY, TTY, dll). |

---

## D. ERP Integration Terms

| **Term** | **Definition** |
|---|---|
| **ERP** | Enterprise Resource Planning — sistem core untuk inventory, sales, accounting. Oracle-based di company ini. |
| **ERP Replica** | Tables di PostgreSQL yang menyimpan copy data dari ERP (cost_erp_item, cost_erp_grade, cost_erp_shade). |
| **ERP 3-Part Key** | Oracle ERP unique key product: item_code + grade_code_1 + grade_code_2. Replicated as attributes di `cost_product_master`. |
| **Item Code** | ERP item identifier (one of 3-PK components). |
| **Grade Code 1 / Grade Code 2** | ERP grade differentiator codes (other 2 components of 3-PK). |
| **Sync Mechanism** | Process untuk replicate data ERP → PostgreSQL. Options: CDC, DB link, scheduled, API. TBD. |
| **Store Rate** | Cost model dimana RM cost ambil dari ERP item directly (vs Captive Cost yang internal). |

---

## E. Technical / Architecture Terms

| **Term** | **Definition** |
|---|---|
| **Advisory Lock** | PostgreSQL lock mechanism digunakan calculation engine untuk prevent concurrent runs. |
| **Append-Only** | Strategy: insert new records vs update — preserves history. Used di calculation_result. |
| **CDC (Change Data Capture)** | Technique untuk capture row-level changes dari source DB ke replicate. Candidate untuk ERP sync. |
| **Column Prefix Convention** | Naming rule: column = prefix + name. Prefix derived from table name initials. Eliminate alias needs. |
| **COPY (PostgreSQL)** | Bulk-insert mechanism, 10-100x faster than batch INSERT. Used by engine batch writer. |
| **CTE (Common Table Expression)** | SQL `WITH` clause untuk multi-step query. Used for recursive BOM traversal. |
| **Denormalized FK** | Foreign key yang stored di "wrong" side untuk speed up lookup. E.g., `CPR_resolved_product_sys_id`. |
| **Effective Dating** | Pattern: store `effective_from / effective_to` di records untuk audit & versioning. |
| **Hot Path** | Code path yang dieksekusi sangat sering (e.g., calculation per product). Optimize aggressively. |
| **Idempotency** | Operation yang aman dijalankan multiple times. Critical untuk retry safety. |
| **JSONB** | PostgreSQL binary JSON type. Indexable via GIN. Used untuk flexible attributes (master_data, calc result snapshot). |
| **N+1 Query** | Anti-pattern: 1 query untuk parent + N queries untuk children. Engine avoid this via bulk loading. |
| **Partial Unique Index** | PostgreSQL index dengan WHERE clause. Used untuk constraint "max 1 OPEN period" / "max 1 active run". |
| **Polymorphic Audit** | Pattern: audit table dengan `entity_type + entity_id` untuk track changes across many tables. Used di `cost_audit_log`. |
| **Topological Sort** | Algorithm sort nodes in DAG sehingga dependencies come first. Used both for params (intra-product) and products (inter-product). |
| **Pipeline (6-Stage)** | Calculation engine flow: Load → Resolve → Dispatch → Calculate → Write → Audit. |
| **Worker Pool** | Pattern: fixed number of goroutines/threads processing tasks from queue. Engine uses NumCPU workers. |

---

## F. Phase-Specific Acronyms

### Phase A
- **CPR** = Cost Product Request
- **CPS** = Cost Product Spec
- **CRD** = Cost Routing Draft
- **CAL** = Cost Audit Log

### Phase B
- **CPM** = Cost Product Master
- **CPO** = Cost Product Order
- **CPOV** = Cost Product Order Version
- **CPOC** = Cost Product Order Component
- **CPRM** = Cost Parameter Master
- **CMD** = Cost Master Definition
- **CMSD** = Cost Master Data
- **CPP** = Cost Product Parameter (static)
- **CRMT** = Cost RM Type

### Phase C
- **CCP** = Cost Calculation Period
- **CCR** = Cost Calculation Run
- **CCRE** = Cost Calculation Result
- **CPPP** = Cost Product Parameter Period (dynamic)

---

## G. State Machine Terminology

### Phase A Request States

| State | Meaning |
|---|---|
| **DRAFT** | Request sedang disusun Marketing, belum di-submit. |
| **SUBMITTED** | Request sudah dikirim ke PIC, menunggu review. |
| **UNDER_REVIEW** | PIC sedang review. Akan lanjut ke feasibility decision. |
| **ROUTING_DEFINED** | PIC sudah set FEASIBLE & routing draft sudah dibuat. |
| **PARAMETER_PENDING** | Routing sudah promote ke product_order, menunggu departemen isi parameter. |
| **PARAMETER_COMPLETE** | Semua required parameter terisi. Cost calculation siap. |
| **COSTING_DONE** | Cost result sudah keluar dari calculation engine. |
| **QUOTED** | Marketing sudah submit quote ke customer. |
| **CLOSED** | Request selesai (won/lost/cancelled). |
| **REJECTED** | PIC tolak request (NOT_FEASIBLE). Terminal state. |

### Calculation Period States

| State | Meaning |
|---|---|
| **OPEN** | Period bisa di-run calculation, param values bisa di-update. |
| **CLOSED** | Period final. Tidak bisa run ulang, tidak bisa update param. |

### Calculation Run States

| State | Meaning |
|---|---|
| **PENDING** | Run sudah dicatat tapi belum mulai. |
| **RUNNING** | Engine sedang eksekusi. |
| **SUCCESS** | Semua product berhasil calculate. |
| **PARTIAL** | Sebagian product partial atau failed, tapi run completed. |
| **FAILED** | Run aborted (error fatal). |
| **CANCELLED** | Run dibatalkan manual. |

### Calculation Result States (per product)

| State | Meaning |
|---|---|
| **SUCCESS** | Semua 125 param berhasil dihitung. |
| **PARTIAL** | Ada missing/failed dependency, tapi cost tetap dihitung dengan degradation. |
| **FAILED** | Required param missing — cost tidak bisa dihitung sama sekali. |

---

## H. Common Abbreviations

| Abbr | Full Form |
|---|---|
| API | Application Programming Interface |
| BOM | Bill of Materials |
| CDC | Change Data Capture |
| CRUD | Create, Read, Update, Delete |
| CSV | Comma-Separated Values |
| DAG | Directed Acyclic Graph |
| DB | Database |
| DDL | Data Definition Language |
| DML | Data Manipulation Language |
| ERD | Entity Relationship Diagram |
| ERP | Enterprise Resource Planning |
| FG | Finished Goods |
| FK | Foreign Key |
| IT | Information Technology |
| JSON | JavaScript Object Notation |
| JSONB | Binary JSON (PostgreSQL) |
| MVP | Minimum Viable Product |
| NFR | Non-Functional Requirement |
| OPU | Oil Pick-Up |
| PIC | Person-In-Charge |
| PK | Primary Key |
| PRD | Product Requirements Document |
| PRM | Parameter |
| RBAC | Role-Based Access Control |
| RM | Raw Material |
| SDK | Software Development Kit |
| SSO | Single Sign-On |
| SQL | Structured Query Language |
| TBD | To Be Determined |
| TPM | Twist Per Meter |
| UAT | User Acceptance Testing |
| UI | User Interface |
| UTC | Coordinated Universal Time |
| UUID | Universally Unique Identifier |
| WIB | Waktu Indonesia Barat (UTC+7) |
| YYYYMM | Year-Month format (e.g., 202605) |

---

## I. Reference Links

- PRD Phase A: `PRD_PhaseA_ProductRequest_v1.1.md`
- PRD Phase B: `PRD_PhaseB_ProductOrder_v1.4.md`
- PRD Phase C: `PRD_PhaseC_ParameterEntry_v1.0.md`
- ERD Master: `ERD_Master.md`
- Integration Doc: `INTEGRATION_CrossPhase.md`
- Engine Blueprint: `CALCULATION_ENGINE_BLUEPRINT.md`
- DDL Files: `phase_a_ddl.sql`, `phase_b_ddl.sql`, `phase_b_addendum_v1.4_ddl.sql`, `phase_c_ddl.sql`
