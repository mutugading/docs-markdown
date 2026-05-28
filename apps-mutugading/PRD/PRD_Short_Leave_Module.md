# Product Requirements Document
## Short Leave Module — HRIS Add-on

| | |
|---|---|
| **Document Version** | 1.0 (Draft) |
| **Date** | May 28, 2026 |
| **Module Type** | Add-on to existing HRIS |
| **Status** | For Review |

---

## 1. Overview

### 1.1 Background
HRIS existing sudah memiliki modul Employee Master, Working Schedule, Actual Attendance, Leave, Overtime, dan Change Shift, masing-masing dengan approval flow standar (Draft → Submit → Approve → Release). Saat ini belum ada mekanisme untuk meng-handle absence partial dalam satu hari kerja — yaitu situasi di mana karyawan tidak hadir penuh tapi juga tidak full-day leave.

### 1.2 Problem Statement
Karyawan kerap memerlukan ijin partial dalam satu hari kerja (datang terlambat, pulang awal, ijin di tengah jam kerja, atau on-duty di luar lokasi). Saat ini situasi ini tidak ter-record secara struktur, sehingga:
- Sulit menelusuri alasan kekurangan jam kerja di attendance
- Manager tidak punya tool formal untuk meng-approve permission jenis ini
- HR dan payroll tidak punya data terstandar untuk decision dan analytics

### 1.3 Objective
Menyediakan modul Short Leave sebagai add-on HRIS untuk:
- Menstandarkan request, approval, dan record absence partial dalam satu hari
- Menyediakan marker informatif di attendance tanpa memodifikasi data IN/OUT actual
- Mendukung analytics dan reporting bagi HR dan manager

---

## 2. Scope

### 2.1 In Scope
- Master Short Leave Type
- Create, submit, approve, release, reject, abort, delete request
- Validasi konflik dengan modul lain (Leave, Overtime, Change Shift, Attendance)
- Security verification untuk type mid-shift (Ijin Tengah Jam, On-Duty)
- Linking ke attendance record saat Release (add-on marker, read-only)
- Attachment upload sejak Draft
- Reporting untuk Employee, Manager, dan HR

### 2.2 Out of Scope
- Approval level configuration — menggunakan existing approval engine
- Notification engine — menggunakan existing notification mechanism
- Payroll calculation dampak jam hilang — consumer responsibility (payroll module)
- Quota management per employee per type — defer ke future version
- Bulk approve — defer ke future version
- Auto re-sync ke attendance jika attendance ter-update setelah release

---

## 3. Definitions

| Term | Definition |
|---|---|
| Short Leave | Ijin tidak hadir partial dalam satu hari kerja (kurang dari full shift) |
| Hours Lost | Durasi declared yang merepresentasikan jam hilang dari short leave (kecuali type on-duty) |
| Release | Tahap akhir lifecycle yang membuat short leave record menjadi final dan ter-link ke attendance |
| Drafter | User yang membuat request (biasanya employee yang bersangkutan) |
| Add-on Marker | Catatan supplementary yang menempel ke attendance tanpa memodifikasi data IN/OUT actual |
| Mid-Shift Type | Type short leave yang tidak melibatkan clock IN/OUT — yaitu Ijin Tengah Jam dan On-Duty Tengah Jam |

---

## 4. User Roles

| Role | Responsibility |
|---|---|
| Employee / Drafter | Membuat dan submit request; abort sebelum release |
| Approver | Approve atau reject submitted request (multi-level, sesuai existing setup) |
| Releaser | Release atau reject approved request (sesuai existing setup) |
| Security | Update `actual_out_mid` dan `actual_in_back` untuk type mid-shift |
| HR | View reporting, audit trail, rekap |

Konfigurasi approver dan releaser **menggunakan existing approval setup**, tidak di-redefine di modul ini.

---

## 5. Functional Requirements

### 5.1 Short Leave Type Master

Sistem menyediakan master Short Leave Type dengan attribute berikut:

| Attribute | Description |
|---|---|
| `type_code` | Unique identifier |
| `type_name` | Display name |
| `affects_clock_in` | Boolean — apakah type ini terkait dengan jam IN |
| `affects_clock_out` | Boolean — apakah type ini terkait dengan jam OUT |
| `is_mid_shift` | Boolean — true jika tidak melibatkan IN/OUT |
| `is_work_activity` | Boolean — true jika durasi dianggap jam kerja (mis. on-duty) |
| `attachment_required` | Boolean — apakah attachment wajib |
| `requires_security_verification` | Boolean — apakah perlu validasi security |
| `active` | Boolean — flag aktif/non-aktif |

