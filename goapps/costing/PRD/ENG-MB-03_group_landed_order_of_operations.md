# ENG-MB-03 — Group Landed Cost: Order-of-Operations Defect

**Versi:** 1.0
**Status:** Ready for dev (Ilham)
**Author:** Indra + Claude (reconciliation session 2026-08-12)
**Related:** `cst_rm_cost` group cost resolution; period 202607 MB reconciliation
**Kode terdampak:** `services/finance/internal/domain/rmcost/calculation.go`,
`services/finance/internal/domain/rmcost/entity.go` (`CalculateCost`)

---

## Ringkasan (1 paragraf)

Engine menghitung group landed cost sebagai **average-rate-lalu-landed**: rata-rata
tertimbang rate seluruh item dulu (`AggregateRates`), baru menerapkan formula landed
**sekali** dengan `cost_percentage`/`cost_per_kg` dari **header grup**. Legacy Oracle
menghitung **landed per item** (tiap item memakai komponen landed-nya sendiri: duty,
fix cost, freight), **lalu** merata-tertimbang landed antar item. Untuk grup dengan
≥2 item ber-qty yang punya rate/komponen berbeda, kedua urutan ini **tidak** ekuivalen.
Akibatnya group `cost_val` menyimpang dari legacy, dan tiap produk MB yang memakai grup
tersebut ikut mismatch di reconciliation.

Ini **engine defect murni** (bukan data). Perbaikan: hitung landed per item lalu
weighted-average, atau ekuivalen aljabar yang menyertakan komponen landed per item.

---

## Bukti (grup 202007214, period 202607)

Grup 202007214 punya 2 item ber-qty positif (setelah perbaikan data group-detail —
lihat catatan di bawah):

| item | grade | cons_qty | cons_rate | fix_cost | duty | **lndd_cst (legacy)** |
|---|---|---|---|---|---|---|
| DYE0000004 | PCI | 74.527  | 7.0428 | 60.5532  | 20.9952  | **8.1370** |
| DYE0000057 | PCI | 606.95  | 7.2932 | 493.1469 | 177.0632 | **8.3974** |

Perhatikan `fix_cost` dan `duty` **sangat berbeda** antar item (60.55 vs 493.15;
21.0 vs 177.1) — komponen landed melekat **per item**, bukan per grup.

### Angka legacy (target)
```
group landed = Σ(lndd_cst_i × qty_i) / Σ(qty_i)
             = (8.1370×74.527 + 8.3974×606.95) / (74.527 + 606.95)
             = (606.40 + 5096.71) / 681.477
             = 5703.11 / 681.477
             = 8.3689   ← = cgch_landed_cost legacy (cocok 4dp)
```

### Angka engine sekarang (salah)
```
avg rate = Σ(cons_val)/Σ(cons_qty) = (524.88 + 4426.58) / (74.527 + 606.95)
         = 4951.46 / 681.477 = 7.2657
cost_val = LandedCost(pct_header, 7.2657, perkg_header) = 7.4779   ← SALAH
```

### Sejarah nilai (menunjukkan defect muncul saat item ≥2)
| kondisi | item ber-qty | engine cost_val | legacy | catatan |
|---|---|---|---|---|
| sebelum fix data | 1 (DYE0000004 saja) | 8.1370 | 8.3689 | 1 item → landed = lndd item itu; beda karena item ke-2 hilang |
| sesudah fix data  | 2 | **7.4779** | 8.3689 | **defect urutan operasi terekspos** |
| target (legacy)   | 2 | — | 8.3689 | weighted-avg per-item-landed |

Saat hanya 1 item, average-then-landed dan landed-then-average kebetulan sama, jadi
defect tak terlihat. Begitu ada 2 item ber-rate/komponen beda, selisih urutan muncul.

---

## Root cause (kode)

`services/finance/internal/domain/rmcost/entity.go` — `CalculateCost`:

```go
func CalculateCost(items []RateInputs, h HeaderInputs) Computed {
    rates := AggregateRates(items)                                  // (1) rata-rata rate SELURUH item dulu
    valRate, valUsed := SelectRate(rates, h.FlagValuation, ...)     // (2) pilih stage dari rate agregat
    return Computed{
        CostValuation: LandedCost(h.CostPercentage, valRate, h.CostPerKg),  // (3) landed SEKALI, pakai pct/perkg HEADER
        ...
    }
}
```

