# PRD — TQM: Pengembangan Produk & Sampel (migrasi Oracle TQM + TQM RND → GoApps)

Status: DRAFT v0.4 · 2026-11-19 (K2 diganti: modul di finance) · Pemilik: (isi nama pemilik proyek)
Rujukan: `LEGACY-MAP.md` (peta form, tabel, alur, dan angka production; kode `L1`–`L6`).

## 1. Latar belakang

Di Oracle Forms ada dua aplikasi terpisah dengan alur yang sulit diikuti:

- **TQM / Color Development (CDM):** Marketing atau TQM meminta pengembangan warna (`MGT-PD`).
  TQM meneruskannya ke Masterbatch, menerima hasilnya, menguji, lalu mengirim shade card/bobbin
  ke customer lewat Marketing (L2).
- **TQM RND / Sample Market Advice (SMA):** TQM membuat advice pengiriman sampel benang ke customer
  untuk trial, lengkap dengan harga, HS code, dan berat (L3).

Keduanya memakai tabel user, master feedback, pola approval, dan form despatch yang sama
(L1, L4). Jadi ini **satu proses bisnis**: *permintaan pengembangan → sampel → kirim → feedback
customer*. Hanya saja proses itu terpecah ke dua login. Bagian akhirnya, yaitu feedback dan
status komersialisasi, praktis tidak pernah diisi (0 dari 2.232 untuk CDM, 6 dari 21.097 untuk
SMA, L5). Akibatnya tidak ada yang tahu sampel mana yang akhirnya menjadi order, padahal
informasi itu yang dibutuhkan **costing**.

## 2. Tujuan

| # | Tujuan | Ukuran |
|---|---|---|
| G1 | Satu modul dan satu login untuk seluruh siklus pengembangan & sampel | form CDM + SMA = 1 menu GoApps |
| G2 | Alur terlihat: setiap permintaan punya status dan riwayat langkah yang jelas | timeline per dokumen, tidak ada langkah tanpa jejak |
| G3 | Feedback customer benar-benar tercatat | ≥ 80% sampel terkirim punya feedback ≤ 60 hari |
| G4 | Sampel yang lolos bisa langsung menjadi **Cost Product Request** tanpa ketik ulang | tombol "Ajukan Costing" mengisi customer + spek + shade |
| G5 | Approval terkontrol | hanya role approver yang bisa approve, ada reject, riwayat, dan audit log |

Di luar lingkup: Color Kitchen (`MGT_CK_*`), proses produksi masterbatch itu sendiri,
dan despatch umum (`MGT_DES_SMPL` di luar sampel TQM).

## 3. Analisis: digabung atau tidak?

**Rekomendasi: digabung menjadi satu modul dengan dua jenis permintaan dan alur hilir bersama.**

| Aspek | CDM | SMA | Kesimpulan |
|---|---|---|---|
| Pemicu | customer minta warna/shade | customer minta sampel benang | sama-sama *permintaan customer* |
| Langkah tengah | kirim ke MB → terima → uji | (siapkan sampel di TQM) | **khusus CDM**: subproses "Lab/Supplier Trial" |
| Kirim | Send to Marketing → Despatch | Despatch | **sama** (TQMCDM10 sudah melayani keduanya) |
| Feedback | per detail (FB status, commercialized) | per item (FB status, commercialized) | **sama**, field dan master-nya identik |
| Approval | Request/Approve/Amendment | Request/Approve/Amendment | **sama** |
| User | CDM, SPB | SMA (TQM, TWT, TFO) | satu master user (IAM) dengan role |

Yang tetap dibedakan: isi header. CDM memakai spek warna berpasangan customer/MGT (L2),
sedangkan SMA memakai data pengiriman komersial (harga, HS code, asuransi, invoice). Keduanya
dimodelkan sebagai **satu entitas `Development Request`** dengan `type = COLOR | SAMPLE` dan
bagian detail sesuai tipe. Dengan begitu daftar, status, approval, despatch, feedback, dan
tautan costing cukup dibangun sekali.

Alternatif yang ditolak:
- *Dua modul terpisah (salin 1:1):* masalah yang sama terulang, dan feedback/costing harus dibuat dua kali.
- *Satu dokumen tanpa tipe:* header SMA dan CDM terlalu berbeda, sehingga hasilnya form besar penuh field kosong.

## 3a. Keputusan yang sudah disepakati

