# PREFLIGHT.md — Transporter

Dijalankan **setiap awal sesi**, sebelum task pertama:

```bash
bash .ai/transporter/preflight.sh
```

Ada satu `FAIL` → **berhenti dan laporkan ke Indra.** Jangan dikerjakan sambil jalan.
`WARN` boleh dilewati, tapi sebutkan di laporan.

> **Kenapa ini lebih penting dari biasanya.** CI tidak pernah jalan di branch
> `feat/Transporter` — `tests.yml` hanya terpicu `pull_request` ke `main`/`develop` dan
> `push` ke `main`; `lint.yml` hanya `pull_request`. Karena PR baru dibuka di akhir
> pengembangan, blocker 13 (`test --parallel`), blocker 14 (`pint --test`), dan warning 4
> (`check-pages.mjs`) adalah **satu-satunya** yang menjalankan perintah yang sama dengan
> CI selama 23 minggu.

---

## Cara skrip ini menjalankan PHP

**PHP tidak terpasang di host WSL.** Ia jalan di dalam container Docker. Skrip mencari
runner-nya sendiri dan menampilkannya di header:

```
 php:  docker exec apps-mutugading-app-1
```

Urutan pencarian: `php` di host → `laravel-app` → container lain yang namanya mengandung
`app` (bukan `vite`/`queue`) dan bisa menjalankan `php -v`. Override dengan
`TRANSPORTER_APP_CONTAINER=<nama>`.

`node` juga hanya ada di dalam container, jadi cek e2e ikut lewat sana.

> **Jebakan yang sudah memakan waktu sekali.** `php artisan tinker --execute="exit(0)"`
> **selalu** mengembalikan kode keluar ≠ 0 — psysh melempar `BreakException` apa pun
> statusnya. Cek lewat tinker harus memakai pola `echo "TANDA:..."` lalu `grep -q`,
> bukan kode keluar. Jangan dikembalikan ke `exit()`.

---

## 🔴 BLOCKER — semua harus PASS

| # | Cek | Kenapa ini blocker |
|---|---|---|
| 1 | Berada di repo yang benar | Salah repo = kerja terbuang |
| 2 | Runner PHP tersedia | Tanpa ini semua cek berikutnya tidak berarti — skrip berhenti di sini |
| 3 | PHP ≥ 8.2 | Modul memakai enum, readonly, constructor promotion |
| 4 | `vendor/` dan `node_modules/` ada | — |
| 5 | `.env` ada | Tanpa ini `artisan` apa pun gagal |
| 6 | Laravel bisa boot | — |
| 7 | Koneksi `oracle_mgtdat` terdaftar | Seluruh integrasi ERP bergantung padanya |
| 8 | Koneksi `oracle_mgthris` terdaftar | Tabel modul tinggal di sini |
| 9 | `Modules/LcControl` dan `Modules/Core` ada | T012 memindahkan kode dari yang satu ke yang lain |
| 10 | `JournalVoucherPostingService` ada di salah satu dari keduanya | Sebelum T012 di LcControl, sesudahnya di Core. Hilang dari keduanya = ada yang salah |
| 11 | `app/Helpers/SysIdHelper.php` ada | Semua PK memakainya |
| 12 | `Searchable` + `LogsActivityWithDescription` ada | — |
| 13 | Test suite hijau **sebelum** mulai | Kalau sudah merah sebelum disentuh, jangan menambah variabel baru |
| 14 | Pint bersih | CI menolak yang tidak |
| 15 | 10 berkas konteks lengkap di `.ai/transporter/` | Kerja tanpa spec = menebak |
| 16 | PRD terjangkau di `/mnt/d/...` | Banyak task merujuk PRD langsung |
| 17 | `legacy-source/` terjangkau | T035 dan T059–T063 membaca `PKG_TRANSPORTER.pkb` dan report lama |

## 🟡 WARNING — tidak memblokir, tapi sebutkan

| # | Cek | Kenapa perlu tahu |
|---|---|---|
| 1 | Working tree bersih | Commit per task jadi kacau kalau ada perubahan lain menumpang |
| 2 | Tidak di branch `main` / `develop` | Keduanya protected; kerja di `feat/transporter-*` |
| 3 | Queue worker jalan | Generate, posting, dan export semuanya queued. Tanpa worker hasilnya "tidak terjadi apa-apa" — dan itu menyesatkan, bukan sekadar lambat |
| 4 | `e2e/bin/check-pages.mjs` hijau | Descriptor yang basi bikin PR merah; cek ini jalan di CI |
| 5 | Migration terakhir masih `2026_09_*` | Kalau sudah lebih baru, sesuaikan penomoran migration Transporter |
| 6 | Oracle benar-benar bisa dihubungi | Hanya perlu untuk task yang menyentuh `MGTDAT` (T012, T034, T037, T044, T053, P6). Task lain jalan dengan SQLite |

---

## Yang preflight **tidak** bisa cek — tanggung jawab manusia

| Hal | Kapan dibutuhkan | Siapa |
|---|---|---|
| Prasyarat `TPJV` di Orion (`IM_TXN_AUTH`, `FM_TRAN_DOC_NO` tahun berjalan) | Sebelum T044 | Finance / DBA. Sudah diverifikasi ada untuk 2026 (PRD §3.7.4a) — **tapi wajib dibuat ulang tiap pergantian tahun** |
| `HMEMD_USER_ORION` untuk pemosting Finance | Sebelum T044 diuji sungguhan | Finance (T072) |
| Mount `Doc_Folder` read-only di server aplikasi | Sebelum T055 | Tim development + infra (Q-25) |
| Grant eksplisit `MGTDAT` dari DBA | Sebelum T044 | DBA (§3.8) — sekarang aplikasi bersandar pada role `DBA` yang terlalu longgar |
| Sign-off pemetaan master 739 → 50 vendor | Sebelum T067 lanjut | Head of Despatch + Head of Finance (Q-18) |
| Konfirmasi nama akun GL di Appendix B | Sebelum go-live | Finance |