`calculation.go`:
```go
func AggregateRates(items []RateInputs) StageRates {
    // Σ per-stage val / Σ per-stage qty  → satu rate per stage untuk seluruh grup
}
func LandedCost(costPercentage, selectedRate, costPerKg float64) float64 {
    return (costPercentage * selectedRate) + costPerKg   // pct & perkg skalar (dari header)
}
```

Masalah: `LandedCost` dipanggil **sekali di level grup** dengan `h.CostPercentage`
dan `h.CostPerKg` (header-level, satu nilai). Komponen landed **per item**
(`fix_cost`, `duty`, `freight`, `anti_dumping`, `transport` — yang di
`cst_rm_group_detail` tersimpan sebagai `valuation_*` per item) **tidak** ikut
terhitung. Legacy menerapkan komponen ini per item sebelum agregasi.

---

## Perbaikan yang diminta

Ubah urutan menjadi **landed-per-item → weighted-average**:

```
group_landed = Σ( landed_i × qty_i ) / Σ( qty_i )

di mana landed_i dihitung per item memakai komponen landed item tersebut:
  landed_i = f(rate_i, fix_cost_i, duty_i, freight_i, ...)   -- formula per item, samakan dg legacy
```

Formula `landed_i` harus direkonstruksi persis dari kolom legacy `cgci_lndd_cst`
(sudah terbukti: legacy `lndd_cst` per item = 8.1370 & 8.3974). Sumber komponen per
item di sisi new: kolom `valuation_*` pada `cst_rm_group_detail`
(`valuation_freight_rate`, `valuation_anti_dumping_pct`, `valuation_duty_pct`,
`valuation_transport_rate`, `valuation_default_value`) + `cst_item_cons_stk_po`.

**Catatan penting untuk dev:** Perlu dikonfirmasi apakah semua input `landed_i` sudah
tersedia per item di struktur new (rate per stage + komponen `valuation_*`). Jika belum
lengkap, ada dependency data yang harus diselesaikan lebih dulu. Rekomendasi: verifikasi
`landed_i` per item bisa direproduksi = `cgci_lndd_cst` legacy untuk 202007214
(8.1370 & 8.3974) sebagai unit test sebelum mengubah agregasi.

---

## Acceptance criteria

- [ ] AC1: Untuk grup 202007214 period 202607 (2 item), `cost_val` = **8.3689** (±0.0001).
- [ ] AC2: `landed_i` per item dapat direproduksi = legacy `cgci_lndd_cst`
      (DYE0000004=8.1370, DYE0000057=8.3974) — unit test pure-function.
- [ ] AC3: Grup 1-item tetap tidak berubah nilainya (tidak ada regresi pada grup
      yang sekarang sudah match).
- [ ] AC4: Grup multi-item lain yang sekarang match (rate & komponen item seragam)
      tetap match — tidak ada regresi.
- [ ] AC5: Setelah fix + recalc 202607, rollup mismatch_rm turun signifikan
      (kandidat: 8 grup rate-geser × ratusan produk).

---

## Unit test yang disarankan (pure function, tanpa I/O)

```go
// CalculateCost harus weighted-average per-item-landed, bukan landed-of-average-rate.
func TestCalculateCost_MultiItem_WeightedPerItemLanded(t *testing.T) {
    items := []RateInputs{
        // DYE0000004/PCI: rate 7.0428, qty 74.527, komponen landed → lndd 8.1370
        // DYE0000057/PCI: rate 7.2932, qty 606.95, komponen landed → lndd 8.3974
    }
    got := CalculateCost(items, header /* pct, perkg, flags */)
    want := 8.3689
    if math.Abs(got.CostValuation-want) > 1e-4 {
        t.Fatalf("group landed = %.4f, want %.4f", got.CostValuation, want)
    }
}
```

---

## Dampak & scope

- **Grup terdampak:** semua grup multi-item ber-rate/komponen berbeda. Terdeteksi
  minimal pada 8 grup "rate-geser" 202607 (202007214, 202007594, 202006047, 202007209,
  202504840, 202007651, 202007207, 202502827) yang bersama menjangkau ~600+ pemakaian
  produk. Kemungkinan lebih banyak grup multi-item lain juga terdampak namun tersamar.
