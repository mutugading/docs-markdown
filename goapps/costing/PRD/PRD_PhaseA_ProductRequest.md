---
title: "PRD — Costing Workflow Suite, Phase A: Product Request Module"
version: "1.1"
status: "Draft"
phase: "A"
last_updated: "2026-05"
author: "[IT Leader]"
related:
  - "PRD_PhaseB_ProductOrder.md"
  - "ERD_Master.md"
  - "GLOSSARY.md"
changelog:
  - version: "1.1"
    date: "2026-05"
    changes:
      - "Tambah Section 2 Product Specification (conditional form untuk new product)"
      - "Tambah feasibility gate di UNDER_REVIEW (PIC decision: Dapat/Tidak Dapat Dibuat)"
      - "Tambah state REJECTED di state machine"
      - "Redefinisi request type: QUOTE = flexible by classification, DEVELOPMENT = always full flow"
      - "Terapkan Column Prefix Naming Convention (seluruh data model)"
      - "Tambah tabel baru: cost_product_spec (CPS_)"
      - "Tambah seed fields: shade dan raw_material_type dari spec ke routing draft"
---

# PRD — Phase A: Product Request Module
## Costing Workflow Suite

> *Ticketing & Workflow Orchestration untuk Product Costing Request*
> Version 1.1 — Draft | May 2026

---

## 1. Executive Summary

Costing Workflow Suite adalah project IT yang menyatukan rangkaian aktivitas product costing dari hulu (request masuk) hingga hilir (cost result siap dipakai). Phase A ini berfokus pada modul Product Request — sistem ticketing internal yang menghubungkan Marketing, Process Engineering, dan departemen-departemen pemilik parameter cost (Produksi, R&D, Finance, dan lain-lain).

Tujuan utama Phase A adalah menyelesaikan masalah lintas-departemen yang user identifikasi: kesulitan tracking request dari customer, ketidakjelasan pending parameter entry per departemen, dan saling-menyalahkan antar fungsi ketika quote terlambat. Sistem ini tidak menambah enforcement birokratis — fokusnya pada transparansi: setiap request terlihat statusnya, setiap kewajiban terlihat owner-nya, dan setiap keterlambatan terlihat penyebabnya.

Phase A di-deliver standalone — siap dipakai produksi tanpa harus menunggu Phase B (Product Order, sudah didesign di PRD terpisah) atau Phase C (Parameter Entry & Validation). Kontrak interface ke phase berikutnya ditegaskan di dokumen ini agar integrasi nanti tidak butuh refactoring besar.

### 1.1. Visi 3-Phase Project Costing Workflow Suite

| **Phase** | **Status** | **Scope ringkas** |
|---|---|---|
| A — Product Request | Modul ini | Ticketing untuk request, tracking lifecycle, accountability lintas departemen |
| B — Product Order | PRD terpisah, sudah final | Definisi struktur BOM multi-level, visualisasi tree/flow, versioning, costing orchestrator |
| C — Parameter Entry | Belum dimulai | Form entry parameter per departemen dengan workflow validasi dan co-ownership |

## 2. Background & Problem Statement

### 2.1. Konteks Bisnis

Perusahaan menggunakan metode standard costing: cost suatu finished goods (FG) terdiri dari biaya bahan baku ditambah biaya proses. Karena banyak intermediate product yang menjadi bahan baku product di tahap berikutnya, kalkulasi cost bersifat rollup berjenjang. Sebelum cost dapat dihitung, dua hal harus tersedia: definisi product routing (urutan proses) dan parameter cost dari berbagai departemen.

Aktivitas costing biasanya dipicu dari Marketing — yang menerima order atau pertanyaan dari customer/calon customer. Request ini bisa untuk product existing maupun product baru. Marketing membutuhkan harga jual yang memerlukan basis cost yang akurat.

### 2.2. Masalah Saat Ini

Berdasarkan diskusi dengan tim, problem yang dialami:

- Tracking manual: request dari Marketing dikomunikasikan via email/WhatsApp/lisan, tidak ada single source of truth.

- Tidak ada visibilitas progress: Marketing tidak tahu request mereka sampai mana — apakah masih di Process Engineering untuk routing, atau menunggu parameter dari departemen tertentu.

- Pending parameter entry per departemen tidak ter-track: tidak ada daftar terpadu siapa yang harus input apa, kapan.

- Saling menyalahkan antar departemen: ketika quote terlambat, tidak jelas penyebab keterlambatan — apakah Produksi belum input yield rate, atau Finance belum input rate tenaga kerja, atau lainnya.

- Tidak ada audit trail keputusan: tidak ada record kapan request masuk, kapan routing selesai, kapan parameter siap, kapan quote dikirim — sulit melakukan retrospective improvement.

### 2.3. Stakeholders Phase A

| **Stakeholder** | **Kebutuhan Utama** | **Role** |
|---|---|---|
| Marketing | Membuat request, lihat progress, terima notifikasi quote ready | Tier: User · Functional: Marketing |
| Marketing Lead | Review request team, dashboard pipeline, eskalasi | Tier: Dept Lead · Functional: Marketing |
| Process Engineering PIC | Triage request, verify klasifikasi, feasibility check, define routing draft | Tier: User · Functional: Engineering |
| Engineering Lead (Triage Owner) | Distribusi request dari triage queue ke PIC, eskalasi | Tier: Dept Lead · Functional: Engineering |
| Department Users (Produksi, R&D, Finance, dll.) | Konsumen tracking task (di Phase C aktif penuh) | Tier: User · Functional: \<department\> |
| Department Leads | Dashboard task department, monitor SLA informatif | Tier: Dept Lead · Functional: \<department\> |
| Manager / Cost Controller | Cross-department analytics, bottleneck reporting | Tier: Manager |
| System Administrator | Konfigurasi routing rule, request type, user role mapping | Tier: Admin |

## 3. Goals & Non-Goals

### 3.1. Goals (In-Scope Phase A)

1. Menyediakan form ticketing untuk Marketing mengajukan product request dengan field standar dan attachment.

