# TQM — Pengembangan Produk & Sampel

Proyek pengganti dua aplikasi Oracle Forms, **TQM Color Development** dan **TQM RND Sample
Market Advice**, yang digabung menjadi satu modul di service `finance` (tabel `tqm_sample_*`). Sampel dengan
feedback customer Approved bisa langsung diajukan ke Cost Product Request (finance).

## Mulai dari mana
1. Buka `HANDOVER.html` di browser untuk gambaran satu halaman.
2. Baca `CONTEXT.md` (wajib, ±5 KB): lokasi kode, konvensi, dan aturan Git.
3. Ambil task di `TASKS.md` yang dependensinya sudah selesai, lalu baca hanya rujukan di baris **Ref**.

## Berkas
| Berkas | Isi |
|---|---|
| `HANDOVER.html` | ringkasan untuk tim (kondisi lama, desain target, keputusan, rencana) |
| `PRD.md` | kebutuhan bisnis; keputusan user K1–K9 ada di §3a |
| `LEGACY-MAP.md` | peta form, tabel, dan alur Oracle, plus angka production |
| `CONTEXT.md` | panduan kerja developer |
| `DECISIONS.md` | keputusan teknis `TQ-xx` (status PROPOSED/ACCEPTED) |
| `TASKS.md` | task aktif `Q0xx` |
| `BACKLOG.md` | fase berikutnya, belum dipecah jadi task |
| `ACCEPTANCE.md` | kriteria gerbang per fase + bukti |
| `ARCHIVE.md` | task yang sudah selesai |

## Aturan singkat
- Commit dan PR memakai ID task: `[Q012] ...`. Branch `feat/tqm` dari `main`.
- Dokumen ini ada di repo `docs-markdown` (`github.com/mutugading/docs-markdown`, folder `goapps/tqm/`). Clone ke
  root workspace kalau belum ada.
- Task selesai: hapus dari `TASKS.md`, tambah satu baris di `ARCHIVE.md`, isi `ACCEPTANCE.md` kalau
  menutup kriteria gerbang. Commit di `docs-markdown` dengan ID task yang sama seperti commit kodenya.
- Keputusan bisnis hanya di PRD §3a, keputusan teknis hanya di DECISIONS. Kalau ada yang belum
  diputuskan, tulis usulan `TQ-xx` (PROPOSED) dan tanyakan ke pemilik proyek, jangan diputuskan diam-diam.