- **Produk terdampak:** grup 202007214 saja dipakai 328 produk MB di 202607.

---

## Catatan operasional (WAJIB dibaca sebelum eksekusi)

1. **Perbaikan data group-detail sudah dilakukan dan BENAR — JANGAN di-rollback.**
   Item DYE0000057/PCI di-INSERT ke `cst_rm_group_detail` grup 202007214
   (created_by='fix-grpdetail-202607') karena memang hilang dari master new (legacy
   punya 9 item member, new hanya 8). INSERT ini valid dan diperlukan; ia justru yang
   mengekspos ENG-MB-03. Ada ~14 item qty>0 hilang di ~11 grup lain yang perlu perlakuan
   serupa (terdaftar terpisah) — TETAPI tunda sampai ENG-MB-03 di-fix, agar recalc tidak
   menyebarkan angka salah.

2. **Recalc produk DITUNDA sampai engine diperbaiki.** Recalc sekarang menghasilkan
   cost_val lebih salah (7.4779) daripada sebelum fix data (8.1370). Menjalankan recalc
   produk hanya akan menyebarkan nilai salah ke ratusan produk.

3. **Scope periode:** hanya 202607 yang di-recalc. 202606 sengaja tidak di-recalc
   (keputusan IT Lead — komponen RM berubah, register 202606 sudah final). `cst_rm_group_detail`
   adalah master tanpa kolom period; perubahan member akan mempengaruhi 202606 HANYA JIKA
   202606 di-recalc. Selama tidak, angka 202606 tidak bergerak.

4. **Urutan kerja yang benar:** fix ENG-MB-03 (dev) → recompute group rate 202607 →
   verifikasi grup 202007214 cost_val=8.3689 → recalc produk 202607 → rollup ulang.

---

## KEPUTUSAN 2026-08-13 — item multi-grup = known-gap (diterima)

**Konteks:** Constraint `uk_rm_group_detail_item_grade_active` melarang satu
`item_code + grade_code` menjadi anggota >1 grup. Legacy Oracle membolehkannya
(satu bahan baku di banyak grup, kadang dengan rate berbeda per grup — inkonsistensi
legacy). ~30 item terdampak; user telah meng-assign tiap item ke SATU confirmed_group
di sistem baru.

**Investigasi:** Resep produk (contoh mbh_oracle_sys_id=20201102813, pakai grup
202007173) menunjukkan produk menunjuk grup karena **identitas utama grup** (mis.
"HOMBITON LCS"), BUKAN karena item multi-grup minor (mis. TiO2 PIG0000048) yang
kebetulan juga anggota. Karena itu **menggeser mapping resep ke confirmed_group SALAH** —
produk akan mendapat grup yang tidak sesuai kebutuhannya.

**Keputusan (IT Lead):**
- Item multi-grup tetap di confirmed_group. Grup lain & resep produk **tidak diubah**.
- Selisih cost yang timbul pada produk yang memakai grup-grup tersebut ditandai sebagai
  **known-gap "legacy item multi-grup"** — tidak dikejar. Justifikasi: legacy inkonsisten;
  sistem baru memaksa konsistensi (satu item → satu grup → satu rate). Selisih = koreksi,
  bukan defect.
- INSERT batch item hilang (48 baris) **dibatalkan** — item qty>0 yang berdampak semuanya
  kena constraint multi-grup. Hanya DYE0000057/PCI (grup 202007214, item tunggal yang
  memang hilang) yang valid & sudah di-COMMIT. Varian DDR tunggal (DYE0000066, DYE0000062)
  boleh di-INSERT terpisah bila diperlukan (kecil, tidak mendesak).

**Sisa mismatch MB 202607 = kombinasi terjustifikasi:**
1. ENG-MB-03 (order-of-operations) — menunggu fix Ilham; grup multi-item lengkap akan match.
2. Known-gap item multi-grup — diterima (keputusan ini).
3. Group-0 Finance-blocked (rate legacy=0) — diterima, menunggu Finance.

MB reconciliation dihentikan di titik "cukup & tertandai". Fokus beralih ke Yarn costing.
