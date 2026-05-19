---
title: "PRD — Costing Workflow Suite, Phase B: Product Order & BOM Management"
version: "1.2"
status: "Draft"
phase: "B"
last_updated: "2026-05"
author: "[IT Leader]"
related:
  - "PRD_PhaseA_ProductRequest.md"
  - "ERD_Master.md"
  - "GLOSSARY.md"
---

# PRD — Phase B: Product Order & BOM Management System
## Costing Workflow Suite

> *Sistem Pengelolaan Urutan Proses Produksi (Material to Finished Goods)*
> Version 1.2 — Draft | May 2026

---

## 1. Executive Summary

Dokumen ini mendefinisikan kebutuhan untuk membangun sistem pengelolaan Product Order — sebuah aplikasi internal yang menyimpan dan mengelola urutan proses produksi dari raw material awal hingga finished goods. Setiap produk dapat melalui satu hingga lebih dari tujuh tahap produksi, dimana output suatu tahap dapat menjadi input bagi tahap berikutnya (multi-level Bill of Materials).

Tujuan utama sistem ini adalah:

- Menjadi single source of truth struktur BOM (Bill of Materials) multi-level untuk seluruh produk perusahaan.

- Menghilangkan ketergantungan pada file Excel manual yang rawan duplikasi data dan sulit di-update.

- Menyediakan visualisasi tree produk dan reporting BOM explosion / where-used yang cepat dan akurat.

- Mempertahankan historical traceability ketika struktur BOM berubah (versioning).

Sistem ini berfokus pada struktur dan urutan proses (process sequence) saja. Aspek quantity, costing, inventory, dan production scheduling berada di luar scope dan diasumsikan ditangani sistem lain.

## 2. Background & Problem Statement

### 2.1. Konteks Bisnis

Perusahaan memproduksi beragam jenis yarn (POY, PTY, TTY, ACY, MELANGE, dll.) yang dihasilkan melalui rangkaian proses produksi berjenjang. Sebuah finished goods seperti TCM0000001 (varian shade tertentu) dapat dihasilkan melalui lima atau lebih tahap berurutan, dimana setiap tahap mengubah material dari bentuk satu ke bentuk berikutnya:

Chips BRT → POY0000458 → PTY0001531 → PTY0001532 → TCY0000061 → TCM0000001

Output dari setiap tahap adalah finished goods bagi tahap tersebut, sekaligus menjadi raw material bagi tahap selanjutnya. Karena satu intermediate product (misalnya TCY0000061) dapat dipakai oleh banyak product hilir, struktur datanya bersifat directed acyclic graph (DAG), bukan tree sederhana.

### 2.2. Kondisi Saat Ini

Saat ini mapping urutan proses dikelola dalam file Excel berisi 31.012 baris pasangan FG-RM. Sebuah finished goods muncul beberapa kali dengan urutan komponen di-flatten dari komponen terjauh (chips) hingga ke dirinya sendiri.

Beberapa masalah dari pendekatan saat ini:

- Data redundan — struktur flatten menyebabkan satu intermediate product dicatat berulang kali di setiap product hilir yang memakainya.

- Update sulit — perubahan komponen di satu intermediate product mengharuskan update di banyak baris pada banyak produk hilir; rawan inkonsistensi.

- Tidak ada versioning — perubahan struktur menghilangkan jejak struktur sebelumnya, menyulitkan audit produksi historis.

- Visualisasi & analisis terbatas — sulit menjawab pertanyaan seperti "semua produk yang memakai PTY0001531" atau "berapa total stage proses untuk TCM0000001" tanpa pengolahan manual.

- Tidak ada validasi struktural — potensi terjadinya cycle (BOM lingkar) tidak dideteksi sistem.

### 2.3. Stakeholders

| **Stakeholder**        | **Kebutuhan Utama**                                   | **Akses Tipikal**            |
|------------------------|-------------------------------------------------------|------------------------------|
| Production Planning    | Membaca BOM untuk perencanaan produksi                | Read-only, BOM explosion     |
| Process Engineering    | Memelihara struktur BOM, mendefinisikan urutan proses | Create, Read, Update, Delete |
| Cost Accounting        | Membaca struktur captive cost untuk perhitungan HPP   | Read-only, Where-used        |
| Master Data Management | Memelihara konsistensi data master & integrasi        | Admin, Import/Export         |
| Auditor (Internal)     | Verifikasi historis struktur produksi                 | Read-only, Version history   |

## 3. Goals & Non-Goals

### 3.1. Goals (In-Scope)

1.  Menyimpan struktur BOM multi-level untuk seluruh finished goods variant secara normalized (1 record = 1 komponen langsung).

2.  Menyediakan CRUD untuk product order, version, dan komponennya melalui antarmuka pengguna.

3.  Menyediakan visualisasi BOM tree (vertical hierarchical) dan flow view (horizontal DAG) yang dapat di-expand multi-level.

4.  Menyediakan editor BOM visual berbasis drag-and-drop canvas yang memungkinkan user meng-compose struktur BOM secara grafis, mendukung convergent (many-to-one) dan divergent (one-to-many) flow.

5.  Menyediakan report BOM explosion (drill-down semua komponen dari finished goods ke raw material terbawah).

6.  Menyediakan report where-used (drill-up: produk apa saja yang memakai suatu item).

7.  Mempertahankan versioning lengkap atas perubahan BOM (setiap save = versi baru, versi lama tetap accessible).

8.  Mendeteksi dan memberi peringatan bila terjadi struktur cycle (FG memakai dirinya sendiri sebagai RM, langsung maupun tidak langsung).

9.  Menyediakan fasilitas import data awal dari file Excel format baku.

10. Mengintegrasikan dengan master data eksternal (item, cylinder type, shade) melalui foreign key.

11. Menjadi costing orchestrator: menyediakan topological sort dan dependency resolution untuk calculation engine eksternal, dengan dukungan batch (per period) maupun on-demand.

### 3.2. Non-Goals (Out-of-Scope)

- Quantity / consumption rate per komponen (berapa kg/meter RM untuk 1 unit FG).

- Formula kalkulasi cost (weighted, yield factor, loss percentage) — dilakukan oleh calculation engine eksternal.

- Penyimpanan parameter cost & cost result — disimpan di tabel eksternal.

