# spec.md — ShipMark Module (Phase 1)
 
---

## 1. Database Schema
DDL lengkap ada di **`ship_mark_schema.sql`** (4 tabel + sequence + trigger + seed `TPL_BEKAERT_AU`).
Jalankan sebagai raw migration:

```php
// database/migrations/2026_07_11_000001_create_ship_mark_tables.php
public function up(): void {
    DB::connection('oracle')->unprepared(file_get_contents(
        module_path('ShipMark','database/sql/ship_mark_schema.sql')
    ));
}
```
> Jangan pakai Schema builder utk sequence/trigger (11g) — pakai `unprepared` dgn file SQL.
> Split statement per `/` bila driver rewel dgn multiple statements.
 
---

## 2. Data Source Spec (read MGTDAT)

### 2.1 Base per-box query (grain CARTON = row-level)
```sql
SELECT
  soim_item_code                                                               AS item_code,   -- ITEM
  ss.ss_batch_no                                                               AS lot_no,      -- LOT_NO / MERGE
  ss.ss_qty                                                                    AS net_wt,      -- NET_WT
  ss.ss_gross_wgt                                                              AS gross_wt,    -- GROSS_WT
  prd.prd_tare_wt                                                              AS tare_wt,     -- TARE (fallback gross-net)
  ss.ss_invi_shade                                                            AS color_mgt,   -- COLOR_MGT (shade)
  prd.prd_color                                                                AS tube_color,  -- TUBE_COLOR
  prd.prd_sub_units                                                            AS bobbin_qty,  -- BOBBIN_QTY (bobbin/box)
  prd.prd_qty                                                                  AS box_wt,      -- berat box (utk berat/bobbin)
  prd.prd_box_type                                                             AS box_type,
  SUBSTR(ss.ss_pallet_no,1,2)||'/'||
    LPAD(SUBSTR(ss.ss_pallet_no,INSTR(ss.ss_pallet_no,'/',1)+1,5),5,0)||'/'||
    LPAD(ss.ss_cart_no,2,0)                                                    AS pallet_no,   -- PALLET_NO
  ss.ss_cart_no                                                                AS carton_no,   -- CARTON_NO
  sohm_cust_code                                                               AS cust_code
FROM ot_so_head_mgt
JOIN ot_so_item_mgt                     ON soim_sohm_sys_id = sohm_sys_id
JOIN ot_so_scan_mgt        ss           ON ss.ss_soim_sys_id = soim_sys_id
LEFT JOIN ot_wms_pack_table_althara prd ON prd.prd_scan_code = ss.ss_scan_code
WHERE sohm_txn_code = :p_txn_code
  AND sohm_no       = :p_no;
```

### 2.2 Grain (GROUP BY) — dibangun di `GrainResolver` di atas 2.1
| Grain | GROUP BY | net/gross/bobbin | lot | pallet/carton no |
|---|---|---|---|---|
| CARTON | (row-level) | nilai baris | nilai baris | carton_no |
| PALLET | pallet_no | SUM | MULTI-aware | pallet_no |
| ITEM_LOT | item_code, lot_no | SUM | lot | '-' |
| ITEM | item_code | SUM | MULTI-aware | '-' |
| UNIFORM | (none) | SUM semua | MULTI-aware | '-' |

`lot MULTI-aware`: `CASE WHEN COUNT(DISTINCT ss_batch_no) > 1 THEN 'MULTI' ELSE MAX(ss_batch_no) END`.
`unit_count` = `COUNT(*)` unit di grain itu; `unit_seq` = ROW_NUMBER utk format `n/total`.
`berat/bobbin` (COMPUTED, bila diminta) = `SUM(box_wt) / NULLIF(SUM(bobbin_qty),0)`.

### 2.3 ESC / CONTRACT_NO resolver (dari user)
```sql
SELECT NVL(soh_ref_txn_code, soh_txn_code)||'-'||NVL(soh_ref_no, soh_no) AS so_no
FROM   ot_so_head, ot_so_head_mgt
WHERE  sohm_ref_sys_id = soh_sys_id
  AND  sohm_txn_code = :p_txn_code
  AND  sohm_no       = :p_no;
```

