# 13. Daily Performance — Efficiency, Waste, Downtime & Shift Entry

> **Baru di v1.1.** Halaman ini menggantikan daily report Excel:
> `TX_Performace_Daily_Report`, `Eff_*` (MC EFF grid), `Daily_Report_TWT_Based_Floor`,
> `Daily_Report_TWT_Second_Floor`, dan daily report Spinning (`SPG-1/SPG-2/PROD/WASTE/DG`).

## Latar Belakang

Concern utama manajemen adalah **machine efficiency, waste, dan idle/downtime**.
Saat ini semua metrik tersebut dihitung **di Excel atau manual** — tidak ada di Oracle.
Bobbin tracking Oracle hanya menyediakan jumlah bobbin per merge/machine/shift.

Konsekuensi arsitektur:

1. **Sistem PPC menjadi calculation engine efficiency** — formula pindah dari Excel ke aplikasi.
2. **Sistem PPC menjadi sistem input data shift-level** — operator input per shift
   (produksi, waste, downtime/idle, breaks, activity), bukan sekadar extend ETL.
3. **Output = dashboard interaktif** + tombol **Export to Excel** (bukan report engine
   pixel-perfect; template export mengikuti layout report existing).

---

## Shift Entry — Input Operator per Shift

Titik input tunggal: **entry operator di akhir tiap shift, per mesin**. Form dua bagian
karena granularity campur:

| Bagian | Granularity | Isi |
|---|---|---|
| **Level mesin** | machine + date + shift | Posisi running, running time (menit), downtime/idle event per posisi + reason, activity note |
| **Level WO/lot** | WO + date + shift | Qty produksi (prefill dari ETL bobbin sebagai suggest), waste per kategori, breaks, doff count, changeover failure |

Prinsip form:
- **Prefill dari ETL** — bobbin count dari Oracle muncul sebagai suggest; operator konfirmasi/koreksi
- **Cepat** — default kategori waste = 0, hanya isi yang ada
- Parameter mesin actual tetap di `WO_EXECUTION` (existing, tidak berubah)
- Reminder H+1 jika shift log belum lengkap

---

## Efficiency — Definisi & Formula (dari Excel existing)

Semua varian efficiency **wajib direproduksi**. Formula diambil dari file Excel aktual
(sudah di-reverse-engineer dan diverifikasi terhadap nilai report Juli 2026).
Semua metrik punya versi **Today** dan **Todate (MTD)**; Todate = agregasi dari daily snapshot.

### Basis Theoretical Production

```
theoretical_kg = positions × running_minutes × speed_mpm × denier / 9.000.000

(denier/9000 = gram per meter; × m/min × menit / 1000 = kg)
```

Dua varian denominator:
- **100% Production** — semua posisi nominal × waktu penuh (kapasitas nominal)
- **Running-adjusted** — hanya posisi & waktu yang benar-benar running (dikurangi idle/CO)

Sumber parameter: `PRODUCT_MACHINE_CAPACITY` (speed, positions) + denier produk (CPM_).
Running positions & running time: **input operator** (tidak ada di Oracle).

### SPG (per line × merge)

| Kode | Metrik | Formula |
|---|---|---|
| A | Total Throughput Possible | theoretical dari posisi running × waktu × speed × denier |
| B | Production Loss | Σ 6 kategori loss (kg) |
| C | Adjusted Throughput | `A − B` |
| D | Waste | Σ 8 kategori waste (kg) |
| E | Spump Production | `C − D` |
| F | 100% Production | theoretical kapasitas nominal penuh |
| G | Actual POY Doffed | qty doffed aktual (ditimbang / dari GROSS_BOBBINS) |
| I | **Yield %** | `E / C` |
| J | **Efficiency %** | `C / A` |
| K | **Plant Efficiency %** | `G / F` — bisa > 100% (variasi berat bobbin/denier) |
| — | **Machine Efficiency %** | per mesin per shift (grid MC EFF I/II/III/AVG) |
| M | Breaks / Ton | `total_breaks / (G dalam ton)` |
| Q | Change Over % | `(total_doff − CO_failure) / total_doff`, total_doff = full + revolving manual |

Analisis pendukung: Full bobbin % (by weight `full_kg/G`, by count), Small Production >2kg,
Downgrade B-Grade / C-Grade per reason (lihat bagian Downgrade).

