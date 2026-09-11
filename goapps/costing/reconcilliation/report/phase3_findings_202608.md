# Fase 3 — Masterbatch Recipe (Composition): Temuan

| Field | Value |
|---|---|
| Periode | 202608 (Agustus 2026) — lihat §1 soal keterbatasan periode |
| Snapshot | 2026-09-09 (lihat `snapshot_stamp_202608.md`) |
| Mode | READ-ONLY — nol write, nol trigger recompute |
| Query | `cmp_02_mb_recipe_202608.sql` |
| Script join | `build_phase3.py` |
| Output | `out/cmp_02_mb_recipe_head_202608.csv` (4.325 head) · `out/cmp_02_mb_recipe_line_202608.csv` (22.589 line) |
| Sumber legacy | `CST_MST_BATCH_HEAD` 4.325 + `CST_MST_BATCH_ITEM` 22.589 |
| Sumber baru | `mst_mb_head` 4.226 + `mst_mb_composition_version` @ `mbcv_version = mbh_current_version` 21.863 |

---

## 1. Keterbatasan yang harus disadari: recipe tidak punya periode

Tidak ada kolom periode di `CST_MST_BATCH_HEAD`/`ITEM` maupun di
`mst_mb_head`/`mst_mb_composition_version`. Recipe adalah **master data**.

Jadi Fase 3 membandingkan **kondisi master per 2026-09-09**, bukan kondisi
per 202608. Kalau ada resep yang diubah setelah 202608 di-calculate, angka
`cst_product_cost` 202608 dihitung dengan resep versi lama sementara
perbandingan di sini memakai resep versi sekarang. `mbcv_version` memberi
alat untuk melacaknya, tapi tidak ada stempel "versi mana yang dipakai untuk
202608" — kolom seperti itu tidak ada.

Ini dicatat sebagai keterbatasan metodologis, bukan temuan defect.

---

## 2. Crosswalk — eksplisit, tanpa heuristik

Berbeda dari Fase 2, sisi baru menyimpan pointer legacy secara langsung:

| Level | Kunci | Bukti |
|---|---|---|
| Head | `mst_mb_head.mbh_oracle_sys_id` = `CMBH_SYS_ID` | 4.226 baris, **4.226 distinct, 0 null** |
| Line | `mst_mb_composition.mbcm_legacy_sys_id` = `CMBI_SYS_ID` | 21.863 baris, **21.863 distinct, 0 null** |

Kolom `mbcm_legacy_sys_id` hanya ada di working set, tidak di tabel version —
jadi nilai dibandingkan dari `mst_mb_composition_version` (sesuai instruksi
prompt), sementara pointer legacy diambil dari working set lewat join
`(mbh_id, seq_no)`. Kesejajaran join itu sudah dibuktikan: 21.863 = 21.863,
**0 selisih pada `composition_pct`**, 0 selisih pada `source_type`.

### 2.1 Temuan Fase 2 yang lahir di sini

`CMBI_CGH_SYS_ID` legacy bernilai `202007181`, `202007594` — pola `YYYYMM`+seq
yang identik dengan `group_code` sisi baru. Inilah yang mengungkap bahwa
kunci join RM group yang benar adalah `CGH_SYS_ID` ↔ `group_code`, bukan
`CGH_GROUP_CODE`. **Laporan Fase 2 sudah dikoreksi** memakai kunci itu
(crosswalk berubah dari 292/350 comparable menjadi 350/350).

---

## 3. Populasi head

| Metrik | Nilai |
|---|---:|
| Legacy `CST_MST_BATCH_HEAD` | 4.325 |
| goapps `mst_mb_head` | 4.226 |
| **Match by `oracle_sys_id`** | **4.226 (100% sisi baru)** |
| goapps tanpa padanan legacy | **0** |
| Legacy tanpa padanan goapps | **99** |

**Seluruh 99 legacy-only berstatus `check_status = 'Waiting'`** — draft yang
tidak pernah dimigrasikan. Nol di antaranya `Current`, `Approved`, atau
`Boughtout`. Ini penjelasan bersih dan lengkap untuk gap populasi: `SCOPE`,
bukan gap migrasi yang perlu ditindaklanjuti.

Sebaran `check_status` memperkuat itu — status "riil" cocok **persis** di dua sisi:

