# Spec Teknis — NIK Baru & Org Scope

Referensi: [PRD_HRIS_NIK.md](PRD_HRIS_NIK.md). SQL lengkap di [sql/](sql/) (`01_ddl` → `02_backfill` → `03_verify` → `04_cutover`, rollback `99_rollback`).

---

## A. NIK

### 1. Data Model

#### 1.1 `MGTHRIS.HM_EMP_DATA` (ubah)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `HMEMD_NIK` | VARCHAR2(30) (existing) | **Kunci operasional** yang dibaca semua modul. Fase 1–2: karyawan lama = NIK lama, karyawan baru = `HMEMD_NIK_NEW`. **Sejak cutover (§11): seluruh karyawan aktif = `HMEMD_NIK_NEW`.** |
| `HMEMD_NIK_NEW` | VARCHAR2(10) **baru**, nullable | NIK standar `YYYYMMNNNN`. `UQ_HMEMD_NIK_NEW` UNIQUE **(`HMEMD_HMCOMP_COMP_ID`, `HMEMD_NIK_NEW`)** + `CK_HMEMD_NIK_NEW` (`REGEXP_LIKE(...,'^[0-9]{10}$')`). NULL untuk akun sistem, non-survivor merge, dan baris tanpa join date/company (sementara). |
| `HMEMD_PREV_SYS_ID` | VARCHAR2(30) **baru**, FK self (`FK_HMEMD_PREV_SYS_ID`) | Baris penempatan sebelumnya (rehire / kontrak→permanen / pindah company). Index `IX_HMEMD_PREV_SYS_ID`. |
| `HMEMD_NIK_REASON` | VARCHAR2(30) **baru**, `CK_HMEMD_NIK_REASON` | `BACKFILL` \| `NEW_HIRE` \| `REHIRE` \| `CONTRACT_TO_PERMANENT` \| `TRANSFER_COMPANY`. |
| `HMEMD_JOIN_DATE` | DATE (existing) | Wajib untuk insert baru (dasar prefix NIK). |
| `HMEMD_HMCOMP_COMP_ID` | (existing) | Wajib untuk insert baru (dasar seri NIK). |

> **Kenapa `HMEMD_NIK` tidak langsung diganti saat backfill, tetapi baru saat cutover?** `HMEMD_NIK` disalin (denormalized) ke 47 kolom `%NIK%` di ±40 tabel MGTHRIS, tabel payroll MGTDAT (sebagian `VARCHAR2(5)`), MGTAPPS, dan menjadi PIN mesin. Kolom bridge `HMEMD_NIK_NEW` memungkinkan backfill, verifikasi HR, dan uji modul **tanpa** mengubah perilaku produksi. Setelah prasyarat terpenuhi (kolom diperlebar, map lengkap, modul teruji), cutover mengganti `HMEMD_NIK` **hanya di master** (satu UPDATE) sehingga semua transaksi baru otomatis ber-NIK baru. Transaksi historis tidak di-mass-update; relasinya aman karena 39 FK ke `HM_EMP_DATA` memakai `HMEMD_SYS_ID`.

#### 1.2 `MGTHRIS.HM_NIK_COUNTER` (baru) — tabel bantu seri
| Kolom | Tipe | |
|---|---|---|
| `HNC_COMP_ID` | VARCHAR2 PK, FK `FK_HNC_COMP` → `HM_COMPANY` | seri terpisah per company |
| `HNC_PERIOD` | VARCHAR2(6) PK, `CK_HNC_PERIOD` | `YYYYMM` bulan join |
| `HNC_LAST_NO` | NUMBER(4) NOT NULL, `CK_HNC_LAST_NO` 0–9999 | nomor terakhir terpakai |
| `HNC_MODIFIED_TIMESTAMP` | DATE | |

PK komposit `PK_HM_NIK_COUNTER (HNC_COMP_ID, HNC_PERIOD)`. Contoh isi: `(MGT, 202610, 3)`, `(ABC, 202610, 1)` → NIK berikutnya MGT `2026100004`, ABC `2026100002`.

