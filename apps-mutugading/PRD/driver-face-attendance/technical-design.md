# Rancangan Teknis — Absensi Wajah Driver

Dokumen pendamping [PRD](PRD.md). Nama berikut adalah usulan implementasi, bukan tabel/class yang sudah dibuat. Dasar inspeksi: repository pada 17 September 2026; DDL database produksi belum diperiksa.

## 1. Peta integrasi kode existing

Semua path relatif terhadap root repository.

| Komponen existing | Integrasi/perubahan yang diperlukan |
|---|---|
| `Modules/Hr/app/Models/MgtHris/Master/Employees/HmEmpData.php` | Identitas autentikasi; simpan sys ID sebagai FK, NIK sebagai snapshot string |
| `Modules/Core/database/migrations/2026_08_10_110000_create_hm_adms_face_table.php` | Hanya marker wajah terdaftar pada mesin; bukan template web |
| `Modules/Core/database/migrations/2026_08_06_100200_create_hm_adms_userpic_table.php` | Foto referensi mesin; tidak dianggap enrollment web sah otomatis |
| `Modules/Hr/app/Models/MgtHris/Master/Attendance/HmAttParsedLogs.php` | Tambah sumber dan referensi driver; raw relation nullable |
| `Modules/Mis/database/migrations/2025_12_11_012845_create_hm_att_parsed_logs_table.php` | Definisi awal raw ID non-null; jangan edit migration ini |
| `Modules/Hr/app/Interfaces/Master/Attendance/ParsedAttLogRepositoryInterface.php` | Tambah operasi idempotent berdasarkan source; pertahankan kontrak raw existing |
| `Modules/Hr/app/Repositories/Master/Attendance/EloquentParsedAttLogRepository.php` | Implementasi source lookup/write, source filter, raw nullable dan scope |
| `Modules/Hr/app/Services/Attendance/ParsingAttLogService.php` | Tetap untuk mesin/manual; writer menambahkan source yang benar pada log baru |
| `Modules/Hr/app/Services/Attendance/ManualAttendanceService.php` | Gunakan klasifikasi manual existing untuk fallback/backfill, jangan menganggap semua histori adalah mesin |
| `Modules/Hr/app/Exports/Attendance/ParsedLogExport.php` | Source label dan null raw-safe; pertahankan NIK sebagai teks |
| `Modules/Mis/app/Services/Transactions/Actual/AttendanceActualizationService.php` | Engine tunggal; adapter recalc memakai hasil yang dapat diverifikasi |
| `Modules/Mis/app/Services/Transactions/Actual/AttendanceSessionResolver.php` | Engine penentuan sesi final; jangan disalin ke service driver |
| `Modules/Hr/app/Jobs/Attendance/PostAttendanceReanalysisJob.php` | Evaluasi scoping/rate/balance dan error handling sebelum reuse |
| `Modules/Mis/app/Services/Transactions/Actual/AttendanceSummaryService.php` | Reuse pembentukan summary via kontrak; tambah overlay status, bukan kalkulasi jam baru |
| `Modules/Mis/app/Livewire/Transactions/Actual/AttendanceSummary.php` | Tetap sebagai entry administratif |
| `Modules/Mis/app/Data/Transactions/Actual/AttendanceSummaryDayCellData.php` | Tambah metadata marker/detail secara backward compatible |
| `Modules/Mis/app/Services/Transactions/Clarif/AttendanceClarificationService.php` | Tangani released clarification tanpa actual; referensi sumber, recalc, anti-duplikasi |
| `Modules/SelfService/routes/web.php` dan `routes/breadcrumbs.php` | Entry personal baru; prefix `dashboard.module-self-service.` |
| `Modules/Core/database/migrations/2026_09_16_090000_sync_sidebar_menus_with_permissions.php` | Pola migrasi sidebar idempotent keyed by code |
| `config/filesystems.php` | Reuse `minio_private`; cek hasil write karena `throw` saat ini false |
| `app/Notifications/BaseNotification.php` | Notification database+broadcast setelah commit |
| `app/Helpers/SysIdHelper.php` | ID dari master sequence existing |
| `app/Helpers/PeriodDateRangeHelper.php` | Default payroll period personal summary |

## 2. Batas modul dan struktur kode

### Kepemilikan

- **Hr** memiliki akses, lokasi, enrollment/profile, capture, punch, review, audit, evidence, dan dispatch. Model/repository/DDL domain tersebut berada di Hr, termasuk tabel transaksi berawalan `ht_`.
- **Mis** tetap memiliki actualization, summary, klarifikasi, dan link klarifikasi ke sumber driver.
- **SelfService** memiliki Livewire/UI personal dan application service tipis. Tidak menulis tabel Hr/Mis langsung.
- **Core** memiliki kontrak actualization bersama bila diperlukan untuk menghindari dependensi Hr→Mis→Hr. Implementasi kontrak berada di Mis.
- **UI** memiliki komponen rendering summary/detail yang benar-benar digunakan lintas modul; tidak ada query bisnis di Blade component.

Tidak membuat modul `FaceId` baru. Jangan meneruskan ketergantungan ke model/service internal modul lain meskipun beberapa kode lama melakukannya. Kontrak antarmodul mengembalikan DTO/scalar, bukan Eloquent Builder yang membocorkan akses query.

### Struktur yang diusulkan

```text
Modules/Hr/app/
  Models/MgtHris/Master/Attendance/       # HmAttFaceAccess, HmAttLocation, HmAttEmpLocation
  Models/MgtHris/Master/Employees/        # HmEmpFaceProfile
  Models/MgtHris/Transactions/Attendance/ # HtAttFaceCapture, Enrollment, Punch, Event, Dispatch
  Interfaces/Attendance/Face/            # kontrak public domain + verifier + evidence
  Interfaces/Master/Attendance/          # repository akses/lokasi/penempatan
  Interfaces/Master/Employees/           # repository profile
  Interfaces/Transactions/Attendance/    # repository capture/enrollment/punch/event/dispatch
  Repositories/...                      # Eloquent{Entity}Repository, mirror interfaces
  Data/Attendance/Face/                  # form/input/output DTO
  Enums/Attendance/Face/                 # backed enums TitleCase
  Services/Attendance/Face/              # bisnis dan adapter verifier
  Livewire/Master/Attendance/            # master lokasi/akses
  Livewire/Attendance/Face/              # enrollment review dan punch validation
  Jobs/Attendance/Face/                  # verification/dispatch/notification/cleanup
  Notifications/Attendance/Face/
  Console/Commands/                      # recover dispatch, expire capture, prune evidence

Modules/SelfService/app/
  Livewire/Attendance/                   # FaceAttendance, FaceEnrollment, MyAttendanceSummary
  Services/Attendance/                   # orchestration personal dan ownership
  Http/Controllers/Attendance/           # capture upload/status dan evidence read tipis

Modules/Mis/app/
  Interfaces/Transactions/Actual/        # PersonalAttendanceSummaryInterface
  Services/Transactions/Actual/          # implementation adapter actualization/summary
  Models/MgtHris/Transactions/Clarif/     # HtAttFaceClarifLink
  Interfaces/Transactions/Clarif/        # repository link
  Repositories/Transactions/Clarif/

Modules/Core/app/Interfaces/Attendance/  # AttendanceRecalculationInterface, AttendancePeriodPolicyInterface
Modules/Core/app/Data/Attendance/        # DTO recalc/period bersama
services/face-verification/             # rencana service Python, belum diimplementasikan
```

