# Agenda Sesi Validasi — Production Plan System
## PT Mutu Gading Tekstil | Tim PPC

> **Tujuan sesi:** Mengkonfirmasi open items yang blocking desain sistem
> sebelum masuk ke PRD formal dan development.
>
> **Durasi:** 1.5 – 2 jam (bisa dibagi 2 sesi jika perlu)
> **Peserta:** IT Leader, PPC Lead, Production Manager (untuk item yang butuh approval decision)
> **Output:** Semua open items resolved → siap masuk PRD formal

---

## Persiapan Sebelum Sesi

**IT Leader siapkan:**
- Dokumen Design Decisions (23 keputusan yang sudah dikunci) → review ulang sebelum sesi
- Dokumen Open Items → ini yang akan dibahas
- Contoh mockup flow sederhana (opsional) untuk bantu visualisasi

**Tim PPC siapkan:**
- Data historis changeover jika ada (estimasi waktu, waste)
- Contoh kasus real: produk yang sering over/under produksi
- Contoh jadwal produksi bulan lalu (untuk referensi pola kerja)

---

## Agenda

### Pembukaan (10 menit)

IT Leader recap singkat:
- Apa yang akan dibangun (sistem pengganti Excel planning)
- 3 layer: Demand → Plan Item → Work Order
- Apa yang sudah dikunci (23 keputusan)
- Tujuan sesi hari ini: validasi 3 hal yang masih terbuka

---

### Blok 1 — Threshold Over-Production (30 menit)

**Konteks untuk PPC:**
> Saat ini sistem direncanakan punya 3 level threshold:
> - 0–2% over → OK, produksi lanjut
> - 2–5% over → warning muncul ke PPC
> - >5% over → material issue di-block, butuh approval Production Manager

**Pertanyaan yang harus dijawab:**

1. **Angka realistis** — apakah 2% dan 5% masuk akal untuk kondisi produksi PT Mutu Gading?
   - Contoh: PTY 150/36 target 1.000 kg — berapa toleransi yang wajar?
   - Apakah ada produk yang karakternya berbeda (high-value vs komoditi)?

2. **Diferensiasi per produk** — apakah threshold perlu berbeda per jenis?
   - Contoh: PTY high-value (customer khusus) → lebih ketat dari MTS komoditi?
   - Atau satu threshold global lebih simpel untuk awal?

3. **Siapa yang approve saat block?**
   - Production Manager saja? Atau PPC Lead cukup?
   - Apakah perlu ada batas waktu approval? (mis. harus respond dalam X menit sebelum mesin idle)

**Output yang diharapkan:** Angka threshold final (bisa 1 global atau per kategori) + nama jabatan approver

---

### Blok 2 — Changeover Data & Kategorisasi (30 menit)

**Konteks untuk PPC:**
> Sistem akan menyimpan estimasi waktu dan waste changeover dalam bentuk matrix:
> (mesin × dari produk → ke produk) = estimasi waktu + estimasi waste
>
> Tujuan: PPC bisa lihat dampak sebelum memutuskan urutan produksi

**Pertanyaan yang harus dijawab:**

1. **Ada data atau hanya rule of thumb?**
   - Apakah saat ini ada catatan estimasi changeover per perpindahan produk?
   - Atau selama ini hanya perkiraan kepala mesin?

2. **Apakah kategori berikut masuk akal?**

   | Kategori | Kondisi | Estimasi waktu | Estimasi waste |
   |----------|---------|----------------|----------------|
   | MINOR | Ganti variant kecil, same family | 1–2 jam | 10–20 kg |
   | MEDIUM | Ganti denier ATAU ganti warna | 2–4 jam | 20–50 kg |
   | MAJOR | Ganti denier DAN warna | 4–8 jam | 50–100 kg |
   | DEEP CLEAN | Ganti spec drastis / periodik | 8+ jam | — |

   - Apakah ada kategori yang tidak sesuai kondisi di lapangan?
   - Apakah estimasinya terlalu longgar atau terlalu ketat?