| `check_status` | Legacy | goapps | Selisih |
|---|---:|---:|---:|
| `Current` | 477 | 477 | **0** |
| `Approved` | 41 | 41 | **0** |
| `Boughtout` | 137 | 137 | **0** |
| `Outdated` | 8 | 8 | **0** |
| `Rejected` | 3 | 3 | **0** |
| `Waiting` | 3.478 | 3.353 | 125 |
| *(null)* | 181 | 207 | −26 |
| **Total** | **4.325** | **4.226** | **99** |

Selisih hanya di `Waiting` dan `(null)` — dua status draft.

### 3.1 Bucket head

| Bucket | Head | Arti |
|---|---:|---|
| `comparable` | **4.192** | ada di dua sisi, dua-duanya punya recipe |
| `legacy-only` | 99 | draft `Waiting`, tidak dimigrasikan → `SCOPE` |
| `recipe-missing-in-new` | 30 | head termigrasi tapi komposisinya tidak |
| `no-recipe-both` | 4 | tidak punya recipe di dua sisi (wajar) |

**30 head `recipe-missing-in-new`** semuanya seragam: `check_status='Waiting'`
di dua sisi, `mbh_current_version = 0`, `mbh_is_active = false`, total 160 line
legacy. `current_version = 0` berarti komposisinya **belum pernah di-version**,
jadi tidak ada snapshot untuk dikonsumsi engine. Draft, bukan defect.

---

## 4. Populasi line — dan hasil utamanya

| Metrik | Nilai |
|---|---:|
| Legacy `CST_MST_BATCH_ITEM` | 22.589 |
| goapps versi current | 21.863 |
| **Match by `CMBI_SYS_ID`** | **21.863 (100% sisi baru)** |

| Klasifikasi | Line |
|---|---:|
| `match` (pct persis sama) | **21.635** |
| `match-within-0.001` (artefak presisi 3 desimal) | 185 |
| `pct-null-one-side` (legacy NULL → baru 0.000) | 41 |
| **`pct-differs`** | **2** |
| `line-missing-in-new` | 726 |

**Hanya 2 dari 21.863 line yang persentasenya berbeda.** Untuk perbandingan
resep, ini hasil yang sangat kuat: 21.635 identik persis, 185 beda pembulatan
(delta 0,0001–0,0005 — sisi baru menyimpan 3 desimal), 41 NULL legacy
dinormalisasi jadi 0 di sisi baru.

### 4.1 Dua selisih pct itu satu perubahan resep, bukan dua defect

Keduanya di head yang sama, **`20251004943`** (`MGT DESTINY BK 7766-N-D-04527-B`,
status `Waiting`):

| seq | komponen | legacy | baru | delta | perubahan lain |
|---:|---|---:|---:|---:|---|
| 1 | CHM0000186 | 2,00 | 2,000 | 0 | — |
| 2 | PIG0000067 | 36,45 | 18,500 | **−17,950** | — |
| 3 | **PBT** (carrier) | 2,05 | 20,000 | **+17,950** | — |
| 4 | MBB0000087 | 59,50 | 59,500 | 0 | `source_type` **GROUP → MB** |

Sum tetap 100 di dua sisi. Ini satu restrukturisasi resep yang koheren:
komponen MBB0000087 diubah menjadi **referensi nested MB**, dan selisihnya
diserap carrier PBT. Ini juga **satu-satunya** line dengan `source_type`
berbeda di seluruh 21.863 line.

Jadi: `pct-differs` 2 + `source_type-differs` 1 = **satu perubahan pada satu
MB draft**, bukan tiga temuan terpisah.

### 4.2 726 line hilang di sisi baru — terurai bersih

| Asal | Line | Kategori |
|---|---:|---|
| Dari 99 head `legacy-only` | 538 | `SCOPE` — head-nya memang tidak dimigrasikan |
| Dari 30 head `recipe-missing-in-new` | 160 | `SCOPE` — draft, `current_version=0` |
| Dari head `comparable` | **21** | perlu diperiksa (lihat di bawah) |
| Line orphan (parent head tidak resolve) | 7 | kualitas data **legacy** |

**21 line hilang pada head yang comparable** terbagi dua:

- **17 line kosong**: `CMBI_CODE` kosong dan `CMBI_COMPOSITION` NULL. Baris
  filler di legacy yang tidak dimigrasikan. Sum tetap 100 di dua sisi →
  **benign**.
- **4 line dengan nilai riil**, dan inilah temuan pentingnya (§5).