- Inventory management, stok, dan transaksi gudang.

- Production scheduling, work order, MRP.

- Master data untuk item, cylinder type, shade — diasumsikan sudah ada dan tidak diduplikasi.

- User & role management — diambil dari SSO/IAM eksisting.

- Approval workflow — perubahan langsung berlaku setelah disimpan (audit trail tersedia).

## 4. Key Concepts & Terminology

| **Term**         | **Definition**                                                                                                                                                        |
|------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Product Order    | Definisi struktur BOM untuk satu finished goods variant — entity utama sistem ini. Setiap product order memiliki kode unik (eks. FG_TOP_2).                           |
| Component        | Satu raw material atau intermediate product yang masuk sebagai komponen langsung sebuah product order.                                                                |
| RM Type          | Klasifikasi komponen: Store Rate (dibeli dari luar/master), Captive Cost (diproduksi sendiri di tahap sebelumnya), Multi Yarn (blending banyak yarn), Uneven Packing. |
| Single-Level BOM | Daftar komponen langsung satu product order (parent → direct children saja).                                                                                          |
| Multi-Level BOM  | Drill-down rekursif dari finished goods hingga raw material terbawah (intermediate product di-expand otomatis).                                                       |
| BOM Explosion    | Operasi traversal turun (parent → children → grand-children) untuk mendapatkan multi-level BOM.                                                                       |
| Where-Used       | Operasi traversal naik (child → parents → grand-parents) untuk mengetahui semua produk yang memakai suatu item.                                                       |
| Version          | Snapshot struktur BOM pada satu titik waktu. Setiap perubahan menghasilkan version baru; version lama menjadi superseded namun tetap dapat ditelusuri.                |
| Cycle            | Kondisi error dimana sebuah product order, langsung atau melalui rantai komponen, menggunakan dirinya sendiri sebagai komponen.                                       |

## 5. Assumptions & Dependencies

12. Master data sudah tersedia: tabel master item, cylinder type, shade, beserta sys_id-nya.

13. Database menggunakan PostgreSQL (versi 14+ untuk dukungan recursive CTE yang optimal).

14. SSO/IAM eksisting menyediakan identitas user dan role; sistem ini mempercayai token/session yang diberikan.

15. Struktur BOM yang ada di file Excel sumber diperlakukan sebagai inisial data dan akan diimport satu kali.

16. Field RM_LEFT_NO_INTO di Excel sumber dianggap redundan (selalu sama dengan FG_LEFT_NO record itu sendiri); 156 baris anomali diperlakukan sebagai data error dan akan di-normalisasi saat import.

17. Field FG_SEQUENCE di Excel sumber dianggap derivative dari cylinder type (1:1 mapping); tidak disimpan sebagai kolom terpisah.

18. Field RM_SUB_SEQUENCE_REAL di Excel sumber adalah invers dari RM_SEQUENCE; salah satu redundan, hanya RM_SEQUENCE yang akan disimpan.

## 6. Functional Requirements

### 6.1. Product Order Management (CRUD)

**FR-1: Create Product Order**

- User dapat membuat product order baru dengan mengisi: product code (top_2), item code (FK master), cylinder type (FK master), shade code/name (FK master, optional).

- Sistem otomatis generate sys_id (PK numerik); user tidak menentukan sys_id manual.

- Saat create, sistem otomatis membuat version pertama dengan status draft.

- Validasi: product code (top_2) harus unik secara global.

**FR-2: Read / Search Product Order**

- User dapat mencari product order berdasarkan: kode (partial match), item code, cylinder type, shade.

- Hasil pencarian dapat di-filter, di-sort, dan dipaginasi.

- User dapat melihat detail product order: atribut, daftar komponen langsung pada version aktif, history version.

**FR-3: Update Product Order**

- Update terhadap atribut (item code, cyl type, shade) langsung berlaku tanpa membuat version baru.

- Update terhadap struktur komponen (BOM lines) selalu membuat version baru: version aktif sebelumnya menjadi superseded.

- Tidak diperbolehkan menghapus atau mengubah version yang sudah superseded — hanya read-only.

**FR-4: Delete Product Order**

- Soft-delete: status berubah menjadi inactive, record tetap ada untuk historical traceability.

- Tidak diperbolehkan men-delete product order yang sedang dipakai sebagai komponen di product order lain yang masih aktif — sistem menampilkan daftar dependency dan menolak operasi.

### 6.2. BOM Component Management

**FR-5: Add Component**

- Pada saat editing version draft, user dapat menambah komponen dengan input: RM sys_id (autocomplete dari master item ATAU product order lain), RM type, sequence_no, dan optional sub_sequence + sub_type untuk multi-yarn blending.

- Bila RM type = Captive Cost, RM sys_id harus mengarah ke product order lain yang sudah ada di sistem.

- Bila RM type = Store Rate, RM sys_id harus mengarah ke master item eksternal.

- Bila RM type = Multi Yarn, satu sequence_no boleh memiliki beberapa baris dengan sub_sequence berbeda.

**FR-6: Reorder / Edit / Remove Component**

- User dapat mengubah sequence_no, mengganti RM, atau menghapus komponen — semua hanya pada version draft.

- Perubahan tersimpan saat user men-commit version draft menjadi active (membuat version baru).

**FR-7: Cycle Detection**

- Sebelum commit version baru, sistem menjalankan traversal komponen Captive Cost untuk mendeteksi cycle (langsung atau tidak langsung).

- Bila cycle terdeteksi, sistem menampilkan warning lengkap dengan jalur cycle dan meminta konfirmasi user.

- User dapat memilih untuk tetap menyimpan (override) atau membatalkan. Bila override, sistem mencatat tanda cycle_override pada version tersebut.

### 6.3. Visualization

**FR-8: BOM Tree View**

- Tampilan tree expand/collapse dari finished goods ke bawah hingga raw material terbawah.

- Setiap node menampilkan: kode product/RM, nama, tipe (FG/Intermediate/Raw Material), RM type.

- Node Captive Cost dapat di-expand karena mengarah ke product order lain; node Store Rate adalah leaf (tidak bisa di-expand).

- User dapat klik node Captive Cost untuk navigasi ke product order tersebut sebagai context baru.

**FR-9: BOM Graph View (Flow View)**

