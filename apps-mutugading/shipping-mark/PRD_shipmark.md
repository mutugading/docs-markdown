# PRD — Shipping Mark Module

| | |
|---|---|
| **Module** | `Modules/ShipMark` (Laravel, `apps-mutugading`) |
| **Config schema** | MGTAPPS · **Read** MGTDAT (Orion) |
| **Doc location** | `docs-markdown/apps-mutugading/PRD/shipmark/PRD.md` |
| **Status** | Draft v1.0 |
| **Author** | IT (Indra) |
| **Referensi** | `shipping_mark_template_catalog.md`, `ship_mark_schema.sql`, `design.md`, `spec.md`, `TASKS.md` |
 
---

## 1. Latar Belakang & Masalah

Saat pengiriman barang export dari despatch, customer sering meminta **shipping mark tambahan** untuk ditempel di box — penanda di luar label box yang sudah ada. Kondisi saat ini:

- Formatnya **sangat beragam per customer**: field berbeda, ukuran berbeda (A4/A5/custom), party berbeda (Buyer/Consignee/Notify/Shipper), bahkan sebagian info duplikat dari label box.
- Request **selalu mepet** menjelang delivery, saat barang sudah di warehouse.
- Dibuat **manual** oleh tim export (Excel/Word, sebagian sudah pakai Word mail-merge) lalu dikirim ke despatch/warehouse untuk diisi (lot/berat/pallet no diisi tangan), dicetak, dan ditempel.
- Akibatnya: lambat, **rawan salah isi** karena field per-unit diketik manual, revisi bolak-balik lewat email, dan **tidak traceable**.
  **Temuan analisis 38 contoh** (8 dari tim export + 30 dari despatch): shipping mark **random antar customer tapi stabil per customer**, dan semuanya tersusun dari **satu kosakata field yang sama**. Grain (tingkat detail) bervariasi: uniform → per item → per lot → per pallet → per carton. Beberapa template despatch bahkan **sudah memakai mail-merge** (`«PALLET»`, `«Merge»`) — bukti bahwa mereka sudah berpikir "template + data", hanya masih manual.

---

## 2. Tujuan

1. Generate shipping mark **otomatis dari data SSC** yang sudah ada di sistem (scan/pack), bukan diketik ulang.
2. **Template per customer (data-driven)** — dibuat sekali, dipakai berulang. Customer/format baru = **konfigurasi**, bukan koding.
3. **Kurangi manual entry & risiko salah tempel**: mark di-bind ke data scan nyata sehingga traceable dan konsisten dengan label box.
4. **Fleksibel**: grain, ukuran, susunan field, dan party semuanya dapat diatur per template.
### Non-Goals (di luar scope)
- Tidak mengatur **proses fisik penempelan** mark ke box — itu SOP warehouse.
- **v1 tidak** menarik otomatis Customer PO / L/C / Invoice / Customer Color dari SO (belum ada field/integrasi — jadi manual dulu).
- **v1 tidak** mendukung logo/gambar (tidak ditemukan di 38 sampel).
- Bukan pengganti label box existing — ini mark **tambahan**.
---

## 3. Stakeholder & Pengguna

| Peran | Tanggung jawab |
|---|---|
| **Tim Export** (marketing) | Owner template + master mapping — setup sekali per customer (di luar critical path). |
| **Despatch & Warehouse** | Generate + print per SSC (mereka pegang barang + data scan). |
| **IT** | Build & maintain engine + kamus field. |

**Batas tanggung jawab:** IT menjamin *data benar + layout benar + ukuran benar + terikat ke unit yang traceable*. Penempelan fisik tetap tanggung jawab warehouse. Karena sistem ini **mengurangi** risiko dibanding proses manual (menghilangkan pengisian blank oleh tangan), argumen "aplikasi ini bikin masalah baru" tidak berlaku.
 
---

## 4. Konsep Inti

