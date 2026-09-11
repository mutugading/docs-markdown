# Fase 2 — Raw Material Cost: Temuan

| Field | Value |
|---|---|
| Periode | 202608 (Agustus 2026) |
| Snapshot | 2026-09-09 09:51 WIB (lihat `snapshot_stamp_202608.md`) |
| Mode | READ-ONLY — nol write, nol trigger recompute |
| Query | `cmp_01_raw_material_cost_202608.sql` |
| Script join | `build_phase2_v2.py` |
| Output | `out/cmp_01_rm_cost_matrix_202608.csv` (1.050 baris) · `out/cmp_01_rm_legacy_orphan_202608.csv` (574 baris) |
| Sumber legacy | `CST_GRP_CONSUMP_HEAD` @ (2026, 8) — 924 baris |
| Sumber baru | `cst_rm_cost` @ `period='202608'` — 350 baris, + `cst_rm_group_head_period` |

> **Revisi.** Versi pertama laporan ini memakai jembatan ERP item code dan
> menghasilkan 19 group ambigu + 38 tanpa padanan. Kunci join yang benar
> ditemukan saat Fase 3 (lihat §1.1) dan menghasilkan crosswalk 350/350 tanpa
> ambiguitas. Seluruh angka di bawah ini sudah memakai kunci yang benar.

---

## 1. Empat koreksi terhadap asumsi prompt

### 1.1 Kunci join antar sistem — arti nama kolom bertukar

Ini kesalahan yang paling mudah terjadi dan paling mahal:

| Legacy `CST_GRP_HEAD` | Nilai contoh | Padanan goapps |
|---|---|---|
| `CGH_SYS_ID` | `202006002` | **`group_code`** |
| `CGH_GROUP_CODE` | `PIG0000005` | **`group_name`** |

Nama kolomnya **bertukar makna** antar sistem. Join `group_code = group_code`
menghasilkan **0 match**: `CGCH_GROUP_CODE` legacy berisi kode ERP/spec
(`PIG0000005`, `BLACK FR`, `160/36 RSD`), sementara `group_code` goapps berpola
`YYYYMM`+seq — dan pola itu ada di `CGH_SYS_ID`.

Kunci yang benar untuk tabel periode:

```
CST_GRP_CONSUMP_HEAD.CGCH_CGH_SYS_ID  ==  cst_rm_group_head.group_code
```

Prompt menyuruh *"join dari route via `group_code`, BUKAN `group_head_id`"* —
itu benar **di dalam sisi baru**, tapi menyesatkan kalau dibaca sebagai kunci
lintas sistem.

### 1.2 `CGCH_COST` kosong — ACTUAL harus dari `CGCH_LANDED_COST`

Fill rate 924 baris legacy 202608:

| Kolom | Non-null | Dipakai sebagai |
|---|---:|---|
| `CGCH_COST` | **0** | — (kosong total) |
| `CGCH_LANDED_COST` | 924 | **ACTUAL** ↔ `cost_val` |
| `CGCH_MARKET_RATE1` | 924 | **SELLING** ↔ `cost_mark` |
| `CGCH_MARKET_RATE2` | 924 | — (peran belum jelas) |
| `CGCH_MARKET_RATE1_FIX` | 31 | override SELLING |
| `CGCH_SIMULATION_RATE` | **11** | FORECAST ↔ `cost_sim` |
| `CGCH_STOCK_RATE` | 924 | input tier |
| `CGCH_LAST_PURC_PRICE` | 281 | input tier |

### 1.3 `valuation_legacy_charge_rate` tidak ada

Kolom itu **tidak ada** di `cst_rm_cost` (46 kolom, diperiksa lengkap). Yang ada:
`cl_rate`/`sl_rate`/`fl_rate`/`cr_rate`/`sr_rate`/`pr_rate` (tier valuation) dan
`sp_rate`/`pp_rate`/`fp_rate` (tier marketing).

