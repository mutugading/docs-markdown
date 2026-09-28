# Impact Analysis

Sumber: `ALTHARADEV` (read-only, 2026-09-28) + repo `apps-mutugading` (search statis).
Legenda dampak: 🟥 wajib diubah · 🟧 diubah bila karyawan baru pakai NIK 10 digit · 🟩 tidak berubah strukturnya.

> **Dua gelombang dampak.** Fase 1–2 (bridge + auto) hanya menambah kolom/tabel; nilai `HMEMD_NIK` karyawan lama belum berubah. **Fase 3 (cutover)** mengganti `HMEMD_NIK` karyawan aktif menjadi NIK baru → struktur tabel transaksi tetap (🟩), tetapi **nilai** yang ditulis sejak tanggal cutover adalah NIK baru, sehingga setiap query yang menggabungkan periode sebelum & sesudah cutover lewat string NIK harus memakai resolver dua nilai (§1.5, §2.3).

## 1. Database

### 1.1 Profil data `HM_EMP_DATA` (DEV)
| Item | Nilai |
|---|---|
| Total baris | SLO 2.815, JKT 23 (migrasi `MIGRASI_JKT` 112 baris belum ada di DEV) |
| Aktif | SLO 1.329, JKT 23 |
| NIK duplikat / NULL | 3 / 2 |
| NIK non-numerik | 62 (14 `EX…`, ±40 akun `¿…`, nama) |
| Join date NULL | 1.236 (283 aktif); 1.191 bisa diisi dari `PE_DATE_JOIN` |
| Nilai join YYYYMM terbanyak | NULL 1.259; bulan riil max 52 (2003-08) → seri 4 digit cukup |
| KTP muncul >1 baris | 262 |
| `HMEMD_USER_ID` terisi / = NIK | 2.543 / 2.464 |
| `HMEMD_USER_MACHINE_ID` terisi | 34 (semua = NIK) |
| Aktif untuk cutover (resign NULL, status ≠ 00) | 1.308; **1.284** tanpa `HMEMD_USER_MACHINE_ID` → diisi NIK lama saat cutover |
| Pola KTP >1 baris | `OVERLAP` 55 · `SEQUENTIAL` 11 · `JOIN_NULL` 196 |
| Transisi status 02→01 dalam satu baris | 508 (tidak dipecah, PRD Q1) |
| Anomali | status `00` tanpa resign date 250 · status NULL 22 (dikecualikan dari cutover) |
| Kolom `%NIK%` MGTHRIS / FK ke `HM_EMP_DATA` | 47 kolom · 39 FK (semua via `HMEMD_SYS_ID`) |
| Grant `HM_EMP_AUTHORIZE_ORG` | 379 baris, 65 user, semua kolom terisi s/d section |
| Org | 1 company (MGT), 3 divisi (SLO, JKT, BDG), 22 dept, 47 section; dept/section **global** (tanpa kolom divisi), hirarki hanya via `HM_COMP_MAP` (76) |

