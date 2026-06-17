# PRD — Short Leave Module (HRIS Add-on)

**Version:** 3.0 Final
**Date:** 2026-05-21
**Author:** IT Lead — HRIS Team
**Status:** Approved for Development
**Related Module:** Shift Status Change Module (shared Smart Workflow pattern)

---

## Executive Summary

Modul Short Leave adalah add-on di sistem HRIS yang memungkinkan karyawan mengajukan ijin sebagian jam kerja dalam satu hari: datang terlambat (Late In), pulang awal (Early Out), ijin tengah hari (Permit), atau tugas luar (On Duty).

Modul ini menggunakan **Dual-Track Workflow**: satu track untuk approval departemen (DRAFT → SUBMITTED → APPROVED → RELEASED) dan satu track paralel untuk verifikasi operasional (HR Verification + Security Confirmation). Kedua track berjalan independen — attendance marker di-apply saat RELEASED tanpa menunggu HR/Security, namun data dari HR dan Security dicatat sebagai audit trail.

Modul ini mengikuti pattern HRIS standar (DRAFT/SUBMITTED/APPROVED/RELEASED) yang konsisten dengan modul Shift Status Change dan modul HRIS lainnya.

---

## 1. Background & Objective

### 1.1 Background

Kebutuhan karyawan untuk ijin sebagian jam kerja selama ini dilakukan secara informal (chat, email, lisan), menyebabkan:
- Tidak ada record formal untuk justifikasi anomali attendance.
- Sulit untuk reporting dan analisis pola short leave.
- Security tidak punya visibilitas karyawan mana yang keluar dengan ijin approved.
- HR kesulitan membedakan keterlambatan tanpa ijin dengan yang sudah disetujui.

### 1.2 Objective

1. Menyediakan kanal formal untuk pengajuan dan approval short leave.
2. Mengintegrasikan record dengan modul attendance (attendance marker saat RELEASED).
3. Memberikan visibilitas ke Security per site untuk karyawan yang keluar/masuk di luar jam normal.
4. Menyediakan data audit trail lengkap dari HR dan Security.
5. Konsisten dengan pattern Smart Workflow yang diterapkan di semua modul HRIS.

### 1.3 Non-Objective

- Modul ini tidak menggantikan modul Leave (cuti full day).
- Modul ini tidak menangani limit kuota kuantitatif.
- Modul ini tidak memodifikasi data tap-in/tap-out aktual dari mesin attendance.
- HR Rejection dan Security track tidak memblokir attendance marker.

---

## 2. Scope

### 2.1 In-Scope

- Form entry pengajuan oleh HR/Admin drafter.
- Smart Workflow approval: L1 Submit → L2 Approve → HOD Release.
- Attendance marker apply saat RELEASED.
- HR Verification track (paralel sejak SUBMITTED).
- Security Confirmation track per site (setelah HR VERIFIED).
- Status composite untuk representasi dual-track di tabel utama.
- Reporting dan audit trail.
- Notifikasi per transisi status.

### 2.2 Out-of-Scope

- Mobile app native.
- Auto-approval rules.
- Reversal Request (koreksi via pengajuan baru).

---

## 3. Business Rules

### 3.1 Tipe Short Leave

| Kode | Nama | Working Hour Impact | Security Input |
|------|------|---------------------|---------------|
| LATE_IN | Datang terlambat | Berkurang sesuai aktual tap-in | Jam masuk aktual |
| EARLY_OUT | Pulang awal | Berkurang sesuai aktual tap-out | Jam keluar aktual |
| PERMIT | Ijin tengah hari | Berkurang sebesar durasi ijin | Jam keluar + jam masuk kembali aktual |
| ON_DUTY | Tugas luar tengah hari | Tidak berkurang (dihitung kerja) | Jam keluar + jam masuk kembali aktual |

### 3.2 Aturan Umum