2. Menyediakan form product specification (conditional) untuk product baru: raw material type, deskripsi produk lengkap, shade (master atau custom), jenis paper tube, berat per bobbin, jenis box.

3. Mendukung multiple request types yang configurable (initial: Quote inquiry dan Development/Sample) dengan workflow berbeda per tipe.

4. Menyediakan hybrid routing: simple request auto-assign ke PIC berdasarkan rule, complex request masuk triage queue.

5. Menyediakan tracking lifecycle lengkap dengan state machine yang jelas — Marketing tahu request mereka sampai mana, kapan, dan menunggu apa/siapa.

6. Menyediakan feasibility gate di UNDER_REVIEW: PIC Engineering memutuskan apakah product "dapat dibuat" atau "tidak dapat dibuat" sebelum melanjutkan ke routing.

7. Menyediakan kolaborasi dalam request: thread comment dengan rich-text editor, @mention, attachment per comment, dan edit history.

8. Menyediakan dual-confirmation untuk klasifikasi existing vs new: Marketing mark di submit, Engineering verify saat review, sistem track keduanya.

9. Menyediakan routing draft (shadow entity Product Order) yang memungkinkan Engineering define struktur produksi langsung di Phase A — siap di-promote ke Phase B saat live. Routing draft di-seed dari product specification (shade dan raw material type).

10. Menyediakan notification (email + in-app) dengan preferensi per-user.

11. Menyediakan dashboard tetap (fixed) untuk visibility: pipeline per role, bottleneck per department, customer pipeline, cycle time, conversion rate.

12. Menyediakan audit trail lengkap atas semua perubahan status request, komen, dan attachment.

### 3.2. Non-Goals (Out of Phase A)

- Pembuatan / editing struktur BOM multi-level lengkap (di Phase B Product Order Module).

- Form entry parameter per departemen dengan validasi field-level dan workflow approval (di Phase C).

- Perhitungan cost (di calculation engine eksternal).

- Custom configurable dashboard (future enhancement). Phase A pakai fixed dashboard yang well-designed.

- Recosting flow — recosting bukan request bisnis tetapi operasi internal Finance/Cost Accounting; ditangani di Phase B atau di costing engine eksternal.

- SLA enforcement (auto-escalation, auto-reassign, locking). Phase A hanya tampilkan due date informatif dengan warna indikator.

- Integrasi dengan CRM eksternal untuk customer data. Phase A simpan customer info di-field bebas; integrasi CRM ditambahkan bila kebutuhan muncul.

## 4. Key Concepts & Terminology

| **Term** | **Definition** |
|---|---|
| Product Request | Entry-point sistem; satu ticket yang merepresentasikan satu permintaan dari Marketing terkait product (untuk costing/quote). |
| Product Specification | Data teknis produk yang Marketing isi saat membuat request untuk product baru: raw material type, deskripsi, shade, paper tube, berat bobbin, jenis box. |
| Request Type | Klasifikasi request yang menentukan workflow berbeda. MVP: Quote inquiry dan Development/Sample. Configurable di master. |
| Triage Queue | Inbox bersama Process Engineering Lead untuk request yang tidak auto-route (kompleks atau ambigu). |
| Feasibility Decision | Keputusan PIC Engineering saat UNDER_REVIEW: apakah product "dapat dibuat" atau "tidak dapat dibuat". Gate sebelum routing. |
| Routing Decision | Hasil keputusan PIC Engineering: request menggunakan costing existing (shortcut) atau memerlukan full flow (routing + parameter + costing baru). |
| Routing Draft | Shadow entity yang menampung definisi struktur produksi di Phase A, sebelum Phase B (Product Order) live. Di-seed dari product specification (shade, raw material type). Saat Phase B live, draft di-promote menjadi Product Order resmi. |
| Parameter Pending | State menunggu departemen-departemen melengkapi parameter cost. Di Phase A, state ini menampilkan task abstract; di Phase C tasks-nya menjadi form entry konkret. |
| Tier (Role Permission) | Level otoritas: User, Department Lead, Manager, Admin. Menentukan permission CRUD/approve/admin. |
| Functional Role | Konteks fungsional dari SSO/department: Marketing, Engineering, Produksi, R&D, Finance, dst. Menentukan default landing page, dashboard, dan UI context. |
| Activity Timeline | Stream kronologis aksi-aksi pada satu request: status change, comment, attachment, mention, feasibility decision, dst. Display utama di halaman detail request. |

## 5. Assumptions & Dependencies

1. SSO/IAM eksisting menyediakan identitas user + department affiliation; sistem mempercayai token/session.

2. Database: PostgreSQL 14+ (konsisten dengan Phase B). Memungkinkan JSONB untuk metadata fleksibel.

3. Email gateway tersedia (SMTP/SES/equivalent) untuk notifikasi outbound.

4. Object storage tersedia (S3/MinIO/equivalent) untuk attachment di comment dan request.

5. Master data customer belum tentu lengkap di sistem internal; Phase A menyediakan free-text input dengan opsi autocomplete jika master tersedia.

6. Phase B (Product Order Module) belum live saat Phase A go-live. Phase A menyediakan routing draft sebagai shadow entity.

7. Master data shade color tersedia; namun Marketing dapat input shade secara free-text jika warna yang diinginkan belum ada di master (misalnya "biru langit" saat master hanya punya "biru tua", atau "natural" untuk produk tanpa pewarnaan).

8. Master data paper tube type tersedia dan dikelola oleh Admin.

## 6. Functional Requirements

### 6.1. Product Request Lifecycle

**FR-1: Submit Request**

User dengan functional role Marketing dapat membuat request baru via form tiga section:

**Section 1 — Request Info (selalu tampil):**

