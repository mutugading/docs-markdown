# Design — Application Flow & User Access

## A. Identitas Karyawan (konsep)

```
          HM_EMP_DATA (1 baris = 1 penempatan: 1 company, 1 periode kerja)
  ┌────────────────────────────────────────────────────────────────────────┐
  │ HMEMD_SYS_ID        PK teknis, FK 39 tabel                  tidak berubah │
  │ HMEMD_NIK           kunci operasional semua modul                          │
  │                       fase 1–2: NIK lama (karyawan lama)                    │
  │                       fase 3  : = HMEMD_NIK_NEW (cutover)                   │
  │ HMEMD_NIK_NEW       YYYYMMNNNN, UNIQUE (company, NIK_NEW)   BARU, immutable │
  │ HMEMD_PREV_SYS_ID   penempatan sebelumnya (rehire/C2P/pindah) BARU        │
  │ HMEMD_NIK_REASON    BACKFILL|NEW_HIRE|REHIRE|CONTRACT_TO_PERMANENT|        │
  │                     TRANSFER_COMPANY                          BARU        │
  │ HMEMD_USER_ID       akun login (tidak diganti massal)                     │
  │ HMEMD_USER_MACHINE_ID PIN mesin (karyawan lama = NIK lama sejak cutover)  │
  │ COMP / DIV / DEPT / SECT (org saat ini)                                   │
  └────────────────────────────────────────────────────────────────────────┘
      │ 1..n                         │ n..1 (PREV_SYS_ID)
      ▼                              ▼
  HM_EMP_NIK_MAP                 baris penempatan lama (resign / ditutup)
  (NIK lama: HRIS primary, PE_NIK, PE_SUB_ACNT, MERGED_ROW)

  HM_NIK_COUNTER (company, period YYYYMM, last_no) ── FN_GEN_NIK ──► NIK_NEW
  HM_NIK_CONFIG  (AUTO_ENABLED, CUTOVER_DATE)
```

**Satu orang, banyak penempatan:**
```
KTP 3372…  ─┬─ [MGT] 2019040007  2019-04-01 … 2024-06-30  reason BACKFILL
            ├─ [MGT] 2026110001  2026-11-02 … (aktif)      reason REHIRE,  PREV = baris 1
            └─ [ABC] 2027010001  2027-01-01 … (aktif)      reason TRANSFER_COMPANY, PREV = baris 2 (baris 2 ditutup 2026-12-31)
```
Mutasi divisi/dept/section di dalam MGT **tidak** menambah baris (NIK tetap).

| Pertanyaan | Kolom yang dipakai |
|---|---|
| Join teknis antar tabel HRIS | `HMEMD_SYS_ID` |
| Join ke transaksi/absen/payroll existing | `HMEMD_NIK` (nilai saat transaksi dibuat) — untuk rekap lintas cutover pakai `HMEMD_SYS_ID` / `V_EMP_NIK_LOOKUP` |
| Ditampilkan ke user / dicetak | `HMEMD_NIK_NEW` (fallback `HMEMD_NIK`); "NIK Lama" dari map HRIS primary |
| Pencarian | NIK baru ∪ NIK ∪ `HM_EMP_NIK_MAP` (`V_EMP_NIK_LOOKUP`) + filter company |
| Mesin absensi | `HMEMD_USER_MACHINE_ID` → fallback `LTRIM(HMEMD_NIK,'0')` (pra-cutover) |
| Riwayat orang | rantai `HMEMD_PREV_SYS_ID` (+ KTP) |

### Fase

| Fase | `AUTO_ENABLED` | `CUTOVER_DATE` | Karyawan lama `HMEMD_NIK` | Karyawan baru `HMEMD_NIK` | Transaksi baru |
|---|---|---|---|---|---|
| 1 Bridge | N | NULL | NIK lama | input lama (NIK_NEW di bridge) | NIK lama |
| 2 Auto | Y | NULL | NIK lama | = NIK_NEW | campuran (lama/baru) |
| 3 Cutover | Y | tanggal | **= NIK_NEW** (aktif) | = NIK_NEW | **NIK baru** semua modul |