- Visualisasi horizontal node-graph (mirip flow diagram industri) untuk product dengan convergent flow — banyak raw material bertemu di satu finished goods, atau satu raw material berkembang ke banyak intermediate product.

- Node disusun secara horizontal per stage (Stage 1 di paling kiri sebagai chips/raw material; FG di paling kanan).

- Edge berupa kurva Bezier ber-arrow yang menunjukkan arah aliran material dari komponen ke product.

- Mendukung kasus convergent (many-to-one, mis. Multi Yarn blending) dan divergent (one-to-many, mis. satu intermediate dipakai oleh banyak FG).

- Mode read-only: user dapat pan, zoom, dan klik node untuk highlight; tidak ada modifikasi struktur di mode ini.

- Stage labels otomatis berdasarkan cyl_type / sequence_no komponen.

- Tersedia tombol "Edit di Flow Editor" untuk berpindah ke mode editing (FR-17).

**FR-17: Flow Editor (Drag-Drop Visual BOM Builder)**

Editor visual yang memungkinkan user membangun atau memodifikasi struktur BOM dengan drag-and-drop pada canvas, mirip Lucidchart / n8n / Figma. Ini adalah cara alternatif (selain form-based FR-1 sampai FR-6) untuk meng-compose product order beserta komponennya.

Komponen UI:

- Panel kiri (Item Library / Palette): daftar item yang dapat di-drag ke canvas, terbagi dalam kategori Raw Material (Store Rate), Intermediate / Captive (product order yang sudah ada), dan node types khusus (Empty FG, Multi Yarn group).

- Canvas tengah (Editor Surface): area kerja dengan grid background, snap-to-grid, pan/zoom, dan fit-to-screen.

- Panel kanan (Inspector): menampilkan atribut node atau edge yang sedang dipilih; user dapat edit item code, cyl type, shade, rm_type, sequence di sini.

- Toolbar atas: undo/redo, auto-layout, align-stages, zoom controls, snap toggle, validate, save draft, commit.

Interaksi pokok:

19. Drag item dari Palette ke Canvas → membuat node baru pada posisi drop.

20. Drag dari port output (kanan) sebuah node ke port input (kiri) node lain → membuat edge / koneksi (komponen relationship).

21. Klik node → select, atribut tampil di Inspector. Klik lagi di area kosong → deselect.

22. Drag node (selain port) → reposisi; canvas mendukung snap-to-grid (20px default).

23. Delete key pada node terpilih → hapus node (dengan konfirmasi bila punya koneksi).

24. Tombol Validate → jalankan cycle detection dan referensi master data; menampilkan jalur cycle bila ditemukan.

25. Tombol Commit version → simpan canvas sebagai version baru product order.

Mapping canvas ke schema:

- Setiap node mewakili satu product order (existing atau new); position (x, y) disimpan di metadata version untuk preserve layout antar sesi.

- Setiap edge mewakili satu baris di product_order_component (parent product order = node tujuan edge; rm_ref = node sumber edge).

- Convergent flow (banyak edge masuk ke 1 node) otomatis di-mapping sebagai komponen multiple dengan rm_type Captive Cost atau Multi Yarn (sub_sequence terisi otomatis berurutan).

Persistensi layout:

- Tambahkan tabel pendukung product_order_version_layout (version_id, node_id, x, y) untuk menyimpan posisi visual.

- Bila version dibuka tanpa metadata layout (mis. hasil import Excel), sistem menjalankan auto-layout algorithm (dagre / elk) untuk menghasilkan layout default berbasis stage.

**FR-18: Auto-Layout & Visual Helpers**

- Auto-layout: tombol untuk menghitung ulang posisi semua node menggunakan hierarchical layout algorithm — node disusun per stage (kolom) berdasarkan cyl_type / sequence_no, dengan node convergent diletakkan center-vertical relatif terhadap input-nya.

- Snap-to-grid (toggle): posisi node dibulatkan ke grid 20px terdekat.

- Align-stages: paksa semua node dengan stage yang sama berada di kolom x yang sama.

- Minimap (opsional, Phase 2): peta thumbnail di pojok canvas untuk navigasi cepat pada graph besar.

### 6.4. Reporting

**FR-10: BOM Explosion Report**

- Input: satu atau lebih product order code; opsional: max depth, filter RM type.

- Output: daftar lengkap semua komponen multi-level beserta level kedalaman (1 = komponen langsung, 2 = komponen dari komponen, dst).

- Format hasil: tabel pada layar, exportable ke Excel/CSV.

- Performa target: explosion 1 product (max depth 10) selesai dalam \< 2 detik.

**FR-11: Where-Used Report**

- Input: satu item (item code dari master ATAU product order code) — sistem mendukung kedua tipe input.

- Output: daftar lengkap product order yang memakai item tersebut sebagai komponen, langsung maupun tidak langsung, beserta level.

- Format hasil sama seperti FR-10.

### 6.5. Version Management

**FR-12: View Version History**

- Pada halaman detail product order, terdapat tab Version History menampilkan: nomor version, status (draft/active/superseded), tanggal create, user, jumlah komponen.

- User dapat klik version untuk melihat snapshot struktur BOM pada version tersebut (read-only).

**FR-13: Compare Versions**

- User dapat memilih dua version dan melihat diff: komponen yang ditambah, dihapus, atau diubah.

### 6.6. Data Import / Export

**FR-14: Initial Import dari Excel**

- Sistem menyediakan fasilitas import dari file Excel format baku (didefinisikan di Section 8).

- Import bersifat upsert: bila product order sudah ada, akan dibuatkan version baru; bila belum ada, dibuat record baru.

- Sistem menampilkan preview validasi sebelum commit (count rows, error rows, summary).

- Setiap import dicatat sebagai import job dengan log lengkap.

**FR-15: Export ke Excel**

- User dapat mengexport: single product order (full BOM exploded), multiple product order (hasil filter), atau dump seluruh database.

### 6.7. Costing Orchestration

Sistem ini berperan sebagai costing orchestrator: menyediakan urutan eksekusi (topological sort), resolusi dependency komponen, dan integrasi dengan engine eksternal yang melakukan kalkulasi sebenarnya. Sistem ini tidak menyimpan parameter cost maupun cost result; keduanya berada di tabel eksternal (lihat Section 5 Assumptions). Formula kalkulasi cost (apakah weighted, ada yield factor, dan sebagainya) ditangani oleh calculation engine eksternal di luar scope dokumen ini.

