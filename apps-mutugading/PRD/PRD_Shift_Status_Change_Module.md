# PRD — Shift Status Change Module (HRIS Add-on)

**Version:** 2.0 Final
**Date:** 2026-05-21
**Author:** IT Lead — HRIS Team
**Status:** Approved for Development

---

## Executive Summary

Modul Shift Status Change adalah add-on di sistem HRIS untuk mengelola pengajuan perubahan status karyawan antara GS (General Shift) dan Shift. Perbedaan dua status ini terletak pada hak tunjangan di payroll: OT In Work allowance (7.50% untuk GS vs 8.77% untuk Shift) dan Tunjangan Shift.

Modul mengikuti pattern approval HRIS standar (DRAFT/SUBMITTED/APPROVED/RELEASED) dengan dua step administratif tambahan post-Released: VERIFIED (oleh HR) dan CONFIRMED (oleh Finance). Payroll closing diblokir sampai semua transaksi dalam periode terkait berstatus CONFIRMED.

---

## 1. Background & Objective

### 1.1 Background

Saat ini pengajuan perubahan shift status dilakukan dengan form manual. Workflow manualnya melibatkan: Manager/HOD ajukan form ke HRM Dept, HRM Dept isi riwayat jadwal kerja secara manual, Finance Dept hitung impact tunjangan, form diarsipkan fisik.

Masalah utama:
- Tidak ada audit trail elektronik untuk pengajuan dan persetujuan.
- HRM Dept harus manual mencatat riwayat jadwal kerja, padahal data sudah ada di tabel `HM_ATT_ACTUAL`.
- Tidak ada visibilitas real-time untuk Finance kapan harus memproses tunjangan.
- Risk payroll closing terlambat karena tidak tahu masih ada transaksi pending confirmation.
- Tidak ada mekanisme reporting agregat.

### 1.2 Objective

1. Digitalisasi workflow pengajuan perubahan shift status (GS ↔ Shift).
2. Otomatisasi verifikasi HR dengan integrasi ke data attendance existing (`HM_ATT_ACTUAL`).
3. Visibilitas dan kontrol Finance atas impact tunjangan sebelum payroll closing.
4. Audit trail lengkap dari draft sampai confirmed.
5. Gating mechanism untuk payroll closing.

### 1.3 Non-Objective

- Modul ini tidak menghitung tunjangan secara aktual. Perhitungan tetap di payroll system existing.
- Modul ini tidak mengubah working schedule karyawan secara langsung. Schedule diatur di modul Working Schedule existing.

---

## 2. Scope

### 2.1 In-Scope

- Form entry pengajuan oleh HR/Admin drafter.
- Approval workflow standar HRIS dengan tambahan step VERIFIED dan CONFIRMED.
- HR Verification: snapshot attendance dengan count hari Shift dan GS dalam periode payroll.
- Finance Confirmation: input 4 field impact tunjangan.
- Audit log untuk edit data post-Released oleh HR dan Finance.
- Snapshot refresh dengan history.
- Payroll closing gate: API untuk check pending transactions.
- Reporting agregat.

### 2.2 Out-of-Scope

- Mobile app native.
- Auto-confirmation rules.
- Integrasi langsung ke payroll calculation (payroll pull data via query).
- Reversal Request (koreksi dilakukan via pengajuan baru oleh user departemen).

---

## 3. Business Rules

### 3.1 Definisi Status Karyawan

| Status | Deskripsi | Default OT In Work | Default Tunjangan Shift |
|--------|-----------|-------------------|------------------------|
| GS | General Shift, jam kerja kantor normal | 7.50% | Tidak |
| Shift | Pekerja shift (pagi/siang/malam) | 8.77% | Ya |

Default dapat di-override oleh Finance saat konfirmasi untuk kasus khusus (Shift Adjustment).

### 3.2 Aturan Pengajuan

1. Drafter terbatas pada role HR/Admin dengan delegated entry rights.
2. **Drafter hanya membuat DRAFT — tidak punya hak SUBMIT.** Submit adalah aksi L1 Supervisor.
3. 1 pengajuan = 1 effective date. Multiple changes dalam satu periode = multiple records terpisah.
4. Validasi minimal saat entry: hanya cek master employee (NIK valid dan aktif).
5. Field mandatory: NIK karyawan, effective date, from status, to status, alasan perubahan.
6. **Koreksi setelah CONFIRMED tidak menggunakan Reversal Request.** User departemen membuat pengajuan baru.

