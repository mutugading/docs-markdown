 # spec.md — Transporter v1: spesifikasi siap kode

Ini berkas yang paling sering dibuka saat mengerjakan task. Isinya hal yang tidak
boleh ditebak: nama kolom, tipe, rumus, ambang, dan pemetaan.

---

## 0. Kepemilikan schema — aturan yang tidak bisa ditawar

> **Seluruh 23 tabel baru milik modul ini (22 + `transp_carrier_alias`, D-20) dibuat di schema `MGTHRIS`, lewat koneksi
> `oracle_mgthris`. Modul ini TIDAK PERNAH membuat tabel di `MGTDAT`.**

| Schema | Koneksi | Peran modul Transporter |
|---|---|---|
| **`MGTHRIS`** | `oracle_mgthris` | **Pemilik.** 23 tabel baru, seluruh migration, seluruh tulis-baca data modul |
| `MGTDAT` | `oracle_mgtdat` | **Milik Orion.** Baca saja — `OT_INVOICE_HEAD`, `OT_GR_HEAD`, `OM_SUPPLIER`, `IM_VS_STATIC_VALUE`, `FT_OS`, `MENU_USER`, dst. |
| `MGTAPPS` | `oracle_mgtapps` | Tidak dipakai modul ini |

**Tiga pengecualian tulis ke `MGTDAT`, dan tidak ada yang keempat:**

| Objek | Kapan | Oleh |
|---|---|---|
| `FT_UNPOSTED_TRANS_HEADER` | posting `TPJV` / `TJV` | service GL bersama di `Core` |
| `FT_UNPOSTED_TRANS_DETAIL` | idem | idem |
| `FM_TRAN_DOC_NO` (`tdoc_cur_no`) | idem — memajukan nomor dokumen | idem |

**Compatibility view (§4.5) memang dibuat di `MGTDAT`** — itu justru maksudnya: view
bernama sama dengan tabel lama supaya ± 25 objek dan 12 report Orion tidak patah. Tapi
view, bukan tabel; `WITH READ ONLY`; dan hanya di jendela cutover (T074), tidak pada
fase mana pun sebelumnya.

Setiap migration modul ini membuka dengan:

```php
protected $connection = 'oracle_mgthris';
```

Migration Transporter dengan `$connection = 'oracle_mgtdat'` adalah **kesalahan**,
bukan pilihan — kecuali satu-satunya migration compatibility view di T074, yang
memang membuat view dan bukan tabel.

**Cara memeriksa:**

```bash
grep -rn "oracle_mgtdat" Modules/Transporter/database/migrations/
# harus kosong, kecuali migration compatibility view T074
```

---

## 1. Konvensi

**23 tabel baru** di `MGTHRIS` (koneksi `oracle_mgthris`) — tabel ke-23 `transp_carrier_alias` ditambahkan di T025 (D-20).


| Hal | Aturan |
|---|---|
| Nama tabel | `snake_case` lowercase: `transp_order` |
| Nama kolom di migration | **lowercase** ber-prefix: `tro_sys_id`. Dideklarasikan UPPERCASE, SQLite mengembalikannya UPPERCASE juga dan **setiap** pembacaan atribut model bernilai null di CI, sementara Oracle tetap jalan — test jadi tidak menguji apa pun. Grammar oci8 meng-uppercase identifier sendiri, jadi DDL Oracle-nya identik. Lihat `DECISIONS.md` D-08 |
| Nama constraint & index di migration | tetap **UPPERCASE**: `TCA_PK01`, `TRC_UK01`, `TRO_FK01` |
| Nama kolom di model/`$fillable`/query | lowercase: `tro_sys_id` (Oracle case-insensitive) |
| PK | `{prefix}_SYS_ID` `varchar(30)`, diisi `SysIdHelper::generate()` |
| Audit | `{prefix}_CREATED_BY` `varchar(30)`, `{prefix}_CREATED_TIMESTAMP` datetime, `{prefix}_MODIFIED_BY`, `{prefix}_MODIFIED_TIMESTAMP` — **di setiap tabel, tanpa kecuali** |
| Traceability | `{prefix}_LEGACY_ID` `varchar(30)` nullable — nilai `*_SYS_ID` lama (§4.2) |
| Uang | `decimal(18, 2)` — IDR maupun USD. Tidak pernah `NUMBER` polos (T-16) |
| Berat | `decimal(14, 3)` kg |
| Boolean | `$table->boolean(...)` → **`CHAR(1)` dengan default `'0'`/`'1'`** di Oracle, bukan `NUMBER(1)`. Itu pemetaan bawaan grammar `yajra/laravel-oci8` dan dipakai seluruh modul lain — diverifikasi lewat `migrate --pretend` di T002 |
| Soft delete | Kolom eksplisit `{prefix}_DELETED_BY` / `_AT` / `_DELETE_REASON`, **bukan** `SoftDeletes` trait Laravel — supaya alasannya wajib |
| Nama constraint & index | **Selalu dinamai eksplisit**: `{PREFIX}_PK01`, `{PREFIX}_UK01`, `{PREFIX}_NX01`, `{PREFIX}_FK01` — mis. `TCA_PK01`. Dibiarkan otomatis, grammar oci8 memampatkan nama agar muat 30 karakter dan hasilnya tidak terbaca (`trans_charg_typ_tc_sy_id_pk`). Prefix kolom sudah unik se-database, jadi nama pendek ini tidak pernah bentrok |

### Prefix per tabel

| Tabel | Prefix | Tabel | Prefix |
|---|---|---|---|
| `transp_carrier` | `TCA_` | `transp_additional_expense` | `TAE_` |
| `transp_rate_card` | `TRC_` | `transp_additional_expense_line` | `TAL_` |
| `transp_rate_line` | `TRL_` | `transp_provision` | `TRP_` |
| `transp_charge_type` | `TCT_` | `transp_provision_dn` | `TPD_` |
| `transp_service_category` | `TSC_` | `transp_bill` | `TRB_` |
| `transp_posting_account` | `TPA_` | `transp_bill_line` | `TBL_` |
| `transp_document_type` | `TDT_` | `transp_document_scan` | **`TDC_`** |
| `transp_order` | `TRO_` | `transp_posting_log` | `TPL_` |
| `transp_order_dn` | `TOD_` | `transp_payment` | `TPY_` |
| `transp_order_cost` | `TOC_` | `transp_grn_pull_attempt` | `TGP_` |
| `transp_dn_stage` | **`TST_`** | `transp_migration_exception` | `TME_` |