#### 1.3 `MGTHRIS.HM_NIK_CONFIG` (baru) — flag fase
| `HNCF_KEY` | Nilai | Makna |
|---|---|---|
| `AUTO_ENABLED` | `N` / `Y` | `N` fase 1 (bridge saja); `Y` fase 2 (karyawan baru `HMEMD_NIK = NIK_NEW`, join date & company wajib) |
| `CUTOVER_DATE` | NULL / `yyyy-mm-dd` | diisi oleh `04_cutover.sql`; transaksi ≥ tanggal ini ber-NIK baru |

Kolom lain: `HNCF_VALUE`, `HNCF_DESC`, `HNCF_MODIFIED_BY`, `HNCF_MODIFIED_TIMESTAMP`.

#### 1.4 `MGTHRIS.HM_EMP_NIK_MAP` (baru) — alias NIK lama
| Kolom | Tipe | |
|---|---|---|
| `HENM_SYS_ID` | NUMBER PK (sequence + trigger `HM_EMP_NIK_MAP_TRG01`) | |
| `HENM_HMEMD_SYS_ID` | VARCHAR2(30) FK → `HM_EMP_DATA` | baris survivor (pemilik NIK baru) |
| `HENM_NIK_NEW` | VARCHAR2(10) | redundan untuk lookup cepat |
| `HENM_NIK_OLD` | VARCHAR2(30) NOT NULL | nilai NIK lama |
| `HENM_SOURCE` | VARCHAR2(20) | `HRIS` \| `PAYROLL_NIK` \| `PAYROLL_SUBACNT` \| `MERGED_ROW` \| `MACHINE_PIN` |
| `HENM_DIV_ID` | VARCHAR2(30) | divisi asal NIK lama |
| `HENM_SRC_SYS_ID` | VARCHAR2(30) | SYS_ID baris duplikat (untuk `MERGED_ROW`) |
| `HENM_IS_PRIMARY` | CHAR(1) | `Y` = NIK lama utama (nilai `HMEMD_NIK` sebelum cutover) — **wajib ada sebelum cutover** |
| audit | `CREATED_BY/TIMESTAMP` | |

Unique `UQ_HENM_OLD_SRC_DIV (HENM_NIK_OLD, HENM_SOURCE, HENM_DIV_ID)`. Index: `HENM_HMEMD_SYS_ID`, `LTRIM(HENM_NIK_OLD,'0')`, `HENM_NIK_NEW`.

#### 1.5 View bantu `MGTHRIS.V_EMP_NIK_LOOKUP`
Kolom: `NIK_ANY, MATCH_SOURCE, HMEMD_SYS_ID, NIK_NEW, NIK_LEGACY, COMP_ID, DIV_ID`. Union dari `HMEMD_NIK_NEW` ∪ `HMEMD_NIK` (bila ≠ NIK_NEW) ∪ `LTRIM(HMEMD_NIK,'0')` ∪ `HM_EMP_NIK_MAP`. Pasca cutover, NIK lama terbaca dari map `HRIS`. Satu `NIK_ANY` bisa >1 baris (NIK lama bentrok antar divisi, NIK baru sama di company lain, rehire) → **pemanggil wajib filter `COMP_ID`** (dan `DIV_ID` bila perlu) serta memprioritaskan baris aktif.

### 2. Algoritma Generate

```
FN_GEN_NIK(p_comp_id VARCHAR2, p_join_date DATE) RETURN VARCHAR2   -- bukan autonomous
  IF p_comp_id IS NULL   THEN RAISE -20904 'Company wajib diisi'
  IF p_join_date IS NULL THEN RAISE -20902 'Tanggal join wajib diisi'
  v_period := TO_CHAR(p_join_date,'YYYYMM')
  MERGE INTO HM_NIK_COUNTER (comp, period)           -- insert last_no=0 bila belum ada
  UPDATE HM_NIK_COUNTER SET last_no = last_no + 1
   WHERE comp = p_comp_id AND period = v_period
   RETURNING last_no INTO v_no                        -- row lock → serialisasi per company+bulan
  -- CK_HNC_LAST_NO (ORA-02290) → RAISE -20901 'Seri NIK <comp> periode <yyyymm> penuh'
  RETURN v_period || LPAD(v_no,4,'0')
```
- Dipanggil dalam transaksi yang sama dengan INSERT → rollback insert = rollback counter, tidak ada nomor loncat.
- Lock hanya per (company, bulan join); company berbeda tidak saling menunggu (AC-02).
- Kapasitas: maksimum historis 52/bulan (di luar bulk default), jauh di bawah 9999.
- Tidak memakai `SysIdHelper` Laravel karena (a) tanggal diambil dari `now()`, (b) tidak reset per bulan/company, (c) tidak menangkap insert non-Laravel (`PRC_SYNC_PAYROLL_EMP`, migrasi SQL). Satu sumber di DB = konsisten di semua jalur.
- Join date lampau (mis. input telat, atau Duplicate Employee dengan join date bulan lalu) tetap mengambil nomor berikutnya di seri bulan tersebut.