1. **Drafter terbatas pada role HR/Admin** dengan delegated entry rights. Tidak ada self-service karyawan.
2. **Drafter hanya membuat DRAFT** — tidak punya hak SUBMIT. Submit adalah aksi L1 Supervisor.
3. Short leave tidak mengurangi kuota leave karyawan.
4. Tidak ada limit kuantitatif sistem (jam/frekuensi). Kontrol via approval.
5. Backdate diizinkan dengan justifikasi mandatory (cutoff: periode payroll aktif).
6. Multiple short leave per hari diizinkan sebagai request terpisah, range jam tidak boleh overlap.
7. Attachment opsional untuk semua tipe.
8. Short leave hanya valid di working day sesuai working schedule karyawan.
9. Short leave tidak dapat diajukan di tanggal yang sudah ada Leave full day approved.
10. **Koreksi setelah RELEASED**: tidak ada Reversal Request. User ajukan request baru.

### 3.3 Working Hour Calculation

Urutan:
1. Ambil attendance aktual (tap-in/tap-out) sebagai baseline.
2. Cek short leave RELEASED di tanggal tersebut.
3. Apply rule per tipe (lihat tabel 3.1).
4. Hitung working hour final.

Short leave tidak mengubah angka tap in-out. Short leave hanya menambah marker yang menjelaskan anomali. Calculation harus idempotent.

---

## 4. Dual-Track Workflow

### 4.1 Konsep

Short Leave menggunakan dua track yang berjalan secara paralel dan independen:

**Track 1 — Approval Track (Departemen)**
Flow keputusan: DRAFT → SUBMITTED → APPROVED → RELEASED.
Attendance marker di-apply saat RELEASED. Tidak menunggu Track 2.

**Track 2 — Verification Track (Operasional)**
- **HR Sub-track**: aktif sejak SUBMITTED (paralel dengan approval chain).
- **Security Sub-track**: aktif setelah HR VERIFIED.
Kedua sub-track bersifat audit/record. Tidak memblokir attendance marker.

### 4.2 Status Composite

Tabel utama menyimpan 3 kolom status:

| Kolom | Nilai | Default |
|-------|-------|---------|
| `slr_approval_status` | DRAFT / SUBMITTED / APPROVED / RELEASED / REJECTED / ABORTED | DRAFT |
| `slr_hr_status` | NULL / PENDING / VERIFIED / REJECTED | NULL |
| `slr_security_status` | NULL / PENDING / CONFIRMED | NULL |

**Trigger perubahan status**:

| Event | slr_approval_status | slr_hr_status | slr_security_status |
|-------|--------------------|--------------|--------------------|
| Drafter create | DRAFT | NULL | NULL |
| L1 Submit | SUBMITTED | PENDING | NULL |
| L2 Approve | APPROVED | (tidak berubah) | NULL |
| HOD Release | RELEASED | (tidak berubah) | (tidak berubah) |
| HR Verify | (tidak berubah) | VERIFIED | PENDING |
| HR Reject | (tidak berubah) | REJECTED | NULL |
| Security Confirm | (tidak berubah) | (tidak berubah) | CONFIRMED |
| Any Reject (approval) | REJECTED | NULL | NULL |
| Drafter Abort | ABORTED | NULL | NULL |

### 4.3 Aktor & Aksi per Status

| Status | Aksi | Aktor | Catatan |
|--------|------|-------|---------|
| DRAFT | Create | Drafter (HR/Admin) | Tidak bisa Submit |
| DRAFT | Hard delete | Drafter | Record dihapus permanen |
| SUBMITTED | Submit | L1 Supervisor | Trigger HR track aktif |
| APPROVED | Approve | L2 Manager | |
| RELEASED | Release | HOD | Apply attendance marker |
| HR PENDING | Verify / Reject | HR Verifier | Paralel sejak SUBMITTED |
| SECURITY PENDING | Confirm | Security per site | Setelah HR VERIFIED |
| REJECTED | Reject | L1 / L2 / HOD | Final, approval chain |
| ABORTED | Abort | Drafter | Sebelum RELEASED |

### 4.4 Aturan REJECT & ABORT