> `TST_` dan `TDC_` menyimpang dari PRD §4.2 yang memberi `tds_` kepada keduanya.
> Lihat `gap.md` C-4 dan `DECISIONS.md` D-02.

---

## 2. DDL

Setiap tabel juga punya 4 kolom audit + `{prefix}_LEGACY_ID` yang **tidak diulang**
di daftar di bawah. Tulis semuanya.

### 2.1 Master

**`transp_carrier`** — satu baris per pengangkut (± 50, bukan 739)

| Kolom | Tipe | Aturan |
|---|---|---|
| `TCA_SYS_ID` | varchar(30) PK | |
| `TCA_TYPE` | varchar(15) | `VENDOR` / `BUYER_BORNE` / `INTERNAL` (F-01.16) |
| `TCA_SUPP_CODE` | varchar(20) nullable, **UNIQUE** | → `MGTDAT.OM_SUPPLIER.SUPP_CODE`. **Wajib** untuk `VENDOR` & `INTERNAL`, **harus NULL** untuk `BUYER_BORNE` (F-01.1) |
| `TCA_LABEL` | varchar(120) nullable | Hanya untuk `BUYER_BORNE`, yang tidak punya padanan ERP. Untuk tipe lain **harus NULL** — nama dibaca dari `OM_SUPPLIER` (F-01.2) |
| `TCA_IS_GROSS_UP` | boolean default 0 | **Deprecated** (F-01.3a). Mengaktifkan butuh persetujuan eksplisit + activity log |
| `TCA_IS_ACTIVE` | boolean default 1 | |
| `TCA_REMARK` | varchar(255) nullable | |

**`transp_rate_card`** — satu baris per kombinasi yang berlaku (± 716)

| Kolom | Tipe | Aturan |
|---|---|---|
| `TRC_SYS_ID` | varchar(30) PK | |
| `TRC_CARRIER_ID` | varchar(30) FK → `transp_carrier` | |
| `TRC_SERVICE_TYPE` | varchar(10) | `DESPATCH` / `CHIP` |
| `TRC_DESTINATION` | varchar(60) nullable | Untuk `DESPATCH`. Lookup `IM_VS_STATIC_VALUE` `VSSV_VS_CODE='DESTINATION'` (45 nilai) |
| `TRC_CHIP_VENDOR_CODE` | varchar(20) nullable | Untuk `CHIP`, → `OM_SUPPLIER.SUPP_CODE` (F-01.13). Menggantikan kolom serbaguna `MTM_CUST_SUPP` |
| `TRC_TRUCK_TYPE` | varchar(30) | Lookup `TYPE_TRUCK` (8 nilai) |
| `TRC_TRUCK_CAP` | decimal(14,3) | kg |
| `TRC_VALID_FROM` | date | **F-01.5** |
| `TRC_VALID_TO` | date nullable | NULL = masih berlaku |
| `TRC_IS_ACTIVE` | boolean default 1 | |
| `TRC_LEGACY_MTM_NO` | varchar(20) nullable | `MTM_NO` lama |

UNIQUE `(TRC_CARRIER_ID, TRC_SERVICE_TYPE, TRC_DESTINATION, TRC_CHIP_VENDOR_CODE, TRC_TRUCK_TYPE, TRC_VALID_FROM)`

**`transp_rate_line`** — tarif berjenjang

| Kolom | Tipe | Aturan |
|---|---|---|
| `TRL_SYS_ID` | varchar(30) PK | |
| `TRL_RATE_CARD_ID` | varchar(30) FK → `transp_rate_card` **ON DELETE CASCADE** | |
| `TRL_PRIORITY` | number(3) | urutan penerapan |
| `TRL_RATE_TYPE` | varchar(1) | `W` (borongan) / `Q` (per kg) |
| `TRL_MAX_CAP` | decimal(14,3) nullable | kg |
| `TRL_IS_OVERFLOW` | boolean default 0 | **Menggantikan nilai sentinel `MTR_MAX_CAP = 1`** sistem lama |
| `TRL_RATE` | decimal(18,2) | |

UNIQUE `(TRL_RATE_CARD_ID, TRL_PRIORITY)`

**`transp_carrier_alias`** (`TCL_`) — ditambahkan T025 (D-20). `TCL_SYS_ID` PK, `TCL_ALIAS` varchar(240) UNIQUE
(teks `GH_FLEX_05` dinormalisasi: trim, huruf besar, spasi ganda dirapatkan), `TCL_CARRIER_ID` FK → `transp_carrier`,
`TCL_SOURCE` varchar(10) `MIGRATION`/`MANUAL`, `TCL_IS_ACTIVE`, legacy id + 4 kolom audit.

**`transp_charge_type`** (`TCT_`) — `TCT_CODE` varchar(30) UNIQUE, `TCT_NAME` varchar(100), `TCT_REQUIRES_ATTACHMENT` boolean, `TCT_IS_ACTIVE`.
Seed: `TOL`, `SOLAR`, `INAP`, `KAWAL`, `JALAN_DITUTUP`, `AMBIL_BARANG` (F-04.1, C-11).

**`transp_service_category`** (`TSC_`) — `TSC_CODE` varchar(30) UNIQUE, `TSC_NAME` varchar(100), `TSC_IS_ACTIVE`.
Seed: `AMBIL_BARANG`, `RETUR_BENANG`, `PALLET`, `TRUCKING_BANDARA` (F-02.12, F-02.12a).

**`transp_posting_account`** (`TPA_`) — akun GL dari data, bukan literal (F-05.7, T-09)

| Kolom | Tipe |
|---|---|
| `TPA_SYS_ID` varchar(30) PK · `TPA_POSTING_TYPE` varchar(40) · `TPA_SERVICE_TYPE` varchar(10) nullable · `TPA_DR_ACCOUNT` varchar(20) nullable · `TPA_CR_ACCOUNT` varchar(20) nullable · `TPA_SUB_ACCOUNT_SOURCE` varchar(30) nullable · `TPA_VALID_FROM` date · `TPA_VALID_TO` date nullable · `TPA_DESCRIPTION` varchar(255) · `TPA_IS_ACTIVE` boolean | |