### 3. Trigger

#### 3.1 `HM_EMP_DATA_TRG_NIK` (baru, BEFORE INSERT, FOLLOWS `HM_EMP_DATA_TRG01`, dibuat DISABLED)
```
IF :NEW.HMEMD_NIK_NEW IS NOT NULL OR NVL(:NEW.HMEMD_IS_USER_APP,0) = 1 THEN RETURN;   -- migrasi / akun sistem
IF join_date IS NULL OR comp IS NULL THEN
   IF flag = 'Y' THEN RAISE -20904 (company) / -20902 (join date); END IF;
   RETURN;                                       -- fase 1: dilengkapi via backfill ulang
END IF;
:NEW.HMEMD_NIK_NEW    := FN_GEN_NIK(:NEW.HMEMD_HMCOMP_COMP_ID, :NEW.HMEMD_JOIN_DATE);
:NEW.HMEMD_NIK_REASON := NVL(:NEW.HMEMD_NIK_REASON, 'NEW_HIRE');   -- Duplicate Employee mengisi REHIRE/dst.
IF :NEW.HMEMD_NIK IS NULL
   OR (flag = 'Y' AND CLIENT_IDENTIFIER <> 'LEGACY_NIK') THEN
   :NEW.HMEMD_NIK := :NEW.HMEMD_NIK_NEW;
END IF;
```
- Fase 1: `HMEMD_NIK` tetap dari input (seperti sekarang); hanya bridge yang terisi.
- Fase 2+: `HMEMD_NIK = HMEMD_NIK_NEW`, kecuali session `DBMS_SESSION.SET_IDENTIFIER('LEGACY_NIK')` (script migrasi yang wajib membawa NIK lama, mis. `jkt_02_insert.sql`). Setelah cutover `LEGACY_NIK` tidak boleh dipakai lagi.
- Karena BEFORE, trigger AFTER existing (`HM_EMP_DATA_TRG` → leave balance, `TRG_HMEMD_TO_PAYROLL_EMP` → payroll) otomatis menerima `HMEMD_NIK` final.

#### 3.2 `HM_EMP_DATA_TRG_NIK_UPD` (baru, BEFORE UPDATE OF HMEMD_NIK_NEW)
Tolak perubahan bila nilai lama tidak NULL (BR-03, `-20903`), kecuali `CLIENT_IDENTIFIER='NIK_ADMIN'`. Koreksi join date **tidak** mengubah NIK. Mutasi dalam company (Employee Movement) tidak menyentuh kolom NIK sama sekali (BR-14).

#### 3.3 `TRG_HMEMD_TO_PAYROLL_EMP` (ubah)
- `PE_NIK := :NEW.HMEMD_NIK` (tetap) — untuk karyawan baru/pasca cutover nilainya NIK baru 10 digit.
- `PE_SUB_ACNT := :NEW.HMEMD_NIK_NEW` bila tersedia.
- Harus **update-aware** (AFTER INSERT OR UPDATE OF HMEMD_NIK): saat cutover, `PE_NIK` karyawan aktif ikut berganti. Bila Orion/Finance menolak, cutover payroll dijalankan terpisah via mapping `HM_EMP_NIK_MAP`.
- **Syarat**: kolom payroll `VARCHAR2(5)` sudah diperlebar (impact_analysis §1.3; cek `04_cutover.sql` C0.2). Sampai itu selesai, `AUTO_ENABLED` = N.

#### 3.4 `PRC_SYNC_PAYROLL_EMP` (ubah)
Hilangkan asumsi `max(PE_NIK)` numerik (`p_nik_floor`). Ganti seleksi kandidat: `PE_NIK NOT IN (SELECT NIK_ANY FROM V_EMP_NIK_LOOKUP)` + `PE_DATE_JOIN >= trunc(sysdate)-p_lookback_days`. Job `JOB_SYNC_PAYROLL_EMP` diubah parameternya. Insert baru lewat trigger §3.1 (company wajib).

