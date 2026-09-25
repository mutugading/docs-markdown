# Transporter Suites — Context-Driven AI Coding

Berkas di folder ini adalah **konteks kerja** untuk membangun `Modules/Transporter`
(migrasi Oracle Forms/Reports 6i → Laravel). Dibuat 2026-09-17 dari
PRD v2.2 Draft.

Satu kalimat yang mengatur semuanya:

> **PRD adalah kebenaran tentang _apa_ dan _kenapa_. Folder ini adalah kebenaran
> tentang _bagaimana_ dan _di mana_.** Kalau keduanya bertabrakan, PRD menang —
> dan pertentangannya dicatat di `DECISIONS.md`, tidak diselesaikan diam-diam.

---

## Sumber

| Apa | Di mana |
|---|---|
| PRD (1.514 baris, v2.2) | `D:\IT Project\docs-markdown\apps-mutugading\transporter-module\PRD_Transporter_Module.md` |
| Indeks sumber legacy | `…\transporter-module\LEGACY_REFERENCE.md` |
| Kode legacy terekstrak | `…\transporter-module\legacy-source\` — `plsql/`, `forms/`, `reports/`, `schema/` |
| Aturan repo | `CLAUDE.md`, `RULES.md`, `CONTRIBUTING.md` di root |
| Aturan e2e | `e2e/CLAUDE.md` |

Dari WSL, drive D ada di `/mnt/d/IT Project/…`.

---

## Berkas di folder ini

### Dibaca sebelum menulis kode

| Berkas | Isi | Kapan dibaca |
|---|---|---|
| `gap.md` | PRD vs kondisi codebase nyata: apa yang sudah ada dan bisa dipakai ulang, apa yang bertentangan, apa yang belum ada | **Sekali, sebelum task pertama.** Berisi 6 pertentangan di PRD yang harus diketahui sebelum menulis baris pertama |
| `plan.md` | Urutan fase, dependency graph antar task, apa yang boleh paralel | Saat memilih task berikutnya |
| `design.md` | Rancangan teknis: entitas, interface, service, alur job, strategi error | Sebelum task di fase yang bersangkutan |
| `spec.md` | Siap-kode: DDL per tabel, pemetaan kolom lama → baru, aturan validasi, rumus, struktur berkas | **Saat mengerjakan task.** Ini yang dibuka paling sering |
| `TASKS.md` | 82 task v1, atomic, masing-masing dengan acceptance criteria | Setiap kali mulai dan selesai task |
| `PREFLIGHT.md` + `preflight.sh` | Blocker yang harus lulus sebelum coding dimulai | **Setiap awal sesi.** Jalankan `bash .ai/transporter/preflight.sh` |

### Untuk kontrol & pelacakan

| Berkas | Isi | Siapa yang mengisi |
|---|---|---|
| `PROGRESS.md` | Status per task, tanggal, commit, siapa yang review | Diisi agen/developer **setiap kali satu task selesai**, sebelum lanjut |
| `DECISIONS.md` | Keputusan teknis yang tidak ada di PRD, beserta alasannya | Diisi **saat keputusan diambil**, bukan belakangan |
| `ACCEPTANCE.md` | Gerbang per fase: apa yang harus lulus sebelum fase dinyatakan selesai | Dicentang bersama Indra / Finance |

---

## Branch

Dikerjakan **di dalam repo `apps-mutugading` biasa — tanpa git worktree.**

| Hal | Aturan |
|---|---|
| Branch | **Satu branch untuk seluruh proyek: `feat/Transporter`.** Bercabang dari `develop` |
| Commit | Satu task = satu commit: `feat(transporter): [T0xx] judul task` |
| Push | Kapan saja, sesering mungkin. Push tidak memicu apa pun, jadi tidak ada alasan menahannya |
| PR | **Sekali saja, di akhir** — setelah testing selesai dan aplikasi jalan. Praktik tim. Koreksi setelah merge ditangani lewat PR baru |
| Rebase | Ke `develop` **di setiap gerbang `ACCEPTANCE.md`** — 8 rebase kecil, bukan satu rebase 82 commit di minggu ke-23 |

### CI tidak jalan di branch ini — `preflight.sh` yang menggantikannya

`.github/workflows/tests.yml` hanya terpicu oleh `pull_request` ke `main`/`develop` dan
`push` ke `main`. `lint.yml` hanya oleh `pull_request`. **Push ke `feat/Transporter`
tidak menjalankan apa pun.**

Karena PR baru dibuka di akhir, artinya selama 23 minggu tidak ada satu pun verifikasi
otomatis — kecuali yang dijalankan sendiri. Tiga blocker di `preflight.sh` persis
menjalankan perintah yang sama dengan CI:

| Blocker | Perintah | Padanan CI |
|---|---|---|
| 13 | `php artisan test --parallel` | `tests.yml` |
| 14 | `vendor/bin/pint --test` | `lint.yml` |
| Warning 4 | `node e2e/bin/check-pages.mjs` | `lint.yml` langkah terakhir |

Jadi **menjalankan `preflight.sh` setiap awal sesi bukan formalitas** — itu satu-satunya
hal yang berdiri di antara pekerjaan ini dan PR merah di minggu ke-23.

### Satu pengecualian yang diusulkan: T012

T012 memindahkan `JournalVoucherPostingService` + 4 berkas pendukung **keluar dari
`Modules/LcControl` ke `Modules/Core`** — satu-satunya task yang menyentuh kode milik
modul lain yang sedang aktif dikerjakan orang lain.

Usulannya: kerjakan dan PR-kan sendiri lebih dulu. Alasannya ada di `DECISIONS.md` D-05.
**Belum diputuskan** — keputusannya diambil saat T012 tiba (minggu ke-2), bukan sekarang.

## Menjalankan perintah

`php` dan `node` **tidak ada di host WSL** — keduanya di dalam container:

```bash
docker exec -w /var/www/html apps-mutugading-app-1 bash -lc 'php artisan test --parallel'
docker exec -w /var/www/html apps-mutugading-app-1 bash -lc 'vendor/bin/pint --dirty'
docker exec -w /var/www/html apps-mutugading-app-1 bash -lc 'node e2e/bin/check-pages.mjs'
```

`preflight.sh` menemukan container-nya sendiri dan menampilkannya di header.
Detailnya di `gap.md` C-10 — termasuk satu jebakan `tinker` yang sudah memakan waktu sekali.

## Aturan kerja

1. **Jalankan `preflight.sh` dulu.** Kalau ada `FAIL`, berhenti dan laporkan —
   jangan dikerjakan sambil jalan.
2. **Satu task = satu commit.** Format `feat(transporter): [T0xx] judul task`.
   Scope `transporter` belum terdaftar di `.github/COMMIT_CONVENTION.md` —
   itu task T001.
3. **Update `TASKS.md` `[TODO]` → `[DONE]` dan `PROGRESS.md` setelah commit,**
   bukan sebelum.
4. **Keputusan yang tidak ada di PRD masuk `DECISIONS.md` sebelum kodenya ditulis.**
   Kalau keputusannya mengubah angka akuntansi, berhenti dan tanya — jangan putuskan sendiri.
5. **Jangan lanjut ke fase berikutnya sebelum gerbang `ACCEPTANCE.md` fase ini
   dicentang.**
6. **Kalau sebuah task ternyata salah atau tidak mungkin, jangan diakali.**
   Tandai `[BLOCKED]` di `TASKS.md`, tulis alasannya di `PROGRESS.md`, lanjut ke
   task lain yang tidak bergantung padanya.

## Yang tidak boleh dilakukan tanpa persetujuan

| Aksi | Kenapa |
|---|---|
| **Membuat tabel di `MGTDAT`** | Semua 22 tabel modul ini milik schema **`MGTHRIS`**. Tidak ada tabel baru di `MGTDAT`, tidak pada fase mana pun — lihat `spec.md` §0 |
| Menulis apa pun ke schema `MGTDAT` di luar `FT_UNPOSTED_TRANS_HEADER`, `FT_UNPOSTED_TRANS_DETAIL`, dan `FM_TRAN_DOC_NO` | `MGTDAT` milik Orion. `OT_GR_HEAD` saja memikul 18 trigger |
| Memasang / mengubah compatibility view di `MGTDAT` | Menjatuhkan ± 25 objek + 12 report Orion. Hanya di jendela cutover (fase P6/P7) |
| Mengubah kalkulasi tarif, pajak, atau pemetaan jurnal di luar yang tertulis di `spec.md` | Angka GL harus identik dengan sistem lama. Ini kriteria go/no-go |
| Menjalankan script migrasi data ke produksi | Fase M-*, dengan sign-off Finance |
| Mengubah `Modules/LcControl` di luar yang disebut T012 | LcControl sudah jalan di produksi |
