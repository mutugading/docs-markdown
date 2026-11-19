# TQM — BACKLOG (kasar, dipecah jadi task saat gerbang fase sebelumnya lolos)

## Fase 2 — Lab trial, pengiriman, cetak (PRD§F3, §F4; K4)
- Q020 Lab trial backend: kirim ke lab (nomor TQM_SUPP), terima oleh user MB, hasil per varian
  (dev no, shade, dosing, MB LDR, TR no, OK/NOT OK), trial ulang. Proto `finance/v1/tqm_sample_lab.proto`.
- Q021 Varian OK → "Buat SAMPLE dari COLOR" (K1) diaktifkan penuh. Shade baru cukup teks (TQ-05), tidak ke master.
- Q022 Kirim ke marketing/customer + despatch (kurir, paket, berat, rate/kg) untuk kedua tipe.
- Q023 Cetak PDF: Sample Development Format, surat ke supplier, Sample Advice (+ properti teknis), label.
  Acuan tata letak: report legacy CDM001–009, TQMRND01/02 (minta contoh cetakan ke TQM).
- Q024 Frontend lab trial (tampilan khusus role `TQM_SAMPLE_LAB`), pengiriman, cetak.

## Fase 3 — Feedback & costing (PRD§F5, §F6; K3, K9)
- Q030 Feedback per item/varian, status komersial, Redevelopment → request anak.
- Q031 Pengingat: sampel DESPATCHED > N hari tanpa feedback (N ditanyakan ke TQM), per marketing PIC.
- Q032 Ajukan Costing (TQ-06): panggil use case create CPR dalam satu transaksi, FK ke `cost_product_request`,
  idempoten, hanya pengisi feedback, hanya Approved.
  Peringatan kalau feedback berubah setelah CPR dibuat (F6.4).
- Q033 Frontend feedback + Ajukan Costing + status CPR.
- Q034 (koordinasi pemilik CPR, repo finance) Riwayat TQM tampil di CPR (F6.2) dan referensi balik (F6.3).

## Fase 4 — Laporan, deploy, cut-over (PRD§F7, §7)
- Q040 Laporan pipeline, funnel, lead time, backlog per divisi + export Excel.
- (Q041 dihapus: TQM ikut deploy finance, tidak ada infra baru. TQ-01.)
- Q042 Seed counter nomor dari Oracle (nomor terakhir per jenis tahun berjalan) + runbook cut-over.
- Q043 UAT bersama TQM, MKT, MB, dan Head of TQM, lalu go-live. Oracle TQM/TQM RND dibuat read-only.
