---
title: "PRD — Costing Workflow Suite, Phase B: Product Order, BOM & Parameter Management"
version: "1.4"
status: "Draft"
phase: "B"
last_updated: "2026-05"
author: "[IT Leader]"
related:
  - "PRD_PhaseA.md"
  - "PRD_PhaseC.md"
  - "ERD_Master.md"
  - "GLOSSARY.md"
  - "INTEGRATION_CrossPhase.md"
changelog:
  - version: "1.4"
    date: "2026-05"
    changes:
      - "Tambah cost_parameter_master (CPRM_) — definisi 125 parameter dengan metadata engine"
      - "Tambah Generic Master Pattern: cost_master_definition (CMD_) + cost_master_data (CMSD_)"
      - "Tambah cost_product_parameter (CPP_) — static parameter values per product"
      - "Tambah cost_parameter_dependency (CPRD_) — dependency graph (visualization only)"
      - "Tambah Section 6.12-6.16 FR parameter management & cross-phase hook"
      - "Tambah Section 7.9 data model parameter & master tables"
      - "Seed data: 125 parameter records, 8 master type definitions"
  - version: "1.3"
    date: "2026-05"
    changes:
      - "Tambah cost_product_master (CPM_) — product identity terpisah dari product order"
      - "Tambah cost_product_type (CPT_) — master jenis product (POY/PTY/TTY/dll)"
      - "Tambah cost_product_code_counter (CPCC_) — atomic auto-increment per type+YYMM"
      - "Tambah cost_rm_type (CRMT_) — RM type jadi master table user-definable"
      - "Tambah ERP replica tables: cost_erp_item (CEI_), cost_erp_grade (CEG_), cost_erp_shade (CES_)"
      - "Restructure product_order: identity pindah ke product_master"
      - "Terapkan Column Prefix Naming Convention ke seluruh data model"
      - "Product code format: CST + TYPE(3) + YYMM(4) + AUTO(6)"
  - version: "1.2"
    date: "2026-05"
    changes:
      - "Added Section 6.7 Costing Orchestration (FR-19 sampai FR-24)"
  - version: "1.1"
    date: "2026-05"
    changes:
      - "Added FR-9 Flow View, FR-17 Flow Editor, FR-18 Auto-Layout"
---

# PRD — Phase B: Product Order & BOM Management System
## Costing Workflow Suite

> *Sistem Pengelolaan Urutan Proses Produksi, BOM, dan Parameter Cost*
> Version 1.4 — Draft | May 2026

---

## 1. Executive Summary

Dokumen ini mendefinisikan kebutuhan untuk membangun sistem pengelolaan Product Order — sebuah aplikasi internal yang menyimpan dan mengelola urutan proses produksi dari raw material awal hingga finished goods. Setiap produk dapat melalui satu hingga lebih dari tujuh tahap produksi, dimana output suatu tahap dapat menjadi input bagi tahap berikutnya (multi-level Bill of Materials).

Sejak v1.3, sistem ini juga mengelola **Product Master** — identitas product di costing system yang terpisah dari ERP. Setiap product di costing punya kode unik (format: CSTPTY2605000001) dan dapat di-link ke ERP item secara informational. Product master menjadi fondasi: product order (BOM) mereferensi product master, dan komponen BOM juga mereferensi product master (untuk Captive Cost) atau ERP item langsung (untuk Store Rate).

Tujuan utama sistem ini adalah:

- Menjadi single source of truth identitas product di costing system, terpisah dari ERP karena costing terjadi sebelum transaksi produksi/sales.

- Menjadi single source of truth struktur BOM (Bill of Materials) multi-level untuk seluruh produk perusahaan.

- Menghilangkan ketergantungan pada file Excel manual yang rawan duplikasi data dan sulit di-update.

- Menyediakan visualisasi tree produk dan reporting BOM explosion / where-used yang cepat dan akurat.

- Mempertahankan historical traceability ketika struktur BOM berubah (versioning).

- Menjadi costing orchestrator: menyediakan topological sort dan dependency resolution untuk calculation engine eksternal.

## 2. Background & Problem Statement

### 2.1. Konteks Bisnis

Perusahaan memproduksi beragam jenis yarn (POY, PTY, TTY, ACY, MELANGE, dll.) yang dihasilkan melalui rangkaian proses produksi berjenjang. Sebuah finished goods seperti TCM0000001 (varian shade tertentu) dapat dihasilkan melalui lima atau lebih tahap berurutan, dimana setiap tahap mengubah material dari bentuk satu ke bentuk berikutnya:

Chips BRT → POY0000458 → PTY0001531 → PTY0001532 → TCY0000061 → TCM0000001

Output dari setiap tahap adalah finished goods bagi tahap tersebut, sekaligus menjadi raw material bagi tahap selanjutnya. Karena satu intermediate product (misalnya TCY0000061) dapat dipakai oleh banyak product hilir, struktur datanya bersifat directed acyclic graph (DAG), bukan tree sederhana.

### 2.2. Konteks ERP vs Costing

Di ERP (Oracle), transaksi produksi dan sales menggunakan 3 Primary Key: item_code, item_grade_code_1, item_grade_code_2. Contoh:

- item_code: PTY0000001 (prefix 3 digit jenis product + 7 digit auto increment)
- item_grade_code_1: AX, AM, B, C (grading kualitas)
- item_grade_code_2: NL, Z114S, Z108S (warna/shade)

Cost di ERP di-define per kombinasi ketiga key tersebut, misalnya PTY0000001-AX-Z108S = USD 1.2/kg.

Namun costing terjadi **sebelum** produksi dan sales di ERP. Product yang akan di-cost mungkin belum ada di ERP. Untuk itu, costing system memiliki product master sendiri dengan format kode: CST + jenis product (3 digit) + YYMM (4 digit) + auto number (6 digit). Contoh: CSTPTY2605000001.

Perbedaan kunci dengan ERP: di costing, setiap kombinasi item+shade = **satu product record terpisah** (bukan satu item dengan multiple grade/shade). Grade di costing default ke grade terbaik (AX) karena produksi selalu menargetkan grade tertinggi — grade dibawahnya adalah turunan yang harganya diturunkan sesuai policy perusahaan. Namun grade tetap di-record untuk future differentiation.

Product master costing memiliki field untuk link ke item_code, grade, dan shade di ERP — sebagai attribut informational untuk rekonsiliasi setelah product masuk ERP.

### 2.3. Kondisi Saat Ini

Saat ini mapping urutan proses dikelola dalam file Excel berisi 31.012 baris pasangan FG-RM. Sebuah finished goods muncul beberapa kali dengan urutan komponen di-flatten dari komponen terjauh (chips) hingga ke dirinya sendiri.

Beberapa masalah dari pendekatan saat ini:

- Data redundan — struktur flatten menyebabkan satu intermediate product dicatat berulang kali di setiap product hilir yang memakainya.

- Update sulit — perubahan komponen di satu intermediate product mengharuskan update di banyak baris pada banyak produk hilir; rawan inkonsistensi.

- Tidak ada versioning — perubahan struktur menghilangkan jejak struktur sebelumnya, menyulitkan audit produksi historis.

- Visualisasi & analisis terbatas — sulit menjawab pertanyaan seperti "semua produk yang memakai PTY0001531" atau "berapa total stage proses untuk TCM0000001" tanpa pengolahan manual.

- Tidak ada validasi struktural — potensi terjadinya cycle (BOM lingkar) tidak dideteksi sistem.

### 2.4. Stakeholders

| **Stakeholder** | **Kebutuhan Utama** | **Akses Tipikal** |
|---|---|---|
| Production Planning | Membaca BOM untuk perencanaan produksi | Read-only, BOM explosion |
| Process Engineering | Memelihara product master dan struktur BOM | Create, Read, Update, Delete |
| Cost Accounting | Membaca struktur captive cost untuk perhitungan HPP | Read-only, Where-used |
| Master Data Management | Memelihara konsistensi data master & integrasi ERP | Admin, Import/Export, ERP sync |
| Auditor (Internal) | Verifikasi historis struktur produksi | Read-only, Version history |