**7 line orphan**: `CMBI_CMBH_SYS_ID` kosong, sementara `CMBI_CODE`-nya justru
memuat nilai yang salah tempat (`20200701727`, `BIO DEGRADABLE...`,
`ANTIBACTERIAL...`). Ini pelanggaran integritas referensial **di legacy**;
benar untuk tidak dimigrasikan. Dicatat sebagai temuan kualitas data legacy.

---

## 5. Temuan utama: 4 resep rusak, satu akar penyebab

Validasi sum komposisi (presisi 3 desimal):

| Sisi | tepat 100 | beda ≤ 0,01 | **materially off** | tanpa recipe |
|---|---:|---:|---:|---:|
| Legacy | 4.197 | 123 | **0** | 5 |
| goapps | 4.045 | 143 | **4** | 133 |

Legacy **tidak punya satupun** resep yang sum-nya menyimpang. Sisi baru punya 4.

> Catatan: query Oracle A5 menampilkan `materially_off = 1`. Itu **artefak** —
> pseudo-group dari 7 line orphan (§4.2) yang head-ref-nya kosong dan
> composition-nya NULL sehingga `SUM` = NULL. Head legacy riil: nol.

Keempatnya, dan penyebabnya:

| head | nama | legacy sum | baru sum | line hilang | pct hilang |
|---|---|---:|---:|---|---:|
| 20241004279 | MGT NEWRY BN 6628 N-D-03671-B | 100,00 | **75,000** | `SPC 3221` | 25,00 |
| 20241204383 | MGT NEWRY BN 6628-D-03671-B | 100,00 | **75,000** | `SPC 3221` | 25,00 |
| 20250804838 | MGT IXORA RD 3431--B | 100,00 | **65,000** | `SPC 3221` | 35,00 |
| 20250804886 | MGT ZENBU RD 3426-D-04141-B | 100,000 | **40,420** | `SPC 3221` | 59,58 |

**Akar penyebab tunggal: komponen `SPC 3221` gagal termigrasi pada 4 resep.**
Selisih sum-nya persis sama dengan persentase komponen yang hilang di setiap
kasus (100 − 25 = 75, 100 − 35 = 65, 100 − 59,58 = 40,42). Tidak ada
kompensasi carrier, jadi resepnya benar-benar tidak lengkap.

Dan ini **bukan** master data yang hilang: sisi baru punya tiga group yang
cocok secara nama —

| `group_code` | `group_name` |
|---|---|
| 202007188 | RED MGTP-3221 |
| 202007190 | RED MGTP-3221 BASF |
| 202007191 | RED MGTP-3221 CLA |

Tapi tidak satupun `cst_rm_group_detail.item_code` yang cocok dengan
`SPC 3221`. Jadi ini **kegagalan resolusi nama saat migrasi**: legacy memakai
kode spec `SPC 3221`, sisi baru mengenal `RED MGTP-3221`, dan tidak ada
crosswalk di antaranya.

Klasifikasi: **`SCOPE` → `DATA`**. Bisa diperbaiki, dan perbaikannya jelas.

Mitigasi keadaan: keempat head berstatus `Waiting` dan `is_active = false`,
jadi tidak dipakai produksi saat ini. Tapi kalau salah satu di-approve tanpa
`SPC 3221` diperbaiki, cost-nya akan salah 25–60%.

---

## 6. Drift working set vs snapshot engine — 701 line

Ini temuan yang arahnya berlawanan dari dugaan awal, jadi perlu dibaca teliti.

| Perbandingan `mst_mb_composition` (working) vs `mst_mb_composition_version` (engine) | Line |
|---|---:|
| Baris ter-join | 21.863 |
| `composition_pct` berbeda | **0** |
| `source_type` berbeda | **0** |
| **`group_head_id` berbeda** | **701** |

Dari 701 line yang drift itu:

| Sisi mana yang sejalan dengan legacy | Line |
|---|---:|
| **Snapshot engine == legacy** | **690** |
| Working set == legacy | 5 |

Jadi yang menyimpang adalah **working set**, bukan snapshot. Seseorang mengubah
penugasan group di working set (`mst_mb_composition`) tanpa membuat versi baru,
sehingga engine masih memakai penugasan lama — yang justru masih cocok dengan
legacy.

Konsekuensinya:

- Angka 202608 **saat ini konsisten** dengan legacy pada 690 line itu.
- Begitu ada yang me-re-version MB tersebut, 701 penugasan group berubah dan
  cost-nya bergerak. Ini **risiko ke depan**, bukan selisih hari ini.
