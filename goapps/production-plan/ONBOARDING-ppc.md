# ONBOARDING — PPC Production Planning System

> Dokumen spesifik untuk project PPC.
> **Baca dulu:** `docs-markdown/ONBOARDING.md` (umum) sebelum dokumen ini.
> Pertanyaan? Hubungi Indra (IT Lead).

---

## 1. Overview PPC

PPC (Production Planning & Control) menggantikan proses planning Excel yang sangat
kompleks (19 sheet, formula saling terhubung) dengan sistem terintegrasi.

```
Yang digantikan:
  PL_F26_R-000_R-0003_FIN.xls  ← file Excel aktual planning
  PRD_TXT_MLR_ENTRY             ← Oracle form untuk input produksi

Yang dibangun:
  Layer 1: Production Demand    → pengganti sheet Sales Plan
  Layer 2: Production Plan Item → pengganti sheet datewise
  Layer 3: Work Order           → pengganti MLR form
  ETL Oracle → PostgreSQL       → data produksi real-time
  Dashboard                     → pengganti sheet SUMMARY
```

**Repo:** `mutugading/goapps-backend` (service baru di dalam repo ini)
**PRD:** `docs-markdown/goapps/production-plan/` (12 halaman)
**Stack:** Go 1.24, PostgreSQL 18, gRPC, Oracle 11g (ETL source)

---

## 2. Baca PRD Ini Dulu

Sebelum mulai apapun, baca minimal 3 halaman PRD ini:

```
docs-markdown/goapps/production-plan/

WAJIB dibaca pertama:
  README.md          ← index dan daftar isi
  01-overview.md     ← latar belakang, scope, definisi istilah
  11-phase-plan.md   ← roadmap 3 phase, scope tiap phase

Baca sesuai task:
  03-layer1-demand.md     ← kalau kerjakan demand module
  04-layer2-plan-item.md  ← kalau kerjakan plan item
  05-layer3-work-order.md ← kalau kerjakan work order
  08-integrasi-etl.md     ← kalau kerjakan ETL
  12-schema.md            ← kalau kerjakan database / migration
```

---

## 3. Setup Spesifik PPC

Setup umum sudah ada di `docs-markdown/ONBOARDING.md`.
Tambahan spesifik untuk PPC:

### Dev container

```powershell
cd "D:\IT Project\goapps-backend"
docker compose -f docker-compose.dev.yml up -d

# Services yang jalan:
#   goapps-dev      → Go + Claude Code (port 50051, 8080)
#   goapps-postgres → PostgreSQL 18 (port 5433 dari host)
#   goapps-redis    → Redis 7 (port 6380 dari host)
```

### Oracle connection (ETL source)

```
Host:    192.168.0.7:1521
Service: althara
Schema:  MGTDAT (summary tables), ASPTXT (TXT data), ASPSPG (SPG data)
DSN env: ORACLE_DSN=mgthris/mgthris@192.168.0.7:1521/althara
```

Oracle Instant Client perlu di-mount dari host. Lihat `DEV_SETUP.md` section Oracle.

### Dokumen referensi kritis

```
File                                          Isi
────────────────────────────────────────────────────────────────
internal/ppc/CLAUDE.md                        Context untuk Claude Code
internal/ppc/TASKS.md                         Task queue Phase 1
internal/ppc/PREFLIGHT.md                     Blocker checklist
scripts/preflight.sh                          Auto-check script
docs-markdown/goapps/production-plan/         PRD lengkap
  oracle/PPC_ORACLE_DDL.sql                   DDL Oracle summary tables
  oracle/PPC_ORACLE_PROCEDURES.sql            Refresh procedures
```

---

## 4. Phase Plan

```
Phase 1 — Foundation + TXT (Bulan 1-4)  ← SEDANG DIKERJAKAN
  Infrastructure, ETL TXT/TWT, Demand, Plan Item, WO TXT, Dashboard

Phase 2 — SPG + Balance (Bulan 4-7)
  SPG integration, Carry-forward, Balance for Sale, RM Allocation BOM

Phase 3 — TWT + Packing + Analytics (Bulan 7-9)
  TWT integration, Packing sync, Full analytics, Excel retirement
```

**Sekarang fokus di Phase 1.** Lihat ClickUp list `🏗️ Phase 1 — Foundation + TXT`.

---

## 5. ClickUp PPC

```
Space: IT Project
  Folder: PPC — Production Planning System
    📋 Requirements & Design   (ID: 901818891952)
    🏗️ Phase 1 — Foundation + TXT  (ID: 901818891955)  ← aktif
    ⚙️ Phase 2 — SPG + Balance  (ID: 901818891959)
    📦 Phase 3 — TWT + Packing + Analytics  (ID: 901818891962)

Dokumen ClickUp:
  ETL Spec:        2kzmeddw-2758  ← query Oracle + field mapping
  Design Decisions: 2kzmeddw-2138 ← 193 keputusan design
```

---

## 6. Cara Mulai Development PPC

### Langkah standar setiap mulai session

```bash
# 1. Masuk container
docker exec -it goapps-dev bash

# 2. Pull latest
cd /workspace && git pull

# 3. Buat/pindah ke branch
git checkout -b feature/ppc-[nama-fitur]
# atau
git checkout feature/ppc-[nama-fitur]

# 4. Wajib: jalankan preflight check
bash scripts/preflight.sh
# Semua BLOCKER harus PASS sebelum lanjut

# 5. Mulai Claude Code
claude
```

