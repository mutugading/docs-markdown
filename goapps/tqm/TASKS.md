# TQM — TASKS

Hanya berisi TODO/WIP/BLOCKED. Task DONE dipindah ke ARCHIVE.md sebagai satu baris.
Kolom **Model**: `opus` = Opus 5.5 · `sonnet` = Sonnet 5.5 (saran kalau dikerjakan dengan Claude Code).
**Ref** = bagian PRD/LEGACY/DECISIONS yang wajib dibaca (hanya itu). `PRD§F2` = `### F2.` di PRD.md.
**Dep** = task yang harus DONE dulu. **Repo** = repo yang disentuh (branch `feat/tqm`).
Status: `TODO` · `WIP` · `BLOCKED(alasan)`. **Review**: `wajib` / `opsional` (CONTEXT §6).
Fase 2–4 ada di BACKLOG.md dan dipecah jadi task saat gerbang fase sebelumnya lolos.

## Fase 0 — Fondasi

### Q000 Kickoff: branch
Status: TODO · Model: sonnet · Repo: — · Dep: — · Review: opsional
Ref: DECISIONS (semua), CONTEXT§5
- TQ-01..TQ-07 sudah ACCEPTED dan akun Oracle sudah ditetapkan (MGTDAT, TQ-07) pada 2026-11-19.
- Buat branch `feat/tqm` dari `main` di repo backend, frontend, dan proto, lalu push.
DoD: branch ada di GitHub, ACCEPTANCE 0.1 terisi.

### Q001 Modul `tqmsample` di finance + tabel counter
Status: TODO · Model: sonnet · Repo: backend · Dep: Q000, Q002 · Review: wajib
Ref: TQ-01, TQ-03, CONTEXT§3–4; contoh modul shade di finance (domain, postgres repo, grpc handler, server.go)
- Kerangka paket `internal/{domain,application,infrastructure/postgres,delivery/grpc}/tqmsample*`, handler
  terdaftar di `server.go` + health status, cek permission `finance.tqmsample.*`.
- Migrasi finance pertama TQM: `tqm_sample_doc_counter` (TQ-03).
DoD: finance jalan dengan service TQM terdaftar (health OK), migrasi up/down/up OK, lint + test finance hijau.

### Q002 Proto `finance/v1/tqm_sample_*` dasar + generate
Status: TODO · Model: sonnet · Repo: proto (+ hasil gen di backend/frontend) · Dep: Q000 · Review: wajib
Ref: TQ-01, TQ-02, PRD§4, §F1–F2; `goapps-shared-proto/CLAUDE.md` §6–§12, §17
- `finance/v1/tqm_sample_master.proto` (lookup generik, color master, base color, supplier),
  `finance/v1/tqm_sample_request.proto` (DevelopmentRequest + enum type/status, CRUD, list/filter, aksi
  Submit/Approve/Reject/Revise/Cancel/Duplicate, history). Hanya kontrak; fase 2–3 menambah file sendiri.
- `finance` sudah ada di input `buf.gen*.yaml`. Jalankan `make lint`, `make breaking`, `make gen-all`.
DoD: lint/breaking hijau, kode gen ada di backend dan frontend.

### Q003 IAM: role, menu, permission TQM Sample
Status: TODO · Model: sonnet · Repo: backend (services/iam) · Dep: Q000 · Review: opsional
Ref: PRD§6, TQ-01, CONTEXT§4; contoh `iam/migrations/postgres/000079_seed_ppc_roles_menus_permissions.up.sql`,
widen action `000094_seed_erp_integration_permissions.up.sql`
- Role `TQM_SAMPLE_REQUESTER`, `_OFFICER`, `_LAB`, `_APPROVER`, `_DESPATCH`, `_ADMIN`. Menu top-level TQM →
  grup Sample (Master, Permintaan, Lab Trial, Pengiriman, Feedback, Laporan). Permission
  `finance.tqmsample.<entity>.<action>` untuk setiap entitas Q002. Action yang belum ada di `chk_permission_action`
  ditambahkan di migrasi yang sama.
DoD: migrasi IAM up/down/up OK, menu tampil untuk user ber-role (cek di stack dev).

### Q004 Master customer di finance + sync Oracle `OM_CUSTOMER`
Status: TODO · Model: sonnet · Repo: proto + backend (finance, iam) · Dep: Q000 · Review: wajib
Ref: TQ-05; contoh `ppc/v1/customer.proto`, `ppc/internal/{domain,application,infrastructure/oracle}/customer*`,
`ppc/migrations/postgres/000036_create_customer.up.sql`, sync shade finance (`infrastructure/oracle/shade_repository.go`)
- Tabel customer di finance (bukan berprefix tqm: dipakai bersama, kelak juga oleh CPR), sync dari Oracle
  read-only, baris MANUAL tidak ditimpa sync, deactivate tanpa hard delete. Proto `finance/v1/customer.proto`
  (List/Get/Create/Update/Deactivate/Sync). Permission `finance.master.customer.*` + menu di bawah Master Finance.
