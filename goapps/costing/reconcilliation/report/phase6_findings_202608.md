# Fase 6 — FG Calculation Breakdown & Atribusi Layer Penyebab

| Field | Value |
|---|---|
| Periode | 202608 (Agustus 2026) |
| Snapshot | 2026-09-09 |
| Mode | READ-ONLY — nol write, nol trigger recompute |
| Query | `cmp_05_fg_calculation_breakdown_202608.sql` |
| Script | `build_phase6.py` |
| Output | `out/cmp_05_fg_breakdown_202608.csv` (20.400 baris, satu per cost type × produk, memuat `cause_layer` + nilai kedua sisi per layer) |

---

## 1. Alat yang dipakai — dan yang paling berguna ternyata belum disebut prompt

Empat kolom breakdown di `cst_product_cost`, semuanya terisi **17.611/17.611**
baris ACTUAL 202608:

| Kolom | Bentuk | Isi |
|---|---|---|
| `cpc_param_snapshot` | object, ~105 key | nilai parameter & komponen |
| `cpc_rm_cost_detail` | **array** of `{ref_code, rm_type, ratio, unit_cost, contribution, route_level}` | rate RM yang benar-benar dipakai engine |
| `cpc_cost_by_level` | **array** of `{level, product_sys_id, rm_cost, conversion}` | dekomposisi per level DAG — dipakai juga Fase 7 |
| **`cpc_formula_trace`** | **array** of `{formula_code, expression, inputs, output, result_param_code}` | **jejak evaluasi formula** |

`cpc_formula_trace` adalah alat atribusi utama fase ini dan **tidak disebut
prompt sama sekali**. Ia memuat ekspresi formula persis seperti yang dievaluasi
engine, beserta output-nya — jadi rantai dependensi bisa dibaca dari data, bukan
dari dokumentasi.

Catatan: prompt menyebut `cpc_rm_cost_detail` dan `cpc_param_snapshot` sebagai
"jsonb"; keduanya benar, tapi `cpc_rm_cost_detail` adalah **array**, bukan
object — jadi harus di-`jsonb_array_elements`, tidak bisa di-`->>` langsung.

---

## 2. Peringatan prompt yang TIDAK terbukti — dan itu kabar baik

Prompt: *"`cpc_rm_cost_detail` (jsonb — ini yang match UI, berisi rate yang
benar-benar dipakai engine saat calc; rate di `cst_rm_cost` sekarang bisa sudah
berbeda). … Kalau keduanya beda, laporkan dua-duanya — selisih antar keduanya
sendiri adalah temuan."*

Diuji atas seluruh baris GROUP:

| | Nilai |
|---|---:|
| Baris `rm_type='GROUP'` di `cpc_rm_cost_detail` | 25.726 |
| Resolve ke `cst_rm_cost` 202608 | **25.726 (100%)** |
| **Identik** | **25.726 (100%)** |
| **Berbeda** | **0** |
| Tidak resolve | 0 |

**Nol selisih.** Penyebabnya jelas: 202608 di-recompute **2026-09-09 09:01–09:13**
— setelah rate RM ditetapkan. Jadi untuk periode ini "apa yang user lihat" ==
"apa yang seharusnya", dan tidak ada temuan di sumbu itu.

Ini temuan negatif yang tetap penting dilaporkan: kekhawatiran prompt valid
secara desain, tapi tidak materialisasi di 202608. Ia **akan** materialisasi
kalau rate RM diubah tanpa recompute — misalnya setelah Paket 01/02 remediasi
dijalankan.

---

## 3. Rantai formula yang terbaca dari engine

