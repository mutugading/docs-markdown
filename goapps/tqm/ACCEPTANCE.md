# TQM — ACCEPTANCE (gerbang per fase)

Fase berikutnya **tidak dimulai** sebelum semua kotak di fase ini terisi bukti (ID task + berkas
laporan/perintah, atau "diverifikasi bersama <siapa>"). Tidak ada SHA.

## G0 — Fondasi
| # | Kriteria | Rujukan | Bukti | Tgl |
|---|---|---|---|---|
| 0.1 | TQ-01..TQ-07 ACCEPTED/SUPERSEDED, branch `feat/tqm` dari `main` di setiap repo | Q000 | | |
| 0.2 | Service `tqm` hidup di stack dev, migrasi up/down/up OK, CI hijau | Q001 | | |
| 0.3 | Proto `tqm/v1` lint + breaking hijau, kode gen ada di backend & frontend | Q002 | | |
| 0.4 | Role/menu/permission TQM tampil sesuai role | Q003 | | |

## G1 — Master data & Development Request
| # | Kriteria | Rujukan | Bukti | Tgl |
|---|---|---|---|---|
| 1.1 | Master data terisi dari Oracle (jumlah = Oracle), pemetaan lookup disetujui TQM | Q010, Q011 | | |
| 1.2 | Penomoran unik di bawah beban konkuren, format sesuai K5 | Q012 | | |
| 1.3 | State machine teruji per role, revise menyimpan snapshot, link COLOR→SAMPLE tervalidasi | Q012 | | |
| 1.4 | Approve/reject hanya oleh Head of TQM | Q013 | | |
| 1.5 | Demo alur fase 1 di browser disetujui user/TQM | Q014, Q015 | | |

## G2 — Lab trial, pengiriman, cetak
| # | Kriteria | Rujukan | Bukti | Tgl |
|---|---|---|---|---|
| 2.1 | User MB menyelesaikan lab trial sendiri | Q020, Q024 | | |
| 2.2 | Cetakan PDF disetujui TQM | Q023 | | |

## G3 — Feedback & costing
| # | Kriteria | Rujukan | Bukti | Tgl |
|---|---|---|---|---|
| 3.1 | Ajukan Costing membuat CPR terisi benar, idempoten, hanya pengisi feedback Approved | Q032 | | |

## G4 — Go-live
| # | Kriteria | Rujukan | Bukti | Tgl |
|---|---|---|---|---|
| 4.1 | Counter di-seed = nomor terakhir Oracle + 1, tidak ada bentrok | Q042 | | |
| 4.2 | UAT ditandatangani, Oracle TQM read-only | Q043 | | |