3. **Changeover yang sudah sangat rutin:**
   - Ada perpindahan produk yang SOP-nya sudah baku? (mis. PTY 150D Natural → PTY 150D Off-White)
   - Data ini bisa langsung jadi seed data di sistem

4. **Siapa yang input/maintain matrix changeover?**
   - PPC? Kepala produksi? IT bisa input awal dari data yang PPC berikan

**Output yang diharapkan:** Konfirmasi kategori changeover + data estimasi awal (bisa rough) + PIC yang maintain

---

### Blok 3 — MTS Decision Rule (20 menit)

**Konteks untuk PPC:**
> MTS (Make to Stock) adalah produksi untuk stok, bukan untuk order spesifik.
> Pertanyaan: kapan dan siapa yang boleh inisiasi MTS di tengah bulan?

**Pertanyaan yang harus dijawab:**

1. **Ada trigger rule atau murni judgement?**
   - Apakah ada kondisi yang biasanya memicu MTS? Contoh:
     - Mesin idle lebih dari X jam
     - Stok produk tertentu di bawah safety stock
     - Customer biasanya repeat order produk ini
   - Atau PPC memutuskan case-by-case?

2. **Siapa yang perlu di-notify/approve?**
   - PPC inisiasi sendiri cukup?
   - Atau perlu notifikasi ke Production Manager?
   - Apakah Sales perlu tahu kalau ada MTS baru?

3. **Apakah ada kuota MTS per bulan?**
   - Ada batasan % dari total kapasitas yang boleh dipakai untuk MTS?
   - Atau tidak ada batasan formal?

**Output yang diharapkan:** Decision rule yang jelas (trigger + approver + notifikasi)

---

### Blok 4 — Tambahan: Konfirmasi User & Akses (15 menit)

> Ini belum ada di Open Items tapi penting untuk PRD.

**Pertanyaan:**

1. **Siapa saja yang akan pakai sistem ini?**
   - Tim PPC (berapa orang?)
   - Production Manager (monitoring saja atau bisa input?)
   - Operator produksi (hanya input actual hasil, atau lebih?)
   - Sales/Marketing (view order status saja?)
   - BOD (dashboard monitoring saja?)

2. **Frekuensi penggunaan:**
   - PPC: setiap hari? Berapa kali sehari?
   - Manager: daily review? Weekly?

3. **Akses dari mana?**
   - Desktop/laptop saja atau butuh mobile?
   - Dari dalam pabrik (production floor) atau kantor saja?

**Output yang diharapkan:** Daftar user role + frekuensi + device

---

### Penutupan (10 menit)

- Recap keputusan yang diambil
- Konfirmasi semua open items resolved
- Next step: IT Leader buat PRD formal dalam 1–2 sesi dengan Claude

---

## Template Notulensi

```
Sesi Validasi PPC — Production Plan System
Tanggal: [isi]
Peserta: [isi]

BLOK 1 — THRESHOLD OVER-PRODUCTION
Threshold yang disepakati: [isi]
Diferensiasi per produk: Ya / Tidak → [detail]
Approver saat block: [jabatan]

BLOK 2 — CHANGEOVER
Kategori changeover disepakati: Ya / Perlu diubah → [detail]
Data existing: Ada / Hanya rule of thumb
PIC maintain matrix: [nama/jabatan]
Seed data tersedia: Ya / Perlu dibuat dari awal

BLOK 3 — MTS
Decision rule: [rule trigger]
Approver MTS baru: [jabatan]
Notifikasi ke: [list]
Kuota MTS: Ada / Tidak ada

BLOK 4 — USER & AKSES
User roles: [list dengan jumlah orang]
Device: Desktop / Mobile / Keduanya
Frekuensi: [per role]

NEXT STEPS:
- [ ] IT Leader buat PRD formal (target: [tanggal])
- [ ] PPC siapkan data changeover awal (target: [tanggal])
- [ ] [item lain]
```

---

*Source: github.com/mutugading/docs-markdown/goapps/production-plan/AGENDA_PPC_VALIDATION.md*
*Dibuat: 15 Juni 2026 | Owner: IT Leader*