**FR-19: Topological Sort untuk Cost Rollup**

- Sistem menyediakan API yang menerima product_sys_id dan version_id (opsional, default current_version), lalu mengembalikan urutan node yang harus di-cost — dari level terdalam (raw material) ke FG itu sendiri.

- Urutan ini di-derive dari product_order_exploded dengan ORDER BY level DESC, di-dedup berdasarkan (rm_ref_type, rm_ref_id) sehingga node yang muncul di banyak path hanya dihitung sekali.

- Output API berisi: ordinal, product_sys_id, version_id, level, jumlah komponen langsung, daftar referensi komponen (rm_ref_type, rm_ref_id) yang dibutuhkan untuk menghitung node ini.

**FR-20: Dependency Resolution per Node**

- Untuk setiap node N dalam urutan kalkulasi, sistem menyediakan daftar komponen yang dibutuhkan: dari product_order_component WHERE version_id = active_version_of(N).

- Setiap komponen disertai klasifikasi (rm_ref_type = MASTER atau PRODUCT), sub_sequence (bila Multi Yarn), dan sub_type.

- Sistem tidak mengambil cost-nya sendiri; consumer (calculation engine) yang akan query master price atau cost result period sebelumnya berdasarkan klasifikasi tersebut.

**FR-21: Batch Costing Orchestrator**

- Mendukung trigger batch (per period) yang menghitung cost untuk seluruh product_order aktif dalam satu kali eksekusi.

- Orchestrator menjalankan: (a) gather semua FG product yang harus di-cost, (b) untuk setiap FG, panggil FR-19 dan FR-20, (c) panggil calculation engine eksternal, (d) tulis hasil ke tabel cost result eksternal.

- Bila parameter cost untuk suatu product tidak tersedia di period tersebut, orchestrator melewati product tersebut dan mencatat ke error log. Eksekusi tetap berlanjut untuk product lainnya — semua product yang bisa dihitung akan dihitung; report failed entries di akhir batch.

- Eksekusi dilakukan dengan menghormati dependency: node level terdalam dihitung lebih dulu sehingga ketika node level atasnya dihitung, cost komponen captive cost sudah tersedia di period yang sama.

**FR-22: On-Demand Costing**

- User dapat memilih product order dan period, lalu trigger kalkulasi cost untuk product tersebut saja (atau termasuk dependency chain bila belum ada hasil di period tersebut).

- Sebelum eksekusi, sistem menampilkan preview: berapa node yang akan dihitung, mana yang sudah ada cost (reuse) dan mana yang akan dihitung baru.

- Hasil on-demand menimpa cost result period yang sama (overwrite); audit log mencatat user dan timestamp.

**FR-23: Costing Status Dashboard**

- Menampilkan untuk period berjalan: total product, sudah di-cost, gagal di-cost (dengan alasan), pending.

- Drill-down per product: status, last calculated, link ke detail error bila gagal.

- Menjadi dasar bagi cost accounting tim untuk monitoring tutup buku.

**FR-24: External Dependencies Contract**

Sistem ini bergantung pada tiga tabel eksternal yang spesifikasinya akan didetailkan di dokumen integrasi terpisah. Untuk PRD ini, kontrak minimal yang harus disepakati:

| **Tabel**                     | **Pemilik** | **Operasi sistem ini**                                                                                                                                                     | **Konteks pemakaian**                                            |
|-------------------------------|-------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------------------------------------------|
| Master raw material price     | TBD         | Lookup cost per master_item_id di period tertentu. Sistem ini hanya read.                                                                                                  | Saat node berikutnya butuh cost komponen rm_ref_type = MASTER    |
| Parameter cost (process cost) | TBD         | Lookup parameter cost per product_sys_id di period tertentu. Sistem ini hanya read.                                                                                        | Input bagi calculation engine untuk menghitung process cost node |
| Cost result                   | TBD         | Tabel hasil kalkulasi cost per (product_sys_id, version_id, period). Sistem ini insert/update; calculation engine yang menulis isinya, atau orchestrator atas nama engine. | Output kalkulasi; jadi input bagi node level atasnya saat rollup |

Bila tabel-tabel di atas belum tersedia pada saat implementasi Phase 1–4, fitur costing (FR-19 sampai FR-23) ditunda ke phase setelah tabel-tabel tersebut siap.

### 6.8. Audit Trail

**FR-16: Activity Log**

- Setiap operasi write (create, update, delete, version commit, import) tercatat dengan: timestamp, user ID, operasi, entity, before-value, after-value (untuk update).

- Log dapat dilihat oleh user dengan role appropriate (sesuai SSO/IAM).

## 7. Data Model

### 7.1. Pendekatan: Hybrid Storage

Storage utama menggunakan model normalized single-level BOM (mengikuti praktik industri ERP). Untuk mendukung query BOM explosion yang sering dan cepat, sistem menyediakan materialized view yang berisi struktur exploded (flatten) — di-refresh otomatis setiap kali ada perubahan version yang di-commit menjadi active.

Pendekatan ini memberikan keuntungan: (1) update sederhana dan single source of truth, (2) where-used cepat karena natural pada normalized table, (3) BOM explosion juga cepat karena dilayani materialized view.

### 7.2. Entitas Utama

#### 7.2.1. product_order

Tabel utama yang menyimpan satu record per FG variant. Mengganti konsep FG_TOP_2 di file Excel sumber.

| **Column**             | **Type**              | **Constraint**                | **Notes**                                              |
|------------------------|-----------------------|-------------------------------|--------------------------------------------------------|
| product_sys_id         | BIGSERIAL             | PK                            | Pengganti FG_LEFT_NO; di-generate oleh sistem          |
| product_code           | VARCHAR(100)          | UNIQUE NOT NULL               | Pengganti FG_TOP_2 (mis. TCM0000001-6378-01-MEEREBAH)  |
| item_code              | VARCHAR(50)           | FK master item                | Pengganti FG_ITEM_CODE                                 |
| cyl_type_id            | INT                   | FK master cyl_type            | Pengganti FG_CYL_TYPE; FG_SEQUENCE = derived dari sini |
| shade_id               | INT                   | FK master shade, NULL allowed | Pengganti FG_SHADE_CODE/NAME                           |
| current_version_id     | BIGINT                | FK product_order_version      | Version yang aktif saat ini                            |
| status                 | VARCHAR(20)           | NOT NULL                      | active / inactive (soft-delete)                        |
| created_by, created_at | VARCHAR / TIMESTAMPTZ | NOT NULL                      | Audit fields                                           |
| updated_by, updated_at | VARCHAR / TIMESTAMPTZ | NOT NULL                      | Audit fields                                           |

