# plan.md — Transporter v1: urutan kerja

**Total:** ± 23 minggu · 82 task · 8 fase · target go-live: batas bulan setelah periode JV ditutup

---

## Prinsip urutan

1. **Skema dulu, sekaligus.** Semua 22 tabel dimigrasikan di P0, bukan dicicil per fase.
   Alasannya ada di PRD RK-16: skema terbawa migrasi data — mengubahnya belakangan
   berarti migrasi ulang.
2. **Yang paling berisiko diuji paling awal.** Kalkulator tarif dan pemetaan jurnal
   punya unit test di P0/P3, jauh sebelum halamannya jadi. Kalau angkanya meleset,
   ketahuan saat masih murah.
3. **`MGTDAT` tidak disentuh sampai P6.** Semua fase sebelumnya menulis hanya ke
   `MGTHRIS`. Posting GL di P3/P4 diuji lewat mode preview dan `tests/Integration`.
4. **Approval dibangun sekali di P2 dan dipakai ulang.** Additional expense memakai
   mesin maker-checker yang sama, bukan salinannya.

---

## Dependency graph

```
P0 ─ T001 (scaffold)
      ├─→ T002..T006 (migration 22 tabel) ─→ T007 (index) ─→ T008 (sequence)
      ├─→ T009 (enum) ─┐
      ├─→ T010 (model) ┴─→ T011 (repository + binding) ──────────┐
      ├─→ T012 (angkat service GL ke Core) ───────────────┐      │
      ├─→ T013 (log channel + config)                     │      │
      ├─→ T014 (menu + permission + route + breadcrumb)   │      │
      └─→ T015 (helper due date + kalkulator + unit test) ┤      │
                                                          │      │
P1 ─ T016..T026 (master) ←────────────────────────────────┼──────┘
      │  T016 carrier ─→ T017 rate card ─→ T018 rate line
      │                       └─→ T019 matriks chip
      └─ T021..T024 (master kecil, paralel penuh)
                    │
P2 ─ T027..T041 ←───┘ (butuh master & kalkulator)
      T027 order dasar ─→ T028 daftar ─→ T029 form manual ─→ T030 kalkulasi
                                              └─→ T031 TPSVC
      T032 approval ←── T027         (dipakai ulang T039)
      T034 staging ─→ T035 generate TPDN ─→ T036 command
      T037 GRN siap ─→ T038 generate TPCHP
      T039 additional expense ─→ T040 monitoring
                    │
P3 ─ T042..T047 ←───┘ (butuh transaksi Approved + T012)
      T042 provisi ─→ T043 preview jurnal ─→ T044 posting job ─→ T045 posting log
                    │
P4 ─ T048..T058 ←───┘ (butuh provisi Posted)
      T048 tagihan header ─→ T049 matching ─→ T050 EXPENSE_DIRECT ─→ T053 posting TJV
      T054 kontrol dokumen ─→ T055 job scan ─┘ (gerbang T053)
                    │
P5 ─ T059..T064 ←───┘ (report butuh semua data path)
P6 ─ T065..T076    (migrasi — boleh mulai paralel dengan P5 setelah P4 selesai)
P7 ─ T077..T082    (UAT & cutover — serial, tidak boleh diparalelkan)
```

**Jalur kritis:** `T001 → T002..T006 → T011 → T016 → T017 → T027 → T032 → T042 → T044 → T048 → T053 → T065 → T077`.
Setiap keterlambatan di sini menggeser go-live satu-untuk-satu.

---

## Fase

| Fase | Isi | Task | Estimasi | Gerbang keluar |
|---|---|---|---|---|
| **P0 — Fondasi** | Scaffolding, 22 migration, index, sequence, enum, model, repository, angkat service GL ke Core, log channel, menu & permission, helper + kalkulator | T001–T015 | 2 minggu | `ACCEPTANCE.md` §P0 |
| **P1 — Master** | Carrier, rate card berversi, rate line, matriks tarif chip, 4 master kecil, monitoring GRN chip, export | T016–T026 | 2,5 minggu | §P1 |
| **P2 — Transaksi** | Order 3 jalur + approval maker-checker + inbox, TPSVC, staging & generate otomatis, GRN chip siap generate, additional expense + monitoring, notifikasi | T027–T041 | 5 minggu | §P2 |
| **P3 — Provisi** | Provisi, preview jurnal, posting `TPJV` sebagai job, `transp_posting_log`, idempotensi | T042–T047 | 2,5 minggu | §P3 |
| **P4 — Tagihan** | Tagihan + pajak + matching + baris `EXPENSE_DIRECT` + posting `TJV`, kontrol dokumen surat jalan + job scan | T048–T058 | 4 minggu | §P4 |
| **P5 — Report** | 5 report (Excel + PDF), semuanya queued job | T059–T064 | 1,5 minggu | §P5 |
| **P6 — Migrasi** | Script migrasi 13 tabel, 23 aturan cleansing, compatibility view, rekonsiliasi R-01..R-12, shadow-run | T065–T076 | 3,5 minggu | §P6 |
| **P7 — UAT & Cutover** | UAT Finance, dry-run, cutover, hypercare | T077–T082 | 2 minggu | §P7 |