Binding repository pada `Hr/Providers/RepositoryServiceProvider`; provider application/adapter sesuai modul. Jika provider deferrable, update `provides()`. Tambahkan provider SelfService hanya bila ada binding baru yang membutuhkannya. Deklarasikan dependensi baru di `module.json`: SelfService sudah memerlukan Hr/Mis/Core/UI; Hr perlu deklarasi Core/UI untuk kontrak/komponen yang dipakai; Mis perlu Core selain Hr/UI. Hindari siklus dengan tidak menambah Mis sebagai dependency Hr.

### Aturan implementasi

- `Livewire → Service → Repository → Model`. Service inject interface, Livewire inject lewat `boot()`.
- Semua query dan transaksi data baru melalui repository/unit-of-work. Gunakan connection model yang benar, misalnya `$model->getConnection()->transaction(...)`; jangan menambahkan `DB::` facade pada kode baru.
- `env()` hanya pada config; migration disable menggunakan config bridge baru bila belum ada, tetap mendukung `DISABLE_MIGRATIONS` seperti migration existing.
- PHP parameter/return type eksplisit, enum TitleCase dengan `label()`, `badgeVariant()`, `options()`; DTO form memakai Spatie Data/Wireable, DTO boundary readonly.
- Query memakai eager loading, pagination, filter searchable dan scope. Data biometrik tidak termasuk searchable/logAttributes.
- UI module lebih dahulu, Flux Pro berikutnya; ES modules/Alpine, tanpa jQuery atau SPA navigation.

## 3. State machine

| Enum baru | Nilai yang diusulkan | Catatan |
|---|---|---|
| `FaceCaptureStatusEnum` | Issued=0, Uploaded=1, Verifying=2, Passed=3, Failed=4, Error=5, Expired=6, Cancelled=7 | Error teknis tidak berarti wajah palsu |
| `FaceEnrollmentStatusEnum` | PendingReview=0, Approved=1, Rejected=2, Superseded=3 | Profile hanya aktif setelah Approved |
| `FaceProfileStatusEnum` | Active=1, Superseded=2, Revoked=3 | Pointer profile aktif di master akses |
| `FacePunchReviewStatusEnum` | Pending=0, Approved=1, Rejected=2 | Tidak mengikuti ladder MIS; UI memakai label Validate |
| `FaceDispatchStatusEnum` | Pending=0, Processing=1, Completed=2, Retryable=3, Blocked=4 | Tahap pekerjaan disimpan terpisah |
| `FaceDispatchStageEnum` | Parsed, Actual, PostActual, Notify | String stabil untuk durable steps |
| `AttendanceSourceEnum` | Machine=`MACHINE`, Manual=`MANUAL`, DriverFace=`DRIVER_FACE` | `hmapl_type` tetap IN=0 / OUT=1 |

Contoh transaksi: capture Passed + review Approved + dispatch Retryable(stage Actual) berarti HR sudah setuju, parsed mungkin sudah ada, actual belum selesai. Retry tidak mengulangi enrollment, keputusan HR, atau insert parsed.

Transisi harus lewat service. Tidak menerima nilai status, skor verifier, reviewer, waktu server, employee ID, atau parsed ID dari public property/JSON client.

## 4. Desain database

### 4.1 Konvensi

- Connection `oracle_mgthris`; logical table lowercase di model, DDL uppercase mengikuti migration existing; physical schema/prefix melalui konfigurasi koneksi, tidak menulis `MGTHRIS.` hardcoded.
- PK baru `string(50)` dengan `{prefix}_sys_id`, `$incrementing=false`, `$keyType='string'`. FK ke master employee mengikuti tipe dan panjang kolom target live, bukan asumsi dari NIK.
- Semua mutable record memiliki `{prefix}_created_by`, `_created_timestamp`, `_modified_by`, `_modified_timestamp`. Timestamp bisnis `timestamp(6)`; aplikasi memakai Asia/Jakarta saat normalisasi ke tabel attendance existing. Wire API memakai ISO-8601 dengan offset; tidak menyimpan string waktu ambigu.
- Untuk audit umum, `created_by`/`modified_by` menggunakan NIK actor atau `SYSTEM` sesuai konvensi creator gate existing. Kolom keputusan `reviewed_by`, `approved_by`, `revoked_by` serta event `actor_id` menyimpan sys ID employee dengan tipe FK yang sesuai. Mapping ini harus eksplisit; jangan membandingkan NIK dan sys ID sebagai identifier yang sama.
- Boolean Oracle-compatible melalui schema builder; status integer/string backed enum, bukan native DB enum. Koordinat `decimal(10,7)`, meter/skor `decimal` dengan presisi memadai; cegah NaN/Infinity dan range invalid sebelum write.
- Snapshot/hasil terstruktur memakai `longText` (CLOB pada Oracle) dengan cast array; template memakai encrypted cast atas longText. Tidak mewajibkan native Oracle JSON atau indexing CLOB.
- Nama indeks/constraint eksplisit dan pendek (target <=30 karakter); jangan mengandalkan nama otomatis yang panjang.
- FK historis memakai restrict/no cascade-delete. Hard delete hanya untuk master belum terpakai; archive untuk lainnya. Jangan pakai soft-delete implicit sehingga global scope menyembunyikan referensi audit.
- ID bisnis memakai `SysIdHelper`, sequence master dibuat idempotent. Nonce/idempotency UUID bukan pengganti sys ID.
- Hindari unique `(employee,date,type)` karena multi-sesi valid. Penguncian dan uniqueness ada pada capture/source/pair yang tepat.

### 4.2 Daftar tabel baru

