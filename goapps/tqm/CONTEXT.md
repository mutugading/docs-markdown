# TQM — CONTEXT

Modul **TQM: Pengembangan Produk & Sampel**. Pengganti dua aplikasi Oracle Forms (TQM Color
Development + TQM RND Sample Market Advice) yang digabung menjadi satu service GoApps baru. Sampel
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
- Approver: Head of TQM, satu level (K6). Masterbatch login sendiri, role `tqm.lab` (K4).
- Ajukan Costing: hanya saat feedback Approved, hanya oleh marketing yang mengisi feedback (K3, K9).
- **Mulai kosong** (K8): dokumen Oracle tidak dimigrasi, hanya master data yang dibawa.

## 3. Lokasi kode
Service baru, **bukan** bagian dari finance (K2). Tiru struktur service `ppc` (service terbaru dan paling rapi):
```
goapps-shared-proto/tqm/v1/*.proto              ← kontrak API (tambahkan `tqm` ke input buf.gen*.yaml)
goapps-backend/services/tqm/
  cmd/server/  config.yaml  Makefile  Dockerfile
  internal/{domain,application,infrastructure,delivery}/
  migrations/postgres/NNNNNN_*.{up,down}.sql   ← mulai 000001
goapps-backend/services/iam/migrations/postgres/ ← role, menu, permission tqm.*
goapps-frontend/src/app/(dashboard)/tqm/...      ← halaman
goapps-infra/                                    ← deploy service tqm (fase 4)
```
Rujukan pola: client IAM workflow `services/finance/internal/infrastructure/iamclient/workflow_client.go`,
master customer `ppc/v1/customer.proto`, master shade `finance/v1/shade.proto`, CPR
`finance/v1/cost_product_request.proto`.

## 4. Konvensi wajib
- Contract-first: ubah proto dulu, generate dengan skrip repo proto (jangan sunting `gen/` manual).
- Uang (harga, biaya, invoice, rate/kg) dan berat: `NUMERIC` di DB, `decimal.Decimal` di Go, string di proto. **Dilarang float.**
- Nomor dokumen hanya dibuat sistem (TQ-03), dan setiap nomor dilindungi unique constraint.
- Setiap perubahan status lewat domain service + riwayat status + audit log. Jangan UPDATE status langsung dari handler.
- Permission `tqm.<module>.<entity>.<action>`. Menu dan permission di-seed lewat migrasi IAM.
- Integrasi ke service lain hanya lewat gRPC (finance CPR, ppc customer, finance shade, IAM workflow),
  tanpa akses langsung ke DB service lain.
- Oracle hanya dibaca (impor master, seed counter), dengan akun read-only. Tidak boleh ada DML ke Oracle.
- Migrasi: golang-migrate, up + down, idempotent. Uji up/down/up di DB lokal.
- Test: unit test domain (state machine, penomoran) wajib. Integration test repo dijalankan dengan `-run`.
- Konvensi repo selengkapnya: grep heading di `CLAUDE.md`/`RULES.md` masing-masing repo (berkas besar, jangan dibaca utuh).

## 5. Git & sesi paralel
- Awalan task/commit `Q0xx` / `[Q0xx]`. Dokumen di `.ai/tqm/`. Keputusan teknis `TQ-xx`.
- Branch kode **`feat/tqm`** di setiap repo yang disentuh (`goapps-backend`, `goapps-frontend`,
  `goapps-shared-proto`, `goapps-infra`), dibuat dari `main`. Satu branch panjang, bukan satu branch
  per task. Developer boleh membuat branch pendek dari `feat/tqm` per task lalu PR ke `feat/tqm`.
- Clone biasa sudah cukup. Kalau satu mesin juga mengerjakan proyek lain di repo yang sama, pakai
  `git worktree` (mis. `git -C goapps-backend worktree add ../.wt-tqm/backend feat/tqm`) supaya tidak
  perlu berganti branch di folder yang sama.
- Commit per path (jangan `git add -A`). Dilarang `stash`/`reset`/`checkout`/`rebase`/`push --force`
  tanpa izin. Dokumen di root: `git commit .ai/tqm/ -m "[Q0xx] ..."`.
- Satu task = satu commit per repo, termasuk update TASKS/ARCHIVE di root.
- Jangan mengubah file proyek payroll/HR/packprod/finance-CPR. Perubahan di sisi CPR (PRD F6.2/F6.3)
  dibuat sebagai task terpisah dan dikoordinasikan dengan pemilik CPR.
- Port & DB lokal: lihat TQ-01. Jangan memakai port stack proyek lain.

## 6. Review
Task bertanda `Review: wajib` direview oleh developer lain lewat PR sebelum ditutup. Fokus review: state machine, penomoran konkuren, hak akses per role, decimal, dan migrasi down.

## 7. Kalau memakai Claude Code (opsional)
Kolom **Model** di TASKS adalah saran model per task. Di workspace pemilik proyek ada perintah
`/task Q0xx` (mengerjakan satu task per sesi) dan `/review-task Q0xx`. Tanpa Claude Code, ikuti langkah
yang sama secara manual: baca CONTEXT, blok task, dan rujukan Ref-nya, kerjakan, lalu tutup task
(TASKS → ARCHIVE, isi ACCEPTANCE).