> ⚠️ **SPG efficiency berbasis DOFFED (`GROSS_BOBBINS`), bukan transferred.**
> Realisasi pemenuhan WO tetap berbasis transferred. Lihat dual qty di halaman 5.

### TXT (per mesin × section × lot)

| Metrik | Formula |
|---|---|
| Prodn 100% | theoretical nominal |
| Prodn Rng (running-adjusted) | theoretical hanya waktu/posisi running |
| Prodn Actual | qty actual (dari ETL + koreksi operator) |
| **Production Efficiency %** | `Actual / Prodn 100%` |
| **Running Efficiency %** | `Actual / Prodn Rng` |
| **Break metrics** | yarn breaks per ton (original & inspection), running % |
| **MC EFF %** | per mesin per shift (grid I/II/III/AVG, satu bulan) |
| AX Grade Before % | AX sebelum inspection terhadap produksi |

Segmentasi summary: **Efficiency DTY** (versi *Excluding*: Power failure, B-to-B, APQ,
Trial & Small lot — dan *Including*), **Efficiency ACY** (rewinding AM→AX), **Efficiency ATY**.

### TWT (per mesin/autoclave × merge)

| Metrik | Formula |
|---|---|
| Prodn 100% | theoretical (today / merge-todate / machine-todate) |
| Prodn Actual | qty actual |
| **Production Efficiency %** | `Actual / Prodn 100%` |
| FQ % / AX % | dari grade actual |
| Downtime vs target | mis. Meerabah target breakdown 2.160 menit/bulan, lost-eff % |

> 📌 **Validasi rumus**: definisi break metrics TXT (kolom original vs inspection)
> dikunci bersama tim produksi sebelum development (Open Item).

---

## Klasifikasi Produksi — Excluding / Including

Report menyajikan efficiency versi *Excluding (Power failure, B-to-B, APQ, Trial & Small lot)*
dan *Including*. Implementasi:

- `wo_prod_category` di `WORK_ORDER`: `NORMAL / B_TO_B / APQ / TRIAL / SMALL_LOT`
- Power failure = downtime event dengan reason kategori `POWER_FAILURE`
  (flag `drm_is_exclude_from_eff`)
- Kalkulasi *Excluding* = exclude WO ber-kategori ≠ NORMAL + exclude loss power failure

---

## Waste

Taxonomy per area, dikelola via **master kategori** (configurable, bukan hardcode):

| Area | Kategori (seed awal) |
|---|---|
| SPG | Spinning, Take Up, Stripping, Paper Tube Cleaning, Upsets, Production Mix Change, Laboratory, Solid — masing-masing sub **Reguler / Upsets** |
| TXT | DTY waste, POY waste, Waste ACY/ATY, Cutting DTY Rapuh, Ateja Waste |
| TWT | per tipe mesin: Cops Winder, PT/ST, Assembly Winder, Carpet Twister, Bluemoon, Meerabah, SSM, ACY, Ply |

Penyajian: Kg + % terhadap produksi, versi **with/without upsets** (SPG),
**Excluding/Including trip** dan **Excluding B-to-B** (TXT). Semua Today + Todate.

Input: operator per shift, per WO (atau per mesin untuk waste yang tidak terikat lot).

---

## Downtime, Idle & Activity

- **Downtime/idle event**: per mesin (opsional per posisi & per WO), start–end / durasi menit,
  reason code, lost production (kg + %) — lost kg auto-calculated dari theoretical rate.
- **Reason master** per area (configurable):
  - TXT idle position: `XST`, `LB`, `TP`, `Fuse`, `Bowl`, …
  - SPG production loss: `Reguler`, `CPF`, `Utility`, `Electric`, `Power Plants`, `Others`
  - TWT: start up, MC off, ganti proses, breakdown, …
- **Activity log** naratif per mesin per shift (changeover, stop doff, thread up,
  premature doff kg/BB, perbaikan mekanik/elektrik) — menggantikan sheet `ACTIVITY`/`Reason`.
- Changeover yang sudah dimodelkan di `CHANGEOVER_EVENT` **tetap di sana**; downtime event
  dapat me-refer `ce_id` agar tidak dobel hitung.

---

## Downgrade Record (SPG)

Rekap downgrade harian per reason (menggantikan sheet `DG`):
B-Grade (Abnormal Properties, Inspection Reject, Flying Filament, Upsets, Small Bobbin
1,0–1,5 kg, Product Mix Change, OLT), C-Grade (Small Bobbin 0,5–1,0 kg), plus
reason Rm / CC / PC / BB / MB Fail. Implementasi memakai `waste_category_master`
dengan kategori tipe `DOWNGRADE` (satu master, dua tipe) — qty masuk `waste_actual`
dengan tipe `DOWNGRADE`.