### Untuk autonomous 24 jam

```bash
claude --dangerously-skip-permissions \
  "jalankan bash scripts/preflight.sh dulu.
   kalau semua PASS, baca TASKS.md dan kerjakan semua task [TODO]
   satu per satu. refer ke design.md dan spec.md untuk setiap task.
   commit setelah setiap task: 'feat(ppc): [TXXX] judul'
   update [TODO] → [DONE] di TASKS.md setelah commit."
```

---

## 7. Critical Things — Wajib Tahu

### ⚠️ TRN_STS TXT/TWT — Paling Sering Salah

```
TXTTRANSFER.TRN_STS:
  0 = Full bobbin   ← KEBALIKAN dari intuisi
  1 = Unfull bobbin

DOFFCONT.DOFF_OPTION (SPG):
  1 = Full bobbin
  2 = Unfull bobbin

Kalkulasi qty TXT/TWT:
  qty = (COUNT(TRN_STS=0) × std_weight_full)
      + (COUNT(TRN_STS=1) × std_weight_unfull)
```

### TQM Logic TXT/TWT

```
Status final bobbin = TYPE dan APP_REL dari TRN_NO terbesar per posisi:

  TYPE != 7 AND APP_REL = 2  → NORMAL
  TYPE = 7                   → DOWNGRADE FINAL (meski APP_REL=2)
  APP_REL = 1 tanpa lanjutan → PENDING (di-hold TQM)
  APP_REL IS NULL            → BELUM DICEK
```

### Oracle summary tables (ETL source)

```
Semua di schema MGTDAT:

  PPC_TXT_PRODUCTION    → TXT/TWT bobbin production per shift
                          Window: TRN_PRD_DT >= SYSDATE-7
                          ETL: incremental via LAST_UPDATED

  PPC_SPG_PRODUCTION    → SPG production per doff (Phase 2)
                          Window: DOFF_DATE >= SYSDATE-7

  PPC_GRADE_ACTUAL      → Grade aktual packing per lot (Phase 3)
                          Window: PKG_PUT_DATE >= SYSDATE-1

  MGT_SO_PENDING_WEB    → SO Orion staging (existing)
                          ETL: full replace
```

### Oracle 11g Constraints

```
✗ Tidak ada native JSON  → pakai CLOB
✗ Tidak ada IDENTITY     → pakai SEQUENCE + TRIGGER
✓ Summary tables di MGTDAT sudah dibuat (DDL sudah di-run)
```

### Migration Numbering

```
PENTING: cek nomor terakhir sebelum buat migration baru
  ls /workspace/migrations/ | sort | tail -3

Format: 000XXX_create_ppc_yyy.up.sql
Jangan pakai nomor yang sudah ada.
```

### Architecture Pattern (Go)

```
Selalu ikuti Clean Architecture + DDD:
  domain/ → application/ → infrastructure/ → delivery/

Referensi: lihat internal/finance/ untuk pattern yang sudah proven.
Tidak boleh ada dependency dari domain ke infrastructure.

Error wrapping:
  fmt.Errorf("ppc.CreateWO: %w", err)
```

---

## 8. Validated Test Data

Data ini sudah divalidasi manual — gunakan untuk integration tests:

```
TXT/TWT:
  lot_no    : qU04qB006
  machine   : SM2
  prd_date  : 15/06/2026
  doff_no   : 34
  Expected  : TOTAL=20, NORMAL=18, DOWNGRADE=2, PENDING=0

SPG:
  lot_no    : 11F3226
  position  : 201
  doff_date : 17/06/2026
  Expected  : GROSS=8/doff per posisi, TQM sesuai TQMAPP
```

Simpan sebagai fixtures di `internal/ppc/testdata/` supaya
test bisa jalan tanpa koneksi Oracle langsung.

---

## 9. Cara Tambah Project Baru

Kalau ada project baru yang perlu ONBOARDING sendiri:

```
1. Copy template ini sebagai starting point
2. Update section 1 (overview project baru)
3. Update section 3 (setup spesifik)
4. Isi section 7 (critical things spesifik project)
5. Taruh di: docs-markdown/[folder-project]/ONBOARDING.md
6. Tambah entry di docs-markdown/ONBOARDING.md section 12
```

**Selalu refer ke `docs-markdown/ONBOARDING.md`** di bagian atas file —
supaya member baru tahu harus baca umum dulu sebelum spesifik.

---

## 10. Checklist Hari Pertama PPC

```
[ ] Sudah baca docs-markdown/ONBOARDING.md (umum)
[ ] Docker compose up berhasil (3 containers)
[ ] Claude Code login berhasil
[ ] Baca PRD: README.md + 01-overview.md + 11-phase-plan.md
[ ] Baca internal/ppc/CLAUDE.md
[ ] Baca internal/ppc/TASKS.md (lihat task yang tersedia)
[ ] Jalankan preflight.sh — perhatikan apa yang FAIL
[ ] Baca ETL Spec di ClickUp Doc 2kzmeddw-2758
[ ] Pilih satu task [TODO] dari TASKS.md
[ ] Mulai!
```

---

*Dokumen ini di-maintain bersama oleh tim IT.*
*Kalau ada yang perlu diupdate, buat PR langsung.*
*Last updated: Juni 2026 oleh Indra (IT Lead).*