---

## Yang boleh dikerjakan paralel

| Bisa paralel | Catatan |
|---|---|
| T002–T006 (5 batch migration) | Beda tabel, tidak saling merujuk sampai T007 memasang FK |
| T021, T022, T023, T024 (master kecil) | CRUD sederhana, tidak ada dependensi |
| T034–T036 (jalur yarn) vs T037–T038 (jalur chip) | Dua jalur berbeda, keduanya bermuara ke T027 |
| T059–T064 (5 report) | Beda query, beda export class |
| P5 dan P6 | Setelah P4 selesai, report dan migrasi tidak saling menunggu |

## Yang TIDAK boleh diparalelkan

| Jangan | Kenapa |
|---|---|
| T017 dengan T018 | Validasi overlap rate card (F-01.7) butuh rate line untuk tahu kombinasi mana yang sah |
| T044 dengan T053 | Posting TJV me-reverse provisi. Kalau posting JV belum stabil, TJV menguji di atas fondasi yang bergerak |
| Fase P7 | Cutover berurutan menurut definisi; lihat §6.6 PRD |
| Apa pun dengan T065+ di **produksi** | Script migrasi hanya dijalankan di staging sampai gerbang P6 dicentang |

---

## Checkpoint review dengan Indra

Bukan di akhir fase saja — di lima titik yang kalau salah, mahal untuk diperbaiki:

| Kapan | Apa yang direview | Kenapa titik ini |
|---|---|---|
| Setelah **T007** | DDL 22 tabel dan indexnya | Setelah ini skema terbawa ke migrasi data. Mengubahnya nanti = migrasi ulang (RK-16) |
| Setelah **T015** | Hasil unit test kalkulator tarif terhadap 500 transaksi historis | Ini uji R-12 versi dini. Kalau tarif meleset, normalisasi master salah (RK-15) |
| Setelah **T032** | Aturan maker ≠ approver dan apa yang terjadi pada data migrasi (C-17) | Menentukan apakah 33.126 transaksi lama bisa masuk tanpa melalui alur approval |
| Setelah **T043** | Preview jurnal `TPJV` untuk 3 periode historis vs `FT_*` | Shadow-run RK-01. Ini gerbang go/no-go yang sebenarnya |
| Setelah **T072** | Laporan exception cleansing + pemetaan 739 master → 50 vendor | Butuh sign-off Head of Despatch & Head of Finance (Q-18, fase M-1) |

Hasil tiap checkpoint dicatat di `PROGRESS.md` kolom *Review*.

---

## Rebase di setiap gerbang fase

Seluruh proyek berjalan di satu branch `feat/Transporter` dan PR-nya baru dibuka di
akhir, jadi `develop` akan bergerak jauh selama 23 minggu tanpa pernah bertemu branch
ini. Yang menjaganya tetap bisa di-merge: **rebase ke `develop` setiap kali satu
gerbang `ACCEPTANCE.md` tercentang.**

```bash
git fetch origin
git rebase origin/develop
bash .ai/transporter/preflight.sh      # wajib — CI tidak akan memberi tahu apa pun
```

Delapan rebase kecil jauh lebih murah daripada satu rebase 82 commit di minggu ke-23.
Sebagai gambaran seberapa cepat `develop` bergerak: branch e2e yang ada sekarang sudah
15 commit di belakangnya.

Kalau sebuah rebase menghasilkan konflik di luar `Modules/Transporter/`, **berhenti dan
laporkan** — itu tanda ada yang menyentuh kode bersama, dan menyelesaikannya diam-diam
adalah cara termudah merusak pekerjaan orang lain.
