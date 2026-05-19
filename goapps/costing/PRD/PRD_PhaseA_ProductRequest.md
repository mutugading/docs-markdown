---
title: "PRD — Costing Workflow Suite, Phase A: Product Request Module"
version: "1.0"
status: "Draft"
phase: "A"
last_updated: "2026-05"
author: "[IT Leader]"
related:
  - "PRD_PhaseB_ProductOrder.md"
  - "ERD_Master.md"
  - "GLOSSARY.md"
---

# PRD — Phase A: Product Request Module
## Costing Workflow Suite

> *Ticketing & Workflow Orchestration untuk Product Costing Request*
> Version 1.0 — Draft | May 2026

---

## 1. Executive Summary

Costing Workflow Suite adalah project IT yang menyatukan rangkaian aktivitas product costing dari hulu (request masuk) hingga hilir (cost result siap dipakai). Phase A ini berfokus pada modul Product Request — sistem ticketing internal yang menghubungkan Marketing, Process Engineering, dan departemen-departemen pemilik parameter cost (Produksi, R&D, Finance, dan lain-lain).

Tujuan utama Phase A adalah menyelesaikan masalah lintas-departemen yang user identifikasi: kesulitan tracking request dari customer, ketidakjelasan pending parameter entry per departemen, dan saling-menyalahkan antar fungsi ketika quote terlambat. Sistem ini tidak menambah enforcement birokratis — fokusnya pada transparansi: setiap request terlihat statusnya, setiap kewajiban terlihat owner-nya, dan setiap keterlambatan terlihat penyebabnya.

Phase A di-deliver standalone — siap dipakai produksi tanpa harus menunggu Phase B (Product Order, sudah didesign di PRD terpisah) atau Phase C (Parameter Entry & Validation). Kontrak interface ke phase berikutnya ditegaskan di dokumen ini agar integrasi nanti tidak butuh refactoring besar.

### 1.1. Visi 3-Phase Project Costing Workflow Suite

| **Phase**           | **Status**                | **Scope ringkas**                                                                          |
|---------------------|---------------------------|--------------------------------------------------------------------------------------------|
| A — Product Request | Modul ini                 | Ticketing untuk request, tracking lifecycle, accountability lintas departemen              |
| B — Product Order   | PRD terpisah, sudah final | Definisi struktur BOM multi-level, visualisasi tree/flow, versioning, costing orchestrator |
| C — Parameter Entry | Belum dimulai             | Form entry parameter per departemen dengan workflow validasi dan co-ownership              |

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

| **Stakeholder**                                 | **Kebutuhan Utama**                                            | **Role**                                     |
|-------------------------------------------------|----------------------------------------------------------------|----------------------------------------------|
| Marketing                                       | Membuat request, lihat progress, terima notifikasi quote ready | Tier: User · Functional: Marketing           |
| Marketing Lead                                  | Review request team, dashboard pipeline, eskalasi              | Tier: Dept Lead · Functional: Marketing      |
| Process Engineering PIC                         | Triage request, verify klasifikasi, define routing draft       | Tier: User · Functional: Engineering         |
| Engineering Lead (Triage Owner)                 | Distribusi request dari triage queue ke PIC, eskalasi          | Tier: Dept Lead · Functional: Engineering    |
| Department Users (Produksi, R&D, Finance, dll.) | Konsumen tracking task (di Phase C aktif penuh)                | Tier: User · Functional: \<department\>      |
| Department Leads                                | Dashboard task department, monitor SLA informatif              | Tier: Dept Lead · Functional: \<department\> |
| Manager / Cost Controller                       | Cross-department analytics, bottleneck reporting               | Tier: Manager                                |
| System Administrator                            | Konfigurasi routing rule, request type, user role mapping      | Tier: Admin                                  |

## 3. Goals & Non-Goals

### 3.1. Goals (In-Scope Phase A)

1.  Menyediakan form ticketing untuk Marketing mengajukan product request dengan field standar dan attachment.

2.  Mendukung multiple request types yang configurable (initial: Quote inquiry dan Development/Sample) dengan workflow berbeda per tipe.

3.  Menyediakan hybrid routing: simple request auto-assign ke PIC berdasarkan rule, complex request masuk triage queue.