### 3.3 Periode Payroll

- Periode payroll: tanggal 26 bulan sebelumnya sampai dengan tanggal 25 bulan berjalan.
- Effective date pengajuan akan mapped ke periode payroll yang sesuai untuk keperluan HR snapshot.

### 3.4 Aturan Integrasi Payroll

- Payroll closing diblokir jika ada transaksi Shift Status Change dalam periode payroll tersebut yang berstatus DRAFT, SUBMITTED, APPROVED, RELEASED, atau VERIFIED.
- Payroll boleh pull data hanya untuk transaksi berstatus CONFIRMED.
- Bila ada beberapa transaksi CONFIRMED untuk karyawan sama dengan effective date sama, **yang ter-CONFIRMED paling akhir** menjadi acuan. Pengajuan sebelumnya tetap di-arsip sebagai historical.

---

## 4. Status Flow & Aktor

### 4.1 Status & Aktor

| Status | Aksi | Aktor | Catatan |
|--------|------|-------|---------|
| DRAFT | Create | Drafter (HR/Admin) | Hanya drafter, tidak punya hak submit |
| SUBMITTED | Submit | L1 Supervisor | Atasan langsung karyawan target |
| APPROVED | Approve | L2 Manager | Atasan supervisor |
| RELEASED | Release | HOD | Head Department karyawan target |
| VERIFIED | Verify | HR Verifier | Snapshot attendance + count days |
| CONFIRMED | Confirm | Finance Confirmer | Set 4 field tunjangan. **Final, locked.** |
| REJECTED | Reject | Any approver | Final state. Buat pengajuan baru bila perlu. |
| ABORTED | Abort | Drafter | Hanya valid sebelum RELEASED. |

### 4.2 Aturan Khusus

**DRAFT**:
- Dapat di-edit ulang oleh drafter.
- Dapat di-**hard delete** oleh drafter (record dihapus permanen dari database).
- Tidak ada audit log untuk DRAFT yang dihapus.

**ABORTED**:
- Hanya bisa di-trigger oleh drafter.
- Valid di status SUBMITTED dan APPROVED.
- Setelah RELEASED, tidak bisa abort. Koreksi via pengajuan baru.
- Memerlukan reason mandatory saat abort.
- Notifikasi ke approver yang sudah approve.

**REJECTED**:
- Dapat di-trigger oleh L1, L2, HOD, HR, atau Finance.
- Final state — transaksi tidak bisa dilanjutkan.
- Memerlukan note mandatory dari rejecter.
- User departemen membuat pengajuan baru bila perlu.

**CONFIRMED**:
- Final state, locked permanent.
- Tidak ada Reversal Request.
- Koreksi hanya via pengajuan baru oleh user departemen.

### 4.3 Transisi Status Lengkap

| Dari | Ke | Trigger | Aktor |
|------|----|---------|----|
| (none) | DRAFT | Create | Drafter |
| DRAFT | (deleted) | Hard delete | Drafter |
| DRAFT | SUBMITTED | Submit | L1 Supervisor |
| DRAFT | REJECTED | Reject | L1 Supervisor |
| SUBMITTED | APPROVED | Approve | L2 Manager |
| SUBMITTED | REJECTED | Reject | L2 Manager |
| SUBMITTED | ABORTED | Abort | Drafter |
| APPROVED | RELEASED | Release | HOD |
| APPROVED | REJECTED | Reject | HOD |
| APPROVED | ABORTED | Abort | Drafter |
| RELEASED | VERIFIED | Verify | HR Verifier |
| RELEASED | REJECTED | Reject | HR Verifier |
| VERIFIED | CONFIRMED | Confirm | Finance Confirmer |
| VERIFIED | REJECTED | Reject | Finance Confirmer |

CONFIRMED, REJECTED, ABORTED = terminal states (tidak ada transisi keluar).

### 4.4 Smart Workflow Skip

Smart skip diterapkan pada level L1/L2/HOD bila terjadi kondisi berikut:

| Skip Type | Kondisi |
|-----------|---------|
| SKIP_SELF | Approver di level itu adalah drafter sendiri |
| SKIP_SUBORDINATE | Approver adalah bawahan dari drafter di hirarki |
| SKIP_NO_APPROVER | Tidak ada user dengan authority approval di level itu untuk karyawan target |

