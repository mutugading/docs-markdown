# 8. Integrasi & ETL

## Peta Integrasi

```
ERP Orion (Oracle 11g)
  MGT_SO_PENDING_WEB  (existing, direfresh oleh PRC_SO_PENDING_MGT)
    → ETL Go (full replace) → SALES_ORDER_STAGING (PostgreSQL)

Bobbin Tracking (Oracle 11g) — Oracle summary tables di MGTDAT
  PPC_TXT_PRODUCTION  (refresh setiap 15 menit, window SYSDATE-7)
    → ETL Go (UPSERT) → WO_PRODUCTION_ACTUAL (TXT/TWT)
  PPC_SPG_PRODUCTION  (refresh setiap 15 menit, window SYSDATE-7)
    → ETL Go (UPSERT) → WO_PRODUCTION_ACTUAL (SPG)
  PPC_GRADE_ACTUAL    (refresh setiap 15 menit, window SYSDATE-1)
    → ETL Go (UPSERT) → WO_GRADE_ACTUAL

PPC System (PostgreSQL)
  → Write-back ke Orion: Phase 2
```

**Prinsip:** Oracle summary tables melakukan heavy aggregation. ETL Go hanya
`SELECT * FROM summary WHERE LAST_UPDATED > :watermark` lalu UPSERT ke PostgreSQL.

---

## SO Orion → SALES_ORDER_STAGING

**Source Oracle:** `MGTDAT.MGT_SO_PENDING_WEB` (existing, proven, dipakai reporting web)

**ETL mode:** Full replace setiap run (data kecil, always fresh)

**Field mapping:**

| Field Oracle | Field PostgreSQL | Keterangan |
|---|---|---|
| `PEND_CUST_CODE` | `sos_customer_code` | — |
| `PEND_CUST_NAME` | `sos_customer_name` | — |
| `PEND_CONTRACT_NO` | `sos_contract_no` | — |
| `PEND_CONTRACT_DT` | `sos_contract_date` | — |
| `PEND_CTRT_SYS_ID` | `sos_contract_sys_id` | FK ke Orion SOH |
| `PEND_ITEM_CODE` | `sos_item_code` | — |
| `PEND_GRADE_CODE_1` | `sos_grade_code` | Dari FNC_GRADE_SO_MGT |
| `PEND_GRADE_CODE_2` | `sos_shade_code` | — |
| `PEND_QTY` | `sos_qty_remaining` | **Field utama untuk PPC** |
| `PEND_SO_QTY` | `sos_qty_ordered` | — |
| `PEND_DEL_QTY` | `sos_qty_delivered` | — |
| `PEND_DEL_DT` | `sos_deadline` | Delivery date |
| `PEND_MERGE_NO` | `sos_merge_no` | Dari FNC_MERGE_SO_MGT |
| `PEND_TERM` | `sos_term` | CBD / non-CBD |
| `PEND_RATE` | `sos_rate` | Harga per kg |
| `PEND_STS` | `sos_blocked_status` | Overdues / Delivery Blocked |
| `PEND_CURR_CODE` | `sos_currency` | — |
| `PEND_OUTSTANDING` | `sos_outstanding_ar` | AR per customer |

Field tambahan PostgreSQL: `sos_pulled_to_demand_id` (NULL=available, FK=sudah di-pull)

---

## ETL Bobbin Tracking — TXT/TWT

**Source Oracle:** `MGTDAT.PPC_TXT_PRODUCTION`
**Refresh pattern:** DELETE `TRN_PRD_DT >= SYSDATE-7` + INSERT fresh, setiap 15 menit
**ETL mode:** UPSERT by `(lot_no, machine_no, trn_date, shift, doff_no)`
**Watermark:** `LAST_UPDATED`

**⚠️ TRN_STS: 0=Full, 1=Unfull** (kebalikan dari SPG DOFF_OPTION)

**Kolom penting:**

| Kolom Oracle | Kolom PostgreSQL | Keterangan |
|---|---|---|
| `TOTAL_BOBBINS` | `wpa_total_bobbins` | Dari TYPE=1 (produksi asli) |
| `FULL_BOBBINS` | `wpa_full_bobbins` | TRN_STS=0 |
| `UNFULL_BOBBINS` | `wpa_unfull_bobbins` | TRN_STS=1 |
| `NORMAL_BOBS` | `wpa_normal_bobs` | TQM lulus final |
| `DOWNGRADE_BOBS` | `wpa_downgrade_bobs` | TYPE=7 final defect |
| `PENDING_BOBS` | `wpa_pending_bobs` | Masih di-hold TQM |
| `PACK_CEK_BOBS` | `wpa_pack_cek_bobs` | Handover ke packing |