### 1.2 MGTHRIS
| Objek | Dampak | Catatan |
|---|---|---|
| `HM_EMP_DATA` | 🟥 | + `HMEMD_NIK_NEW` (UQ comp+NIK), `HMEMD_PREV_SYS_ID`, `HMEMD_NIK_REASON`, trigger `TRG_NIK`/`TRG_NIK_UPD`; cutover: `HMEMD_NIK` aktif := NIK baru |
| `HM_NIK_COUNTER`, `HM_NIK_CONFIG`, `HM_EMP_NIK_MAP`, `V_EMP_NIK_LOOKUP`, `FN_GEN_NIK` | 🟥 baru | seri per (company, bulan), flag fase, alias NIK lama, resolver |
| `HM_EMP_DATA_TRG01` | 🟩 | tetap (SYS_ID) |
| `HM_EMP_DATA_TRG` (leave balance, sync NIK/NAME) | 🟩 | menerima NIK final dari trigger BEFORE |
| `TRG_HMEMD_TO_PAYROLL_EMP` | 🟥 | `PE_SUB_ACNT` = NIK baru; guard panjang kolom; **update-aware** (ikut ganti `PE_NIK` saat cutover) |
| `PRC_SYNC_PAYROLL_EMP` + `JOB_SYNC_PAYROLL_EMP` | 🟥 | hapus asumsi `max(PE_NIK)` numerik (`p_nik_floor=>2867`) |
| `HM_EMP_AUTHORIZE_ORG` | 🟥 | semantik wildcard, check constraint, `HCM_SYS_ID` nullable dipakai |
| 39 FK ke `HM_EMP_DATA` (by SYS_ID) | 🟩 | tidak terpengaruh cutover |
| Tabel transaksi NIK+SYS_ID (`HM_ATT_ACTUAL`, `HM_EMP_OVERTIME`, `HT_EMP_LEAVE_HDR/DTL`, `HM_EMP_CHANGE_SCH`, `HESSC*`, `HTSL*`, dll.) | 🟩 | historis tetap NIK lama; transaksi ≥ cutover NIK baru; rekap via `HMEMD_SYS_ID` |
| Tabel hanya NIK (`ATTENDANCES` 130k, `HM_ATT_LOGS` 460k, `HM_ATT_PARSED_LOGS` 461k, `RAW_ATTENDANCE_LOGS` 258k, `ANNUAL_BALANCE_LOG`, `*_LOGS.ACTOR_NIK`, `HESSCD_APPROVER_NIK`, `HTSLS_CONFIRMER_NIK`, `HTSLV_VERIFIER_NIK`, `SALES_CTL_APPROVER.SCAP_NIK`) | 🟩 struktur / 🟧 query | panjang ≥10 cukup; pasca cutover berisi NIK baru → rekap lintas periode & join actor wajib via `V_EMP_NIK_LOOKUP` |
| `MV_EMP_OVERTIME`, `MV_EMP_SCHEDULE` | 🟩 | |
| Package `PKG_ANUAL_BALANCE`, `PKG_EMP_OVERTIME`, `PKG_EMP_SCHEDULE`, `ADD_ANNUAL_BALANCE`, `SP_GEN_REKAP_OVT`, `TRG_HM_REKAP_OVT_SYSID` | 🟧 | review: operasi numerik / `TO_NUMBER(nik)` / `LPAD(nik,4)` |
| View `VIEW_ANOMALY_ATT`, `VIEW_OT_WEEKLY_HM`, `V_LEG_EMP`, `VIEW_OT_DAILY`, `VIEW_ATT_REKAP_HM` | 🟧 | review; tambah kolom `NIK_NEW` untuk tampilan |

### 1.3 MGTDAT (payroll Orion) — koordinasi tim Orion
| Kolom | Panjang | Dampak |
|---|---|---|
| `MGT_PAYROLL_EMP.PE_NIK` (PK dengan `PE_DIVISION`) | 15 | 🟩 muat 10 |
| `MGT_PAYROLL_ABNORMAL_*`, `MPARA`, `MPCEC/MPCED`, `MGT_PAYROLL_COMPNT_EMP_NW`, `MPCU`, `MPEF`, `MGT_PAYROLL_EMP_UPD/UPL`, `PL_NIK`, `MPSR`, `MTA`, `MGT_EMP_NPWP`, `MGT_EMP_CLEAR_HEAD`, `JKT_EMP.JE_NIK` | **5** | 🟧 perlebar ke VARCHAR2(15) |
| `PD_NIK` (2,27 jt), `MPJS_NIK` (2,0 jt), `PM_NIK`, `MPO_NIK`, `TD/TM`, `PS_NIK`, `MPA`, `MPAR` | 15 | 🟩 |
| `MGT_BUKTI_POTONG.TAX_NIK`, `CRH/CRD_REQ_NIK`, `CSH_NIK` | 10 | 🟩 pas (batas) |
| `MGTSALARY.SAL_SUB_ACNT`, `MENU_USER.USER_EMP_ID` | 12 | 🟩 |
| Fungsi/prosedur payroll (`FNC_MGT_PAYROLL_*`, `FNC_PPH_SLIP_DETAIL`, `FNC_MGT_THR_*`, `FNC_MGT_BONUS_*`, `PRC_SALARY_MGT`, `PRC_SALARY_PROCESS_MGT`, `PRC_RUN_PAYROLL_CHUNK*`, `PRC_EMP_PAY_RESIGN_MGT`, `PRC_MGT_PAY_OT_SINKRON`, `PKG_CASH_TO_VOUCHER`, `TRG_ATTD_JKT_JADWAL`, `TRG_MGT_PAYROLL_EMP_MAST`, `TRG_MGT_PAYROLL_LOAN_POST`) | 🟧 | cek variabel `VARCHAR2(5)` / `%TYPE` / `TO_NUMBER` |