> ⚠️ **Tabel seed di bawah digantikan `DECISIONS.md` D-18** (2026-09-24): `BILL_REVERSAL` ternyata
> mendebit akun hutang provisi (`208027`/`208026`), bukan akun beban, dan ada jenis posting
> `BILL_EXPENSE_SERVICE` per kategori jasa lewat kolom baru `TPA_SERVICE_CATEGORY_CODE`.
> Tabel lama dibiarkan untuk jejak.

Seed (Appendix B PRD — **nama akun masih asumsi, minta konfirmasi Finance sebelum go-live**):

| `POSTING_TYPE` | `SERVICE_TYPE` | DR | CR |
|---|---|---|---|
| `PROVISION` | `DESPATCH` | `404001` | `208027` |
| `PROVISION` | `CHIP` | `401001` | `208026` |
| `BILL_REVERSAL` | `DESPATCH` | `404001` | — |
| `BILL_REVERSAL` | `CHIP` | `401001` | — |
| `BILL_AP` | — | — | `203001` (sub-account = kode supplier) |
| `BILL_PPH` | — | — | `206005` |
| `BILL_PPN_IN` | — | `108004` | — |
| `BILL_PPN_IN_05` | — | `108005` | — |

**`transp_document_type`** (`TDT_`) — `TDT_CODE` varchar(10) UNIQUE (`LDN`/`PDN`/`JWDN`), `TDT_FILE_PREFIX` varchar(10), `TDT_FOLDER_PATTERN` varchar(255), `TDT_IS_CONTROLLED` boolean, `TDT_IS_ACTIVE` (F-10.7).
`CHPGRN` **tidak** diseed — chip tidak dikontrol dokumennya (F-10.6, C-15).

### 2.2 Transaksi

**`transp_order`** (`TRO_`) — pengganti `MGT_TRANSP_HEAD`

| Kolom | Tipe | Aturan |
|---|---|---|
| `TRO_SYS_ID` | varchar(30) PK | |
| `TRO_TXN_CODE` | varchar(10) | `TPDN` / `TPCHP` / `TPSVC` |
| `TRO_TRANSP_NO` | varchar(20) | `{YYYY}{6 digit}` dari sequence, **bukan `MAX()+1`** (F-02.2, T-04) |
| `TRO_ORDER_DATE` | date | |
| `TRO_CARRIER_ID` | varchar(30) FK → `transp_carrier` | |
| `TRO_RATE_CARD_ID` | varchar(30) nullable FK | rate card yang **berlaku pada `TRO_ORDER_DATE`** |
| `TRO_SERVICE_CATEGORY_ID` | varchar(30) nullable FK | **wajib bila `TRO_TXN_CODE = 'TPSVC'`** (F-02.12) |
| `TRO_DESTINATION` | varchar(60) nullable | |
| `TRO_POLICE_NO` | varchar(20) nullable | |
| `TRO_DRIVER` | varchar(60) nullable | |
| `TRO_TRUCK_TYPE` | varchar(30) nullable | |
| `TRO_QTY_KG` | decimal(14,3) | |
| `TRO_FREIGHT_AMOUNT` | decimal(18,2) | jumlah `transp_order_cost` |
| `TRO_OTHER_AMOUNT` | decimal(18,2) default 0 | jumlah additional expense yang terbawa (F-04.10) |
| `TRO_TOTAL_AMOUNT` | decimal(18,2) | |
| `TRO_IS_COST_OVERRIDDEN` | boolean default 0 | F-02.4 |
| `TRO_OVERRIDE_REASON` | varchar(255) nullable | wajib bila di atas = 1 |
| `TRO_STATUS` | number(1) | `TransactionStatusEnum` |
| `TRO_SOURCE` | varchar(10) | `MANUAL` / `GRN_PULL` / `AUTO_DN` / (`GATE`, fase berikutnya) |
| `TRO_SOURCE_REF` | varchar(30) nullable | mis. `gh_sys_id` |
| `TRO_SUBMITTED_BY` / `_AT` | varchar(30) / datetime, nullable | |
| `TRO_APPROVED_BY` / `_AT` | varchar(30) / datetime, nullable | `'MIGRATION'` untuk data lama (C-17) |
| `TRO_REJECTED_BY` / `_AT` / `TRO_REJECT_REASON` | nullable | alasan **wajib** saat reject (F-02.6a) |
| `TRO_DELETED_BY` / `_AT` / `TRO_DELETE_REASON` | nullable | soft delete (F-02.9) |
| `TRO_IS_LEGACY` | boolean default 0 | **kunci unique index fungsional** (C-08) |
| `TRO_LEGACY_NO` | varchar(60) nullable | nomor lama non-standar (`AMBIL BARANG` dll, C-06) |
| `TRO_IS_MULTI_TRUCK` | boolean default 0 | F-09.14 |
| `TRO_TRUCK_COUNT_NOTE` | varchar(120) nullable | F-09.14 |
| `TRO_GATE_IN_AT` / `_OUT_AT` / `_VERIFIED_BY` | nullable | §5.12 — disiapkan, tidak dipakai v1 |
| `TRO_REMARK` | varchar(255) nullable | |

Index: `(TRO_TXN_CODE, TRO_ORDER_DATE)`, `(TRO_CARRIER_ID)`, `(TRO_STATUS)`, `(TRO_SOURCE)`.
Unique: lihat §4.

**`transp_order_dn`** (`TOD_`) — `TOD_SYS_ID` PK, `TOD_ORDER_ID` FK, `TOD_DN_TXN_CODE` varchar(10) (`LDN`/`PDN`/`JWDN`/`CHPGRN`), `TOD_DN_NO` varchar(30), `TOD_DN_DATE` date, `TOD_DN_QTY` decimal(14,3), `TOD_DN_SYS_ID` varchar(30) nullable (referensi Orion), `TOD_SOURCE` varchar(20) nullable (`GRN_TRIGGER` untuk data C-14).
UNIQUE `(TOD_ORDER_ID, TOD_DN_TXN_CODE, TOD_DN_NO)`. Index `(TOD_DN_TXN_CODE, TOD_DN_NO)` — **wajib**, 92k baris (RK-08).