## 3. Goals & Non-Goals

### 3.1. Goals (In-Scope)

1. Menyediakan Product Master sebagai identitas product di costing system, dengan format kode sendiri (CST prefix) dan linkage informational ke ERP.

2. Menyimpan struktur BOM multi-level untuk seluruh finished goods variant secara normalized (1 record = 1 komponen langsung).

3. Menyediakan CRUD untuk product master, product order, version, dan komponennya melalui antarmuka pengguna.

4. Menyediakan visualisasi BOM tree (vertical hierarchical) dan flow view (horizontal DAG) yang dapat di-expand multi-level.

5. Menyediakan editor BOM visual berbasis drag-and-drop canvas yang memungkinkan user meng-compose struktur BOM secara grafis.

6. Menyediakan report BOM explosion (drill-down semua komponen dari FG ke RM terbawah) dan where-used (drill-up: produk apa saja yang memakai suatu item).

7. Mempertahankan versioning lengkap atas perubahan BOM (setiap save = versi baru, versi lama tetap accessible).

8. Mendeteksi dan memberi peringatan bila terjadi struktur cycle.

9. Menyediakan fasilitas import data awal dari file Excel format baku.

10. Menyediakan ERP replica tables (item, grade, shade) di PostgreSQL untuk referensi master data dan komponen Store Rate.

11. Menyediakan RM Type sebagai master table yang user-definable, dengan reference_target (PRODUCT atau MASTER) untuk menentukan FK mana yang dipakai oleh komponen.

12. Menjadi costing orchestrator: menyediakan topological sort dan dependency resolution untuk calculation engine eksternal, dengan dukungan batch maupun on-demand.

### 3.2. Non-Goals (Out-of-Scope)

- Quantity / consumption rate per komponen (berapa kg/meter RM untuk 1 unit FG).

- Formula kalkulasi cost (weighted, yield factor, loss percentage) — dilakukan oleh calculation engine eksternal.

- Penyimpanan parameter cost & cost result — disimpan di tabel eksternal.

- Inventory management, stok, dan transaksi gudang.

- Production scheduling, work order, MRP.

- Pengelolaan master data ERP (item, grade, shade) — hanya replica read-only; pengelolaan di ERP Oracle.

- User & role management — diambil dari SSO/IAM eksisting.

- Approval workflow — perubahan langsung berlaku setelah disimpan (audit trail tersedia).

## 4. Key Concepts & Terminology

| **Term** | **Definition** |
|---|---|
| Product Master | Identitas product di costing system. Satu record = satu product (item+shade). Memiliki kode sendiri (CST prefix) terpisah dari ERP. |
| Product Code | Business key product master, format: CST + TYPE(3) + YYMM(4) + AUTO(6). Contoh: CSTPTY2605000001. |
| Product Type | Klasifikasi jenis product (POY, PTY, TTY, dll). Master table, dikelola Admin. |
| Product Order | Definisi struktur BOM untuk satu product master — entity yang menyimpan HOW to make a product. |
| Component | Satu raw material atau intermediate product yang masuk sebagai komponen langsung sebuah product order. |
| RM Type | Klasifikasi komponen — user-definable via master table. Initial: Store Rate, Captive Cost, Multi Yarn, Uneven Packing. Setiap RM type punya reference_target (PRODUCT atau MASTER) yang menentukan FK di komponen. |
| Reference Target | Properti RM type: PRODUCT (komponen referensi ke product master / Captive Cost) atau MASTER (komponen referensi ke ERP item / Store Rate). |
| Single-Level BOM | Daftar komponen langsung satu product order (parent → direct children saja). |
| Multi-Level BOM | Drill-down rekursif dari finished goods hingga raw material terbawah. |
| BOM Explosion | Operasi traversal turun (parent → children → grand-children) untuk mendapatkan multi-level BOM. |
| Where-Used | Operasi traversal naik (child → parents → grand-parents) untuk mengetahui semua produk yang memakai suatu item. |
| Version | Snapshot struktur BOM pada satu titik waktu. Setiap perubahan menghasilkan version baru; version lama menjadi superseded namun tetap dapat ditelusuri. |
| Cycle | Kondisi error dimana sebuah product order menggunakan dirinya sendiri sebagai komponen (langsung atau tidak langsung). |
| ERP Linkage | Field informational di product master (item_code, grade_code_1, grade_code_2) untuk link ke ERP Oracle. Bukan FK — hanya attribut. |
| Grade | Klasifikasi kualitas produk (AX, AM, B, C). Di costing default ke grade terbaik (AX). Editable untuk future differentiation. |

## 5. Assumptions & Dependencies

1. Database menggunakan PostgreSQL (versi 14+ untuk dukungan recursive CTE yang optimal).

2. SSO/IAM eksisting menyediakan identitas user dan role.

3. ERP menggunakan Oracle DB. Master data (item, grade, shade) perlu di-replika ke PostgreSQL sebagai read-only tables. Mekanisme sync (CDC / scheduled job) = infrastructure concern, ditentukan saat implementasi.

4. Struktur BOM yang ada di file Excel sumber diperlakukan sebagai inisial data dan akan diimport satu kali.

5. Field RM_LEFT_NO_INTO di Excel sumber dianggap redundan; 156 baris anomali diperlakukan sebagai data error dan akan di-normalisasi saat import.

6. Field FG_SEQUENCE di Excel sumber dianggap derivative dari cylinder type (1:1 mapping); tidak disimpan sebagai kolom terpisah.

7. Field RM_SUB_SEQUENCE_REAL di Excel sumber adalah invers dari RM_SEQUENCE; redundan, hanya RM_SEQUENCE yang akan disimpan.

8. Phase A (Product Request) mungkin sudah live saat Phase B go-live. Routing draft di Phase A dapat di-promote ke product order di Phase B. Promote flow: PIC manual select/create product master → system create product order + components.

9. Costing terjadi sebelum produksi/sales di ERP. Product yang di-cost mungkin belum ada di ERP — sehingga ERP linkage fields nullable.

10. Grade di costing default AX (top grade) — karena produksi menargetkan grade tertinggi. Grade dibawahnya adalah turunan dengan harga yang diturunkan sesuai policy perusahaan. Namun field grade tetap ada untuk future differentiation.

## 6. Functional Requirements

### 6.1. Product Master Management

**FR-1a: Create Product Master**

- User dapat membuat product master baru dengan mengisi: product type (dropdown dari master), product name (free-text deskripsi lengkap), shade code (free-text introductory), grade code (default AX, editable), description.

- Sistem auto-generate product code: CST + type_code + YYMM + auto_number(6). Counter per (product_type + YYMM), bukan global. Contoh: CSTPTY2605000001 (PTY pertama di Juni 2026).

- ERP linkage fields (item_code, grade_code_1, grade_code_2) opsional — dapat diisi saat product sudah ada di ERP.

- Validasi: product code harus unik secara global (dijamin oleh sistem).

**FR-1b: Search / Browse Product Master**

- User dapat mencari product master berdasarkan: product code (partial match), product name (full-text search), product type, shade code, ERP item code.

- Hasil pencarian dapat di-filter, di-sort, dan dipaginasi.

- Detail product master menampilkan: semua attribut, ERP linkage, linked product order (jika ada), audit info.

**FR-1c: Update Product Master**

- User dapat update: product name, shade code, grade code, description, ERP linkage fields.

- Product code dan product type tidak dapat diubah setelah create (immutable).

- Setiap update dicatat di audit log.

**FR-1d: Deactivate Product Master**

- Soft-delete: is_active = false.

