# Keputusan — RM Ratio Berbasis Denier (MEL & ACY)

**Tanggal**: 2026-10-13 · **Status**: Implemented (belum deploy) · **Migration**: finance `000570_rm_ratio_denier`

## Latar Belakang

Calc engine menghitung RM Cost sebagai `Σ(unit cost × ratio route)`. Ratio route adalah input murni dan benar untuk product
seperti MB atau POY yang ratio-nya memang diinput. Product **Melange (MEL)** dan **Air Covered Yarn (ACY)** memakai lebih dari
satu yarn sebagai RM, dan di data ratio-nya diisi `1` dan `1`. Akibatnya RM Cost menjumlahkan cost penuh setiap yarn (overstated).

Hasil diskusi dengan tim finance: untuk MEL dan ACY, ratio dihitung dari **porsi denier** masing-masing yarn.

## Rumus

### MEL — 2 (atau lebih) intermediate product POY

```
Ratio POY_i  = Denier POY_i / Σ Denier POY
Cost POY_i   = Ratio POY_i × CapCost with Q.Loss POY_i     (TOP 13 = DENIER, TOP 105 = CAPTIVE_COST_QLTY_LOSS)
RM Cost MEL  = Σ Cost POY_i
```

### ACY — intermediate product (PTY/POY) + RM group bought-out spandex (SPD)

```
Denier Spandex = Denier RM Group Head (20 / 40 / 70) / SPANDEX_DRAFT product (default 3, bisa 3,3 dst)
Ratio PTY      = Denier PTY     / (Denier PTY + Denier Spandex)
Ratio Spandex  = Denier Spandex / (Denier PTY + Denier Spandex)
Cost PTY       = Ratio PTY     × CapCost with Q.Loss PTY
Cost Spandex   = Ratio Spandex × Landed cost spandex
RM Cost ACY    = Cost PTY + Cost Spandex
```

Landed cost spandex mengikuti flag di RM Group Head:
- **ACTUAL** → valuation flag; AUTO = CL → SL → FL → PR (first non-zero)
- **FORECAST** → marketing flag; AUTO = SP → PP → FP

Total ratio selalu = 1, berapa pun jumlah yarn-nya.

**Contoh (data prod 202609)**: PTY 150D (cap 1,890299) + spandex 40D / 3 = 13,333D, landed 100
→ ratio 0,918367 / 0,081633 → RM Cost = 1,735989 + 8,163265 = **9,899254**.

## Keputusan

| # | Keputusan | Alasan |
|---|-----------|--------|
| 1 | Penanda per **Product Type**: `RM Ratio Mode` = `INPUT` (default) / `DENIER`. MEL & ACY = `DENIER`. | Product type lain tidak tersentuh; bisa diaktifkan untuk type lain tanpa ubah kode. |
| 2 | Denier spandex disimpan di **RM Group Head** (`Denier`), pembaginya di param product **`SPANDEX_DRAFT`** (seed 3 untuk semua ACY, bisa diedit per product). | Denier adalah spesifikasi fisik group; draft bisa berbeda per product. |
| 3 | Landed cost spandex mengikuti flag RM Group Head (bukan cascade engine lama). Hanya untuk product mode `DENIER`. | Sesuai aturan finance; product lain tidak berubah. |
| 4 | Baris RM tanpa denier (mis. oil group, RMP0000087, item) tetap pakai ratio input. | Tidak semua RM adalah yarn. |
| 5 | Denier/draft kosong atau 0 → dihitung **0**, kalkulasi **tidak gagal**, dan ada **catatan** di calc job (drill-down product). | Keputusan user saat approval. |
| 6 | Tidak ada param baru untuk "Cost POY 1/2"; rincian denier, ratio, kontribusi per baris tampil di tab RM hasil cost (kolom *Denier*, *Ratio Src*). | Formula lanjutan (RM_RATE, RM_LANDED_COST, captive cost) tidak perlu diubah. |

## Kondisi Prod (2026-10-13)

- MEL: 0 product aktif. Sebagian product MEL ter-seed sebagai PTY → akan diperbaiki terpisah (list dari user).
- ACY: 106 product aktif, umumnya 1 PTY/POY + 1 spandex (SPD0000006/7/8/10).
- Nama spandex group hanya kode, jadi **Denier harus diisi** di menu RM Group sebelum recalc ACY.
  Selama Denier kosong, spandex tetap pakai ratio input (hasil ACY sama seperti sebelumnya).

## Langkah Setelah Deploy

1. Isi **Denier** di RM Group spandex (SPD0000006/7/8/10, dan 001–005 jika dipakai).
2. Cek `SPANDEX_DRAFT` per product ACY (default 3).
3. Recalc periode, lalu cek kolom *Denier / Ratio Src* di tab RM dan catatan di calc job.

## Batasan Diketahui

- VB-loss inheritance masih memakai ratio route (relevan untuk MEL multi-POY).
- Catatan denier hanya tersimpan untuk product yang sukses dihitung.