| Tabel | Model | Prefix | Pemilik | Tujuan |
|---|---|---|---|---|
| `hm_att_face_access` | `HmAttFaceAccess` | `hmafa_` | Hr | Eligibility, active profile pointer, serialisasi pengambilan |
| `hm_att_locations` | `HmAttLocation` | `hmal_` | Hr | Master titik/radius |
| `hm_att_emp_locations` | `HmAttEmpLocation` | `hmael_` | Hr | Penempatan karyawan pada lokasi |
| `hm_emp_face_profiles` | `HmEmpFaceProfile` | `hmefp_` | Hr | Template wajah disahkan dan versioning |
| `ht_att_face_captures` | `HtAttFaceCapture` | `htafc_` | Hr | Catatan raw pengambilan, bukti, nonce, pemeriksaan |
| `ht_att_face_enrollments` | `HtAttFaceEnrollment` | `htafe_` | Hr | Review enrollment dan identitas oleh HR |
| `ht_att_face_punches` | `HtAttFacePunch` | `htafp_` | Hr | Pengajuan IN/OUT dan keputusan HR |
| `ht_att_face_events` | `HtAttFaceEvent` | `htafv_` | Hr | Riwayat domain append-only |
| `ht_att_face_dispatches` | `HtAttFaceDispatch` | `htafd_` | Hr | Durable pekerjaan integrasi/notifikasi |
| `ht_att_face_clarif_links` | `HtAttFaceClarifLink` | `htafcl_` | Mis | Referensi Attendance Clarification ke bukti driver |

`HT` digunakan untuk transaksi baru meskipun sejumlah transaksi legacy memakai `HM`. Tidak mengganti nama tabel legacy. Sepuluh tabel ini memisahkan master, bukti, keputusan, dan proses yang memiliki lifecycle/retensi berbeda; tidak membuat tabel attendance actual paralel.

### 4.3 `hm_att_face_access`

| Kolom (selain audit umum) | Tipe/aturan |
|---|---|
| `hmafa_sys_id` | PK |
| `hmafa_hmemd_sys_id` | FK karyawan, unique `UQ_HMAFA_EMP` |
| `hmafa_is_active` | boolean default false |
| `hmafa_valid_from`, `hmafa_valid_until` | timestamp nullable; inclusive start/exclusive end |
| `hmafa_hmefp_sys_id` | FK profile aktif nullable; harus milik karyawan yang sama |
| `hmafa_reenroll_allowed` | boolean default false; HR mengizinkan penggantian |
| `hmafa_disabled_reason` | string(1000) nullable |
| `hmafa_open_htafp_sys_id` | nullable pointer IN sesi pengambilan yang terbuka |
| `hmafa_active_htafc_sys_id` | nullable pointer capture aktif; expiry/recovery wajib |
| `hmafa_version` | integer optimistic version |

Pointer open punch bukan sumber perhitungan actual. Rekonsiliasi pointer dilakukan dari bukti/urutan pengambilan, bukan dari perubahan approval. Penambahan FK pointer dilakukan setelah tabel target terbentuk. Lock satu row akses per karyawan saat issuing/finalizing capture, changing template, dan updating pair.

### 4.4 `hm_att_locations` dan `hm_att_emp_locations`

`hmal_sys_id` PK; `hmal_code` string(50) unique `UQ_HMAL_CODE`; `hmal_name` string(150); `hmal_address` string(1000) nullable; `hmal_latitude`, `hmal_longitude`; `hmal_radius_m` decimal(10,2)>0; `hmal_max_accuracy_m` decimal(10,2)>0; `hmal_is_active`; `hmal_is_archived`; `hmal_version`. Tambahkan scope perusahaan/organisasi memakai tipe FK master existing bila titik dibatasi perusahaan; validasi HR tidak melampaui scope.

`hmael_sys_id` PK; `hmael_hmafa_sys_id` FK akses; `hmael_hmal_sys_id` FK lokasi; `hmael_valid_from` wajib; `hmael_valid_until` nullable; `hmael_is_active`; `hmael_note` string(1000) nullable. Unique `(access,location,valid_from)` bernama `UQ_HMAEL_PERIOD`; indeks `(access,is_active)` bernama `IX_HMAEL_ACCESS`. Cegah interval aktif overlap pada pasangan sama melalui service di bawah lock akses. Akhiri penempatan lama dan buat interval baru untuk perubahan masa berlaku; jangan hilangkan sejarah.

### 4.5 `ht_att_face_captures` — catatan raw mandiri

| Kolom | Tipe/aturan |
|---|---|
| `htafc_sys_id` | PK |
| `htafc_hmemd_sys_id` | FK employee |
| `htafc_hmafa_sys_id` | FK akses |
| `htafc_purpose` | string(20): Enrollment/Punch |
| `htafc_punch_type` | integer nullable untuk enrollment; 0/1 untuk punch |
| `htafc_idempotency_key` | string(64), unique bersama employee: `UQ_HTAFC_IDEMP` |
| `htafc_nonce_hash` | string(64), unique `UQ_HTAFC_NONCE`; plaintext nonce tidak disimpan |
| `htafc_status` | enum status capture |
| `htafc_issued_at`, `htafc_expires_at` | timestamp server |
| `htafc_received_at` | timestamp server saat bukti lengkap diterima, immutable |
| `htafc_client_at` | timestamp perangkat nullable, untrusted |
| `htafc_verified_at` | timestamp verifier selesai |
| `htafc_payload_hash` | string(64), digest manifest evidence+context |
| `htafc_hmefp_sys_id` | profile versi yang dipakai; null untuk enrollment awal |
| `htafc_hmael_sys_id` | assignment terpilih nullable untuk enrollment |
| `htafc_latitude`, `htafc_longitude` | nullable pada enrollment; wajib pada punch |
| `htafc_accuracy_m`, `htafc_distance_m` | decimal nullable pada enrollment |
| `htafc_geo_sample_at` | timestamp sample yang dilaporkan client |
| `htafc_access_snapshot` | CLOB: versi akses dan scope organisasi saat capture |
| `htafc_location_snapshot` | CLOB: lokasi, radius, akurasi maksimum, assignment validity dan keputusan geofence |
| `htafc_policy_snapshot` | CLOB: version, threshold, model IDs, freshness limits, notice version |
| `htafc_verification_result` | CLOB hasil terstruktur, bukan arbitrary provider log |
| `htafc_failure_code` | string(50) nullable; aman ditampilkan melalui mapping pesan |
| `htafc_evidence_manifest` | CLOB disk/path/media hash/bytes/dimensi/type/retention untuk setiap artifact |
| `htafc_candidate_template` | encrypted CLOB nullable untuk enrollment; pindah ke profile setelah approve, purge sesuai retensi |
| `htafc_platform` | string(20) `WEB`; OS/browser metadata tidak dianggap attestation |
| `htafc_request_context` | CLOB terbatas: request ID, browser/OS, metadata audit tanpa token/kuki |
| `htafc_notice_version` | string(50) versi pemberitahuan yang diakui saat enrollment |
| `htafc_evidence_purged_at` | timestamp nullable; metadata tetap ada |
| `htafc_retention_hold` | boolean untuk pending review/sengketa |