- Tidak diperbolehkan men-deactivate product master yang sedang dipakai sebagai komponen di product order aktif — sistem menampilkan daftar dependency dan menolak operasi.

### 6.2. Product Order Management (CRUD)

**FR-2: Create Product Order**

- User dapat membuat product order baru dengan memilih product master (autocomplete search) dan mengisi cylinder type (FK master).

- Satu product master dapat memiliki maksimal satu product order aktif.

- Saat create, sistem otomatis membuat version pertama dengan status draft.

**FR-3: Read / Search Product Order**

- User dapat mencari product order berdasarkan: product code (dari product master), product name, cylinder type.

- Hasil pencarian dapat di-filter, di-sort, dan dipaginasi.

- User dapat melihat detail product order: product master info, daftar komponen pada version aktif, history version.

**FR-4: Update Product Order**

- Update terhadap atribut order (cyl_type) langsung berlaku tanpa membuat version baru.

- Update terhadap struktur komponen (BOM lines) selalu membuat version baru: version aktif sebelumnya menjadi superseded.

- Tidak diperbolehkan menghapus atau mengubah version yang sudah superseded — hanya read-only.

**FR-5: Delete Product Order**

- Soft-delete: is_active = false, record tetap ada untuk historical traceability.

- Tidak diperbolehkan men-delete product order yang sedang dipakai sebagai komponen (via product master) di product order lain yang masih aktif.

### 6.3. BOM Component Management

**FR-6: Add Component**

- Pada saat editing version draft, user dapat menambah komponen dengan input: RM type (dropdown dari master cost_rm_type), RM reference (autocomplete), sequence_no, dan optional sub_sequence + sub_type.

- RM type menentukan reference target:
  - reference_target = PRODUCT → autocomplete dari product master (Captive Cost-like types).
  - reference_target = MASTER → autocomplete dari cost_erp_item (Store Rate-like types).
  - Bila RM type allow_sub_sequence = true → satu sequence_no boleh memiliki beberapa baris dengan sub_sequence berbeda (Multi Yarn-like types).

- Fallback: bila RM belum ada di master/product, user dapat isi rm_description sebagai free-text temporary.

**FR-7: Reorder / Edit / Remove Component**

- User dapat mengubah sequence_no, mengganti RM, atau menghapus komponen — semua hanya pada version draft.

- Perubahan tersimpan saat user men-commit version draft menjadi active (membuat version baru).

**FR-8: Cycle Detection**

- Sebelum commit version baru, sistem menjalankan traversal komponen dengan reference_target = PRODUCT untuk mendeteksi cycle (langsung atau tidak langsung).

- Bila cycle terdeteksi, sistem menampilkan warning lengkap dengan jalur cycle dan meminta konfirmasi user.

- User dapat memilih untuk tetap menyimpan (override) atau membatalkan. Bila override, sistem mencatat tanda cycle_override pada version tersebut.

### 6.4. Visualization

**FR-9: BOM Tree View**

- Tampilan tree expand/collapse dari finished goods ke bawah hingga raw material terbawah.

- Setiap node menampilkan: product code, product name, RM type name.

- Node dengan reference_target = PRODUCT dapat di-expand (mengarah ke product order lain); node dengan reference_target = MASTER adalah leaf.

- User dapat klik node PRODUCT untuk navigasi ke product order tersebut sebagai context baru.

**FR-10: BOM Graph View (Flow View)**

- Visualisasi horizontal node-graph (mirip flow diagram industri) untuk product dengan convergent flow.

- Node disusun secara horizontal per stage. Edge berupa kurva Bezier ber-arrow yang menunjukkan arah aliran material.

- Mendukung convergent (many-to-one) dan divergent (one-to-many).

- Mode read-only: user dapat pan, zoom, dan klik node untuk highlight.

- Tersedia tombol "Edit di Flow Editor" untuk berpindah ke mode editing (FR-18).

**FR-18: Flow Editor (Drag-Drop Visual BOM Builder)**

Editor visual yang memungkinkan user membangun atau memodifikasi struktur BOM dengan drag-and-drop pada canvas.

Komponen UI:

- Panel kiri (Item Library / Palette): daftar item dari product master dan cost_erp_item yang dapat di-drag ke canvas, terbagi per RM type.

- Canvas tengah (Editor Surface): area kerja dengan grid background, snap-to-grid, pan/zoom, dan fit-to-screen.

- Panel kanan (Inspector): menampilkan atribut node atau edge yang sedang dipilih; user dapat edit rm_type, sequence di sini.

- Toolbar atas: undo/redo, auto-layout, align-stages, zoom controls, snap toggle, validate, save draft, commit.

Interaksi pokok:

- Drag item dari Palette ke Canvas → membuat node baru pada posisi drop.

- Drag dari port output node ke port input node lain → membuat edge / koneksi (komponen relationship).

- Tombol Validate → jalankan cycle detection; menampilkan jalur cycle bila ditemukan.

- Tombol Commit version → simpan canvas sebagai version baru product order.

Mapping canvas ke schema:

- Setiap node = satu product master (PRODUCT) atau satu ERP item (MASTER); posisi disimpan di cost_bom_layout.

- Setiap edge = satu baris di cost_product_order_component.

- Convergent flow otomatis di-mapping sebagai komponen multiple.

**FR-19: Auto-Layout & Visual Helpers**

- Auto-layout: tombol untuk menghitung ulang posisi semua node menggunakan hierarchical layout algorithm (dagre / elkjs).

- Snap-to-grid (toggle): posisi node dibulatkan ke grid 20px terdekat.

- Align-stages: paksa semua node dengan stage yang sama berada di kolom x yang sama.

### 6.5. Reporting

**FR-11: BOM Explosion Report**

- Input: satu atau lebih product code; opsional: max depth, filter RM type.

- Output: daftar lengkap semua komponen multi-level beserta level kedalaman.

- Format hasil: tabel pada layar, exportable ke Excel/CSV.

- Performa target: explosion 1 product (max depth 10) selesai dalam < 2 detik.

**FR-12: Where-Used Report**

- Input: satu product code (dari product master) ATAU satu item code (dari ERP item).

- Output: daftar lengkap product order yang memakai item tersebut sebagai komponen, langsung maupun tidak langsung, beserta level.

### 6.6. Version Management

**FR-13: View Version History**

- Pada halaman detail product order, terdapat tab Version History menampilkan: nomor version, status (draft/active/superseded), tanggal create, user, jumlah komponen.

- User dapat klik version untuk melihat snapshot struktur BOM pada version tersebut (read-only).

**FR-14: Compare Versions**

- User dapat memilih dua version dan melihat diff: komponen yang ditambah, dihapus, atau diubah.

### 6.7. Data Import / Export

**FR-15: Initial Import dari Excel**

- Sistem menyediakan fasilitas import dari file Excel format baku (didefinisikan di Section 8).

- Import bersifat upsert: bila product order sudah ada, akan dibuatkan version baru; bila belum ada, dibuat record baru (termasuk auto-create product master jika belum ada).

- Sistem menampilkan preview validasi sebelum commit.

- Setiap import dicatat sebagai import job dengan log lengkap.

**FR-16: Export ke Excel**

- User dapat mengexport: single product order (full BOM exploded), multiple product order, atau dump seluruh database.

### 6.8. Costing Orchestration

Sistem ini berperan sebagai costing orchestrator: menyediakan urutan eksekusi (topological sort), resolusi dependency komponen, dan integrasi dengan engine eksternal yang melakukan kalkulasi sebenarnya. Sistem ini tidak menyimpan parameter cost maupun cost result.

**FR-20: Topological Sort untuk Cost Rollup**

- Sistem menyediakan API yang menerima CPM_product_sys_id dan version_id (opsional, default current_version), lalu mengembalikan urutan node yang harus di-cost dari level terdalam ke FG.

- Urutan di-derive dari cost_product_order_exploded, di-dedup berdasarkan (rm_product_sys_id / rm_master_item_id).

