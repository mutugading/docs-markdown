# Plan — Fase, Deployment, Cutover, Rollback

## Fase

| Fase | Isi | Prasyarat | Risiko data |
|---|---|---|---|
| **0. Persiapan data** | Cleansing per company: akun sistem, join date NULL (283 aktif), grup KTP (`OVERLAP` 55 / `SEQUENTIAL` 11 / `JOIN_NULL` 196), NIK duplikat (3), anomali status 00 tanpa resign (250) & NULL (22). Output: daftar keputusan HR (`TMP_NIK_DEDUP.DECISION`). | Migrasi `MIGRASI_JKT` (112) sudah masuk DEV | nol (read-only + tabel kerja) |
| **1. Bridge NIK** | DDL (`01_ddl.sql`: kolom, `HM_NIK_COUNTER` per company+bulan, `HM_NIK_CONFIG`, map, view, `FN_GEN_NIK`, trigger), backfill (`02_backfill.sql`), verifikasi (`03_verify.sql`). Aplikasi: tampil `nik_display`, pencarian NIK lama/baru. `AUTO_ENABLED=N` → karyawan baru hanya dapat `HMEMD_NIK_NEW`. | Fase 0 disetujui HR | rendah — tidak ada update nilai kolom existing |
| **2. Auto-NIK + Duplicate Employee** | Perlebar kolom MGTDAT/MGTAPPS `VARCHAR2(5)`; `TRG_HMEMD_TO_PAYROLL_EMP` update-aware, `PRC_SYNC_PAYROLL_EMP`; PIN via `MACHINE_ID`; form/import tanpa input NIK, company & join date wajib; Employee Movement menolak pindah company; **Duplicate Employee** (rehire / kontrak→permanen / pindah company). Set `AUTO_ENABLED=Y` → karyawan baru `HMEMD_NIK = HMEMD_NIK_NEW`. | Tim Orion setuju; payroll paralel 1 periode di DEV | sedang — payroll |
| **3. Cutover identity** | `04_cutover.sql` CHECK → APPLY: `HMEMD_NIK` seluruh karyawan aktif := NIK baru; PIN kosong := NIK lama; `CUTOVER_DATE`. Sejak tanggal ini **NIK baru = ID di semua modul**. Laporan lintas periode memakai resolver dua nilai. | Fase 2 stabil ≥ 1 periode payroll; C0.1–C0.4 lulus; regresi modul; anomali status dibereskan HR; tanggal disetujui (PRD Q3) | **tinggi** — semua modul |
| **4. Org Scope** | `OrgScopeService`, refactor trait & salinan inline, tutup gap scope, UI grant wildcard, hierarki SelfService comp+div. | Bisa paralel sejak fase 1 | sedang — visibilitas |
| **5. Opsional** | Login alias NIK baru; selector company/division aktif; config lokasi ERP; retire kolom legacy. | | |

## Deployment Fase 1–2 (DEV `ALTHARADEV` dulu → PROD `ALTHARA`)

1. Freeze input master karyawan (±30 menit) — di luar shift & di luar `JOB_SYNC_PAYROLL_EMP` 02:00.
2. `CREATE TABLE BAK_HM_EMP_DATA_<yyyymmdd> AS SELECT * FROM HM_EMP_DATA` (+ `HM_EMP_AUTHORIZE_ORG`).
3. Jalankan `01_ddl.sql` → `02_backfill.sql` (REPORT → review HR → APPLY, per company) → `03_verify.sql` V1–V21. Semua cek harus 0 anomali.
4. Deploy aplikasi (migration Laravel hanya untuk menu/permission; DDL NIK dijalankan DBA karena menyentuh trigger lintas schema).
5. Smoke test: create karyawan dummy (lalu hapus), cari NIK lama & baru, absen mesin 1 karyawan lama, approval 1 cuti, export master; fase 2: Duplicate Employee dummy (REHIRE, C2P, TRANSFER_COMPANY).
6. Buka freeze.

## Cutover Fase 3 (sekali, per environment)

