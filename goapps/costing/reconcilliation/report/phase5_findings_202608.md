# Fase 5 — FG Product Cost (Yarn / POY): Temuan

| Field | Value |
|---|---|
| Periode | 202608 (Agustus 2026) |
| Snapshot | 2026-09-09; **legacy MARKETING masih bergerak** — lihat §7 |
| Mode | READ-ONLY — nol write, nol trigger recompute |
| Query | `cmp_04_fg_product_cost_202608.sql` |
| Script join | `build_phase5b.py` |
| Output | `out/cmp_04_fg_product_cost_202608_summary.csv` · `..._diff.csv` (55.419 baris, in-scope) · `out/cmp_04_fg_crosswalk_202608.csv` (dipakai ulang Fase 6 & 7) |
| Sumber legacy | `CST_YARN_CALCULATION_CUR` — 22.170 baris (VALUATION 7.505 / MARKETING 14.665) |
| Sumber baru | `cst_product_cost` @ 202608, `cpc_status <> 'SUPERSEDED'`, ACTUAL+SELLING, yarn — 26.844 baris |

---

## 1. Crosswalk — dibuktikan 1:1, dan peringatan prompt terpecahkan

### 1.1 `cpm_flex_02` polimorfik

| Tipe produk | Isi `cpm_flex_02` | Baris |
|---|---|---:|
| MB | `CMBH_SYS_ID` legacy (11 digit) | 4.071 |
| Yarn (PTY/POY/TTY/…) | `CYL_LEFT_NO` (≤6 digit) | 13.624 |

Prompt menyebut `cpm_flex_02 = CYL_LEFT_NO` — itu **hanya benar untuk yarn**.
Fase 5 karena itu mencakup **yarn saja**; produk MB sudah ditangani Fase 3/4.
Nilai `cpm_flex_02` **100% distinct** (17.695 non-kosong, 0 duplikat).

### 1.2 Peringatan fan-out terbukti — tapi bukan cacat data

| | Nilai |
|---|---:|
| `CST_YARN_LEFT` baris | 22.195 |
| `CYL_LEFT_NO` distinct | **14.665** → 7.530 duplikat |
| `CYL_SYS_ID` distinct | 22.195 (unik) |

Penyebab duplikat sudah dibuktikan: **dimensi COST TYPE.** Setiap `left_no`
duplikat punya **tepat 2 baris — satu VALUATION, satu MARKETING**
(`in_cur_valuation=1, in_cur_marketing=1` untuk seluruh sampel).

Dan di `_CUR`, `CYCC_LEFT_NO` **unik per `prs_type`**:

| `prs_type` | baris | `left_no` distinct | `cyl_sys_id` distinct | null |
|---|---:|---:|---:|---:|
| MARKETING 20210800119 | 14.665 | **14.665** | 14.665 | 0 |
| VALUATION 20210800120 | 7.505 | **7.505** | 7.505 | 0 |

**Jadi join `(left_no, prs_type)` ke `_CUR` adalah 1:1 dan tidak fan-out.**
Prompt benar bahwa `CYL_SYS_ID` adalah product key yang tepat — dipakai untuk
mengambil nama/spec (resolve 100%, 0 gagal), sementara `left_no` + `prs_type`
dipakai sebagai kunci join. Fan-out hanya terjadi kalau `prs_type` diabaikan.

### 1.3 Cakupan

| | Nilai |
|---|---:|
| **Pasangan terbandingkan** | **20.400** (ACTUAL 6.978 / SELLING 13.422) |
| Legacy tanpa padanan sisi baru | 1.770 |
| Sisi baru tanpa padanan legacy | 6.444 |

Catatan: ACTUAL hanya 6.978 dari 13.422 produk sisi baru karena legacy
VALUATION hanya memuat 7.505 produk — legacy memang tidak menghitung track
ACTUAL untuk semua produk.

---

## 2. Deviasi dari query basis — dengan alasan tertulis

Prompt mewajibkan: *"Jangan menyimpang dari query itu tanpa alasan tertulis."*

