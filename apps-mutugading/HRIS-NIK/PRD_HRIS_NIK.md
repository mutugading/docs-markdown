# PRD — Standarisasi NIK Karyawan & Hak Akses Multi Company/Division

| | |
|---|---|
| Modul | HRIS (Hr, Mis, SelfService, Core, Auth) |
| Schema utama | `MGTHRIS` (+ dampak `MGTDAT`, `MGTAPPS`) |
| Status | Draft untuk review developer |
| Tanggal | 2026-09-28 |

---

## 1. Latar Belakang

1. Format NIK saat ini tidak seragam:
   | Kelompok | Contoh | Jumlah (DEV) |
   |---|---|---|
   | SOLO numerik 4 digit | `0002` … `2929` | ±2.761 |
   | JAKARTA numerik 5–7 digit | `12157`, `99050` | 23 (+112 migrasi `MIGRASI_JKT`) |
   | Payroll JAKARTA | `2006-0686` (`9999-9999`) | 111 |
   | Expat | `EX0012` | 14 |
   | Berbasis nama | `NARAYAN`, `SUNIL` | beberapa |
   | Akun sistem / non-karyawan | `¿FIN11`, `¿AUDITOR`, `LARADEMO` | ±40 |
2. `HMEMD_NIK` **tidak unik** (tidak ada constraint; 3 NIK duplikat di DEV) dan 2 baris NULL.
3. Satu orang bisa punya >1 identitas: ±90 karyawan JAKARTA di payroll juga tercatat di SOLO (KTP sama / NIK JKT = tahun + NIK SLO). 262 nomor KTP muncul di lebih dari satu baris `HM_EMP_DATA`.
4. NIK dipakai luas sebagai kunci: transaksi absensi/lembur/cuti, PIN mesin fingerprint, payroll (`MGTDAT`), login (`HMEMD_USER_ID` = NIK pada 2.464 akun), kolom `created_by`/actor.
5. Aplikasi mulai dipakai lintas divisi (SOLO, JAKARTA, BANDUNG) dan disiapkan untuk multi-company, tetapi hak akses data baru sebagian dan tidak konsisten (lihat §7).
6. Status kepegawaian & penempatan (DEV, `HMEMD_KTP_NO` sama di >1 baris):
   | Pola | Jumlah | Makna |
   |---|---|---|
   | `SEQUENTIAL` | 11 KTP | baris lama resign, baris baru join setelahnya → rehire / kontrak→permanen |
   | `OVERLAP` | 55 KTP | dua baris aktif bersamaan → duplikat data (SLO↔JKT) |
   | `JOIN_NULL` | 196 KTP | minimal satu baris tanpa join date → belum bisa diputuskan |
   | Transisi status 02 (Kontrak) → 01 (Permanen) dalam satu baris | 508 | historis tidak dipecah (open question HR, §10) |
   | Status `00` (Resign) tetapi `HMEMD_RESIGN_DATE` NULL | 250 | anomali — terhitung aktif |
   | Status NULL | 22 | anomali |

## 2. Tujuan

| # | Tujuan | Ukuran keberhasilan |
|---|---|---|
| G1 | 1 **penempatan** = 1 NIK baru, unik per company | Tiap baris karyawan riil punya tepat satu `HMEMD_NIK_NEW`, unik secara constraint `(company, NIK)` |
| G2 | Format NIK standar `YYYYMMNNNN` | 100% karyawan (aktif & non-aktif) ter-backfill; karyawan baru auto-generate dari join date + company |
| G3 | NIK lama tetap dapat dirujuk | Mapping lama ↔ baru tersedia; transaksi historis tetap terbaca via `HMEMD_SYS_ID` / map |
| G4 | Setelah cutover, **NIK baru menjadi ID utama di semua modul** | `HMEMD_NIK` karyawan aktif = `HMEMD_NIK_NEW`; payroll, absensi, cuti, lembur, approval, actor memakai NIK baru sejak tanggal cutover |
| G5 | Hak akses data per Company/Division/Department/Section | Semua halaman berisi data karyawan ter-scope via satu service terpusat |

## 3. Non-Goals

