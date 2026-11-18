# TQM — TASKS

Hanya berisi TODO/WIP/BLOCKED. Task DONE dipindah ke ARCHIVE.md sebagai satu baris.
Kolom **Model**: `opus` = Opus 5.5 · `sonnet` = Sonnet 5.5 (saran kalau dikerjakan dengan Claude Code).
**Ref** = bagian PRD/LEGACY/DECISIONS yang wajib dibaca (hanya itu). `PRD§F2` = `### F2.` di PRD.md.
**Dep** = task yang harus DONE dulu. **Repo** = repo yang disentuh (branch `feat/tqm`).
Status: `TODO` · `WIP` · `BLOCKED(alasan)`. **Review**: `wajib` / `opsional` (CONTEXT §6).
Fase 2–4 ada di BACKLOG.md dan dipecah jadi task saat gerbang fase sebelumnya lolos.

## Fase 0 — Fondasi

### Q000 Kickoff: konfirmasi TQ-01..TQ-07, siapkan worktree
Status: TODO · Model: opus · Repo: — · Dep: — · Review: opsional
Ref: DECISIONS (semua), PRD§3a, CONTEXT§5
- Bahas TQ-01..TQ-07 dengan user/lead. Ubah status ke ACCEPTED, atau tulis keputusan pengganti.
- Baca alur workflow CPR (finance iamclient) untuk memutuskan TQ-04.
- Pastikan akun Oracle read-only untuk TQ-07 dan cut-over.
- Buat branch `feat/tqm` dari `main` di repo backend, frontend, dan proto, lalu push.
DoD: semua TQ berstatus ACCEPTED/SUPERSEDED, branch ada di GitHub, ACCEPTANCE 0.1 terisi.

### Q001 Scaffold service `tqm`
Status: TODO · Model: sonnet · Repo: backend · Dep: Q000 · Review: wajib
Ref: TQ-01, CONTEXT§3–4; contoh `services/ppc` (cmd, config, Makefile, Dockerfile, migrations, health)
- Service kosong yang bisa jalan: config, koneksi Postgres `tqm_db`, gRPC + gateway HTTP, health check,
  auth interceptor IAM, logging, dan migrasi 000001 (tabel `tqm_doc_counter`, TQ-03).
- Tambahkan Postgres TQM ke `docker-compose.yml` dev (port TQ-01) dan pipeline CI backend.
DoD: `make run` hidup, health OK, migrasi up/down/up OK, lint + test hijau.

### Q002 Proto `tqm/v1` dasar + generate
Status: TODO · Model: sonnet · Repo: proto (+ hasil gen di backend/frontend) · Dep: Q000 · Review: wajib
Ref: TQ-02, PRD§4, §F1–F2; `goapps-shared-proto/CLAUDE.md` §6–§12, §17
- `tqm/v1/master.proto` (lookup generik, color master, base color, supplier), `tqm/v1/request.proto`
  (DevelopmentRequest + enum type/status, CRUD, list/filter, aksi Submit/Approve/Reject/Revise/Cancel/Duplicate,
  history). Hanya kontrak; fase 2–3 menambah file sendiri.
- Tambahkan `tqm` ke input `buf.gen.yaml` dan `buf.gen.ts.yaml`. Jalankan `buf lint`, `buf breaking`, dan generate.
DoD: lint/breaking hijau, kode gen ada di backend dan frontend.

### Q003 IAM: role, menu, permission TQM
Status: TODO · Model: sonnet · Repo: backend (services/iam) · Dep: Q000 · Review: opsional
Ref: PRD§6, CONTEXT§4; contoh `iam/migrations/postgres/000079_seed_ppc_roles_menus_permissions.up.sql`
- Role `tqm.requester`, `tqm.officer`, `tqm.lab`, `tqm.approver`, `tqm.despatch`, `tqm.admin`. Menu grup TQM
  (Master, Permintaan, Lab Trial, Pengiriman, Feedback, Laporan). Permission `tqm.*.*.<action>` untuk setiap entitas Q002.
DoD: migrasi IAM up/down/up OK, menu tampil untuk user ber-role (cek di stack dev).