Query basis `fg cost product from legacy system.txt` hanya memilih
**TOP 1/4/5/6/20/96/128/80/57** — yaitu field **identifikasi** (Item Code, Item
Name, Shade Code, Shade Name, Raw Material) plus beberapa komponen. Ia
**tidak memuat** total cost, RM cost, maupun conversion.

Karena Fase 5 harus membandingkan `cpc_total_rm_cost` / `cpc_total_conversion` /
`cpc_total_cost`, TOP yang memuat angka itu **harus ditambahkan**. Logika scope
periode (join ke `CST_MST_ORION_REFF_HDR`/`DTL`) dipertahankan apa adanya dan
dipakai sebagai **penanda**, bukan filter — karena Anda mengonfirmasi `_CUR`
sudah = 202608.

---

## 3. Semantik TOP — dua trap yang mahal

### 3.1 Label `CST_YARN_TOP` tidak selalu mencerminkan isi

Pertama saya verifikasi tidak ada offset: kolom pivot `_CUR` dibandingkan
TOP-per-TOP dengan bentuk tall (`CST_YARN_CALCULATION`) untuk satu produk uji —
**17 dari 17 nilai identik**. Jadi penomorannya benar.

Tapi isinya tidak selalu sesuai nama:

| TOP | Nama di `CST_YARN_TOP` | Isi sebenarnya |
|---|---|---|
| 97 | *Value loss* | **teks** — mis. `Type 3POY BC` |
| 124 | *VALUATION* | **flag** — `Y` |

Keduanya dikeluarkan dari perbandingan. Pelajarannya: setiap TOP kandidat harus
diverifikasi numerik, jangan dipilih dari namanya.

### 3.2 Penomoran TOP berbeda antar cost type mulai TOP 127

| TOP | VALUATION (131 TOP) | MARKETING (140 TOP) |
|---|---|---|
| 127 | **CAPTIVE COVERSION** | % Add Top 95 |
| 128 | **DEL. CONVERTION** | Value Top 95 before Process |
| 129 | % Add Top 95 | Top 95 X % Add |
| 130–140 | — | NSBC / R-AX / loss fields |

**MARKETING sama sekali tidak punya TOP Captive/Del Conversion.** Query basis
memakai `CYCC_TOP_128_DATA_VALUE` **dengan** filter `prs_type = 20210800120`;
kalau query itu dipakai ulang untuk MARKETING, TOP 128 berubah arti tanpa
peringatan. Ini trap nyata untuk siapa pun yang menyalin query basis.

---

## 4. Koreksi metodologi — pembanding yang benar bukan kolom ringkas

Percobaan pertama saya membandingkan TOP legacy lawan kolom ringkas
`cst_product_cost` (`cpc_total_conversion`, `cpc_captive_cost`, …). Hasilnya:
**kecocokan ≤2,5% untuk SEMUA 25 TOP kandidat** yang diuji. Contoh:

- legacy TOP 91 (*Total* = Pwr/kg + MP/kg + OH/kg + CS/kg) ≈ **0,12–0,29**
- `cpc_total_conversion` ≈ **1,85–2,19** → 7–8× lebih tinggi

Itu **bukan** selisih data: definisinya berbeda. Prompt sendiri menyebutkan
`conversion = FIXED_TOTAL + WASTE_VAL + COST_OTHERS` — jauh lebih luas dari
TOP 91 yang hanya 4 komponen fixed-cost per kg.

**Pembanding legacy yang benar adalah `cpc_param_snapshot`**, yang ternyata
analog langsung struktur TOP legacy — dan punya **~105 key, bukan 19** seperti
disebut prompt. Buktinya, untuk `left_no 79`:

| legacy | nilai | `cpc_param_snapshot` | nilai |
|---|---:|---|---:|
| TOP 87 Pwr/kg | 0,0847 | `POWER_PER_KG` | 0,08466659 |
| TOP 88 MP/kg | 0,1175 | `MANPOWER_PER_KG` | 0,11745628 |
| TOP 89 OH/kg | 0,0199 | `OVERHEAD_PER_KG` | 0,01986298 |
| TOP 90 CS/kg | 0,0231 | `SPARESCOST_PER_KG` | 0,02305981 |
| TOP 91 Total | 0,2452 | `TOTAL_FIXEDCOST_PER_KG` | 0,24504566 |