### 2.4 Field → source mapping (kamus VALUE_REF)
| VALUE_REF | TYPE | Sumber |
|---|---|---|
| ITEM | SYSTEM | soim_item_code |
| LOT_NO | SYSTEM | ss_batch_no (/ prd_merge_no) |
| NET_WT | SYSTEM | ss_qty (SUM) |
| GROSS_WT | SYSTEM | ss_gross_wgt (SUM) |
| TARE | SYSTEM | prd_tare_wt (fallback gross-net) |
| PALLET_NO | SYSTEM | ss_pallet_no fmt + n/total |
| CARTON_NO | SYSTEM | ss_cart_no + n/total |
| TUBE_COLOR | SYSTEM | prd_color |
| BOBBIN_QTY | SYSTEM | prd_sub_units |
| BOX_TYPE | SYSTEM | prd_box_type |
| COLOR_MGT | SYSTEM | ss_invi_shade |
| CONTRACT_NO | SYSTEM | EscResolver (2.3) |
| WEIGHT_PER_BOBBIN | COMPUTED | box_wt / bobbin_qty |
| CUST_MATERIAL_NO | MASTER | CustMap MATERIAL_NO, key=item |
| CUST_ITEM_CODE | MASTER | CustMap ITEM_CODE, key=item |
| COLOR_CUST | MANUAL* | (map COLOR_NAME, key=shade — bila di-save) |
| NCM | MASTER | CustMap NCM, key=item |
| CUST_PO_NO / LC_NO / INVOICE_NO / POS_NO | MANUAL | entry (next integration) |
| BRAND_LABEL | MASTER | per customer |

### 2.5 Learn-as-you-go UPSERT (SHIP_MARK_CUST_MAP)
```sql
MERGE INTO ship_mark_cust_map d
USING (SELECT :cust cust, :attr attr, :key mgtkey, :val val FROM dual) s
ON (d.smcm_cust_code=s.cust AND d.smcm_attr_type=s.attr AND d.smcm_mgt_key=s.mgtkey)
WHEN MATCHED THEN UPDATE SET d.smcm_cust_value=s.val, d.smcm_upd_dt=SYSDATE
WHEN NOT MATCHED THEN INSERT (smcm_cust_code,smcm_attr_type,smcm_mgt_key,smcm_cust_value)
                       VALUES (s.cust,s.attr,s.mgtkey,s.val);
```
> Simpan hanya bila field MASTER diisi manual pertama kali (nilai sebelumnya null).
 
---

## 3. Service & Livewire Contracts
Lihat design.md §2–§3 utk signature. Livewire wire:model pakai array `manualValues[item][valueRef]`;
submit → `GenerateService::build(...)` → return path PDF → `Storage`/stream download.
 
---

## 4. Validation Rules
| Field | Rule | Error |
|---|---|---|
| SMT_TEMPLATE_CODE | required, unique, alnum+underscore | "shipmark: kode template wajib & unik" |
| SMT_GRAIN | in enum | "shipmark: grain tidak valid" |
| SMT_PAPER_SIZE=CUSTOM | width_mm & height_mm > 0 | "shipmark: ukuran custom wajib w×h" |
| SMTL_VALUE_TYPE=STATIC | static_value required | "shipmark: STATIC wajib isi teks" |
| SMTL_VALUE_TYPE in (SYSTEM,MASTER,MANUAL,COMPUTED) | value_ref required & ada di kamus | "shipmark: value_ref tidak dikenal" |
| generate: copies | 1..20 | "shipmark: copies 1–20" |
| generate: template.cust_code | == SSC sohm_cust_code (bila di-set) | "shipmark: template beda customer dgn SSC" |
 
---

## 5. File Structure
Lihat design.md §5. Taruh `ship_mark_schema.sql` di `Modules/ShipMark/database/sql/`.
 
---

## 6. Dependencies (composer)
```
Perlu dicek/ditambah:
  mpdf/mpdf : ^8.2        ← render PDF ukuran arbitrer (A4/A5/custom mm)
              (alternatif: barryvdh/laravel-dompdf bila sudah dipakai di repo)
 
Sudah ada (jangan duplikat):
  yajra/laravel-oci8
  livewire/livewire, livewire/volt, livewire/flux (Pro)
  spatie/laravel-permission
  maatwebsite/excel
  nwidart/laravel-modules
```
 
