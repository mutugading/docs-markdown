# 5. Layer 3 — Work Order

> **Ditulis ulang di v1.2.** WO sekarang **route-driven & product-parameter-driven**,
> menggantikan MLR (`PRD_TXT_MLR_ENTRY`) dengan cara **rebuild native** — bukan menarik
> data MLR. Referensi legacy (DDL MLR, JSON web app) dipakai sebagai acuan rancang-ulang.

## Konsep

Work Order (WO) adalah **instruksi produksi konkret per mesin per lot** yang di-generate
dari Production Plan. Rantai turunannya:

```
Product Route (Costing)  →  Production Plan Item  →  WORK ORDER  →  Realisasi
  resep + RM + routing        alokasi mesin+waktu      instruksi       (bobbin ETL + shift entry + packing)
   cost_route_head/seq/rm      consider route           snapshot route
```

WO **mewarisi** dari route (spec, komposisi RM, routing) dan dari plan (mesin, periode).
Karena route sudah menyimpan komposisi RM & urutan proses, WO **tidak mengulang** field
berulang ala MLR (POY merge 1/2/3, chips 1/2/3) — itu jadi relasi ke route.

```
WORK_ORDER (header)
    ├── WO_PARAMETER          (1:N per param)  planned: nilai PPC + nilai PC
    ├── WO_EXECUTION          (1:N per date+shift+param)  parameter actual saat running
    ├── WO_RM_ALLOCATION      (1:N per komponen RM route)  lot aktual + genealogy
    ├── WO_PRODUCTION_ACTUAL  (1:N per date+shift)  qty aktual (lihat halaman 13)
    └── WO_GRADE_ACTUAL       (1:N per grade)  packing aktual dari ETL, link by lot
```

---

## Prinsip rebuild: "jangan dikurangi" = artefak, bukan schema

User tidak akan menerima WO sebagai pengganti MLR kalau informasinya berkurang. Tapi
"jangan dikurangi" berlaku untuk **informasi yang user lihat, pakai, dan cetak** — bukan
untuk 180 kolom mentah MLR (yang berisi kolom recycle-bin `BIN$`, field PHP-undefined
tech-debt, dan duplikasi).

**Acceptance test = cetakan.** Cetakan WO / MCN harus minimal selengkap cetakan MLR/MCN
sekarang, field-per-field. Gerbang sign-off bareng produksi = **side-by-side cetakan MLR
lama vs cetakan WO baru**. Field yang dulu flat di MLR tetap **tercetak** di kartu WO,
di-resolve dari relasinya (route / demand / allocation).

**Yang jadi relasi/turunan (bukan lagi field flat):**

| MLR (legacy flat) | Sistem baru | Sumber |
|---|---|---|
| POY merge 1/2/3 + chips 1/2/3 + Fresh/Box | `WO_RM_ALLOCATION` (N baris) | `cost_route_rm` |
| Customer / end-use | referensi demand | `production_demand` |
| Next process / produk lanjutan | urutan route | `cost_route_seq` |
| Grade requirement ×3 | grade req WO | demand (WO boleh override) |
| Entry-type A/B, TWT group | **tidak ada** | tiap route = WO sendiri |
| Parameter mesin (speed/nozzle/dll) | `WO_PARAMETER` | product-parameter master |

---

## WORK_ORDER (header)

**Identity & genealogy:**
- `wo_no` — nomor WO
- `wo_lot_no` — **di-generate PPC** saat WO dibuat. Ini jadi **sumber lot** yang harus
  dipakai bobbin tracking Oracle, supaya ETL produksi bisa nyambung ke WO by lot.
- `wo_area` (TXT / SPG / TWT), `wo_machine_id`
- `wo_ref_wo_id` + `wo_ref_type` — **referensi WO sebelumnya** (lihat "WO Reference")
- `wo_revision_no` + `wo_revision_reason` — revisi tampil di muka WO (mis. "PINDAH MC 05")

**Link (integrasi sistematis):**
- `wo_crh_head_id` + `wo_crh_version` — **snapshot route** yang dipakai (route bisa
  di-fork/lock; WO mengunci versi yang benar saat generate/approve)