**`transp_order_cost`** (`TOC_`) — `TOC_SYS_ID` PK, `TOC_ORDER_ID` FK, `TOC_RATE_LINE_ID` nullable FK, `TOC_PRIORITY` number(3), `TOC_RATE_TYPE` varchar(1), `TOC_QTY` decimal(14,3), `TOC_RATE` decimal(18,2), `TOC_TOTAL_AMOUNT` decimal(18,2), `TOC_DESCRIPTION` varchar(255).

**`transp_dn_stage`** (`TST_`) — `TST_SYS_ID` PK, `TST_DN_SYS_ID` varchar(30) **UNIQUE** (F-03.6), `TST_DN_TXN_CODE`, `TST_DN_NO`, `TST_DN_DATE`, `TST_QTY_KG`, `TST_CARRIER_CODE` (dari `INVH_FLEX_06`), `TST_POLICE_NO` (`INVH_FLEX_02`), `TST_DRIVER` (`INVH_FLEX_03`), `TST_DESTINATION` (`INVH_FLEX_07`), `TST_TRUCK_TYPE`, `TST_ORDER_ID` nullable FK, `TST_PULLED_AT` datetime.

**`transp_grn_pull_attempt`** (`TGP_`) — `TGP_SYS_ID` PK, `TGP_GH_SYS_ID` varchar(30), `TGP_GH_NO` varchar(30), `TGP_ATTEMPTED_AT` datetime, `TGP_ATTEMPTED_BY` varchar(30), `TGP_TRIGGER` varchar(10) (`MANUAL`), `TGP_RESULT` varchar(10) (`SUCCESS`/`FAILED`), `TGP_FAIL_REASON` varchar(500), `TGP_ORDER_ID` nullable FK, `TGP_ORIGINAL_PAYLOAD` clob, `TGP_CORRECTED_PAYLOAD` clob (F-09.4, F-09.11).

### 2.3 Additional expense

**`transp_additional_expense`** (`TAE_`) — `TAE_SYS_ID` PK, `TAE_NO` varchar(20) UNIQUE, `TAE_ORDER_ID` varchar(30) FK **NOT NULL** (F-04.3), `TAE_EXPENSE_DATE` date, `TAE_TOTAL_AMOUNT` decimal(18,2), `TAE_STATUS` number(1) (`AdditionalExpenseStatusEnum`), kolom approval lengkap (`_SUBMITTED_BY/_AT`, `_APPROVED_BY/_AT`, `_REJECTED_BY/_AT`, `_REJECT_REASON`), `TAE_BILL_LINE_ID` varchar(30) nullable FK, `TAE_CANCEL_REASON` varchar(255) nullable.

**`transp_additional_expense_line`** (`TAL_`) — `TAL_SYS_ID` PK, `TAL_EXPENSE_ID` FK **ON DELETE CASCADE**, `TAL_CHARGE_TYPE_ID` FK, `TAL_AMOUNT` decimal(18,2), `TAL_DESCRIPTION` varchar(255), `TAL_ATTACHMENT_PATH` varchar(500) nullable (F-04.4).

### 2.4 Provisi & tagihan

**`transp_provision`** (`TRP_`)

| Kolom | Tipe | Aturan |
|---|---|---|
| `TRP_SYS_ID` | varchar(30) PK | |
| `TRP_ORDER_ID` | varchar(30) nullable FK, **UNIQUE** | **F-06.9: satu induk = satu provisi.** NULL untuk 331 baris yatim C-04 — Oracle mengizinkan banyak NULL di unique index |
| `TRP_NO` | varchar(30) | `= TRO_TXN_CODE ‖ '-' ‖ TRO_TRANSP_NO` |
| `TRP_PROVISION_DATE` | date | |
| `TRP_CARRIER_ID` | varchar(30) nullable FK | NULL untuk C-05 |
| `TRP_LEGACY_TP_CODE` | varchar(20) nullable | kode mentah bila carrier tidak ketemu (C-05) |
| `TRP_AMOUNT` | decimal(18,2) | biaya angkutan |
| `TRP_OTHER_AMOUNT` | decimal(18,2) default 0 | additional expense yang terbawa (F-04.10) |
| `TRP_GROSS_UP_AMOUNT` | decimal(18,2) default 0 | |
| `TRP_TOTAL_AMOUNT` | decimal(18,2) | |
| `TRP_DUE_DATE` | date | aturan 10/25 §3.7.3 |
| `TRP_STATUS` | number(1) | `ProvisionStatusEnum` |
| `TRP_JV_TRAN_CODE` | varchar(10) nullable | `TPJV` baru, `JV` historis |
| `TRP_JV_NO` | varchar(30) nullable | |
| `TRP_JV_DATE` | date nullable | |
| `TRP_IS_ORPHAN` | boolean default 0 | C-04 |

**`transp_provision_dn`** (`TPD_`) — `TPD_SYS_ID` PK, `TPD_PROVISION_ID` FK, `TPD_ORDER_DN_ID` nullable FK, `TPD_DN_TXN_CODE`, `TPD_DN_NO`.

**`transp_bill`** (`TRB_`) — `TRB_SYS_ID` PK, `TRB_TRX_NO` varchar(30) UNIQUE (`TBILL-{YYYY}{6}`), `TRB_CARRIER_ID` FK, `TRB_INVOICE_NO` varchar(40), `TRB_INVOICE_DATE` date, `TRB_CURRENCY` varchar(5) default `IDR`, `TRB_FP_NO` varchar(40) nullable, `TRB_FP_DATE` date nullable, `TRB_DPP_AMOUNT`, `TRB_PPN_RATE` decimal(5,2), `TRB_PPN_AMOUNT`, `TRB_PPH_RATE` decimal(5,2), `TRB_PPH_AMOUNT`, `TRB_TOTAL_AMOUNT` — semua decimal(18,2), `TRB_TYPE` varchar(10) (`YARNS`/`CHIPS`/`OTHERS`), `TRB_STATUS` number(1), `TRB_TJV_NO` varchar(30) nullable, `TRB_TJV_DATE` date nullable.