| ID | Keputusan |
|---|---|
| K1 | Permintaan COLOR **bisa** berlanjut menjadi SAMPLE. SAMPLE menyimpan tautan opsional `source_request_id` (+ varian) ke COLOR asalnya. SAMPLE tanpa asal COLOR tetap boleh |
| K2 | Modul berjalan **di dalam service `finance`** (keputusan PM 2026-11-19: terlalu kecil untuk service sendiri), dengan tabel sendiri berprefix `tqm_sample_`, tidak berbagi tabel CPR. Nama role/permission/tabel memakai `tqm_sample` supaya aplikasi TQM lain kelak punya prefix sendiri. Detail teknis: TQ-01, TQ-02 |
| K3 | Costing diajukan **saat feedback customer = Approved** (bukan Waiting Order, bukan sebelum kirim) |
| K4 | Divisi **Masterbatch login ke GoApps** dan mengisi sendiri penerimaan & hasil lab trial (role `TQM_SAMPLE_LAB`) |
| K5 | Format nomor legacy **dipertahankan** (`MGT-PD-NNN/YYYY`, `SMA-NNN/YYYY`, `TQM/NNN/MM/YYYY`), tetapi selalu **dibuat oleh sistem** (counter per jenis per tahun di DB, unik, aman konkurensi). User tidak bisa mengetik atau mengubah nomor |
| K6 | Approver = **Head of TQM** (role `TQM_SAMPLE_APPROVER`), satu level, untuk semua tipe dan divisi |
| K7 | Properti teknis benang (tenacity, elongation, twist, OPU, dst., legacy `MGT_SMA_ITEM_DETAIL`) **dihidupkan lagi** sebagai bagian item SAMPLE |
| K8 | **Mulai kosong:** dokumen Oracle tidak dimigrasi. Hanya master data yang dibawa. Oracle tetap menjadi arsip read-only. Counter nomor tahun cut-over dimulai dari nomor terakhir Oracle + 1 supaya tidak bentrok |
| K9 | Tombol **Ajukan Costing** hanya untuk **marketing yang mengisi feedback** item itu (role `TQM_SAMPLE_REQUESTER`, pengisi feedback = user yang menekan) |

## 4. Alur target

```
            ┌───────────── type = COLOR ─────────────┐
DRAFT ─▶ SUBMITTED ─▶ APPROVED ─▶ LAB TRIAL ─▶ RESULT OK ─┐
   ▲          │                   (MB/supplier)  │ NOT OK → trial ulang
   └─revisi───┘ REJECTED                         │
            └───────────── type = SAMPLE ────────┴──▶ READY TO SEND ─▶ DESPATCHED ─▶ FEEDBACK
                                                                                                  │
            Feedback per item/varian:                                                             │
              APPROVED ──▶ [Ajukan Costing] → CPR (K3) ──▶ WAITING ORDER → COMMERCIALIZED ◀───────┤
              REDEVELOPMENT → permintaan turunan (parent_id)                                      │
              REJECTED / CANCELLED → CLOSED ◀─────────────────────────────────────────────────────┘

COLOR varian OK ──[Buat SAMPLE]──▶ SAMPLE baru dengan source_request_id (K1)
```

Perbedaan dari legacy:
1. **Amendment** di legacy hanya mengembalikan status ke `Request` dengan `revision+1`. Di target menjadi
   aksi *Revise* yang menyimpan snapshot versi sebelumnya.
2. **Redevelopment** menjadi permintaan anak yang tertaut (`parent_id`), sehingga riwayat percobaan bisa ditelusuri.
3. **Feedback wajib** sebelum dokumen bisa ditutup, dan dokumen yang sudah *DESPATCHED* lebih dari N hari
   tanpa feedback muncul di daftar pengingat.
4. Langkah MB "Accept from TQM" dan "Send to TQM" (TQMCDM12/11) digabung menjadi satu tugas
   *Lab Trial* dengan status Diterima → Selesai dan hasil per varian (dev no). Tugas ini dikerjakan
   langsung oleh user MB di GoApps (K4).
5. Tautan COLOR → SAMPLE (K1) membuat satu rantai yang bisa ditelusuri: permintaan warna → varian OK →
   sampel benang → feedback → costing.

## 5. Kebutuhan fungsional

### F1. Master data
- F1.1 Color master: base color + ID warna otomatis (aturan L2 TQMCDM05), dengan import Excel.
- F1.2 Lookup terkendali sebagai pengganti teks bebas: sample form (Pom-Pom, Strip, 2 Strip, Shade Strip,
  Baby Cone, Shade Card, Bobbin …), polymer, luster, cross section, fastness, end use, matching,
  light source, subject kirim, FB status, commercialized status, kurir.
- F1.3 Customer **diambil dari master customer finance** (salinan Oracle `OM_CUSTOMER`, TQ-05; bukan teks bebas). Kalau customer belum ada,
  input sementara ditandai sebagai prospek.