Pemetaan lengkap 27 metric ada di Blok C `cmp_04_fg_product_cost_202608.sql`.
Toleransi: legacy menyimpan 4 desimal, sisi baru presisi penuh — |selisih| ≤
0,00005 diklasifikasikan `match-rounding` (artefak presisi legacy).

Kolom ringkas tetap **dilaporkan** tapi **tidak dibandingkan** lawan TOP legacy.
Dekomposisinya sendiri sudah diverifikasi: `total_cost = total_rm + conversion`
berlaku di **16.566 dari 17.611** baris ACTUAL (94,1%) — **1.045 baris tidak**,
dan `cost_per_unit == total_cost` di 17.611/17.611 (100%).

---

## 5. Hasil — berlapis, dan itu yang menentukan kesimpulan

### 5.1 ACTUAL (6.978 produk)

| Layer | Metric | TOP | setara | % |
|---|---|---|---:|---:|
| **1 — Parameter input** | OPU | 22 | 6.972 | **99,9%** |
| | WASTE_PCT | 21 | 6.715 | **96,2%** |
| | RM_NORM | 57 | 6.206 | **88,9%** |
| **2 — Fixed cost / kg** | OVERHEAD_PER_KG | 89 | 1.684 | 24,1% |
| | SPARES_PER_KG | 90 | 1.441 | 20,7% |
| | POWER_PER_KG | 87 | 1.067 | 15,3% |
| | MANPOWER_PER_KG | 88 | 982 | 14,1% |
| | FIXED_TOTAL_KG | 91 | 971 | 13,9% |
| | RM_LANDED_COST | 56 | 590 | 8,5% |
| | **RM_RATE** | 55 | 28 | **0,4%** |
| **3 — Cost turunan** | QLOSS_CAPTIVE / DELIVERY | 103/104 | 40 / 34 | 0,6% |
| | CAP/DEL_BEFORE_QLOSS | 94/95 | 18 / 19 | 0,3% |
| | CAP/DEL_WITH_QLOSS | 105/106 | 12 / 26 | 0,2–0,4% |
| | VB1–VB5_DEL_COST | 118–122 | 6–7 | **0,1%** |
| | CAP/DEL_CONVERSION | 127/128 | 6 / 11 | 0,1% |
| | CAP/DEL_PACK_COST | 42/48 | 39 | 0,6% |

### 5.2 SELLING (13.422 produk)

| Layer | Metric | setara | % |
|---|---|---:|---:|
| 1 | OPU | 13.394 | **99,8%** |
| | WASTE_PCT | 12.802 | **95,4%** |
| | RM_NORM | 11.032 | 82,2% |
| 2 | OVERHEAD_PER_KG | 6.399 | 47,7% |
| | SPARES_PER_KG | 5.994 | 44,7% |
| | POWER_PER_KG | 5.432 | 40,5% |
| | MANPOWER_PER_KG | 4.734 | 35,3% |
| | FIXED_TOTAL_KG | 4.582 | 34,1% |
| | **RM_RATE / RM_LANDED_COST** | **1** | **0,0%** |
| 3 | semua cost turunan | 0–11 | **0,0%** |

**SELLING RM cost = 0 untuk praktis seluruh 13.422 produk.** Ini kelanjutan
langsung temuan Fase 2 (156 group SELLING material + 42 group nol karena tier
`NONE`) yang merambat ke level FG.

### 5.3 Pola yang paling menjelaskan: in-scope vs out-of-scope ORION

| ACTUAL metric | In-scope 202608 (1.269) | Out-of-scope (5.709) |
|---|---:|---:|
| OPU | 100,0% | 99,9% |
| WASTE_PCT | 93,7% | 96,8% |
| RM_NORM | 86,1% | 89,6% |
| **RM_LANDED_COST** | **42,5%** | **0,9%** |
| **OVERHEAD_PER_KG** | **50,5%** | **18,3%** |
| **SPARES_PER_KG** | **49,7%** | **14,2%** |
| **POWER_PER_KG** | **43,8%** | **9,0%** |
| **MANPOWER_PER_KG** | **42,1%** | **7,8%** |
| **FIXED_TOTAL_KG** | **41,8%** | **7,7%** |
| CAP_WITH_QLOSS | 0,1% | 0,2% |
| DEL_CONVERSION | 0,0% | 0,2% |