**`transp_bill_line`** (`TBL_`)

> ⚠️ **Koreksi (T007).** Versi pertama tabel ini punya `TBL_ADDITIONAL_EXPENSE_ID`,
> pasangan dua arah dari `TAE_BILL_LINE_ID` di §2.3. **Dibuang** — hubungannya
> disimpan satu arah saja, di `TAE_BILL_LINE_ID`. Lihat `DECISIONS.md` D-06.

| Kolom | Tipe | Aturan |
|---|---|---|
| `TBL_SYS_ID` | varchar(30) PK | |
| `TBL_BILL_ID` | varchar(30) FK | |
| `TBL_LINE_TYPE` | varchar(20) | `PROVISION` / `EXPENSE_DIRECT` (F-06.7) |
| `TBL_PROVISION_ID` | varchar(30) nullable FK | wajib bila `PROVISION` |
| `TBL_DIFFERENCE_TYPE` | varchar(20) nullable | `ADDITIONAL` / `CANCELLATION` / `DEDUCTION` — wajib bila `EXPENSE_DIRECT` |
| `TBL_EXPENSE_ACCOUNT` | varchar(20) nullable | wajib bila `EXPENSE_DIRECT` (F-06.7a) |
| `TBL_REASON` | varchar(255) nullable | wajib bila `EXPENSE_DIRECT` |
| `TBL_AMOUNT` | decimal(18,2) | **negatif** untuk `CANCELLATION` & `DEDUCTION` (F-06.11) |

### 2.5 Infrastruktur

**`transp_document_scan`** (`TDC_`) — `TDC_SYS_ID` PK, `TDC_DN_TXN_CODE` varchar(10), `TDC_DN_NO` varchar(30), `TDC_STATUS` varchar(10) (`Pending`/`Scanned`/`Waived`), `TDC_FILE_NAME` varchar(255) nullable, `TDC_FILE_PATH` varchar(500) nullable, `TDC_FOUND_AT` datetime nullable, `TDC_CHANGE_SOURCE` varchar(10) (`JOB`/`MANUAL`), `TDC_CHANGED_BY` varchar(30) nullable, `TDC_WAIVE_REASON` varchar(255) nullable.
UNIQUE `(TDC_DN_TXN_CODE, TDC_DN_NO)`. Index `(TDC_STATUS)`.

**`transp_posting_log`** (`TPL_`) — `TPL_SYS_ID` PK, `TPL_POSTING_TYPE` varchar(10) (`JV`/`TJV`/`BPS`), `TPL_SOURCE_TABLE` varchar(60), `TPL_SOURCE_ID` varchar(30), `TPL_IDEMPOTENCY_KEY` varchar(80) **UNIQUE**, `TPL_TRAN_CODE` varchar(10), `TPL_DOC_NO` varchar(30) nullable, `TPL_STATUS` varchar(10) (`Pending`/`Success`/`Failed`), `TPL_PAYLOAD` clob, `TPL_ERROR_MESSAGE` varchar(2000) nullable, `TPL_POSTED_BY` varchar(30), `TPL_ORION_USER_ID` varchar(30), `TPL_POSTED_AT` datetime nullable.

**`transp_payment`** (`TPY_`) — dibuat, **tidak dipakai v1** (§5.11, `gap.md` C-9).
`TPY_SYS_ID` PK, `TPY_BILL_ID` FK, `TPY_PAYMENT_DATE` date, `TPY_BANK_CODE` varchar(20), `TPY_BANK_ACNT` varchar(30), `TPY_AMOUNT` decimal(18,2), `TPY_TRAN_CODE` varchar(10), `TPY_VOUCHER_NO` varchar(30) nullable, `TPY_STATUS` number(1).

**`transp_migration_exception`** (`TME_`) — `TME_SYS_ID` PK, `TME_RULE_CODE` varchar(10) (`C-01`…`C-23`), `TME_SOURCE_TABLE` varchar(60), `TME_SOURCE_ID` varchar(30), `TME_REASON` varchar(500), `TME_PAYLOAD` clob, `TME_RESOLVED` boolean default 0, `TME_RESOLVED_BY` / `_AT` nullable (§6.1 butir 4).

---

## 3. Enum

```php
// Transaction/TransactionStatusEnum.php
enum TransactionStatusEnum: int {
    case Draft = 0; case Submitted = 1; case Approved = 2;
    case Rejected = 3; case Provisioned = 4; case Cancelled = 5;
}

// Transaction/AdditionalExpenseStatusEnum.php
enum AdditionalExpenseStatusEnum: int {
    case Draft = 0; case Submitted = 1; case Approved = 2;
    case Rejected = 3; case Billed = 4; case Cancelled = 5;
}

// Transaction/ProvisionStatusEnum.php
enum ProvisionStatusEnum: int {
    case Unposted = 0; case Posted = 1; case Matched = 2; case Billed = 3;
}

enum TransactionTypeEnum: string { case Tpdn = 'TPDN'; case Tpchp = 'TPCHP'; case Tpsvc = 'TPSVC'; }
enum OrderSourceEnum: string { case Manual = 'MANUAL'; case GrnPull = 'GRN_PULL'; case AutoDn = 'AUTO_DN'; case Gate = 'GATE'; }
enum CarrierTypeEnum: string { case Vendor = 'VENDOR'; case BuyerBorne = 'BUYER_BORNE'; case Internal = 'INTERNAL'; }
enum ServiceTypeEnum: string { case Despatch = 'DESPATCH'; case Chip = 'CHIP'; }
enum RateTypeEnum: string { case Flat = 'W'; case PerUnit = 'Q'; }
enum BillLineTypeEnum: string { case Provision = 'PROVISION'; case ExpenseDirect = 'EXPENSE_DIRECT'; }
enum DifferenceTypeEnum: string { case Additional = 'ADDITIONAL'; case Cancellation = 'CANCELLATION'; case Deduction = 'DEDUCTION'; }
enum DocumentStatusEnum: string { case Pending = 'Pending'; case Scanned = 'Scanned'; case Waived = 'Waived'; }
enum PostingTypeEnum: string { case Jv = 'JV'; case Tjv = 'TJV'; case Bps = 'BPS'; }
enum PostingStatusEnum: string { case Pending = 'Pending'; case Success = 'Success'; case Failed = 'Failed'; }
```