## Fase 1 — Master data & Development Request

### Q010 Master data: schema + backend CRUD + import/export Excel
Status: TODO · Model: sonnet · Repo: backend · Dep: Q001, Q002 · Review: opsional
Ref: PRD§F1, LEGACY L2 (TQMCDM05), TQ-07
- Lookup generik `tqm_lookup(category, code, name, sort, is_active)`: sample form, polymer, luster, cross section,
  fastness, end use, matching, light source, subject kirim, FB status, commercial status, kurir, alasan kirim.
- Color master: ID warna otomatis = base color + urut 3 digit (base 1 → 1001, 1002 …), sesuai aturan legacy.
- Supplier/lab (MGT Masterbatch + eksternal). Pola CRUD + Export/Import/Template standar goapps.
DoD: unit test aturan ID warna, integration test repo, endpoint dicoba lewat gateway.

### Q011 CLI `import-master` dari Oracle + CSV usulan lookup
Status: TODO · Model: sonnet · Repo: backend · Dep: Q010 · Review: opsional
Ref: TQ-07, LEGACY L1, L6
- Impor color master (±4.346), base color, supplier, dan lookup kecil. Idempoten (upsert by code).
- Hasilkan CSV teks bebas legacy (`CDM_MGT_TYPE`, `CDMM_SUBJECT`, `CDMS_SUPPLIER`, dll.) + jumlah pemakaiannya
  + usulan code lookup, lalu serahkan ke user/TQM untuk direview.
DoD: run ke DB lokal, jumlah baris = Oracle, CSV diserahkan. Pemetaan final dimuat setelah disetujui TQM.

### Q012 Domain Development Request: state machine + penomoran
Status: TODO · Model: opus · Repo: backend · Dep: Q001, Q002 · Review: wajib
Ref: PRD§4, §F2, K1/K5/K6/K7, TQ-02, TQ-03
- Schema TQ-02 untuk bagian fase 1 (request, color, sample, sample_item + properti teknis, history).
- State machine sebagai fungsi domain murni beserta tabel transisi + role yang boleh. Submit memberi nomor
  (TQ-03) dalam transaksi yang sama. Revise → snapshot + revision+1. Duplicate. Cancel.
- `parent_id` (redevelopment) dan `source_request_id` (K1) beserta validasinya: source harus COLOR, dan
  SAMPLE tidak bisa menjadi source.
DoD: unit test semua transisi (boleh/tidak per role), test konkuren penomoran (N goroutine → N nomor unik
berurutan), migrasi up/down/up.

### Q013 Approval Head of TQM
Status: TODO · Model: opus · Repo: backend · Dep: Q012, Q003 · Review: wajib
Ref: TQ-04, K6, PRD§F2.4
- Submit → approval sesuai TQ-04. Approve/Reject (dengan alasan) hanya untuk `tqm.approver`. Notifikasi
  mengikuti pola CPR kalau ada.
DoD: test approve/reject/revise ulang, user non-approver ditolak (403).

### Q014 Frontend master data
Status: TODO · Model: sonnet · Repo: frontend · Dep: Q010, Q003 · Review: opsional
Ref: PRD§F1; pola halaman master ppc/finance di frontend
DoD: CRUD + import/export jalan di stack dev, lint/typecheck hijau.

### Q015 Frontend Development Request (daftar, form COLOR/SAMPLE, detail + timeline)
Status: TODO · Model: sonnet · Repo: frontend · Dep: Q012, Q013, Q014 · Review: wajib
Ref: PRD§4, §F2, LEGACY L2 (field TQMCDM04), L3 (field TQMRND02)
- Daftar dengan filter (tipe, status, customer, tanggal). Form COLOR dengan spek customer vs MGT berdampingan.
  Form SAMPLE: header + item + properti teknis. Detail menampilkan timeline status, aksi sesuai role,
  tombol "Buat SAMPLE dari COLOR" (K1; aktif penuh setelah lab trial di fase 2), dan pemilih customer (TQ-05).
DoD: alur DRAFT → SUBMITTED → APPROVED/REJECTED → REVISE dicoba di browser dengan 3 role, lint/typecheck hijau.