### 4. Backfill (sekali jalan, per company)
Lihat `sql/02_backfill.sql` (mode `REPORT` → review HR → `APPLY`). Tahap:
1. Akun sistem: `HMEMD_NIK LIKE CHR(191)||'%'` → `HMEMD_IS_USER_APP=1`; sisa non-karyawan (mis. `LARADEMO`) review manual (R1).
2. Lengkapi `HMEMD_JOIN_DATE` NULL dari `MGT_PAYROLL_EMP.PE_DATE_JOIN` via `TMP_JOIN_FILL`. Sisa NULL / company NULL → laporan HR (R3, R6), **tidak di-generate**.
3. `TMP_NIK_DEDUP` — grup KTP sama diklasifikasi otomatis (`GRP_CLASS`), HR mengisi `DECISION`:

   | `GRP_CLASS` | DEV | Saran | Hasil APPLY |
   |---|---|---|---|
   | `OVERLAP` (masa kerja tumpang tindih) | 55 KTP | `MERGE` + survivor | 1 NIK baru di survivor; baris lain `MERGED_ROW`, `NIK_NEW` NULL |
   | `SEQUENTIAL` (resign lalu join lagi) | 11 KTP | `SEPARATE` | tiap baris dapat NIK sendiri; baris berikutnya `HMEMD_PREV_SYS_ID` = baris sebelumnya |
   | `JOIN_NULL` | 196 KTP | — | ditahan sampai join date lengkap |
   | company beda dalam satu grup | — | selalu `SEPARATE` | = pindah company (BR-13) |

   Grup `PAYROLL_XDIV` (payroll JKT ↔ HRIS SLO, KTP sama) → `MERGE` dicatat sebagai alias `PAYROLL_NIK`.
4. Generate bulk deterministik (tidak lewat `FN_GEN_NIK`): `ROW_NUMBER() OVER (PARTITION BY comp, YYYYMM ORDER BY join_date, div_order(SLO,JKT,BDG), nik_num, sys_id)`; reason `BACKFILL`; lalu `HM_NIK_COUNTER` = max per (comp, period).
5. Isi `HM_EMP_NIK_MAP`: `HRIS` (HMEMD_NIK, **primary**), `PAYROLL_NIK`, `PAYROLL_SUBACNT` (bila ≠), `MERGED_ROW`.
6. Transisi status 02→01 di satu baris (508) **tidak dipecah** (PRD Q1) — NIK mengikuti join date baris tsb.
7. Anomali status `00` tanpa resign date (250) & status NULL (22) dilaporkan (R7), tidak memblok backfill tetapi **dikecualikan dari cutover**.
8. Verifikasi `sql/03_verify.sql` V1–V21.

### 5. PIN Mesin Absensi
- Sumber tunggal PIN = `HMEMD_USER_MACHINE_ID`. Accessor `machinePin()` = `hmemd_user_machine_id ?? ltrim(hmemd_nik,'0')` (fallback hanya pra-cutover).
- **Karyawan lama**: saat cutover, `HMEMD_USER_MACHINE_ID` yang kosong diisi `LTRIM(NIK lama,'0')` (DEV: 1.284 dari 1.308 aktif) → PIN di mesin tidak berubah, **tidak perlu re-enroll**.
- **Karyawan baru** (fase 2+): default `HMEMD_USER_MACHINE_ID` = `HMEMD_NIK_NEW`. **Verifikasi firmware ZKTeco**: bila PIN max < 10 digit (umumnya 9 pada firmware lama), isi `YYMMNNNN` (8 digit) — unik per company; untuk multi-company di mesin yang sama tambahkan prefix company numerik.
- **Duplicate Employee**: baris baru mendapat PIN baru; PIN baris lama dihapus dari mesin (`MachineUserMoveService`) setelah baris lama ditutup.
- Parsing (`ParsingAttLogService:119`): PIN → resolve `HMEMD_USER_MACHINE_ID` (karyawan aktif) dulu, fallback `LTRIM(HMEMD_NIK,'0')`, lalu simpan `hmapl_hmemd_nik = HMEMD_NIK` karyawan saat ini (bukan PIN mentah) → log pasca cutover ber-NIK baru walau PIN tetap NIK lama.
- `MachineUserMoveService`, `UserCreateService`, `UserPinEditService`, `ZkTecoUdpService`, `AdmsCommandService`: sumber PIN = `machinePin()`.