**Default Types yang harus di-seed:**

| Type | affects_in | affects_out | is_mid_shift | is_work_activity | requires_security_verification |
|---|---|---|---|---|---|
| Datang Terlambat | ✓ | – | – | No | – |
| Pulang Awal | – | ✓ | – | No | – |
| Ijin Tengah Jam | – | – | ✓ | No | ✓ |
| On-Duty Tengah Jam | – | – | ✓ | Yes | ✓ |

HR dapat menambahkan type baru di master selama kombinasi flag-nya valid.

### 5.2 Request Fields

| Field | Required | Editable | Notes |
|---|---|---|---|
| Employee | ✓ | Saat Draft | Auto-populate untuk self-service |
| Short Leave Type | ✓ | Saat Draft | Dari master |
| Leave Date | ✓ | Saat Draft | Harus ≥ tanggal hari ini saat creation |
| Start Time | ✓ | Saat Draft | Format HH:mm |
| End Time | ✓ | Saat Draft | Harus > Start Time |
| Duration (Hours Lost) | Auto | – | Computed = End Time − Start Time, dalam menit |
| Reason | ✓ | Saat Draft | Free text |
| Attachment | Conditional | Saat Draft | Max 3 files; PDF/JPG/PNG; max 2MB per file. Mandatory jika type punya `attachment_required = true` |
| Actual Out (Mid) | – | Security only | Diisi oleh security untuk type mid-shift |
| Actual In (Back) | – | Security only | Diisi oleh security untuk type mid-shift |

### 5.3 State Machine

#### 5.3.1 States
- **Draft** — record baru dibuat, belum di-submit
- **Submitted** — sudah di-submit, menunggu approval
- **Approved** — sudah di-approve, menunggu release
- **Released** — final, ter-link ke attendance, immutable
- **Rejected** — final, tidak bisa di-resubmit
- **Aborted** — dibatalkan oleh drafter, final

#### 5.3.2 Allowed Transitions

| From State | Action | By Role   | To State |
|---|---|-----------|---|
| Draft | Submit | Submitter | Submitted |
| Draft | Delete | Drafter   | (record dihapus, hard delete) |
| Draft | Abort | Drafter   | Aborted |
| Submitted | Approve | Approver  | Approved |
| Submitted | Reject | Approver  | Rejected |
| Submitted | Abort | Drafter   | Aborted |
| Submitted | Update actual time | Security  | Submitted (data update only) |
| Approved | Release | Releaser  | Released |
| Approved | Reject | Releaser  | Rejected |
| Approved | Abort | Drafter   | Aborted |
| Approved | Update actual time | Security  | Approved (data update only) |
| Released | — | —         | — (immutable) |
| Rejected | — | —         | — (final) |
| Aborted | — | —         | — (final) |

#### 5.3.3 State Machine Diagram

```
         ┌──────────────────────────────────────────┐
         │                                          │
         │     [delete]                             │
         │        ▼                                 │
         │      (gone)                              │
         │                                          │
    ┌────┴────┐    submit    ┌───────────┐  approve  ┌──────────┐  release  ┌──────────┐
    │  Draft  ├─────────────►│ Submitted ├──────────►│ Approved ├──────────►│ Released │
    └────┬────┘              └─────┬─────┘           └─────┬────┘           └──────────┘
         │                         │                       │                  (immutable)
         │ abort                   │ reject                │ reject
         │                         │                       │
         │                         ▼                       ▼
         │                    ┌──────────┐           ┌──────────┐
         │                    │ Rejected │           │ Rejected │
         │                    └──────────┘           └──────────┘
         │
         │                    ┌──────────┐           ┌──────────┐
         └───────────────────►│ Aborted  │◄──────────┤ Aborted  │
                              └──────────┘           └──────────┘
                                   ▲
                                   │ abort (from Submitted or Approved)
```

### 5.4 Creation Rules

- `leave_date` harus ≥ tanggal hari ini (server timezone)
- Backdate creation **tidak diperbolehkan**
- Same-day creation diperbolehkan
- Drafter bisa save sebagai Draft tanpa langsung submit
- Attachment bisa ditambahkan sejak Draft
- Multiple short leave dalam satu hari diperbolehkan selama slot waktunya tidak overlap

### 5.5 Submit Validation (Conflict Check)

Saat Submit, sistem menjalankan validasi konflik. Validasi yang sama dijalankan ulang saat **Approve** dan **Release**.

