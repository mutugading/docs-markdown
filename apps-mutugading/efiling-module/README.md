# E-Filing Module — Dokumen Proyek

Migrasi aplikasi PHP native e-Filing (`\\192.168.0.3\d$\XAMPP\htdocs\webapps\Efilling_File`) ke
`Modules/Efiling` di apps-mutugading, dengan penyimpanan berkas di MinIO.

| Berkas | Isi |
|---|---|
| `PRD_Efiling_Module.md` | PRD v0.3 Draft — schema MGTHRIS (tabel baru `efl_*`), MinIO, intake otomatis, modul terkait |
| `ROLE_MAPPING_PROPOSAL.md` | Usulan role pengganti 72 kombinasi akses legacy (bahan diskusi pemilik dokumen) |
| `EFILING_USER_FOLDER_ACCESS.xlsx` | Grant legacy user × folder (113 user, 91 folder, aktivitas 12 bln) — bahan OI-07 pemilik & OI-08 kerahasiaan |
| `LEGACY_REFERENCE.md` | Hasil analisa sistem lama (layar, tabel Oracle, layout Doc_Folder, temuan keamanan); dirujuk PRD sebagai `§L-x` |
| `legacy-related/` | Source aplikasi legacy lain yang memakai Doc_Folder (GRN Merge, Costing, Payment, Sending Document, Check DN) — kredensial di-redact |
| `legacy-source/` | Salinan source PHP legacy (tanpa fpdf/bcp/_BCP/_TEST). **Kredensial sudah di-redact** — jangan salin ulang dari server tanpa redaksi |

Status: analisa sistem & PRD. Belum ada branch/kode. Langkah berikut: jawab OI blocking (PRD §11),
lalu susun `plan.md` / `spec.md` / `TASKS.md` seperti `transporter-module/`.