> Perluasan `VARCHAR2(5)→(15)` adalah `ALTER TABLE MODIFY` metadata-only di Oracle (tidak rewrite data), aman online. Variabel PL/SQL ber-`%TYPE` ikut otomatis setelah recompile; yang di-hardcode `VARCHAR2(5)` harus diedit.

### 1.4 MGTAPPS / MGTATTD / legacy
| Objek | Dampak |
|---|---|
| `EMP_PAYROLL_OVERTIME.EPO_NIK` VARCHAR2(5) | 🟧 perlebar |
| `EMP_COUPON_DATA.ECD_PE_NIK`(15), `EMP_JADWAL`(30), `PRIMATA_MPO.PMPO_NIK`, `EVENT_*` | 🟩 |
| `MST_USERS` (414; `MU_HMEMD_SYS_ID`, `USER_ID`) | 🟩 (login fase 2 opsional) |
| `PKGCANTEENCOUPON`, `HRM_ATTENDENCE_PRS`, `PKG_ATTENDANCE_JKT`, `PKG_EVENT`, `PKG_OVERTIME_PRS`, `EMP_JADWAL_TRG01`, `FGETBREAK*_EMP` | 🟧 review |
| `MGTATTD` (`OS_NIK`/`TA_NIK` NUMBER) — outsourcing | 🟩 di luar scope |
| `ASMAR/ASMAT/ASPTXT` (`EMP_NIK` VARCHAR2(4)/(5), `LOAN_NIK` NUMBER) | 🟧 legacy: karyawan baru tidak dapat dicatat → konfirmasi apakah masih dipakai |

### 1.5 Dampak cutover (Fase 3) per jenis data
| Jenis | Sebelum cutover | Sesudah cutover | Penanganan |
|---|---|---|---|
| Master `HM_EMP_DATA` aktif | NIK lama | NIK baru | `04_cutover.sql` (backup + map `HRIS` primary) |
| Master resign / status 00 | NIK lama | NIK lama | tidak diubah (identitas historis) |
| Transaksi historis (< tanggal) | NIK lama | NIK lama | tidak di-mass-update; resolve via SYS_ID/map |
| Transaksi baru (≥ tanggal) | — | NIK baru | otomatis (aplikasi membaca `HMEMD_NIK`) |
| Leave balance, payroll emp | NIK lama | NIK baru | trigger existing + `TRG_HMEMD_TO_PAYROLL_EMP` update-aware |
| PIN mesin | = NIK lama | tetap NIK lama | `HMEMD_USER_MACHINE_ID` diisi saat cutover (1.284) |
| Login `HMEMD_USER_ID` | NIK lama | tetap | alias login NIK baru opsional |
| Kolom actor (`created_by`, approver) | NIK lama | NIK baru | tampilan & "milik sendiri" via resolver |
| Transaksi OPEN (draft/submitted) | NIK lama | NIK lama | default dibiarkan; opsional `04_cutover` C3 |
| Payroll MGTDAT kolom `VARCHAR2(5)` | NIK lama | **gagal** bila belum diperlebar | prasyarat C0.2 |

## 2. Repo — NIK (path relatif `Modules/`)