```
F_YARN_RM_LANDED    RM_LANDED_COST = RM_RATE                    <-- KOLAPS (§4.1)
F_YARN_RM_NORMS     RM_NORMS = 1 + WASTE_PERC - RP_DOZING/100
F_YARN_POWER_KG     POWER_PER_KG = IS_SPIN_POOL_MODEL > 0
                        ? SPIN_POWER_MONTH / SPIN_POY_PRODUCTION ... : ...
F_YARN_TOTAL_FIXED  TOTAL_FIXEDCOST_PER_KG = POWER_PER_KG + MANPOWER_PER_KG
                                           + OVERHEAD_PER_KG + SPARESCOST_PER_KG
F_YARN_CONV_CAP     ONLY_CONV_CAP_PACK_EXCL_MB = TOTAL_FIXEDCOST_PER_KG
                        + CAPTIVE_PACK_COST + OIL_COST + INTERMINGLING
                        + SPECIAL_COST_1                        <-- EXCL MB (§4.2)
F_YARN_CAP_PRE_QL   CAPTIVE_COST_BEFORE_QLOSS = RM_NORMS * RM_LANDED_COST
                                              + ONLY_CONV_CAP_PACK_EXCL_MB
F_YARN_QLOSS_CAP    QLTY_LOSS_CAPTIVE_COST = BC_VAL_LOSS_CAPTIVE + NON_STD_VALUE_LOSS
F_YARN_CAP_FINAL    CAPTIVE_COST_QLTY_LOSS = CAPTIVE_COST_BEFORE_QLOSS
                                           + QLTY_LOSS_CAPTIVE_COST
F_YARN_VB1_DEL      VOLUME_BUCKET_1_DEL_COST = DELIVERY_COST_QLTY_LOSS
                                             + VOLUME_BUCKET_1_LOSS
F_YARN_NET_PROD     NET_PRODUCTION = NO_OF_POSITION * MC_SPEED
                        * (MC_EFFICIENCY/100) * 1440 * DENIER / 9000000
```

Prompt menyebut dekomposisi `conversion = FIXED_TOTAL + WASTE_VAL + COST_OTHERS`.
Untuk **yarn** yang tidak persis begitu — bentuk sebenarnya
`ONLY_CONV_CAP_PACK_EXCL_MB = FIXED_TOTAL + PACK + OIL + INTERMINGLING +
SPECIAL_COST_1`, dan waste masuk lewat `RM_NORMS`, bukan sebagai suku terpisah.
Dekomposisi yang disebut prompt cocok untuk **MB** (§6).

---

## 4. Dua perbedaan DEFINISI — bukan perbedaan data

### 4.1 Landed cost kolaps jadi rate

`F_YARN_RM_LANDED` isinya literal `RM_LANDED_COST = RM_RATE`.

| Sisi | Kondisi |
|---|---|
| Legacy | membedakan **TOP 55 (RM Rate)** dan **TOP 56 (RM Landed cost)** |
| Legacy ACTUAL | berbeda di **2.275 dari 7.460 (30,5%)** |
| Legacy SELLING | berbeda di **4.603 dari 14.622 (31,5%)** |
| goapps | `RM_LANDED_COST == RM_RATE` di **13.421 dari 13.422 (100%)**, ACTUAL & SELLING |

Jadi **uplift landed cost (duty / clearing / freight) yang legacy terapkan ke
~31% produk hilang seluruhnya di sisi baru.**

Konsekuensi untuk pemetaan — dan ini mengoreksi tafsir awal saya: field yang
dinamai `RM_RATE` di sisi baru sebenarnya sebanding dengan **TOP 56 (landed)**,
bukan TOP 55 (rate). Kecocokan lawan TOP 56 = 8,5%; lawan TOP 55 hanya 0,4%.
Memakai TOP 55 sebagai dasar atribusi salah mengaitkan 99,7% kasus ke "RM_RATE"
— atribusi di §5 sudah memakai TOP 56.

Klasifikasi: **`ENGINE`**.

### 4.2 MB cost masuk di legacy, tidak di sisi baru

Nama parameternya sendiri sudah mengumumkannya: `ONLY_CONV_CAP_PACK_**EXCL_MB**`.

Diuji dengan merekonstruksi legacy TOP 94 (CapCost before Q.Loss) dua cara:

| Rekonstruksi | ACTUAL (n=6.773) | SELLING (n=13.270) |
|---|---:|---:|
| `RM_NORM×RM_LANDED + FIXED + PACK + OIL` (tanpa MB) | 18,4% | 17,0% |
| **+ MB_COST** | **45,8%** | **44,6%** |

Dengan MB cost, kecocokan **2,5× lebih baik**. Jadi legacy memang memasukkan MB
cost ke pre-quality-loss captive cost; sisi baru sengaja mengeluarkannya. Sisa
ketidakcocokan (54%) berasal dari komponen lain yang belum saya rekonstruksi
(intermingling, special cost, steam/CNG, softner, drying).