---

## Efficiency Snapshot & Kalkulasi

Efficiency dihitung oleh service saat shift log final / ETL update, disimpan sebagai
snapshot agar dashboard cepat dan Todate konsisten:

- Scope snapshot: `MACHINE_SHIFT` → `MACHINE_DAY` → `AREA_DAY` (roll-up)
- Todate (MTD) = agregasi ulang dari komponen (Σ actual / Σ theoretical), **bukan** rata-rata persentase
- Snapshot menyimpan komponen (theoretical 100%, running-adjusted, loss, waste, actual)
  + hasil (production eff, running eff, plant eff, yield, breaks/ton)
- Re-calc otomatis jika data sumber dikoreksi (log koreksi tetap di `WO_ACTUAL_LOG`)

---

## Dashboard & Export

**Daily Performance Dashboard** (lihat juga halaman 10):
- KPI cards: Total Production (Today/MTD), Efficiency per area (DTY excl/incl, ACY, ATY, SPG, TWT), Waste %, Idle positions, OT hours
- Grid MC EFF per mesin per shift (heatmap bulanan — pengganti file `Eff_*`)
- Panel waste per kategori, panel downtime/idle per reason (pareto)
- Activity feed per mesin
- Drill-down: area → mesin → WO/lot → shift
- **Export to Excel** per tanggal dengan template menyerupai report existing

**Di luar scope halaman ini** (keputusan brainstorming Juli 2026):
- WIP POY per lokasi (Take Up / Machine / Lag Area) — pandangan inventory fisik; angka
  doffed-belum-transfer bila dibutuhkan = turunan `NOT_TRANSFER × weight` dari ETL, tanpa tabel baru
- WIP Chips pipeline (Issue Area → Silo → Crystaliser → Dryer → Extruder Hopper) — raw
  material conditioning, di luar scope PPC
- Overtime (jam): tampil di dashboard sebagai input `AREA_SHIFT_LOG`; sumber resmi HR
  menyusul (Open Item)

---

## Schema

Prefix registry baru: `msl_` machine_shift_log · `asl_` area_shift_log ·
`drm_` downtime_reason_master · `de_` downtime_event · `wcm_` waste_category_master ·
`wa_` waste_actual · `es_` efficiency_snapshot.