### 2.1 Wajib diubah 🟥
| File | Perubahan |
|---|---|
| `Hr/app/Models/MgtHris/Master/Employees/HmEmpData.php` | fillable, searchable, accessor `nik_display`, `machine_pin`, scope `whereNikAny` |
| `Hr/app/Livewire/Master/Employees/Form/EmployeeForm.php` (L71, 445) | NIK read-only, join date required |
| `Hr/app/Services/Master/Employees/EmployeeService.php` (L56, 131, 502, 564) | refresh setelah create; dropdown label |
| `Hr/app/Repositories/Master/Employees/EloquentEmployeeRepository.php` (L104, 184-218) | search NIK baru/lama; create tanpa NIK |
| `Hr/app/Imports/Master/Employees/EmployeeImport.php` (L17, 27, 42) | NIK tidak dari file; dedup KTP |
| `Auth/app/Repositories/Auth/EloquentAuthRepository.php` (L40) | jangan isi NIK dari input |
| `Hr/app/Services/Attendance/ParsingAttLogService.php` (L119) | resolve PIN → karyawan |
| `Core/app/Livewire/Master/Attendance/AttMachineUsers.php`, `Core/app/Services/Master/Attendance/MachineUserMoveService.php`, `ZkTecoUdpService`, `UserPinEditService`, `UserCreateService`, `AdmsCommandService`, `AttMachineImport` | PIN = `machine_pin` |
| `Hr/app/Services/Attendance/ManualAttendanceService.php` (L999, 1007) | PIN XML ke mesin = `machine_pin` |
| Rule `exists:hm_emp_data,hmemd_nik` (`WorkUnitEmployeeImport:69`) + semua import transaksi (Leave, AnnualBalance, Clarif, ShiftStatusChange, OvertimePlanning, ShortLeave, EmployeeSchedule, ManualAttendance, ShiftStatus, ChangeSchedule, MassAssignRolePermission) | resolve NIK lama/baru via `whereNikAny` (+ company) → simpan `HMEMD_NIK` saat ini |
| **Baru** `Hr/app/Services/Master/Employees/DuplicateEmployeeService.php` + aksi di detail karyawan | Duplicate Employee (REHIRE / CONTRACT_TO_PERMANENT / TRANSFER_COMPANY), spec §12 |
| Employee Movement (service/form mutasi) | tolak company tujuan ≠ asal → arahkan ke Duplicate Employee; NIK tidak disentuh |
| Laporan/rekap Mis (MonthlyAttendanceRecap, AttendanceSummary, AnnualBalance, Overtime*/Leave* report) | grouping via `HMEMD_SYS_ID` / `V_EMP_NIK_LOOKUP`, bukan string NIK (FR-15) |

### 2.2 Tampilan 🟧 (ganti ke `nik_display`)
- Export Hr: EmployeeExport, EmployeeMovementExport, ParsedLogExport, ManualAttendanceExport, ActualAttendanceExport, EmployeeScheduleDetailExport, TransactionMonitoringExport.
- Export Mis: EmployeeSchedule(AllDepartment)Export, WorkUnitEmployeeExport, AllDepartmentsSummarySheet, AttendanceSummaryExport, OvertimeActualSheet, OvertimeValidatedSheet, AnomalyListExport, AnnualBalanceExport, AttendanceClarificationExport, LeaveRequestExport, OvertimePlanningExport, MonthlyAttendanceRecapExport, ChangeScheduleExport, ShiftStatusChangeExport, ShortLeaveRequestExport, EmployeeDirectoryExport.
- Core: UserGroupExport, EfilingAccessExport.
- PDF: `pdf/leave/annual-leave-report`, `overtime-leave-report`, `exports/attendance-summary-pdf`.
- Notifikasi ApprovalRequired (Clarification, Leave, Overtime, Schedule, ShiftStatusChange, ShortLeave), VerificationRequired.
- View: `components/employee/profile`, Auth `_employee-header`, master-employee, `_personal-details`, parsed-log-monitor, monitor attendance/movement, riwayat kontrak/disiplin/shift, employee-detail-drawer, annual-balance-page.
- Template import (AnnualBalanceTemplateExport, import-modal) → header "NIK (baru/lama)".

### 2.3 Struktur tetap, nilai berubah pasca cutover 🟧
Kode di bawah tetap menulis/membaca `hmemd_nik`, sehingga otomatis memakai NIK baru setelah cutover. Yang perlu diubah hanya titik yang **membandingkan** nilai lintas periode (mis. "milik sendiri", join creator ke master) → via resolver.
- Actor/`created_by` (AppliesTransactionAuthorization L162/241, NotificationActionService L153, semua service Mis yang menulis `*_by`).
- Relasi `belongsTo(..., 'hmemd_nik')`: AnnualBalanceLog, HmEmpAttClarifLogs, HtEmpLeaveLogs, HmEmpOvertimeLogs, HmEmpChangeSchLogs, HmEmpShiftStsChgDtl/Logs.
- Join SQL NIK di approval repo (Clarification:340, Overtime:262, ChangeSchedule:245), listener notifikasi creator, AttendanceActualizationService (LTRIM).
- Index `2026_08_01_100000_add_attendance_lookup_indexes.php`.