#### 7.2.2. product_order_version

Snapshot struktur BOM pada satu titik waktu. Setiap commit perubahan komponen menghasilkan version baru.

| **Column**             | **Type**              | **Constraint**            | **Notes**                                           |
|------------------------|-----------------------|---------------------------|-----------------------------------------------------|
| version_id             | BIGSERIAL             | PK                        | Identifier unique                                   |
| product_sys_id         | BIGINT                | FK product_order NOT NULL | Owner product order                                 |
| version_no             | INT                   | NOT NULL                  | Increment per product (1, 2, 3, ...)                |
| status                 | VARCHAR(20)           | NOT NULL                  | draft / active / superseded                         |
| effective_from         | TIMESTAMPTZ           | NOT NULL                  | Kapan version mulai aktif                           |
| effective_to           | TIMESTAMPTZ           | NULL allowed              | Kapan version berhenti aktif (NULL = masih berlaku) |
| cycle_override         | BOOLEAN               | DEFAULT false             | True bila user override cycle warning               |
| created_by, created_at | VARCHAR / TIMESTAMPTZ | NOT NULL                  | Audit fields                                        |

Constraint tambahan: UNIQUE (product_sys_id, version_no). Hanya satu version dengan status = active per product_sys_id (partial unique index).

#### 7.2.3. product_order_component

Daftar komponen langsung sebuah version. Inilah representasi normalized single-level BOM.

| **Column**     | **Type**     | **Constraint**                    | **Notes**                                                                           |
|----------------|--------------|-----------------------------------|-------------------------------------------------------------------------------------|
| component_id   | BIGSERIAL    | PK                                | Identifier unique                                                                   |
| version_id     | BIGINT       | FK product_order_version NOT NULL | Parent version                                                                      |
| sequence_no    | INT          | NOT NULL                          | Urutan proses di dalam version (1 = paling awal)                                    |
| sub_sequence   | INT          | NULL allowed                      | Untuk multi-yarn blending (NULL = single component)                                 |
| sub_type       | VARCHAR(30)  | NULL allowed                      | Yarn-Cap / Stores / PTY / REWINDING                                                 |
| rm_type        | VARCHAR(30)  | NOT NULL                          | Store Rate / Captive Cost / Multi Yarn / Uneven Packing                             |
| rm_ref_type    | VARCHAR(10)  | NOT NULL                          | PRODUCT (FK ke product_order) atau MASTER (FK ke master item)                       |
| rm_ref_id      | BIGINT       | NOT NULL                          | Polymorphic FK: ke product_order.product_sys_id ATAU master item id                 |
| rm_description | VARCHAR(255) | NULL allowed                      | Deskripsi fallback untuk Multi Yarn yang RM-nya belum ada di master (lihat catatan) |

Catatan implementasi polymorphic FK: bisa dipisah menjadi dua kolom (rm_product_sys_id, rm_master_item_id) yang mutually exclusive (CHECK constraint) untuk dapat menggunakan FK constraint database. Pendekatan ini direkomendasikan.

Catatan rm_description: 276 baris di data sumber memiliki RM_TYPE = Multi Yarn dengan RM_ITEM_CODE NULL — hanya RM_TOP_2 (deskripsi text) yang tersedia. Untuk akomodasi ini, kolom rm_description menampung deskripsi text bila item belum ada di master. Best practice: master item dilengkapi terlebih dahulu sehingga rm_ref_id selalu terisi.

#### 7.2.4. product_order_exploded (materialized view)

Materialized view yang berisi hasil flatten/explode rekursif. Setiap baris merepresentasikan satu komponen multi-level dari sebuah product order.

| **Column**                      | **Type** | **Constraint**           | **Notes**                                         |
|---------------------------------|----------|--------------------------|---------------------------------------------------|
| product_sys_id                  | BIGINT   | FK product_order         | Product order yang di-explode                     |
| version_id                      | BIGINT   | FK product_order_version | Version snapshot                                  |
| level                           | INT      | —                        | Kedalaman: 1 = direct child, 2 = grand-child, dst |
| sequence_path                   | TEXT     | —                        | Jalur sequence (mis. 1 → 2 → 1) untuk debugging   |
| rm_ref_type, rm_ref_id          | —        | —                        | Sama seperti di product_order_component           |
| rm_type, sub_sequence, sub_type | —        | —                        | Diturunkan dari komponen asal                     |

View ini di-refresh oleh trigger pada perubahan status version (commit ke active) atau via scheduled job (untuk recovery). REFRESH MATERIALIZED VIEW CONCURRENTLY direkomendasikan agar tidak mem-block read query.

#### 7.2.5. audit_log

Log audit aktivitas user pada entitas product_order, version, dan component.

| **Column**            | **Type**              | **Constraint** | **Notes**                           |
|-----------------------|-----------------------|----------------|-------------------------------------|
| log_id                | BIGSERIAL             | PK             |                                     |
| entity_type           | VARCHAR(50)           | NOT NULL       | product_order / version / component |
| entity_id             | BIGINT                | NOT NULL       |                                     |
| operation             | VARCHAR(20)           | NOT NULL       | INSERT / UPDATE / DELETE / COMMIT   |
| before_data           | JSONB                 | NULL allowed   | Snapshot sebelum perubahan          |
| after_data            | JSONB                 | NULL allowed   | Snapshot setelah perubahan          |
| user_id, performed_at | VARCHAR / TIMESTAMPTZ | NOT NULL       | Audit fields                        |

#### 7.2.6. product_order_version_layout

Menyimpan posisi visual (x, y) setiap node pada Flow Editor agar tata letak yang sudah diatur user dapat di-preserve antar sesi.