Skip biasanya jarang trigger di modul ini karena drafter HR/Admin terpisah dari struktur departemen karyawan target. Tetap relevan untuk kasus:
- Drafter coincidentally adalah Supervisor/Manager/HOD karyawan target.
- HR/Admin entry untuk karyawan HR Department sendiri (skip-self di HR Verify level).

---

## 5. Post-Released Steps

### 5.1 HR Verification

**Tujuan**: HR melakukan verifikasi data attendance karyawan untuk mendokumentasikan riwayat jadwal kerja dalam periode payroll terkait.

**Mekanisme**:
- Sistem otomatis pull data dari `HM_ATT_ACTUAL` join `HM_SCHEDULE` dan `HM_EMP_DATA` untuk periode payroll relevan.
- Data di-snapshot ke tabel `shift_status_snapshot` saat HR pertama kali buka form verification.
- Summarize berdasarkan `HMSCHD_TYPE`: hanya GS dan Shift yang dihitung (Off tidak masuk count).
- HR dapat refresh snapshot manual; setiap refresh tercatat di audit log.

**Hak HR**:
- Verify (transisi ke VERIFIED).
- Reject (transisi ke REJECTED final).
- Edit count days dan periode observation (sebelum verify, dengan audit log).
- Refresh snapshot (sebelum verify, dengan history).

**Query base data**:
```sql
SELECT a.HATA_HMEMD_NIK, c.HMEMD_NAME, a.HATA_DATE,
       a.HATA_HMSCHD_SCHEDULE, b.HMSCHD_TYPE
FROM mgthris.HM_ATT_ACTUAL a, mgthris.HM_SCHEDULE b, mgthris.HM_EMP_DATA c
WHERE a.HATA_HMEMD_NIK = :nik
  AND a.HATA_DATE BETWEEN :period_start AND :period_end
  AND b.HMSCHD_SCHEDULE = a.HATA_HMSCHD_SCHEDULE
  AND c.HMEMD_SYS_ID = a.HATA_HMEMD_SYS_ID
```

### 5.2 Finance Confirmation

**Tujuan**: Finance menentukan impact tunjangan untuk karyawan.

**Field yang harus diisi Finance**:

| Field | Tipe | Pilihan |
|-------|------|---------|
| Jadwal kerja efektif | enum | GS / Shift |
| OT In Work | enum | 7.50% / 8.77% |
| Tunjangan Shift | boolean | Ya / Tidak |
| Shift Adjustment | boolean | Ya / Tidak |

**Default values** (auto-populate berdasarkan to status, dapat di-override):
- Bila to status = GS: Jadwal = GS, OTIW = 7.50%, Tunjangan Shift = Tidak, Shift Adjustment = Tidak.
- Bila to status = Shift: Jadwal = Shift, OTIW = 8.77%, Tunjangan Shift = Ya, Shift Adjustment = Tidak.

**Hak Finance**:
- Confirm (transisi ke CONFIRMED, locked permanent).
- Reject (transisi ke REJECTED final).
- Edit 4 field tunjangan (sebelum confirm, dengan audit log).

### 5.3 Locking Setelah Confirmed

Setelah CONFIRMED:
- Semua field locked, tidak dapat diedit.
- Payroll system dapat mulai pull data.
- Koreksi hanya via pengajuan baru oleh user departemen.

---

## 6. Functional Requirements

### 6.1 Entry oleh Drafter
- FR-01: Drafter (role HR/Admin) dapat membuat DRAFT dengan field: NIK, effective date, from status, to status, alasan.
- FR-02: Auto-populate nama karyawan, departemen, posisi dari NIK.
- FR-03: Sistem tampilkan periode payroll terkait dan status karyawan saat ini.
- FR-04: **Drafter hanya bisa save DRAFT — tidak ada button Submit.** Submit oleh L1.
- FR-05: Drafter dapat **hard delete** DRAFT (record dihapus permanen).
- FR-06: Drafter dapat abort SUBMITTED atau APPROVED dengan reason mandatory.
- FR-07: Validasi minimal saat save: NIK valid dan aktif.