**FR-21: Dependency Resolution per Node**

- Untuk setiap node dalam urutan kalkulasi, sistem menyediakan daftar komponen dari cost_product_order_component pada active version.

- Setiap komponen disertai RM type (dan reference_target-nya), sub_sequence, dan sub_type.

**FR-22: Batch Costing Orchestrator**

- Trigger batch per period untuk seluruh product_order aktif.

- Orchestrator menjalankan: gather → topological sort → dependency resolution → call calculation engine → write result.

- Eksekusi menghormati dependency order: node terdalam dihitung lebih dulu.

- Product yang parameternya tidak lengkap di-skip dan dicatat ke error log.

**FR-23: On-Demand Costing**

- User dapat trigger kalkulasi cost untuk satu product beserta dependency chain-nya.

- Preview sebelum eksekusi: berapa node yang akan dihitung.

**FR-24: Costing Status Dashboard**

- Menampilkan per period: total product, sudah di-cost, gagal, pending.

- Drill-down per product: status, last calculated, link ke error detail.

**FR-25: External Dependencies Contract**

| **Tabel** | **Pemilik** | **Operasi sistem ini** | **Konteks** |
|---|---|---|---|
| Master raw material price | TBD | Lookup cost per CEI_item_id di period tertentu. Read only. | Node berikutnya butuh cost komponen reference_target = MASTER |
| Parameter cost (process cost) | TBD | Lookup parameter per CPM_product_sys_id di period tertentu. Read only. | Input bagi calculation engine |
| Cost result | TBD | Insert/update per (CPM_product_sys_id, CPOV_version_id, period). | Output kalkulasi; input bagi node atasnya saat rollup |

### 6.9. RM Type Management (Admin)

**FR-26: RM Type CRUD**

- Admin dapat create/edit/disable RM type via admin panel.

- Setiap RM type memiliki: code, display name, reference_target (PRODUCT atau MASTER), allow_sub_sequence flag.

- RM type tidak dapat dihapus jika ada komponen yang menggunakannya — hanya di-disable.

- reference_target tidak dapat diubah jika ada komponen existing yang menggunakan RM type tersebut.

### 6.10. ERP Replica Management (Admin)

**FR-27: ERP Sync Status**

- Admin dashboard menampilkan: last sync timestamp per replica table (item, grade, shade), jumlah record, jumlah record active/inactive.

- Manual trigger untuk force sync (selain scheduled job).

- Sync error log: record yang gagal di-sync dengan alasan.

### 6.11. Audit Trail

**FR-17: Activity Log**

- Setiap operasi write (create, update, delete, version commit, import, ERP link change) tercatat dengan: timestamp, user ID, operasi, entity, before-value, after-value.

- Menggunakan cost_audit_log (CAL_) yang shared dengan Phase A.

### 6.12. Parameter Master Management

**FR-28: Parameter Master Display**

- Read-only listing untuk semua user: 125 parameters, filterable by department, function type, display group.
- Setiap param menampilkan: code, name, description, function type, owner dept, is_required, is_period_dependent, lookup target (jika LOOKUP).
- Sorted berdasarkan `CPRM_display_order`.

**FR-29: Parameter Master Admin Edit**

- Admin dapat update field non-critical: description, display_order, display_group, is_required_for_costing, owner_department.
- Field critical **immutable** post-creation (param_code, function_type, data_type, calc_function_key) — perubahan butuh code deployment dan reviewer approval.

**FR-30: Parameter Search**

- Full-text search pada param_code, param_name, description.
- Multi-filter: owner_dept + function_type + is_required + is_period_dependent.

### 6.13. Generic Master Management

**FR-31: Master Definition CRUD**

- Admin dapat create master type baru via Admin panel.
- Field wajib: master_code (immutable setelah create), master_name, is_period_dependent flag.
- Optional: attributes_schema — JSON schema untuk validasi JSONB attributes di data rows.

**FR-32: Master Data CRUD**

- Admin dapat create/update/disable rows di master data per master type.
- Form dinamis berdasarkan `attributes_schema` dari master definition (guided editor jika schema ada).
- Untuk period-dependent master: field period wajib diisi.
- Disable (soft delete) — tidak hard delete agar history calculation terjaga.

**FR-33: Master Data Listing**

- Per master type: tabel listing dengan filter period (untuk period-dependent), filter active status.
- Pagination, sortable columns.

**FR-34: Master Audit**

- Setiap INSERT/UPDATE/disable pada cost_master_data tercatat di cost_audit_log.
- Event ini dapat surface di Phase A activity timeline untuk product yang terdampak.

### 6.14. Static Parameter Entry (Phase B)

**FR-35: Static Parameter Entry Form**

- Per product, form parameter diorganisasi per display_group (Spec, Machine, Grade, Packing, dll).
- Hanya menampilkan params yang `function_type IN ('ENTRY', 'JSONB')` dan `is_period_dependent = false`.
- Visibility param disesuaikan departemen user: params dengan `owner_department` matching user's department diprioritaskan di atas.
- Required indicator (asterisk) untuk `is_required_for_costing = true`.

**FR-36: Conditional Parameter Display**

- Param dengan `required_for_yarn_types` filter hanya tampil jika yarn type product (PARAM 1) match.
- Param JSONB (Raw Material, Masterbatch) tampil sebagai compound JSON editor.

**FR-37: Save Parameter Value**

- UPSERT ke `cost_product_parameter` (static).
- Validasi: hanya satu value column yang terisi, sesuai data_type param.
- Mandatory field: filled_by, filled_at.
- Trigger **service hook auto-complete** (FR-38).

**FR-38: Cross-Phase Auto-Complete Hook**

- Setiap kali parameter value disimpan (FR-37 atau Phase C dynamic save):
  1. Cari Phase A request yang link ke product ini via `CPR_resolved_product_sys_id`.
  2. Jika request status = PARAMETER_PENDING, call `get_missing_required_params()`.
  3. Jika hasilnya empty → auto-transition ke PARAMETER_COMPLETE (**monotonic** — tidak bounce back).
- Detail flow di `INTEGRATION_CrossPhase.md` section INT-5.

### 6.15. Parameter Dependency Graph (Visualization)

**FR-39: Dependency Graph Display**

- Visualisasi DAG antar parameter menggunakan graph view (force-directed atau tree layout).
- Click param → tampilkan: depends on (upstream), is depended by (downstream).
- Use case: impact analysis — "kalau PARAM 8 berubah, param mana saja yang terpengaruh?"

**FR-40: Dependency Graph Sync**

- Read-only dari user perspective. Source of truth ada di Go calculation engine registry.
- Auto-sync triggered pada deployment via CI/CD: truncate-and-insert `cost_parameter_dependency`.
- Timestamp sync ditampilkan di header graph view.

### 6.16. Bulk Parameter Import

**FR-41: Bulk Import Parameter Values**

- Admin dapat import nilai parameter via CSV.
- Format: product_code, param_code, value (static values only — period values via Phase C).
- Preview validation sebelum commit: validasi product exists, param exists, data_type match.
- Import report: success count, failed rows dengan alasan.

## 7. Data Model

### 7.0. Konvensi Penamaan — Column Prefix

Seluruh tabel menggunakan Column Prefix Naming Convention: setiap kolom diawali prefix inisial dari nama tabel (termasuk module prefix `cost_`). Nama kolom globally unique di seluruh database.

**Prefix Registry Phase B:**

| Prefix | Table Name | Category |
|---|---|---|
| CPT_ | cost_product_type | Master |
| CPCC_ | cost_product_code_counter | Master |
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
| CAL_ | cost_audit_log | Shared (Phase A+B) |

**Combined Phase A + B: 28 tables, zero collision.**

### 7.1. Pendekatan: Hybrid Storage

