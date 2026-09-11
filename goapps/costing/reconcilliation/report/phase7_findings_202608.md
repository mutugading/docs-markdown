# Fase 7 — Intermediate Product (DAG Yarn): Temuan

| Field | Value |
|---|---|
| Periode | 202608 (Agustus 2026) |
| Snapshot | 2026-09-09 |
| Mode | READ-ONLY — nol write, nol trigger recompute |
| Query | `cmp_06_intermediate_product_202608.sql` |
| Output | `out/cmp_06_intermediate_product_202608.csv` (8.718 baris terdampak) |
| Prasyarat | Crosswalk Fase 5 (`out/cmp_04_fg_crosswalk_202608.csv`) |

> **Temuan utama fase ini adalah defect terbesar di seluruh recon**: propagasi
> cost produk antara tidak berjalan untuk **8.718 dari 9.780** baris route
> bertipe PRODUCT (89,1%). Lihat §4.

---

## 1. Struktur route — semua klaim prompt terverifikasi

### 1.1 Nama tabel

Prompt benar: nama produksi adalah `cost_route_head` / `cost_route_seq` /
`cost_route_rm` — **bukan** `cost_route_rms` / `cost_route_sequences` yang
ilustratif di PRD. Row count: **17.916 / 34.328 / 53.105**.

### 1.2 Aturan polimorfik — terbukti sempurna, dengan satu kejutan

| `crm_rm_type` | baris | `product` terisi | `item` terisi | `group` terisi |
|---|---:|---:|---:|---:|
| `GROUP` | 35.672 | 0 | 0 | **35.672** |
| `PRODUCT` | 17.433 | **17.433** | 0 | 0 |
| **`ITEM`** | **0** | — | — | — |

Aturan "tepat satu kolom terisi" berlaku **100%, nol silang**. Tapi tipe
**`ITEM` tidak pernah dipakai** — nol baris. Prompt menyebut tiga tipe
polimorfik; di data hanya dua yang hidup, dan `crm_rm_item_code` selalu kosong
(padahal ada index untuknya).

### 1.3 "Route MB selalu single-level" — terbukti

| Tipe | produk | single-level | 2 level | ≥3 level | terdalam | avg seq |
|---|---:|---:|---:|---:|---:|---:|
| **MB** | 4.291 | **4.291** | 0 | 0 | **1** | **1,00** |
| POY | 4.062 | **4.062** | 0 | 0 | **1** | 1,00 |
| PTY | 6.599 | 226 | 5.776 | 597 | 8 | 2,13 |
| TTY | 1.015 | 24 | 63 | 928 | 10 | 3,70 |
| **TCY** | 453 | 3 | 20 | 430 | **13** | 4,42 |
| TCS | 210 | 0 | 0 | 210 | 9 | 4,94 |
| TCM | 135 | 0 | 0 | 135 | 8 | 5,24 |
| TTS | 409 | 0 | 3 | 406 | 7 | 5,35 |

MB **4.291 dari 4.291 single-level, deepest = 1, avg_seq = 1,00** — klaim prompt
tepat. Tambahan yang tidak disebut prompt: **POY juga seluruhnya single-level**,
jadi POY adalah produk basis, bukan intermediate. Yang benar-benar multi-level
adalah PTY/TTY/TCY/TCS/TCM/TTS.

### 1.4 Fan-out dan cycle

| | Nilai |
|---|---:|
| Parent dengan input bertipe PRODUCT | 9.474 |
| Rata-rata produk hulu per parent | 1,84 |
| **Maksimum produk hulu per parent** | **117** |
| Self-reference (`parent = upstream`) | **0** |

---

## 2. Perbandingan struktur DAG dengan legacy

### 2.1 Padanan legacy: `CST_LVL_LEFT_PROD`

Analog struktural langsung dari `cost_route_seq` + `cost_route_rm`:

| Legacy | goapps |
|---|---|
| `CLLP_CYL_SYS_ID` | `crm_parent_product_sys_id` |
| `CLLP_CYL_SYS_ID_REFF` | `crm_rm_product_sys_id` |
| `CLLP_CGH_SYS_ID` | `crm_rm_group_code` |
| `CLLP_LVL_PROD` | `crs_route_level` |
| `CLLP_SEQ_LVL` | `crs_route_seq` |
| `CLLP_TYPE_RM` | `crm_rm_type` |

