# Shipment Control backfill — pre-flight report

| | |
|---|---|
| Run | 2026-09-08 16:00:01 |
| Source connection | `oracle_mgtapps` |
| Checks | 23 |
| RED with rows | 1 |
| AMBER with rows | 8 |
| Verdict | **Blocked** — RED findings must be fixed or waived. |

Checks are `data-migration.md` §5. RED stops the backfill; AMBER migrates as it stands and
is recorded here so it is not met as a surprise afterwards.

## Summary

| Check | Severity | Finding | Reads | Rows |
|---|---|---|---|---|
| RED 1 | RED | Provisions whose trans code is neither SHPEXP nor SHPARR | `shp_arr_head` | none |
| RED 1 | AMBER | Provisions from the cancelled project, which the backfill skips | `shp_arr_head` | **2** |
| RED 1 | RED | Settlements whose trans code is neither EXPBILL nor IMPBILL | `shp_bill_head` | none |
| RED 1b | RED | Provision numbers reused within one trans code | `shp_arr_head` | none |
| RED 1b | RED | Settlement numbers reused within one trans code | `shp_bill_head` | none |
| RED 1b | AMBER | Provision numbers shared between the export and import series | `shp_arr_head` | **454** |
| RED 1b | AMBER | Settlement numbers shared between the export and import series | `shp_bill_head` | **638** |
| RED 2 | RED | One vendor invoice number entered twice for the same vendor | `shp_bill_inv` | **1** |
| RED 2 | RED | One invoice number twice from the same vendor on one provision | `shp_arr_inv` | none |
| RED 3 | RED | One provision cost line settled by two settlement lines | `shp_bill_det` | none |
| RED 4 | RED | Provision invoices whose header is gone | `shp_arr_inv` | none |
| RED 4 | RED | Provision cost lines whose invoice is gone | `shp_arr_det` | none |
| RED 4 | RED | Settlement lines pointing at a provision line that is gone | `shp_bill_det` | none |
| RED 4 | RED | Settlements pointing at a provision that is gone | `shp_bill_head` | none |
| AMBER 5 | AMBER | Provisions with a pay date but no payment voucher | `shp_arr_head` | **17** |
| AMBER 5 | AMBER | Settlements with a pay date but no payment voucher | `shp_bill_head` | none |
| AMBER 6 | AMBER | Aju numbers that are not 14 characters | `shp_arr_head` | none |
| AMBER 7 | AMBER | Provision cost types with no `ship_cost_type` row | `shp_arr_head.sah_cost_type` | **4** |
| AMBER 7 | AMBER | Invoice cost types (export flex 2) with no `ship_cost_type` row | `shp_arr_inv.sai_flex_2` | **4** |
| AMBER 7 | AMBER | Activity codes with no `shp_activities` row | `shp_arr_det.sad_act_code` | none |
| AMBER 7 | AMBER | Facilities with no `ship_facility` row | `shp_arr_det.sad_fasilitas` | **16** |
| AMBER 8 | AMBER | Provision cost lines with no main or provision account | `shp_arr_det` | **162** |
| AMBER 9 | AMBER | One provision voucher reference on more than one provision | `shp_arr_head` | none |

## RED 1 · Provisions from the cancelled project, which the backfill skips

AMBER — migrated as-is, recorded · reads `shp_arr_head` · **2 rows**.

> Q15, decided 2026-09-08: `SHPCTR` and `EXPCTR` are a cancelled project. They are not migrated — neither the headers nor their invoices, cost lines, containers or POs — and they stay in the legacy schema, which is the read-only audit copy after cut-over.

| sah_sys_id | sah_trans_no | sah_trans_code | sah_status |
|---|---|---|---|
| 2025000649 | 2025000003 | SHPCTR | 0 |
| 2025000894 | 2025000002 | EXPCTR | 0 |

## RED 1b · Provision numbers shared between the export and import series

AMBER — migrated as-is, recorded · reads `shp_arr_head` · **454 shared numbers**.

> Not duplicates: an export number and an import number that read the same, in two separate legacy series. They only block while the new unique key is on the bare number, which is what Q12 is deciding. The 433 / 638 figures recorded against Q12 count these too and are therefore an over-count — this run replaces them.

| sah_trans_no | series |
|---|---|
| 2025000001 | 2 |
| 2025000002 | 2 |
| 2025000003 | 2 |
| 2025000005 | 2 |
| 2025000006 | 2 |
| 2025000007 | 2 |
| 2025000008 | 2 |
| 2025000010 | 2 |
| 2025000011 | 2 |
| 2025000013 | 2 |

_444 further row(s) not listed; re-run with a larger `--limit` for the full set._

## RED 1b · Settlement numbers shared between the export and import series

AMBER — migrated as-is, recorded · reads `shp_bill_head` · **638 shared numbers**.