### 2.4 Perlu dicek developer
- `Modules/SelfService/config/config.php` — 9 referensi NIK (kemungkinan NIK hardcode).
- Owner key relasi `createdByUser` di HmEmpContractHistory/HmEmpDisciplinary.
- `MonthlyAttendanceRecap` match `mpar_nik` ke tabel eksternal `AttRekapHm` (MGTDAT, VARCHAR2(5)).

## 3. Repo — Hak Akses

### 3.1 Kondisi sekarang
- Spatie permission (connection `oracle_mgthris`), model auth = `HmEmpData` (model_id = SYS_ID). Tidak ada Policy/Gate.
- Scope = tuple (comp, div, dept) dari org sendiri + `HM_EMP_AUTHORIZE_ORG`; **section disimpan tapi tidak dipakai**; tanpa wildcard.
- `AppliesEmployeeAuthorization::userBypassesEmployeeScope` return true jika user null (L38) → **job export tanpa user = lihat semua**.

### 3.2 Logic duplikat → ganti `OrgScopeService` 🟥
| File | Baris |
|---|---|
| `app/Traits/AppliesEmployeeAuthorization.php` | L33, 68, 115, 156, 216 |
| `app/Traits/AppliesTransactionAuthorization.php` | L224, 272, 316 |
| `Mis/.../EloquentEmployeeDirectoryRepository.php` | 139-160 |
| `Mis/.../EloquentWorkUnitRepository.php` | 91-95 |
| `Hr/app/Livewire/Attendance/ManualAttendance.php` | 641-720 |
| `Hr/app/Services/Attendance/ManualAttendanceService.php` | 609-656 |
| `Hr/.../EloquentParsedAttLogRepository.php` | 126-145 |
| `Mis/.../EloquentMonthlyAttendanceRecapRepository.php` | 43, 113 |
| `Hr/app/Services/Master/Employees/EmployeeService.php` | 499-553 |
| `Hr/app/Models/.../HmEmpData.php::scopeAuthorizedFor` | 399-415 |

### 3.3 Halaman/data tanpa scope 🟥
| Area | File |
|---|---|
| Master Employee list, widget, filter dropdown, export/import | `Hr/.../EloquentEmployeeRepository.php:80-135`, `ExportEmployees` |
| Transaction Monitoring (hanya cek role) | `Hr/.../EloquentTransactionMonitoringRepository.php:325` |
| Hr Dashboard | `Hr/app/Services/Dashboard/HrDashboardService.php:24` (trait di-use, tidak dipanggil) |
| Attendance Actual (filter dept manual) | `Mis/.../EloquentAttendanceActualRepository.php:112-128` |
| Import transaksi (tidak validasi scope target) | `Mis/app/Imports/Transactions/*` |
| Export/PDF lain | `LeaveReportService`, `annual-leave-report` |
| Attendance Machine Users | `Core/app/Livewire/Master/Attendance/AttMachineUsers.php` |
| Report SQL | `Report/.../ReportRunService`, `ReportViewer` |
| CI Project (dept saja) | `Finance/.../CiProjectAuthorizationService.php:178-190` |

### 3.4 Approval
| Area | Perubahan |
|---|---|
| `*ApprovalRepository::scopeApplyAuthorization` (Leave:88-110, Overtime:118, ChangeSchedule:104, Clarif:124/206, ShiftStatus:58/67, ShortLeave:97) | via OrgScope; section + wildcard |
| `findPotentialApproversForStatus` (Leave:305-338, Overtime:347, ChangeSchedule:327, ShiftStatus:236, ShortLeave:329, Clarif:432) | pakai `OrgScope::usersCovering(org)` |
| `SelfService/app/Services/DepartmentHierarchyResolver.php` (55-90, 128-160) | filter comp+div; releaser tidak lagi company-wide global |
| `hm_workflow_apprv_lvl` | 🟩 (level-based) |

### 3.5 Hardcode lokasi 🟧
`LcControl/.../JournalVoucherPostingService.php:75-105`, `PaymentVoucherPostingService.php:70-86`, `Transporter/.../TransporterPostingService.php:62-66`, default registrasi `Auth/.../EloquentAuthRepository.php:43-47` (`MGTSLOITGST`, div `IT`, dept `SLO` — terbalik, perbaiki).