- **Anchor = SSC** (`sohm_txn_code-sohm_no`). Customer ditarik otomatis dari `sohm_cust_code`.
- **Template** = layout (baris field terurut) + grain default + ukuran + party role + jumlah copies.
- **4 tipe nilai field** (kunci yang membuat satu engine melayani semua customer):
    - **STATIC** — teks tetap per template (Shipper MGT, alamat, "MADE IN INDONESIA", nama & alamat customer).
    - **SYSTEM** — otomatis dari data scan/pack (item, lot, net, gross, tare, pallet/carton no, tube color, bobbin, dsb).
    - **MASTER** — nama versi customer (material no, color name, NCM) via mapping per customer — *learn-as-you-go*.
    - **MANUAL** — diketik per SSC (PO, L/C, invoice, customer color) — kandidat integrasi berikutnya.
    - *(COMPUTED — turunan: tare = gross−net, berat/bobbin = berat box / jumlah bobbin.)*
- **Grain = "model generate" = pilihan GROUP BY** di atas data scan: `UNIFORM / ITEM / ITEM_LOT / PALLET / CARTON`. Default disimpan di template, **bisa di-override saat generate** (tergantung request). Field SYSTEM resolve exact (grain carton) atau agregat SUM/COUNT (grain kasar); lot jadi `'MULTI'` bila lebih dari satu.
- **Learn-as-you-go master**: saat user pertama kali mengetik nama versi customer (material/color), sistem menyimpannya; kiriman berikutnya otomatis. Beban entry menyusut seiring waktu.
- **Copies** = jumlah cetak per unit (mark ditempel di beberapa sisi box) — atribut **cetak**, bukan konten template.
---

## 5. Functional Requirements

### FR-1 — Template Management
Sistem menyediakan CRUD template + **clone**. Atribut header: kode, nama, customer (`OM_CUSTOMER`, boleh kosong = shared), ship-to (pembeda destinasi), party role, grain default, paper size (+ w×h custom), default copies, is_default (bila customer punya >1), is_supp (label tambahan spt brand/L-C), active, versi.

### FR-2 — Field Lines
Tiap template berisi baris field terurut. Atribut baris: urutan, line type (FIELD/TITLE/FOOTER/STATIC/SPACER), label, value type, value ref (dari kamus), static value, dan dukungan layout: **2 kolom** (mis. GW+NW satu baris di BOX H), sub-indent (WEIGHT → Net/Gross), bold, font size.

### FR-3 — Kamus Field
Sistem mengenali kosakata field baku (§7). Value ref pada baris hanya boleh dari kamus ini. Field baru cukup ditambah **1 entri** kamus, sekali, lalu tersedia untuk semua template.

### FR-4 — Generate Flow
Pilih SSC → sistem menampilkan customer + **auto-suggest template** (preselect bila hanya 1; pilih bila >1) → pilih grain (default template, dapat di-override) + copies → isi entry → preview → **Generate & Print** (PDF).

### FR-5 — Entry Screen Dinamis
Layar entry **dihasilkan dari metadata template**:
- **Kolom** = baris MANUAL + MASTER yang belum ke-mapping (jadi berbeda otomatis per customer; template yang semua field-nya SYSTEM/STATIC → tidak ada entry, langsung print).
- **Baris** = item di dalam SSC (`distinct grade`).
- Field per-unit (lot/net/gross/pallet/carton) **tidak diinput** — otomatis.
- **Fill-down** untuk field yang sama di semua item (mis. PO).
- MASTER ke-mapping tampil sebagai nilai + badge "auto"; nilai baru yang diketik **disimpan** ke master (learn-as-you-go).
### FR-6 — Grain Resolver
Mendukung 5 grain (§4), agregasi SUM/COUNT sesuai grain, lot MULTI-aware, dan format `n/total`.

### FR-7 — Data Sources
Menarik dari Orion (§6): join scan↔pack, ESC resolver, dan mapping field→sumber.

### FR-8 — PDF Render
Render mark sesuai `paper size` template (A4/A5/custom mm) dan orientation. Untuk SSC besar (mis. 704 carton) render **batch/stream** agar tidak OOM.

### FR-9 — Print Log & Reprint
Setiap generate dicatat (SSC, template, grain, copies, jumlah label, snapshot nilai MANUAL sebagai JSON). Reprint menghasilkan output identik dari snapshot.