### 6.2 Approval Level Departemen (L1/L2/HOD)
- FR-08: L1 Supervisor memiliki inbox berisi DRAFT yang menunggu submission.
- FR-09: L1 dapat Submit (status → SUBMITTED) atau Reject (status → REJECTED).
- FR-10: L2 Manager memiliki inbox berisi SUBMITTED yang menunggu approval.
- FR-11: L2 dapat Approve (status → APPROVED) atau Reject.
- FR-12: HOD memiliki inbox berisi APPROVED yang menunggu release.
- FR-13: HOD dapat Release (status → RELEASED) atau Reject.
- FR-14: Reject di level manapun memerlukan note mandatory.

### 6.3 HR Verification
- FR-15: HR Verifier memiliki inbox berisi RELEASED yang menunggu verifikasi.
- FR-16: Saat HR pertama kali buka form, sistem auto-snapshot attendance dari `HM_ATT_ACTUAL`.
- FR-17: HR dapat refresh snapshot manual; tercatat di audit log.
- FR-18: HR dapat edit count days Shift/GS dan periode; tercatat di audit log.
- FR-19: HR dapat Verify (status → VERIFIED) atau Reject (status → REJECTED).

### 6.4 Finance Confirmation
- FR-20: Finance memiliki inbox berisi VERIFIED yang menunggu konfirmasi.
- FR-21: Form Confirmation menampilkan summary HR snapshot (read-only).
- FR-22: Finance input 4 field tunjangan; default value auto-populated.
- FR-23: Finance dapat save & continue later, Confirm (status → CONFIRMED, locked), atau Reject.

### 6.5 Payroll Integration
- FR-24: Endpoint API: `GET /shift-status/by-employee?nik=X&period=YYYY-MM` mengembalikan transaksi CONFIRMED efektif untuk periode tersebut.
- FR-25: Endpoint API: `GET /shift-status/pending-confirmation?period=YYYY-MM` mengembalikan list transaksi belum CONFIRMED.
- FR-26: Payroll closing process harus memanggil FR-25 dan memblokir closing bila ada hasil.
- FR-27: Bila ada multiple CONFIRMED untuk effective date sama, payroll pakai yang `ssc_confirmed_at` terakhir.

### 6.6 Notifikasi
- FR-28: Notifikasi ke L1 saat ada DRAFT baru.
- FR-29: Notifikasi ke L2 saat status SUBMITTED.
- FR-30: Notifikasi ke HOD saat status APPROVED.
- FR-31: Notifikasi ke HR saat status RELEASED.
- FR-32: Notifikasi ke Finance saat status VERIFIED.
- FR-33: Notifikasi ke karyawan target saat status RELEASED (perubahan resmi efektif).
- FR-34: Notifikasi ke drafter saat REJECTED dengan note dari rejecter.
- FR-35: Notifikasi escalation ke Finance Manager bila ada VERIFIED > 5 hari belum CONFIRMED.

### 6.7 Reporting
- FR-36: Report per karyawan dengan riwayat semua perubahan status.
- FR-37: Report agregat per departemen.
- FR-38: Report SLA: rata-rata waktu RELEASED → CONFIRMED.
- FR-39: Audit report: daftar transaksi dengan edit history oleh HR/Finance.
- FR-40: Export ke Excel/CSV.

---

## 7. Data Model

### 7.1 Tabel Utama

**`shift_status_change` (ssc_)**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| ssc_id | BIGINT PK | Auto increment |
| ssc_doc_no | VARCHAR(20) | Format SSC-YYYY-NNNN, unique |
| ssc_employee_id | VARCHAR FK | Karyawan target |
| ssc_effective_date | DATE | Tanggal mulai perubahan efektif |
| ssc_change_from | VARCHAR(10) | GS atau Shift |
| ssc_change_to | VARCHAR(10) | GS atau Shift |
| ssc_payroll_period | VARCHAR(7) | Format YYYY-MM |
| ssc_reason | VARCHAR(500) | Mandatory |
| ssc_status | VARCHAR(20) | DRAFT/SUBMITTED/APPROVED/RELEASED/VERIFIED/CONFIRMED/REJECTED/ABORTED |
| ssc_abort_reason | VARCHAR(500) | Mandatory bila ABORTED |
| ssc_submitted_at | TIMESTAMP | Nullable |
| ssc_approved_at | TIMESTAMP | Nullable |
| ssc_released_at | TIMESTAMP | Nullable |
| ssc_verified_at | TIMESTAMP | Nullable |
| ssc_confirmed_at | TIMESTAMP | Nullable |
| ssc_created_by | VARCHAR FK | Drafter |
| ssc_created_at | TIMESTAMP | |
| ssc_updated_at | TIMESTAMP | |