Storage utama menggunakan model normalized single-level BOM (mengikuti praktik industri ERP). Untuk mendukung query BOM explosion yang sering dan cepat, sistem menyediakan materialized view (cost_product_order_exploded) yang berisi struktur flatten — di-refresh otomatis setiap kali ada version commit.

### 7.2. Entitas — Master & Reference

#### 7.2.1. cost_product_type (CPT_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CPT_type_id | SERIAL | PK | |
| CPT_type_code | VARCHAR(5) | UNIQUE NOT NULL | "POY", "PTY", "TTY", dll |
| CPT_type_name | VARCHAR(100) | NOT NULL | Display name |
| CPT_is_active | BOOLEAN | DEFAULT true | |
| CPT_created_at | TIMESTAMPTZ | NOT NULL | |
| CPT_updated_at | TIMESTAMPTZ | NOT NULL | |

#### 7.2.2. cost_product_code_counter (CPCC_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CPCC_counter_id | SERIAL | PK | |
| CPCC_product_type_id | INT | FK cost_product_type, NOT NULL | |
| CPCC_year_month | VARCHAR(4) | NOT NULL | "2605" = Jun 2026 |
| CPCC_last_number | INT | NOT NULL DEFAULT 0 | Last used auto number |

UNIQUE constraint: (CPCC_product_type_id, CPCC_year_month).

#### 7.2.3. cost_rm_type (CRMT_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CRMT_type_id | SERIAL | PK | |
| CRMT_type_code | VARCHAR(30) | UNIQUE NOT NULL | User-definable |
| CRMT_type_name | VARCHAR(100) | NOT NULL | Display name |
| CRMT_reference_target | VARCHAR(10) | NOT NULL | PRODUCT / MASTER |
| CRMT_allow_sub_sequence | BOOLEAN | DEFAULT false | True untuk Multi Yarn-like types |
| CRMT_is_active | BOOLEAN | DEFAULT true | |
| CRMT_created_at | TIMESTAMPTZ | NOT NULL | |

Initial seed: STORE_RATE (MASTER), CAPTIVE_COST (PRODUCT), MULTI_YARN (PRODUCT, allow_sub_sequence=true), UNEVEN_PACK (PRODUCT).

### 7.3. Entitas — ERP Replica

Replicated from Oracle ERP. Read-only di costing. Sync via scheduled job (mechanism TBD).

#### 7.3.1. cost_erp_item (CEI_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CEI_item_id | BIGSERIAL | PK | Internal PK |
| CEI_item_code | VARCHAR(20) | UNIQUE NOT NULL | "PTY0000001" |
| CEI_item_name | VARCHAR(255) | | |
| CEI_item_type | VARCHAR(10) | | "POY", "PTY", dll |
| CEI_is_active | BOOLEAN | DEFAULT true | |
| CEI_synced_at | TIMESTAMPTZ | NOT NULL | Last sync timestamp |

#### 7.3.2. cost_erp_grade (CEG_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CEG_grade_id | SERIAL | PK | |
| CEG_grade_code | VARCHAR(20) | UNIQUE NOT NULL | "AX", "AM", "B", "C" |
| CEG_grade_name | VARCHAR(100) | | |
| CEG_is_active | BOOLEAN | DEFAULT true | |
| CEG_synced_at | TIMESTAMPTZ | NOT NULL | |

#### 7.3.3. cost_erp_shade (CES_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CES_shade_id | SERIAL | PK | |
| CES_shade_code | VARCHAR(20) | UNIQUE NOT NULL | "NL", "Z114S", "Z108S" |
| CES_shade_name | VARCHAR(100) | | |
| CES_is_active | BOOLEAN | DEFAULT true | |
| CES_synced_at | TIMESTAMPTZ | NOT NULL | |

Note: CES_ juga dipakai Phase A untuk autocomplete shade saat Marketing isi product spec. Phase A field CPS_shade_id → FK ke CES_shade_id.

### 7.4. Entitas — Product Master

#### 7.4.1. cost_product_master (CPM_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CPM_product_sys_id | BIGSERIAL | PK | Internal, immutable |
| CPM_product_code | VARCHAR(20) | UNIQUE NOT NULL | CSTPTY2605000001 (generated) |
| CPM_product_type_id | INT | FK cost_product_type, NOT NULL | |
| CPM_product_name | TEXT | NOT NULL | "PTY 150/36/RND/DSD/NI/DH/N/1/Z" |
| CPM_shade_code | VARCHAR(50) | | Free-text introductory |
| CPM_grade_code | VARCHAR(20) | NOT NULL DEFAULT 'AX' | Default top grade, editable |
| CPM_description | TEXT | | |
| CPM_erp_item_code | VARCHAR(20) | | Informational ("PTY0000001") |
| CPM_erp_grade_code_1 | VARCHAR(20) | | Informational ("AX") |
| CPM_erp_grade_code_2 | VARCHAR(20) | | Informational ("Z108S") |
| CPM_erp_linked_at | TIMESTAMPTZ | | |
| CPM_erp_linked_by | VARCHAR(64) | | |
| CPM_is_active | BOOLEAN | DEFAULT true | ACTIVE / INACTIVE |
| CPM_created_at | TIMESTAMPTZ | NOT NULL | |
| CPM_created_by | VARCHAR(64) | NOT NULL | |
| CPM_updated_at | TIMESTAMPTZ | NOT NULL | |
| CPM_updated_by | VARCHAR(64) | NOT NULL | |

Product code generation: atomic function menggunakan cost_product_code_counter. Format CST + CPT_type_code + YYMM + LPAD(auto,6,'0').

### 7.5. Entitas — Product Order & BOM

#### 7.5.1. cost_product_order (CPO_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CPO_order_id | BIGSERIAL | PK | |
| CPO_product_sys_id | BIGINT | FK cost_product_master, NOT NULL | WHAT product |
| CPO_cyl_type_id | INT | | FK master cyl_type (ERP) |
| CPO_current_version_id | BIGINT | FK cost_product_order_version | Set after first commit |
| CPO_is_active | BOOLEAN | DEFAULT true | Soft-delete |
| CPO_created_at | TIMESTAMPTZ | NOT NULL | |
| CPO_created_by | VARCHAR(64) | NOT NULL | |
| CPO_updated_at | TIMESTAMPTZ | NOT NULL | |
| CPO_updated_by | VARCHAR(64) | NOT NULL | |

Perubahan dari v1.2: field identity (product_code, item_code, shade_id) pindah ke cost_product_master. Product order sekarang = "BOM definition for a product" bukan "the product itself".

#### 7.5.2. cost_product_order_version (CPOV_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CPOV_version_id | BIGSERIAL | PK | |
| CPOV_order_id | BIGINT | FK cost_product_order, NOT NULL | |
| CPOV_version_no | INT | NOT NULL | Increment per order |
| CPOV_status | VARCHAR(20) | NOT NULL | draft / active / superseded |
| CPOV_effective_from | TIMESTAMPTZ | | Kapan version aktif |
| CPOV_effective_to | TIMESTAMPTZ | | Kapan superseded |
| CPOV_cycle_override | BOOLEAN | DEFAULT false | |
| CPOV_created_at | TIMESTAMPTZ | NOT NULL | |
| CPOV_created_by | VARCHAR(64) | NOT NULL | |

Constraints: UNIQUE (CPOV_order_id, CPOV_version_no). Partial unique index: max 1 active per order.

#### 7.5.3. cost_product_order_component (CPOC_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CPOC_component_id | BIGSERIAL | PK | |
| CPOC_version_id | BIGINT | FK cost_product_order_version, NOT NULL | |
| CPOC_sequence_no | INT | NOT NULL | |
| CPOC_sub_sequence | INT | | Untuk Multi Yarn-like types |
| CPOC_sub_type | VARCHAR(30) | | Yarn-Cap / Stores / PTY / REWINDING |
| CPOC_rm_type_id | INT | FK cost_rm_type, NOT NULL | User-definable RM type |
| CPOC_rm_product_sys_id | BIGINT | FK cost_product_master | Diisi jika reference_target = PRODUCT |
| CPOC_rm_master_item_id | BIGINT | FK cost_erp_item | Diisi jika reference_target = MASTER |
| CPOC_rm_description | VARCHAR(255) | | Fallback bila RM belum di-master |