#### 5.5.1 Hard Reject (block transition)
- Ada full-day Leave (Approved/Released) di tanggal yang sama untuk employee tersebut
- Ada short leave lain (Submitted/Approved/Released) dengan slot waktu overlap
- Ada Overtime (Approved/Released) dengan slot waktu overlap
- Tanggal adalah day off atau public holiday per existing working schedule (kecuali ada policy override)

#### 5.5.2 Soft Warning (boleh lanjut, inform user)
- Ada Change Shift yang masih pending untuk tanggal yang sama
- Approved Change Shift menggeser jadwal → short leave akan di-evaluate terhadap shift baru

### 5.6 Approval & Rejection

- **Approval flow dan approver chain mengikuti existing setup**
- Reject di level apa saja bersifat **final** — record tidak bisa di-resubmit, drafter harus create record baru
- Reason wajib di-isi saat Reject
- Re-validasi konflik dijalankan saat Approve

### 5.7 Security Verification

Untuk type dengan `requires_security_verification = true` (Ijin Tengah Jam, On-Duty Tengah Jam):

- Security role dapat update field `actual_out_mid` dan `actual_in_back` selama record berada di state **Submitted** atau **Approved**
- Update ini **tidak mengubah status** record (tetap di state saat ini)
- Field ini bersifat **informational** dan akan ditampilkan di attendance link saat Release
- Security verification **tidak gating** — release tetap boleh dilakukan walau security belum update
- Setelah Released, field ini juga frozen (tidak bisa di-update lagi)

### 5.8 Release Behavior

Saat transisi Approved → Released:

#### 5.8.1 Auto-Compare Validation (soft warning, tidak block)

| Type | Validasi |
|---|---|
| Datang Terlambat | Compare `short_leave.end_time` dengan `attendance.actual_in` di `leave_date`. Jika `actual_in > end_time`, set warning flag dengan note "actual in melebihi declared end" |
| Pulang Awal | Compare `short_leave.start_time` dengan `attendance.actual_out`. Jika `actual_out < start_time`, set warning flag dengan note "actual out lebih cepat dari declared start" |
| Ijin Tengah Jam | Tidak ada auto-compare |
| On-Duty Tengah Jam | Tidak ada auto-compare |

Warning di-store di field `release_warning_flag` dan `release_warning_note` di record short leave, dan ter-display di UI untuk audit. Releaser tetap bisa proceed.

#### 5.8.2 Create Attendance Link
- Insert ke `attendance_short_leave_link` (composite reference)
- Attendance record sendiri **tidak dimodifikasi**
- Marker ter-display di UI attendance (via JOIN ke link table)

#### 5.8.3 Data Snapshot
- Semua field di-freeze
- Tidak ada auto re-sync jika attendance ter-update di kemudian hari

#### 5.8.4 Future-Dated Release
Jika `leave_date` masih di masa depan saat Release, link belum dibuat secara fisik. Link akan ter-bind saat attendance record untuk `leave_date` pertama kali ter-generate. (Implementasi: bisa pakai trigger di attendance generation, atau pakai virtual join via query.)

### 5.9 Reject

- Bisa terjadi di state **Submitted** (oleh Approver) atau **Approved** (oleh Releaser)
- Bersifat **final**
- Tidak bisa di-resubmit dari record yang sama
- Reason wajib di-isi
- Audit log mencatat: rejected_by, rejected_at, rejected_reason

### 5.10 Abort

- Dilakukan oleh **Drafter**
- Allowed dari state **Draft, Submitted, atau Approved**
- **Tidak boleh** dari state Released
- Bersifat **final**
- Audit log mencatat: aborted_at

### 5.11 Delete

- Hanya allowed dari state **Draft**
- Hanya oleh Drafter
- Hard delete (record tidak masuk audit history)

---

## 6. Integration with Existing Modules

### 6.1 Actual Attendance Module
- **Model: Add-on, bukan modifier**
- Short leave **TIDAK** mengubah field `actual_in`, `actual_out`, atau `total_work_hours` di attendance
- Link via tabel `attendance_short_leave_link` yang di-populate saat Release
- UI attendance dapat JOIN ke link table untuk menampilkan marker "✱ Short Leave: [type] [durasi]"
- Consumer modules (payroll, report) yang aggregate "hours lost" dengan menjumlahkan short leave records yang `is_work_activity = false`

### 6.2 Leave Module
- Validasi konflik di Submit/Approve/Release
- Short leave tidak boleh overlap dengan full-day leave yang sudah Approved/Released

### 6.3 Overtime Module
- Validasi konflik di Submit/Approve/Release
- Short leave tidak boleh overlap dengan overtime yang sudah Approved/Released