## B. Application Flow

### F1 — Hire karyawan baru
```mermaid
sequenceDiagram
  actor HR
  participant UI as EmployeeForm (Livewire)
  participant S as EmployeeService/Repo
  participant DB as HM_EMP_DATA triggers
  participant P as MGT_PAYROLL_EMP
  HR->>UI: isi data (tanpa NIK), company & join date wajib
  UI->>S: save()
  S->>DB: INSERT
  DB->>DB: TRG01 → SYS_ID
  DB->>DB: TRG_NIK → FN_GEN_NIK('MGT', join) = 2026100001 (lock counter MGT/202610)
  DB->>DB: REASON := NEW_HIRE; fase 2+: NIK := NIK_NEW
  DB->>DB: HM_EMP_DATA_TRG → leave balance
  DB->>P: TRG_HMEMD_TO_PAYROLL_EMP (PE_NIK = 2026100001)
  S->>S: refresh() → baca NIK hasil trigger
  UI-->>HR: tampil "NIK 2026100001"
  HR->>UI: (opsional) buat akun login & enroll mesin (PIN = MACHINE_ID)
```
Error: company kosong → ORA-20904; join date kosong → ORA-20902 ("Tanggal join wajib diisi untuk membuat NIK"); seri penuh → ORA-20901.

### F2 — Import karyawan (Excel)
Sama seperti F1 per baris. Kolom NIK di template dihapus (atau diabaikan untuk baris baru). Duplikat dideteksi dengan KTP (maks. satu baris aktif per KTP per company) / email. Baris dengan KTP yang pernah resign → tolak dan arahkan ke Duplicate Employee (F9) agar `PREV_SYS_ID` & reason tercatat.

### F3 — Backfill karyawan lama (sekali, oleh DBA, per company)
```
[1] snapshot tabel  →  [2] flag akun sistem  →  [3] lengkapi join date dari payroll
   →  [4] REPORT: sisa NULL join/company, grup KTP (OVERLAP / SEQUENTIAL / JOIN_NULL), anomali status
   →  HR review → isi DECISION (MERGE / SEPARATE) di TMP_NIK_DEDUP
   →  [5] APPLY: generate NIK_NEW bulk per (company, bulan), reason BACKFILL
   →  [6] SEPARATE: set PREV_SYS_ID antar baris berurutan  →  [7] set HM_NIK_COUNTER per (company, period)
   →  [8] isi HM_EMP_NIK_MAP (HRIS primary, payroll, merged)  →  [9] 03_verify  →  [10] aktifkan trigger NIK
```
Tidak ada UPDATE ke `HMEMD_NIK` maupun tabel transaksi di fase ini.

### F4 — Karyawan dengan 2 NIK lama (SLO ↔ JKT, periode overlap)
```
HR memilih survivor (baris aktif) di daftar TMP_NIK_DEDUP (GRP_CLASS = OVERLAP → MERGE)
 ├─ survivor: HMEMD_NIK_NEW = 2006030012
 ├─ baris lain: HMEMD_NIK_NEW = NULL, tetap ada (transaksi lama tetap tertaut SYS_ID-nya)
 └─ HM_EMP_NIK_MAP: 0686 (HRIS,SLO,primary) | 2006-0686 (PAYROLL_NIK,JKT) | 13138 (PAYROLL_SUBACNT,JKT) | <sys_id lain> (MERGED_ROW)
```
Grup `SEQUENTIAL` (resign lalu join lagi) → `SEPARATE`: masing-masing NIK sendiri + `PREV_SYS_ID`. Grup beda company → selalu `SEPARATE`. Laporan per orang (riwayat lintas divisi) = union transaksi dari semua SYS_ID dalam map + rantai PREV.