**`shift_status_approval` (ssa_)**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| ssa_id | BIGINT PK | |
| ssa_ssc_id | BIGINT FK | |
| ssa_level | VARCHAR(20) | L1_SUBMIT / L2_APPROVE / HOD_RELEASE / HR_VERIFY / FINANCE_CONFIRM |
| ssa_approver_id | VARCHAR FK | User actor |
| ssa_action | VARCHAR(10) | SUBMIT / APPROVE / RELEASE / VERIFY / CONFIRM / REJECT / SKIP |
| ssa_skip_reason | VARCHAR(30) | SKIP_SELF / SKIP_SUBORDINATE / SKIP_NO_APPROVER |
| ssa_note | VARCHAR(500) | Mandatory bila REJECT |
| ssa_acted_at | TIMESTAMP | |

**`shift_status_verification` (ssv_)**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| ssv_id | BIGINT PK | |
| ssv_ssc_id | BIGINT FK | One-to-one |
| ssv_verifier_id | VARCHAR FK | HR yang verify |
| ssv_count_shift_days | INT | Editable, audit-logged |
| ssv_count_gs_days | INT | Editable, audit-logged |
| ssv_period_start | DATE | |
| ssv_period_end | DATE | |
| ssv_verified_at | TIMESTAMP | |
| ssv_last_refreshed_at | TIMESTAMP | |

**`shift_status_snapshot` (sss_)**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| sss_id | BIGINT PK | |
| sss_ssv_id | BIGINT FK | |
| sss_attendance_date | DATE | |
| sss_schedule_code | VARCHAR(10) | |
| sss_schedule_type | VARCHAR(10) | GS / Shift / Off |
| sss_captured_at | TIMESTAMP | |

**`shift_status_confirmation` (ssn_)**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| ssn_id | BIGINT PK | |
| ssn_ssc_id | BIGINT FK | One-to-one |
| ssn_confirmer_id | VARCHAR FK | |
| ssn_work_schedule | VARCHAR(10) | GS / Shift |
| ssn_otiw_percent | DECIMAL(5,2) | 7.50 atau 8.77 |
| ssn_shift_allowance | BOOLEAN | |
| ssn_shift_adjustment | BOOLEAN | |
| ssn_note | VARCHAR(500) | Opsional |
| ssn_confirmed_at | TIMESTAMP | |

**`shift_status_audit_log` (ssl_)**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| ssl_id | BIGINT PK | |
| ssl_ssc_id | BIGINT FK | |
| ssl_changed_by | VARCHAR FK | |
| ssl_field_name | VARCHAR(50) | |
| ssl_old_value | VARCHAR(255) | |
| ssl_new_value | VARCHAR(255) | |
| ssl_change_context | VARCHAR(30) | HR_VERIFY / FINANCE_CONFIRM / REFRESH_SNAPSHOT |
| ssl_changed_at | TIMESTAMP | |

### 7.2 Indeks

- `shift_status_change`: index `(ssc_employee_id, ssc_effective_date)`, `(ssc_status)`, `(ssc_payroll_period)`.
- `shift_status_approval`: index `(ssa_ssc_id)`, `(ssa_approver_id, ssa_acted_at)`.
- `shift_status_snapshot`: index `(sss_ssv_id, sss_attendance_date)`.
- `shift_status_audit_log`: index `(ssl_ssc_id, ssl_changed_at)`.

### 7.3 Existing Tables Referenced (Read-only)

| Tabel | Purpose |
|-------|---------|
| MGTHRIS.HM_EMP_DATA | Master karyawan |
| MGTHRIS.HM_ATT_ACTUAL | Data attendance harian |
| MGTHRIS.HM_SCHEDULE | Master schedule code dan type |

---

## 8. UI/UX

### 8.1 Halaman & Komponen

1. **Shift Status Change List** — list view dengan filter periode, status, departemen. Quick stats per status.
2. **New Request Form** — form entry untuk drafter (hanya save Draft).
3. **Request Detail** — view detail dengan timeline lengkap (Draft → Confirmed atau Rejected/Aborted).
4. **L1 Submit Inbox** — pending submission untuk L1 Supervisor.
5. **L2 Approval Inbox** — pending approval untuk L2 Manager.
6. **HOD Release Inbox** — pending release untuk HOD.
7. **HR Verification Workbench** — pending verification dengan snapshot attendance.
8. **Finance Confirmation Workbench** — pending confirmation dengan 4 field input.
9. **Audit Log Viewer** — read-only view perubahan data.
10. **Reports** — agregat dan SLA.