4.  Menyediakan tracking lifecycle lengkap dengan state machine yang jelas — Marketing tahu request mereka sampai mana, kapan, dan menunggu apa/siapa.

5.  Menyediakan kolaborasi dalam request: thread comment dengan rich-text editor, @mention, attachment per comment, dan edit history.

6.  Menyediakan dual-confirmation untuk klasifikasi existing vs new: Marketing mark di submit, Engineering verify saat review, sistem track keduanya.

7.  Menyediakan routing draft (shadow entity Product Order) yang memungkinkan Engineering define struktur produksi langsung di Phase A — siap di-promote ke Phase B saat live.

8.  Menyediakan notification (email + in-app) dengan preferensi per-user.

9.  Menyediakan dashboard tetap (fixed) untuk visibility: pipeline per role, bottleneck per department, customer pipeline, cycle time, conversion rate.

10. Menyediakan audit trail lengkap atas semua perubahan status request, komen, dan attachment.

### 3.2. Non-Goals (Out of Phase A)

- Pembuatan / editing struktur BOM multi-level lengkap (di Phase B Product Order Module).

- Form entry parameter per departemen dengan validasi field-level dan workflow approval (di Phase C).

- Perhitungan cost (di calculation engine eksternal).

- Custom configurable dashboard (future enhancement). Phase A pakai fixed dashboard yang well-designed.

- Recosting flow — recosting bukan request bisnis tetapi operasi internal Finance/Cost Accounting; ditangani di Phase B atau di costing engine eksternal.

- SLA enforcement (auto-escalation, auto-reassign, locking). Phase A hanya tampilkan due date informatif dengan warna indikator.

- Integrasi dengan CRM eksternal untuk customer data. Phase A simpan customer info di-field bebas; integrasi CRM ditambahkan bila kebutuhan muncul.

## 4. Key Concepts & Terminology

| **Term**               | **Definition**                                                                                                                                                             |
|------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Product Request        | Entry-point sistem; satu ticket yang merepresentasikan satu permintaan dari Marketing terkait product (untuk costing/quote).                                               |
| Request Type           | Klasifikasi request yang menentukan workflow berbeda. MVP: Quote inquiry dan Development/Sample. Configurable di master.                                                   |
| Triage Queue           | Inbox bersama Process Engineering Lead untuk request yang tidak auto-route (kompleks atau ambigu).                                                                         |
| Routing Decision       | Hasil keputusan PIC Engineering: request menggunakan costing existing (shortcut) atau memerlukan full flow (routing + parameter + costing baru).                           |
| Routing Draft          | Shadow entity yang menampung definisi struktur produksi di Phase A, sebelum Phase B (Product Order) live. Saat Phase B live, draft di-promote menjadi Product Order resmi. |
| Parameter Pending      | State menunggu departemen-departemen melengkapi parameter cost. Di Phase A, state ini menampilkan task abstract; di Phase C tasks-nya menjadi form entry konkret.          |
| Tier (Role Permission) | Level otoritas: User, Department Lead, Manager, Admin. Menentukan permission CRUD/approve/admin.                                                                           |
| Functional Role        | Konteks fungsional dari SSO/department: Marketing, Engineering, Produksi, R&D, Finance, dst. Menentukan default landing page, dashboard, dan UI context.                   |
| Activity Timeline      | Stream kronologis aksi-aksi pada satu request: status change, comment, attachment, mention, dst. Display utama di halaman detail request.                                  |

## 5. Assumptions & Dependencies

11. SSO/IAM eksisting menyediakan identitas user + department affiliation; sistem mempercayai token/session.

12. Database: PostgreSQL 14+ (konsisten dengan Phase B). Memungkinkan JSONB untuk metadata fleksibel.

13. Email gateway tersedia (SMTP/SES/equivalent) untuk notifikasi outbound.

14. Object storage tersedia (S3/MinIO/equivalent) untuk attachment di comment dan request.

15. Master data customer belum tentu lengkap di sistem internal; Phase A menyediakan free-text input dengan opsi autocomplete jika master tersedia.

16. Phase B (Product Order Module) belum live saat Phase A go-live. Phase A menyediakan routing draft sebagai shadow entity.