- Instruksi prompt untuk memakai `mst_mb_composition_version` (bukan
  `mst_mb_composition`) **terbukti benar dan penting**. Kalau Fase 3 memakai
  working set, 701 line akan salah dilaporkan sebagai selisih.

Ini paralel persis dengan catatan prompt di Fase 6 soal `cpc_rm_cost_detail`
vs `cst_rm_cost`: "apa yang dipakai engine" ≠ "apa yang tampil sekarang".

---

## 7. Identitas group per line

Dari 21.863 line yang ada di dua sisi:

| | Line |
|---|---:|
| Group identik | **21.773** |
| Group berbeda | 90 |

90 yang berbeda terpecah dua, dan pct-nya **identik di semua 90**:

**(a) 14 line — beda representasi, bukan beda data.** Sisi baru tidak menyimpan
group untuk komponen nested MB (dipakai `mbcv_mb_ref_mbh_id`), sementara legacy
tetap mengisi `CMBI_CGH_SYS_ID`. 13 di antaranya `MB → MB`, 1 adalah konversi
`GROUP → MB` pada head 20251004943 (§4.1). Bukan temuan.

**(b) 76 line — re-assignment group yang riil** (`GROUP → GROUP`, group berbeda).
Pola terkonsentrasi:

| legacy group | → group baru (engine) | Line |
|---|---|---:|
| `202007618` | `202504840` | **51** |
| `202310720` | `202007651` | 6 |
| `202503839` | `202007651` | 5 |
| `202006051` | `202007176` | 5 |
| `202310720` | `202007625` | 5 |
| `202007173` | `202504840` | 2 |
| `202310720` | `202503839` | 1 |
| `202006002` | `202007204` | 1 |

**51 line dari satu perpindahan**: group `202007618` → `202504840`, komponen
`PIG0000032`. Ini nyambung dengan dua temuan lain:

- `202007618` adalah **HOMBITAN LCS**, yang di Fase 2 muncul sebagai selisih
  material ACTUAL (legacy 3,777396 vs baru 5,453425, 44,37%).
- Ada tabel backup manual `bak_route_rm_hombitan_20260806` di live DB.

Jadi ada satu operasi re-grouping HOMBITAN/`PIG0000032` yang menyentuh **51
resep MB** dan sekaligus mengubah rate RM-nya. Ini kandidat kuat penyebab
lintas-fase, dan layak jadi satu item investigasi tersendiri di Fase 6.

Verifikasi tambahan: tidak ada line `source_type='GROUP'` dengan
`group_head_id` NULL atau tidak resolve (0 dari 21.510), dan semua 353 line
nested MB punya `mbcv_mb_ref_mbh_id` terisi (0 null). Jadi integritas
referensial sisi baru bersih.

---

## 8. Bucket wajib menurut prompt

### 8.1 Nested MB — `KNOWN-GAP`, 250 head

| | Nilai |
|---|---:|
| Line `source_type='MB'` (versi current) | 353 |
| Head yang memuatnya | **250** |
| `mbcv_mb_ref_mbh_id` null | 0 |
| Head dengan nested MB di salah satu sisi | 266 |

Legacy: 373 line / 265 head. Selisihnya sejalan dengan gap populasi head.
Dipisahkan sebagai bucket `KNOWN-GAP` sesuai instruksi; tidak dinilai
match/tidak-match di fase ini.

### 8.2 Boughtout — klaim prompt tidak berlaku

Prompt: *"Boughtout MB tidak punya recipe — hilang kalau pakai INNER JOIN."*
Di data 202608 itu **tidak benar**:

| `check_status` | `is_boughtout` | Head | Punya recipe |
|---|---|---:|---:|
| `Boughtout` | `false` | 130 | **129** |
| `Boughtout` | `true` | 7 | **7** |

**136 dari 137 head Boughtout justru PUNYA recipe** pada versi current. Saran
prompt untuk memakai LEFT JOIN tetap benar sebagai praktik, tapi alasannya
salah — yang hilang dengan INNER JOIN bukan boughtout, melainkan 34 head tanpa
komposisi versi current (33 di antaranya `current_version = 0`).