Indeks: `(employee,received_at)` `IX_HTAFC_EMP_TIME`, `(status,expires_at)` `IX_HTAFC_STATUS`. Data input/snapshot/bukti immutable setelah Uploaded; hanya status/verdict/lifecycle boleh maju lewat service, dicatat event. Jangan menyimpan ulang foto base64 atau full request body di CLOB. Digest membuktikan konsistensi bytes, bukan keaslian kejadian fisik.

Kolom pemulihan verifier: `htafc_attempts` integer, `htafc_available_at`, `htafc_lease_until`, `htafc_lease_token` string(64), dan `htafc_version`. Worker claim/retry menggunakan lease serta compare-and-swap; expired worker tidak dapat menyimpan hasil sesudah claim berpindah.

### 4.6 Enrollment dan profile

`ht_att_face_enrollments`: `htafe_sys_id`, `htafe_hmemd_sys_id`, `htafe_htafc_sys_id` unique `UQ_HTAFE_CAPTURE`, `htafe_status`, `htafe_replaces_hmefp_id` nullable, `htafe_reviewed_by`, `htafe_reviewed_at`, `htafe_review_reason`, `htafe_identity_method`, `htafe_version` + audit. Satu pending enrollment per employee ditegakkan dengan lock akses, tanpa bergantung partial unique index. Metode HR mencatat pemeriksaan identitas; bukan menyimpan dokumen identitas berlebihan.

`hm_emp_face_profiles`: `hmefp_sys_id`, `hmefp_hmemd_sys_id`, `hmefp_htafe_sys_id` unique `UQ_HMEFP_ENROLL`, `hmefp_version`, `hmefp_status`, `hmefp_template` encrypted CLOB, `hmefp_model_id`, `hmefp_model_hash`, `hmefp_pipeline_version`, `hmefp_dimensions`, `hmefp_evidence_manifest` CLOB referensi foto enrollment, `hmefp_approved_by`, `hmefp_approved_at`, `hmefp_revoked_by`, `hmefp_revoked_at`, `hmefp_revoke_reason`, `hmefp_key_version` + audit. Unique `(employee,version)` `UQ_HMEFP_EMP_VER`. Pointer master akses menentukan satu versi aktif; pergantian profile, status lama dan pointer disimpan atomik.

Jangan membandingkan embedding model/version/dimensi berbeda. Upgrade model memerlukan migrasi template tervalidasi dari referensi yang eligible atau enrollment ulang; tidak mengubah profile historis diam-diam.

### 4.7 `ht_att_face_punches`

| Kolom | Aturan |
|---|---|
| `htafp_sys_id` | PK |
| `htafp_htafc_sys_id` | FK capture Passed, unique `UQ_HTAFP_CAPTURE` |
| `htafp_hmemd_sys_id`, `htafp_hmemd_nik` | identity FK + snapshot NIK string |
| `htafp_type` | 0=IN, 1=OUT |
| `htafp_punched_at` | timestamp dari capture received_at; immutable |
| `htafp_in_htafp_sys_id` | nullable reference IN web; unique nullable `UQ_HTAFP_WEB_IN` agar satu OUT normal per IN |
| `htafp_in_hmapl_sys_id` | nullable reference IN mesin/manual; unique nullable `UQ_HTAFP_EXT_IN`; jangan isi bersamaan dengan IN web |
| `htafp_exception_code` | null / MissingIn / PreviousSessionIncomplete / PotentialDuplicate |
| `htafp_exception_reason` | nullable string(1000), wajib untuk pengecualian urutan |
| `htafp_review_status` | Pending/Approved/Rejected |
| `htafp_reviewed_by`, `htafp_reviewed_at` | actor/time server |
| `htafp_review_reason` | string(1000), wajib saat Rejected |
| `htafp_hmapl_sys_id` | FK parsed nullable, unique `UQ_HTAFP_PARSED`; hasil projection |
| `htafp_version` | integer compare-and-swap |

Tambahkan audit umum; indeks `(employee,punched_at)` `IX_HTAFP_EMP_TIME`, `(review_status,punched_at)` `IX_HTAFP_REVIEW`. Kolom outcome dispatch tidak diduplikasi sebagai sumber kebenaran di sini: status UI dibentuk dari dispatch records. Jika cache status dibutuhkan, harus dapat direkonstruksi.

Untuk antrean HR, punch dan enrollment memiliki snapshot kolom organisasi `{prefix}_hmcomp_comp_id`, `{prefix}_hmdiv_div_id`, `{prefix}_hmdep_dept_id`, `{prefix}_hmsec_sect_id` dengan tipe master existing. Nilai diambil dari employee di server saat submit. Scope histori menggunakan organisasi saat pengajuan, dengan mekanisme handover administratif jika diperlukan; perpindahan employee tidak diam-diam membuka bukti bagi scope yang salah. Repository memakai pemetaan scope existing, namun creator/edit/abort bypass MIS tidak otomatis memberi hak approve HR. Master akses dikelola memakai scope employee terkini.

### 4.8 Event, dispatch, dan clarification links

**`ht_att_face_events`:** `htafv_sys_id`, `htafv_subject_type`, `htafv_subject_id`, `htafv_event_type`, `htafv_actor_id` nullable/system, `htafv_occurred_at`, `htafv_from_status`, `htafv_to_status`, `htafv_reason`, `htafv_request_id`, `htafv_metadata` CLOB terbatas. Append-only; indeks `(subject_type,subject_id,occurred_at)` `IX_HTAFV_SUBJECT`. Tidak menyimpan biometrik mentah. Spatie Activitylog tetap dipakai untuk aktivitas umum; event domain menjaga timeline state transition.