> Not duplicates: an export number and an import number that read the same, in two separate legacy series. They only block while the new unique key is on the bare number, which is what Q12 is deciding. The 433 / 638 figures recorded against Q12 count these too and are therefore an over-count — this run replaces them.

| sbh_trans_no | series |
|---|---|
| 2025000005 | 2 |
| 2025000006 | 2 |
| 2025000007 | 2 |
| 2025000008 | 2 |
| 2025000009 | 2 |
| 2025000010 | 2 |
| 2025000011 | 2 |
| 2025000012 | 2 |
| 2025000013 | 2 |
| 2025000014 | 2 |

_628 further row(s) not listed; re-run with a larger `--limit` for the full set._

## RED 2 · One vendor invoice number entered twice for the same vendor

RED — must be fixed or waived · reads `shp_bill_inv` · **1 repeated invoice number**.

> The new schema treats (vendor, invoice no.) as unique, case-insensitively and with surrounding spaces stripped — which is how the migration stores it.

| invoice_no | sbi_vnd_code | occurrences |
|---|---|---|
| 2026-46 | IS00697 | 2 |

## AMBER 5 · Provisions with a pay date but no payment voucher

AMBER — migrated as-is, recorded · reads `shp_arr_head` · **17 rows**.

> Behaviour change B2: a pay date is copied only when its voucher column is set, so each of these dates is dropped. The rows are listed because "paid" disappearing off a document is exactly the kind of change Finance must see before it happens, not after.

| sah_sys_id | sah_trans_no | sah_pay_date |
|---|---|---|
| 2026003841 | 2026000901 | 2026-08-26 00:00:00 |
| 2026003842 | 2026000902 | 2026-08-26 00:00:00 |
| 2026003843 | 2026000903 | 2026-08-26 00:00:00 |
| 2026003844 | 2026000904 | 2026-08-26 00:00:00 |
| 2026003845 | 2026000905 | 2026-08-26 00:00:00 |
| 2026003846 | 2026000906 | 2026-08-26 00:00:00 |
| 2026003847 | 2026000907 | 2026-08-26 00:00:00 |
| 2026003848 | 2026000908 | 2026-08-26 00:00:00 |
| 2026003955 | 2026000925 | 2026-08-31 00:00:00 |
| 2026003956 | 2026000926 | 2026-08-31 00:00:00 |

_7 further row(s) not listed; re-run with a larger `--limit` for the full set._

## AMBER 7 · Provision cost types with no `ship_cost_type` row

AMBER — migrated as-is, recorded · reads `shp_arr_head.sah_cost_type` · **4 unmatched values**.

> Unmatched values are seeded as UNKNOWN and inactive, so history renders and no new document can pick one. Empty masters mean T004 has not seeded yet.

| value |
|---|
| EDN |
| EMKL |
| PIB |
| SHIP |

## AMBER 7 · Invoice cost types (export flex 2) with no `ship_cost_type` row

AMBER — migrated as-is, recorded · reads `shp_arr_inv.sai_flex_2` · **4 unmatched values**.

| value |
|---|
| COMM |
| EMKL |
| INS |
| SHIP |

## AMBER 7 · Facilities with no `ship_facility` row

AMBER — migrated as-is, recorded · reads `shp_arr_det.sad_fasilitas` · **16 unmatched values**.

> Unmatched facilities are auto-registered inactive by the backfill, so nothing is lost; the list is here so Finance can name the real ones first.

| value |
|---|
| BKPM |
| BKPM, COO (RCEP) |
| COO (FORM AI) |
| COO (RCEP) |
| E-COO (FORM D) |
| E-COO (FORM E) |
| E-COO (FORM E) - LS |
| KITE |
| KITE , COO (RCEP) |
| KITE - FORM AI |

_6 further row(s) not listed; re-run with a larger `--limit` for the full set._

## AMBER 8 · Provision cost lines with no main or provision account

AMBER — migrated as-is, recorded · reads `shp_arr_det` · **162 rows**.

> History migrates without accounts, but a migrated provision that is still open cannot be confirmed until somebody fills them in.

| sad_sys_id | sad_sai_sys_id | sad_main_acnt | sad_prov_acnt |
|---|---|---|---|
| 2025000061 | 2025000037 | 401100 |  |
| 2025000064 | 2025000039 | 401100 |  |
| 2025000091 | 2025000048 | 401100 |  |
| 2025000136 | 2025000067 | 401100 |  |
| 2025000137 | 2025000068 | 401100 |  |
| 2025000138 | 2025000069 | 401100 |  |
| 2025000142 | 2025000072 | 401100 |  |
| 2025000151 | 2025000076 | 401100 |  |
| 2025000152 | 2025000077 | 401100 |  |
| 2025000162 | 2025000082 | 401100 |  |

_152 further row(s) not listed; re-run with a larger `--limit` for the full set._