### 6. Perubahan Aplikasi (Laravel)

| Area | Perubahan |
|---|---|
| `HmEmpData` model | `$fillable` + `hmemd_nik_new`, `hmemd_prev_sys_id`, `hmemd_nik_reason`; accessor `nik_display` (`nik_new ?? nik`), `nik_legacy` (map HRIS primary), `machine_pin`; relasi `previous()`/`next()`; `hmemd_nik_new` masuk `searchable`; scope `whereNikAny($v, $comp)` (via `V_EMP_NIK_LOOKUP`). Setelah insert → `refresh()` agar nilai trigger terbaca. |
| `EmployeeForm` | Field NIK read-only (tampil setelah simpan); `hmemd_join_date` & company `required`. Hapus input NIK saat create. Tampilkan "NIK Lama" dan tautan penempatan sebelumnya. |
| `EmployeeImport` | Kolom NIK opsional & diabaikan untuk baris baru; unique check pindah ke KTP/email (maks. satu baris aktif per KTP per company); join date & company wajib. |
| `EloquentAuthRepository` (register local) | Jangan isi `hmemd_nik` dari input. |
| Employee Movement (mutasi dalam company) | Tidak mengubah NIK; validasi: company tujuan ≠ company asal → tolak, arahkan ke Duplicate Employee (`TRANSFER_COMPANY`). |
| Duplicate Employee | Lihat §12. |
| Import transaksi (Leave, Overtime, Schedule, Clarif, ShiftStatus, ShortLeave, AnnualBalance, ManualAttendance, WorkUnitEmployee, MassAssignRole) | Resolve NIK via `whereNikAny` (NIK lama **atau** baru, filter company user) → simpan `HMEMD_NIK` **saat ini** + `HMEMD_SYS_ID` bila kolom ada. Pasca cutover otomatis tersimpan NIK baru. Rule `exists:hm_emp_data,hmemd_nik` → custom rule `NikExists`. |
| Laporan/rekap lintas periode | **Resolver dua nilai** (FR-15): grouping/join via `HMEMD_SYS_ID`; untuk tabel tanpa SYS_ID (impact_analysis §1.2) join `V_EMP_NIK_LOOKUP` (`NIK_ANY` → `HMEMD_SYS_ID`). Dilarang `GROUP BY` string NIK. |
| Export/PDF/Notifikasi | `hmemd_nik` → `nik_display`; export master tambah kolom "NIK Lama". `FormatsNikForExport` tetap. |
| Komponen UI | `components/employee/profile`, `_employee-header`, dropdown "name (nik)" → `nik_display`. |
| Login (fase 2, opsional) | `AuthenticationService`: bila input 10 digit numerik → resolve `hmemd_user_id` via `V_EMP_NIK_LOOKUP` (baris aktif). `HMEMD_USER_ID` (DEV: 2.464 baris = NIK lama) **tidak diganti** — tetap alias login. |

**Kolom actor** (`created_by`, `approved_by`, dll.) menyimpan `hmemd_nik` saat transaksi terjadi → sebelum cutover NIK lama, sesudahnya NIK baru. Tampilan actor di-resolve lewat `V_EMP_NIK_LOOKUP` (bukan join langsung `= HMEMD_NIK`) agar keduanya terbaca.

---

## B. Org Scope

### 7. Grant model
`HM_EMP_AUTHORIZE_ORG` dipakai apa adanya dengan semantik baru: **kolom NULL = wildcard** (turunan dari kiri ke kanan).

| comp | div | dept | sect | makna |
|---|---|---|---|---|
| MGT | NULL | NULL | NULL | seluruh company |
| MGT | JKT | NULL | NULL | seluruh divisi |
| MGT | SLO | TXT | NULL | dept, semua section |
| MGT | SLO | TXT | WVG | section |