**`ht_att_face_dispatches`:** `htafd_sys_id`, `htafd_subject_type`, `htafd_subject_id`, `htafd_stage`, `htafd_revision`, `htafd_status`, `htafd_attempts`, `htafd_available_at`, `htafd_lease_until`, `htafd_lease_token`, `htafd_completed_at`, `htafd_last_error_code`, `htafd_last_error` (pesan tersanitasi), `htafd_payload` CLOB (IDs/range, tanpa foto/template), audit. Unique `(subject_type,subject_id,stage,revision)` `UQ_HTAFD_WORK`; indeks `(status,available_at)` `IX_HTAFD_READY`. Berfungsi sebagai transactional outbox dan checkpoint; job bukan sumber kebenaran tunggal.

**`ht_att_face_clarif_links` (Mis):** `htafcl_sys_id`, `htafcl_heac_sys_id` FK klarifikasi existing, `htafcl_htafc_sys_id` FK capture, `htafcl_htafp_sys_id` nullable FK punch, `htafcl_link_type` (`Context`/`Resolution`), `htafcl_is_active`, audit. Unique `(clarification,capture)` `UQ_HTAFCL_REF`; bila punch diisi wajib milik capture tersebut. Satu klarifikasi bisa menjelaskan IN dan OUT. Satu sumber hanya boleh memiliki satu Resolution aktif; penegakan dengan lock sumber dan query repository, bukan hanya validasi browser.

### 4.9 Perubahan pada parsed existing

Migration baru di Hr karena model/repository parsed dimiliki Hr; dependensi deploy memastikan migration pembentuk tabel Mis sudah terpasang. Jangan mengedit migration awal.

| Kolom | Perubahan |
|---|---|
| `hmapl_hmatl_sys_id` | Jadikan nullable untuk driver; mesin/manual tetap mengisi raw ID |
| `hmapl_source` | string(20) nullable pada rollout/backfill: MACHINE / MANUAL / DRIVER_FACE |
| `hmapl_htafp_sys_id` | string(50) nullable, FK punch driver, unique `UQ_HMAPL_FACE_PUNCH` |
| `hmapl_type` | Tidak diubah: 0/1 |
| `hmapl_machine_ip`, `hmapl_machine_name` | Null untuk driver; jangan mengarang IP/nama mesin |

Invariant source: driver wajib punya punch ID dan raw ID null; mesin/manual wajib raw ID dan punch ID null. Tegakkan di writer/service dan DB check setelah audit kompatibilitas DDL. Kolom source histori boleh null selama rollout; reader memiliki fallback klasifikasi existing. Backfill manual menggunakan flag raw/XML/nama legacy yang sudah dikenali, baru klasifikasi mesin. Jangan mengubah semua histori menjadi MACHINE.

Tambah `findByFacePunchSysId()` dan `createFromFacePunch()` pada repository atau kontrak writer source khusus. Jangan memakai `upsertByRawSysId()` dengan ID palsu. Retry data sama mengembalikan row sama; konflik payload pada referensi sama adalah error, bukan overwrite waktu punch.

Mapping normalisasi:

| Parsed | Nilai |
|---|---|
| `hmapl_sys_id` | `SysIdHelper` sequence parsed existing |
| `hmapl_hmemd_nik` | NIK master/snapshot terpercaya dengan leading zeros dipertahankan |
| `hmapl_time` | `htafp_punched_at` pada zona waktu attendance existing |
| `hmapl_date` | Tanggal kalender punch; bukan tanggal approval atau tanggal kerja yang ditebak |
| `hmapl_type` | `htafp_type` |
| `hmapl_source` | DRIVER_FACE |
| `hmapl_htafp_sys_id` | Pengajuan Approved |
| `hmapl_is_ignored` | 0 untuk insert normal |
| `hmapl_synced_at` | null saat insert; jangan dipakai sendiri sebagai bukti seluruh actual/post-process sukses |

Inspeksi `rawLog()` consumers, inner joins, export/filter mesin, holiday overtime candidate query, dan parsed monitor untuk null raw. Unique punch source adalah jaminan idempotency; similarity waktu/NIK lintas sumber hanya kandidat duplikasi.

### 4.10 Migration dan sequence

Urutan: config disable → master lokasi/akses → captures/enrollments → profiles → punches → events/dispatch → FK pointer → parsed additive/nullability → clarification links (Mis) → sequences → permission → menu → optional backfill command.

Sequence baru mengikuti `HM_ATT_LOCATIONS_HMAL_SYS_ID_SEQ`, `HM_ATT_FACE_ACCESS_HMAFA_SYS_ID_SEQ`, dan pola `{TABLE}_{PREFIX}_SYS_ID_SEQ` bagi tabel lain. Ini nama konfigurasi pada master sequence, bukan harus menjadi nama Oracle sequence object. Inspeksi kapasitas kolom sequence dan konfigurasi generator saat implementasi; prefix output dan panjang hasil <=50.

Setiap migration memiliki down dan disable key, Oracle/SQLite compatible. Down yang mengembalikan raw ID menjadi non-null harus menolak dengan pesan jelas bila driver rows masih ada; tidak menghapus bukti/punch otomatis agar rollback berhasil. Rollback operasional utama mematikan capture/dispatch baru, mempertahankan schema dan hasil attendance.

## 5. Service dan kontrak wajib

Nama implementation di Hr kecuali disebutkan berbeda. Jumlah file boleh dirapikan selama tanggung jawab dan boundary tetap jelas.

| Service | Tanggung jawab dan metode kunci |
|---|---|
| `FaceAttendanceAccessService` | Grant/disable/eligibility, penempatan dan active profile; `assertCanCapture()`, `grantAccess()`, `disableAccess()` |
| `AttendanceLocationService` | CRUD/archive master dan penempatan; geofence snapshot, freshness/accuracy; `evaluateLocation()` |
| `FaceCaptureService` | Issue/finalize/expire challenge, identity/payload binding, idempotency, storage-verifier orchestration |
| `FaceVerificationService` | Panggil adapter verifier internal, validate schema/digests/model versions, map errors; tidak mempercayai boolean client |
| `FaceEnrollmentService` | Submit/review enrollment, HR identity confirmation, version activation/revoke atomik |
| `FacePunchService` | Status pengambilan, aturan IN/OUT dan exception, buat pending punch dari capture Passed |
| `FaceAttendanceReviewService` | Scope HR, no self-review, versioned Validate/Reject, event + outbox dalam transaksi |
| `FaceAttendanceIntegrationService` | Project Approved ke parsed secara idempotent; dispatch recalc; checkpoint/error/recovery |
| `FaceEvidenceService` | Batas upload, hash, image normalization, private read, retained artifacts, purge dan orphan cleanup |
| `FaceAttendanceNotificationService` | Event → recipient/template/deep link; gunakan BaseNotification dan outbox |
| `PersonalAttendanceService` (SelfService) | Ownership mutlak, summary personal, detail driver, DTO untuk UI |
| `AttendanceRecalculationService` (Mis adapter) | Wrap engine existing, range terdampak, lock, verify actual result, post-process terkait |
| `FaceAttendanceClarificationService` (Mis) | Link sumber, finality dan Released reconciliation; tidak membuat workflow klarifikasi kedua |