## 6. Functional Requirements

### 6.1. Product Request Lifecycle

**FR-1: Submit Request**

- User dengan functional role Marketing dapat membuat request baru via form.

- Field wajib: judul/ringkasan, request_type, customer_name, product_classification (existing/new — Marketing's best guess), description.

- Field opsional: customer_code (jika existing customer), target_volume, target_price_range, urgency_level (low/medium/high), needed_by_date, attachment(s).

- Bila product_classification = existing, sistem menampilkan autocomplete untuk mencari product code dari master atau dari Phase B (jika sudah live).

- Saat submit, sistem generate request_no unik (format: REQ-YYYYMM-NNNN), set status awal sesuai routing rule (lihat FR-3).

**FR-2: Request Lifecycle State Machine**

State machine untuk Development/Sample request (full flow):

DRAFT → SUBMITTED → UNDER_REVIEW → ROUTING_DEFINED → PARAMETER_PENDING

→ PARAMETER_COMPLETE → COSTING_DONE → QUOTED → CLOSED

Untuk Quote inquiry yang menggunakan costing existing, state shortcut:

DRAFT → SUBMITTED → UNDER_REVIEW → QUOTE_READY → QUOTED → CLOSED

State CLOSED memiliki sub-status: won, lost, cancelled, on_hold. State dapat di-cancel dari mana saja sebelum CLOSED (dengan reason).

**FR-3: Hybrid Routing (Auto + Triage)**

- Sistem memiliki tabel routing_rule yang configurable oleh Admin.

- Rule berisi: condition (request_type, product_classification, urgency_level, custom criteria) dan action (auto-assign ke user/role, atau masuk triage queue).

- Saat request submitted, sistem evaluasi rule (urutan first-match). Bila tidak ada rule yang match, request masuk default triage queue.

- Triage owner (Engineering Lead, Tier: Dept Lead + Functional: Engineering) melihat queue dan assign manual ke PIC.

- Rule dapat diubah Admin tanpa code deployment. Audit log mencatat siapa mengubah rule kapan.

**FR-4: Review & Classification Verification**

- PIC Engineering yang menerima request membuka detail, verify klasifikasi Marketing (existing/new).

- Bila PIC override klasifikasi, wajib mengisi reason; sistem track both: marketing_classification dan verified_classification.

- Untuk Quote inquiry + verified=existing: PIC dapat pilih action "Use existing costing" → state ke QUOTE_READY langsung.

- Untuk Development atau verified=new: state ke ROUTING_DEFINED setelah routing draft dibuat (FR-7).

- PIC dapat reject request dengan reason (infeasible, duplicate, insufficient info). Marketing menerima notifikasi dan dapat re-submit/revise.

**FR-5: Comments & Communication**

- Setiap request memiliki thread comment yang dapat diakses semua user dengan permission view atas request tersebut.

- Comment menggunakan rich-text editor (rekomendasi library: Tiptap atau Lexical) — mendukung formatting dasar (bold, italic, list, link).

- @mention user lain di comment akan trigger notifikasi langsung ke user tersebut (FR-13).

- Attachment dapat di-upload per comment (gambar, dokumen, spreadsheet); maks 25 MB per file, format whitelist.

- User dapat edit comment milik sendiri; edit history tersimpan dan dapat dilihat user lain (sebagai indikator transparansi).

- Comment tidak dapat dihapus oleh user (immutable history). Admin dapat menandai comment sebagai "hidden" dengan reason; comment tersembunyi tetap di database.

**FR-6: Activity Timeline**

- Halaman detail request menampilkan timeline kronologis yang menggabungkan: status changes, comments, mentions, attachments, klasifikasi changes.

- Tiap entry timeline: actor, action, timestamp, detail (optional).

- Filter: by actor, by event type. Sort: chronological asc/desc.

### 6.2. Routing Draft (Shadow Entity untuk Phase B)

**FR-7: Routing Draft CRUD**

- PIC Engineering dapat membuat routing draft di dalam halaman detail request — terkait 1:N (satu request bisa punya multiple draft, mis. untuk variant berbeda).

- Routing draft menampung: product_top_2 placeholder (boleh sementara), item_code, cyl_type (dropdown dari master), shade_code, dan daftar komponen langsung sebagai array record (sequence_no, rm_type, rm_ref_text).

- Schema routing draft mirror dengan Phase B Product Order, tetapi lebih lenient (rm_ref_text adalah free-text bila intermediate product belum ada di sistem).

- Validasi minimal: sequence_no unique per draft, rm_type ada di whitelist (Store Rate, Captive Cost, Multi Yarn, Uneven Packing).

- Bila Phase B live: draft memiliki kolom linked_product_order_id; PIC dapat "Promote to Product Order" yang akan create record di Phase B dan link kembali.

**FR-8: Status Sync dengan Routing Draft**

- Saat routing draft pertama dibuat untuk sebuah request, status request otomatis berpindah ke ROUTING_DEFINED.

- Saat request masuk PARAMETER_PENDING, routing draft di-lock dari perubahan (read-only). Bila perlu revisi, PIC harus rollback status request via UI eksplisit.

### 6.3. Visibility & Access Control

**FR-9: Permission Matrix (Tier × Functional)**

Permission ditentukan oleh kombinasi Tier (otoritas) dan Functional role (konteks fungsional). Matrix singkat:

| **Tier**        | **View scope**                  | **Capabilities**                                                          |
|-----------------|---------------------------------|---------------------------------------------------------------------------|
| User            | Create / view own + assigned    | Edit own draft, comment, attach, mark classification                      |
| Department Lead | View department-scoped requests | Plus: triage assign (Engineering), monitor department dashboard, eskalasi |
| Manager         | View cross-department           | Plus: analytics, override status (with reason)                            |
| Admin           | View all                        | Plus: configure routing rules, request types, user role mapping           |

- Functional role menentukan default landing page, dashboard yang relevan, dan request types yang dapat dibuat (mis. hanya Marketing yang dapat create Product Request).

- Department-scoped artinya: Department Lead Produksi melihat request yang punya task untuk Produksi; Marketing Lead melihat request yang dibuat tim Marketing.

- Audit log mencatat akses yang gagal (permission denied) untuk security review.

### 6.4. Notifications

**FR-10: Notification Triggers**

- Request status change → notif ke requester + assignee + mentioned users.

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

- Outlier list: requests yang cycle time \>2x median, untuk retrospective.

**FR-17: Export & API**

- Semua dashboard export ke CSV/Excel.

- Read-only API untuk konsumsi BI tools eksternal (Metabase / Superset / Power BI), agar custom dashboard dapat dibangun di luar sistem ini.

### 6.6. Configuration (Admin)

**FR-18: Request Type Management**

- Admin dapat create/edit/disable request type.

- Tiap type punya: name, description, state machine variant (full vs shortcut), required fields, default urgency.

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

- Setiap mutasi (state change, comment, attachment, classification change, role change, rule change) dicatat dengan: timestamp, actor, action, before/after state.

- Audit log accessible by Admin dan Manager.

- Retention: 5 tahun (sesuai kebijakan kepatuhan internal — TBD dengan legal).

## 7. Data Model

### 7.1. Entitas Utama

#### 7.1.1. product_request

| **Column**                     | **Type**      | **Constraint**  | **Notes**                                                      |
|--------------------------------|---------------|-----------------|----------------------------------------------------------------|
| request_id                     | BIGSERIAL     | PK              |                                                                |
| request_no                     | VARCHAR(30)   | UNIQUE NOT NULL | Format REQ-YYYYMM-NNNN                                         |
| request_type_id                | INT           | FK request_type |                                                                |
| title                          | VARCHAR(255)  | NOT NULL        | Judul ringkas                                                  |
| description                    | TEXT          | NULL            | Detail request                                                 |
| customer_name                  | VARCHAR(255)  | NOT NULL        | Free-text                                                      |
| customer_code                  | VARCHAR(50)   | NULL            | Optional FK ke master customer                                 |
| marketing_classification       | VARCHAR(20)   | NOT NULL        | existing / new (Marketing's mark)                              |
| verified_classification        | VARCHAR(20)   | NULL            | Diisi PIC saat review; bisa beda dari marketing_classification |
| classification_override_reason | TEXT          | NULL            | Wajib bila verified ≠ marketing                                |
| target_volume                  | DECIMAL(18,4) | NULL            | Unit dari request                                              |
| target_price_range             | VARCHAR(50)   | NULL            | Free-text                                                      |
| urgency_level                  | VARCHAR(10)   | NOT NULL        | low / medium / high                                            |
| needed_by_date                 | DATE          | NULL            |                                                                |
| status                         | VARCHAR(30)   | NOT NULL        | DRAFT, SUBMITTED, UNDER_REVIEW, ROUTING_DEFINED, ...           |
| closed_substatus               | VARCHAR(20)   | NULL            | won/lost/cancelled/on_hold bila status=CLOSED                  |
| assigned_to_user_id            | VARCHAR(64)   | NULL            | Current assignee dari Engineering                              |
| requester_user_id              | VARCHAR(64)   | NOT NULL        | Pembuat request (Marketing)                                    |
| created_at, updated_at         | TIMESTAMPTZ   | NOT NULL        |                                                                |

#### 7.1.2. request_type

| **Column**            | **Type**    | **Constraint**  | **Notes**                       |
|-----------------------|-------------|-----------------|---------------------------------|
| type_id               | SERIAL      | PK              |                                 |
| code                  | VARCHAR(30) | UNIQUE NOT NULL | QUOTE / DEVELOPMENT / ...       |
| display_name          | VARCHAR(80) | NOT NULL        |                                 |
| state_machine_variant | VARCHAR(30) | NOT NULL        | FULL / SHORTCUT_CAPABLE         |
| required_field_config | JSONB       | NOT NULL        | Daftar field wajib utk type ini |
| is_active             | BOOLEAN     | DEFAULT true    |                                 |

#### 7.1.3. routing_rule

| **Column**             | **Type**              | **Constraint** | **Notes**                            |
|------------------------|-----------------------|----------------|--------------------------------------|
| rule_id                | SERIAL                | PK             |                                      |
| priority               | INT                   | NOT NULL       | Urutan evaluasi (lower = earlier)    |
| condition              | JSONB                 | NOT NULL       | Predicate tree: AND/OR over fields   |
| action_type            | VARCHAR(20)           | NOT NULL       | AUTO_ASSIGN / TO_TRIAGE              |
| action_target          | VARCHAR(100)          | NULL           | user_id atau role (bila AUTO_ASSIGN) |
| is_active              | BOOLEAN               | DEFAULT true   |                                      |
| created_by, created_at | VARCHAR / TIMESTAMPTZ | NOT NULL       |                                      |

#### 7.1.4. routing_draft (Shadow Entity untuk Phase B)

Schema mirror Phase B product_order, tetapi lebih lenient (free-text references diperbolehkan):

| **Column**              | **Type**              | **Constraint**              | **Notes**                        |
|-------------------------|-----------------------|-----------------------------|----------------------------------|
| draft_id                | BIGSERIAL             | PK                          |                                  |
| request_id              | BIGINT                | FK product_request NOT NULL |                                  |
| product_top_2           | VARCHAR(100)          | NULL                        | Placeholder, bisa diisi nanti    |
| item_code               | VARCHAR(50)           | NULL                        | FK master item bila ada          |
| cyl_type_id             | INT                   | NULL                        | FK master cyl_type               |
| shade_code              | VARCHAR(50)           | NULL                        |                                  |
| status                  | VARCHAR(20)           | NOT NULL                    | DRAFT / LOCKED / PROMOTED        |
| linked_product_order_id | BIGINT                | NULL                        | Diisi saat di-promote ke Phase B |
| created_by, created_at  | VARCHAR / TIMESTAMPTZ | NOT NULL                    |                                  |

#### 7.1.5. routing_draft_component

| **Column**         | **Type**     | **Constraint**            | **Notes**                                               |
|--------------------|--------------|---------------------------|---------------------------------------------------------|
| component_id       | BIGSERIAL    | PK                        |                                                         |
| draft_id           | BIGINT       | FK routing_draft NOT NULL |                                                         |
| sequence_no        | INT          | NOT NULL                  |                                                         |
| sub_sequence       | INT          | NULL                      | Untuk Multi Yarn                                        |
| sub_type           | VARCHAR(30)  | NULL                      |                                                         |
| rm_type            | VARCHAR(30)  | NOT NULL                  | Store Rate / Captive Cost / Multi Yarn / Uneven Packing |
| rm_ref_text        | VARCHAR(255) | NOT NULL                  | Free-text reference                                     |
| rm_ref_resolved_id | BIGINT       | NULL                      | Resolved bila ada master / Phase B product              |
| notes              | TEXT         | NULL                      |                                                         |

#### 7.1.6. request_comment

| **Column**             | **Type**    | **Constraint**              | **Notes**                           |
|------------------------|-------------|-----------------------------|-------------------------------------|
| comment_id             | BIGSERIAL   | PK                          |                                     |
| request_id             | BIGINT      | FK product_request NOT NULL |                                     |
| parent_comment_id      | BIGINT      | NULL                        | Reserved untuk threading di future  |
| author_user_id         | VARCHAR(64) | NOT NULL                    |                                     |
| body_richtext          | JSONB       | NOT NULL                    | Tiptap/Lexical JSON tree            |
| body_plaintext         | TEXT        | NOT NULL                    | Plaintext copy untuk search & notif |
| is_edited              | BOOLEAN     | DEFAULT false               |                                     |
| is_hidden              | BOOLEAN     | DEFAULT false               | Admin moderation                    |
| created_at, updated_at | TIMESTAMPTZ | NOT NULL                    |                                     |

#### 7.1.7. request_comment_edit_history

Versi historis dari comment yang di-edit (untuk transparansi).

Schema: edit_id, comment_id (FK), body_richtext (snapshot), body_plaintext (snapshot), edited_by, edited_at.

#### 7.1.8. request_mention

Tabel mention untuk fast lookup dan notification trigger.

Schema: mention_id, comment_id (FK), mentioned_user_id, is_notified, notified_at.

#### 7.1.9. attachment

Generic attachment yang dapat dilampirkan ke request atau ke comment.

| **Column**               | **Type**              | **Constraint**                   | **Notes**                  |
|--------------------------|-----------------------|----------------------------------|----------------------------|
| attachment_id            | BIGSERIAL             | PK                               |                            |
| request_id               | BIGINT                | FK product_request, NULL allowed | Attachment level request   |
| comment_id               | BIGINT                | FK request_comment, NULL allowed | Attachment level comment   |
| filename                 | VARCHAR(255)          | NOT NULL                         |                            |
| mime_type                | VARCHAR(100)          | NOT NULL                         |                            |
| size_bytes               | BIGINT                | NOT NULL                         |                            |
| storage_key              | VARCHAR(500)          | NOT NULL                         | Path/key di object storage |
| uploaded_by, uploaded_at | VARCHAR / TIMESTAMPTZ | NOT NULL                         |                            |

CHECK constraint: exactly one of (request_id, comment_id) is non-null.

#### 7.1.10. user_role_mapping

Mapping antara user dari SSO dengan Tier dan Functional role.

Schema: mapping_id, user_id, tier (User/Dept Lead/Manager/Admin), functional_role (Marketing/Engineering/Produksi/RND/Finance/...), is_active, effective_from, effective_to.

#### 7.1.11. notification

Record in-app notification + status pengiriman email.

Schema: notification_id, recipient_user_id, trigger_type, request_id (FK, NULL allowed), payload (JSONB), is_read, email_sent_at, created_at.

#### 7.1.12. user_notification_preference

Preferensi notif per user per trigger type. Schema: pref_id, user_id, trigger_type, channel_email (bool), channel_in_app (bool), digest_mode (immediate/daily).

#### 7.1.13. audit_log

Log audit umum (mirror konsep dari Phase B). Schema: log_id, entity_type, entity_id, operation, before_data (JSONB), after_data (JSONB), user_id, performed_at.

### 7.2. Relasi (ERD Summary)

\[product_request\] 1:N \[routing_draft\] 1:N \[routing_draft_component\]

\[product_request\] 1:N \[request_comment\] 1:N \[attachment\]

\[product_request\] 1:N \[attachment\] (attachment di-request level)

\[request_comment\] 1:N \[request_mention\] N:1 \[user\]

\[request_comment\] 1:N \[request_comment_edit_history\]

\[request_type\], \[routing_rule\], \[user_role_mapping\] — master/config tables

\[notification\] N:1 \[user\] ; \[user_notification_preference\] N:1 \[user\]

\[audit_log\] — cross-cutting; mereferensi entity manapun via (entity_type, entity_id)

Catatan integrasi Phase B (ditulis sebagai future migration):

- Saat Phase B live: tambah kolom routing_draft.linked_product_order_id (FK ke Phase B.product_order).

- Migration job: untuk setiap routing_draft dengan status DRAFT/LOCKED, generate record product_order di Phase B sesuai isi draft + komponennya; set linked_product_order_id dan status draft → PROMOTED.

- Setelah Phase B live, draft baru tetap dibuat di Phase A; promote eksplisit yang trigger pembuatan Product Order.

## 8. Non-Functional Requirements

### 8.1. Performance

- List request (paginated, ≤50/halaman): response \< 800 ms untuk dataset ≤20.000 request.

- Detail request (termasuk thread comment): response \< 1.2 detik.

- Dashboard fixed: render initial \< 2 detik.

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

| **Risk**                                                                                 | **Severity** | **Mitigation**                                                                                                                                               |
|------------------------------------------------------------------------------------------|--------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Adoption gap: tim Marketing tetap pakai email/WA karena merasa form sistem ribet         | High         | UX test dengan Marketing user awal; form submit \< 60 detik untuk request sederhana; notif lewat email tetap aktif agar familiar                             |
| Triage owner jadi single point of failure (1 orang Engineering Lead overload)            | Medium       | Sistem mendukung multi-triage-owner; routing rule untuk "high urgency → CC triage backup"                                                                    |
| Comment thread jadi panjang & noisy; mention berlebihan menyebabkan notification fatigue | Medium       | Notification preference per trigger; daily digest opsi; sub-thread reply diserahkan ke future enhancement                                                    |
| Routing draft drift dari Phase B Product Order (struktur berbeda saat Phase B live)      | Medium       | Schema draft di-mirror dari Phase B PRD; migration script disusun bersama dengan Phase B implementation; draft di-validate dengan Phase B rules saat promote |
| Custom role kebutuhan muncul (mis. "Marketing Region Leader" yang lihat region tertentu) | Low-Med      | Tier × Functional matrix punya extension via functional_role naming; bila kebutuhan kompleks → Phase A v1.x add scoped permission                            |
| Performance degradation saat dataset besar (\>100K request)                              | Medium       | Pagination konsisten, index pada (status, requester_user_id, assigned_to_user_id); archive request lebih dari 2 tahun ke tabel terpisah bila perlu           |

## 11. Implementation Phasing (Within Phase A)

**Sprint Block 1 — Foundation**

- Schema database lengkap, master data seeding (request_type, routing_rule default).

- Autentikasi SSO integration, user_role_mapping admin UI.

- FR-1 Submit Request, FR-2 State Machine.

**Sprint Block 2 — Lifecycle & Routing**

- FR-3 Hybrid Routing dengan admin UI untuk routing rule (FR-19).

- FR-4 Review & Classification.

- FR-7 Routing Draft CRUD, FR-8 Status Sync.

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

17. Customer master data: integrasi ke CRM eksternal atau cukup free-text di MVP? Pengaruhi FR-1.

18. Email gateway specific: SES, SendGrid, atau internal SMTP relay? Pengaruhi NFR & deployment.

19. Object storage: S3, MinIO on-prem, atau eksternal lain? Pengaruhi attachment NFR.

20. Retention audit log 5 tahun: konfirmasi dengan tim legal/compliance.

21. Bahasa rich-text content: hanya Bahasa Indonesia, atau juga Bahasa Inggris (untuk customer asing)? Pengaruhi search indexing.

22. Read-only API authentication: API key, OAuth client credentials, atau bearer token via SSO?

23. Default routing rules awal: butuh workshop dengan Engineering Lead untuk seed routing_rule master sebelum go-live.

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

| **Version** | **Date** | **Description**                                | **Author** |
|-------------|----------|------------------------------------------------|------------|
| 1.0         | May 2026 | Initial draft Phase A — Product Request Module | —          |