CHECK constraint: exactly one of (rm_product_sys_id, rm_master_item_id) non-null, ATAU kedua null + rm_description non-null (fallback).

UNIQUE constraint: (CPOC_version_id, CPOC_sequence_no, CPOC_sub_sequence).

#### 7.5.4. cost_bom_layout (CBL_)

| **Column** | **Type** | **Constraint** | **Notes** |
|---|---|---|---|
| CBL_layout_id | BIGSERIAL | PK | |
| CBL_version_id | BIGINT | FK cost_product_order_version, NOT NULL | |
| CBL_node_ref_type | VARCHAR(10) | NOT NULL | PRODUCT / MASTER |
| CBL_node_ref_id | BIGINT | NOT NULL | |
| CBL_pos_x | INT | NOT NULL | Posisi horizontal (px) |
| CBL_pos_y | INT | NOT NULL | Posisi vertikal (px) |
| CBL_updated_at | TIMESTAMPTZ | NOT NULL | |

UNIQUE constraint: (CBL_version_id, CBL_node_ref_type, CBL_node_ref_id).

#### 7.5.5. cost_product_order_exploded (CPOE_) — Materialized View

| **Column** | **Type** | **Notes** |
|---|---|---|
| CPOE_root_product_sys_id | BIGINT | Product yang di-explode |
| CPOE_version_id | BIGINT | Version snapshot |
| CPOE_level | INT | 1 = direct child, 2 = grand-child, dst |
| CPOE_sequence_no | INT | |
| CPOE_sub_sequence | INT | |
| CPOE_sub_type | VARCHAR(30) | |
| CPOE_rm_type_id | INT | FK cost_rm_type |
| CPOE_rm_product_sys_id | BIGINT | |
| CPOE_rm_master_item_id | BIGINT | |
| CPOE_rm_description | VARCHAR(255) | |
| CPOE_sequence_path | TEXT | Jalur sequence untuk debugging |

View di-refresh via REFRESH MATERIALIZED VIEW CONCURRENTLY setelah version commit atau via scheduled daily job.

### 7.6. Relasi (ERD Summary)

```
[cost_product_type] 1:N [cost_product_master] 1:0..1 [cost_product_order]
                                                           │
[cost_product_code_counter] — utility untuk code generation │
                                                           │
                                              [cost_product_order_version]
                                                  │              │
                                                  1:N            1:N
                                                  │              │
                                    [cost_product_order_component]  [cost_bom_layout]
                                          │              │
                                  Dual FK │              │
                     ┌────────────────────┴──────────────┐
                     │                                   │
          CPOC_rm_product_sys_id              CPOC_rm_master_item_id
                     │                                   │
          [cost_product_master]                [cost_erp_item]
          (reference_target=PRODUCT)           (reference_target=MASTER)

[cost_rm_type] — master table, determines which FK to use

[cost_product_order_exploded] — materialized view dari recursive traversal

[cost_erp_grade], [cost_erp_shade] — ERP replica, supporting tables

[cost_audit_log] — cross-cutting, shared with Phase A
```

### 7.7. Integrasi Phase A → Phase B

Promote routing draft ke product order:

1. PIC di Phase A klik "Promote to Product Order".

2. Sistem buka dialog: search/create product master.
   - Search existing → select CPM_product_sys_id.
   - Create new → input product_type, product_name, shade_code → system generate product_code.

3. System create cost_product_order (CPO_) yang reference product master terpilih.

4. System copy cost_routing_draft_component (CRDC_) → cost_product_order_component (CPOC_):
   - CRDC_rm_ref_text → resolve ke CPOC_rm_product_sys_id atau CPOC_rm_master_item_id (atau fallback ke CPOC_rm_description).
   - CRDC_rm_type → match ke CRMT_type_id di cost_rm_type.

5. System set CRD_linked_product_order_id → CPO_order_id.

6. System set CRD_status → PROMOTED.

Seed fields dari Phase A:
- CRD_shade_code → CPM_shade_code (saat create new product master).
- CRD_raw_material_type → hint untuk komponen terdalam (informational, Engineering adjust).

### 7.8. Indeks Wajib

- cost_product_master: UNIQUE(CPM_product_code), INDEX(CPM_product_type_id), INDEX(CPM_erp_item_code) WHERE NOT NULL, GIN full-text search pada CPM_product_name.

- cost_product_order: INDEX(CPO_product_sys_id), INDEX(CPO_is_active) WHERE true.

- cost_product_order_version: INDEX(CPOV_order_id, CPOV_status), partial UNIQUE(CPOV_order_id) WHERE status='active'.

- cost_product_order_component: INDEX(CPOC_version_id, CPOC_sequence_no), INDEX(CPOC_rm_product_sys_id) WHERE NOT NULL, INDEX(CPOC_rm_master_item_id) WHERE NOT NULL, INDEX(CPOC_rm_type_id).

- cost_product_order_exploded: UNIQUE(root, version, level, sequence_path), INDEX(CPOE_rm_product_sys_id) WHERE NOT NULL, INDEX(CPOE_rm_master_item_id) WHERE NOT NULL.

- cost_bom_layout: UNIQUE(CBL_version_id, CBL_node_ref_type, CBL_node_ref_id).

### 7.9. Entitas — Parameter Master & Generic Master

#### 7.9.1. cost_parameter_master (CPRM_)

Definisi 125+ parameter. Single source of truth metadata parameter — engine, UI, dan validation semua merujuk ke tabel ini.

| **Column** | **Type** | **Notes** |
|---|---|---|
| CPRM_param_id | SERIAL PK | |
| CPRM_param_code | VARCHAR(50) UNIQUE | "DENIER", "YARN_TYPE" — immutable |
| CPRM_param_name | VARCHAR(200) | Human-readable |
| CPRM_description | TEXT | |
| CPRM_function_type | VARCHAR(20) | ENTRY / CALCULATION / LOOKUP / JSONB / NOT_USED |
| CPRM_data_type | VARCHAR(20) | NUMERIC / TEXT / FLAG / JSON |
| CPRM_unit_of_measure | VARCHAR(20) | kg, %, USD/kg |
| CPRM_owner_department | VARCHAR(30) | Engineering / Production / Finance / RND |
| CPRM_is_required_for_costing | BOOLEAN | Wajib diisi untuk dapat cost |
| CPRM_required_for_yarn_types | JSONB | ["PTY","TTY"] atau NULL = all yarn types |
| CPRM_is_period_dependent | BOOLEAN | false → CPP (Phase B) / true → CPPP (Phase C) |
| CPRM_formula_doc | TEXT | Human-readable formula documentation |
| CPRM_calc_function_key | VARCHAR(100) | Go function key, wajib untuk CALCULATION type |
| CPRM_lookup_master_code | VARCHAR(30) | FK ke CMD_master_code, wajib untuk LOOKUP type |
| CPRM_display_order | INT | Urutan tampil di form |
| CPRM_display_group | VARCHAR(50) | Spec / Machine / Grade / Packing / Cost / dll |
| CPRM_is_active | BOOLEAN | |

**Breakdown 125 parameter berdasarkan function type:**

| Function Type | Jumlah | Keterangan |
|---|---|---|
| ENTRY | 45 | Diisi manual (termasuk static specs) |
| CALCULATION | 41 | Dihitung Go engine |
| LOOKUP | 28 | Dari master data |
| JSONB | 14 | Struktur JSON complex (RM, MB) |
| NOT_USED | 6 | Placeholder, dikosongkan dulu |