Kontrak antarmodul minimum:

- `Hr/Interfaces/Attendance/Face/FaceAttendanceGatewayInterface`: methods eligible context, enrollment, issue/complete capture, punch status. Selalu menerima actor context terpercaya dari server dan menegakkan ownership lagi.
- `Hr/Interfaces/Attendance/Face/FaceAttendanceReadInterface`: own/scoped summaries, detail dan evidence authorization DTO; tidak mengembalikan template.
- `Hr/Interfaces/Attendance/Face/FaceVerificationClientInterface`: `enroll()`, `verify()`, `health()`; adapter DeepFace dan fake hanya untuk test.
- `Hr/Interfaces/Attendance/Face/FaceEvidenceStorageInterface`: put/read-authorized/delete dan manifest.
- `Core/Interfaces/Attendance/AttendanceRecalculationInterface`: `recalculate(employeeId, range, reason, sourceId): RecalculationResultData`; implementasi Mis. Tidak ada null implementation yang diam-diam melaporkan sukses.
- `Core/Interfaces/Attendance/AttendancePeriodPolicyInterface`: keputusan CanProcess/Blocked dan alasan. Implementasi disambungkan ke aturan periode perusahaan; jangan mengasumsikan tabel lock sudah ada.
- `Mis/Interfaces/Transactions/Actual/PersonalAttendanceSummaryInterface`: entry own-summary terpercaya menggunakan engine existing.

Repository minimum: `AttendanceLocationRepositoryInterface`, `FaceAttendanceAccessRepositoryInterface`, `EmployeeAttendanceLocationRepositoryInterface`, `EmployeeFaceProfileRepositoryInterface`, `FaceCaptureRepositoryInterface`, `FaceEnrollmentRepositoryInterface`, `FacePunchRepositoryInterface`, `FaceAttendanceEventRepositoryInterface`, `FaceAttendanceDispatchRepositoryInterface`, `FaceAttendanceClarificationLinkRepositoryInterface`. Implementation `Eloquent...` mirror lokasi domain. Repository UnitOfWork menyediakan transaksi/lock di satu connection.

DTO minimum: `AttendanceLocationData`, `FaceAttendanceAccessData`, `EmployeeLocationAssignmentData`, `FaceCaptureRequestData`, `FaceCaptureContextData`, `FaceVerificationResultData`, `FaceEnrollmentReviewData`, `FacePunchReviewData`, `FacePunchDetailData`, `FaceAttendanceMarkerData`, `RecalculationResultData`. Validasi coordinate/range/type/length/purpose dan forbidden properties dilakukan pada boundary.

## 6. Concurrency, processing dan pemulihan

### Capture

1. Transaksi pendek lock row akses → validasi eligibility/urutan → buat Issued capture dan pointer → commit.
2. Upload media ke lokasi privat deterministik dalam batas ukuran; catat manifest dan first received_at. Put yang gagal tidak menghasilkan Uploaded. Cek return/exception karena existing disk memakai `throw=false`.
3. Freeze manifest/context; hash nonce/payload. Request ulang dengan idempotency key yang sama dan hash sama mengembalikan status existing. Hash berbeda menghasilkan conflict.
4. Verifier di luar transaksi Oracle; claim capture secara compare-and-swap. Queue terpisah dengan lease dan timeout, tidak menahan row lock saat inference.
5. Finalisasi lock akses/capture → periksa verdict berasal dari service dan context/hash sama → periksa eligibility → simpan Passed dan enrollment/punch unik → update pointer → event/outbox → commit.
6. Worker crash dipulihkan melalui persisted capture status/lease. Expiry berlaku untuk pengiriman bukti; bukti yang sudah diterima tepat waktu tidak dianggap terlambat hanya karena queue lambat.

### Review/integrasi

1. Lock punch, validasi Pending+scope+no self-review+version.
2. Approved dan dispatch Parsed/Notify dibuat dalam transaksi yang sama. Rejected membuat event/Notify, tanpa Parsed.
3. Worker claim dispatch dengan lease token. Cek source masih Approved, periode boleh diproses, dan tidak memiliki Resolution klarifikasi konflik; buat parsed dan link secara atomik. Jika periode terkunci, tahan sebelum insert parsed agar cron existing tidak memprosesnya melalui jalur lain.
4. Setelah parsed commit, buat durable pekerjaan Actual. Stale worker tidak boleh menandai Complete setelah lease diambil worker lain.
5. Adapter Mis memproses tanggal terdampak secara kronologis. Baseline mencakup D-1 sampai D+1, diperluas bila dependency/window resolver menunjukkan rentang lebih lebar. Persetujuan IN/OUT berbeda waktu tetap aman diproses ulang.
6. Panggilan tidak hanya memeriksa exception: `processSingleEmployeeDay()` dapat mengembalikan false. Cek hasil, actual IDs/status/anomali dan dependency processing. Bedakan Failed dari ProcessedWithAnomaly.
7. Koordinasikan lock recalc per employee/range dengan semua entry yang menyentuh actual (cron, manual re-analyze, klarifikasi, driver) agar hasil lama tidak menimpa hasil lebih baru. Merge/debounce request boleh, tetapi semua source memperoleh outcome yang tepat.
8. PostActual memakai proses existing untuk OT/rate/balance bila relevan. Hindari menghitung seluruh departemen tanpa perlu; jangan menyalin job existing yang menangkap error lalu dianggap berhasil. Exit code/status command harus dipropagasi, dan side effect saldo harus idempotent.
9. Mark Complete hanya untuk tahap yang benar-benar berhasil. Recovery command memulihkan Pending/Retryable/lease expired dengan backoff dan batas percobaan; permanent errors berstatus Blocked untuk operator.

`queue afterCommit` membantu visibilitas transaksi tetapi tidak menggantikan outbox: proses dapat crash di antara commit dan enqueue. Scheduler men-scan dispatch outstanding sebagai jalur pemulihan.

### Notifikasi