Klasifikasi: **`ENGINE`**.

---

## 5. Atribusi layer penyebab — hasil utama Fase 6

Aturan: untuk tiap produk yang selisih material di metric final
(`CAP_WITH_QLOSS` / TOP 105), tentukan layer **pertama** menurut urutan
dependensi §3 yang sudah selisih. Layer hulu menjelaskan hilir.

### ACTUAL

| Layer penyebab | In-scope 202608 (1.269) | Di luar scope (5.709) |
|---|---:|---:|
| **RM_COST** | **730 (57,5%)** | **5.604 (98,2%)** |
| **PACKING** | **349 (27,5%)** | 18 (0,3%) |
| PARAMETER_NORM | 106 (8,4%) | 13 (0,2%) |
| PARAMETER (waste %) | 71 (5,6%) | 0 |
| FIXED_COST | 12 (0,9%) | 22 (0,4%) |
| SETARA | 1 (0,1%) | 11 (0,2%) |
| Tidak bisa dibandingkan | 0 | 41 (0,7%) |
| **DEFINISI_AGREGAT** | **0** | **0** |

### SELLING

| Layer penyebab | In-scope (1.270) | Di luar scope (12.152) |
|---|---:|---:|
| **RM_COST** | **1.270 (100%)** | **12.150 (100%)** |
| FIXED_COST | 0 | 2 |

### 5.1 Yang paling penting dari tabel ini

**Nol kasus `DEFINISI_AGREGAT`.** Tidak ada satupun produk di mana semua input
setara tapi hasil akhirnya berbeda. Artinya **formula engine konsisten** — yang
berbeda adalah **input**-nya. Ini membalik kekhawatiran Fase 5 bahwa layer cost
turunan "rusak": layer turunan berbeda semata karena mewarisi selisih dari hulu.

**Selisih tidak tersebar acak.** Untuk populasi in-scope 202608, 85% selisih
terjelaskan hanya oleh dua layer: RM cost (57,5%) dan packing (27,5%).

**SELLING 100% RM cost** — konsisten dengan Fase 2 dan Fase 5: RM SELLING nol di
sisi baru, jadi tidak ada layer lain yang perlu diperiksa.

---

## 6. Dua signature defect yang sangat spesifik

### 6.1 Packing sisi baru ~2× legacy — 349 dari 349

| Statistik rasio `CAPTIVE_PACK_COST` baru / legacy TOP 42 | Nilai |
|---|---:|
| n | 349 |
| min | 1,881 |
| p25 | 1,957 |
| **median** | **1,975** |
| p75 | 1,991 |
| max | 5,664 |
| new > legacy | **349 dari 349** |

Rasio yang terkonsentrasi sedekat itu ke **2,0** — dengan p25–p75 hanya 1,96–1,99
dan nol kasus yang lebih rendah — adalah signature **double-count**, bukan
sebaran selisih data. Formulanya:

```
CAPTIVE_PACK_COST = CAPTIVE_BOX_WT > 0
    ? (CAPTIVE_NO_OF_BOB * CAPTIVE_BOB_RATE + CAPTIVE_BOX_RATE) / CAPTIVE_BOX_WT
    : ...
```

Kandidat penyebab: `CAPTIVE_NO_OF_BOB` atau `CAPTIVE_BOX_WT` terisi separuh /
dua kali dari yang legacy pakai. Perlu satu penelusuran dev — ini paling
mendekati "bug yang bisa langsung diperbaiki" dari seluruh recon.

Delivery pack lebih parah lagi pada sampel yang saya periksa (legacy 0,0078 vs
baru 0,0661 ≈ 8,5×), jadi kedua jalur packing perlu diperiksa.

### 6.2 RM landed cost sisi baru ~70% legacy

| Statistik rasio `RM_LANDED_COST` baru / legacy TOP 56 (kasus RM_COST) | Nilai |
|---|---:|
| n | 716 |
| p25 | 0,648 |
| **median** | **0,703** |
| p75 | 0,747 |
| new < legacy | **682 dari 716** |
| new = 0 | 14 |

Arah konsisten: sisi baru **lebih rendah ~30%**. Ini menyatu dengan tiga temuan
sebelumnya: uplift landed hilang (§4.1), tier `PR` fallback ke PO rate (Fase 2),
dan 25 group nol karena tier `NONE` (Fase 2).

