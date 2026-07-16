# 10. Balance for Sale & Dashboard

## Balance for Sale

Balance for Sale adalah kalkulasi real-time stok AX available untuk dijual,
khusus untuk produk **commodity watch**.

### Formula

```
Balance for Sale (AX) =
  current_stock_AX
  + WO_running_output_estimated   (dari WO_PRODUCTION_ACTUAL, status RUNNING)
  + MTS_plan_qty                  (dari plan item type MTS yang confirmed)
  - committed_contract_qty        (dari demand status IN_PRODUCTION + CONFIRMED)
```

### Commodity Watch

Produk masuk watchlist jika `ppc_is_commodity_watch = true` di `PRODUCT_PPC_CONFIG`.
Dikelola Management.

### Trigger Notifikasi
- Saat MTS demand confirmed untuk produk commodity
- Saat balance berubah signifikan (configurable threshold)
- Periodic summary ke Management

---

## Dashboard Utama

### Morning Review (harian PPC)

**Actual vs Plan kemarin:**
- Per mesin: actual vs target
- Over / under production flag
- Mesin yang changeover

**Open issues hari ini:**
- WO butuh action (RM mendekati limit, over-production)
- WO pending approval hampir auto-approve

**Priority hari ini:**
- WO yang harus jalan hari ini, sorted by deadline
- No of machine needed vs available

### Summary View (pengganti `00_SUMMARY` Excel)
- Produk × mesin running × total production plan × balance AX
- No of machine needed per produk
- Status demand per produk

### Quick Stats
- Machines running / total
- Plan items this month (breakdown status)
- WOs pending approval
- RM fence alerts
- Unmatched SO alerts

---

## Daily Performance Dashboard (v1.1)

Pengganti daily report Excel TXT/TWT/SPG. Detail metrik & formula di halaman 13.

**KPI Cards (Today + MTD):**
- Total Production per area — versi Excluding/Including
- Efficiency: DTY (excl/incl), ACY, ATY, SPG (Yield/Efficiency/Plant), TWT
- Waste % per area · Idle positions · OT hours

**Panel:**
- Heatmap MC EFF per mesin per shift (bulanan — pengganti file `Eff_*`)
- Waste per kategori (with/without upsets, excl/incl trip & B-to-B)
- Downtime/idle pareto per reason + lost kg
- Activity feed per mesin per shift
- Downgrade record SPG per reason

**Drill-down:** area → mesin → WO/lot → shift.

**Export to Excel:** per tanggal, template menyerupai layout report existing
(dashboard = source of truth, Excel = generated output).

**Actual vs Plan di Morning Review** diperkaya kolom efficiency & waste per mesin.