Tambahan: `valuation_flag_v2` dan `marketing_flag_v2` di `cst_rm_cost`
**NULL untuk seluruh 350 baris** 202608. Tier yang benar-benar dipakai dibawa
`flag_valuation_used` / `flag_marketing_used`. Jangan pakai kolom `_v2`.

### 1.4 Konfigurasi group sekarang period-scoped

Sesuai Fase 1 §2.1, sumber konfigurasi = `cst_rm_group_head_period` @ 202608
(migration 000503–000505), bukan anchor. Untuk 202608 sudah dibuktikan
snapshot == anchor (selisih 0 di 16 kolom), jadi angkanya sama — tapi snapshot
yang dipakai karena itu yang dibaca engine.

---

## 2. Crosswalk — 350/350, tanpa ambiguitas

| Metrik | Nilai |
|---|---:|
| Legacy group ber-rate 202608 | 924 |
| goapps group ber-rate 202608 | 350 |
| **Match** | **350 (100% sisi baru)** |
| goapps tanpa padanan legacy | **0** |
| Legacy tanpa padanan goapps | 574 |

Kunci ini 1:1 — nol fan-out, nol ambiguitas. Seluruh 350 group sisi baru bisa
dibandingkan.

### 2.1 574 legacy-only sudah terurai

| Bucket | Group | Kategori |
|---|---:|---|
| **Tanpa item aktif** — placeholder spec POY (`CGI_IS_DUMMY='DUMMY'`) | **561** | `KNOWN-GAP` |
| **Punya item aktif** → kandidat gap migrasi nyata | **13** | `SCOPE` |

Diverifikasi independen lewat query Oracle A4: dari 924 group legacy 202608,
584 tidak punya item aktif dan 340 punya. Konsisten: 561 + 23 (matched tanpa
item aktif) = 584 ✔ · 13 + 327 (matched dengan item aktif) = 340 ✔

Contoh placeholder: `160/36 RSD` → `POY 160/36/RND/RSD/SIM/NS/1/O`. Ini spesifikasi
POY, bukan RM group — memang tidak pernah dimigrasikan sebagai RM group.
**Yang perlu ditindaklanjuti hanya 13 group** (`has_active_items = Y` di
`out/cmp_01_rm_legacy_orphan_202608.csv`).

### 2.2 Catatan tentang jembatan item code

Jembatan ERP item code (`CGI_ITEM_CODE` ↔ `cst_rm_group_detail.item_code`) tetap
berguna sebagai verifikasi silang, tapi **lossy**: 5 item riil terdaftar di lebih
dari satu group di sisi baru, sehingga 19 group jadi ambigu.

| `item_code` | Group | Group codes |
|---|---:|---|
| `CHP0000036` | 3 | `202006022`, `202410800`, `202411806` |
| `PIG0000024` | 3 | `202007158`, `202007593`, `202007594` |
| `CHP0000040` | 2 | `202401745`, `202411805` |
| `PIG0000005` | 2 | `202007204`, `202007205` |
| `PIG0000016` | 2 | `202007167`, `202007600` |

Ini **known-gap "item multi-group constraint conflict"** yang disebut prompt —
terkonfirmasi. Dampaknya sekarang terbatas pada kualitas master data, **tidak
lagi memblokir perbandingan** karena kunci join tidak melewatinya.

---

## 3. Matrix hasil — klasifikasi per cost type

350 group × 3 cost type = 1.050 baris. Seluruhnya terklasifikasi.

| Klasifikasi | ACTUAL | SELLING | FORECAST |
|---|---:|---:|---:|
| `match` (selisih persis 0) | 89 | 67 | 0 |
| `match-rounding` (≤ 5e-7, artefak presisi) | 124 | 67 | 0 |
| `match-both-zero` | 32 | 15 | 0 |
| `minor` (< 0,1%, riil) | 2 | 3 | 0 |
| **`material` (≥ 0,1%)** | **78** | **156** | 11 |
| `new-zero-flag-none` | 25 | 42 | 0 |
| `missing-in-legacy` | 0 | 0 | 339 |
| **Total** | **350** | **350** | **350** |