### FR-10 — Master Mapping (learn-as-you-go)
Satu tabel generik menampung nama versi customer per atribut (MATERIAL_NO / ITEM_CODE / COLOR_NAME / NCM), diisi dari entri user. Alasan: 1 FG bisa punya nama berbeda di tiap customer; shade sama di MGT bisa bernama beda per customer.

### FR-11 — Permissions
Akses di-gating Spatie: `shipmark.template.manage`, `shipmark.generate`.
 
---

## 6. Data & Integrasi

**Sumber Orion (MGTDAT, read-only):** `OT_SO_HEAD_MGT`, `OT_SO_ITEM_MGT`, `OT_SO_SCAN_MGT`, `OT_WMS_PACK_TABLE_ALTHARA`, `OT_SO_HEAD`, `OM_CUSTOMER`, `OM_GRADE_CODE_2`.

- **Join scan ↔ pack:** `SS_SCAN_CODE = PRD_SCAN_CODE`. ALTHARA = **data per box**.
- **ESC / Contract no:** `SOHM_REF_SYS_ID = SOH_SYS_ID` → `NVL(SOH_REF_TXN_CODE,SOH_TXN_CODE)||'-'||NVL(SOH_REF_NO,SOH_NO)`.
- **Bobbin:** `PRD_SUB_UNITS` = jumlah bobbin per box; `PRD_QTY` = berat box (ada CHECK `PRD_QTY=PRD_NET_WT`); berat/bobbin = `PRD_QTY / PRD_SUB_UNITS`.
- **Tube color / tare / box type:** `PRD_COLOR` / `PRD_TARE_WT` / `PRD_BOX_TYPE`.
  **Config (MGTAPPS):** `SHIP_MARK_TEMPLATE` (SMT_), `SHIP_MARK_TEMPLATE_LINE` (SMTL_), `SHIP_MARK_CUST_MAP` (SMCM_), `SHIP_MARK_PRINT_LOG` (SMPL_). DDL lengkap: `ship_mark_schema.sql`. SQL exact & mapping: `spec.md`.

---

## 7. Kamus Field (ringkas)

| Key | Type | Sumber |
|---|---|---|
| MGT_SHIPPER / MGT_ADDRESS / ORIGIN / MADE_IN | STATIC | teks tetap MGT |
| CUST_PARTY_NAME / CUST_PARTY_ADDR / CUST_TAX_ID / CUST_SPEC_TEXT | STATIC | per customer (label party = atribut template) |
| ITEM | SYSTEM | `soim_item_code` |
| LOT_NO / MERGE | SYSTEM | `ss_batch_no` (`prd_merge_no`) |
| NET_WT / GROSS_WT | SYSTEM | `ss_qty` / `ss_gross_wgt` (SUM di grain kasar) |
| TARE | SYSTEM | `prd_tare_wt` (fallback gross−net) |
| PALLET_NO / CARTON_NO | SYSTEM | `ss_pallet_no` fmt / `ss_cart_no` + `n/total` |
| TUBE_COLOR / BOBBIN_QTY / BOX_TYPE | SYSTEM | `prd_color` / `prd_sub_units` / `prd_box_type` |
| COLOR_MGT | SYSTEM | `ss_invi_shade` (nama shade MGT) |
| CONTRACT_NO | SYSTEM | ESC resolver |
| WEIGHT_PER_BOBBIN | COMPUTED | box_wt / bobbin_qty |
| CUST_MATERIAL_NO / CUST_ITEM_CODE / NCM / BRAND_LABEL | MASTER | `SHIP_MARK_CUST_MAP` (learn-as-you-go) |
| COLOR_CUST | MANUAL* | (kandidat MASTER bila di-save; next: field SO) |
| CUST_PO_NO / LC_NO / INVOICE_NO / POS_NO | MANUAL | entry (next integration) |

> Detail 14 template contoh (Bekaert AU/CPT/Brasil/Argentina, Hamilton, Scobie, Furniweb, Oriental, Tencate, Marionette, Greentex, L/C carton, BOX H, dst) ada di **`shipping_mark_template_catalog.md`**.