| **Column**    | **Type**    | **Constraint**                    | **Notes**                                      |
|---------------|-------------|-----------------------------------|------------------------------------------------|
| layout_id     | BIGSERIAL   | PK                                |                                                |
| version_id    | BIGINT      | FK product_order_version NOT NULL | Owner version snapshot                         |
| node_ref_type | VARCHAR(10) | NOT NULL                          | PRODUCT atau MASTER (sama seperti rm_ref_type) |
| node_ref_id   | BIGINT      | NOT NULL                          | ID node yang di-layout                         |
| pos_x         | INT         | NOT NULL                          | Posisi horizontal canvas (px)                  |
| pos_y         | INT         | NOT NULL                          | Posisi vertikal canvas (px)                    |
| updated_at    | TIMESTAMPTZ | NOT NULL                          | Audit field                                    |

Catatan: bila tidak ada record layout untuk sebuah version (mis. version hasil import Excel atau version yang dibuka di Flow Editor pertama kali), front-end menjalankan auto-layout algorithm dan men-generate record default.

### 7.3. Relasi (ERD Summary)

Diagram relasi (high-level):

\[master_item\] ──(FK)── \[product_order\] ──1:N── \[product_order_version\] ──1:N── \[product_order_component\]

\[product_order_component\].rm_ref_id (polymorphic) ──\> \[product_order\] (Captive Cost)

──\> \[master_item\] (Store Rate)

\[product_order_version\] ──1:N── \[product_order_version_layout\] (posisi visual canvas)

\[product_order_exploded\] = materialized view dari traversal rekursif atas \[product_order_component\]

### 7.4. Pendekatan Teknis: Visual BOM Editor

Untuk implementasi Flow Editor (FR-17) dan Flow View (FR-9), direkomendasikan penggunaan library canvas/diagram berikut sesuai stack front-end:

| **Library**                | **Konteks**        | **Catatan**                                                                                                                                                             |
|----------------------------|--------------------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| React Flow / @xyflow/react | React              | Library matang, MIT licensed, dukungan penuh untuk custom node/edge, pan/zoom, minimap, snap-to-grid, save/load state ke JSON. Banyak dipakai di production (mis. n8n). |
| dagre / elkjs              | Layout engine      | Dipakai bersama React Flow untuk auto-layout hierarchical. Dagre lebih sederhana; elkjs lebih powerful untuk graph besar dengan banyak constraint.                      |
| d3-zoom / d3-drag          | Vanilla / fallback | Bila perlu kontrol lebih granular atau target non-React; lebih banyak coding manual.                                                                                    |

Format penyimpanan state canvas:

- Nodes: di-derive dari product_order + product_order_component pada version aktif; tidak perlu disimpan terpisah sebagai "node" entity.

- Edges: di-derive dari product_order_component (1 baris komponen = 1 edge dari rm_ref ke parent product order).

- Position: disimpan di product_order_version_layout (lihat 7.2.6).

- Saat user save draft, sistem write ke 3 tabel: product_order, product_order_component, product_order_version_layout dalam 1 transaction.

Pertimbangan UX penting:

- Karena BOM bisa besar (max depth = 13 di data current, ratusan node untuk product complex), Flow Editor sebaiknya memungkinkan user fokus pada subset graph — mis. "hanya 2 level ke atas dan 2 level ke bawah dari node terpilih" — agar canvas tidak overwhelming.

- Untuk eksplorasi multi-level penuh, gunakan BOM Tree (vertical) atau Flow View read-only (horizontal); Flow Editor lebih cocok untuk editing fokus.

- Validate cycle harus dijalankan baik saat real-time saat user menarik koneksi baru (visual warning), maupun saat commit (block save jika user tidak override).

### 7.5. Indeks Wajib

- UNIQUE INDEX (product_order.product_code).

- INDEX (product_order_version.product_sys_id, status) — untuk lookup version aktif cepat.

- INDEX (product_order_component.version_id, sequence_no) — untuk read komponen terurut.

- INDEX (product_order_component.rm_ref_type, rm_ref_id) — untuk where-used query.

- INDEX (product_order_exploded.product_sys_id, level), (rm_ref_type, rm_ref_id) — query explosion & where-used.

- UNIQUE INDEX (product_order_version_layout.version_id, node_ref_type, node_ref_id) — satu posisi per node per version.

## 8. Excel Input Format (untuk Import)

### 8.1. Filosofi

Format Excel baru disederhanakan untuk match dengan model normalized: satu baris di Excel = satu komponen langsung. Hasilnya, jumlah baris akan turun signifikan dibanding format flatten lama (estimasi dari 31.012 baris menjadi sekitar 13.000–15.000 baris).

Sistem akan menyediakan converter dari format Excel lama ke format Excel baru untuk migrasi data awal — user tidak perlu mengubah file Excel lama secara manual.

### 8.2. Struktur Sheet

Excel input terdiri dari satu sheet utama. Atribut master (cyl_type, shade) tidak perlu diisi ulang karena akan di-resolve oleh sistem berdasarkan kode.

| **Column**       | **Type** | **Required** | **Description**                                                                |
|------------------|----------|--------------|--------------------------------------------------------------------------------|
| FG_TOP_2         | VARCHAR  | Yes          | Identitas product order (unik global)                                          |
| FG_ITEM_CODE     | VARCHAR  | Yes          | Item code (harus ada di master item)                                           |
| FG_CYL_TYPE_CODE | VARCHAR  | Yes          | Kode cylinder type (harus ada di master)                                       |
| FG_SHADE_CODE    | VARCHAR  | No           | Kode shade (harus ada di master bila diisi)                                    |
| SEQUENCE_NO      | INT      | Yes          | Urutan komponen di product order ini (1, 2, 3, ...)                            |
| SUB_SEQUENCE     | INT      | No           | Untuk multi-yarn blending; kosongkan bila single component                     |
| SUB_TYPE         | VARCHAR  | No           | Yarn-Cap / Stores / PTY / REWINDING                                            |
| RM_TYPE          | VARCHAR  | Yes          | Store Rate / Captive Cost / Multi Yarn / Uneven Packing                        |
| RM_REF_TYPE      | VARCHAR  | Yes          | PRODUCT (mengarah ke FG_TOP_2 lain) atau MASTER (mengarah ke kode master item) |
| RM_REF_CODE      | VARCHAR  | Yes          | Kode product order lain (bila PRODUCT) atau kode master item (bila MASTER)     |
| RM_DESCRIPTION   | VARCHAR  | No           | Fallback deskripsi bila Multi Yarn RM belum ada di master                      |