### 6.3 Dampak ke angka akhir

Rasio `CAP_WITH_QLOSS` baru/legacy, dipecah per layer penyebab (ACTUAL in-scope):

| Layer | n | p10 | median | p90 |
|---|---:|---:|---:|---:|
| RM_COST | 730 | 0,610 | **0,774** | 0,853 |
| PACKING | 349 | 0,843 | 0,940 | 1,005 |
| PARAMETER_NORM | 106 | 0,726 | 0,885 | 0,953 |
| PARAMETER | 71 | 0,779 | 0,868 | 0,972 |
| FIXED_COST | 12 | 1,008 | 1,025 | 1,031 |

**Sistem baru menghasilkan FG cost lebih rendah dari legacy di hampir semua
layer**, paling besar pada kasus RM (median 0,774 → 23% lebih rendah). Hanya
kasus FIXED_COST yang sedikit lebih tinggi (1,025).

Ini arah yang perlu disampaikan jelas ke Finance: kalau sistem baru dipakai apa
adanya untuk 202608, **FG cost akan lebih rendah ~23% pada produk yang selisih
karena RM.**

---

## 7. Dekomposisi MB — formula prompt terverifikasi, dengan dua koreksi

Dibaca dari `cpc_formula_trace` produk bertipe MB (`cpm_product_type_id = 29`):

| Prompt | Engine sebenarnya | Status |
|---|---|---|
| `MB_FINAL_COST = MB_RM_COST + MB_CONV_COST` | `IS_BOUGHTOUT == 1 ? MB_RM_COST : MB_RM_COST + MB_CONV_COST` | **ada cabang boughtout** |
| `MB_CONV_COST = MB_FIXED_TOTAL + MB_WASTE_VAL + MB_COST_OTHERS` | `(MB_NO_PROCESS * MB_FIXED_TOTAL) + MB_COST_OTHERS + MB_WASTE_VAL` | **ada pengali `MB_NO_PROCESS`** |
| `MB_FIXED_TOTAL = MACHINE_MB_FIXED_TOTAL / MB_NET_PROD` | `MB_NET_PROD > 0 ? MACHINE_MB_FIXED_TOTAL / MB_NET_PROD : 0` | ✅ sesuai (+ guard) |
| `MB_NET_PROD = MB_THROUGHPUT * (MB_EFFICIENCY/100) * MB_PROD_PER_DAY` | identik | ✅ persis |
| `MB_WASTE_VAL = (MB_RM_COST/(1-MB_WASTE/100)) * (MB_WASTE/100)` | identik | ✅ persis |
| *(tidak disebut prompt)* | `MB_COST_OTHERS = ((MB_RM_COST + MB_WASTE_VAL + MB_FIXED_TOTAL) * ((MB_QUALITY_LOSS + MB_DEV_EXPENSE + …)/100))` | tambahan |

Cabang `IS_BOUGHTOUT` bertaut dengan temuan Fase 3: SSOT boughtout adalah
`mbh_is_boughtout` (keputusan Anda), dan flag itu hanya `true` di **7 head**
padahal `check_status='Boughtout'` ada 137. Kalau cabang formula ini membaca
`mbh_is_boughtout`, maka **130 head boughtout salah jalur formula** — mereka
mendapat `MB_RM_COST + MB_CONV_COST` padahal semestinya `MB_RM_COST` saja.
Ini konsekuensi konkret dari flag yang tidak sinkron, dan perlu diverifikasi dev.

### 7.1 `cst_mb_cost` tidak memuat komponen MB

| `mbc_cost_type` | baris | non-nol | punya `source_cpc_id` |
|---|---:|---:|---:|
| ACTUAL | 4.189 | 4.174 | 4.189 |
| SELLING | 4.189 | 4.124 | 4.189 |
| **FORECAST** | 4.189 | **4.174** | 4.189 |

`mbc_cost_type` hanya memuat **ACTUAL / SELLING / FORECAST** — bukan
`MB_FINAL_COST` / `MB_RM_COST` / `MB_CONV_COST` seperti tersirat di formula
prompt. Komponennya hidup di `cpc_formula_trace` produk MB, dan `cst_mb_cost`
sepenuhnya terlacak ke `cst_product_cost` (**12.567/12.567 resolve**).