Database+broadcast mengikuti BaseNotification. Retry dapat menghasilkan delivery lebih dari sekali; gunakan event key/deterministic notification identity bila perlu agar database notification tidak berlipat. Jangan mengklaim transport exactly-once. Broadcast boleh gagal tanpa rollback keputusan; UI dapat reload/poll status dengan interval terbatas.

### Klarifikasi

- Existing enum adalah Draft=0, Confirmed=1, Submitted=2, Approved=3, Released=4, Rejected=5, Aborted=6. Jangan memakai nilai baru review punch untuk klarifikasi.
- Link `Context` tidak mengubah source; `Resolution` aktif mencegah jalur persetujuan ulang yang bertentangan. Link dan keputusan memakai lock sumber konsisten.
- Source Rejected terminal untuk v1. Released clarification memengaruhi actual melalui aturan existing, bukan mengubahnya menjadi Approved/parsed.
- Jika source Approved sudah masuk parsed lalu perlu koreksi, clarification menjadi correction overlay sesuai engine existing. Jangan membuat duplicate driver parsed.
- Hilangkan asumsi `actual must exist` pada jalur klarifikasi yang ditangani. Adapter harus memproses scoped employee/date agar actual dapat dibentuk dari data released clarification yang sah, termasuk tanpa schedule/punch. Uji urutan early-return engine; menghapus satu guard saja belum menjamin seluruh alur bekerja.
- Ketika final period lock berlaku, antrekan Blocked dan arahkan prosedur koreksi resmi. Inspeksi belum menemukan kontrak lock periode existing; penetapan/integrasi kebijakan adalah prerequisite produksi, bukan fakta yang sudah tersedia.
- Kebijakan periode harus diperiksa juga pada semua entry actualization/penutupan periode. Penutupan harus berkoordinasi dengan pekerjaan yang sudah berjalan; pemeriksaan sebelum parsed saja tidak mengatasi race periode ditutup sesudah parsed dibuat. Jangan mengklaim final-period protection jika cron/manual re-analyze masih dapat melewatinya.

## 7. Route, menu dan permission

### Halaman baru

| Route name | URI setelah prefix modul | Component |
|---|---|---|
| `dashboard.module-self-service.face-attendance` | `/face-attendance` | `SelfService\Livewire\Attendance\FaceAttendance` |
| `dashboard.module-self-service.face-enrollment` | `/face-enrollment` | `SelfService\Livewire\Attendance\FaceEnrollment` |
| `dashboard.module-self-service.attendance-summary` | `/attendance-summary` | `SelfService\Livewire\Attendance\MyAttendanceSummary` |
| `dashboard.module-hr.master-attendance-location` | `/master-attendance-location` | `Hr\Livewire\Master\Attendance\MasterAttendanceLocation` |
| `dashboard.module-hr.master-face-attendance-access` | `/master-face-attendance-access` | `Hr\Livewire\Master\Attendance\MasterFaceAttendanceAccess` |
| `dashboard.module-hr.face-enrollment-review` | `/face-enrollment-review` | `Hr\Livewire\Attendance\Face\FaceEnrollmentReview` |
| `dashboard.module-hr.face-attendance-validation` | `/face-attendance-validation` | `Hr\Livewire\Attendance\Face\FaceAttendanceValidation` |

Blade namespace SelfService adalah `selfservice::`, bukan `self-service::`. Contoh view `selfservice::livewire.attendance.face-attendance`, HR `hr::livewire.attendance.face.face-attendance-validation`. Semuanya mempunyai breadcrumb. Detail deep link memakai query `date=YYYY-MM-DD&face_punch=<id>` lalu validasi ownership/scope sebelum membuka drawer.

Public authenticated capture endpoints berada di route web SelfService dengan session auth, verified, CSRF, permission, eligibility middleware dan rate limit; tidak memakai endpoint ADMS `/iclock` yang unauthenticated. Contoh POST `face-attendance/captures`, POST `captures/{id}/evidence`, GET `captures/{id}/status`. Evidence read route berbeda dari upload dan memiliki policy own/HR-scope. Jangan expose URL Python ke browser.

### Permission baru

Mengikuti pola page/domain/action existing; tidak membuat role Driver dengan hardcoded global grant.

| Permission | Pemakaian |
|---|---|
| `self-service_face_attendance-view` | Page gate capture dan enrollment |
| `self-service_face_attendance-create` | Issue/upload punch dan enrollment |
| `self-service_attendance_summary-view` | Summary personal dan own evidence/detail |
| `hr-master_attendance_location-view/create/edit/delete` | Notasi empat permission terpisah untuk master lokasi |
| `hr-master_face_attendance_access-view/create/edit/delete` | Empat permission; penempatan bagian dari akses |
| `hr-face_enrollment_review-view/approve/reject` | Tiga permission, review identitas |
| `hr-face_enrollment_review-revoke` | Pencabutan/re-enrollment template |
| `hr-face_attendance_validation-view/approve/reject` | Tiga permission review punch |
| `hr-face_attendance_validation-retry` | Retry integrasi, bukan mengubah bukti |
| `hr-face_attendance_validation-view_all_scope` | Opsional, bypass scope administratif secara eksplisit |

Simbol slash pada tabel berarti perlu diekspansi menjadi nama permission lengkap, bukan literal slash. Gunakan `guard_name=web` sesuai auth existing. Grant kepada reviewer bukan otomatis karena nama role; administrator menentukan role permission.

Saat HR mengaktifkan akses driver, layanan akses memastikan page/create/summary entitlement melalui konfigurasi role/direct grants yang ditetapkan perusahaan. Catat grant yang dibuat fitur; jangan revoke permission yang berasal dari role lain. Disable capture ditentukan row eligibility sehingga riwayat summary tidak hilang. Cache permission dan menu harus invalidated setelah perubahan.

Menu codes: `self-service.face-attendance`, `self-service.face-enrollment`, `self-service.attendance-summary`, `hr.masters.attendance.locations`, `hr.masters.attendance.face-access`, `hr.attendance.face-enrollment-review`, `hr.attendance.face-validation`. Upsert `cm_menus` keyed code; parent di-resolve by code. Page gate menu sama dengan route gate; eligibility filter capture diterapkan konsisten pada route dan shortcut/menu melalui service server. Parent module landing tidak menjadi prerequisite untuk direct link.

Jangan memasang `approval_access` MIS secara mentah pada route HR baru: allowlist-nya khusus permission MIS. Gunakan middleware review HR khusus dengan per-route permission/policy setara intent approval protection, tanpa menambah dependency Hr→Mis. Alur Attendance Clarification tetap memakai middleware existing.

## 8. Konfigurasi dan operasi