- Tidak mass-update NIK pada **data transaksi historis** (< tanggal cutover) di ±40 tabel/47 kolom `%NIK%`. Historis tetap menyimpan NIK lama dan di-resolve lewat `HMEMD_SYS_ID` (39 FK) atau `V_EMP_NIK_LOOKUP`. Transaksi **baru** (≥ cutover) otomatis memakai NIK baru karena membaca `HMEMD_NIK`.
- Tidak mengubah kunci payroll Orion (`MGTDAT`) untuk periode historis.
- Tidak memecah ulang riwayat kontrak→permanen yang sudah terjadi di satu baris (508 kasus) — menunggu keputusan HR (§10).
- Tidak mencakup tenaga outsourcing (`MGTATTD` `OS_NIK`/`TA_NIK` NUMBER).
- Tidak mengubah mekanisme password (`PKG_PASSWORD_APPS`) dan tidak mengganti `HMEMD_USER_ID` (login) massal.
- Tidak membangun "switch company" UI penuh pada fase ini (opsional, lihat §7.5).

## 4. Aturan Bisnis NIK Baru

| Kode | Aturan |
|---|---|
| BR-01 | Format **`YYYYMMNNNN`** 10 karakter numerik. `YYYY`=tahun join, `MM`=bulan join, `NNNN`=nomor urut 0001–9999 **per company per bulan join**, reset tiap bulan. |
| BR-02 | Seri disimpan di tabel bantu `HM_NIK_COUNTER (company, period, last_no)`. Company berbeda = seri berbeda; NIK yang sama boleh ada di dua company. Keunikan = `(HMEMD_HMCOMP_COMP_ID, HMEMD_NIK_NEW)`. |
| BR-03 | NIK baru **immutable** untuk satu penempatan: tidak berubah walau join date dikoreksi atau mutasi divisi/department/section **dalam company yang sama**. |
| BR-04 | Karyawan baru: NIK **wajib auto-generate oleh sistem** dari join date + company; user tidak bisa mengetik NIK. Join date & company wajib diisi saat create. |
| BR-05 | Karyawan lama: di-backfill sekali per company. Urutan nomor dalam satu (company, bulan): join date ASC → divisi (SLO, JKT, BDG) → NIK lama numerik ASC → SYS_ID. |
| BR-06 | Sumber join date backfill: `HMEMD_JOIN_DATE` → `MGT_PAYROLL_EMP.PE_DATE_JOIN` → input manual HR. Baris tanpa join date atau company **tidak di-backfill** sampai dilengkapi (daftar dikirim ke HR). |
| BR-07 | Akun sistem / non-karyawan (prefix `¿`, `LARADEMO`, dll.) **tidak** mendapat NIK baru. Penanda memakai flag existing `HMEMD_IS_USER_APP = 1`; baris `¿…` yang belum ber-flag di-set saat backfill. |
| BR-08 | Duplikat data (KTP sama, **periode kerja overlap**) → satu NIK baru pada baris survivor; baris lain menjadi alias di `HM_EMP_NIK_MAP` (`MERGED_ROW`). Keputusan merge **dikonfirmasi HR**. |
| BR-09 | Tahapan identitas: **fase 1–2** `HMEMD_NIK` karyawan lama tetap NIK lama, karyawan baru `HMEMD_NIK = HMEMD_NIK_NEW`. **Fase 3 (cutover)** `HMEMD_NIK` seluruh karyawan aktif diganti `HMEMD_NIK_NEW`; NIK lama disimpan di map (`HRIS`, primary). Sejak tanggal cutover semua modul memakai NIK baru. |
| BR-10 | Tampilan UI, export, slip, notifikasi menampilkan **NIK baru** sebagai "NIK"; NIK lama ditampilkan sebagai "NIK Lama" di detail karyawan dan dapat dipakai untuk pencarian. |
| BR-11 | **Rehire** (keluar lalu masuk lagi, company sama) → **NIK baru** berdasarkan join date baru; baris baru ditautkan ke baris lama (`HMEMD_PREV_SYS_ID`), reason `REHIRE`. |
| BR-12 | **Kontrak → Permanen** → **NIK baru** berdasarkan join date permanen; baris kontrak ditutup (resign date = H-1), reason `CONTRACT_TO_PERMANENT`. |
| BR-13 | **Mutasi antar company** = karyawan baru di company tujuan → NIK baru dari seri company tujuan, reason `TRANSFER_COMPANY`; baris di company asal ditutup. |
| BR-14 | **Mutasi dalam company** (divisi/dept/section/jabatan) via Employee Movement → **NIK tetap**, tidak ada baris baru. |
| BR-15 | BR-11/12/13 dijalankan lewat fitur **Duplicate Employee** (bukan input manual NIK). Satu KTP boleh punya banyak baris, tetapi maksimal **satu baris aktif** per company. |