### 8.2 Status Color Coding

| Status | Warna |
|--------|-------|
| DRAFT | Gray |
| SUBMITTED | Blue (light) |
| APPROVED | Blue |
| RELEASED | Teal |
| VERIFIED | Purple |
| CONFIRMED | Teal (darker) |
| REJECTED | Red |
| ABORTED | Amber |

### 8.3 Timeline Display

Detail page menampilkan timeline lengkap dengan icon dan keterangan:

- DRAFT — Created by Siti Rahayu (HR Admin), 2026-02-22 14:30
- SUBMITTED — Submitted by Bambang Setia (L1 Spv), 2026-02-22 16:10
- APPROVED — Approved by Catur Wibowo (L2 Mgr), 2026-02-23 09:05
- RELEASED — Released by Indra Pratama (HOD), 2026-02-23 14:30
- VERIFIED — Verified by Anita Wijaya (HR), 2026-02-28 09:15
- CONFIRMED — Confirmed by Dewi Saraswati (Finance), 2026-03-01 10:30

---

## 9. Edge Cases & Handling

| # | Edge Case | Handling |
|---|-----------|----------|
| 1 | Karyawan resign setelah submission | Block lanjut approval. Drafter perlu abort manual. |
| 2 | Effective date di masa lalu (backdated) | Diizinkan, audit log mencatat backdate. |
| 3 | Effective date melewati payroll closed | Block submit, harus via HR manual adjustment. |
| 4 | from_status sama dengan to_status | Block di entry validation. |
| 5 | Multiple changes overlap effective date | Diizinkan. Payroll resolve berdasarkan confirmed_at terakhir. |
| 6 | Data attendance kosong saat snapshot | Snapshot created dengan count 0. HR dapat input manual dengan justifikasi. |
| 7 | Snapshot refresh setelah HR Verify | Tidak diizinkan setelah status VERIFIED, snapshot locked. |
| 8 | Finance set OTIW selain 7.50 atau 8.77 | Block via enum validation. |
| 9 | Pengajuan baru untuk koreksi (replace) CONFIRMED | Pengajuan baru independen, payroll resolve by confirmed_at terakhir. |
| 10 | Payroll closing dengan transaksi pending | Block dengan list transaksi yang harus diselesaikan. |
| 11 | Drafter resign saat transaksi DRAFT | DRAFT dihapus manual oleh admin atau diambil alih drafter lain. |
| 12 | Drafter delete DRAFT tidak sengaja | Tidak ada undo. Drafter perlu re-entry. |
| 13 | Reject di level manapun | Status REJECTED final dengan note. User dept buat pengajuan baru. |
| 14 | HR reject setelah RELEASED | Diizinkan, transaksi REJECTED final meskipun sudah lewat departemen. |
| 15 | Finance reject setelah VERIFIED | Diizinkan, transaksi REJECTED final. |
| 16 | Edit data setelah CONFIRMED | Block, locked permanent. Koreksi via pengajuan baru. |
| 17 | Smart Skip trigger di approval level | Skip tercatat di approval history, transaksi lanjut ke level berikutnya. |

---

## 10. Acceptance Criteria

### 10.1 Functional
- Drafter dapat save DRAFT dengan validasi minimal (master employee).
- Drafter tidak punya button Submit; submission oleh L1.
- Drafter dapat hard delete DRAFT atau abort di SUBMITTED/APPROVED.
- Flow approval L1 Submit → L2 Approve → HOD Release berjalan dengan Smart Skip.
- Reject di level manapun = REJECTED final.
- HR Verification auto-snapshot dari HM_ATT_ACTUAL.
- Finance Confirmation dengan 4 field, default auto-populated.
- CONFIRMED transaksi locked, payroll dapat pull data.
- Pengajuan baru untuk koreksi independen, tidak menghapus transaksi lama.
- Payroll closing diblokir bila ada pending transaction.
- Audit log lengkap untuk semua edit post-Released.

### 10.2 Non-Functional
- Snapshot attendance load < 2 detik untuk periode payroll standar.
- Form entry response < 1 detik.
- Audit log query < 500ms.
- API payroll integration response < 1 detik per karyawan.