Setiap enum wajib punya `label()`, `badgeVariant()`, dan `options()` (CLAUDE.md §Enum Pattern).
`TransactionStatusEnum` juga `nextActionLabel()` dan `timelineSteps()`.

**Transisi yang sah** — `TransactionStatusEnum`:

| Dari | Ke | Syarat |
|---|---|---|
| `Draft` | `Submitted` | pembuat |
| `Draft` | `Cancelled` | pembuat, wajib alasan |
| `Submitted` | `Approved` | **user lain** + `transporter-approve-transaction` |
| `Submitted` | `Rejected` | idem, wajib alasan |
| `Rejected` | `Draft` | otomatis; pembuat boleh mengubah lagi |
| `Approved` | `Submitted` | unapprove oleh approver, **hanya selama belum diprovisi** |
| `Approved` | `Provisioned` | oleh `ProvisionService` |
| `Provisioned` | — | terkunci (F-02.7) |

---

## 4. Unique index fungsional (C-08)

21 nomor transaksi duplikat di data lama dibiarkan apa adanya (keputusan Finance),
jadi unique polos tidak bisa dipasang.

```sql
CREATE UNIQUE INDEX transp_order_no_uk ON transp_order (
    CASE WHEN TRO_IS_LEGACY = 0 THEN TRO_TXN_CODE || '-' || TRO_TRANSP_NO END
);
```

Migration T007 (`gap.md` C-5):

```php
// Guard-nya diikat ke sqlite, BUKAN ke Oracle — lihat peringatan di bawah.
if (DB::connection($this->connection)->getDriverName() === 'sqlite') {
    // SQLite/CI: tidak ada data legacy, unique biasa sudah cukup
    Schema::connection($this->connection)->table('transp_order', function (Blueprint $t) {
        $t->unique(['TRO_TXN_CODE', 'TRO_TRANSP_NO'], 'transp_order_no_uk');
    });
} else {
    DB::connection($this->connection)->statement(
        "CREATE UNIQUE INDEX transp_order_no_uk ON transp_order (
             CASE WHEN TRO_IS_LEGACY = 0 THEN TRO_TXN_CODE || '-' || TRO_TRANSP_NO END)"
    );
}
```

> ⚠️ **Koreksi (T007). Versi pertama blok ini memakai `getDriverName() === 'oci8'`
> dan itu salah.** Nama driver yang terdaftar di `config/database.php` untuk
> `oracle_mgthris` adalah **`oracle`**, bukan `oci8` — diverifikasi langsung
> 2026-09-17. Guard `'oci8'` tidak pernah cocok, jadi **produksi akan diam-diam
> mendapat unique polos**, dan migrasi T068 menolak 21 nomor duplikat yang justru
> sudah diputuskan Finance untuk dibiarkan.
>
> Percabangannya dibalik dengan sengaja: yang dideteksi adalah SQLite, bukan
> Oracle. Kalau suatu saat nama driver berubah lagi, yang terjadi paling buruk
> adalah CI merah — bukan produksi yang salah skema. Berlaku untuk **setiap**
> percabangan driver di modul ini, termasuk T074.

---

## 5. Sequence (`HM_MST_SEQUENCES`)

| Nama sequence | Untuk | Format hasil |
|---|---|---|
| `TRANSP_TPDN_NO_SEQ` | `TRO_TRANSP_NO` TPDN | `{YYYY}{6}` |
| `TRANSP_TPCHP_NO_SEQ` | idem TPCHP | `{YYYY}{6}` |
| `TRANSP_TPSVC_NO_SEQ` | idem TPSVC | `{YYYY}{6}` |
| `TRANSP_BILL_NO_SEQ` | `TRB_TRX_NO` | `TBILL-{YYYY}{6}` |
| `TRANSP_EXPENSE_NO_SEQ` | `TAE_NO` | `TAE-{YYYY}{6}` |
| `TRANSP_ORDER_SYS_ID_SEQ` | `TRO_SYS_ID` | `{Ymd}{8}` |
| `TRANSP_MASTER_SYS_ID_SEQ` | PK semua tabel master | idem |
| `TRANSP_TRX_SYS_ID_SEQ` | PK tabel transaksi lain | idem |

Lima sequence nomor transaksi `seq_type = 1` dan dipanggil dengan `dateFormat 'Y'`;
tiga sequence SysId `seq_type = 0` dan memakai default `'Ymd'`. Lebar 8 digit untuk
SysId adalah penyimpangan yang disengaja — lihat `DECISIONS.md` D-07.

**Nomor per tahun.** Sequence direset tiap pergantian tahun — sama seperti sistem lama.
Fase M-6: set nilai awal di atas nilai maksimum data ter-migrasi.

---

## 6. Rumus

### 6.1 Tarif yarn (§3.7.2)

```
untuk setiap rate_line pada rate_card, urut TRL_PRIORITY:
    jika TRL_RATE_TYPE = 'W':
        total_baris = TRL_RATE
    jika TRL_RATE_TYPE = 'Q':
        qty_baris = TRL_IS_OVERFLOW ? (qty_kg - TRC_TRUCK_CAP) : TRL_MAX_CAP
        lewati baris ini bila qty_baris <= 0        ← muatan tidak melebihi kapasitas
        total_baris = qty_baris * TRL_RATE

freight = Σ total_baris
```

Padanan SQL lama: `DECODE(b.mtr_max_cap, 1, (a.qty - b.mtm_truck_cap), b.mtr_max_cap)`
dengan `WHERE mtr_max_cap >= 1`.

### 6.2 Tarif chip (F-09.9)

```
freight = gross_weight_kg * TRL_RATE        // rate line prioritas 1, tipe Q
```

Tanpa prioritas, tanpa kelebihan muatan. **Jangan pakai `YarnRateCalculator`.**

### 6.3 Due date (§3.7.3)