- `HMEATO_HCM_SYS_ID` boleh NULL untuk grant wildcard (saat ini diisi dari `HM_COMP_MAP`). Ubah UI `AuthorizeOrgMappingModal` agar bisa memilih level.
- Tambah check constraint: bila `dept` NOT NULL maka `div` NOT NULL, dst.
- Data existing (379 baris, 65 user, semua kolom terisi sampai sect) → **perilaku berubah hanya pada section**: baris existing yang punya `sect` akan mulai membatasi section. Migrasi: set `HMEATO_HMSEC_SECT_ID = NULL` untuk grant existing agar perilaku tetap (keputusan HR per user bisa dipersempit belakangan).

### 8. `App\Services\Authorization\OrgScopeService` (baru, di `app/`)
```php
interface OrgScope {
  public function bypasses(HmEmpData $user, ?string $bypassPermission): bool;
  public function grantsFor(HmEmpData $user): Collection;      // own org + authorize_org, cache per request
  public function apply(Builder $q, HmEmpData $user, array $cols, ?string $bypassPermission = null): Builder;
  // $cols = ['comp'=>'x_hmcomp_comp_id','div'=>...,'dept'=>...,'sect'=>...]
  public function allows(HmEmpData $user, string $comp, string $div, ?string $dept, ?string $sect): bool;
}
```
SQL yang dihasilkan:
```sql
WHERE ( (comp = :c1 AND (:d1 IS NULL OR div = :d1) AND (:p1 IS NULL OR dept = :p1) AND (:s1 IS NULL OR sect = :s1))
     OR (...grant 2...) )
```
(dibangun per grant dengan binding, tanpa `:x IS NULL` bila nilainya null — cukup hilangkan predikat.)

- User null → **throw/deny** (AR-04). Job export wajib `->forUser($userSysId)`.
- `AppliesEmployeeAuthorization` & `AppliesTransactionAuthorization` → delegasi ke service ini (API trait tetap agar pemanggil tidak berubah).
- Hapus salinan inline (daftar di impact_analysis §3.2).
- `HmEmpData::scopeAuthorizedFor` (pencarian approver) memakai `allows()` terbalik: cari user yang grant-nya mencakup org transaksi (support wildcard).

### 9. Hierarki SelfService
`DepartmentHierarchyResolver::resolveChainAbove` & `resolveReleasers`: tambah filter `hmemd_hmcomp_comp_id` + `hmemd_hmdiv_div_id` = milik pemohon. Release fallback "management company-wide" → "management se-company yang grant-nya mencakup divisi pemohon".

### 10. Kode lokasi hardcoded
`JournalVoucherPostingService`, `PaymentVoucherPostingService`, `TransporterPostingService`: pindahkan mapping `JAKARTA → JJV/BPJ/JKT`, default `JV/BPS/FIN` ke config `config/erp_location.php` (atau tabel master). Tidak terkait NIK; wajib bila divisi BDG aktif.

---

## C. Siklus Identitas

### 11. Cutover (Fase 3) — NIK baru menjadi ID utama
Script: `sql/04_cutover.sql <CHECK|APPLY> <yyyy-mm-dd>`. Disarankan tanggal = awal periode payroll (PRD Q3).

**Prasyarat (C0, wajib 0/OK sebelum APPLY):**
| Cek | Isi | Gate di APPLY |
|---|---|---|
| C0.1 | `AUTO_ENABLED = Y`, `CUTOVER_DATE` masih NULL | `-20910` / `-20911` |
| C0.2 | Tidak ada kolom `%NIK%`/`%SUB_ACNT%` CHAR < 10 di MGTDAT/MGTAPPS/MGTHRIS | manual (koordinasi Orion) |
| C0.3 | Setiap karyawan aktif ber-NIK_NEW punya map `HRIS` primary = NIK lama | `-20912` |
| C0.4 | `NIK_NEW` tidak bentrok dengan `HMEMD_NIK` karyawan lain di company sama | manual |
| C0.5–C0.7 | Info: aktif tanpa NIK_NEW, anomali status 00/NULL, kandidat per company | — |

Prasyarat non-DB: regresi modul (absensi, cuti, lembur, payroll, approval, import, laporan resolver dua nilai) lulus di DEV; `TRG_HMEMD_TO_PAYROLL_EMP` sudah update-aware; firmware PIN terverifikasi.