- `wo_plan_item_id` — plan item asal
- `wo_demand_id` — demand asal (customer, order, grade req mengalir dari sini)

**Target:**
- `wo_qty_target`, `wo_deadline`, tanggal jadwal
- `wo_grade_requirement` — default dari demand, **WO boleh override** (produksi realistis
  bisa beda dari yang dipesan)

**Klasifikasi (untuk Incl/Excl, lihat halaman 13):**
- `wo_prod_category` — NORMAL / B_TO_B / APQ / TRIAL / SMALL_LOT

**Snapshot spesifikasi (saat approve):**
- den/fil/type, ply, yarn_type, shade, twist — **di-snapshot dari route/produk saat
  approve**, supaya WO closed tetap arsip instruksi apa adanya walau master berubah.

**Packing instruction:**
- tipe box, paper tube, package weight, pallet — **default dari product master, WO boleh
  override** (cara mengemas = properti produk).

**Status & audit:** lihat "Lifecycle" & "Approval".

---

## Parameter Mesin — Product-Parameter Model (v1.2)

Parameter mesin **tidak lagi kolom fixed**. Sistem baru mengonsumsi **modul product-parameter
milik Costing** (definisi + akses sudah diatur di modul costing, termasuk untuk orang
produksi). PPC hanya **konsumen** + menyimpan layer nilai per-mesin.

### Sumber definisi & nilai

```
mst_parameter (definisi global parameter)
  param_code, param_name, data_type (NUMBER/TEXT/BOOLEAN),
  param_category (INPUT/RATE/CALCULATED/MASTER_LOOKUP),
  lookup_master_code (enum combobox, mis. YARN_TYPE),
  uom_id, default_value, min_value, max_value,
  owner_department, display_group (Spec/Machine/Grade/Packing/Cost/…),
  display_order, is_required_for_costing

cost_product_parameter (nilai per PRODUK)
  cpp_product_sys_id, cpp_param_id → mst_parameter,
  cpp_value_numeric / cpp_value_text / cpp_value_flag  (three-column typed)
```

### Selector parameter WO

Parameter yang mengalir ke WO dipilih dengan **`display_group`**:
- `Machine` → `WO_PARAMETER` (setup mesin; dual PPC/PC untuk 8 param)
- `Spec` → snapshot spesifikasi
- `Packing` → packing instruction
- `Grade` → grade requirement
- `Cost` → costing-only, WO abaikan

> **Rekomendasi ke tim costing (Open Item):** tambah flag `is_for_production` di
> `mst_parameter` (mirror `is_required_for_costing`) untuk kontrak yang bersih. Sementara
> pakai `display_group='Machine'`.

### Rantai resolusi nilai (dua-grain — Opsi A)

`cost_product_parameter` grain-nya **per produk saja** (tidak ada dimensi mesin). Padahal
speed/posisi/draw-ratio **bervariasi per mesin** untuk produk sama. Karena itu ada **dua
lapis nilai** dengan **satu definisi** (`mst_parameter`):

Untuk WO produk P di mesin M, tiap parameter di-resolve berurutan:

```
1. WO referensi / WO running terakhir (duplicate/continuation)   → layer histori PPC
2. Override per PRODUK+MESIN                                       → PRODUCT_MACHINE_PARAMETER (PPC)
3. Nilai per PRODUK                                                → cost_product_parameter (Costing)
4. mst_parameter.default_value                                    → fallback
```

Costing membaca layer 3; produksi/WO membaca 2→3→4 (plus 1 kalau ada referensi). Kalau
suatu saat costing butuh speed berbeda dari produksi, cukup isi di layer produk — model
parameter yang bisa ditambah/dikurangi menampung ini tanpa ubah schema.

### WO_PARAMETER — planned (dual PPC vs PC)

Materialisasi baris parameter saat WO generate. Pola **3-tingkat**: nilai PPC (usulan) →
nilai PC (konfirmasi saat approve) → actual (`WO_EXECUTION`).

| Field | Deskripsi |
|---|---|
| `wop_wo_id` | FK WO |
| `wop_param_id` | FK `mst_parameter` (UUID) |
| `wop_value_ppc_num/text/flag` | nilai usulan PPC (typed sesuai `data_type`) |
| `wop_value_pc_num/text/flag` | nilai konfirmasi PC (diisi saat approve; default = PPC) |
| `wop_is_dual` | true hanya untuk 8 param dual |