**REJECT di approval track (L1/L2/HOD)**:
- `slr_approval_status` = REJECTED (final).
- `slr_hr_status` dan `slr_security_status` di-set NULL.
- Note mandatory dari rejecter.
- HR track yang sedang berjalan dihentikan.

**REJECT di HR track**:
- `slr_hr_status` = REJECTED.
- `slr_approval_status` **tidak berubah** — approval chain tetap berjalan.
- Attendance marker tetap apply saat RELEASED.
- Security track tidak dibuka (karena HR tidak VERIFIED).
- HR Rejection bersifat catatan audit. Manager/HOD dapat melihat ini saat review.

**Security tidak bisa REJECT** — hanya CONFIRM dengan input jam aktual.

**ABORT oleh Drafter**:
- Valid di status SUBMITTED dan APPROVED.
- Setelah RELEASED, tidak bisa abort.
- Reason mandatory.
- Notifikasi ke approver yang sudah approve.

**Hard Delete DRAFT**:
- Record dihapus permanen dari database.
- Tidak ada audit log untuk DRAFT yang dihapus.
- Konfirmasi dialog sebelum delete.

### 4.5 Smart Workflow Skip

| Skip Type | Kondisi |
|-----------|---------|
| SKIP_SELF | Approver di level itu adalah drafter |
| SKIP_SUBORDINATE | Approver adalah bawahan dari drafter di hirarki |
| SKIP_NO_APPROVER | Tidak ada user valid di level itu untuk karyawan target |

Semua skip tercatat di `slr_approval` dengan `sla_action = SKIP` dan `sla_skip_reason`.

**Special case — Top Position**:
Bila semua level ter-skip dan tidak ada atasan untuk eskalasi (top position), transaksi auto-RELEASED dengan peer notification.

---

## 5. Security Confirmation Detail

### 5.1 Security per Site

Security yang berhak confirm adalah Security **sesuai site/plant karyawan**. Setiap site memiliki inbox terpisah. Sistem menentukan site berdasarkan lokasi kerja karyawan di master data.

### 5.2 Field Input Security per Tipe

| Tipe | Field Security |
|------|---------------|
| LATE_IN | `sls_actual_start_time` (jam masuk aktual) |
| EARLY_OUT | `sls_actual_end_time` (jam keluar aktual) |
| PERMIT | `sls_actual_end_time` + `sls_actual_start_time` (jam keluar & kembali) |
| ON_DUTY | `sls_actual_end_time` + `sls_actual_start_time` (jam keluar & kembali) |

### 5.3 Discrepancy Handling

Jam aktual dari Security bisa berbeda dari jam yang diajukan. Sistem mencatat selisih (`sls_duration_diff_minutes`) untuk keperluan audit dan reporting. Tidak ada auto-action berdasarkan selisih — flagging untuk review HR/Manager saja.

---

## 6. Functional Requirements

### 6.1 Entry oleh Drafter
- FR-01: Drafter (HR/Admin) dapat membuat DRAFT: NIK, tanggal, tipe, jam mulai, jam selesai, alasan.
- FR-02: Auto-populate nama karyawan, departemen, posisi, site dari NIK.
- FR-03: Sistem tampilkan shift karyawan di tanggal tersebut dan preview working hour impact.
- FR-04: Sistem tampilkan preview Smart Workflow (level mana akan di-skip).
- FR-05: **Drafter tidak punya button Submit** — hanya Save Draft.
- FR-06: Drafter dapat hard delete DRAFT dengan konfirmasi dialog.
- FR-07: Drafter dapat abort SUBMITTED atau APPROVED dengan reason mandatory.
- FR-08: Field alasan mandatory. Bila backdated, field backdate justification mandatory.
- FR-09: Attachment opsional, max 2MB, format jpg/png/pdf.

