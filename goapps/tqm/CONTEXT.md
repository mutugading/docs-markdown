# TQM — CONTEXT

Modul **TQM: Pengembangan Produk & Sampel**. Pengganti dua aplikasi Oracle Forms (TQM Color
Development + TQM RND Sample Market Advice) yang digabung menjadi satu modul di service `finance`. Sampel
dengan feedback customer Approved bisa langsung diajukan ke **Cost Product Request** (finance).

Baca di awal setiap sesi task. Dokumen lain dibaca sesuai rujukan task saja.

## 1. Dokumen
| Berkas | Isi | Kapan dibaca |
|---|---|---|
| `PRD.md` | kebutuhan bisnis: keputusan user K1–K9 (§3a), alur target (§4), F1–F7, role (§6), migrasi (§7) | section yang dirujuk task (`PRD§F2` = baris `### F2.`) |
| `LEGACY-MAP.md` | peta form/tabel/alur Oracle + angka production (L1–L6) | saat butuh arti field legacy |
| `DECISIONS.md` | keputusan teknis `TQ-xx` | hanya ID yang dirujuk task |
| `TASKS.md` | task aktif `Q0xx` (TODO/WIP/BLOCKED) | awal sesi: blok task sendiri |
| `BACKLOG.md` | fase berikutnya, kasar | saat perencanaan |
| `ACCEPTANCE.md` | gerbang per fase + bukti | saat task menutup kriteria |
| `ARCHIVE.md` | task DONE, satu baris | hampir tidak pernah |

Keputusan **bisnis** (K1–K9) hanya ada di PRD §3a. Keputusan **teknis** hanya di DECISIONS. Jangan disalin.

## 2. Ringkasan domain (detail di PRD)
- Satu entitas **Development Request**, `type = COLOR | SAMPLE`. Daftar, status, approval, despatch,
  feedback, dan tautan costing berlaku untuk keduanya. Lab trial (Masterbatch) hanya untuk COLOR.
- Status: DRAFT → SUBMITTED → APPROVED/REJECTED → (COLOR: LAB TRIAL → RESULT) → READY TO SEND →
  DESPATCHED → FEEDBACK → CLOSED. Revise menyimpan snapshot. Redevelopment = request anak (`parent_id`).
  COLOR varian OK bisa melahirkan SAMPLE (`source_request_id`, K1).
- Nomor dibuat sistem saat submit, format legacy `MGT-PD-NNN/YYYY`, `SMA-NNN/YYYY`, `TQM/NNN/MM/YYYY` (K5).
- Approver: Head of TQM, satu level (K6). Masterbatch login sendiri, role `TQM_SAMPLE_LAB` (K4).
- Ajukan Costing: hanya saat feedback Approved, hanya oleh marketing yang mengisi feedback (K3, K9).
- **Mulai kosong** (K8): dokumen Oracle tidak dimigrasi, hanya master data yang dibawa.

## 3. Lokasi kode
Modul di dalam service **finance** (K2, TQ-01). Semua nama berprefix `tqm_sample` / `tqmsample`:
```
goapps-shared-proto/finance/v1/tqm_sample_*.proto            ← kontrak API (package finance.v1)
goapps-backend/services/finance/
  internal/domain/tqmsample*/                                ← entitas, state machine, penomoran
  internal/application/tqmsample*/  internal/infrastructure/postgres/tqm_sample_*.go
  internal/delivery/grpc/tqm_sample_*_handler.go             ← daftarkan di server.go
  cmd/import-tqm-sample-master/                              ← CLI impor Oracle (TQ-07)
  migrations/postgres/NNNNNN_*tqm_sample*.{up,down}.sql      ← lanjut nomor migrasi finance
goapps-backend/services/iam/migrations/postgres/             ← role TQM_SAMPLE_*, menu, permission finance.tqmsample.*
goapps-frontend/src/app/(dashboard)/tqm/sample/...           ← halaman (menu top-level TQM)
```
Tidak ada service, DB, port, atau perubahan infra baru. Rujukan pola: master shade + sync Oracle
`finance/v1/shade.proto` dan `infrastructure/oracle/shade_repository.go`, sync customer ppc
`ppc/internal/infrastructure/oracle/customer_source.go` (untuk Q004), CPR `finance/v1/cost_product_request.proto`,
CLI `services/finance/cmd/sync-mb-active`, cek permission `HasPermission(ctx, "finance....")` di `delivery/grpc`.

