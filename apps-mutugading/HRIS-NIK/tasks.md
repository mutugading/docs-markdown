# Tasks

Estimasi: S ≤ 0,5 hari · M ≤ 2 hari · L ≤ 5 hari. Owner: DBA / BE (Laravel) / HR / Orion / QA.

## Fase 0 — Persiapan data
- [ ] **T0.1** (DBA, S) Pastikan migrasi JKT (`jkt_02_insert.sql`, 112 baris) sudah di DEV; snapshot tabel.
- [ ] **T0.2** (DBA, S) Jalankan `02_backfill.sql` mode `REPORT` per company → kirim ke HR: akun sistem, join date/company NULL, grup KTP (`OVERLAP` 55 / `SEQUENTIAL` 11 / `JOIN_NULL` 196), NIK duplikat, anomali status 00 tanpa resign (250) & NULL (22).
- [ ] **T0.3** (HR, M) Review & isi `TMP_NIK_DEDUP.DECISION` (`MERGE` + survivor / `SEPARATE`), join date manual, resign date untuk anomali status 00. Jawab open question PRD Q1–Q5.
- [ ] **T0.4** (DBA, S) Export DDL `TRG_HMEMD_TO_PAYROLL_EMP`, `PRC_SYNC_PAYROLL_EMP`, `JOB_SYNC_PAYROLL_EMP` untuk rollback.

## Fase 1 — Bridge NIK
- [ ] **T1.1** (DBA, M) `01_ddl.sql`: kolom `HMEMD_NIK_NEW`/`PREV_SYS_ID`/`NIK_REASON`, UQ (company, NIK_NEW), `HM_NIK_COUNTER` (company, period), `HM_NIK_CONFIG`, `HM_EMP_NIK_MAP`, `FN_GEN_NIK`, trigger (DISABLED), `V_EMP_NIK_LOOKUP`.
- [ ] **T1.2** (DBA, M) `02_backfill.sql` APPLY per company (NIK_NEW, PREV_SYS_ID grup SEQUENTIAL, counter, map `HRIS` primary) + `03_verify.sql` V1–V21 di DEV → PROD; enable trigger NIK.
- [ ] **T1.3** (BE, M) `HmEmpData`: fillable, searchable, `nik_display`, `nik_legacy`, `machine_pin`, relasi `previous()`/`next()`, `scopeWhereNikAny($v, $comp)`; model `HmEmpNikMap`.
- [ ] **T1.4** (BE, M) Ganti tampilan NIK → `nik_display` (views, exports, PDF, notifikasi — impact §2.2). Export master tambah "NIK Lama".
- [ ] **T1.5** (BE, M) Pencarian & dropdown menerima NIK baru/lama (EmployeeRepository, EmployeeService, SelfService ManagementApproval, ActivityLogMonitor) — filter company.
- [ ] **T1.6** (BE, M) Rule `NikExists` + resolver di semua import transaksi (impact §2.1); simpan `HMEMD_NIK` saat ini.
- [ ] **T1.7** (BE, S) Detail karyawan: section "Identitas NIK" (NIK baru, NIK lama, alias map, rantai penempatan).
- [ ] **T1.8** (BE, S) Pest test: accessor, lookup (NIK bentrok antar company/divisi), import dengan NIK lama & baru.

## Fase 2 — Auto-NIK + Duplicate Employee
- [ ] **T2.1** (Orion/DBA, M) Perlebar kolom `VARCHAR2(5)`→`(15)` MGTDAT & `EMP_PAYROLL_OVERTIME.EPO_NIK`; grep `ALL_SOURCE` untuk `VARCHAR2(5)`/`TO_NUMBER(.*NIK`; recompile invalid.
- [ ] **T2.2** (DBA, S) `TRG_HMEMD_TO_PAYROLL_EMP`: `PE_SUB_ACNT := NIK_NEW` dan **update-aware** (`AFTER INSERT OR UPDATE OF HMEMD_NIK`) untuk cutover.
- [ ] **T2.3** (DBA, M) Refactor `PRC_SYNC_PAYROLL_EMP` (tanpa `p_nik_floor`, kandidat via `V_EMP_NIK_LOOKUP`) + ubah job.
- [ ] **T2.4** (BE, M) `EmployeeForm`/`EmployeeImport`/`EloquentAuthRepository`: hapus input NIK, company & join date wajib, `refresh()` setelah insert, map ORA-20904/20902/20901 ke pesan validasi; import menolak KTP eks-resign → arahkan ke Duplicate Employee.
- [ ] **T2.5** (BE, L) PIN mesin: `ParsingAttLogService` (resolve PIN → simpan `HMEMD_NIK` saat ini), `ManualAttendanceService` (XML), `AttMachineUsers`, `MachineUserMoveService`, `ZkTecoUdpService`, `UserPinEditService`, `UserCreateService`, `AdmsCommandService`, `AttMachineImport` → `machine_pin`. Default `HMEMD_USER_MACHINE_ID` saat enroll.
- [ ] **T2.6** (BE/Infra, S) Uji panjang PIN di tiap model mesin (fallback `YYMMNNNN`).
- [ ] **T2.7** (BE, L) `DuplicateEmployeeService` (spec §12): validasi reason↔company, KTP aktif ganda, lock asal, tutup baris lama, salin data pribadi, insert tanpa NIK, audit; langkah mesin/akun via queue idempotent.
- [ ] **T2.8** (BE, M) UI Duplicate Employee di detail karyawan (permission `employee.duplicate`, scope asal & tujuan) + tampilan rantai penempatan.
- [ ] **T2.9** (BE, S) Employee Movement: tolak company tujuan ≠ asal, arahkan ke Duplicate Employee; NIK tidak berubah.
- [ ] **T2.10** (BE, M) Pest test Duplicate Employee: REHIRE, CONTRACT_TO_PERMANENT, TRANSFER_COMPANY, rollback saat gagal (counter tidak loncat), V19–V21 = 0; mutasi dalam company NIK tetap (AC-08, AC-09).
- [ ] **T2.11** (DBA, S) Set `HM_NIK_CONFIG.AUTO_ENABLED=Y`.
- [ ] **T2.12** (QA, M) Payroll paralel 1 periode di DEV (karyawan dummy NIK 10 digit): slip, PPh, BPJS, kupon kantin, lembur.