### 6.2 Validasi
- FR-10: Tanggal short leave tidak boleh di hari libur/off sesuai working schedule.
- FR-11: Tidak boleh ada Leave full day APPROVED/RELEASED di tanggal sama.
- FR-12: Range jam tidak boleh overlap dengan short leave aktif lain di tanggal sama.
- FR-13: Jam mulai/selesai harus dalam range jam shift karyawan.
- FR-14: Backdate hanya sampai cutoff date periode payroll aktif.
- FR-15: LATE_IN: jam mulai = jam mulai shift. EARLY_OUT: jam selesai = jam selesai shift.
- FR-16: from_type dan to_type tidak boleh sama (validasi tipe).

### 6.3 Approval Track (L1/L2/HOD)
- FR-17: L1 Supervisor memiliki inbox DRAFT yang menunggu submission.
- FR-18: L1 dapat Submit (→ SUBMITTED, trigger HR track) atau Reject.
- FR-19: L2 Manager memiliki inbox SUBMITTED yang menunggu approval.
- FR-20: L2 dapat Approve (→ APPROVED) atau Reject.
- FR-21: HOD memiliki inbox APPROVED yang menunggu release.
- FR-22: HOD dapat Release (→ RELEASED, apply attendance marker) atau Reject.
- FR-23: Reject di level manapun memerlukan note mandatory.

### 6.4 Attendance Marker (saat RELEASED)
- FR-24: Saat HOD release, sistem otomatis create record di `short_leave_attendance_impact`.
- FR-25: Marker per tipe sesuai business rules (LATE_IN/EARLY_OUT mengurangi WH, PERMIT mengurangi, ON_DUTY tidak).
- FR-26: Marker hanya valid bila ada attendance record di tanggal tersebut. Bila tidak ada, flag "abnormal" untuk review.

### 6.5 HR Verification Track
- FR-27: HR Verifier memiliki inbox PENDING (slr_hr_status = PENDING) sejak SUBMITTED.
- FR-28: HR dapat melihat detail request, shift karyawan, attendance history 7 hari, short leave history 30 hari.
- FR-29: HR dapat Verify (slr_hr_status → VERIFIED, trigger Security track) atau Reject (slr_hr_status → REJECTED, approval chain tidak terpengaruh).
- FR-30: HR Reject memerlukan note mandatory.
- FR-31: Approval chain yang sedang berjalan tidak diblokir oleh HR status.

### 6.6 Security Confirmation Track
- FR-32: Security per site memiliki inbox PENDING (slr_security_status = PENDING) setelah HR VERIFIED.
- FR-33: Security input jam aktual sesuai tipe (lihat section 5.2).
- FR-34: Sistem menghitung `sls_duration_diff_minutes` antara jam diajukan vs aktual.
- FR-35: Security Confirm (slr_security_status → CONFIRMED). Security tidak bisa REJECT.
- FR-36: Security hanya bisa confirm setelah slr_hr_status = VERIFIED.

### 6.7 Notifikasi
- FR-37: Notifikasi ke L1 saat ada DRAFT baru.
- FR-38: Notifikasi ke L2 saat SUBMITTED.
- FR-39: Notifikasi ke HOD saat APPROVED.
- FR-40: Notifikasi ke HR saat SUBMITTED (HR track mulai).
- FR-41: Notifikasi ke Security site saat HR VERIFIED (Security track mulai).
- FR-42: Notifikasi ke karyawan target saat RELEASED.
- FR-43: Notifikasi ke drafter saat REJECTED dengan note.
- FR-44: Notifikasi ke Manager/HOD saat HR Reject (untuk awareness, bukan blocking).

### 6.8 Reporting
- FR-45: Report short leave per karyawan dengan filter periode dan tipe.
- FR-46: Report agregat per departemen.
- FR-47: Report discrepancy: daftar transaksi dengan selisih jam aktual Security vs jam diajukan.
- FR-48: Report HR Rejection: transaksi yang di-reject HR untuk audit compliance.
- FR-49: Report pending track: berapa transaksi RELEASED tapi belum HR VERIFIED / Security CONFIRMED.
- FR-50: Export ke Excel/CSV.

---

## 7. Data Model

### 7.1 Tabel Utama