## 5. Skenario

### S1 — Karyawan baru (hire)
HR mengisi form tanpa NIK, company `MGT`, join `2026-10-05` → simpan → trigger memanggil `FN_GEN_NIK('MGT', 2026-10-05)` → `2026100001` (reason `NEW_HIRE`). Setelah `AUTO_ENABLED=Y`, `HMEMD_NIK` = `HMEMD_NIK_NEW` → tersalin ke payroll (`PE_NIK`, `PE_SUB_ACNT`), user login, PIN mesin.

### S2 — Karyawan lama (backfill)
Script backfill mengisi `HMEMD_NIK_NEW` untuk semua karyawan riil per company. `HMEMD_NIK` belum disentuh (fase 1–2). Transaksi, absensi, payroll tetap memakai NIK lama sampai cutover.

### S3 — Satu orang dua NIK lama (SLO & JKT, periode overlap)
Contoh: SLO `0686`, payroll JKT `2006-0686`, sub-account `13138`. Setelah verifikasi HR → satu NIK baru `2006030012`; ketiga nilai lama tercatat di `HM_EMP_NIK_MAP`. Pencarian dengan salah satu NIK lama menemukan orang yang sama.

### S4 — Mutasi divisi / department / section (company sama)
Org fields berubah via Employee Movement; **NIK tetap**. Hak akses data historis mengikuti org pada transaksi (denormalized), data master mengikuti org saat ini.

### S5 — Login
Login tetap dengan user id/email existing. Tambahan: login memakai NIK baru sebagai alias (resolver `V_EMP_NIK_LOOKUP`). `HMEMD_USER_ID` tidak diganti massal.

### S6 — Absensi mesin
PIN mesin = `HMEMD_USER_MACHINE_ID`. Saat cutover, karyawan lama yang PIN-nya kosong (DEV 1.284 dari 1.308 aktif) diisi NIK lama tanpa nol di depan → tidak perlu re-enroll. Karyawan baru memakai PIN dari `HMEMD_USER_MACHINE_ID` (lihat spec §5 — batas panjang PIN).

### S7 — Rehire
Karyawan `2019040007` resign 2024-06-30, masuk lagi 2026-11-02 → HR buka baris lama → **Duplicate Employee** (reason `REHIRE`) → baris baru ber-NIK `2026110001`, `PREV_SYS_ID` = baris lama. Riwayat lama tetap di NIK lama; saldo cuti tidak dibawa (mengikuti kebijakan HR).

### S8 — Kontrak → Permanen
Karyawan kontrak `2025010015` diangkat permanen 2026-10-01 → Duplicate Employee (reason `CONTRACT_TO_PERMANENT`, join date 2026-10-01) → baris kontrak ditutup 2026-09-30, baris baru `2026100003` status 01. Payroll & PIN mengikuti baris baru.

### S9 — Mutasi antar company
Karyawan MGT `2022050002` pindah ke company `ABC` per 2027-01-01 → Duplicate Employee (reason `TRANSFER_COMPANY`, company `ABC`) → baris baru `2027010001` (seri `ABC`), baris MGT ditutup 2026-12-31. `2027010001` di `ABC` boleh sama dengan `2027010001` di MGT.

### S10 — Cutover identity
Pada tanggal cutover, `04_cutover.sql` mengganti `HMEMD_NIK` seluruh karyawan aktif menjadi NIK baru (backup + map HRIS). Sejak itu transaksi absensi, cuti, lembur, payroll, approval, dan kolom actor menyimpan NIK baru. Transaksi sebelum tanggal cutover tetap ber-NIK lama dan tetap tampil di laporan lewat resolver dua nilai (`HMEMD_SYS_ID` / map).

## 6. Kebutuhan Fungsional