**TQM logic (embedded di TXTTRANSFER):**
- `FINAL_TYPE != 7 AND APP_REL = 2` → NORMAL
- `FINAL_TYPE = 7` → DOWNGRADE FINAL
- `APP_REL = 1 tanpa lanjutan` → PENDING

**Sanity:** `TOTAL = NORMAL + DOWNGRADE + PENDING`

---

## ETL Bobbin Tracking — SPG

**Source Oracle:** `MGTDAT.PPC_SPG_PRODUCTION`
**Refresh pattern:** DELETE `DOFF_DATE >= SYSDATE-7` + INSERT fresh, setiap 15 menit
**ETL mode:** UPSERT by `(lot_no, machine_line, doff_date, position_no, doff_no)`
**Watermark:** `LAST_UPDATED`

**Kolom penting:**

| Kolom Oracle | Kolom PostgreSQL | Keterangan |
|---|---|---|
| `GROSS_BOBBINS` | `wpa_gross_bobbins` | Semua keluar mesin (DOFFCONT) |
| `TRANSFERRED_BOBS` | `wpa_transferred_bobs` | TRN_TYPE!=4, TRN_STATUS=2 |
| `CUT_BOBBINS` | `wpa_cut_bobbins` | TRN_TYPE=4 (dipotong) |
| `NOT_TRANSFER` | `wpa_not_transfer` | Belum ada di TRANSFER |
| `NORMAL_BOBS` | `wpa_normal_bobs` | TQM_GRADE=1 (TQMAPP) |
| `DOWNGRADE_BOBS` | `wpa_downgrade_bobs` | TQM_GRADE=0 |
| `NOT_CHECKED_BOBS` | `wpa_not_checked_bobs` | TRN_APP_REL_DT IS NULL |
| `TQM_DONE_BOBS` | `wpa_tqm_done_bobs` | NORMAL + DOWNGRADE |

**Sanity 1:** `GROSS = TRANSFERRED + CUT + NOT_TRANSFER`
**Sanity 2:** `TRANSFERRED = NORMAL + DOWNGRADE + NOT_CHECKED`

**Pemakaian dual qty (v1.1):** `GROSS_BOBBINS` → `wpa_qty_doffed_kg` (basis efficiency &
daily report — sesuai daily report Excel existing yang doffing-based), `TRANSFERRED_BOBS` →
`wpa_qty_transferred_kg` (basis pemenuhan WO & feeding RM ke TXT). WIP doffed-belum-transfer
bila diperlukan = `NOT_TRANSFER × weight` (turunan, tanpa tabel baru).

**TQM join SPG (TQMAPP):** exact match via
`TRN_APP_REL_DT=TQM_PRD_DT, TRN_APP_REL_DOFF=TQM_DOFF, TRN_POS=TQM_POS, TRN_BOB=TQM_BOB`

---

## ETL Grade Aktual (Packing)

**Source Oracle:** `MGTDAT.PPC_GRADE_ACTUAL`
**Refresh pattern:** DELETE aktif lot + INSERT semua history, setiap 15 menit
**ETL mode:** UPSERT by `(original_lot_no, grade, dept)`

**Grade B/BB:** original lot dari PAKPKG (outer join)
**Grade AM:** dibreakup ke A9+A via PAKPKGDUPAM (outer join)

---

## ETL Config

| Parameter | Value | Notes |
|---|---|---|
| Refresh Oracle summary | 15 menit | Adjust setelah performance test |
| Window TXT/TWT | `TRN_PRD_DT >= SYSDATE-7` | Cover delay TQM TYPE=6/7 |
| Window SPG | `DOFF_DATE >= SYSDATE-7` | Cover delay TQM check SPG |
| Window grade actual | `PKG_PUT_DATE >= SYSDATE-1` | Rolling 1 hari |
| ETL Go watermark | `LAST_UPDATED > :last_run` | Per summary table |
| SO ETL mode | TRUNCATE + INSERT | Full replace, sesuai PRC_SO_PENDING_MGT |
| PostgreSQL write | UPSERT by natural key | `ON CONFLICT DO UPDATE` |
| On ETL fail | `wpa_sync_status = SYNC_FAILED` | Alert ke PPC |

**Catatan v1.1:** Efficiency, waste, downtime/idle, running time & posisi running
**tidak tersedia di Oracle** — bukan scope ETL. Sumbernya input operator per shift
(shift entry) dan kalkulasi engine di sistem PPC. Lihat halaman 13.

---

## Oracle Summary Tables Reference

File DDL dan procedures tersedia di repo:
- `goapps/ppc/oracle/PPC_ORACLE_DDL.sql` — DDL 3 summary tables di MGTDAT
- `goapps/ppc/oracle/PPC_ORACLE_PROCEDURES.sql` — 3 refresh procedures
- ETL Spec lengkap: ClickUp Doc `2kzmeddw-2758`