**`short_leave_request` (slr_)**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| slr_id | BIGINT PK | |
| slr_doc_no | VARCHAR(20) | Format SL-YYYY-NNNN, unique |
| slr_employee_id | VARCHAR FK | Karyawan target |
| slr_site_id | VARCHAR FK | Site/plant karyawan |
| slr_type | VARCHAR(20) | LATE_IN / EARLY_OUT / PERMIT / ON_DUTY |
| slr_leave_date | DATE | |
| slr_start_time | TIME | Jam mulai yang diajukan |
| slr_end_time | TIME | Jam selesai yang diajukan |
| slr_duration_minutes | INT | Computed |
| slr_reason | VARCHAR(500) | Mandatory |
| slr_backdate_reason | VARCHAR(500) | Mandatory bila backdated |
| slr_attachment_url | VARCHAR(255) | Optional |
| slr_approval_status | VARCHAR(20) | DRAFT/SUBMITTED/APPROVED/RELEASED/REJECTED/ABORTED |
| slr_hr_status | VARCHAR(20) | NULL/PENDING/VERIFIED/REJECTED |
| slr_security_status | VARCHAR(20) | NULL/PENDING/CONFIRMED |
| slr_is_backdated | BOOLEAN | |
| slr_abort_reason | VARCHAR(500) | Mandatory bila ABORTED |
| slr_submitted_at | TIMESTAMP | Nullable |
| slr_approved_at | TIMESTAMP | Nullable |
| slr_released_at | TIMESTAMP | Nullable |
| slr_hr_acted_at | TIMESTAMP | Nullable |
| slr_security_confirmed_at | TIMESTAMP | Nullable |
| slr_created_by | VARCHAR FK | Drafter |
| slr_created_at | TIMESTAMP | |
| slr_updated_at | TIMESTAMP | |

**`short_leave_approval` (sla_)**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| sla_id | BIGINT PK | |
| sla_slr_id | BIGINT FK | |
| sla_level | VARCHAR(20) | L1_SUBMIT / L2_APPROVE / HOD_RELEASE |
| sla_approver_id | VARCHAR FK | |
| sla_action | VARCHAR(10) | SUBMIT / APPROVE / RELEASE / REJECT / SKIP |
| sla_skip_reason | VARCHAR(30) | SKIP_SELF / SKIP_SUBORDINATE / SKIP_NO_APPROVER |
| sla_note | VARCHAR(500) | Mandatory bila REJECT |
| sla_acted_at | TIMESTAMP | |

**`short_leave_hr_verification` (slh_)**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| slh_id | BIGINT PK | |
| slh_slr_id | BIGINT FK | One-to-one |
| slh_verifier_id | VARCHAR FK | HR yang verify/reject |
| slh_action | VARCHAR(10) | VERIFY / REJECT |
| slh_note | VARCHAR(500) | Mandatory bila REJECT |
| slh_acted_at | TIMESTAMP | |

**`short_leave_security` (sls_)**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| sls_id | BIGINT PK | |
| sls_slr_id | BIGINT FK | One-to-one |
| sls_site_id | VARCHAR FK | Site yang confirm |
| sls_confirmer_id | VARCHAR FK | Security yang input |
| sls_actual_start_time | TIME | Nullable — jam masuk aktual (LATE_IN, PERMIT, ON_DUTY) |
| sls_actual_end_time | TIME | Nullable — jam keluar aktual (EARLY_OUT, PERMIT, ON_DUTY) |
| sls_duration_diff_minutes | INT | Computed: selisih durasi aktual vs diajukan |
| sls_note | VARCHAR(500) | Opsional |
| sls_confirmed_at | TIMESTAMP | |

**`short_leave_attendance_impact` (sli_)**
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| sli_id | BIGINT PK | |
| sli_slr_id | BIGINT FK | |
| sli_attendance_id | BIGINT FK | |
| sli_marker | VARCHAR(20) | LATE_IN / EARLY_OUT / PERMIT / ON_DUTY |
| sli_affected_minutes | INT | |
| sli_reduce_working_hour | BOOLEAN | False untuk ON_DUTY |
| sli_created_at | TIMESTAMP | |