### 3.1 ACTUAL — 247 dalam toleransi, 78 material

`match` 89 + `match-rounding` 124 + `match-both-zero` 32 + `minor` 2 =
**247 dalam toleransi (70,6%)**. **78 material (22,3%)**. 25 nol karena tier
tidak resolve.

**`match-rounding` bukan selisih data.** Legacy menyimpan ~30 angka signifikan,
sisi baru `numeric` 6 desimal:

```
group 202006004   legacy 14.7265708915145005370569280343716433942   new 14.726571
group 202006003   legacy 3.5                                        new 3.500000
```

Hanya **2 group** yang selisihnya riil-tapi-kecil (< 0,1%). Jadi: **245 group
setara persis atau hanya beda pembulatan.**

### 3.2 Penyebab dominan ACTUAL sudah terlokalisasi: tier `PR`

| tier `flag_valuation_used` | material | total | % material |
|---|---:|---:|---:|
| **`PR`** (PO rate) | **65** | 106 | **61,3%** |
| `SL` (stores-landed) | 6 | 127 | 4,7% |
| `CL` (cons-landed) | 6 | 59 | 10,2% |
| `FL` | 1 | 1 | 100% |
| `NONE` | 0 | 57 | 0% |

**65 dari 78 selisih material ACTUAL (83%) ada di group ber-tier `PR`**, dan
tier `PR` sendiri 61% material — sepuluh kali lipat `SL` (4,7%).

Tier `CL` dan `SL` berbasis landed cost dari consumption/stock, yang sejalan
dengan `CGCH_LANDED_COST` di legacy, dan memang sangat cocok. Tier `PR`
berbasis PO rate — sumber angka yang berbeda secara konseptual.

Hipotesis (belum dieliminasi, target Fase 6): engine baru jatuh ke PO rate
ketika data konsumsi periode tidak tersedia, sedangkan legacy tetap memakai
landed cost. Klasifikasi sementara **`DATA` atau `ENGINE`, belum dipisahkan** —
belum boleh disebut defect sampai dibuktikan.

### 3.3 25 group: legacy punya rate, sisi baru nol karena tier `NONE`

Kategori tersendiri, bukan selisih numerik biasa: `flag_valuation_used='NONE'`
berarti tier tidak resolve sama sekali sehingga rate jadi 0, padahal legacy
punya angka riil. Ini bentuk konkret known-gap *"Finance-blocked group-0 rate"*.

Contoh (10 terbesar, ACTUAL):

| group | nama | legacy rate | new rate |
|---|---|---:|---:|
| 202006084 | DYE0000006 | 63,450487 | 0 |
| 202006014 | DYE0000014 | 62,432132 | 0 |
| 202006015 | PIG0000038 | 40,339380 | 0 |
| 202007182 | PV FAST RED BNP | 32,900000 | 0 |
| 202006002 | PIG0000005 | 29,192788 | 0 |
| 202006011 | CHM0000098 | 14,405066 | 0 |
| 202006051 | DYE0000001 | 12,347779 | 0 |
| 202006023 | PIG0000016-CLA | 9,358111 | 0 |
| 202006054 | PIG0000024-CLA | 9,313075 | 0 |
| 202006010 | PIG0000016 | 8,041407 | 0 |

Di SELLING kategori ini lebih luas lagi: **42 group**.

Catatan: dari 57 group ber-`NONE`, 32 memang nol di dua sisi (wajar), 25 nol
hanya di sisi baru.

### 3.4 SELLING jauh lebih buruk — dilaporkan terpisah

**156 material dari 350** (vs 78 di ACTUAL), plus 42 nol-karena-`NONE`. Dalam
toleransi hanya 152. Sesuai aturan prompt, SELLING **tidak digabung** dengan
ACTUAL.