### 2.2 ⚠️ 82% tabel DAG legacy adalah data sampah

Tabel ini **tidak boleh dipakai apa adanya** sebagai basis pembanding:

| Subset | baris | produk distinct | `left_no` distinct | **max level** |
|---|---:|---:|---:|---:|
| `CLLP_LEFT_NO` **NULL** | **16.349 (82%)** | **198** | 0 | **53** |
| `CLLP_LEFT_NO` ada | 3.488 | 2.601 | 2.593 | **13** |

Subset NULL: 16.349 baris tersebar hanya di **198 produk** — sekitar **82 baris
per produk** — dengan level mencapai **53**. Untuk DAG yarn yang paling dalam
pun 13 level, angka 53 adalah tanda **rantai bercycle / runaway**. Ini scratch
data, bukan DAG yang sah.

Subset bersih (3.488 baris / 2.593 produk) punya **max level 13 — persis sama
dengan sisi baru.**

### 2.3 Sebaran level: konvergen di level dalam

| level | legacy (subset bersih) | goapps |
|---|---:|---:|
| 1 | 1.032 | 17.386 |
| 2 | 681 | 9.065 |
| 3 | 450 | 2.975 |
| 4 | 272 | 1.680 |
| 5 | 190 | 899 |
| 6 | 132 | 318 |
| 7 | 77 | 151 |
| 8 | 36 | 98 |
| 9 | 18 | 52 |
| **10** | **9** | **11** |
| **11** | **4** | **5** |
| **12** | **1** | **2** |
| **13** | **1** | **1** |

**Selisih besar di level 1–3 adalah beda representasi, bukan gap data**: legacy
hanya menyimpan baris DAG untuk produk yang benar-benar punya produk hulu,
sedangkan sisi baru menulis entri level 1 untuk **setiap** produk (17.386 =
hampir semua produk ter-cost).

Di level dalam (≥10) — tempat struktur DAG sebenarnya diuji — **angkanya nyaris
identik dan kedalaman maksimumnya sama (13)**. Jadi **struktur DAG multi-level
termigrasi dengan benar.**

### 2.4 Taksonomi tipe tidak sebanding satu-satu

`CLLP_TYPE_RM` legacy: `(null)` 17.278 · `Captive Cost` 1.504 · `Multi Yarn` 855
· `Yarn-Cap` 88 · `Stores` 84 · `From Group Item MKT Rate` 26 · `PTY` 1 ·
`REWINDING` 1.

Mayoritas **NULL**, dan `CLLP_CGH_SYS_ID` hanya terisi 777 (semuanya di bawah
`Multi Yarn`). Jadi taksonomi legacy jauh lebih longgar dari
`GROUP`/`PRODUCT`/`ITEM` di sisi baru — tidak bisa dipetakan 1:1 dan tidak
dipakai sebagai dasar perbandingan.

---

## 3. Temuan performa: kolom traversal tidak ter-index

`EXPLAIN` atas recursive CTE menunjukkan **`Seq Scan on cost_route_rm` di setiap
langkah rekursi** — tiap level memindai penuh 53.105 baris.

Index yang ada di `cost_route_rm`:

| Index | Kolom |
|---|---|
| `cost_route_rm_pkey` | `crm_rm_id` |
| `idx_crm_rm_group` | `crm_rm_group_code` (partial) |
| `idx_crm_rm_item` | `crm_rm_item_code` (partial) — **kolomnya tidak pernah dipakai** |
| `idx_crm_rm_product` | `crm_rm_product_sys_id` (partial) |
| `idx_crm_seq` | `crm_seq_id` |

**Tidak ada index pada `crm_parent_product_sys_id`** — justru kolom yang prompt
sebut sebagai filter "route milik produk X", dan kolom yang di-join traversal
parent → hulu. Arah hulu ter-index; arah traversal tidak.

Konsekuensi nyata: traversal produk terdalam **tidak selesai dalam waktu wajar**
dan saya hentikan (sesuai aturan prompt soal query berat di production). Guard
depth di recursive CTE karena itu **wajib**, bukan opsional.

Perbaikan yang disarankan — **DDL, tidak dijalankan sesi ini**:

```sql
CREATE INDEX idx_crm_parent_product ON cost_route_rm (crm_parent_product_sys_id);
```

---

## 4. TEMUAN UTAMA: cost produk antara tidak dipropagasi