### 7.2 Indeks

- `short_leave_request`: index `(slr_employee_id, slr_leave_date)`, `(slr_approval_status)`, `(slr_hr_status)`, `(slr_security_status)`, `(slr_site_id)`.
- `short_leave_approval`: index `(sla_slr_id)`, `(sla_approver_id, sla_acted_at)`.
- `short_leave_security`: index `(sls_slr_id)`, `(sls_site_id, sls_confirmed_at)`.

### 7.3 Query Penting

```sql
-- Transaksi belum HR Verified meski sudah Released
SELECT * FROM short_leave_request
WHERE slr_approval_status = 'RELEASED'
AND slr_hr_status = 'PENDING';

-- Discrepancy jam aktual Security > 15 menit
SELECT slr.slr_doc_no, slr.slr_type,
       slr.slr_start_time, slr.slr_end_time,
       sls.sls_actual_start_time, sls.sls_actual_end_time,
       sls.sls_duration_diff_minutes
FROM short_leave_request slr
JOIN short_leave_security sls ON sls.sls_slr_id = slr.slr_id
WHERE ABS(sls.sls_duration_diff_minutes) > 15;

-- Transaksi HR Rejected (untuk audit)
SELECT slr.*, slh.slh_note, slh.slh_acted_at
FROM short_leave_request slr
JOIN short_leave_hr_verification slh ON slh.slh_slr_id = slr.slr_id
WHERE slr.slr_hr_status = 'REJECTED';
```

---

## 8. UI/UX

### 8.1 Halaman & Komponen

1. **Short Leave List** — list view dengan filter, status composite badge (3 status sekaligus).
2. **New Request Form** — drafter entry, hanya Save Draft.
3. **Request Detail** — timeline dual-track: approval chain di kiri, HR+Security track di kanan.
4. **L1 Submit Inbox** — DRAFT pending submission.
5. **L2 Approval Inbox** — SUBMITTED pending approval.
6. **HOD Release Inbox** — APPROVED pending release.
7. **HR Verification Inbox** — slr_hr_status = PENDING.
8. **Security Confirmation Inbox** — slr_security_status = PENDING, per site.
9. **Reports & Analytics**.

### 8.2 Status Badge di List View

Setiap baris di list view menampilkan 3 badge kecil:

```
[Released] [HR: Verified] [Sec: Pending]
[Approved] [HR: Pending]  [-]
[Rejected] [-]            [-]
```

### 8.3 Status Color Coding

| Status | Warna |
|--------|-------|
| DRAFT | Gray |
| SUBMITTED | Blue (light) |
| APPROVED | Blue |
| RELEASED | Teal |
| HR PENDING | Purple (light) |
| HR VERIFIED | Purple |
| HR REJECTED | Red (muted) |
| SEC PENDING | Orange (light) |
| SEC CONFIRMED | Orange |
| REJECTED (approval) | Red |
| ABORTED | Amber |

### 8.4 Timeline Display di Request Detail

Detail page menampilkan dua kolom timeline:

**Kiri — Approval Track:**
- DRAFT — Created by Siti Rahayu (HR Admin), 2026-05-22 08:30
- SUBMITTED — Submitted by Bambang (L1 Spv), 2026-05-22 09:05
- APPROVED — Approved by Catur (L2 Mgr), 2026-05-22 09:30
- RELEASED — Released by Indra (HOD), 2026-05-22 10:00 ✓ Marker applied

**Kanan — Verification Track:**
- HR PENDING since 2026-05-22 09:05
- HR VERIFIED — Anita (HR), 2026-05-22 10:15 · Note: attendance match
- SEC PENDING since 2026-05-22 10:15
- SEC CONFIRMED — Security Gate A, 2026-05-22 10:47 · Keluar 10:02 aktual

---

## 9. Edge Cases & Handling