```sql
-- ─────────────────────────────────────────────
-- MACHINE_SHIFT_LOG — entry operator level mesin per date+shift
-- ─────────────────────────────────────────────
CREATE TABLE machine_shift_log (
    msl_id                   BIGSERIAL PRIMARY KEY,
    msl_machine_id           BIGINT        NOT NULL,
    msl_date                 DATE          NOT NULL,
    msl_shift                CHAR(1)       NOT NULL,   -- 1 / 2 / 3
    msl_positions_total      INT,                       -- posisi terpasang
    msl_positions_running    DECIMAL(8,2),              -- posisi running (bisa fraksi)
    msl_running_minutes      INT,                       -- menit running mesin
    msl_activity_note        TEXT,                      -- activity log naratif
    msl_status               VARCHAR(20)   DEFAULT 'DRAFT', -- DRAFT / FINAL
    msl_input_by             BIGINT        NOT NULL,
    msl_input_at             TIMESTAMPTZ   DEFAULT NOW(),
    msl_updated_at           TIMESTAMPTZ   DEFAULT NOW(),
    UNIQUE (msl_machine_id, msl_date, msl_shift)
);

-- ─────────────────────────────────────────────
-- AREA_SHIFT_LOG — entry level departemen (OT hours, catatan)
-- ─────────────────────────────────────────────
CREATE TABLE area_shift_log (
    asl_id                   BIGSERIAL PRIMARY KEY,
    asl_area                 CHAR(3)       NOT NULL,    -- TXT / SPG / TWT
    asl_date                 DATE          NOT NULL,
    asl_shift                CHAR(1),                    -- NULL = harian
    asl_ot_hours             DECIMAL(6,2),
    asl_notes                TEXT,
    asl_input_by             BIGINT        NOT NULL,
    asl_input_at             TIMESTAMPTZ   DEFAULT NOW(),
    UNIQUE (asl_area, asl_date, asl_shift)
);

-- ─────────────────────────────────────────────
-- DOWNTIME_REASON_MASTER — reason code per area (configurable)
-- ─────────────────────────────────────────────
CREATE TABLE downtime_reason_master (
    drm_id                   BIGSERIAL PRIMARY KEY,
    drm_area                 CHAR(3)       NOT NULL,    -- TXT / SPG / TWT
    drm_code                 VARCHAR(20)   NOT NULL,    -- XST / LB / TP / CPF / ...
    drm_name                 VARCHAR(100)  NOT NULL,
    drm_category             VARCHAR(20)   NOT NULL,    -- IDLE_POSITION / MACHINE_DOWN / PRODUCTION_LOSS
    drm_is_exclude_from_eff  BOOLEAN       DEFAULT FALSE, -- true utk POWER_FAILURE dsb
    drm_is_active            BOOLEAN       DEFAULT TRUE,
    drm_sort_order           INT           DEFAULT 0,
    UNIQUE (drm_area, drm_code)
);

-- ─────────────────────────────────────────────
-- DOWNTIME_EVENT — idle/downtime per mesin (opsional posisi & WO)
-- ─────────────────────────────────────────────
CREATE TABLE downtime_event (
    de_id                    BIGSERIAL PRIMARY KEY,
    de_machine_id            BIGINT        NOT NULL,
    de_wo_id                 BIGINT,                     -- nullable
    de_shift_log_id          BIGINT,                     -- FK machine_shift_log
    de_ce_id                 BIGINT,                     -- FK changeover_event (hindari dobel hitung)
    de_date                  DATE          NOT NULL,
    de_shift                 CHAR(1),
    de_position_no           VARCHAR(10),                -- untuk idle position
    de_reason_id             BIGINT        NOT NULL,     -- FK downtime_reason_master
    de_start_at              TIMESTAMPTZ,
    de_end_at                TIMESTAMPTZ,
    de_duration_min          INT,
    de_lost_kg               DECIMAL(12,3),              -- auto dari theoretical rate, editable
    de_notes                 TEXT,
    de_input_by              BIGINT        NOT NULL,
    de_input_at              TIMESTAMPTZ   DEFAULT NOW()
);

-- ─────────────────────────────────────────────
-- WASTE_CATEGORY_MASTER — taxonomy waste & downgrade per area
-- ─────────────────────────────────────────────
CREATE TABLE waste_category_master (
    wcm_id                   BIGSERIAL PRIMARY KEY,
    wcm_area                 CHAR(3)       NOT NULL,     -- TXT / SPG / TWT
    wcm_type                 VARCHAR(15)   NOT NULL,     -- WASTE / DOWNGRADE
    wcm_code                 VARCHAR(30)   NOT NULL,     -- SPINNING / TAKE_UP / DTY / POY / FLYING_FILAMENT / ...
    wcm_name                 VARCHAR(100)  NOT NULL,
    wcm_grade_target         VARCHAR(5),                 -- utk DOWNGRADE: B / C
    wcm_is_active            BOOLEAN       DEFAULT TRUE,
    wcm_sort_order           INT           DEFAULT 0,
    UNIQUE (wcm_area, wcm_type, wcm_code)
);

-- ─────────────────────────────────────────────
-- WASTE_ACTUAL — qty waste/downgrade per shift, input operator
-- ─────────────────────────────────────────────
CREATE TABLE waste_actual (
    wa_id                    BIGSERIAL PRIMARY KEY,
    wa_area                  CHAR(3)       NOT NULL,
    wa_machine_id            BIGINT,                     -- nullable (waste level area)
    wa_wo_id                 BIGINT,                     -- nullable (waste tidak terikat lot)
    wa_shift_log_id          BIGINT,                     -- FK machine_shift_log
    wa_date                  DATE          NOT NULL,
    wa_shift                 CHAR(1),
    wa_category_id           BIGINT        NOT NULL,     -- FK waste_category_master
    wa_qty_kg                DECIMAL(12,3) NOT NULL,
    wa_is_upset              BOOLEAN       DEFAULT FALSE, -- sub Reguler/Upsets (SPG)
    wa_notes                 TEXT,
    wa_input_by              BIGINT        NOT NULL,
    wa_input_at              TIMESTAMPTZ   DEFAULT NOW()
);

-- ─────────────────────────────────────────────
-- EFFICIENCY_SNAPSHOT — hasil kalkulasi engine, basis dashboard
-- ─────────────────────────────────────────────
CREATE TABLE efficiency_snapshot (
    es_id                    BIGSERIAL PRIMARY KEY,
    es_area                  CHAR(3)       NOT NULL,     -- TXT / SPG / TWT
    es_scope                 VARCHAR(20)   NOT NULL,     -- MACHINE_SHIFT / MACHINE_DAY / AREA_DAY
    es_machine_id            BIGINT,                     -- NULL utk AREA_DAY
    es_wo_id                 BIGINT,                     -- NULL utk agregat
    es_date                  DATE          NOT NULL,
    es_shift                 CHAR(1),                    -- NULL utk _DAY
    es_segment               VARCHAR(10),                -- DTY / ACY / ATY / POY / NULL
    es_is_excluding          BOOLEAN       DEFAULT FALSE, -- versi Excluding
    es_qty_theoretical_100   DECIMAL(14,3),
    es_qty_theoretical_rng   DECIMAL(14,3),
    es_qty_loss              DECIMAL(14,3),
    es_qty_waste             DECIMAL(14,3),
    es_qty_actual            DECIMAL(14,3),              -- SPG: doffed
    es_eff_production_pct    DECIMAL(6,2),
    es_eff_running_pct       DECIMAL(6,2),
    es_eff_plant_pct         DECIMAL(6,2),               -- SPG
    es_yield_pct             DECIMAL(6,2),               -- SPG
    es_waste_pct             DECIMAL(6,2),
    es_breaks_count          INT,
    es_breaks_per_ton        DECIMAL(8,2),
    es_calc_at               TIMESTAMPTZ   DEFAULT NOW(),
    UNIQUE (es_area, es_scope, es_machine_id, es_wo_id, es_date, es_shift, es_segment, es_is_excluding)
);
```

