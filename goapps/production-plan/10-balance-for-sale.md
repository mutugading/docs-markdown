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