### 8.3. Contoh Baris

Contoh: TCM0000001-6378-01-MEEREBAH yang sebelumnya memakai 5 baris di format lama, di format baru hanya 1 baris (karena ia hanya punya satu komponen langsung: TCY0000061-6378-01-CABLER):

FG_TOP_2: TCM0000001-6378-01-MEEREBAH

FG_ITEM_CODE: TCM0000001 \| FG_CYL_TYPE_CODE: MEEREBAH \| FG_SHADE_CODE: 6378-01

SEQUENCE_NO: 1 \| RM_TYPE: Captive Cost \| RM_REF_TYPE: PRODUCT

RM_REF_CODE: TCY0000061-6378-01-CABLER

Sedangkan TCY0000061-6378-01-CABLER akan punya barisnya sendiri (1 baris, mengarah ke PTY0001532-6378-01-PLY ( Carp)), dan seterusnya. Chain panjang muncul sebagai banyak entri terpisah, bukan diulang-ulang.

### 8.4. Import Validation Rules

- Setiap FG_TOP_2 harus konsisten atribut FG_ITEM_CODE, FG_CYL_TYPE_CODE, dan FG_SHADE_CODE-nya di seluruh baris.

- Kombinasi FG_TOP_2 + SEQUENCE_NO + SUB_SEQUENCE harus unik.

- Bila RM_REF_TYPE = PRODUCT, RM_REF_CODE harus berupa FG_TOP_2 yang juga ada di file ATAU sudah ada di database.

- Bila RM_REF_TYPE = MASTER, RM_REF_CODE harus ada di master item.

- Sistem mendeteksi cycle pada seluruh file sebelum commit; jika ada, ditampilkan sebagai warning yang perlu di-override.

### 8.5. Migrasi dari Format Lama

Sistem menyediakan converter terpisah (script atau menu) untuk membaca file Excel format lama (22 kolom dengan prefix FG\_ dan RM\_) dan menghasilkan file format baru. Logika converter:

26. Untuk setiap FG_TOP_2 di file lama, identifikasi baris dengan RM_SEQUENCE max — itu adalah komponen langsung (direct child) sebenarnya.

27. Baris-baris dengan RM_SEQUENCE \< max adalah hasil flatten dari intermediate product di bawahnya; di-skip untuk FG ini karena akan muncul sebagai entri tersendiri ketika converter memproses intermediate product itu.

28. Untuk Multi Yarn (RM_SUB_SEQUENCE not null), semua row dengan sequence_no yang sama dipertahankan dengan sub_sequence terisi.

29. Field RM_LEFT_NO_INTO dan RM_SUB_SEQUENCE_REAL diabaikan (redundant).

## 9. Non-Functional Requirements

### 9.1. Performance

- BOM explosion single product (depth ≤ 10): response time \< 2 detik.

- Where-used single item: response time \< 2 detik.

- Search & list product order (paginated): response time \< 1 detik untuk dataset ≤ 50.000 record.

- Refresh materialized view (concurrent): tidak mem-block read; selesai \< 30 detik untuk full refresh.

### 9.2. Scalability

- Mendukung minimal 50.000 product order, 250.000 komponen, 200.000 versions.

- Mendukung minimal 50 concurrent users.

### 9.3. Availability

- Target uptime: 99.5% jam kerja (mengikuti SLA infrastruktur internal).

- Backup database otomatis harian; retensi minimal 30 hari.

### 9.4. Security

- Autentikasi via SSO/IAM eksisting.

- Otorisasi berbasis role dari IAM (mapping role → permission didefinisikan saat implementasi).

- Audit log atas semua operasi write (lihat FR-16).

- Database connection menggunakan TLS.

### 9.5. Platform & Form Factor

Bentuk aplikasi (web app / web + mobile / desktop) belum diputuskan pada saat dokumen ini disusun. Akan ditentukan pada fase technical design berdasarkan profil user dan ketersediaan tim engineering.

Apapun bentuknya, prinsip yang dipegang:

- API back-end yang clean dan dapat dikonsumsi front-end manapun (REST atau GraphQL).

- Pemisahan tegas antara layer presentasi dan business logic.

### 9.6. Internationalization

- UI Bahasa Indonesia sebagai default; struktur kode mendukung penambahan bahasa lain di masa depan.

- Tanggal/waktu disimpan dalam timezone UTC, ditampilkan dalam WIB.

## 10. Success Metrics

- 100% data Excel sumber berhasil di-import tanpa data loss (kecuali baris yang valid sebagai data error).

- Reduksi storage size minimal 50% dibanding file Excel lama (sebagai indikator denormalization removal).

- BOM explosion dan where-used yang sebelumnya dilakukan manual via Excel kini selesai dalam \< 5 detik di sistem.

- Process Engineering tim melaporkan reduksi waktu update BOM minimal 70% dibanding update manual di Excel.

- Zero kasus inkonsistensi data antar produk yang share intermediate product (terjamin oleh single source of truth).

## 11. Risks & Mitigations

| **Risk**                                                                                                           | **Severity** | **Mitigation**                                                                                                                                          |
|--------------------------------------------------------------------------------------------------------------------|--------------|---------------------------------------------------------------------------------------------------------------------------------------------------------|
| Data error pada file Excel sumber (mis. 156 baris RM_LEFT_NO_INTO anomali, 19 FG_TOP_2 dengan multiple FG_LEFT_NO) | Medium       | Sediakan dry-run import dengan validation report; review manual dengan domain expert sebelum commit final                                               |
| Cycle yang tidak terdeteksi karena depth limit pada recursive query                                                | Low-Med      | Set safe maximum depth (mis. 20) yang konservatif, beri warning bila tercapai; tambahkan periodic full-graph cycle detection job                        |
| Materialized view jadi stale karena trigger gagal                                                                  | Medium       | Sediakan endpoint untuk manual refresh dan scheduled refresh harian sebagai safety net; expose monitoring last_refreshed_at di UI                       |
| Master data tidak tersedia/lengkap pada saat import awal                                                           | High         | Lakukan validasi pre-import; tampilkan daftar kode yang tidak ditemukan; sediakan opsi import partial atau auto-create placeholder dengan flag          |
| Polymorphic FK menyulitkan referential integrity                                                                   | Medium       | Gunakan dua kolom mutually-exclusive (rm_product_sys_id, rm_master_item_id) dengan CHECK constraint, bukan satu kolom polymorphic                       |
| User resistance karena format Excel input baru berbeda                                                             | Medium       | Sediakan converter otomatis dari format lama ke baru; training sesi untuk Process Engineering tim; pertahankan export ke format flatten lama bila perlu |