Nuansa penting: **MB FORECAST TERISI** (4.174 non-nol) — berbeda dari RM (Fase 2)
dan FG (Fase 5) yang FORECAST-nya nol. Jadi keputusan meng-exclude FORECAST
berlaku untuk RM/FG, tapi untuk MB sebenarnya ada angkanya. Legacy tetap tidak
punya pembanding forecast, jadi klasifikasinya tetap `SCOPE` — tapi asimetri ini
perlu dicatat supaya tidak disalahbaca.

---

## 8. Ringkasan untuk Executive Summary

| Layer penyebab | ACTUAL in-scope | Klasifikasi |
|---|---:|---|
| RM cost (rate + uplift landed hilang) | 57,5% | `DATA` + `ENGINE` |
| Packing (~2× signature) | 27,5% | `ENGINE` — kandidat bug |
| Parameter (waste / RM norm) | 14,0% | `DATA` |
| Fixed cost per kg | 0,9% | `DATA` |
| Semua input setara tapi output beda | **0%** | — |

**Kesimpulan Fase 6: penyebab selisih FG sudah terlokalisasi dan formula engine
konsisten.** 85% selisih in-scope terjelaskan oleh dua layer, dan salah satunya
(packing) punya signature numerik yang mengarah ke satu bug spesifik.

---

## 9. Temuan yang perlu keputusan

1. **Packing ~2×, 349 dari 349 kasus** (§6.1). Signature double-count. Perlu dev
   memeriksa `CAPTIVE_NO_OF_BOB` / `CAPTIVE_BOX_WT` / `DELIVERY_*`. Prioritas
   tertinggi — paling jelas dan paling bisa diperbaiki.
2. **Uplift landed cost hilang** (§4.1) — `RM_LANDED_COST = RM_RATE` sementara
   legacy menerapkan uplift ke ~31% produk. Perlu keputusan: apakah komponen
   duty/clearing/freight memang dihapus dari model, atau belum diimplementasikan?
3. **MB cost dikeluarkan dari conversion yarn** (§4.2) sementara legacy
   memasukkannya. Perlu konfirmasi Finance: definisi mana yang benar untuk
   pelaporan?
4. **Cabang `IS_BOUGHTOUT` × 130 head yang flag-nya tidak ter-set** (§7). Kalau
   formula membaca `mbh_is_boughtout`, 130 head boughtout salah jalur. Perlu
   diverifikasi dev, dan bertaut dengan script flag di paket remediasi.
5. **`MB_NO_PROCESS` sebagai pengali** (§7) tidak ada di pemahaman sebelumnya.
   Perlu dipastikan nilainya benar — kalau salah, seluruh MB conversion bergeser.
6. **Arah dampak: FG cost baru ~23% lebih rendah** pada kasus RM (§6.3). Ini
   yang paling perlu disampaikan ke Finance sebelum cutover.
7. **1.045 baris dengan `total_cost ≠ total_rm + total_conversion`** (§B9 di
   SQL). Belum terjelaskan; perlu dev.
8. **MB FORECAST terisi sementara RM/FG nol** (§7.1) — perlu dipastikan
   konsisten dengan keputusan meng-exclude FORECAST.

---

## 10. Definition of Done — Fase 6

- [x] Dekomposisi tidak berhenti di total — dipecah sampai komponen
- [x] Sumber breakdown `cpc_param_snapshot` dan `cpc_rm_cost_detail` dipakai;
      ditambah `cpc_formula_trace` dan `cpc_cost_by_level` yang tidak disebut prompt
- [x] `cpc_rm_cost_detail` vs `cst_rm_cost` diuji — hasilnya nol selisih,
      dilaporkan sebagai temuan negatif beserta alasannya
- [x] Rantai formula dibaca dari engine, bukan dari dokumentasi
- [x] Dekomposisi MB diverifikasi; dua koreksi atas formula prompt didokumentasikan
- [x] Setiap FG yang selisih diberi **layer penyebab**; nol kasus tanpa kategori
- [x] Perbedaan DEFINISI dipisahkan dari perbedaan DATA
- [x] Kesalahan pemetaan sendiri (TOP 55 vs TOP 56) ditemukan dan dikoreksi
      sebelum hasil dilaporkan
- [x] Nol statement write, nol trigger recompute