**Static vs Dynamic split:**

| is_period_dependent | Jumlah | Storage |
|---|---|---|
| false | 72 | cost_product_parameter (Phase B) |
| true | 53 | cost_product_parameter_period (Phase C) |

Seed data lengkap di: `V003__phase_b_parameter_master.sql`

#### 7.9.2. cost_master_definition (CMD_)

Type registry untuk generic master pattern.

| **Column** | **Type** | **Notes** |
|---|---|---|
| CMD_master_id | SERIAL PK | |
| CMD_master_code | VARCHAR(30) UNIQUE | Immutable: "MACHINE", "BOX_BOBBIN_COST" |
| CMD_master_name | VARCHAR(100) | |
| CMD_description | TEXT | |
| CMD_attributes_schema | JSONB | Optional JSON schema untuk validasi |
| CMD_is_period_dependent | BOOLEAN | true = rows butuh period field |
| CMD_is_active | BOOLEAN | |

**8 master types initial:**

| Master Code | Period Dep? | Contoh atribut |
|---|---|---|
| MACHINE | No | power_per_day, manpower, overhead, spares |
| BOX_BOBBIN_COST | Yes | bobbin_rate, box_rate, weight_per_box |
| INTERMINGLING | Yes | cost_per_kg |
| PARAM_DATA | Yes | steam_cost, softner_cost, washing_cost |
| PRODUCT_GRADE | No | std_value_loss, bc_special |
| VOLUME_BUCKET | No | bucket_qty, machine_code |
| CHANGEOVER_LOSS | No | loss_kg, machine_code |
| YARN_TYPE | No | yarn_type_code, description |

#### 7.9.3. cost_master_data (CMSD_)

Actual master data rows. Attributes di JSONB.

| **Column** | **Type** | **Notes** |
|---|---|---|
| CMSD_data_id | BIGSERIAL PK | |
| CMSD_master_id | INT FK CMD | |
| CMSD_data_code | VARCHAR(100) | Instance identifier (machine code, pack code) |
| CMSD_data_name | VARCHAR(255) | |
| CMSD_period | VARCHAR(6) | "202605" — NULL untuk non-period master |
| CMSD_attributes | JSONB NOT NULL | Data aktual |
| CMSD_is_active | BOOLEAN | Soft delete |

UNIQUE (CMSD_master_id, CMSD_data_code, CMSD_period).
GIN index pada CMSD_attributes untuk query fleksibel.

#### 7.9.4. cost_product_parameter (CPP_)

Static parameter values per product. Untuk params dengan `CPRM_is_period_dependent = false`.

| **Column** | **Type** | **Notes** |
|---|---|---|
| CPP_value_id | BIGSERIAL PK | |
| CPP_product_sys_id | BIGINT FK CPM | |
| CPP_param_id | INT FK CPRM | |
| CPP_value_numeric | DECIMAL(20,6) | Untuk NUMERIC params |
| CPP_value_text | TEXT | Untuk TEXT params |
| CPP_value_flag | BOOLEAN | Untuk FLAG params |
| CPP_value_json | JSONB | Untuk JSON params |
| CPP_filled_at / by | TIMESTAMPTZ / VARCHAR | |

UNIQUE (CPP_product_sys_id, CPP_param_id).

Estimasi: 12.000 products × 72 static params = **~864.000 rows**.

#### 7.9.5. cost_parameter_dependency (CPRD_)

Dependency graph — visualization only. Auto-synced dari Go calc registry saat deployment. Read-only untuk user.

| **Column** | **Type** | **Notes** |
|---|---|---|
| CPRD_dep_id | BIGSERIAL PK | |
| CPRD_param_id | INT FK CPRM | Dependent param |
| CPRD_depends_on_param_id | INT FK CPRM | Upstream param |
| CPRD_synced_at | TIMESTAMPTZ | Last sync |
| CPRD_git_commit | VARCHAR(40) | Code version saat sync |

UNIQUE (CPRD_param_id, CPRD_depends_on_param_id). No self-dependency (CHECK constraint).

### 7.10. Prefix Registry (Update)

**Phase B lengkap — 17 tables:**

| Prefix | Table |
|---|---|
| CPT_ | cost_product_type |
| CPCC_ | cost_product_code_counter |
| CPM_ | cost_product_master |
| CRMT_ | cost_rm_type |
| CEI_ | cost_erp_item |
| CEG_ | cost_erp_grade |
| CES_ | cost_erp_shade |
| CPO_ | cost_product_order |
| CPOV_ | cost_product_order_version |
| CPOC_ | cost_product_order_component |
| CPOE_ | cost_product_order_exploded (matview) |
| CBL_ | cost_bom_layout |
| CPRM_ | cost_parameter_master |
| CMD_ | cost_master_definition |
| CMSD_ | cost_master_data |
| CPP_ | cost_product_parameter |
| CPRD_ | cost_parameter_dependency |

## 8. Excel Input Format (untuk Import)

### 8.1. Filosofi

Format Excel baru disederhanakan untuk match dengan model normalized: satu baris di Excel = satu komponen langsung. Sistem akan menyediakan converter dari format Excel lama ke format baru.

### 8.2. Struktur Sheet

| **Column** | **Type** | **Required** | **Description** |
|---|---|---|---|
| FG_PRODUCT_CODE | VARCHAR | Yes | Product code (CST format) atau legacy product_code |
| FG_ITEM_CODE | VARCHAR | Yes | Item code (harus ada di cost_erp_item) |
| FG_CYL_TYPE_CODE | VARCHAR | Yes | Kode cylinder type |
| FG_SHADE_CODE | VARCHAR | No | Shade code (free-text) |
| FG_GRADE_CODE | VARCHAR | No | Grade code (default AX jika kosong) |
| SEQUENCE_NO | INT | Yes | Urutan komponen |
| SUB_SEQUENCE | INT | No | Untuk multi-yarn blending |
| SUB_TYPE | VARCHAR | No | Yarn-Cap / Stores / PTY / REWINDING |
| RM_TYPE_CODE | VARCHAR | Yes | Code dari cost_rm_type |
| RM_REF_TYPE | VARCHAR | Yes | PRODUCT / MASTER (harus sesuai RM type reference_target) |
| RM_REF_CODE | VARCHAR | Yes | Product code (bila PRODUCT) atau ERP item code (bila MASTER) |
| RM_DESCRIPTION | VARCHAR | No | Fallback deskripsi |

### 8.3. Import Behavior

- Sistem auto-create product master record jika FG_PRODUCT_CODE belum ada (generate kode baru sesuai format CST).

- ERP linkage auto-fill jika FG_ITEM_CODE match di cost_erp_item.

- Cycle detection pada seluruh file sebelum commit.

## 9. Non-Functional Requirements

### 9.1. Performance

- BOM explosion single product (depth ≤ 10): response time < 2 detik.

- Where-used single item: response time < 2 detik.

- Search & list product order/master (paginated): response time < 1 detik untuk dataset ≤ 50.000 record.

- Refresh materialized view (concurrent): selesai < 30 detik untuk full refresh.

- Product master full-text search: response time < 500ms.

### 9.2. Scalability

- Mendukung minimal 50.000 product master, 50.000 product order, 250.000 komponen, 200.000 versions.

- Mendukung minimal 50 concurrent users.

### 9.3. Availability

- Target uptime: 99.5% jam kerja.

- Backup database otomatis harian; retensi minimal 30 hari.

### 9.4. Security

- Autentikasi via SSO/IAM eksisting.

- Otorisasi berbasis role (mapping role → permission di implementasi).

- Audit log atas semua operasi write.

- Database connection menggunakan TLS.

### 9.5. Platform & Form Factor

- Web app responsif (desktop primary).

- React front-end (konsisten dengan Phase A); React Flow untuk visual BOM editor.

- REST API back-end.

### 9.6. Internationalization