| # | Edge Case | Handling |
|---|-----------|----------|
| 1 | HOD release tapi HR belum verify | Diizinkan. Attendance marker apply. HR track tetap pending. |
| 2 | HR reject setelah HOD release | Diizinkan. HR rejection bersifat catatan. Marker tidak di-revoke. |
| 3 | Security confirm tapi approval chain masih SUBMITTED | Tidak bisa — Security baru bisa setelah HR VERIFIED, dan HR baru bisa setelah SUBMITTED. Tetapi tidak ada syarat harus RELEASED. |
| 4 | Security confirm jam aktual = 00:00 atau tidak masuk akal | Validasi range: jam aktual harus dalam range waktu yang wajar ± 4 jam dari jam yang diajukan. |
| 5 | Karyawan tidak hadir sama sekali tapi ada short leave | Marker di-flag "abnormal" bila tidak ada attendance record. |
| 6 | Overlapping short leave di hari sama | Validasi saat save: block bila range jam overlap. |
| 7 | Short leave di hari libur/off | Block di validasi. |
| 8 | Backdate setelah payroll closed | Block, arahkan ke adjustment manual HR. |
| 9 | HR reject lalu approval chain RELEASED | Marker tetap apply. HR rejection tercatat untuk audit. Flag di reporting. |
| 10 | Drafter abort saat HR sudah VERIFIED | Abort diizinkan (sebelum RELEASED). HR track di-reset (slr_hr_status = NULL). Security track di-reset. |
| 11 | Hard delete DRAFT yang sudah ada HR activity | Tidak mungkin — HR track baru aktif saat SUBMITTED, bukan DRAFT. Hard delete DRAFT aman. |
| 12 | Multiple short leave per hari | Diizinkan sebagai request terpisah. Validasi overlap jam. |
| 13 | Security berbeda site mengkonfirmasi | Block — Security hanya bisa confirm untuk site yang sesuai slr_site_id. |
| 14 | HOD release tapi Security belum punya inbox | Security tetap punya inbox (slr_security_status masih PENDING bila HR sudah VERIFIED). |
| 15 | Top position (no superior) self-entry oleh HR Admin | Smart workflow handle, auto-RELEASED dengan peer notification. |

---

## 10. Acceptance Criteria

### 10.1 Functional
- Drafter tidak punya button Submit. Hanya Save Draft.
- Approval chain berjalan sesuai Smart Workflow dengan skip yang tercatat.
- Attendance marker apply saat HOD Release, tidak menunggu HR/Security.
- HR track aktif sejak SUBMITTED, HR dapat Verify atau Reject tanpa memblokir approval.
- Security track aktif setelah HR VERIFIED, Security input jam aktual sesuai tipe.
- Security per site — inbox terpisah per site.
- HR Reject tidak me-revoke attendance marker yang sudah ter-apply.
- Drafter abort di SUBMITTED/APPROVED mereset semua track (HR/Security).

### 10.2 Non-Functional
- Form entry response < 1 detik.
- Approval list dapat handle 500+ pending tanpa pagination issue.
- Audit trail lengkap untuk semua transisi status di 3 kolom status.
- Security inbox filtered per site, tidak ada cross-site visibility.

---

## 11. Dependencies

- Modul Employee Master (data karyawan, posisi, supervisor, site).
- Modul Working Schedule (shift assignment).
- Modul Attendance (tap-in/out data, attendance record).
- Modul Leave (validasi konflik full day leave).
- Approval Engine (Smart Workflow generic, future phase).
- Notification service.
- File storage service.

---

## 12. Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| HR reject tapi marker sudah apply, karyawan tidak notice | Medium | Medium | Notifikasi ke Manager saat HR reject, flag di reporting |
| Security salah input jam | Medium | Low | Discrepancy report, Security bisa edit sebelum shift berakhir |
| Security inbox tidak diperiksa (jam tidak tercatat) | Medium | Low | Notifikasi escalation bila pending > N jam |
| Confusion 3 status sekaligus di UI | Medium | Medium | Status composite badge yang clear di list view |
| Drafter delete DRAFT tidak sengaja | Low | Low | Konfirmasi dialog, tidak ada undo |