**APPLY (satu transaksi, session `NIK_ADMIN`):**
1. Backup `BAK_HM_EMP_DATA_CUT_YYYYMMDD` (SYS_ID, NIK, NIK_NEW, USER_ID, MACHINE_ID, comp, resign).
2. `HMEMD_USER_MACHINE_ID := LTRIM(HMEMD_NIK,'0')` bila kosong (PIN tetap).
3. `HMEMD_NIK := HMEMD_NIK_NEW` — filter: `RESIGN_DATE IS NULL`, status ≠ `00`, `NIK_NEW NOT NULL`. Trigger existing meneruskan ke leave balance & payroll.
4. `HM_NIK_CONFIG.CUTOVER_DATE := <tanggal>`.

**Tidak diubah:** karyawan resign (identitas historis = NIK lama), `HMEMD_USER_ID`, transaksi historis. Transaksi OPEN (draft/submitted) default tidak diubah; opsional C3 bila modul hanya bisa mencari NIK tunggal.

**Pasca cutover:**
- Verifikasi `03_verify.sql` V22–V25 + C2 (leave balance sinkron).
- `LEGACY_NIK` identifier dilarang; semua insert memakai NIK baru.
- Laporan periode melintasi cutover memakai resolver dua nilai (§6, FR-15).
- Rollback: `99_rollback.sql` blok 0a/0b (restore `HMEMD_NIK` & PIN dari backup, kosongkan `CUTOVER_DATE`) sebelum blok 0–10.

### 12. Duplicate Employee (Rehire / Kontrak→Permanen / Pindah Company)
Aksi di detail karyawan (permission `employee.duplicate`, scope §8 berlaku untuk company asal **dan** tujuan). Service `DuplicateEmployeeService::execute(sourceSysId, reason, joinDate, compId, orgTarget)` — **satu transaksi DB**.

| Reason | Company tujuan | Join date baru | Baris lama |
|---|---|---|---|
| `REHIRE` | = company asal | tanggal masuk kembali (> resign date lama) | sudah resign; tidak diubah |
| `CONTRACT_TO_PERMANENT` | = company asal | tanggal pengangkatan | ditutup: `RESIGN_DATE = join baru − 1`, alasan "Diangkat permanen" |
| `TRANSFER_COMPANY` | ≠ company asal | tanggal efektif di company tujuan | ditutup: `RESIGN_DATE = join baru − 1`, alasan "Pindah company" |

Mutasi **dalam** company (divisi/dept/section/jabatan) **bukan** di sini — pakai Employee Movement; NIK tetap (BR-14).

**Langkah service:**
1. Validasi: reason ↔ company (tabel di atas); join date wajib; tidak ada baris aktif lain dengan KTP sama di company tujuan (selain baris asal yang akan ditutup); lock baris asal `SELECT … FOR UPDATE`.
2. Tutup baris lama (bila perlu) — update resign date & status; hapus user dari mesin (`MachineUserMoveService`); nonaktifkan akun login lama.
3. Insert baris baru: salin data pribadi (nama, KTP, alamat, keluarga, pendidikan, rekening, BPJS — sesuai PRD Q2); isi org tujuan, `HMEMD_JOIN_DATE`, `HMEMD_HMCOMP_COMP_ID`, `HMEMD_PREV_SYS_ID = source`, `HMEMD_NIK_REASON = reason`; **jangan** isi `HMEMD_NIK`/`HMEMD_NIK_NEW` → trigger §3.1 generate dari seri company tujuan.
4. `refresh()` → trigger existing membuat leave balance & payroll emp baru; set `HMEMD_USER_MACHINE_ID` (§5); buat akun login baru (`HMEMD_USER_ID` baru; akun lama tidak dipakai ulang).
5. Audit log (source, target, reason, user).
Gagal di langkah mana pun → rollback penuh (counter ikut rollback, tidak ada nomor loncat).

**Tampilan:** detail karyawan menampilkan rantai penempatan (`previous()`/`next()`), masing-masing dengan NIK, company, periode kerja. Masa kerja per baris; penggabungan masa kerja (bila kebijakan HR) dihitung dari rantai, bukan dari NIK.

**Verifikasi:** `03_verify.sql` V19 (reason tanpa prev), V20 (relasi prev tidak valid), V21 (baris lama masih aktif) = 0; V16 (NIK ambigu antar karyawan aktif) = 0.