**Perubahan tabel existing** (DDL lengkap di halaman 12):

```sql
-- WORK_ORDER: klasifikasi produksi utk versi Excluding/Including
ALTER TABLE work_order ADD COLUMN wo_prod_category VARCHAR(15) DEFAULT 'NORMAL';
       -- NORMAL / B_TO_B / APQ / TRIAL / SMALL_LOT

-- WO_PRODUCTION_ACTUAL: dual qty SPG + KPI shift dari operator
ALTER TABLE wo_production_actual
    ADD COLUMN wpa_qty_doffed_kg      DECIMAL(18,3),  -- SPG: GROSS × weight (basis efficiency)
    ADD COLUMN wpa_qty_transferred_kg DECIMAL(18,3),  -- SPG: TRANSFERRED × weight (basis fulfillment)
    ADD COLUMN wpa_breaks_count       INT,            -- input operator
    ADD COLUMN wpa_doff_full_count    INT,
    ADD COLUMN wpa_doff_manual_count  INT,            -- revolving manual
    ADD COLUMN wpa_co_failure_count   INT;            -- change over failure
```

---

## Mapping Report Excel → Sistem

| Report / Sheet | Digantikan oleh |
|---|---|
| `TX_Performace` Summary | Dashboard KPI cards + `efficiency_snapshot` (AREA_DAY) |
| `TX` Effcy. TX1/TX2 | `efficiency_snapshot` (MACHINE_SHIFT/DAY) per lot |
| `Eff_*` grid MC EFF | Heatmap MC EFF (snapshot MACHINE_SHIFT sebulan) |
| `TX` waste & pck prodn | `waste_actual` + `wo_grade_actual` (FQ/AX dari packing) |
| `TX` Idle TX1/TX2 | `downtime_event` kategori IDLE_POSITION |
| `TX/TWT/SPG` Activity / Reason | `machine_shift_log.activity_note` + `downtime_event` |
| `TWT` daily per autoclave/mesin | `efficiency_snapshot` + `wo_production_actual` |
| `TWT` WASTE | `waste_actual` per tipe mesin |
| `TWT` DOWNTIME MEERABAH | `downtime_event` + target di `downtime_reason_master`/config |
| `SPG-1/SPG-2` | shift entry + `efficiency_snapshot` + `waste_actual` |
| `SPG` PROD | `efficiency_snapshot` (Plant/Machine Eff) |
| `SPG` WASTE | `waste_actual` rollup per line, with/without upsets |
| `SPG` DG | `waste_actual` tipe DOWNGRADE |
| `SPG` Report (WIP POY + Chips) | **Di luar scope** (lihat catatan di atas) |