## 12. Implementation Phasing (Suggested)

**Phase 1 — Foundation (MVP)**

- Schema database lengkap (product_order, version, component, audit_log).

- API CRUD product_order, version, component.

- Integrasi master data (read-only) untuk item, cyl_type, shade.

- Import dari Excel + converter format lama → baru.

- Cycle detection.

**Phase 2 — Visualization & Reporting**

- BOM tree view (FR-8) — vertical hierarchical.

- Flow view (FR-9) — horizontal DAG read-only.

- Materialized view product_order_exploded dengan auto-refresh.

- BOM explosion report (FR-10) dan where-used report (FR-11).

- Export ke Excel.

**Phase 3 — Visual BOM Editor**

- Flow Editor drag-drop canvas (FR-17) menggunakan React Flow.

- Auto-layout & visual helpers (FR-18).

- Persistensi layout via product_order_version_layout.

- Real-time cycle detection saat user membuat koneksi baru.

**Phase 4 — Versioning & Audit**

- Version history dan comparison view (FR-12, FR-13).

- Audit log UI.

**Phase 5 — Costing Orchestration**

- Topological sort API (FR-19) berbasis product_order_exploded.

- Dependency resolution API (FR-20) berbasis product_order_component.

- Batch orchestrator (FR-21) dan on-demand trigger (FR-22).

- Costing status dashboard (FR-23).

- Prasyarat: tabel external (parameter cost, master RM price, cost result) sudah tersedia.

**Phase 6 — Enhancements (Optional)**

- Minimap pada Flow Editor untuk navigasi graph besar.

- Approval workflow (bila kebutuhan muncul di masa depan).

- Public API untuk konsumsi sistem hilir (production scheduling, costing, dll).

## 13. Open Questions

30. Apakah ada batasan maksimum depth BOM yang realistis? (Saat ini ditemukan max RM_SEQUENCE = 13; safe default 20.)

31. Bagaimana proses approval bila perlu ditambahkan di masa depan? (Saat ini di luar scope.)

32. Bentuk aplikasi (web / mobile / desktop) — final decision.

33. Mekanisme integrasi dengan sistem master data: API call real-time, replikasi, atau snapshot harian?

34. Untuk 276 baris Multi Yarn dengan RM_ITEM_CODE NULL: apakah strategi finalnya adalah melengkapi master item, atau membuat kategori "description-only RM" sebagai first-class concept?

35. Apakah perlu role khusus untuk import operation (terpisah dari CRUD biasa)?

36. Calculation engine eksternal: REST API, message queue, atau direct database write? Mempengaruhi cara orchestrator memanggil engine di FR-21/FR-22.

37. Period definition di tabel parameter cost dan cost result: bulanan, kuartalan, atau effective-dated? Mempengaruhi key composite di tabel external.

38. Saat version BOM berubah di tengah period, cost result version lama tetap valid sampai tutup buku, atau perlu re-calculate otomatis?

## 14. Appendix

### 14.1. Mapping Field Excel Lama → Schema Baru

| **Excel Lama (Column)**             | **Skema Baru (Column / Source)**       | **Status**                               |
|-------------------------------------|----------------------------------------|------------------------------------------|
| FG_ITEM_CODE                        | product_order.item_code (via master)   | Kept                                     |
| FG_TOP_2                            | product_order.product_code             | Kept (renamed)                           |
| FG_ITEM_NAME                        | (master item)                          | Dropped — dari master                    |
| FG_CYL_TYPE                         | product_order.cyl_type_id (via master) | Kept (FK)                                |
| FG_SHADE_CODE                       | product_order.shade_id (via master)    | Kept (FK)                                |
| FG_SHADE_NAME                       | (master shade)                         | Dropped — dari master                    |
| FG_LEFT_NO                          | product_order.product_sys_id           | Re-generated by system                   |
| FG_SEQUENCE                         | —                                      | Dropped — derived dari cyl_type          |
| RM_ITEM_CODE                        | (via rm_ref_id ke master/product)      | Resolved via FK                          |
| RM_TOP_2                            | (via rm_ref_id atau rm_description)    | Resolved via FK or fallback              |
| RM_ITEM_NAME / CYL_TYPE / SHADE\_\* | (via master)                           | Dropped — dari master                    |
| RM_LEFT_NO                          | component.rm_ref_id (polymorphic)      | Kept (resolved)                          |
| RM_CYL_SYS_ID                       | (via master)                           | Dropped — dari master                    |
| RM_SEQUENCE                         | component.sequence_no                  | Kept (renamed)                           |
| RM_TYPE                             | component.rm_type                      | Kept                                     |
| RM_SUB_SEQUENCE                     | component.sub_sequence                 | Kept                                     |
| RM_SUB_TYPE                         | component.sub_type                     | Kept                                     |
| RM_LEFT_NO_INTO                     | —                                      | Dropped — redundant                      |
| RM_SUB_SEQUENCE_REAL                | —                                      | Dropped — redundant (invers RM_SEQUENCE) |

### 14.2. Glossary Singkatan

- BOM — Bill of Materials

- CTE — Common Table Expression (SQL)

- DAG — Directed Acyclic Graph

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

### 14.3. Document Revision History

| **Version** | **Date** | **Description**                                                                                                                      | **Author** |
|-------------|----------|--------------------------------------------------------------------------------------------------------------------------------------|------------|
| 1.0         | May 2026 | Initial draft                                                                                                                        | —          |
| 1.1         | May 2026 | Added FR-9 Flow View, FR-17 Flow Editor (drag-drop canvas), FR-18 Auto-Layout; new table product_order_version_layout                | —          |
| 1.2         | May 2026 | Added Section 6.7 Costing Orchestration (FR-19 sampai FR-24); sistem menjadi costing orchestrator untuk calculation engine eksternal | —          |
