# 11. Phase Plan & Development Roadmap

## Prinsip Pembagian Phase

Phase dibagi berdasarkan **area produksi**, bukan per layer. Setiap phase harus deliver
nilai nyata ke PPC yang bisa langsung dipakai — demand → plan → WO → actual semua ada,
scope dibatasi per area.

---

## Phase 1 — Foundation + TXT (Bulan 1–4)

**Target:** PPC mulai pakai sistem baru untuk area TXT. Transisi = **cutover per area**
(bukan MLR jalan paralel — data MLR/daily tidak ditarik, jadi parallel = double-entry).
Overlap double-entry singkat opsional demi keamanan saat go-live.

### Scope

**Infrastructure**
- Go PPC service baru di `goapps-backend`
- PostgreSQL schema: semua tabel core
- ETL framework + incremental watermark pattern
- IAM integration: role PPC, PC, PM, Marketing

**Master Data**
- `PRODUCT_PPC_CONFIG` (extend dari CPM_)
- Konsumsi **product route** (`cost_route_head/seq/rm`) + **product-parameter**
  (`mst_parameter` + `cost_product_parameter`) dari Costing
- `PRODUCT_MACHINE_PARAMETER` (layer nilai per produk+mesin, Opsi A) + `PRODUCT_MACHINE_CAPACITY` (planning)
- `LOT_MASTER`, `MACHINE_MASTER` (TXT)

**Layer 1 — Production Demand (full scope)**
- Pull from Orion LOV (via `MGT_SO_PENDING_WEB`)
- Input manual, MTS approval flow
- Demand page 3 tab + Start New Month workflow

**Layer 2 — Plan Item TXT**
- CRUD plan item TXT
- Gantt view per mesin per hari

**Layer 3 — Work Order TXT (route + product-parameter driven, v1.2)**
- Generate WO dari plan (snapshot route `crh_head_id`+version), lot no di-generate PPC
- WO_PARAMETER dari product-parameter (`display_group='Machine'`), dual PPC/PC (8 param), well-known codes
- WO_RM_ALLOCATION dari `cost_route_rm` (N komponen) + genealogy otomatis
- Approval **PC → PM sequential**, auto-approve 24 jam (bisa di-disable)
- WO reference (duplicate / continuation)
- Snapshot spec saat approve · grade req dari demand (override)
- Over-production threshold

**ETL TXT**
- Oracle: `PRC_PPC_TXT_PRODUCTION` (setiap 15 menit)
- `PPC_TXT_PRODUCTION` → `WO_PRODUCTION_ACTUAL`
- TQM breakdown: NORMAL / DOWNGRADE / PENDING per doff

**Daily Performance TXT (v1.2 — halaman 13, rebuild native)**
- Shift entry berbasis mesin+shift: qty prefill bobbin (→ `qty_actual`, editable),
  running time diturunkan dari downtime, waste, breaks per shift, log book (INSTRUKSI/ACTIVITY)
- Model dua-sumbu: audit (`qty_bobbin`/`qty_actual`) + scope (tag → Incl/Excl); efisiensi derived-only
- Efficiency engine TXT: Production/Running Eff, MC EFF grid; sliceable per customer/produk (ATEJA=filter)
- `EFFICIENCY_SNAPSHOT`, `WASTE_ACTUAL`, `DOWNTIME_EVENT`, `SHIFT_LOG_NOTE` + master
- **Tanpa approval** (final dari packing). Dashboard + Export to Excel
- **Tidak** ada ETL dari `PRD_TXT_MCHN_ACT` — dibangun native

**Dashboard**
- Morning review TXT
- Basic Balance for Sale

### Dependensi

| Item | Status |
|---|---|
| ETL Bobbin queries + DDL Oracle | ✅ Selesai |
| MACH_DEPT konfirmasi | ✅ TXT='TXT', TWT='TWT' |
| SO Orion via MGT_SO_PENDING_WEB | ✅ Selesai |
| Product route (`cost_route_head/seq/rm`) | ✅ DDL diterima |
| `cost_product_parameter` + `mst_parameter` | ✅ DDL diterima |
| **`is_for_production` flag di `mst_parameter`** (tim Costing) | ⏳ rekomendasi — sementara pakai `display_group='Machine'` |
| **Parameter type=PRODUCTION diisi per produk** (produksi via modul Costing) | ⏳ butuh koordinasi tim produksi/costing |

---

## Phase 2 — SPG + Carry-forward + Balance (Bulan 4–7)

**Target:** SPG live, awal bulan digital, Balance for Sale real-time.

### Scope
- SPG integration: `PRC_PPC_SPG_PRODUCTION` + `ASPSPG.TQMAPP`
- Layer 2 & 3 area SPG
- Daily Performance SPG: shift entry, dual qty (doffed/transferred),
  Yield/Efficiency/Plant/Machine Eff, waste 8 kategori (with/without upsets),
  production loss 6 kategori, downgrade record, Change Over % & Breaks/Ton
- Captive RM tracking (STORE/CAPTIVE/MIXED)
- RM Allocation connect product route (`cost_route_rm`) — genealogy otomatis
- Changeover component-based (C1–C7)
- Balance for Sale full
- MTS demand full workflow

---

## Phase 3 — TWT + Packing + Full Analytics (Bulan 7–9)

**Target:** Sistem lengkap, Excel fully retired.

### Scope
- TWT integration (TXTTRANSFER MACH_DEPT='TWT')
- Daily Performance TWT: shift entry, efficiency per autoclave/mesin,
  waste per tipe mesin, downtime vs target (mis. Meerabah 2.160 mnt/bln)
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
| 1 | `is_for_production` flag di `mst_parameter` (tim Costing) — sementara `display_group='Machine'` | Phase 1 | Open |
| 2 | Changeover durasi & waste — validasi tim produksi | Phase 2 | Open |
| 3 | Threshold SPG & TWT data aktual | Phase 2 | Open |
| 4 | Validasi definisi break metrics TXT (original vs inspection) | Phase 1 | Open |
| 5 | Denier produk untuk theoretical calc — field di CPM_ | Phase 1 | Open |
| 6 | Sumber resmi Overtime (HR) — sementara `AREA_SHIFT_LOG` | Phase 2 | Open |