- F1.4 Supplier/lab trial: MGT Masterbatch (internal) + supplier eksternal.

### F2. Development Request
- F2.1 Nomor dibuat sistem saat **submit** (draft belum bernomor), dengan format legacy (K5). Counter
  disimpan per jenis + tahun (+ bulan untuk `TQM/NNN/MM/YYYY`, yang di-reset per tahun seperti legacy), dikunci
  dalam transaksi, dan nomor yang sudah terpakai tidak dipakai ulang walau dokumennya dibatalkan.
  Nilai awal tahun cut-over di-seed dari Oracle (K8).
- F2.2 COLOR: spek customer vs MGT berpasangan, referensi, prioritas, potensi, catatan, lampiran (foto/scan).
- F2.3 SAMPLE: header kirim (agen, kontak, negara, alamat, alasan kirim, biaya, estimasi harga jual)
  + item (produk, denier/filamen, qty, harga, HS code, berat) + **properti teknis** (K7): BWS, contraction,
  elongation, entanglement, fiber shrinkage, knot retention 1st/2nd, modulus, OPU, paper tube, stability,
  tenacity, torque, total recovery, twist direction, twist/m. Ikut tercetak di Sample Advice.
- F2.4 Status machine (§4), approval oleh **Head of TQM** (K6, cek role `TQM_SAMPLE_APPROVER`, TQ-04),
  dengan aksi submit, approve, reject, revise, dan cancel.
- F2.5 Duplicate: salin permintaan lama sebagai dasar permintaan baru (legacy punya `pkg.duplicate`).
- F2.6 **Buat SAMPLE dari COLOR** (K1): dari varian dengan hasil lab OK, buat SAMPLE baru yang terisi
  customer, produk, dan shade, lalu simpan `source_request_id` dan varian asalnya. Dari dokumen COLOR,
  semua SAMPLE turunannya terlihat. Karena mulai kosong (K8), tidak ada tautan historis.

### F3. Lab trial (khusus COLOR)
- F3.1 Kirim ke lab/supplier: ref name, ref code, produk cocok, POY kg, qty bobbin.
- F3.2 User MB (K4) menerima tugas di GoApps, lalu mengisi hasil per varian: dev no/name, shade code/name, range dosing,
  MB LDR, TR no, dan status OK/NOT OK dengan catatan.
- F3.3 Varian OK dicatat dengan kode/nama shade-nya sebagai teks, tanpa menunggu finance. TQM tidak membuat shade
  di master. Shade masuk master costing lewat Orion + sync saat masuk produksi (TQ-05).

### F4. Kirim sampel
- F4.1 Kirim ke marketing/customer: subjek (shade card, bobbin), item yang dikirim.
- F4.2 Despatch: kurir, alamat, paket, dimensi, berat, rate/kg, tanggal kirim. Satu layar untuk kedua tipe.
- F4.3 Cetak dokumen: Sample Development Format, surat ke supplier, Sample Advice, label/packing
  (pengganti CDM001–009, TQMRND01/02) sebagai PDF.

### F5. Feedback customer
- F5.1 Per item/varian: FB status (Approved, Rejected, Redevelopment, Cancelled), tanggal, catatan,
  dan status komersial (Waiting Order, Commercialized).
- F5.2 Redevelopment membuat permintaan anak (F2) yang terisi dari induknya.
- F5.3 Pengingat: daftar sampel terkirim yang belum punya feedback, per marketing PIC.

### F6. Integrasi costing (Cost Product Request, finance)
- F6.1 Tombol **Ajukan Costing** muncul hanya pada item/varian dengan feedback **Approved** (K3), hanya untuk
  marketing yang mengisi feedback tersebut (K9). Tombol ini membuat
  `CostProductRequest` (`product_classification = new`) dengan `customer_name/code`, deskripsi produk,
  `shade_code`/`shade_name`, `target_volume`, dan `target_price_range`
  (dari estimasi harga jual SMA), lalu menyimpan `request_id` CPR di item TQM. Karena satu service (K2), CPR dibuat dalam satu
  transaksi dengan tautannya (TQ-06), dan satu item hanya bisa punya satu CPR aktif (idempoten). Kalau item berasal dari COLOR (K1), riwayat lab ikut dirujuk.
- F6.4 Kalau feedback Approved diubah (mis. menjadi Redevelopment) setelah CPR dibuat, CPR **tidak**
  dibatalkan otomatis. Dokumen TQM menampilkan peringatan, dan pembatalan dilakukan manual di CPR.