### 4.1 Signature: `unit_cost = 1`

`cpc_rm_cost_detail` memuat rate yang dipakai engine per baris route. Dipecah
per tipe:

| `rm_type` | baris | **`unit_cost` tepat 1** | `unit_cost` = 0 | nilai lain | max |
|---|---:|---:|---:|---:|---:|
| **PRODUCT** | 9.780 | **8.718 (89,1%)** | 197 | 865 | 19,05 |
| GROUP | 25.726 | **0 (NOL)** | 535 | 25.191 | 204,78 |

**Kontras inilah buktinya**: tipe GROUP — yang resolusinya berjalan — **tidak
pernah** menghasilkan tepat 1 dari 25.726 baris. Tipe PRODUCT menghasilkan tepat
1 di 89,1% baris. Nilai 1 itu **placeholder**, bukan nilai wajar.

### 4.2 Niat desainnya jelas dari formula

```
CAPTIVE_CONVERSION = sum(ratio * CASE rm_type
     WHEN 'PRODUCT' THEN upstream_product(rm_product_legacy_id).COST_CAP_FINAL
     WHEN 'GROUP'   THEN mst_rm_cost(rm_group_code, period, 'VAL') END)
   for route_rms WHERE route_head_legacy_product_id = current_product
     AND route_level = current_level
```

Engine **seharusnya** mengambil `COST_CAP_FINAL` produk hulu. Yang tersimpan di
data: **1**.

### 4.3 Nilainya TERSEDIA — jadi ini defect engine, bukan gap data

`ref_code` berformat `product:<cpm_product_sys_id>`. Dengan kunci itu:

| | baris | resolve ke master | **punya cost 202608** | rata-rata cost hulu |
|---|---:|---:|---:|---:|
| `unit_cost = 1` | 8.718 | **8.718 (100%)** | **8.718 (100%)** | **2,4092** |
| `unit_cost ≠ 1` | 1.062 | 1.062 (100%) | 1.062 (100%) | 5,0729 |

**Seluruh 8.718 baris merujuk produk hulu yang punya cost 202608 riil dengan
rata-rata 2,41 — dan engine tetap memakai 1.** Klasifikasi: **`ENGINE`**.

### 4.4 Dampak terukur

| | Nilai |
|---|---:|
| **Parent product terdampak** | **8.386** dari 9.321 (**90,0%**) |
| Baris route terdampak | 8.718 |
| **Median understatement per baris** | **1,9859** |
| **Total understatement** | **12.285,76** satuan cost |

Contoh terbesar — perhatikan `rm_sekarang` yang **bulat**:

| produk | nama | RM sekarang | seharusnya + |
|---|---|---:|---:|
| CSTATY2606000188 | ATY 1764/960/RND/RSDFR-DSD | **3,0000** | +7,9855 |
| CSTPTY2606002610 | PTY 1800/576/RND/DSD-DSD/HIM | **4,0000** | +7,8897 |
| CSTPTY2606002616 | PTY 1800/576/RND/DSD-DSD/IM | **4,0000** | +7,8704 |
| CSTATY2606000237 | ATY 370/108/RND/DSD/NIM/DH | **3,0000** | +7,2724 |
| CSTATY2606000187 | ATY 1764/960/RND/RSDFR-BSD | **3,0000** | +6,2898 |

`CSTATY2606000188`: RM sekarang 3,0000 → seharusnya 10,9855. **RM understated
73%.**

### 4.5 Ini menjelaskan gejala dari fase-fase sebelumnya

| Gejala | Fase | Penjelasan |
|---|---|---|
| `cpc_total_rm_cost` = tepat **1,000000** di 678 dari 1.269 produk | 5 | satu produk hulu × ratio 1 → 1 |
| `cpc_total_rm_cost` bernilai **bulat** (3,0 / 4,0) | 5 | menjumlahkan `1 × ratio` untuk tiap produk hulu |
| `cpc_captive_cost` = **1,000000** di 96,7% | 5 | captive cost = cost produk hulu, yang di-placeholder 1 |
| hanya **24 nilai distinct** `total_rm` vs 488 di legacy | 5 | nilai kolaps ke bilangan bulat kecil |
| Layer penyebab **RM_COST 57,5%** | 6 | sebagian besarnya berakar di sini |
| FG cost baru **~23% lebih rendah** dari legacy | 6 | konsisten dengan RM yang understated |