### F5 — Mutasi divisi / department / section (company sama)
Employee Movement mengubah DIV/DEPT/SECT/jabatan → **NIK tidak berubah**, tidak ada baris baru. Transaksi baru membawa org baru (denormalized). Visibilitas: transaksi lama mengikuti org di transaksi (admin divisi lama tetap bisa melihat riwayat di divisinya). Bila user memilih company tujuan berbeda → form menolak dan mengarahkan ke F9 (`TRANSFER_COMPANY`).

### F6 — Login
```
input user id / email → (fase 2+: bila 10 digit & cocok NIK_NEW baris aktif → hmemd_user_id)
  → PKG_PASSWORD_APPS.fCheckUserPwd → Auth::login(HmEmpData)
  → load roles/permissions + OrgScope grants (cache per request)
```
Setelah Duplicate Employee, akun lama dinonaktifkan; login memakai akun baris baru.

### F7 — Absensi mesin
```
mesin (PIN) → FetchAttendanceLogs → ParsingAttLogService
   resolve PIN: HMEMD_USER_MACHINE_ID = PIN (baris aktif) ? : LTRIM(HMEMD_NIK,'0') = LTRIM(PIN,'0')
   → simpan hmapl_hmemd_nik = HMEMD_NIK karyawan saat ini → actualization
```
Pasca cutover PIN karyawan lama tetap NIK lama (tidak re-enroll), tetapi log tersimpan dengan NIK baru.

### F8 — Sync payroll (`JOB_SYNC_PAYROLL_EMP`)
Payroll → HRIS: kandidat = `PE_NIK` yang tidak ada di `V_EMP_NIK_LOOKUP` dan join baru → insert HM_EMP_DATA (company wajib, NIK baru auto) → map `PAYROLL_NIK`. HRIS → Payroll: trigger `TRG_HMEMD_TO_PAYROLL_EMP` (insert & update `HMEMD_NIK`).

### F9 — Duplicate Employee (rehire / kontrak→permanen / pindah company)
```mermaid
sequenceDiagram
  actor HR
  participant UI as Employee Detail → "Duplicate Employee"
  participant S as DuplicateEmployeeService
  participant DB as HM_EMP_DATA + triggers
  participant M as Mesin / Akun
  HR->>UI: pilih reason, join date baru, company & org tujuan
  UI->>S: execute(sourceSysId, reason, joinDate, compId, org)
  S->>S: validasi reason↔company, KTP aktif ganda, scope asal & tujuan
  S->>DB: BEGIN; SELECT baris asal FOR UPDATE
  alt CONTRACT_TO_PERMANENT / TRANSFER_COMPANY
    S->>DB: UPDATE baris asal: RESIGN_DATE = join−1, status tutup
  end
  S->>DB: INSERT baris baru (data pribadi disalin, PREV_SYS_ID, REASON, tanpa NIK)
  DB->>DB: TRG_NIK → FN_GEN_NIK(company tujuan, join baru) → NIK baru
  DB->>DB: leave balance & payroll emp baru
  S->>DB: COMMIT
  S->>M: hapus PIN lama dari mesin, enroll PIN baru; nonaktifkan akun lama, buat akun baru
  UI-->>HR: "NIK baru 2026100003 (sebelumnya 2025010015)"
```
Gagal di tengah → rollback penuh (counter ikut rollback). Langkah mesin/akun di luar transaksi DB → di-queue dan dapat diulang (idempotent).

| Reason | Company | Contoh |
|---|---|---|
| `REHIRE` | sama | `2019040007` (resign 2024-06-30) → `2026110001` |
| `CONTRACT_TO_PERMANENT` | sama | `2025010015` ditutup 2026-09-30 → `2026100003` |
| `TRANSFER_COMPANY` | beda | MGT `2022050002` ditutup 2026-12-31 → ABC `2027010001` |