## 4. Konvensi wajib
- Contract-first: ubah proto dulu, generate dengan skrip repo proto (jangan sunting `gen/` manual).
- Uang (harga, biaya, invoice, rate/kg) dan berat: `NUMERIC` di DB, `decimal.Decimal` di Go, string di proto. **Dilarang float.**
- Nomor dokumen hanya dibuat sistem (TQ-03), dan setiap nomor dilindungi unique constraint.
- Setiap perubahan status lewat domain service + riwayat status + audit log. Jangan UPDATE status langsung dari handler.
- Role `TQM_SAMPLE_*` (huruf besar). Permission `finance.tqmsample.<entity>.<action>`: 4 segmen huruf kecil
  tanpa underscore; action baru harus ditambahkan dulu ke `chk_permission_action` (lihat migrasi IAM 000094).
  Menu dan permission di-seed lewat migrasi IAM.
- Tabel `tqm_sample_*` saja. Tabel CPR dan tabel anaknya tidak diubah. CPR dibuat hanya lewat use case CPR (TQ-06).
- Customer dari master customer finance (Q004), shade sebagai teks + `shade_id` opsional (TQ-05). Tidak ada
  panggilan ke ppc.
- Oracle hanya dibaca (impor master, seed counter), dengan akun read-only. Tidak boleh ada DML ke Oracle.
- Migrasi: golang-migrate, up + down, idempotent. Uji up/down/up di DB lokal.
- Test: unit test domain (state machine, penomoran) wajib. Integration test repo dijalankan dengan `-run`.
- Konvensi repo selengkapnya: grep heading di `CLAUDE.md`/`RULES.md` masing-masing repo (berkas besar, jangan dibaca utuh).

## 5. Git & sesi paralel
- Awalan task/commit `Q0xx` / `[Q0xx]`. Keputusan teknis `TQ-xx`.
- Dokumen ada di repo **`docs-markdown`** (`github.com/mutugading/docs-markdown`, folder `goapps/tqm/`), sejajar
  dengan repo kode di workspace (`goapps/docs-markdown/`). Kalau belum ada:
  `git clone https://github.com/mutugading/docs-markdown.git` di root workspace.
- Branch kode **`feat/tqm`** di setiap repo yang disentuh (`goapps-backend`, `goapps-frontend`,
  `goapps-shared-proto`), dibuat dari `main`. Satu branch panjang, bukan satu branch
  per task. Developer boleh membuat branch pendek dari `feat/tqm` per task lalu PR ke `feat/tqm`.
- Clone biasa sudah cukup. Kalau satu mesin juga mengerjakan proyek lain di repo yang sama, pakai
  `git worktree` (mis. `git -C goapps-backend worktree add ../.wt-tqm/backend feat/tqm`) supaya tidak
  perlu berganti branch di folder yang sama.
- Commit per path (jangan `git add -A`). Dilarang `stash`/`reset`/`checkout`/`rebase`/`push --force`
  tanpa izin. Dokumen: `git -C docs-markdown commit goapps/tqm/ -m "[Q0xx] ..."`.
- Satu task = satu commit per repo kode + satu commit di `docs-markdown` (TASKS/ARCHIVE/ACCEPTANCE) dengan ID task
  yang sama.
- Jangan mengubah file proyek payroll/HR/packprod/finance-CPR. Perubahan di sisi CPR (PRD F6.2/F6.3)
  dibuat sebagai task terpisah dan dikoordinasikan dengan pemilik CPR.
- Nomor migrasi finance dipakai bersama tim lain: ambil nomor berikutnya tepat sebelum merge, dan cek bentrok
  dengan `main` saat rebase/merge.

## 6. Review
Task bertanda `Review: wajib` direview oleh developer lain lewat PR sebelum ditutup. Fokus review: state machine, penomoran konkuren, hak akses per role, decimal, dan migrasi down.

## 7. Kalau memakai Claude Code (opsional)
Kolom **Model** di TASKS adalah saran model per task. Di workspace pemilik proyek ada perintah
`/task Q0xx` (mengerjakan satu task per sesi) dan `/review-task Q0xx`. Tanpa Claude Code, ikuti langkah
yang sama secara manual: baca CONTEXT, blok task, dan rujukan Ref-nya, kerjakan, lalu tutup task
(TASKS → ARCHIVE, isi ACCEPTANCE).