**Dual PPC/PC hanya untuk 8 parameter** (dari MLR `_PC`): speed, disc, nozzle 1, nozzle 2,
air (bar), air (m³/hr), oil, opu, taper angle. Sisanya single value (PC = PPC). Flag `is_dual`
= config sisi PPC (tidak ada di master).

**Well-known codes** — efficiency engine butuh parameter tertentu stabil & selalu ada:
**denier, yarn speed (YS), no-of-position, std-weight**. Ini di-**pin ke `param_id`
spesifik** (bukan dicari by nama), supaya kalkulasi tidak jebol kalau param free-form
ditambah/di-rename.

### WO_EXECUTION — parameter actual (1:N per date+shift+param)

Input operator saat running. Parameter bisa berubah selama WO berjalan → entry baru per
date+shift untuk param yang berubah. Juga menampung parameter **actual-only** yang bukan
bagian MLR (mis. Heater 1/2, D/R, Steps Process untuk ACY) — ini bagian daily report,
bukan planned.

```
Contoh: speed berubah di hari ke-2 shift 2 → satu record WO_EXECUTION baru
        untuk (wo, date, shift, param_id=SPEED, value=760)
```

### ACY / Spandex — extension khusus, lintas area

Produk ACY punya set parameter tambahan (EDR Spandex, SOF, TOF, D/W, Steps Process 2–6,
Cycle Time, Axial Disp/Dwell, T2 Unitens, CV). Karena ACY ada di **area TXT maupun TWT**,
set ini di-attach ke **product type = ACY** (bukan ke area) — jadi otomatis ikut ke mana
pun ACY diproduksi. Di model product-parameter ini natural: parameter melekat ke produk.

---

## WO_RM_ALLOCATION — dari Route (pengganti POY/chips 1/2/3)

Satu baris per **komponen RM yang didefinisikan route** (`cost_route_rm`), N komponen —
bukan 3 slot. "RM lebih dari 1" = N baris, natural.

| Field | Deskripsi |
|---|---|
| `wra_wo_id` | FK WO |
| `wra_crm_rm_id` | FK `cost_route_rm` (komponen RM di route) |
| `wra_rm_type` | PRODUCT / ITEM / GROUP (dari `crm_rm_type`) |
| `wra_lot_no` | **lot aktual** yang dipilih |
| `wra_rm_source` | STORE / CAPTIVE / MIXED |
| `wra_fresh_box` | Fresh / Box |
| `wra_shade_code` | shade RM |
| `wra_qty_allocated` | qty dialokasikan |

**Genealogy otomatis.** `crm_rm_type = PRODUCT` berarti RM-nya produk lain (mis. POY jadi
RM untuk DTY). Kalau lot POY yang dialokasikan ke WO TXT ini diproduksi oleh WO SPG lain,
**link genealogy terbentuk sendiri** (rantai lot From→Into→Ref legacy = turunan, bukan
diketik manual seperti MLR).

**RM Fence:** Warning 85%, Block > limit + 1 doff (TXT). Override hanya PM.

---

## Model Mesin — TXT section, SPG line/position, TWT

Prinsip pemersatu: **mesin punya posisi; satu WO menempati sebagian posisi mesin untuk satu
lot; beberapa WO bisa berbagi satu mesin.** Efisiensi roll-up WO → mesin → area.

- **TXT:** machine (mis. 18) + section A/B → WO per section + lot.
- **SPG:** **Line → Position (winder) → bobbin.** Machine master SPG = line berisi N
  winder. **WO SPG = line + lot + posisi-dipakai** (`RUN_POS`); beberapa WO bisa berbagi
  satu line. Winder = unit efisiensi. Tiap doffing hasilkan 4/8/10/12 bobbin; **bobbin/end
  number = grain terhalus dari bobbin tracking** (bukan input manual). Lihat halaman 9.
- **TWT:** per mesin + lot. **Tidak ada TWT group** — tiap route didefinisikan sendiri lalu
  generate WO masing-masing.

---

## WO Reference — Duplicate & Continuation