Belum ditelusuri sampai layer penyebab — itu Fase 6. Klasifikasi sementara
**`UNKNOWN`**; tidak saya beri penjelasan karangan.

### 3.5 FORECAST tidak bisa dibandingkan — `SCOPE`

| Sisi | Kondisi 202608 |
|---|---|
| Legacy `CGCH_SIMULATION_RATE` | **11 non-null dari 924** group |
| goapps `cost_sim` | **0 untuk seluruh 350 baris** |
| goapps `flag_simulation_used` | `CONS` untuk seluruh 350 baris |

Track FORECAST praktis tidak dipakai di **kedua** sistem. 11 baris `material`
semata karena legacy punya angka sementara sisi baru nol.

Anomali yang perlu dicatat: `flag_simulation_used='CONS'` untuk seluruh 350
baris padahal `cost_sim=0` semuanya — tier resolve ke CONS tapi hasilnya nol.
Belum dikonfirmasi penyebabnya.

Rekomendasi: keluarkan FORECAST dari angka utama, sebutkan sebagai
tidak-dalam-cakupan dengan alasan ini.

---

## 4. Ringkasan untuk Executive Summary

| Scope | Objek | Match dalam toleransi | Selisih material | Tidak bisa dibandingkan |
|---|---:|---:|---:|---:|
| RM cost — ACTUAL | 350 group | 247 | 78 | 25 |
| RM cost — SELLING | 350 group | 152 | 156 | 42 |
| RM cost — FORECAST | 350 group | 0 | 11 | 339 |

"Tidak bisa dibandingkan" untuk ACTUAL/SELLING = `new-zero-flag-none`
(tier tidak resolve); untuk FORECAST = legacy tidak punya angka.

Di luar 350 itu: **13 legacy group** punya item aktif tapi tidak ada di sisi
baru sama sekali (`SCOPE`), dan **561** placeholder spec (`KNOWN-GAP`).

---

## 5. Temuan yang perlu keputusan

1. **65 group ACTUAL material dengan tier `PR`.** Apakah fallback ke PO rate
   memang perilaku yang diinginkan ketika data konsumsi tidak ada, atau bug
   tier resolution? Pertanyaan ke Finance + engineering.
2. **25 group ACTUAL (42 SELLING) nol karena tier `NONE`** padahal legacy punya
   rate riil. Perlu keputusan Finance: group ini memang di-block, atau
   konfigurasi tier-nya perlu dilengkapi?
3. **13 legacy group dengan item aktif** tidak ada di sisi baru. Memang
   di-retire, atau terlewat migrasi?
4. **SELLING 156 material** — perlu Fase 6 sebelum bisa dijelaskan. Jangan
   dipresentasikan sebagai angka final.
5. **FORECAST**: konfirmasi boleh dikeluarkan dari angka utama sebagai `SCOPE`.
6. **5 item multi-group** (§2.2) — perlu dibereskan di master data meski tidak
   lagi memblokir recon.

---

## 6. Definition of Done — Fase 2

- [x] Sumber legacy RM cost dipakai sesuai Fase 0, `CGCH_COST` vs
      `CGCH_LANDED_COST` diputuskan dengan bukti fill rate
- [x] Konfigurasi group dibaca period-aware (`cst_rm_group_head_period` 202608)
- [x] Kunci join antar sistem dibuktikan, bukan diasumsikan; crosswalk 350/350
      tanpa fan-out
- [x] Matrix per group × cost type: legacy rate, new rate, selisih absolut,
      selisih %, klasifikasi
- [x] Seluruh 1.050 baris terklasifikasi, nol tanpa kategori
- [x] Artefak presisi dipisahkan sebagai kelas sendiri (`match-rounding`)
- [x] SELLING & FORECAST dilaporkan terpisah dari ACTUAL
- [x] CSV tersimpan di `out/`, query rerunnable
- [x] Nol statement write, nol trigger recompute