- Field wajib: title (judul/ringkasan), request_type, customer_name, product_classification (existing/new — Marketing's best guess), urgency_level (low/medium/high).

- Field opsional: customer_code (jika existing customer), needed_by_date, description, attachment(s).

- Bila request_type = DEVELOPMENT, sistem auto-set product_classification = new.

- Bila product_classification = existing, sistem menampilkan autocomplete untuk mencari product code dari master atau dari Phase B (jika sudah live).

**Section 2 — Product Specification (conditional: tampil jika product_classification = new):**

- Raw material type (wajib, single select): POY Boughtout, Chips SD, Chips BRT, Chips Recycle. Ini adalah deklarasi awal dari Marketing; detail RM di-define oleh Engineering saat membuat routing draft.

- Product description (wajib): deskripsi lengkap produk yang diminta — berupa teks bebas, bukan product code. Termasuk spesifikasi teknis yang Marketing ketahui.

- Shade / warna (wajib minimal salah satu): pilih dari master shade color, ATAU isi free-text jika warna belum ada di master. Jika produk tanpa pewarnaan, user isi "natural". Contoh: master punya "biru tua" tapi customer minta "biru langit" → Marketing isi free-text "biru langit".

- Paper tube type (wajib): pilih dari master paper tube.

- Weight per bobbin (wajib): berat per bobbin dalam Kgs (desimal).

- Box type (wajib): Jumbo, Normal, atau Pallet.

**Section 3 — Pricing Context (selalu tampil):**

- Field opsional: target_volume, target_price_range.

**Behavior saat submit:**

- Saat submit, sistem generate request_no unik (format: REQ-YYYYMM-NNNN), set status awal sesuai routing rule (lihat FR-3).

**FR-2: Request Lifecycle State Machine**

State machine untuk Development/Sample request atau product_classification = new (full flow):

```
DRAFT → SUBMITTED → UNDER_REVIEW → [Feasibility Gate]
  ├── Dapat Dibuat → ROUTING_DEFINED → PARAMETER_PENDING
  │     → PARAMETER_COMPLETE → COSTING_DONE → QUOTED → CLOSED
  └── Tidak Dapat Dibuat → REJECTED
```

Untuk Quote inquiry yang menggunakan costing existing, state shortcut:

```
DRAFT → SUBMITTED → UNDER_REVIEW → QUOTE_READY → QUOTED → CLOSED
```

State CLOSED memiliki sub-status: won, lost, cancelled, on_hold.

State dapat di-cancel dari mana saja sebelum CLOSED (dengan reason) → status menjadi CLOSED dengan sub-status cancelled.

State REJECTED dapat di-revise oleh Marketing (re-submit dengan perubahan) → status kembali ke SUBMITTED.

**FR-3: Hybrid Routing (Auto + Triage)**

- Sistem memiliki tabel cost_routing_rule yang configurable oleh Admin.

- Rule berisi: condition (request_type, product_classification, urgency_level, custom criteria) dan action (auto-assign ke user/role, atau masuk triage queue).

- Saat request submitted, sistem evaluasi rule (urutan first-match). Bila tidak ada rule yang match, request masuk default triage queue.

- Triage owner (Engineering Lead, Tier: Dept Lead + Functional: Engineering) melihat queue dan assign manual ke PIC.

- Rule dapat diubah Admin tanpa code deployment. Audit log mencatat siapa mengubah rule kapan.

**FR-4: Review, Classification Verification & Feasibility**

PIC Engineering yang menerima request membuka detail dan melakukan:

1. Verify klasifikasi Marketing (existing/new). Bila PIC override klasifikasi, wajib mengisi reason; sistem track both: marketing_classification dan verified_classification.

2. Untuk Quote inquiry + verified=existing: PIC dapat pilih action "Use existing costing" → state ke QUOTE_READY langsung.

3. Untuk Development atau verified=new: PIC melakukan **feasibility assessment** — menentukan apakah product dapat dibuat:

   - **"Dapat Dibuat"** → status request berpindah ke ROUTING_DEFINED. Engineering dapat mulai membuat routing draft. Feasibility note opsional.

   - **"Tidak Dapat Dibuat"** → status request berpindah ke REJECTED. Feasibility note wajib (alasan kenapa tidak bisa dibuat). Marketing menerima notifikasi dan dapat re-submit/revise request.

4. PIC juga dapat reject request dengan reason lain (duplicate, insufficient info). Marketing menerima notifikasi dan dapat re-submit/revise.

**FR-5: Comments & Communication**

- Setiap request memiliki thread comment yang dapat diakses semua user dengan permission view atas request tersebut.

- Comment menggunakan rich-text editor (rekomendasi library: Tiptap atau Lexical) — mendukung formatting dasar (bold, italic, list, link).

- @mention user lain di comment akan trigger notifikasi langsung ke user tersebut (FR-10).

- Attachment dapat di-upload per comment (gambar, dokumen, spreadsheet); maks 25 MB per file, format whitelist.

- User dapat edit comment milik sendiri; edit history tersimpan dan dapat dilihat user lain (sebagai indikator transparansi).

- Comment tidak dapat dihapus oleh user (immutable history). Admin dapat menandai comment sebagai "hidden" dengan reason; comment tersembunyi tetap di database.

**FR-6: Activity Timeline**

- Halaman detail request menampilkan timeline kronologis yang menggabungkan: status changes, feasibility decisions, comments, mentions, attachments, klasifikasi changes.

- Tiap entry timeline: actor, action, timestamp, detail (optional).

- Filter: by actor, by event type. Sort: chronological asc/desc.

### 6.2. Routing Draft (Shadow Entity untuk Phase B)

**FR-7: Routing Draft CRUD**

- PIC Engineering dapat membuat routing draft di dalam halaman detail request — terkait 1:N (satu request bisa punya multiple draft, mis. untuk variant berbeda).

- Routing draft menampung: product_top_2 placeholder (boleh sementara), item_code, cyl_type (dropdown dari master), shade_code, raw_material_type, dan daftar komponen langsung sebagai array record (sequence_no, rm_type, rm_ref_text).

- **Seed dari Product Specification:** Saat Engineering membuat routing draft untuk request yang punya product spec, sistem auto-fill shade_code dari CPS_shade_id/CPS_shade_custom_text dan raw_material_type dari CPS_raw_material_type. Engineering dapat override nilai ini — ini adalah hint dari Marketing, bukan constraint hard.

- Schema routing draft mirror dengan Phase B Product Order, tetapi lebih lenient (rm_ref_text adalah free-text bila intermediate product belum ada di sistem).

- Validasi minimal: sequence_no unique per draft, rm_type ada di whitelist (Store Rate, Captive Cost, Multi Yarn, Uneven Packing).

- Bila Phase B live: draft memiliki kolom linked_product_order_id; PIC dapat "Promote to Product Order" yang akan create record di Phase B dan link kembali.

**FR-8: Status Sync dengan Routing Draft**

- Status ROUTING_DEFINED di-trigger oleh feasibility decision "Dapat Dibuat" (FR-4), bukan oleh pembuatan routing draft.

- Engineering membuat routing draft selama status request = ROUTING_DEFINED.

- Saat request masuk PARAMETER_PENDING, routing draft di-lock dari perubahan (read-only). Bila perlu revisi, PIC harus rollback status request via UI eksplisit.

### 6.3. Visibility & Access Control

**FR-9: Permission Matrix (Tier × Functional)**

Permission ditentukan oleh kombinasi Tier (otoritas) dan Functional role (konteks fungsional). Matrix singkat:

| **Tier** | **View scope** | **Capabilities** |
|---|---|---|
| User | Create / view own + assigned | Edit own draft, comment, attach, mark classification |
| Department Lead | View department-scoped requests | Plus: triage assign (Engineering), monitor department dashboard, eskalasi |
| Manager | View cross-department | Plus: analytics, override status (with reason) |
| Admin | View all | Plus: configure routing rules, request types, user role mapping |

- Functional role menentukan default landing page, dashboard yang relevan, dan request types yang dapat dibuat (mis. hanya Marketing yang dapat create Product Request).

- Department-scoped artinya: Department Lead Produksi melihat request yang punya task untuk Produksi; Marketing Lead melihat request yang dibuat tim Marketing.

- Audit log mencatat akses yang gagal (permission denied) untuk security review.

### 6.4. Notifications

**FR-10: Notification Triggers**

- Request status change → notif ke requester + assignee + mentioned users.

- Feasibility decision (Dapat/Tidak Dapat Dibuat) → notif ke requester.

- New @mention di comment → notif ke user yang di-mention.

- Assigned to me (saat triage assign atau auto-route) → notif ke assignee.

- Due date approaching (3 hari, 1 hari) dan overdue → notif harian ke assignee, mingguan ke Department Lead.

- Comment di request saya buat atau saya subscribe → notif ke subscribers.

**FR-11: Notification Channels & Preferences**

- Channels: email + in-app (bell icon).

- User dapat mengatur preferensi per trigger type via halaman Settings: email only, in-app only, both, atau none.

- Default semua notif aktif (both); user dapat opt-out per trigger.

- Email digest: opsi daily digest untuk "notif yang tidak urgent" agar tidak overwhelm inbox.

**FR-12: In-App Notification Center**

- Bell icon di topbar dengan badge jumlah unread.

- Klik membuka dropdown daftar notif dengan link ke request terkait.

- Mark as read individual / mark all as read.

- History 30 hari tersedia di halaman penuh "All Notifications".

### 6.5. Reporting & Dashboards

**FR-13: My Workspace Dashboard**

Default landing page setelah login. Konten disesuaikan dengan Functional role:

- Marketing: requests saya (active, quoted, closed), pipeline pribadi, conversion rate saya.

- Engineering: requests assigned to me, triage queue (jika Dept Lead), routing drafts in-progress.

- Department user: tasks pending input (saat Phase C aktif, sementara Phase A: placeholder).

- Manager: cross-team summary.

**FR-14: Department Bottleneck Dashboard**

- Pivot: average cycle time per departemen per request_type.

- Heatmap: departemen × bulan, intensitas warna = jumlah hari pending.

- Drill-down ke list request yang sedang stuck di departemen tertentu.

**FR-15: Customer & Conversion Dashboard**

- Top customer by jumlah request, by closed-won, by total quoted value (jika field harga diisi).

- Conversion funnel: submitted → quoted → closed-won, dengan drop-off rate.

- Trend chart bulanan.

**FR-16: Cycle Time Dashboard**

- Distribusi cycle time end-to-end (submitted → closed) per request_type.

- Breakdown per state: berapa hari rata-rata di UNDER_REVIEW, di ROUTING_DEFINED, di PARAMETER_PENDING, dst.

- Outlier list: requests yang cycle time >2x median, untuk retrospective.

**FR-17: Export & API**

- Semua dashboard export ke CSV/Excel.

- Read-only API untuk konsumsi BI tools eksternal (Metabase / Superset / Power BI), agar custom dashboard dapat dibangun di luar sistem ini.

### 6.6. Configuration (Admin)

**FR-18: Request Type Management**

- Admin dapat create/edit/disable request type.

- Tiap type punya: name, description, state machine variant (full vs shortcut_capable), required fields, default urgency.

- Type tidak dapat dihapus jika ada request aktif — hanya di-disable.

**FR-19: Routing Rule Management**

- Admin dapat create/edit/disable routing rule.

- Rule editor: drag-drop reorder priority (first-match).

- Test mode: input sample request, lihat rule mana yang match.

**FR-20: User Role Mapping**

- User identity di-pull dari SSO; Tier dan Functional role di-mapping di admin panel.

- Bulk import via CSV untuk inisialisasi.

- Audit log atas semua perubahan role.

### 6.7. Audit Trail

**FR-21: Comprehensive Audit Log**

- Setiap mutasi (state change, feasibility decision, comment, attachment, classification change, role change, rule change) dicatat dengan: timestamp, actor, action, before/after state.

- Audit log accessible by Admin dan Manager.

- Retention: 5 tahun (sesuai kebijakan kepatuhan internal — TBD dengan legal).

## 7. Data Model

### 7.0. Konvensi Penamaan — Column Prefix

Seluruh tabel menggunakan **Column Prefix Naming Convention**: setiap kolom diawali prefix inisial dari nama tabel (termasuk module prefix `cost_`). Ini menjamin nama kolom globally unique di seluruh database — eliminasi kebutuhan alias saat multi-table JOIN.

**Prefix Registry Phase A:**

| Prefix | Table Name |
|---|---|
| CPR_ | cost_product_request |
| CPS_ | cost_product_spec |
| CRT_ | cost_request_type |
| CRR_ | cost_routing_rule |
| CRD_ | cost_routing_draft |
| CRDC_ | cost_routing_draft_component |
| CRC_ | cost_request_comment |
| CCEH_ | cost_comment_edit_history |
| CRM_ | cost_request_mention |
| CA_ | cost_attachment |
| CURM_ | cost_user_role_mapping |
| CN_ | cost_notification |
| CNP_ | cost_notification_preference |
| CAL_ | cost_audit_log |

Aturan prefix:
- Prefix diambil dari inisial setiap kata dalam nama tabel (termasuk `cost_`).
- Bila terjadi collision antar tabel, extend karakter dari kata yang membedakan.
- Panjang target: 2-4 karakter. Tidak boleh lebih dari 5 karakter.
- Semua kolom tanpa kecuali wajib prefix — termasuk PK, FK, dan audit fields.
- FK menggunakan prefix tabel sendiri + nama stem dari tabel tujuan.

### 7.1. Entitas Utama

#### 7.1.1. cost_product_request (CPR_)

Tabel entry-point: satu record = satu product request dari Marketing.

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CPR_request_id | BIGSERIAL | PK | |
| CPR_request_no | VARCHAR(30) | UNIQUE NOT NULL | Format REQ-YYYYMM-NNNN |
| CPR_request_type_id | INT | FK cost_request_type | |
| CPR_title | VARCHAR(255) | NOT NULL | Judul ringkas |
| CPR_description | TEXT | NULL | Detail request |
| CPR_customer_name | VARCHAR(255) | NOT NULL | Free-text |
| CPR_customer_code | VARCHAR(50) | NULL | Optional FK ke master customer |
| CPR_product_classification | VARCHAR(20) | NOT NULL | existing / new (Marketing's mark) |
| CPR_verified_classification | VARCHAR(20) | NULL | Diisi PIC saat review |
| CPR_classification_override_reason | TEXT | NULL | Wajib bila verified ≠ marketing |
| CPR_target_volume | DECIMAL(18,4) | NULL | Unit dari request |
| CPR_target_price_range | VARCHAR(50) | NULL | Free-text |
| CPR_urgency_level | VARCHAR(10) | NOT NULL | low / medium / high |
| CPR_needed_by_date | DATE | NULL | |
| CPR_status | VARCHAR(30) | NOT NULL | DRAFT, SUBMITTED, UNDER_REVIEW, ROUTING_DEFINED, ... |
| CPR_closed_substatus | VARCHAR(20) | NULL | won/lost/cancelled/on_hold bila status=CLOSED |
| CPR_feasibility_decision | VARCHAR(20) | NULL | FEASIBLE / NOT_FEASIBLE (v1.1) |
| CPR_feasibility_note | TEXT | NULL | Wajib jika NOT_FEASIBLE (v1.1) |
| CPR_feasibility_by | VARCHAR(64) | NULL | user_id PIC yang memutuskan (v1.1) |
| CPR_feasibility_at | TIMESTAMPTZ | NULL | Timestamp keputusan (v1.1) |
| CPR_assigned_to_user_id | VARCHAR(64) | NULL | Current assignee dari Engineering |
| CPR_requester_user_id | VARCHAR(64) | NOT NULL | Pembuat request (Marketing) |
| CPR_created_at | TIMESTAMPTZ | NOT NULL | |
| CPR_updated_at | TIMESTAMPTZ | NOT NULL | |

CHECK constraints:
- `CPR_feasibility_decision <> 'NOT_FEASIBLE' OR CPR_feasibility_note IS NOT NULL` — note wajib jika infeasible.
- `CPR_verified_classification IS NULL OR CPR_verified_classification = CPR_product_classification OR CPR_classification_override_reason IS NOT NULL` — override reason wajib.
- `CPR_status <> 'CLOSED' OR CPR_closed_substatus IS NOT NULL` — sub-status wajib saat CLOSED.

#### 7.1.2. cost_product_spec (CPS_) — BARU v1.1

Product specification untuk request dengan product_classification = new. Relasi 1:1 conditional dengan cost_product_request.

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CPS_spec_id | BIGSERIAL | PK | |
| CPS_request_id | BIGINT | FK cost_product_request, UNIQUE | 1:1 conditional |
| CPS_raw_material_type | VARCHAR(50) | NOT NULL | POY_BOUGHTOUT / CHIPS_SD / CHIPS_BRT / CHIPS_RECYCLE |
| CPS_product_description | TEXT | NOT NULL | Deskripsi lengkap produk (bukan code) |
| CPS_shade_id | INT | NULL | FK master_shade_color bila shade ada di master |
| CPS_shade_custom_text | VARCHAR(100) | NULL | Free-text bila shade belum di master (incl. "natural") |
| CPS_paper_tube_type_id | INT | NOT NULL | FK master_paper_tube |
| CPS_weight_per_bobbin_kg | DECIMAL(10,3) | NOT NULL | Berat per bobbin dalam Kgs |
| CPS_box_type | VARCHAR(20) | NOT NULL | JUMBO / NORMAL / PALLET |
| CPS_created_at | TIMESTAMPTZ | NOT NULL | |
| CPS_created_by | VARCHAR(64) | NOT NULL | |

CHECK constraints:
- `CPS_shade_id IS NOT NULL OR CPS_shade_custom_text IS NOT NULL` — minimal salah satu shade harus diisi.
- `CPS_box_type IN ('JUMBO', 'NORMAL', 'PALLET')`.
- `CPS_raw_material_type IN ('POY_BOUGHTOUT', 'CHIPS_SD', 'CHIPS_BRT', 'CHIPS_RECYCLE')`.

#### 7.1.3. cost_request_type (CRT_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CRT_type_id | SERIAL | PK | |
| CRT_code | VARCHAR(30) | UNIQUE NOT NULL | QUOTE / DEVELOPMENT |
| CRT_display_name | VARCHAR(80) | NOT NULL | |
| CRT_state_machine_variant | VARCHAR(30) | NOT NULL | FULL / SHORTCUT_CAPABLE |
| CRT_required_field_config | JSONB | NOT NULL | Daftar field wajib utk type ini |
| CRT_default_urgency | VARCHAR(10) | DEFAULT 'medium' | |
| CRT_is_active | BOOLEAN | DEFAULT true | |

Request type redefinisi (v1.1): QUOTE = workflow ditentukan product_classification (existing → shortcut, new → full). DEVELOPMENT = selalu full flow, auto-set product_classification = new.

#### 7.1.4. cost_routing_rule (CRR_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CRR_rule_id | SERIAL | PK | |
| CRR_priority | INT | NOT NULL | Urutan evaluasi (lower = earlier) |
| CRR_condition | JSONB | NOT NULL | Predicate tree: AND/OR over fields |
| CRR_action_type | VARCHAR(20) | NOT NULL | AUTO_ASSIGN / TO_TRIAGE |
| CRR_action_target | VARCHAR(100) | NULL | user_id atau role (bila AUTO_ASSIGN) |
| CRR_is_active | BOOLEAN | DEFAULT true | |
| CRR_created_by | VARCHAR(64) | NOT NULL | |
| CRR_created_at | TIMESTAMPTZ | NOT NULL | |

#### 7.1.5. cost_routing_draft (CRD_)

Schema mirror Phase B product_order, tetapi lebih lenient (free-text references diperbolehkan).

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CRD_draft_id | BIGSERIAL | PK | |
| CRD_request_id | BIGINT | FK cost_product_request NOT NULL | |
| CRD_product_top_2 | VARCHAR(100) | NULL | Placeholder, bisa diisi nanti |
| CRD_item_code | VARCHAR(50) | NULL | FK master item bila ada |
| CRD_cyl_type_id | INT | NULL | FK master cyl_type |
| CRD_shade_code | VARCHAR(50) | NULL | Seeded dari CPS_shade_id/custom_text |
| CRD_raw_material_type | VARCHAR(50) | NULL | Seeded dari CPS_raw_material_type |
| CRD_status | VARCHAR(20) | NOT NULL | DRAFT / LOCKED / PROMOTED |
| CRD_linked_product_order_id | BIGINT | NULL | Diisi saat promote ke Phase B |
| CRD_created_by | VARCHAR(64) | NOT NULL | |
| CRD_created_at | TIMESTAMPTZ | NOT NULL | |
| CRD_updated_at | TIMESTAMPTZ | NOT NULL | |

#### 7.1.6. cost_routing_draft_component (CRDC_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CRDC_component_id | BIGSERIAL | PK | |
| CRDC_draft_id | BIGINT | FK cost_routing_draft NOT NULL | |
| CRDC_sequence_no | INT | NOT NULL | |
| CRDC_sub_sequence | INT | NULL | Untuk Multi Yarn |
| CRDC_sub_type | VARCHAR(30) | NULL | |
| CRDC_rm_type | VARCHAR(30) | NOT NULL | Store Rate / Captive Cost / Multi Yarn / Uneven Packing |
| CRDC_rm_ref_text | VARCHAR(255) | NOT NULL | Free-text reference |
| CRDC_rm_ref_resolved_id | BIGINT | NULL | Resolved bila ada master / Phase B product |
| CRDC_notes | TEXT | NULL | |

UNIQUE constraint: (CRDC_draft_id, CRDC_sequence_no, CRDC_sub_sequence).

#### 7.1.7. cost_request_comment (CRC_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CRC_comment_id | BIGSERIAL | PK | |
| CRC_request_id | BIGINT | FK cost_product_request NOT NULL | |
| CRC_parent_comment_id | BIGINT | NULL | Reserved untuk threading (future) |
| CRC_author_user_id | VARCHAR(64) | NOT NULL | |
| CRC_body_richtext | JSONB | NOT NULL | Tiptap/Lexical JSON tree |
| CRC_body_plaintext | TEXT | NOT NULL | Plaintext copy untuk search & notif |
| CRC_is_edited | BOOLEAN | DEFAULT false | |
| CRC_is_hidden | BOOLEAN | DEFAULT false | Admin moderation |
| CRC_hidden_reason | TEXT | NULL | Wajib bila is_hidden = true |
| CRC_created_at | TIMESTAMPTZ | NOT NULL | |
| CRC_updated_at | TIMESTAMPTZ | NOT NULL | |

CHECK constraint: `CRC_is_hidden = false OR CRC_hidden_reason IS NOT NULL`.

#### 7.1.8. cost_comment_edit_history (CCEH_)

Versi historis dari comment yang di-edit (untuk transparansi).

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CCEH_edit_id | BIGSERIAL | PK | |
| CCEH_comment_id | BIGINT | FK cost_request_comment NOT NULL | |
| CCEH_body_richtext | JSONB | NOT NULL | Snapshot sebelum edit |
| CCEH_body_plaintext | TEXT | NOT NULL | |
| CCEH_edited_by | VARCHAR(64) | NOT NULL | |
| CCEH_edited_at | TIMESTAMPTZ | NOT NULL | |

#### 7.1.9. cost_request_mention (CRM_)

Tabel mention untuk fast lookup dan notification trigger.

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CRM_mention_id | BIGSERIAL | PK | |
| CRM_comment_id | BIGINT | FK cost_request_comment NOT NULL | |
| CRM_mentioned_user_id | VARCHAR(64) | NOT NULL | |
| CRM_is_notified | BOOLEAN | DEFAULT false | |
| CRM_notified_at | TIMESTAMPTZ | NULL | |

#### 7.1.10. cost_attachment (CA_)

Generic attachment yang dapat dilampirkan ke request atau ke comment.

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CA_attachment_id | BIGSERIAL | PK | |
| CA_request_id | BIGINT | FK cost_product_request, NULL allowed | Attachment level request |
| CA_comment_id | BIGINT | FK cost_request_comment, NULL allowed | Attachment level comment |
| CA_filename | VARCHAR(255) | NOT NULL | |
| CA_mime_type | VARCHAR(100) | NOT NULL | |
| CA_size_bytes | BIGINT | NOT NULL | |
| CA_storage_key | VARCHAR(500) | NOT NULL | Path/key di object storage |
| CA_uploaded_by | VARCHAR(64) | NOT NULL | |
| CA_uploaded_at | TIMESTAMPTZ | NOT NULL | |

CHECK constraint: exactly one of (CA_request_id, CA_comment_id) is non-null.

#### 7.1.11. cost_user_role_mapping (CURM_)

Mapping antara user dari SSO dengan Tier dan Functional role.

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CURM_mapping_id | BIGSERIAL | PK | |
| CURM_user_id | VARCHAR(64) | NOT NULL | |
| CURM_tier | VARCHAR(20) | NOT NULL | User / Dept Lead / Manager / Admin |
| CURM_functional_role | VARCHAR(30) | NOT NULL | Marketing / Engineering / Produksi / RND / Finance / Admin |
| CURM_is_active | BOOLEAN | DEFAULT true | |
| CURM_effective_from | TIMESTAMPTZ | NOT NULL | |
| CURM_effective_to | TIMESTAMPTZ | NULL | |

#### 7.1.12. cost_notification (CN_)

Record in-app notification + status pengiriman email.

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CN_notification_id | BIGSERIAL | PK | |
| CN_recipient_user_id | VARCHAR(64) | NOT NULL | |
| CN_trigger_type | VARCHAR(50) | NOT NULL | STATUS_CHANGE / MENTION / ASSIGNED / FEASIBILITY / etc |
| CN_request_id | BIGINT | FK cost_product_request, NULL allowed | |
| CN_payload | JSONB | NOT NULL | |
| CN_is_read | BOOLEAN | DEFAULT false | |
| CN_email_sent_at | TIMESTAMPTZ | NULL | |
| CN_created_at | TIMESTAMPTZ | NOT NULL | |

#### 7.1.13. cost_notification_preference (CNP_)

Preferensi notif per user per trigger type.

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CNP_pref_id | BIGSERIAL | PK | |
| CNP_user_id | VARCHAR(64) | NOT NULL | |
| CNP_trigger_type | VARCHAR(50) | NOT NULL | |
| CNP_channel_email | BOOLEAN | DEFAULT true | |
| CNP_channel_in_app | BOOLEAN | DEFAULT true | |
| CNP_digest_mode | VARCHAR(20) | DEFAULT 'immediate' | immediate / daily |

UNIQUE constraint: (CNP_user_id, CNP_trigger_type).

#### 7.1.14. cost_audit_log (CAL_)

Log audit umum (mirror konsep dari Phase B). Append-only, immutable.

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CAL_log_id | BIGSERIAL | PK | |
| CAL_entity_type | VARCHAR(50) | NOT NULL | cost_product_request / cost_routing_draft / etc |
| CAL_entity_id | BIGINT | NOT NULL | |
| CAL_operation | VARCHAR(20) | NOT NULL | INSERT / UPDATE / DELETE / STATUS_CHANGE / FEASIBILITY |
| CAL_before_data | JSONB | NULL | Snapshot sebelum perubahan |
| CAL_after_data | JSONB | NULL | Snapshot setelah perubahan |
| CAL_user_id | VARCHAR(64) | NOT NULL | |
| CAL_performed_at | TIMESTAMPTZ | NOT NULL | |

### 7.2. Relasi (ERD Summary)

```
[cost_product_request] 1:1 [cost_product_spec] (conditional: jika new product)
[cost_product_request] 1:N [cost_routing_draft] 1:N [cost_routing_draft_component]
[cost_product_request] 1:N [cost_request_comment] 1:N [cost_attachment]
[cost_product_request] 1:N [cost_attachment] (attachment di-request level)
[cost_request_comment] 1:N [cost_request_mention] N:1 [user]
[cost_request_comment] 1:N [cost_comment_edit_history]
[cost_request_type], [cost_routing_rule], [cost_user_role_mapping] — master/config tables
[cost_notification] N:1 [user] ; [cost_notification_preference] N:1 [user]
[cost_audit_log] — cross-cutting; mereferensi entity manapun via (CAL_entity_type, CAL_entity_id)
```

Catatan integrasi Phase B (ditulis sebagai future migration):

- Saat Phase B live: kolom CRD_linked_product_order_id aktif sebagai FK ke Phase B product_order.

- Migration job: untuk setiap routing_draft dengan status DRAFT/LOCKED, generate record product_order di Phase B sesuai isi draft + komponennya; set CRD_linked_product_order_id dan status draft → PROMOTED.

- Setelah Phase B live, draft baru tetap dibuat di Phase A; promote eksplisit yang trigger pembuatan Product Order.

- Seed fields: CRD_shade_code dan CRD_raw_material_type di-carry ke Phase B saat promotion.

## 8. Non-Functional Requirements

### 8.1. Performance

- List request (paginated, ≤50/halaman): response < 800 ms untuk dataset ≤20.000 request.

- Detail request (termasuk thread comment): response < 1.2 detik.

- Dashboard fixed: render initial < 2 detik.

- Notification dispatch: in-app instant (≤1 detik), email queued (≤3 menit).

### 8.2. Scalability

- Mendukung minimal 50.000 request, 500.000 comment, 200.000 attachment, 100 concurrent users.

- Attachment storage menggunakan object storage eksternal (S3-like) — tidak boleh disimpan di database.

### 8.3. Availability & Backup

- Target uptime: 99.5% jam kerja.

- Backup database harian, retensi 30 hari minimum.

- Object storage backup dengan policy versioning.

### 8.4. Security

- Autentikasi: SSO/IAM.

- Otorisasi: tier × functional role matrix (FR-9), enforced di backend (not only UI).

- Attachment scan antivirus sebelum disimpan.

- Audit log immutable (append-only).

- Transport security TLS 1.2+.

### 8.5. Platform

- Web app responsif (desktop primary, mobile read-only acceptable di MVP).

- Front-end stack rekomendasi: React (konsisten dengan Phase B); rich-text editor pakai Tiptap atau Lexical.

- Backend: REST API atau GraphQL — TBD saat tech design.

### 8.6. Internationalization

- UI Bahasa Indonesia sebagai default.

- Tanggal/waktu disimpan UTC, ditampilkan WIB.

## 9. Success Metrics

- Adoption: ≥80% request product baru yang dibuat tim Marketing dalam 3 bulan pasca-launch melalui sistem (bukan email/WA).

- Visibility improvement: tim Marketing dapat menjawab "di mana request saya?" tanpa bertanya ke Engineering (survey internal ≥4/5).

- Cycle time reduction: rata-rata waktu submitted → quoted turun ≥30% pasca-launch (baseline diukur 3 bulan pre-launch).

- Accountability transparency: laporan bottleneck per departemen dapat di-generate kapan saja; minimal review bulanan rutin di rapat lintas-departemen.

- Zero blame ambiguity: setiap request yang terlambat dapat di-trace ke event dan owner yang spesifik (no "sapa salahnya").

## 10. Risks & Mitigations

| **Risk** | **Severity** | **Mitigation** |
|---|---|---|
| Adoption gap: tim Marketing tetap pakai email/WA karena merasa form sistem ribet | High | UX test dengan Marketing user awal; form submit < 60 detik untuk request sederhana; notif lewat email tetap aktif agar familiar |
| Triage owner jadi single point of failure (1 orang Engineering Lead overload) | Medium | Sistem mendukung multi-triage-owner; routing rule untuk "high urgency → CC triage backup" |
| Comment thread jadi panjang & noisy; mention berlebihan menyebabkan notification fatigue | Medium | Notification preference per trigger; daily digest opsi; sub-thread reply diserahkan ke future enhancement |
| Routing draft drift dari Phase B Product Order (struktur berbeda saat Phase B live) | Medium | Schema draft di-mirror dari Phase B PRD; migration script disusun bersama dengan Phase B implementation; draft di-validate dengan Phase B rules saat promote |
| Custom role kebutuhan muncul (mis. "Marketing Region Leader" yang lihat region tertentu) | Low-Med | Tier × Functional matrix punya extension via functional_role naming; bila kebutuhan kompleks → Phase A v1.x add scoped permission |
| Performance degradation saat dataset besar (>100K request) | Medium | Pagination konsisten, index pada (status, requester_user_id, assigned_to_user_id); archive request lebih dari 2 tahun ke tabel terpisah bila perlu |
| Master shade color tidak lengkap — Marketing terpaksa selalu pakai free-text | Low | Pre-populate master shade dari katalog produk existing; Admin dapat tambah shade baru kapan saja |

## 11. Implementation Phasing (Within Phase A)

**Sprint Block 1 — Foundation**

- Schema database lengkap (semua tabel dengan prefix convention), master data seeding (cost_request_type, cost_routing_rule default).

- Autentikasi SSO integration, cost_user_role_mapping admin UI.

- FR-1 Submit Request (termasuk Section 2 Product Spec conditional form), FR-2 State Machine.

**Sprint Block 2 — Lifecycle & Routing**

- FR-3 Hybrid Routing dengan admin UI untuk routing rule (FR-19).

- FR-4 Review, Classification & Feasibility Gate.

- FR-7 Routing Draft CRUD (dengan seed dari product spec), FR-8 Status Sync.

**Sprint Block 3 — Communication**

- FR-5 Comments rich-text + attachment per comment.

- FR-6 Activity Timeline.

- FR-10/11/12 Notifications.

**Sprint Block 4 — Visibility & Reporting**

- FR-13 My Workspace.

- FR-14/15/16 Dashboards.

- FR-17 Export & Read-only API.

**Sprint Block 5 — Admin & Hardening**

- FR-18/19/20 Admin configuration UI.

- FR-21 Audit Trail UI.

- UAT, performance test, security review, prod rollout.

## 12. Open Questions

1. Customer master data: integrasi ke CRM eksternal atau cukup free-text di MVP? Pengaruhi FR-1.

2. Email gateway specific: SES, SendGrid, atau internal SMTP relay? Pengaruhi NFR & deployment.

3. Object storage: S3, MinIO on-prem, atau eksternal lain? Pengaruhi attachment NFR.

4. Retention audit log 5 tahun: konfirmasi dengan tim legal/compliance.

5. Bahasa rich-text content: hanya Bahasa Indonesia, atau juga Bahasa Inggris (untuk customer asing)? Pengaruhi search indexing.

6. Read-only API authentication: API key, OAuth client credentials, atau bearer token via SSO?

7. Default routing rules awal: butuh workshop dengan Engineering Lead untuk seed cost_routing_rule master sebelum go-live.

8. Raw material type: apakah opsi POY Boughtout / Chips SD / Chips BRT / Chips Recycle sudah final, atau ada tipe RM lain yang perlu ditambah? Apakah perlu dijadikan master table (bukan enum) agar Admin bisa manage sendiri?

9. Master shade color: berapa banyak shade yang sudah ada di master saat ini? Apakah cukup untuk go-live, atau perlu pre-population effort?

10. Master paper tube: berapa jenis paper tube yang ada? Apakah ada master table existing yang bisa dipakai?

## 13. Appendix

### 13.1. Glossary

- BOM — Bill of Materials
- CRM — Customer Relationship Management
- MVP — Minimum Viable Product
- PIC — Person in Charge
- PRD — Product Requirements Document
- RND — Research & Development
- SLA — Service Level Agreement
- SSO — Single Sign-On

### 13.2. Related Documents

- PRD Phase B — Product Order Management (Costing Workflow Suite), version 1.2.
- Sample Costing Orchestration (Excel) — referensi peran sistem dalam pipeline costing.
- (Future) PRD Phase C — Parameter Entry & Validation.

### 13.3. Document Revision History

| **Version** | **Date** | **Description** | **Author** |
|---|---|---|---|
| 1.0 | May 2026 | Initial draft Phase A — Product Request Module | — |
| 1.1 | May 2026 | Product Spec form (Section 2), Feasibility Gate, REJECTED state, Request Type redefinisi, Column Prefix Naming Convention, tabel baru cost_product_spec | — |