**Jadi tiga fase terakhir sebenarnya melihat satu akar penyebab yang sama.**

---

## 5. Perbandingan cost per level: tidak bisa dilakukan sekarang

Prompt meminta *"Bandingkan cost intermediate per level vs legacy."*

**Belum bisa dilakukan secara bermakna untuk 202608.** Selama `unit_cost` produk
hulu masih 1 di 89,1% baris, setiap perbandingan cost intermediate hanya akan
mengukur defect §4 — bukan selisih data antara kedua sistem. Angka yang keluar
akan valid secara aritmetika tapi menyesatkan sebagai kesimpulan recon.

Yang **bisa** dan sudah dilakukan: perbandingan **struktur** DAG (§2), yang
hasilnya baik — struktur multi-level termigrasi dengan benar.

Rekomendasi: **ulangi perbandingan cost per level setelah defect §4 diperbaiki
dan 202608 di-recompute.** `cpc_cost_by_level` sudah menyediakan bentuk datanya,
jadi pengulangannya murah.

Ini keterbatasan yang saya nyatakan eksplisit, bukan bagian yang dilewatkan.

---

## 6. Ringkasan untuk Executive Summary

| Aspek | Hasil |
|---|---|
| Struktur DAG multi-level | ✅ **termigrasi benar** — max depth 13 di dua sisi, level dalam nyaris identik |
| Aturan polimorfik route | ✅ **100% konsisten**, nol silang |
| Route MB single-level | ✅ **terbukti** 4.291/4.291 |
| **Cost produk antara** | ❌ **tidak dipropagasi — 8.386 produk (90%) terdampak** |
| Cost per level vs legacy | ⏸️ **tidak bisa dibandingkan** sampai defect diperbaiki |
| Kualitas data DAG legacy | ⚠️ 82% baris sampah (level sampai 53) |
| Performa traversal | ⚠️ kolom traversal tidak ter-index |

---

## 7. Temuan yang perlu keputusan

1. **[PRIORITAS TERTINGGI] Propagasi cost produk antara tidak berjalan** (§4).
   8.386 produk terdampak, understatement total 12.285,76. Nilainya tersedia
   (rata-rata 2,41) tapi engine memakai 1. Perlu perbaikan dev pada resolusi
   `upstream_product(...).COST_CAP_FINAL`, lalu **202608 harus di-recompute**.
   Sebelum ini beres, angka FG cost sisi baru tidak layak dipakai Finance.
2. **Index `crm_parent_product_sys_id`** (§3). Satu DDL, dampaknya besar untuk
   performa traversal — dan kalau perbaikan #1 melibatkan traversal DAG, index
   ini jadi prasyarat praktis.
3. **Tipe route `ITEM` nol baris** (§1.2) padahal ada index untuknya. Perlu
   dipastikan: memang tidak dipakai (index bisa dibuang) atau ada jalur yang
   belum terimplementasi?
4. **82% `CST_LVL_LEFT_PROD` legacy adalah sampah** dengan level sampai 53
   (§2.2). Legacy sudah beku, jadi ini informasi saja — tapi perlu dipastikan
   tidak ada proses legacy yang masih membacanya, dan jangan dipakai sebagai
   sumber migrasi.
5. **Perbandingan cost per level ditunda** (§5). Perlu persetujuan Anda bahwa
   ini dilaporkan sebagai `BLOCKED-BY-DEFECT`, bukan `UNKNOWN`.

---

## 8. Definition of Done — Fase 7

- [x] DAG ditelusuri via `cost_route_head` / `cost_route_seq` / `cost_route_rm`
- [x] `crm_parent_product_sys_id` dipakai sebagai filter route milik produk
- [x] Aturan polimorfik triple-column diverifikasi (100% konsisten; `ITEM` nol)
- [x] Klaim "route MB single-level" diverifikasi (4.291/4.291)
- [x] Recursive CTE dipakai untuk yarn multi-level, dengan guard depth dan
      pencegah cycle — dan alasan guard-nya dibuktikan (`EXPLAIN` + level 53 legacy)
- [x] Struktur DAG dibandingkan dengan legacy; subset sampah legacy dipisahkan
- [x] Query berat dihentikan, tidak dibiarkan menggantung di production
- [x] Keterbatasan perbandingan cost per level dinyatakan eksplisit dengan alasan
- [x] Nol statement write, nol trigger recompute, nol DDL