| ID | Kebutuhan |
|---|---|
| FR-01 | Kolom `HMEMD_NIK_NEW` VARCHAR2(10), UNIQUE `(HMEMD_HMCOMP_COMP_ID, HMEMD_NIK_NEW)`; kolom `HMEMD_PREV_SYS_ID`, `HMEMD_NIK_REASON`. |
| FR-02 | Fungsi DB `MGTHRIS.FN_GEN_NIK(p_comp_id, p_join_date)` + tabel bantu `HM_NIK_COUNTER (HNC_COMP_ID, HNC_PERIOD, HNC_LAST_NO)`; lock per company+bulan, aman untuk concurrency. |
| FR-03 | Trigger BEFORE INSERT mengisi NIK untuk semua jalur insert (form, import Excel, register, `PRC_SYNC_PAYROLL_EMP`, Duplicate Employee, script migrasi). |
| FR-04 | Tabel `HM_EMP_NIK_MAP` (alias NIK lama per orang, sumber, divisi). |
| FR-05 | Form & import employee: field NIK read-only; import tidak lagi menerima NIK untuk karyawan baru. |
| FR-06 | Pencarian karyawan (list, dropdown, import transaksi) menerima NIK baru **atau** NIK lama (filter company/divisi bila ambigu). |
| FR-07 | Export/PDF/notifikasi menampilkan NIK baru (+ kolom NIK lama pada export master). |
| FR-08 | Report daftar data belum lengkap (tanpa join date/company, kandidat duplikat, anomali status 00/NULL) untuk HR. |
| FR-09 | `OrgScopeService` terpusat untuk filter data per company/division/department/section (lihat design §B). |
| FR-10 | Grant organisasi mendukung wildcard (mis. seluruh divisi JKT) dan level section. |
| FR-11 | Semua halaman/export/import berisi data karyawan menerapkan scope (daftar gap di impact_analysis §3). |
| FR-12 | Hierarki approval SelfService memfilter company + division. |
| FR-13 | **Duplicate Employee**: aksi di detail karyawan dengan pilihan reason (`REHIRE`, `CONTRACT_TO_PERMANENT`, `TRANSFER_COMPANY`), join date baru, company/org tujuan. Menyalin data pribadi, menutup baris lama, men-generate NIK baru, membuat akun/payroll/PIN baru. Satu transaksi DB. |
| FR-14 | **Cutover**: script `04_cutover.sql` (mode CHECK/APPLY) dengan prasyarat terverifikasi, backup, dan `CUTOVER_DATE` di `HM_NIK_CONFIG`. |
| FR-15 | **Resolver dua nilai**: laporan/rekap lintas periode (sebelum & sesudah cutover) menggabungkan transaksi via `HMEMD_SYS_ID` atau `V_EMP_NIK_LOOKUP`, bukan via string NIK. |

## 7. Hak Akses (User Access)

### 7.1 Model
- **Role/permission (Spatie)** menentukan *aksi* apa yang boleh (view/create/approve…). Tidak berubah.
- **Org scope** menentukan *data karyawan mana* yang boleh dilihat/diproses. Sumber: org milik user sendiri ∪ grant di `HM_EMP_AUTHORIZE_ORG`.
- **Bypass**: role `Super Admin` atau permission `*-view_all_scope`.

### 7.2 Level scope
| Level grant | Contoh grant | Artinya |
|---|---|---|
| Company | `MGT / * / * / *` | seluruh karyawan company MGT |
| Division | `MGT / JKT / * / *` | seluruh karyawan divisi Jakarta |
| Department | `MGT / SLO / TXT / *` | dept TXT di Solo, semua section |
| Section | `MGT / SLO / TXT / WVG` | hanya section tsb |

### 7.3 Matriks peran (default yang disarankan)
| Peran | Scope default | Catatan |
|---|---|---|
| Karyawan (self service) | diri sendiri | tidak perlu grant |
| Atasan/Asmen | dept sendiri (+ grant) | approval mengikuti hierarki grade dalam comp+div+dept |
| Admin HR Divisi | division | grant level division |
| HR Pusat | company | grant level company atau `*-view_all_scope` |
| Super Admin / IT | all | bypass |

### 7.4 Aturan
- AR-01: Tanpa grant, user hanya melihat data org miliknya sendiri (comp+div+dept) — sama seperti perilaku sekarang.
- AR-02: Section hanya membatasi bila grant diisi sampai section.
- AR-03: Approval queue selalu ter-scope (tidak bisa bypass kecuali Super Admin) dan user tidak dapat approve transaksinya sendiri.
- AR-04: Export/job antrian wajib membawa user eksplisit; tanpa user → **deny** (bukan bypass).
- AR-05: Master organisasi (company/division/dept/section/comp map), role & menu tetap global, hanya untuk Super Admin/HR Pusat.