```php
// Modules/Transporter/app/Support/DueDateHelper.php
public static function forTransactionDate(Carbon $date): Carbon
{
    $day = $date->day <= 15 ? 10 : 25;
    return $date->copy()->addMonthNoOverflow()->day($day);
}
```

Di Forms lama ini `DECODE` 15 cabang yang berulang di banyak query. Satu helper, dipakai
semua tempat. **Jangan** pakai `PeriodDateRangeHelper` — itu aturan cutoff payroll tanggal 26,
urusan lain.

### 6.4 Gross-up (§3.7.4)

```
jika carrier.TCA_IS_GROSS_UP = 1  (deprecated, default mati):
    total = ROUND((freight + other) * 1.02)
selain itu:
    total = freight + other
```

### 6.5 Kurs IDR → USD (§3.7.6)

```
faktor  = 1 / ExchangeRateService::getExchangeRate($jvDate->endOfMonth(), 'USD', 'IDR', 'B')
amt_usd = ROUND($amtIdr * $faktor, 2)
```

`ExchangeRateService` mengembalikan **IDR per 1 USD**; `curs_usd_b` sistem lama
mengembalikan kebalikannya (mis. `0.00005531585` = 1/18.078). **Harus dibalik.**
Diambil pada **akhir bulan** periode JV, bukan tanggal transaksi.

Di GL: `td_doc_amt` / `_2` / `_3` = nilai USD, `td_fc_amt` = nilai IDR asli.

RK-04 mewajibkan unit test yang membandingkan hasil kedua sumber untuk 24 bulan terakhir.

### 6.6 Pajak (§3.7.4, F-06.2)

Profil vendor dari `IM_VS_STATIC_VALUE` `WHERE VSSV_VS_CODE = 'TRANSPORTER'` (48 baris):

| Kolom | Isi |
|---|---|
| `VSSV_FIELD_01` | `PPN` / `PPN 1%` / null |
| `VSSV_FIELD_02` | `PPH 0.5` / `NON PPH` / null |
| `VSSV_FIELD_03` | `DDMMYYYY` — batas berlaku `PPH 0.5`, divalidasi `SYSDATE <= TO_DATE(…)` |

Aturan PPN masukan saat posting TJV:

| Nomor faktur pajak diawali | Akun |
|---|---|
| `05` | `108005` |
| `08` | **tidak dibuat baris PPN sama sekali** (faktur non-kreditable) |
| lainnya | `108004` |

### 6.7 Ambang berat chip (F-09.6, F-09.12)

```
tolak   bila gross_weight > config('transporter.chip.max_gross_weight_kg')   // default 60.000
tolak   bila Σ(gi_qty_bu)/1000 > gross_weight                                 // netto > gross
tolak   bila GRN tidak punya batch number (OT_GR_BATCH kosong)
tolak   bila rate card (carrier, chip_vendor, truck_type) tidak ada atau rate = 0
peringatkan (tidak menolak) bila gross_weight > TRC_TRUCK_CAP                  // F-09.13
```

**Pesan validasi wajib mengutip nilai yang berlaku dari config**, bukan angka literal.
T-21 lahir persis dari pesan yang tidak ikut diperbarui saat ambangnya naik empat kali.

---

## 7. Sumber data Orion (read-only)

| Model `MgtDat/` | Tabel | Dipakai untuk | Filter kunci |
|---|---|---|---|
| `OtInvoiceHead` | `OT_INVOICE_HEAD` | LDN / PDN / JWDN | `INVH_APPR_STATUS = 3` |
| `OtInvoiceItem` | `OT_INVOICE_ITEM` | qty PDN | `SUM(TO_NUMBER(INVI_FLEX_01))` |
| `OtWmsPackTableAlthara` | `OT_WMS_PACK_TABLE_ALTHARA` | qty LDN/JWDN | `SUM(PRD_GROSS_WT)` |
| `OtGrHead` | `OT_GR_HEAD` | GRN chip | `GH_TXN_CODE='CHPGRN' AND GH_REF_TXN_CODE='JPO' AND GH_APPR_STATUS=3` |
| `OtGrBatch` | `OT_GR_BATCH` | validasi batch number | — |
| `OmSupplier` | `OM_SUPPLIER` | nama & NPWP vendor | `SUPP_FLEX_06` = flag gross-up |
| `ImVsStaticValue` | `IM_VS_STATIC_VALUE` | lookup destinasi, jenis truk, profil pajak | `VSSV_VS_CODE` |
| `MenuUser` | `MENU_USER` | validasi Orion user id | `USER_FIELD_01` = lokasi |
| `FtOs` | `FT_OS` | outstanding AP | — |
| `FvOsMatch` | `FV_OS_MATCH` | matching outstanding | — |

**Flex field surat jalan:**

| Kolom | Isi |
|---|---|
| `INVH_FLEX_02` | nomor polisi |
| `INVH_FLEX_03` | sopir |
| `INVH_FLEX_06` | kode transporter (`MTM_NO` lama) |
| `INVH_FLEX_07` | tujuan |
| `GH_FLEX_03` / `_04` / `_05` / `_06` | nopol / sopir / nama transporter / gross weight |

> `INVH_FLEX_06` terisi **bukan** berarti ada biaya yang harus ditagih (F-03.10).
> Yang menentukan adalah `TCA_TYPE`. 1.029 `EDN` dan 730 `WDN` sepanjang 2025 semuanya
> terisi dan tidak satu pun punya transaksi angkutan — itu benar, bukan bug (T-28).

**Flex field yang ditulis saat posting GL** (§3.7.7):

| Kolom | Isi |
|---|---|
| `td_flex_15` | nomor transaksi angkutan |
| `td_flex_16` | tanggal, format `DDMMYYYY` |
| `td_flex_17` | nama transporter |
| `td_flex_18` | tujuan |
| `td_flex_19` | nomor polisi |
| `td_flex_20` | sopir |
| `th_flex_10` | nomor tagihan (`TRB_TRX_NO`) untuk TJV |

---

## 8. Permission (Spatie)