Suggestion parameter PPC merujuk WO historis, bukan diketik dari nol. Dua rasa:

**Duplicate (referensi lunak).** Produk+mesin sama, lot beda. PPC klik "Duplikasi sebagai
WO baru" → parameter PPC ter-copy sebagai titik awal → boleh diubah → lot baru → **tanpa
ikatan** ke WO sumber. Murni akselerator input. `wo_ref_type = TEMPLATE`.

**Continuation (referensi keras).** WO selesai tapi barang kurang, produksi lagi. WO baru
**menunjuk** WO lama (`wo_ref_wo_id`) → mewarisi parameter **dan** konteks demand yang sama
→ sisa qty menambal demand yang sama (fulfillment nyambung). `wo_ref_type = CONTINUATION`.

Suggestion parameter berlapis (lihat rantai resolusi di atas): WO referensi → WO running
terakhir produk+mesin sama → default product-parameter.

---

## Lifecycle

```
DRAFT → SUBMITTED → PC APPROVED → PM APPROVED → SCHEDULED → CHANGEOVER → RUNNING → COMPLETED → CLOSED
                                                                         ↘ CANCELLED / REVOKE (manual + alasan)
```

- DRAFT: bisa auto-update/cancel jika plan berubah
- RUNNING+: read-only, perlu PM untuk cancel
- Revisi (Rev.N + alasan) tampil di muka WO

---

## Approval — PC → PM Sequential

```
PPC submit (isi nilai PPC)
    → PC approve  : review + isi/konfirmasi nilai PC (konfirmasi teknis)
    → PM approve  : izin jalan (responsibility produksi)
WO APPROVED = PC dan PM keduanya approve
```