Lokasi config baru yang disarankan: `Modules/Hr/config/face_attendance.php`, pastikan provider module memuatnya menjadi `hr.face_attendance.*`; cek mekanisme merge nested config saat implementasi.

| Key | Isi/usulan awal |
|---|---|
| `enabled` | false sebelum pilot siap |
| `verification.url`, `.credential`, `.timeout_seconds` | Endpoint privat, secret di environment/config, timeout terbatas |
| `verification.pipeline_version` | Versi pipeline yang diizinkan, bukan latest dinamis |
| `capture.ttl_seconds` | 120 sebagai usulan pilot |
| `capture.max_payload_bytes` | 8 MiB usulan untuk total input, disesuaikan protokol liveness |
| `capture.max_frames` | 5 untuk pilot frame-based; tidak berlaku otomatis untuk SDK video |
| `capture.max_image_pixels` | Batas decoded dimensions, bukan hanya byte upload |
| `location.max_age_seconds` | 30 usulan pilot; client timestamp tetap untrusted |
| `location.boundary_policy` | Strict/uncertain handling sesuai hasil uji, jangan implicit tolerance |
| `evidence.disk` | minio_private |
| `evidence.jpeg_quality`, `.max_edge` | 80 dan 960 sebagai nilai uji, bukan jaminan ukuran |
| `retention.punch_days`, `.failed_days`, `.orphan_hours` | 180/7/24, pending/sengketa ditahan |
| `queue.verify`, `.integrate` | Named queue dengan dedicated worker; deployment harus ikut diperbarui |
| `retry.max_attempts`, `.backoff_seconds` | Terukur dan tercatat, tidak infinite retry |

Algoritme geofence pilot: valid latitude/longitude dan accuracy → fresh sample → hitung Haversine di backend → bandingkan center distance/radius. Usulan konservatif: `distance + reported_accuracy <= radius` dan accuracy <= batas titik untuk normal acceptance; area ambigu meminta sampel ulang. Accuracy adalah estimasi yang dilaporkan perangkat, bukan batas error pasti dan bukan bukti lokasi asli. Radius tidak otomatis diperbesar.

Command usulan: `hr:recover-face-attendance`, `hr:expire-face-captures`, `hr:prune-face-evidence`. Register mengikuti service provider module; dedicated log channel `face_attendance`, `face_verification`, `face_attendance_dispatch`; batch/chunk, tanpa full biometric payload. Recovery setiap menit, expiry berkala, prune harian; pakai withoutOverlapping dan distributed lock jika deployment lebih dari satu scheduler.

Deployment saat ini bare-metal menurut panduan repository. Menambah service Python/container tidak berarti mengubah seluruh production menjadi Docker. Siapkan systemd/container internal, TLS/firewall, health/readiness, shared/private storage access, worker queue, limits CPU/RAM/thread. Update deploy/runbook secara eksplisit.

Operasional: metrik latency/queue depth, verifier technical errors vs verification failures, antrean HR tertua, retry count, parsed/actual drift, retained bytes. Jangan menjadikan error teknis sebagai skor buruk karyawan. Backup database+object storage+key material diuji bersama; key rotation harus mempertahankan kemampuan membaca template yang masih aktif.

Retensi harus reference-aware: foto enrollment yang masih dipakai profile aktif tidak dihapus ketika capture enrollment menua. Hold mencakup review pending, profile aktif, dan klarifikasi/sengketa terbuka; pelepasan hold dicatat. Delete object yang gagal dicoba ulang dan metadata tidak menyatakan sudah terhapus sebelum konfirmasi storage. Private URL berumur pendek hanya diterbitkan setelah policy check; jika pencabutan akses harus langsung berlaku, sajikan melalui authorized proxy, bukan URL yang masih berlaku sampai expiry.

## 9. Pengujian dan urutan implementasi

- Pest unit: geofence/freshness, status transitions, pairing tanpa schedule, snapshot immutable, threshold/config mapping dan idempotency rules.
- Feature: owner scoping, HR organizational scope, grants/menu cache, no self-review, enrollment activation, revoke mid-capture, denied proof read, content/dimension limits, HR race, outbox failure/recovery.
- Integrasi database: migration SQLite serta staging Oracle, nullable raw FK, unique reference, transactional source write, locks/concurrent IN/OUT, CLOB casts/encryption, NIK leading zeros.
- Regressions: mesin/manual parser, source filtering/export, actualization overnight/multi-session, holiday overtime, late approval dan downstream balance, clarification Released dengan/tanpa actual.
- Browser: camera permission denied/missing, iOS Safari lifecycle/rotation, Android Chrome, desktop, slow network/retry, expiry, prompt guidance, keyboard/dark mode dan mobile layout.
- Contract Python: fixed request/result schema, artifact/model mismatches, timeout, malformed verdict, wrong capture hash, engine reload, concurrency/memory limits.
- UAT biometrik: lihat dokumen SDK; tidak digantikan mocked unit tests.

Jalankan targeted tests saat implementasi, Pint pada PHP yang diubah, build jika assets berubah, lalu required CI sesuai repository. PR UI menyertakan screenshot. Dokumen perencanaan ini sendiri tidak menjalankan migration, install SDK, maupun test suite aplikasi.

## 10. Risiko khusus yang wajib ditutup sebelum launch

1. DDL live mungkin berbeda dari migration. Verifikasi constraint/trigger/consumer parsed sebelum mengubah nullability.
2. `hmapl_synced_at` ditulis pada jalur tertentu untuk banyak log satu tanggal; tidak cukup sebagai acknowledgement untuk satu source atau seluruh proses lanjutan.
3. Approval terlambat dapat memengaruhi holiday overtime yang sebelumnya belum punya kandidat. Integrasi perlu menilai command terkait pada tanggal sumber, bukan hanya menjalankan actual sekali.
4. Tidak semua pekerjaan post-reanalysis existing scoped per employee dan tidak semua error dilempar. Bungkus/perbaiki jalur yang diperlukan dengan hasil eksplisit serta regression test.
5. `SysIdHelper` memakai koneksi default untuk transaction sequence dalam implementasi existing. Pastikan konfigurasi/atomicity generator pada environment nyata dan concurrent ID allocation; jangan menyatakan satu transaksi bila koneksi berbeda.
6. Face template ADMS/biometrik OS bukan template SFace/DeepFace. Jangan import sebagai embedding baru tanpa migrasi enrollment yang sah.
7. Fotografi kamera web dan browser GPS tidak memiliki attestation asal. Nonce mengurangi replay protokol tetapi tidak membuktikan sensor fisik.