### 6.4 Change Shift Module
- Pending change shift → soft warning
- Approved change shift → short leave di-evaluate terhadap shift baru (jadwal kerja yang berlaku)

### 6.5 Working Schedule
- Day off dan public holiday di-check saat validasi
- Short leave umumnya tidak diperbolehkan di hari off; jika ada exception, perlu policy override

### 6.6 Approval Engine
- **Menggunakan existing approval setup** (multi-level, role mapping, escalation)
- Tidak ada konfigurasi tambahan di modul ini

### 6.7 Notification Engine
- **Menggunakan existing notification mechanism**
- Event yang di-trigger: Submit, Approve, Reject, Release, Abort
- Channel notification (email/in-app/push) mengikuti existing setup

---

## 7. Data Model (Conceptual)

### 7.1 `short_leave_type` (Master)
| Field                          | Type | Notes |
|--------------------------------|---|---|
| slt_id                         | PK | |
| slt_type_code                  | string, unique | |
| slt_type_name                  | string | |
| slt_affects_clock_in           | boolean | |
| slt_affects_clock_out          | boolean | |
| slt_is_mid_shift               | boolean | |
| slt_is_work_activity               | boolean | |
| slt_attachment_required            | boolean | |
| slt_requires_security_verification | boolean | |
| slt_active                         | boolean | |

### 7.2 `short_leave_request` (Header)
| Field                | Type | Notes |
|----------------------|---|---|
| slr_id               | PK | |
| slr_employee_id          | FK → employee | |
| slr_type_id              | FK → short_leave_type | |
| slr_status               | enum | Draft / Submitted / Approved / Released / Rejected / Aborted |
| slr_leave_date           | date | |
| slr_start_time           | time | |
| slr_end_time             | time | |
| slr_duration_minutes     | int | Computed |
| slr_reason               | text | |
| slr_actual_out_mid       | time, nullable | Diisi oleh security |
| slr_actual_in_back       | time, nullable | Diisi oleh security |
| slr_release_warning_flag | boolean, default false | |
| slr_release_warning_note | text, nullable | |
| slr_rejected_reason      | text, nullable | |
| slr_created_by           | FK → user | |
| slr_created_at           | timestamp | |
| slr_submitted_at         | timestamp, nullable | |
| slr_approved_at          | timestamp, nullable | |
| slr_released_at          | timestamp, nullable | |
| slr_rejected_at          | timestamp, nullable | |
| slr_aborted_at           | timestamp, nullable | |

### 7.3 `short_leave_attachment`
| Field          | Type | Notes |
|----------------|---|---|
| sla_id         | PK | |
| sla_short_leave_id | FK → short_leave_request | |
| sla_file_name      | string | |
| sla_file_path      | string | |
| sla_file_size      | int | |
| sla_uploaded_by    | FK → user | |
| sla_uploaded_at    | timestamp | |

### 7.4 `attendance_short_leave_link`
| Field            | Type | Notes |
|------------------|---|---|
| sll_id           | PK | |
| sll_attendance_id    | FK → attendance | |
| sll_short_leave_id   | FK → short_leave_request | |
| sll_type_id          | FK → short_leave_type | Denormalized for query speed |
| sll_duration_minutes | int | Denormalized snapshot |
| sll_is_work_activity | boolean | Denormalized for query speed |
| sll_linked_at        | timestamp | |

### 7.5 `short_leave_audit_log`
| Field          | Type | Notes |
|----------------|---|---|
| slal_id        | PK | |
| slal_short_leave_id | FK → short_leave_request | |
| slal_event          | enum | Created / Submitted / Approved / Rejected / Released / Aborted / Security Updated |
| slal_from_state     | enum, nullable | |
| slal_to_state       | enum, nullable | |
| slal_actor_user_id  | FK → user | |
| slal_actor_role     | string | |
| slal_event_at       | timestamp | |
| slal_note           | text, nullable | |

---

## 8. Reporting Requirements

### 8.1 Personal View (Employee)
- History short leave milik sendiri dengan filter periode dan status
- Detail tiap record (termasuk attachment, audit trail)
- Sortable by leave_date

### 8.2 Manager View
- List short leave pending approval untuk team
- History approved/released team dengan filter periode
- Filter per employee, per type, per status
- Counter: total short leave per employee per periode

### 8.3 HR View
- Rekap per employee per periode:
  - Total durasi short leave (split by work-activity vs hours-lost)
  - Count per type
  - Breakdown approved vs rejected vs aborted
- Rekap per department / unit
- Drill-down ke detail record
- Export ke spreadsheet (CSV/XLSX)

---

## 9. Business Rules Summary