### F10 — Cutover identity (sekali, oleh DBA)
```
[1] 04_cutover.sql CHECK  → C0.1–C0.7 (flag, kolom lebar, map primary, bentrok, anomali)
[2] HR bereskan anomali status 00 / NULL, regresi modul lulus, freeze input H-0
[3] 04_cutover.sql APPLY <tanggal>
     backup BAK_HM_EMP_DATA_CUT_YYYYMMDD → PIN := NIK lama (bila kosong)
     → HMEMD_NIK := NIK_NEW (aktif, status ≠ 00) → CUTOVER_DATE
[4] 03_verify.sql V22–V25 + C2  → [5] buka input; monitor absensi/payroll periode pertama
```
Rollback: `99_rollback.sql` 0a/0b (restore dari backup).

### F11 — Laporan lintas cutover (resolver dua nilai)
```
transaksi (NIK lama, < cutover) ─┐
                                 ├─► V_EMP_NIK_LOOKUP / HMEMD_SYS_ID ─► 1 karyawan ─► rekap
transaksi (NIK baru, ≥ cutover) ─┘
```
Contoh: rekap cuti 2026 untuk karyawan `0686` → Jan–Sep ber-NIK `0686`, Okt–Des ber-NIK `2006030012` → tampil satu baris "2006030012 (NIK lama 0686)".

## C. User Access

### C1. Dua lapis otorisasi
```
Request ──► [1] Route middleware  permission:<module>-<page>-view      (Spatie, tidak berubah)
        ──► [2] Aksi              $this->authorize('...-create/approve') (Spatie, tidak berubah)
        ──► [3] Data              OrgScopeService::apply(query, user)    (BARU / dipusatkan)
```

### C2. Resolusi grant
```
grants(user) = { (user.comp, user.div, user.dept, NULL) }            -- org sendiri
             ∪ { (comp, div?, dept?, sect?) FROM HM_EMP_AUTHORIZE_ORG } -- NULL = wildcard
bypass       = hasRole('Super Admin') OR can('<module>-<page>-view_all_scope')
```
Untuk approval queue: bypass hanya Super Admin; tetap exclude transaksi milik sendiri — cocokkan via `HMEMD_SYS_ID` bila kolom ada, selain itu `created_by IN (NIK lama, NIK baru)` user (resolver `V_EMP_NIK_LOOKUP`), karena actor sebelum cutover tersimpan NIK lama.

### C3. Contoh
| User | Grant | Melihat |
|---|---|---|
| Andi (SLO/TXT) | — | karyawan SLO/TXT |
| Budi Admin HR JKT | `MGT/JKT/*/*` | semua JKT + dept sendiri |
| Citra Spv Weaving | `MGT/SLO/TXT/WVG` | section WVG (+ dept sendiri bila bukan TXT) |
| HR Pusat | `MGT/*/*/*` | semua MGT |
| Super Admin | bypass | semua |

### C4. Penerapan per jenis halaman
| Jenis | Kolom scope | Contoh |
|---|---|---|
| Master karyawan, dropdown picker | `hmemd_hm*` | MasterEmployee, getAuthorizedEmployees, AttMachineUsers |
| Transaksi | kolom org denormalized transaksi (`htelvh_hm*`) | Leave, Overtime, Schedule |
| Log/absensi (hanya NIK) | join `V_EMP_NIK_LOOKUP` (`NIK_ANY` + `COMP_ID`) → `HM_EMP_DATA` → org karyawan (NIK lama & baru terbaca) | ParsedLog, ManualAttendance |
| Duplicate Employee | scope user wajib mencakup org asal **dan** org tujuan | DuplicateEmployeeService |
| Export/Job | sama dengan halaman, user dikirim eksplisit | ExportEmployees |
| Import | validasi tiap baris: `allows(user, org karyawan target)` | semua `Imports/Transactions/*` |
| Report SQL | binding `:p_scope_*` otomatis / wrapper subquery ke daftar SYS_ID | ReportRunService |

### C5. UI pengelolaan grant
`EmployeeAuthorizeOrgForm` → modal pilih level: **Company / Division / Department / Section**, field di kanan level ter-disable (NULL). Tampilkan ringkasan "Scope efektif" di detail karyawan.