| Waktu | Langkah |
|---|---|
| H-14 | `04_cutover.sql CHECK` di PROD; kirim hasil C0.5–C0.7 (aktif tanpa NIK baru, anomali status) ke HR |
| H-7 | Regresi penuh di DEV setelah cutover DEV: absensi, cuti, lembur, schedule, payroll, approval, import, laporan lintas periode, Orion |
| H-1 | Freeze input master & transaksi; tunggu job sync selesai; export DDL trigger/procedure |
| H-0 | `04_cutover.sql APPLY <tanggal>` (backup `BAK_HM_EMP_DATA_CUT_YYYYMMDD` otomatis) → `03_verify.sql` V22–V25 + C2 |
| H-0 | Smoke: tap mesin karyawan lama (PIN lama → log ber-NIK baru), ajukan cuti, cek `PE_NIK` payroll, login |
| H+1…H+30 | Monitor absensi harian & payroll periode pertama; laporan rekap bulan cutover = 1 baris per karyawan |

Tanggal disarankan = awal periode payroll agar satu periode tidak tercampur NIK lama/baru di payroll.

## Rollback

| Kondisi | Langkah |
|---|---|
| Aplikasi | Revert release (accessor `nik_new ?? nik` aman terhadap kolom NULL). |
| **Setelah cutover** | `99_rollback.sql` blok **0a/0b**: restore `HMEMD_NIK` & `HMEMD_USER_MACHINE_ID` dari `BAK_HM_EMP_DATA_CUT_YYYYMMDD`, kosongkan `CUTOVER_DATE`. Transaksi yang terlanjur ber-NIK baru (H-0 s/d rollback) di-resolve via `HMEMD_SYS_ID` / map; bila perlu di-update balik dengan daftar dari backup. Batas aman: sebelum payroll periode pertama diproses. |
| Setelah fase 1–2 | `99_rollback.sql` blok 0–10: drop trigger baru, view, `HM_EMP_NIK_MAP`, `HM_NIK_COUNTER`, `HM_NIK_CONFIG`, fungsi, constraint, kolom `HMEMD_NIK_NEW`/`PREV_SYS_ID`/`NIK_REASON`; restore grant section dari backup; restore `TRG_HMEMD_TO_PAYROLL_EMP` / `PRC_SYNC_PAYROLL_EMP` dari DDL export. |
| Catatan | Karyawan baru ber-NIK 10 digit (fase 2) & baris hasil Duplicate Employee **tetap valid** setelah rollback (hanya kehilangan kolom bridge). Perluasan VARCHAR2 tidak di-rollback. |

## Risiko & Mitigasi
| # | Risiko | Mitigasi |
|---|---|---|
| R1 | Merge orang salah | Merge hanya dari daftar approve HR; baris non-survivor tidak dihapus |
| R2 | Join date / company salah → NIK salah (immutable) | Cleansing fase 0; koreksi join date tidak mengubah NIK; koreksi NIK hanya via `NIK_ADMIN` |
| R3 | Firmware mesin menolak PIN 10 digit | Uji 1 mesin tiap model; fallback PIN 8 digit `YYMMNNNN`; karyawan lama tetap PIN lama |
| R4 | PL/SQL payroll hardcode `VARCHAR2(5)` | Grep `ALL_SOURCE` MGTDAT sebelum fase 2; C0.2 sebelum cutover |
| R5 | Visibilitas berubah setelah OrgScope (section) | Set section grant existing = NULL saat migrasi; komunikasikan ke 65 user pemegang grant |
| R6 | Job export tanpa user sekarang bypass | Ubah default ke deny; semua dispatch kirim user |
| R7 | Legacy ASMAR/ASMAT `VARCHAR2(4)` | Konfirmasi masih dipakai; bila ya perlebar atau kecualikan karyawan baru |
| R8 | Laporan lintas cutover pecah jadi dua orang | Resolver dua nilai (FR-15); review query yang `GROUP BY` / join string NIK |
| R9 | Anomali status 00 tanpa resign (250) ikut dianggap aktif | HR melengkapi resign date sebelum cutover; script mengecualikan status 00 |
| R10 | Duplicate Employee meninggalkan dua baris aktif | Service satu transaksi + V21; KTP maks. satu aktif per company |
| R11 | Integrasi eksternal (Orion, mesin, Excel user) masih mengirim NIK lama setelah cutover | Import menerima NIK lama/baru via lookup; komunikasi ke user & tim Orion H-14 |