```
transporter-dashboard-view
transporter-master-carrier-manage
transporter-master-rate_card-manage
transporter-master-charge_type-manage
transporter-master-service_category-manage
transporter-master-posting_account-manage
transporter-master-document_type-manage
transporter-order-view
transporter-order-create
transporter-order-edit
transporter-order-delete
transporter-approve-transaction          ← maker ≠ approver
transporter-override-cost
transporter-pull-grn
transporter-expense-view
transporter-expense-create
transporter-approve-additional-expense   ← maker ≠ approver, tim yang sama
transporter-pull-additional-expense
transporter-provision-view
transporter-post-jv
transporter-bill-view
transporter-post-tjv
transporter-waive-document
transporter-report-view
transporter-post-payment                 ← rilis lanjutan, diseed tapi belum dipakai
```

Middleware route: `role:Super Admin|Finance|Transporter Admin|Transporter Approver|Transporter Viewer|Transporter - Despatch|Transporter - Stores`
plus `permission:` per aksi sensitif.

> **Dikoreksi 2026-09-22 (D-11).** Versi awal bagian ini menulis sembilan nama dengan
> titik (`transporter.post-jv` dan aksi sensitif lainnya). Titik itu tidak berarti apa-apa
> bagi Spatie dan tidak dipakai modul lain, jadi seluruh 25 nama diseragamkan ke `-`
> sebelum P1 membagikannya. Pagar role juga ditambah `Transporter - Despatch` dan
> `Transporter - Stores`: keduanya dibuat di T014 dan memegang permission halaman, jadi
> tanpa itu mereka terkunci dari halaman yang baru saja diberikan. Kedua nama role
> memakai awalan modul mengikuti pola repo (`Lc Control - Admin`).

---

## 9. Pemetaan migrasi (§6.2, §6.3)

Nama tabel tujuan di §6.2 PRD sudah usang. Yang berlaku:

| Tabel lama (`MGTDAT`) | Baris | Tabel baru (`MGTHRIS`) | Aturan |
|---|---:|---|---|
| `MGT_TRANSP_MASTER` | 739 | `transp_carrier` (± 50) + `transp_rate_card` (± 716) | C-19, C-20, C-21, C-22, C-09 |
| `MGT_TRANSP_RATE` | 799 | `transp_rate_line` | sentinel `max_cap = 1` → `TRL_IS_OVERFLOW = 1` |
| `MGT_TRANSP_HEAD` | 33.126 | `transp_order` | C-06, C-08, C-17, C-18; `TRO_IS_LEGACY = 1` |
| `MGT_TRANSP_DETAIL_DN` | 92.650 | `transp_order_dn` | C-01 (buang 126 yatim), C-14 |
| `MGT_TRANSP_DETAIL_COST` | 35.403 | `transp_order_cost` | C-02 (buang 37 yatim) |
| `MGT_TRANSP_DETAIL_OTHCHG` | 862 | `transp_additional_expense` + `_line` | C-03, C-11, C-23 |
| `MGT_TRANSP_DN_AUTO` | 27.009 | `transp_dn_stage` | hanya `MTDA_TRANSP_TXN IS NULL` |
| `MGT_TP_PROVISION` | 21.411 | `transp_provision` | C-04, C-05 |
| `MGT_TP_PROVISION_ADD` | 42 | `transp_bill_line` (`EXPENSE_DIRECT`) | **bukan** provisi — §5.6.1 melarang provisi susulan |
| `MGT_TP_PROVISION_DN` | 758 | `transp_provision_dn` | |
| `MGT_TP_PROVISION_BILL` | 3.809 | `transp_bill` + `transp_bill_line` (`PROVISION`) | |
| `MGT_TP_PROVISION_BILL_ADD` | 5 | `transp_bill_line` (`EXPENSE_DIRECT`) | |
| `MTDD_STS_PRS` + `MTDD_STS_DOC` | 66.100 | `transp_document_scan` | C-13, C-15, C-16 |
| `MGT_TP_PROVISION_DEL` | 10.479 | **tidak dimigrasi** | artefak, digantikan soft delete |
| `MGT_TP_PROVISION_TPCHP` | 43 | **tidak dimigrasi** | snapshot usang |
| 30+ tabel backup | ± 300.000 | **tidak dimigrasi** | |

> Perhatikan baris `MGT_TP_PROVISION_ADD`: §6.2 PRD menulisnya ke
> `transp_provision (kind = ADDON)`. Kolom `kind` tidak ada lagi. 42 baris add-on
> (terakhir Desember 2022) jadi baris tagihan `EXPENSE_DIRECT`. Dicatat di
> `DECISIONS.md` D-03.

Setiap baris yang dibuang atau diperbaiki **wajib** masuk `transp_migration_exception`
dengan `TME_RULE_CODE` yang sesuai. Tanpa itu rekonsiliasi R-01 tidak bisa dihitung.

---

## 10. Struktur test

```
tests/Unit/Transporter/
    YarnRateCalculatorTest.php        ← 500 transaksi historis, uji R-12 versi dini
    ChipRateCalculatorTest.php
    DueDateHelperTest.php             ← semua tanggal 1–31, dua cabang
    GrossUpHelperTest.php
    ExchangeRateParityTest.php        ← RK-04, 24 bulan terakhir
    ProvisionJournalBuilderTest.php   ← pemetaan Dr/Cr per service type
    BillJournalBuilderTest.php        ← termasuk cabang FP diawali 05 dan 08
tests/Feature/Transporter/
    OrderApprovalTest.php             ← maker ≠ approver, ketiga jalur
    ProvisionEligibilityTest.php      ← BUYER_BORNE & INTERNAL tidak pernah berprovisi
    OneParentOneProvisionTest.php     ← F-06.9
    DocumentGateTest.php              ← F-06.4, daftar LDN yang menahan
    RateCardOverlapTest.php           ← F-01.7
tests/Integration/Transporter/      ⚠ BELUM jadi testsuite — lihat gap.md C-6
    PostingIntegrationTest.php        ← skip sendiri bila variabel Oracle tidak diset
e2e/pages/transporter/               ← descriptor, satu per halaman
e2e/specs/transporter/               ← spec, satu per alur
e2e/docs/modules/transporter/        ← doc, prefix `transporter-`
```

NF-07 menyebut lima area sebagai yang paling berisiko: **kalkulasi tarif, due date,
pajak, gross-up, dan pemetaan jurnal.** Kelimanya punya unit test sebelum halamannya ada.