| ID | Rule                                                                                 |
|---|--------------------------------------------------------------------------------------|
| BR-01 | `slr_leave_date` saat creation harus ≥ tanggal hari ini (server timezone)            |
| BR-02 | Approve dan Release boleh backdate (selama attendance period belum closed)           |
| BR-03 | Released record bersifat immutable — tidak bisa di-abort, reject, atau delete        |
| BR-04 | Reject bersifat final di level manapun; tidak bisa di-resubmit dari record yang sama |
| BR-05 | Edit setelah Submit tidak diperbolehkan; harus Abort + Create baru                   |
| BR-06 | Delete hanya allowed di state Draft (hard delete)                                    |
| BR-07 | Short leave tidak modify data attendance; hanya add-on link                          |
| BR-08 | Auto-compare validation di Release bersifat soft warning, tidak block                |
| BR-09 | Security verification tidak gating release                                           |
| BR-10 | Tidak ada auto re-sync setelah Release jika attendance ter-update                    |
| BR-11 | Attachment wajib jika `type.slt_attachment_required = true`                          |
| BR-12 | Max 3 attachments per request; PDF/JPG/PNG; ≤ 2 MB per file                          |
| BR-13 | Multiple short leave dalam satu hari diperbolehkan selama slot tidak overlap         |
| BR-14 | Approval level configuration menggunakan existing setup                              |
| BR-15 | Notification mechanism menggunakan existing setup                                    |

---

## 10. Non-Functional Requirements

| Category | Requirement                                                                                                                             |
|---|-----------------------------------------------------------------------------------------------------------------------------------------|
| Performance | Response time ≤ 2 detik untuk submit/approve/release pada record tunggal                                                                |
| Audit | Semua perubahan state ter-log dengan timestamp, actor user, dan actor role                                                              |
| Security | Field `slr_actual_out_mid` & `slr_actual_in_back` hanya editable oleh role Security; release hanya oleh role Releaser sesuai existing setup |
| Data Retention | Mengikuti existing HRIS retention policy                                                                                                |
| Localization | UI mendukung bahasa yang sama dengan existing HRIS                                                                                      |
| Compatibility | Berintegrasi dengan modul existing tanpa breaking change                                                                                |

---

## 11. Out of Scope (Future Considerations)

1. **Quota management** per employee per type (mis. max 10 jam Ijin Tengah Jam per bulan)
2. **Bulk approve** untuk approver
3. **Mobile-specific UI optimization** di luar UI existing
4. **Direct integration dengan payroll calculation** untuk auto-deduction
5. **Auto re-sync attendance changes** ke short leave record yang sudah Released
6. **Multi-day short leave** (jika ada use case)
7. **Recurring short leave** (mis. tiap Senin terlambat 30 menit karena alasan tetap)

---

## 12. Open Items / Assumptions

| # | Item | Current Assumption | Action |
|---|---|---|---|
| OI-01 | Backdate boundary untuk Approve/Release | Sampai attendance period closing | Konfirmasi mechanism closing existing |
| OI-02 | Timezone untuk validasi "today" | Server timezone, single timezone | Konfirmasi jika ada employee multi-region |
| OI-03 | Release setelah attendance closing | Block + manual adjustment di luar modul | Align ke HR / Finance policy |
| OI-04 | Self-service vs admin create | Employee bisa create untuk diri sendiri; HR/admin bisa create on-behalf | Konfirmasi role mapping |
| OI-05 | Default behavior public holiday | Hard reject submit | Konfirmasi jika ada policy override |
| OI-06 | Mid-shift permit overlap dengan istirahat | Durasi dihitung apa adanya (tidak dipotong jam istirahat) | Konfirmasi ke HR |

---

## 13. Acceptance Criteria (High-Level)

Modul dianggap selesai jika:

1. Drafter bisa create, save draft, submit, abort short leave sesuai state machine
2. Approver bisa approve atau reject sesuai existing approval setup
3. Releaser bisa release atau reject; auto-compare warning ter-trigger sesuai aturan
4. Security bisa update actual time untuk type mid-shift
5. Released record ter-link ke attendance dan marker visible di UI attendance
6. Attendance data IN/OUT tidak berubah karena short leave
7. Konflik validation ter-trigger sesuai matrix
8. Audit log mencatat semua transisi state
9. Reporting menampilkan rekap sesuai requirement di section 8
10. Reject dan Abort bersifat final, tidak bisa di-undo

---

## 14. Revision History

| Version | Date | Author | Notes |
|---|---|---|---|
| 1.0 | May 28, 2026 | (TBD — IT Leader) | Initial draft, hasil brainstorming |