---

## Appendix A — Glossary

| Istilah | Definisi |
|---------|----------|
| Drafter | HR/Admin yang entry pengajuan. Tidak punya hak Submit. |
| Dual-Track | Dua track paralel: Approval Track (Dept) dan Verification Track (HR+Security). |
| Attendance Marker | Record di tabel attendance_impact yang menandai short leave RELEASED. |
| slr_approval_status | Status track approval departemen di tabel utama. |
| slr_hr_status | Status track HR di tabel utama. |
| slr_security_status | Status track Security di tabel utama. |
| Status Composite | 3 kolom status di tabel utama yang merepresentasikan kedua track. |
| Discrepancy | Selisih antara jam yang diajukan vs jam aktual dari Security. |
| Site | Lokasi/plant tempat karyawan bertugas. Security inbox dipisah per site. |
| Smart Workflow | Pattern approval dengan auto-skip berdasarkan kondisi drafter-approver. |

---

## Appendix B — Test Scenarios

| # | Scenario | Expected |
|---|----------|----------|
| 1 | Drafter save DRAFT untuk karyawan PERMIT | DRAFT created, slr_hr_status NULL, slr_security_status NULL |
| 2 | L1 Submit | slr_approval_status SUBMITTED, slr_hr_status PENDING, notif ke L2 dan HR |
| 3 | HR Verify sebelum L2 Approve | Diizinkan, slr_hr_status VERIFIED, slr_security_status PENDING |
| 4 | Security Confirm sebelum HOD Release | Diizinkan, slr_security_status CONFIRMED |
| 5 | L2 Approve setelah Security sudah Confirm | Diizinkan, slr_approval_status APPROVED |
| 6 | HOD Release | slr_approval_status RELEASED, attendance marker apply |
| 7 | HR Reject saat slr_approval_status APPROVED | slr_hr_status REJECTED, slr_approval_status tetap APPROVED, notif ke Manager |
| 8 | HOD Release setelah HR Reject | Marker tetap apply. HR rejection tercatat. |
| 9 | L1 Reject DRAFT | slr_approval_status REJECTED, HR/Security track di-reset |
| 10 | Drafter abort SUBMITTED | slr_approval_status ABORTED, slr_hr_status NULL (reset) |
| 11 | Drafter abort APPROVED (HR sudah VERIFIED) | slr_approval_status ABORTED, slr_hr_status NULL, slr_security_status NULL |
| 12 | Security konfirmasi PERMIT: jam keluar 13:05 (diajukan 13:00) | sls_actual_end_time 13:05, sls_duration_diff_minutes +5 |
| 13 | Security dari site berbeda coba confirm | Block: site tidak match |
| 14 | Drafter hard delete DRAFT | Record dihapus permanen, tidak ada audit |
| 15 | Top position (no superior), HR Admin entry | Smart skip all levels, auto-RELEASED, peer notification |
| 16 | Overlap dengan Leave full day | Block di validasi saat save |
| 17 | Short leave di hari off | Block di validasi |
| 18 | Backdate setelah payroll closed | Block di validasi |
| 19 | LATE_IN: jam mulai bukan jam mulai shift | Block di validasi |
| 20 | Security confirm LATE_IN: input jam masuk aktual 08:17 (diajukan 08:00) | sls_actual_start_time 08:17, diff +17 menit |

---

## Document Control

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | 2026-05-21 | IT Lead | Initial draft |
| 2.0 | 2026-05-21 | IT Lead | Smart Workflow, ERD dengan prefix |
| 3.0 | 2026-05-21 | IT Lead | Major revision. Align dengan pattern HRIS standar (DRAFT/SUBMITTED/APPROVED/RELEASED). Drafter HR/Admin, tidak ada self-service karyawan. Dual-Track Workflow: Approval Track (Dept) paralel dengan Verification Track (HR + Security). Status Composite 3 kolom. Security per site input jam aktual sesuai tipe. Attendance marker apply saat RELEASED tanpa menunggu HR/Security. |