- Tidak menyentuh ppc dan tidak memanggil ppc.
DoD: sync ke DB lokal, jumlah baris = Oracle (aktif), test sync tidak menimpa MANUAL, migrasi up/down/up.

## Fase 1 — Master data & Development Request

### Q010 Master data: schema + backend CRUD + import/export Excel
Status: TODO · Model: sonnet · Repo: backend · Dep: Q001, Q002 · Review: opsional
Ref: PRD§F1, LEGACY L2 (TQMCDM05), TQ-02, TQ-07
- Lookup generik `tqm_sample_lookup(category, code, name, sort, is_active)`: sample form, polymer, luster, cross
  section, fastness, end use, matching, light source, subject kirim, FB status, commercial status, kurir, alasan kirim.
- Color master `tqm_sample_color`: ID warna otomatis = base color + urut 3 digit (base 1 → 1001, 1002 …), sesuai
  aturan legacy.
- Supplier/lab `tqm_sample_supplier` (MGT Masterbatch + eksternal). Pola CRUD + Export/Import/Template standar goapps.
DoD: unit test aturan ID warna, integration test repo, endpoint dicoba lewat gateway.

### Q011 CLI `import-tqm-sample-master` dari Oracle + CSV usulan lookup
Status: TODO · Model: sonnet · Repo: backend · Dep: Q010 · Review: opsional
Ref: TQ-07, LEGACY L1, L6; contoh `services/finance/cmd/sync-mb-active`
- Impor color master (±4.346), base color, supplier, dan lookup kecil. Idempoten (upsert by code).
- Hasilkan CSV teks bebas legacy (`CDM_MGT_TYPE`, `CDMM_SUBJECT`, `CDMS_SUPPLIER`, dll.) + jumlah pemakaiannya
  + usulan code lookup, lalu serahkan ke user/TQM untuk direview.
DoD: run ke DB lokal, jumlah baris = Oracle, CSV diserahkan. Pemetaan final dimuat setelah disetujui TQM.

### Q012 Domain Development Request: state machine + penomoran
Status: TODO · Model: opus · Repo: backend · Dep: Q001, Q002, Q004 · Review: wajib
Ref: PRD§4, §F2, K1/K5/K6/K7, TQ-02, TQ-03, TQ-05
- Schema TQ-02 untuk bagian fase 1 (`tqm_sample_request`, `_request_color`, `_request_advice`, `_advice_item` +
  properti teknis, `_request_history`, `_attachment`). Customer dari master customer finance (Q004) atau prospek
  teks; shade teks + `shade_id` opsional.
- State machine sebagai fungsi domain murni beserta tabel transisi + role yang boleh. Submit memberi nomor
  (TQ-03) dalam transaksi yang sama. Revise → snapshot + revision+1. Duplicate. Cancel.
- `parent_id` (redevelopment) dan `source_request_id` (K1) beserta validasinya: source harus COLOR, dan
  SAMPLE tidak bisa menjadi source.
DoD: unit test semua transisi (boleh/tidak per role), test konkuren penomoran (N goroutine → N nomor unik
berurutan), migrasi up/down/up.

### Q013 Approval Head of TQM
Status: TODO · Model: opus · Repo: backend · Dep: Q012, Q003 · Review: wajib
Ref: TQ-04, K6, PRD§F2.4
- Approve/Reject (dengan alasan) hanya dengan permission `finance.tqmsample.request.approve` (role
  `TQM_SAMPLE_APPROVER`). Tercatat di history + audit log. Notifikasi ke requester lewat IAM notification client
  finance (`iamclient/notification_client.go`).
DoD: test approve/reject/revise ulang, user non-approver ditolak (PermissionDenied).

### Q014 Frontend master data (TQM + customer finance)
Status: TODO · Model: sonnet · Repo: frontend · Dep: Q010, Q003, Q004 · Review: opsional
Ref: PRD§F1; pola halaman master shade di frontend
DoD: CRUD + import/export jalan di stack dev, lint/typecheck hijau.

### Q015 Frontend Development Request (daftar, form COLOR/SAMPLE, detail + timeline)
Status: TODO · Model: sonnet · Repo: frontend · Dep: Q012, Q013, Q014 · Review: wajib
Ref: PRD§4, §F2, LEGACY L2 (field TQMCDM04), L3 (field TQMRND02), TQ-05
- Daftar dengan filter (tipe, status, customer, tanggal). Form COLOR dengan spek customer vs MGT berdampingan.
  Form SAMPLE: header + item + properti teknis. Detail menampilkan timeline status, aksi sesuai role,
  tombol "Buat SAMPLE dari COLOR" (K1; aktif penuh setelah lab trial di fase 2), pemilih customer dari master
  finance (+ prospek), dan input shade (pilih dari master atau ketik bebas).
DoD: alur DRAFT → SUBMITTED → APPROVED/REJECTED → REVISE dicoba di browser dengan 3 role, lint/typecheck hijau.