## Fase 3 — Cutover identity
- [ ] **T3.1** (DBA, S) `04_cutover.sql CHECK` DEV & PROD → C0.1–C0.7; tindak lanjut hasil ke HR/Orion.
- [ ] **T3.2** (BE, L) Resolver dua nilai (FR-15) di laporan/rekap lintas periode: grouping via `HMEMD_SYS_ID` / `V_EMP_NIK_LOOKUP`; hapus `GROUP BY` / join string NIK (review 47 kolom `%NIK%`).
- [ ] **T3.3** (BE, M) Tampilan kolom actor (`created_by`, `approved_by`, …) di-resolve via `V_EMP_NIK_LOOKUP`; approval self-exclusion via SYS_ID / (NIK lama, NIK baru).
- [ ] **T3.4** (QA, L) Cutover DEV lalu regresi penuh: absensi mesin, cuti, lembur, schedule, payroll, approval, import, laporan lintas periode, Orion, login (AC-04, AC-10).
- [ ] **T3.5** (HR/PM, S) Tetapkan tanggal cutover (awal periode payroll, PRD Q3); komunikasi ke user & tim Orion H-14.
- [ ] **T3.6** (DBA, S) H-0 PROD: freeze → `04_cutover.sql APPLY <tanggal>` → `03_verify.sql` V22–V25 + C2 → smoke test → buka freeze.
- [ ] **T3.7** (DBA/QA, M) Monitoring H+1…H+30: absensi harian, payroll periode pertama, rekap bulan cutover = 1 baris per karyawan. Rollback 0a/0b siap sampai payroll pertama selesai.

## Fase 4 — Org Scope (bisa paralel sejak fase 1)
- [ ] **T4.1** (BE, L) `app/Services/Authorization/OrgScopeService` + interface + binding; cache per request; wildcard & section; deny bila user null.
- [ ] **T4.2** (BE, M) Refactor `AppliesEmployeeAuthorization` & `AppliesTransactionAuthorization` ke service (API trait tetap).
- [ ] **T4.3** (BE, M) Hapus salinan inline (impact §3.2) → pakai service.
- [ ] **T4.4** (BE, L) Tutup gap scope (impact §3.3): Master Employee (list, widget, filter dropdown, export, import), Transaction Monitoring, Hr Dashboard, Attendance Actual, import transaksi, export/PDF, AttMachineUsers, Report SQL.
- [ ] **T4.5** (BE, M) Approval: `scopeApplyAuthorization`, `findPotentialApproversForStatus`, `HmEmpData::scopeAuthorizedFor`.
- [ ] **T4.6** (BE, M) `DepartmentHierarchyResolver`: filter comp+div.
- [ ] **T4.7** (BE, M) UI grant level (Company/Division/Dept/Section) di `AuthorizeOrgMappingModal`, ringkasan scope efektif.
- [ ] **T4.8** (DBA, S) Migrasi grant existing: `HMEATO_HMSEC_SECT_ID = NULL` (backup dulu); check constraint hirarki.
- [ ] **T4.9** (BE, S) Job export: kirim user eksplisit (`ExportEmployees` & lainnya).
- [ ] **T4.10** (BE, M) Pest test matriks scope (AC-06): own, dept, division, company, section, bypass, approval self-exclusion, Duplicate Employee lintas company.
- [ ] **T4.11** (BE, S) Permission baru bila perlu (`hr-master_employee-view_all_scope`, `hr-transaction_monitoring-view_all_scope`, `employee.duplicate`) + menu sync migration.

## Fase 5 — Opsional
- [ ] **T5.1** Login alias NIK baru (`AuthenticationService`).
- [ ] **T5.2** Selector company/division aktif.
- [ ] **T5.3** Config/master lokasi ERP (LcControl, Transporter).
- [ ] **T5.4** Perbaiki default registrasi local (`EloquentAuthRepository:43-47`).
- [ ] **T5.5** Retire kolom/logic legacy (identifier `LEGACY_NIK`, fallback PIN `LTRIM(HMEMD_NIK)`) setelah 2 periode pasca cutover.