**Aturan COLOR:** preferensi per customer beragam — ada yang mau color name versi customer saja, ada yang color customer + shade MGT, ada yang shade MGT saja. Ditangani cukup dengan menyusun 0/1/2 baris color (COLOR_CUST dan/atau COLOR_MGT), tanpa logic khusus.
 
---

## 8. UX Flows

- **Flow A — Setup Template (Export, sekali/customer):** buat template → susun baris field → set party, grain, size, copies → aktifkan.
- **Flow B — Generate (Despatch, tiap SSC):** pilih SSC → template auto → grain + copies → entry grid (kolom dinamis) → preview → generate & print.
- **Flow C — Reprint:** dari Print Log → regenerate dari snapshot.
---

## 9. Non-Functional Requirements

- **Performa:** satu SSC dapat berisi 700+ carton (contoh nyata `194 AM` = 704). Generate wajib batch/stream; siapkan chunking + index.
- **Oracle 11g:** PK via SEQUENCE + BEFORE INSERT trigger (tidak ada IDENTITY); JSON via CLOB + cast `ClobJson`; tanpa Diagnostics/AWR pack.
- **Ukuran:** A4 / A5 / custom (mm) per template.
- **Keamanan:** gating permission Spatie; hanya baca MGTDAT, DML terbatas ke MGTAPPS.
- **Auditability:** semua generate tercatat di print log.
---

## 10. Out of Scope / Next Integration

1. Field di SO untuk **Customer PO / L/C / Invoice / Customer Color** → tarik otomatis (cek dgn tim sales).
2. **QR/barcode pallet** di mark untuk verifikasi sebelum tempel (infra QR sudah ada dari worker QR code). Fase 2.
3. **Slot logo/gambar** bila ada customer baru yang meminta.
4. **Supplementary label types** (brand label, L/C label) sebagai template `is_supp` yang bisa digenerate bersama SSC.
---

## 11. Risiko & Mitigasi

| Risiko | Mitigasi |
|---|---|
| Salah tempel mark ke box (kekhawatiran utama) | Mark di-bind ke data scan nyata (bukan blank isi tangan) → konsisten dgn label box & traceable; ketidakcocokan jadi terlihat. QR verify di fase 2. Proses manual saat ini **lebih** berisiko. |
| Master kosong di awal | Learn-as-you-go; beban entry mengecil tiap kiriman; customer repeat akhirnya nol entry (spt Hamilton). |
| Query grain carton untuk N besar | Chunking render + index pada tabel scan/pack. |
| Format customer baru tak terduga | Data-driven: susun ulang field dari kamus + clone template; hanya field benar-benar baru yang menambah 1 entri kamus. |
 
---

## 12. Phasing

- **Phase 1 (MVP)** — template management, generate 5 grain, entry dinamis, PDF per ukuran, print log + reprint, learn-as-you-go master, permissions. (Task T001–T015 di `TASKS.md`.)
- **Phase 2** — integrasi SO (PO/LC/invoice/color), QR verify, slot logo, supplementary label types.
---

## 13. Success Metrics / Acceptance

- Ke-14 template contoh dapat direpresentasikan **tanpa koding** (hanya config).
- Generate SSC nyata menghasilkan PDF benar sesuai grain & ukuran: Hamilton (40 pallet, single lot), Bekaert AUS (20 pallet, 2 lot, berat beda per pallet), `194 AM` (704 carton, batch).
- Customer/format baru dibuat lewat UI **< 15 menit** tanpa developer.
- Waktu pembuatan satu set shipping mark turun dari proses manual (jam/hari) menjadi **beberapa menit**.
- Entry manual per customer repeat menurun mendekati nol setelah beberapa kiriman (learn-as-you-go).
---

## 14. Referensi & Lampiran

- Katalog template per customer: `shipping_mark_template_catalog.md`
- Schema DDL: `ship_mark_schema.sql`
- Technical design: `design.md` · Code-ready spec: `spec.md`
- Task queue: `TASKS.md` · Preflight: `PREFLIGHT.md` / `preflight.sh`
- Sumber: 38 contoh shipping mark (email tim export + file despatch).
 
