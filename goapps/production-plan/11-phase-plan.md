# 11. Phase Plan & Development Roadmap

## Prinsip Pembagian Phase

Phase dibagi berdasarkan **area produksi**, bukan per layer. Setiap phase harus deliver
nilai nyata ke PPC yang bisa langsung dipakai — demand → plan → WO → actual semua ada,
scope dibatasi per area.

---

## Phase 1 — Foundation + TXT (Bulan 1–4)

**Target:** PPC mulai pakai sistem baru untuk area TXT. MLR tetap jalan paralel sebagai fallback.

### Scope

**Infrastructure**
- Go PPC service baru di `goapps-backend`
- PostgreSQL schema: semua tabel core
- ETL framework + incremental watermark pattern
- IAM integration: role PPC, PC, PM, Marketing

**Master Data**
- `PRODUCT_PPC_CONFIG` (extend dari CPM_ BOM Phase B)
- `PRODUCT_MACHINE_CAPACITY` (dari data `01_PROD` Excel)
- `LOT_MASTER`, `MACHINE_MASTER` (TXT)

**Layer 1 — Production Demand (full scope)**
- Pull from Orion LOV (via `MGT_SO_PENDING_WEB`)
- Input manual, MTS approval flow
- Demand page 3 tab + Start New Month workflow

**Layer 2 — Plan Item TXT**
- CRUD plan item TXT
- Gantt view per mesin per hari

**Layer 3 — Work Order TXT**
- Generate WO, dual approval (PC + PM)
- WO parameter teknis + WO_EXECUTION
- RM Allocation manual (Phase 1)
- Over-production threshold

**ETL TXT**
- Oracle: `PRC_PPC_TXT_PRODUCTION` (setiap 15 menit)
- `PPC_TXT_PRODUCTION` → `WO_PRODUCTION_ACTUAL`
- TQM breakdown: NORMAL / DOWNGRADE / PENDING per doff

**Dashboard**
- Morning review TXT
- Basic Balance for Sale

### Dependensi

| Item | Status |
|---|---|
| BOM Phase B schema | ETA 2–3 hari |
| ETL Bobbin queries + DDL Oracle | ✅ Selesai |
| MACH_DEPT konfirmasi | ✅ TXT='TXT', TWT='TWT' |
| SO Orion via MGT_SO_PENDING_WEB | ✅ Selesai |

---

## Phase 2 — SPG + Carry-forward + Balance (Bulan 4–7)

**Target:** SPG live, awal bulan digital, Balance for Sale real-time.

### Scope
- SPG integration: `PRC_PPC_SPG_PRODUCTION` + `ASPSPG.TQMAPP`
- Layer 2 & 3 area SPG
- Captive RM tracking (STORE/CAPTIVE/MIXED)
- RM Allocation connect BOM Phase B
- Changeover component-based (C1–C7)
- Balance for Sale full
- MTS demand full workflow

---

## Phase 3 — TWT + Packing + Full Analytics (Bulan 7–9)

**Target:** Sistem lengkap, Excel fully retired.

### Scope
- TWT integration (TXTTRANSFER MACH_DEPT='TWT')
- Grade actual: `PRC_PPC_GRADE_ACTUAL` → `WO_GRADE_ACTUAL`
- Full reporting dan export
- Rolling average yield (setelah 3 WO completed)
- Performance tuning

---

## Timeline

```
Bulan 1–2:  Infrastructure + Master Data + Layer 1 Demand
Bulan 2–3:  Layer 2 Plan Item + Gantt TXT
Bulan 3–4:  Layer 3 WO TXT + ETL Bobbin + Dashboard
Bulan 4:    Phase 1 Go-live (TXT)
Bulan 4–5:  SPG integration
Bulan 5–6:  Carry-forward + Balance for Sale
Bulan 6–7:  Phase 2 Go-live (SPG + full demand)
Bulan 7–8:  TWT + Packing
Bulan 8–9:  Full analytics + Excel retirement
```

---

## Open Items

| # | Item | Phase | Status |
|---|---|---|---|
| 1 | BOM Phase B schema → `WO_RM_ALLOCATION` Phase 2 | Phase 2 | ETA 2–3 hari |
| 2 | Changeover durasi & waste — validasi tim produksi | Phase 2 | Open |
| 3 | Threshold SPG & TWT data aktual | Phase 2 | Open |