Catatan penting soal RM: **`RM_RATE` (TOP 55) hanya setara 0,4%, sementara
`RM_LANDED_COST` (TOP 56) setara 8,5% (42,5% in-scope).** Jadi yang sebanding
adalah *landed cost*, bukan *rate* mentah — konsisten dengan Fase 2 di mana
`CGCH_LANDED_COST` yang menjadi padanan ACTUAL, bukan `CGCH_COST`.

Bacaannya:

- **Parameter master** (OPU, waste, RM norm) setara di dua populasi — wajar,
  karena parameter tidak bergantung periode. **Migrasi parameter produk sehat.**
- **Rate dan fixed cost per kg** setara ~42–44% untuk produk yang benar-benar
  bertransaksi di 202608, tapi hanya ~1–9% untuk yang tidak. Artinya angka
  legacy untuk produk di luar scope memang tidak pernah di-refresh untuk 202608
  — jadi sebagian besar "selisih" di populasi luar scope adalah **umur data
  legacy**, bukan defect engine.
- **Cost turunan setara ~0% di dua populasi.** Ini yang tidak bisa dijelaskan
  oleh scope maupun umur data, dan menjadi temuan utama Fase 5.

---

## 6. Dua kandidat akar penyebab layer 3

### 6.1 Machine fixed cost kolaps ke satu nilai

| Sisi | Kondisi |
|---|---|
| Legacy `CST_MST_MACHINE.CMM_TOT_FXD_CST` | **63 mesin**, rentang **29,85 – 5.712,50** |
| goapps `mst_mb_head.mbh_machine_fixed_total` | **4.226 baris, 1 nilai distinct: 939,33** |

Placeholder 384.000 yang prompt peringatkan **tidak ada** (0 baris). Tapi
kondisinya serupa dan justru lebih seragam: seluruh head memakai satu nilai
fixed cost yang sama, sementara legacy membedakan per mesin.

### 6.2 `mst_spin_fixed_cost` tidak punya baris 202608

| `msfc_period` | poy_production | power | manpower | overhead | spares |
|---|---:|---:|---:|---:|---:|
| 202604 | 3.027.153 | 198.634 | 275.561 | 46.600 | 54.100 |
| 202606 | 3.027.153 | 198.634 | 275.561 | 46.600 | 54.100 |
| 202607 | 3.027.153 | 198.634 | 275.561 | 46.600 | 54.100 |
| **202608** | **— tidak ada —** | | | | |

Padahal `cpc_param_snapshot` 202608 memuat `SPIN_POWER_MONTH = 198634` — jadi
engine memakai nilai dari periode lain. Karena ketiga periode bernilai identik,
dampak numeriknya nol untuk sekarang, tapi **secara data 202608 dihitung dengan
baris periode yang tidak ada**. Dan nilainya seragam di semua periode, jadi
mekanisme per-periode-nya belum benar-benar dipakai.

Keduanya perlu ditelusuri lebih dalam di Fase 6 — Fase 5 berhenti pada
identifikasi kandidat, tidak mengklaim sebab-akibat.

---

## 7. Integritas snapshot — legacy ternyata masih bergerak

Stamp ulang saat Fase 5 (≈14:30) dibanding stamp Fase 0 (09:46):

| Cost type | Baris (09:46) | Baris (14:30) | Terakhir diubah | Diubah hari ini |
|---|---:|---:|---|---:|
| MARKETING → SELLING | 14.635 | **14.665** (+30) | **2026-09-09 14:30:34** | **38** |
| VALUATION → ACTUAL | 7.505 | 7.505 | 2026-09-02 15:32 | 0 |

**Track ACTUAL legacy stabil; track SELLING legacy aktif diedit selama sesi
recon berjalan.** Jadi angka SELLING di laporan ini adalah pembanding terhadap
target bergerak, dan itu memperkuat rekomendasi untuk tidak mempresentasikan
SELLING sebagai angka final sebelum Fase 6.