---

## 11. Dependencies

- Modul Employee Master (`HM_EMP_DATA`).
- Modul Attendance (`HM_ATT_ACTUAL`).
- Modul Schedule Master (`HM_SCHEDULE`).
- Payroll System (consumer API).
- Notification service.

---

## 12. Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Payroll terlambat karena pending Confirmation | Medium | High | Notifikasi escalation, SLA dashboard, threshold alert |
| Data attendance berubah setelah HR Verify | Medium | Medium | Snapshot locked setelah verify, koreksi via pengajuan baru |
| Finance salah set tunjangan | Low | Medium | Default values auto-populated, reject mechanism |
| Multiple koreksi pengajuan sulit di-track | Medium | Medium | Reporting menampilkan riwayat per karyawan dengan timeline |
| Drafter delete DRAFT tidak sengaja | Low | Low | Konfirmasi dialog sebelum delete, tidak ada undo |
| Approver resign | Low | Medium | Delegation rule di approval engine (out of scope modul) |

---

## Appendix A — Glossary

| Istilah | Definisi |
|---------|----------|
| GS | General Shift, karyawan jam kerja kantor normal |
| Shift | Karyawan dengan rotasi shift |
| OTIW | OT In Work, persentase tunjangan untuk lembur di jam kerja |
| Drafter | User dengan role HR/Admin yang entry pengajuan |
| L1 / L2 / HOD | Level approval di departemen |
| Verifier | HR yang melakukan verifikasi attendance snapshot |
| Confirmer | Finance yang set impact tunjangan |
| Snapshot | Capture data attendance pada saat HR Verify untuk audit |
| Payroll Period | Tanggal 26 bulan sebelumnya s/d 25 bulan berjalan |
| Smart Workflow | Pattern approval dengan auto-skip berdasarkan kondisi drafter-approver |
| Hard Delete | Penghapusan permanen record dari database (untuk DRAFT) |

---

## Appendix B — Test Scenarios

| # | Scenario | Expected |
|---|----------|----------|
| 1 | Drafter HR save DRAFT untuk staff biasa | DRAFT created, drafter tidak punya button Submit |
| 2 | Drafter hard delete DRAFT | Record dihapus permanen, tidak ada audit |
| 3 | L1 Supervisor Submit DRAFT | Status berubah ke SUBMITTED, notif ke L2 |
| 4 | L2 Manager Approve | Status APPROVED, notif ke HOD |
| 5 | HOD Release | Status RELEASED, notif ke HR dan karyawan target |
| 6 | HR Verify | Status VERIFIED, snapshot locked, notif ke Finance |
| 7 | Finance Confirm | Status CONFIRMED, locked permanent, payroll boleh pull |
| 8 | L1 Reject DRAFT | Status REJECTED final, notif ke drafter dengan note |
| 9 | L2 Reject SUBMITTED | Status REJECTED final |
| 10 | HOD Reject APPROVED | Status REJECTED final |
| 11 | HR Reject RELEASED | Status REJECTED final |
| 12 | Finance Reject VERIFIED | Status REJECTED final |
| 13 | Drafter Abort di SUBMITTED | Status ABORTED, notif ke approver yang sudah approve |
| 14 | Drafter Abort di APPROVED | Status ABORTED |
| 15 | Drafter Abort di RELEASED | Block, tidak diizinkan |
| 16 | User dept buat pengajuan baru untuk koreksi CONFIRMED | Pengajuan baru independen, payroll resolve by confirmed_at terakhir |
| 17 | Smart Skip: Drafter = L1 Supervisor | L1 di-skip, lanjut ke L2 |
| 18 | Payroll closing dengan VERIFIED pending | Block dengan list pending |
| 19 | HR refresh snapshot sebelum verify | Snapshot updated, tercatat di history |
| 20 | HR refresh snapshot setelah VERIFIED | Block, snapshot locked |

---

## Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-05-21 | IT Lead | Initial draft dengan 3-level approval dan Reversal Request |
| 2.0 | 2026-05-21 | IT Lead | Final version. Pattern HRIS 4 status (DRAFT/SUBMITTED/APPROVED/RELEASED). Drafter terpisah dari L1 Submit. Reversal Request dihapus — koreksi via pengajuan baru. REJECT diizinkan di semua level termasuk HR/Finance, final state. Hard delete untuk DRAFT. |