Temuan tersendiri: **dua penanda boughtout tidak sinkron.** `mbh_is_boughtout`
= true hanya 7 head, `mbh_check_status = 'Boughtout'` 137 head, irisannya 7.
Jadi flag boolean-nya praktis tidak terisi. Perlu diputuskan mana yang jadi
sumber kebenaran.

### 8.3 Carrier — flag tidak pernah terisi

`mbcv_is_carrier = true`: **0 dari 21.863 line** pada versi current.

Padahal perilaku carrier nyata ada di data — pada head 20251004943, PBT
menyerap selisih 17,95 persen poin persis seperti carrier remainder yang
disebut prompt. Artinya logika carrier berjalan tapi **tidak dicatat** di
kolomnya.

Konsekuensi praktis: `mbcv_is_carrier` **tidak bisa dipakai** untuk
mengidentifikasi carrier. Identifikasi harus lewat nama komponen (`PBT`).

---

## 9. Ringkasan untuk Executive Summary

| Scope | Objek | Match dalam toleransi | Selisih material | Tidak bisa dibandingkan |
|---|---:|---:|---:|---:|
| MB recipe — head | 4.325 head | 4.188 | **4** | 133 |
| MB recipe — line | 22.589 line | 21.861 | **2** | 726 |

Head "match dalam toleransi" = comparable (4.192) − materially off (4).
"Tidak bisa dibandingkan" head = 99 legacy-only + 30 recipe-missing-in-new + 4
no-recipe-both. Line "match" = 21.635 exact + 185 pembulatan + 41 NULL→0.

**Kesimpulan Fase 3: recipe MB adalah area yang paling sehat sejauh ini.**
21.861 dari 21.863 line yang bisa dibandingkan setara, dan dua selisih yang ada
adalah satu perubahan resep yang disengaja pada satu draft. Empat resep rusak
punya satu akar penyebab yang jelas dan bisa diperbaiki.

---

## 10. Temuan yang perlu keputusan

1. **`SPC 3221` gagal termigrasi di 4 resep** (§5). Perlu keputusan R&D: petakan
   `SPC 3221` ke `RED MGTP-3221` (`202007188`) atau varian BASF/CLA-nya? Empat
   resep tidak bisa di-approve sebelum ini beres.
2. **701 drift working-vs-engine** (§6). Perlu diputuskan: apakah perubahan
   group di working set memang disengaja dan perlu di-version (yang akan
   mengubah cost), atau justru harus dikembalikan? Ini keputusan bisnis, bukan
   teknis.
3. **Re-grouping HOMBITAN / `PIG0000032`** menyentuh 51 resep MB **dan** rate RM
   (§7). Perlu satu penelusuran khusus: kapan, oleh siapa, dan apakah dampak
   costing-nya sudah disetujui.
4. **Dua penanda boughtout tidak sinkron** (§8.2). Mana SSOT-nya —
   `mbh_is_boughtout` atau `mbh_check_status`?
5. **`mbcv_is_carrier` tidak pernah di-set** (§8.3). Apakah perlu di-backfill,
   atau kolomnya memang ditinggalkan?
6. **7 line orphan di legacy** (§4.2). Informasi saja; legacy sudah beku, tapi
   perlu dipastikan tidak ada yang bergantung padanya.
7. **Keterbatasan periode** (§1). Konfirmasi bahwa membandingkan master
   per-hari-ini dapat diterima untuk laporan 202608.

---

## 11. Definition of Done — Fase 3

- [x] Legacy `CST_MST_BATCH_HEAD` + `CST_MST_BATCH_ITEM` dipakai sebagai sumber
- [x] Sisi baru memakai `mst_mb_composition_version` @ `mbcv_version =
      mbh_current_version` (bukan working set), sesuai instruksi prompt
- [x] Crosswalk head & line eksplisit, dibuktikan 1:1 (0 null, 0 duplikat)
- [x] Validasi sum = 100,000 presisi 3 desimal, dua sisi
- [x] LEFT JOIN dari head — head tanpa recipe tidak hilang, dilaporkan terpisah
- [x] Bucket boughtout dilaporkan terpisah (dan klaim prompt diverifikasi ulang)
- [x] Bucket nested MB dipisahkan sebagai `KNOWN-GAP`
- [x] Artefak presisi dipisahkan dari selisih resep riil
- [x] Setiap selisih terklasifikasi; akar penyebab 4 resep rusak teridentifikasi
- [x] CSV tersimpan di `out/`, query rerunnable
- [x] Nol statement write, nol trigger recompute