- F6.2 Arah sebaliknya: dari CPR, reviewer bisa melihat riwayat TQM (sampel, hasil lab, feedback) sebagai
  referensi kelayakan.
- F6.3 Status CPR (Released/Closed) tampil di dokumen TQM, sehingga terlihat dari sampel mana costing berasal.

### F7. Laporan
- F7.1 Status pipeline (pengganti CDM008/Excel): MGT-PD → lab → kirim → feedback, dengan filter tanggal dan customer.
- F7.2 Funnel: permintaan → sampel terkirim → approved → order → costing, per tahun, customer, atau tipe.
- F7.3 Lead time per langkah dan backlog per divisi.
- F7.4 Export Excel di setiap daftar.

## 6. Peran & hak akses

Pengganti `USER_TYPE` + `c_divn` (L4) menjadi role IAM:

| Role | Hak |
|---|---|
| `TQM_SAMPLE_REQUESTER` (MKT) | buat COLOR request, lihat status, isi feedback, **Ajukan Costing** atas feedback yang ia isi (K9) |
| `TQM_SAMPLE_OFFICER` (TQM/TWT/TFO) | semua request, kirim ke lab, terima hasil, kirim sampel, SAMPLE advice |
| `TQM_SAMPLE_LAB` (MB, login sendiri: K4) | hanya tugas lab trial yang ditujukan ke MB: terima, isi hasil, selesai |
| `TQM_SAMPLE_APPROVER` (Head of TQM, K6) | approve/reject semua tipe |
| `TQM_SAMPLE_DESPATCH` | despatch |
| `TQM_SAMPLE_ADMIN` | master data |

Permission mengikuti format `{service}.{module}.{entity}.{action}` = `finance.tqmsample.<entity>.<action>`.
Menu TQM tetap menu top-level sendiri (bukan di bawah Finance).

## 7. Migrasi data (K8: mulai kosong)

- M1. **Dokumen tidak dimigrasi.** Seluruh `MGT_CDM`, `MGT_SMA_*`, `MGT_CDM_SEND_*`, `RECEIVED_*`, dan despatch
  tetap di Oracle. Dokumen yang masih berjalan saat cut-over diselesaikan di Oracle.
- M2. **Master data yang dibawa:** color master (`MGT_CDM_COLOR_MASTER`, ±4.346), base color, supplier, dan lookup
  (polymer, luster, cross section, fastness, end use, matching, subject, FB status, commercialized). Nilai teks
  bebas legacy (L6) dipakai sebagai bahan untuk menyusun daftar lookup, setelah direview TQM.
- M3. **Seed counter nomor** (K5): nomor terakhir per jenis untuk tahun cut-over diambil dari Oracle saat cut-over.
- M4. User dibuat di IAM dan diberi role (§6). `TQM_USERS` tidak disalin, termasuk password-nya.
- M5. Akses arsip: Oracle menjadi read-only setelah cut-over. Kebutuhan laporan histori di GoApps (kalau ada)
  dibahas terpisah dan tidak termasuk lingkup ini.
- M6. Cut-over: satu tanggal untuk kedua tipe, ditentukan bersama TQM, dengan masa paralel singkat (dokumen baru
  hanya di GoApps).

## 8. Kebutuhan non-fungsional

- Mengikuti konvensi GoApps: proto `finance/v1/tqm_sample_*.proto` di `goapps-shared-proto`, modul Go di
  `services/finance` dengan tabel `tqm_sample_*` (K2), frontend Next.js, dan audit log.
- Tabel CPR tidak diubah. TQM membuat CPR hanya lewat use case CPR, tidak menulis tabel CPR langsung.
- Uang (harga, biaya, invoice) memakai decimal, tidak boleh float.
- Lampiran disimpan di object storage yang sudah dipakai modul lain.
- Nomor dokumen dijamin unik (constraint DB) dan dibuat sistem (K5).

## 9. Pertanyaan terbuka

Belum ada. Semua pertanyaan O1–O9 sudah dijawab: O1/O2/O5/O6 → K1–K4, O3 → K5, O4 → K6, O7 → K7,
O8 → K8, O9 → K9.

## 10. Fase usulan

| Fase | Isi |
|---|---|
| 1 | Modul TQM di finance + master customer finance + master data (import dari Oracle) + penomoran sistem + Development Request (COLOR & SAMPLE + properti teknis, tautan K1) + approval Head of TQM |
| 2 | Lab trial (user MB, K4) + kirim sampel + despatch + cetak PDF |
| 3 | Feedback + pengingat + integrasi CPR (F6) |
| 4 | Laporan funnel/lead time + seed counter + cut-over Oracle (tanpa infra baru, ikut deploy finance) |