Premis prompt "legacy sudah beku" **tidak berlaku** untuk `_CUR` MARKETING.
Scope 202608 sendiri tidak berubah (tetap 1.546 left_no).

---

## 8. Ringkasan untuk Executive Summary

| Scope | Objek | Setara (parameter) | Setara (rate/fixed cost) | Setara (cost turunan) |
|---|---:|---:|---:|---:|
| FG cost — ACTUAL | 6.978 produk | 88,9–99,9% | 8,5–24,1% | **0,1–0,6%** |
| FG cost — SELLING | 13.422 produk | 82,2–99,8% | 0,0–47,7% | **0,0%** |

Populasi in-scope 202608 (yang paling relevan untuk laporan periode ini):
1.269 produk ACTUAL / 1.270 SELLING, dengan rate & fixed cost setara ~42–44%.

**Kesimpulan Fase 5: parameter produk termigrasi dengan baik, tapi cost turunan
belum sebanding.** Selisih tidak tersebar acak — ia terkonsentrasi di layer
tertentu, yang berarti bisa ditelusuri, bukan kekacauan menyeluruh.

---

## 9. Temuan yang perlu keputusan

1. **Cost turunan setara ~0%** (§5) — layer quality-loss, packing, volume
   bucket, dan conversion. Ini fokus wajib Fase 6. Jangan dipresentasikan ke
   Finance sebagai angka sebelum layer penyebabnya ditemukan.
2. **`mbh_machine_fixed_total` seragam 939,33** (§6.1) padahal legacy
   membedakan 63 mesin (29,85–5.712,50). Perlu keputusan: apakah model biaya
   mesin memang disederhanakan, atau ini placeholder yang belum diisi?
3. **`mst_spin_fixed_cost` tanpa baris 202608** (§6.2), dan nilainya identik di
   semua periode. Perlu dipastikan apakah 202608 seharusnya punya baris sendiri.
4. **SELLING RM cost = 0 di 13.421 dari 13.422 produk** (§5.2). Konsekuensi
   langsung temuan Fase 2. Perlu keputusan yang sama: lengkapi tier config /
   fixed value.
5. **1.045 baris ACTUAL yang dekomposisinya tidak berlaku**
   (`total_cost ≠ total_rm + total_conversion`, §4). Perlu diperiksa dev.
6. **Legacy SELLING masih diedit** (§7). Perlu ditetapkan tanggal cut-off
   sebelum laporan difinalkan, atau track SELLING dilaporkan sebagai
   provisional.
7. **1.770 produk legacy tanpa padanan** dan **6.444 produk baru tanpa padanan**
   (§1.3) — perlu dipisahkan mana produk baru sah (Fase 8) dan mana gap
   crosswalk.

---

## 10. Definition of Done — Fase 5

- [x] Legacy `CST_YARN_CALCULATION_CUR` dipakai sesuai query basis; deviasi
      didokumentasikan dengan alasan tertulis
- [x] `cpc_status <> 'SUPERSEDED'` diterapkan; APPROVED + CALCULATED keduanya
      dicakup sesuai keputusan user
- [x] Crosswalk dibangun eksplisit, **dibuktikan 1:1**, fan-out dijelaskan
      sebabnya (dimensi cost type) bukan hanya dilaporkan
- [x] Crosswalk disimpan sebagai CSV untuk dipakai ulang Fase 6 & 7
- [x] Semantik TOP diverifikasi lawan bentuk tall; label yang menyesatkan
      diidentifikasi dan dikeluarkan
- [x] Divergensi penomoran TOP antar cost type didokumentasikan
- [x] Pembanding yang benar (`cpc_param_snapshot`) ditemukan setelah pembanding
      pertama dibuktikan tidak valid
- [x] Artefak presisi dipisahkan dari selisih data
- [x] FORECAST di-exclude sesuai keputusan user
- [x] Snapshot di-stamp ulang; drift legacy terdeteksi dan dilaporkan
- [x] Nol statement write, nol trigger recompute