- UI Bahasa Indonesia sebagai default.

- Tanggal/waktu disimpan UTC, ditampilkan WIB.

## 10. Success Metrics

- 100% data Excel sumber berhasil di-import tanpa data loss.

- BOM explosion dan where-used selesai dalam < 5 detik di sistem.

- Process Engineering melaporkan reduksi waktu update BOM minimal 70%.

- Zero kasus inkonsistensi data antar produk yang share intermediate product.

- Product master menjadi single source of truth identitas product di costing (tidak ada spreadsheet parallel).

## 11. Risks & Mitigations

| **Risk** | **Severity** | **Mitigation** |
|---|---|---|
| Data error pada file Excel sumber | Medium | Dry-run import dengan validation report; review manual sebelum commit |
| Cycle tidak terdeteksi karena depth limit | Low-Med | Safe max depth 20; periodic full-graph cycle detection job |
| Materialized view stale | Medium | Manual refresh endpoint + scheduled daily safety net + monitoring last_refreshed_at |
| ERP master data tidak lengkap di replica | High | Validasi pre-import; force sync sebelum bulk operation; placeholder with flag |
| Polymorphic FK complexity | Medium | Dual column mutually-exclusive dengan CHECK constraint (sudah diimplementasi) |
| ERP sync failure (Oracle → PostgreSQL) | Medium | Retry mechanism; alert on sync failure; stale data indicator di UI |
| Product code counter race condition | Low | Atomic upsert via PostgreSQL ON CONFLICT + RETURNING (sudah diimplementasi) |
| Product master proliferation (terlalu banyak record) | Low-Med | Dedup check saat create (similar name + shade + type warning); periodic cleanup review |

## 12. Implementation Phasing (Suggested)

**Phase 1 — Foundation (MVP)**

- Schema database lengkap (semua tabel dengan prefix convention).
- cost_product_type, cost_rm_type seed data.
- ERP replica tables (cost_erp_item, cost_erp_grade, cost_erp_shade) + initial sync.
- Product master CRUD (FR-1a sampai FR-1d).
- Product order CRUD (FR-2 sampai FR-5).
- BOM component management (FR-6 sampai FR-8).
- Cycle detection.
- Import dari Excel + converter format lama.

**Phase 2 — Visualization & Reporting**

- BOM tree view (FR-9).
- Flow view (FR-10).
- Materialized view cost_product_order_exploded + auto-refresh.
- BOM explosion report (FR-11) dan where-used report (FR-12).
- Export ke Excel (FR-16).

**Phase 3 — Visual BOM Editor**

- Flow Editor drag-drop canvas (FR-18).
- Auto-layout & visual helpers (FR-19).
- cost_bom_layout persistence.
- Real-time cycle detection.

**Phase 4 — Versioning & Audit**

- Version history dan comparison view (FR-13, FR-14).
- Audit log UI (FR-17).

**Phase 5 — Costing Orchestration**

- Topological sort API (FR-20).
- Dependency resolution (FR-21).
- Batch orchestrator (FR-22) dan on-demand trigger (FR-23).
- Costing status dashboard (FR-24).
- Prasyarat: tabel external (parameter cost, master RM price, cost result) sudah tersedia.

**Phase 6 — Admin & Enhancements**

- RM type management UI (FR-26).
- ERP sync management UI (FR-27).
- Phase A → Phase B promote flow (Section 7.7).
- Minimap pada Flow Editor.

## 13. Open Questions

1. Apakah ada batasan maksimum depth BOM yang realistis? (Saat ini max RM_SEQUENCE = 13; safe default 20.)

2. Mekanisme integrasi ERP Oracle → PostgreSQL: CDC (Change Data Capture), DB link, scheduled dump, atau API? Mempengaruhi freshness data replica.

3. Untuk 276 baris Multi Yarn dengan RM_ITEM_CODE NULL: strategi final melengkapi master item atau "description-only RM" sebagai first-class concept?

4. Calculation engine eksternal: REST API, message queue, atau direct database write?

5. Period definition di tabel parameter cost dan cost result: bulanan, kuartalan, atau effective-dated?

6. Saat version BOM berubah di tengah period, cost result version lama tetap valid sampai tutup buku, atau perlu re-calculate?

7. Product type list (POY, PTY, TTY, TTS, ATY, ITY, TCH, TTM) — sudah final atau ada tipe lain?

8. Cylinder type master: tetap di ERP (butuh replica table juga) atau dikelola di costing system?

## 14. Appendix

### 14.1. Mapping Field Excel Lama → Schema Baru

| **Excel Lama** | **Skema Baru** | **Status** |
|---|---|---|
| FG_ITEM_CODE | CPM_erp_item_code (informational) | Kept as attribute |
| FG_TOP_2 | CPM_product_code (new format CST) | Remapped |
| FG_ITEM_NAME | CPM_product_name | Kept |
| FG_CYL_TYPE | CPO_cyl_type_id | Kept (FK) |
| FG_SHADE_CODE | CPM_shade_code (free-text) | Kept |
| FG_LEFT_NO | CPM_product_sys_id | Re-generated |
| FG_SEQUENCE | — | Dropped (derived) |
| RM_ITEM_CODE | CEI_item_code (via FK) | Resolved |
| RM_TOP_2 | (via CPOC_rm_product_sys_id) | Resolved via FK |
| RM_LEFT_NO | CPOC_rm_product_sys_id / CPOC_rm_master_item_id | Resolved (dual FK) |
| RM_SEQUENCE | CPOC_sequence_no | Kept |
| RM_TYPE | CPOC_rm_type_id (FK cost_rm_type) | Kept (now FK) |
| RM_SUB_SEQUENCE | CPOC_sub_sequence | Kept |
| RM_SUB_TYPE | CPOC_sub_type | Kept |
| RM_LEFT_NO_INTO | — | Dropped (redundant) |
| RM_SUB_SEQUENCE_REAL | — | Dropped (redundant) |

### 14.2. Product Code Format

```
CST  +  PTY  +  2605  +  000001
│       │       │        │
│       │       │        └── auto-increment 6 digit per (type + YYMM)
│       │       └── YYMM (tahun-bulan pembuatan)
│       └── product type code (3 char, dari cost_product_type)
└── module prefix (costing)

Contoh:
CSTPTY2605000001 - PTY 150/36/RND/DSD/NI/DH/N/1/Z - Z108S
CSTPTY2605000002 - PTY 150/36/RND/DSD/NI/DH/N/1/Z - Z114S
CSTPOY2605000001 - POY 150/36 SD                    - NL
```

### 14.3. Glossary Singkatan

- BOM — Bill of Materials
- CDC — Change Data Capture
- CTE — Common Table Expression (SQL)
- DAG — Directed Acyclic Graph
- ERP — Enterprise Resource Planning
- FG — Finished Goods
- FK — Foreign Key
- HPP — Harga Pokok Produksi
- IAM — Identity & Access Management
- MVP — Minimum Viable Product
- PK — Primary Key
- PRD — Product Requirements Document
- RM — Raw Material
- SLA — Service Level Agreement
- SSO — Single Sign-On

### 14.4. Document Revision History

| **Version** | **Date** | **Description** | **Author** |
|---|---|---|---|
| 1.0 | May 2026 | Initial draft | — |
| 1.1 | May 2026 | Added FR-9 Flow View, FR-17 Flow Editor, FR-18 Auto-Layout | — |
| 1.2 | May 2026 | Added Section 6.7 Costing Orchestration (FR-19 sampai FR-24) | — |
| 1.3 | May 2026 | Product Master (CPM), Product Type (CPT), RM Type master (CRMT), ERP replica tables, Column Prefix Convention | — |
| 1.4 | May 2026 | Parameter Master (CPRM), Generic Master Pattern (CMD+CMSD), Static Params (CPP), Dependency Graph (CPRD), FR-28 s/d FR-41 | — |