### 7.5 Opsional (fase 5)
Selector "Company/Division aktif" di header sebagai filter tampilan (bukan pengganti scope).

## 8. Implikasi & Risiko (ringkas — detail di plan.md)

| Risiko | Dampak | Mitigasi |
|---|---|---|
| Kolom NIK `VARCHAR2(5)`/`(4)` di `MGTDAT`/legacy | NIK 10 digit gagal insert/terpotong di payroll | Perlebar kolom (koordinasi Orion) **sebelum** `AUTO_ENABLED=Y` dan cutover (cek C0.2) |
| `PRC_SYNC_PAYROLL_EMP` pakai `max(PE_NIK)` numerik | Sync lompat/berhenti | Ganti ke berbasis flag/timestamp |
| PIN mesin 10 digit | Sebagian firmware membatasi panjang PIN | PIN = `HMEMD_USER_MACHINE_ID`; karyawan lama = NIK lama; verifikasi firmware |
| Cutover: laporan lintas periode memakai string NIK | Rekap pecah jadi dua orang (NIK lama & baru) | Resolver dua nilai (FR-15), join via `HMEMD_SYS_ID`; review query 47 kolom `%NIK%` |
| Cutover: modul hard-code NIK (import Excel, integrasi mesin, Orion) | Data gagal match setelah cutover | Import menerima NIK lama/baru via lookup; uji regresi per modul sebelum cutover |
| Duplicate Employee: baris lama tidak ditutup | Dua baris aktif untuk satu orang di company sama | Service satu transaksi + verifikasi V21 |
| Anomali status 00 tanpa resign date (250) | Ikut dianggap aktif / ikut cutover | HR melengkapi resign date sebelum cutover; script mengecualikan status 00 |
| Duplikat orang | Merge salah | Merge hanya via daftar yang disetujui HR |
| Export/scope bocor | Data lintas divisi terlihat | `OrgScopeService` + test |

## 9. Acceptance Criteria

- AC-01: Insert karyawan baru dari form, import, dan procedure sync menghasilkan NIK valid format BR-01, unik per company.
- AC-02: 20 insert paralel pada company + bulan yang sama tidak menghasilkan duplikat; insert paralel company berbeda tidak saling menunggu.
- AC-03: Setelah backfill, `count(HMEMD_NIK_NEW) = count(karyawan riil dengan join date & company)`, tanpa duplikat, dan tidak ada baris transaksi yang berubah.
- AC-04: Absensi, cuti, lembur, payroll karyawan lama berjalan sama seperti sebelum backfill (regression test).
- AC-05: Pencarian dengan NIK lama maupun baru menemukan karyawan yang sama.
- AC-06: User dengan grant `MGT/JKT/*/*` tidak dapat melihat karyawan SLO di list, dropdown, export, dan approval.
- AC-07: Rollback script mengembalikan skema ke kondisi awal tanpa kehilangan data transaksi (termasuk pengembalian `HMEMD_NIK` bila cutover sudah jalan).
- AC-08: Duplicate Employee (rehire / kontrak→permanen / pindah company) menghasilkan baris baru ber-NIK baru sesuai join date + company, `PREV_SYS_ID` terisi, baris lama tertutup (V19–V21 = 0).
- AC-09: Mutasi divisi/department dalam company tidak mengubah NIK.
- AC-10: Setelah cutover, transaksi baru semua modul tersimpan dengan NIK baru; laporan periode yang melintasi cutover menampilkan satu karyawan (bukan dua); V22–V25 = 0.

## 10. Open Questions (HR)

| # | Pertanyaan | Default sementara |
|---|---|---|
| Q1 | 508 transisi kontrak→permanen historis di satu baris: dipecah jadi dua NIK atau tetap satu? | Tetap satu baris, NIK berdasarkan join date di baris tsb |
| Q2 | Rehire: saldo cuti, masa kerja, dan data BPJS dibawa ke baris baru? | Masa kerja tidak dibawa; BPJS disalin; cuti mengikuti kebijakan |
| Q3 | Tanggal cutover (disarankan awal periode payroll) | Belum ditentukan |
| Q4 | Keunikan NIK: cukup per company, atau HR ingin unik lintas grup? | Per company (sesuai arahan) |
| Q5 | 196 KTP `JOIN_NULL` & 250 status 00 tanpa resign date — siapa PIC pelengkapan data? | HR divisi masing-masing |