- **Sequential**: PM approve **setelah** PC.
- **Auto-approve 24 jam** — tapi **bisa di-disable** lewat config. Kalau di-disable, WO
  **tidak jalan** tanpa approval PM eksplisit (menghindari skenario "sistem jalan tanpa PM
  lalu disalahkan").
- **PM juga untuk eksepsi**: override RM fence, block over-production, cancel WO running.
- Reject / revoke / cancel **selalu manual** + alasan + tercatat di audit trail.

> Berbeda dari v1.1 (dual PC+PM paralel + auto 4 jam). Diubah sesuai masukan PPC:
> tanggung jawab PM harus eksplisit.

---

## Mapping MLR → Sistem Baru (rebuild)

| MLR Entity | Sistem Baru | Keterangan |
|---|---|---|
| Header MLR (lot, mesin) | `WORK_ORDER` | lot di-generate PPC |
| Merge / spesifikasi produk | snapshot dari route/produk | saat approve |
| POY/chips 1/2/3 + Fresh/Box | `WO_RM_ALLOCATION` (N) | dari `cost_route_rm` |
| Parameter mesin (+`_PC`) | `WO_PARAMETER` (PPC/PC) | dari product-parameter master |
| Parameter actual | `WO_EXECUTION` | per date+shift+param |
| Grade req ×3 | `wo_grade_requirement` | dari demand, override |
| Customer / next process | demand / route seq | relasi, bukan field |
| Approval 5-level + revoke | PC→PM + revisi | disederhanakan |
| TWT group / SPG detail | route → WO / SPG position | lihat model mesin |

**Cutover, bukan parallel-feed.** Karena data MLR tidak ditarik, MLR lama dan sistem baru
tidak sinkron otomatis. Transisi = **cutover per area** (bukan MLR jalan paralel sebagai
fallback seperti v1.1). Lihat halaman 11.

---

## Schema WO

Prefix: `wo_` WORK_ORDER · `wop_` WO_PARAMETER · `woe_` WO_EXECUTION ·
`wra_` WO_RM_ALLOCATION · `wpa_` WO_PRODUCTION_ACTUAL · `wga_` WO_GRADE_ACTUAL ·
`wal_` WO_ACTUAL_LOG. (DDL lengkap di halaman 12.)

```sql
CREATE TABLE work_order (
    wo_id                    BIGSERIAL PRIMARY KEY,
    wo_no                    VARCHAR(30)   NOT NULL UNIQUE,
    wo_lot_no                VARCHAR(30)   NOT NULL UNIQUE,   -- di-generate PPC; dipakai bobbin tracking
    wo_area                  CHAR(3)       NOT NULL,          -- TXT / SPG / TWT
    wo_machine_id            BIGINT        NOT NULL,
    wo_crh_head_id           BIGINT        NOT NULL,          -- snapshot route (cost_route_head)
    wo_crh_version           INT           NOT NULL,
    wo_plan_item_id          BIGINT        NOT NULL,
    wo_demand_id             BIGINT,                          -- customer/grade req mengalir dari sini
    wo_ref_wo_id             BIGINT        REFERENCES work_order(wo_id), -- duplicate/continuation
    wo_ref_type              VARCHAR(15),                     -- TEMPLATE / CONTINUATION
    wo_qty_target            DECIMAL(18,3) NOT NULL,
    wo_grade_requirement     VARCHAR(5),                      -- default demand, override
    wo_deadline              DATE          NOT NULL,
    wo_prod_category         VARCHAR(15)   DEFAULT 'NORMAL',  -- NORMAL/B_TO_B/APQ/TRIAL/SMALL_LOT
    wo_spec_snapshot         JSONB,                           -- den/fil/type/ply/shade/twist saat approve
    wo_packing_snapshot      JSONB,                           -- box/paper tube/pallet (default master + override)
    wo_revision_no           INT           DEFAULT 0,
    wo_revision_reason       TEXT,
    wo_status                VARCHAR(20)   DEFAULT 'DRAFT',
    wo_created_by            BIGINT        NOT NULL,
    wo_created_at            TIMESTAMPTZ   DEFAULT NOW(),
    wo_updated_at            TIMESTAMPTZ   DEFAULT NOW()
);

CREATE TABLE wo_parameter (
    wop_id                   BIGSERIAL PRIMARY KEY,
    wop_wo_id                BIGINT        NOT NULL REFERENCES work_order(wo_id),
    wop_param_id             UUID          NOT NULL,          -- FK mst_parameter (costing)
    wop_value_ppc_num        DECIMAL(20,6),
    wop_value_ppc_text       TEXT,
    wop_value_ppc_flag       BOOLEAN,
    wop_value_pc_num         DECIMAL(20,6),
    wop_value_pc_text        TEXT,
    wop_value_pc_flag        BOOLEAN,
    wop_is_dual              BOOLEAN       DEFAULT FALSE,      -- true utk 8 param
    UNIQUE (wop_wo_id, wop_param_id)
);

CREATE TABLE wo_execution (
    woe_id                   BIGSERIAL PRIMARY KEY,
    woe_wo_id                BIGINT        NOT NULL REFERENCES work_order(wo_id),
    woe_date                 DATE          NOT NULL,
    woe_shift                CHAR(1)       NOT NULL,
    woe_param_id             UUID          NOT NULL,          -- FK mst_parameter (incl actual-only ACY/heater)
    woe_value_num            DECIMAL(20,6),
    woe_value_text           TEXT,
    woe_value_flag           BOOLEAN,
    woe_input_by             BIGINT        NOT NULL,
    woe_input_at             TIMESTAMPTZ   DEFAULT NOW(),
    UNIQUE (woe_wo_id, woe_date, woe_shift, woe_param_id)
);

CREATE TABLE wo_rm_allocation (
    wra_id                   BIGSERIAL PRIMARY KEY,
    wra_wo_id                BIGINT        NOT NULL REFERENCES work_order(wo_id),
    wra_crm_rm_id            BIGINT        NOT NULL,          -- FK cost_route_rm
    wra_rm_type              VARCHAR(10),                     -- PRODUCT/ITEM/GROUP
    wra_lot_no               VARCHAR(30)   NOT NULL,
    wra_rm_source            VARCHAR(10),                     -- STORE/CAPTIVE/MIXED
    wra_fresh_box            VARCHAR(5),                      -- Fresh/Box
    wra_shade_code           VARCHAR(30),
    wra_qty_allocated        DECIMAL(18,3) NOT NULL,
    wra_notes                TEXT
);
```

`WO_PRODUCTION_ACTUAL`, `WO_GRADE_ACTUAL`, `WO_ACTUAL_LOG` → lihat halaman 13 (model
produksi dua-sumbu) & halaman 12.
