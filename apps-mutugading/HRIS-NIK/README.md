# HRIS-NIK — Standarisasi NIK & Hak Akses Multi Company/Division

Paket dokumentasi untuk developer terkait:
1. **NIK baru format `YYYYMMNNNN`** (tahun+bulan join + nomor urut **per company**) untuk seluruh karyawan. NIK lama tetap bisa dirujuk (bridge + map), dan sejak cutover NIK baru menjadi ID di semua modul.
2. **Hak akses berbasis organisasi** (Company → Division → Department → Section) untuk operasi multi-company / multi-division.

| Dokumen | Isi |
|---|---|
| [PRD_HRIS_NIK.md](PRD_HRIS_NIK.md) | Latar belakang, tujuan, scope, aturan bisnis, skenario, acceptance criteria, open questions |
| [spec.md](spec.md) | Spesifikasi teknis: data model, algoritma generate, trigger, perubahan kode, cutover, Duplicate Employee |
| [design.md](design.md) | Application flow (hire, backfill, mutasi, login, absensi, payroll, duplicate, cutover) & desain user access |
| [impact_analysis.md](impact_analysis.md) | Inventaris dampak: tabel/kolom DB, objek PL/SQL, file repo, dampak cutover |
| [plan.md](plan.md) | Fase 0–5, deployment DEV → PROD, cutover, rollback, risiko |
| [tasks.md](tasks.md) | Breakdown task untuk developer |
| [sql/](sql/) | `01_ddl.sql`, `02_backfill.sql`, `03_verify.sql`, `04_cutover.sql`, `99_rollback.sql` |

**Sumber data analisa:** DB `ALTHARADEV` (read-only, snapshot 2026-09-28) dan repo `apps-mutugading` (branch main).

> Keputusan inti (ringkas):
> - NIK = `YYYYMM` bulan join + 4 digit urut, **seri per company per bulan** di tabel bantu `HM_NIK_COUNTER (company, period, last_no)`; di-generate trigger DB (`FN_GEN_NIK`). Unik per `(company, NIK)`.
> - Kolom bridge `HM_EMP_DATA.HMEMD_NIK_NEW` diisi via backfill; NIK lama disimpan di `HM_EMP_NIK_MAP` (termasuk kasus 1 orang >1 NIK lama SLO/JKT/payroll).
> - Fase 2: karyawan baru `HMEMD_NIK = HMEMD_NIK_NEW`. **Fase 3 (cutover)**: `HMEMD_NIK` seluruh karyawan aktif := NIK baru → semua modul memakai NIK baru; transaksi historis tetap (relasi aman via `HMEMD_SYS_ID`), laporan lintas periode memakai resolver dua nilai.
> - **Rehire, kontrak→permanen, pindah company = penempatan baru ber-NIK baru** via fitur Duplicate Employee (`HMEMD_PREV_SYS_ID`, `HMEMD_NIK_REASON`). Mutasi divisi/dept/section dalam company **NIK tetap**.
> - Hak akses: satu `OrgScopeService` terpusat menggantikan ±8 salinan logic, mendukung wildcard level & section.
