# 3. Layer 1 — Production Demand

## Konsep

Production Demand adalah **komitmen bisnis** — customer butuh X kg produk Y sebelum tanggal Z.
Ini bukan rencana produksi. Layer ini menggantikan sheet `02_Sales_Plan`, `CF_EX`, `NEW_EX`, dan `LOCAL`.

PPC memiliki otoritas penuh atas semua demand. ETL Orion hanya mengisi `SALES_ORDER_STAGING` sebagai
inbox — PPC yang memilih SO mana yang perlu diproduksi.

---

## Tipe Demand

| Type | Sub-type | Deskripsi | Approver |
|---|---|---|---|
| CONTRACT | CF_EXPORT | Carry-forward export dari bulan sebelumnya | PPC |
| CONTRACT | NEW_EXPORT | New export booking bulan ini | PPC |
| CONTRACT | LOCAL | Order lokal | PPC |
| MTS | INTERNAL | Make to Stock — produksi tanpa order spesifik | Marketing |
| SAMPLE | — | Sample/development order | PPC |

---

## Demand Status Lifecycle

```
PENDING_CONFIRMATION
    ↓ PPC confirm
CONFIRMED
    ↓ WO dibuat
IN_PRODUCTION
    ↓ sebagian terpenuhi
PARTIAL
    ↓
FULFILLED          ← selesai semua
CANCELLED          ← dibatalkan
CARRIED_OVER       ← dibawa ke bulan baru via carry-forward
DEFERRED           ← push ke bulan depan atas permintaan customer
SPLIT              ← dipecah jadi N demand baru
```

---

## Grade Requirement per Demand

| Nilai | Arti |
|---|---|
| `AX_ONLY` | Customer hanya menerima grade AX |
| `AX_AM_CLAUSE` | Customer menerima AX dan sebagian AM (ada `pd_ax_min_pct`, `pd_am_max_pct`) |
| `NONE` | Tidak ada grade requirement spesifik |

**Kalkulasi produksi yang dibutuhkan:**
```
est_prod_needed = pd_qty_remaining / ppc_ax_yield_pct
ppc_ax_yield_pct = historical %AX per produk (0.75–0.84)
```

---

## Entry Demand — "Pull or Create"

### A. Pull from Orion (LOV Pattern)
1. ETL background → mengisi `SALES_ORDER_STAGING` dari `MGT_SO_PENDING_WEB`
2. PPC klik "+ Add Demand" → "Pull from Orion"
3. List SO staging yang belum di-pull (filter by customer/product/date)
4. Review detail: qty, deadline, grade req — bisa edit sebelum confirm
5. Confirm → `PRODUCTION_DEMAND` dengan `pd_source = ORION_PULL`
6. `sos_pulled_to_demand_id` diisi → tidak muncul lagi di LOV

**Unmatched SO Alert:** notifikasi jika ada SO di staging belum di-pull, sorted by deadline.

### B. Input Manual
1. "+ Add Demand" → "Input manual"
2. Form: product (CPM_), type, sub-type, qty, deadline, grade req
3. MTS: wajib isi justifikasi → notifikasi ke Marketing untuk approval
4. `pd_source = MANUAL`

---

## Carry-Forward Awal Bulan

### Prinsip
Carry-forward adalah keputusan bisnis PPC, bukan operasi otomatis. Stok ditampilkan sebagai
referensi saja — sistem tidak auto-kurangi qty carry dengan stok.

### Start New Month Workflow
1. Sistem generate candidates: PARTIAL / IN_PRODUCTION / CONFIRMED / DEFERRED
2. PPC review list + stok AX available (referensi) + deadline original
3. PPC tentukan aksi per demand:

| Aksi | Deskripsi |
|---|---|
| CARRY_AS_IS | Buat demand baru qty = remaining, deadline baru |
| SPLIT | Pecah jadi N demand baru. SUM(qty baru) ≤ remaining |
| DEFER | Push ke bulan depan. Muncul kembali di carry-forward berikutnya |
| PARTIAL_CARRY | Carry sebagian, sisanya cancel |
| CANCEL | Demand ditutup |

4. Validasi SPLIT: `SUM(qty baru) ≤ pd_qty_remaining`
5. Demand baru: `pd_source = CARRY_FORWARD`, `pd_carry_from_id` FK ke demand asal

---

## UI — Demand Page

**3 Tab:**

| Tab | Konten | Menggantikan |
|---|---|---|
| All Demands | Per produk: carry-forward qty, new booking, AX target, est. prod needed, machines needed | `02_Sales_Plan`, `SUMMARY vs ORDER` |
| By Order | Per contract/order: contract no, incoterm, qty breakdown, fulfilled progress | `CF_EX`, `NEW_EX`, `LOCAL` |
| MTS Requests | Approval Marketing: product, qty, justifikasi, status | — |

---

## Schema

```sql
PRODUCTION_DEMAND
  pd_id                    BIGSERIAL PK
  pd_type                  VARCHAR(20)   -- CONTRACT / MTS / SAMPLE
  pd_sub_type              VARCHAR(20)   -- CF_EXPORT / NEW_EXPORT / LOCAL / INTERNAL
  pd_source                VARCHAR(20)   -- ORION_PULL / MANUAL / MTS_APPROVED / CARRY_FORWARD
  pd_carry_action          VARCHAR(20)   -- CARRY_AS_IS / SPLIT / DEFER / PARTIAL_CARRY
  pd_cpm_product_sys_id    BIGINT        NOT NULL
  pd_qty_original          DECIMAL(18,3) NOT NULL
  pd_qty_remaining         DECIMAL(18,3) NOT NULL
  pd_deadline              DATE          NOT NULL
  pd_customer_id           BIGINT
  pd_contract_no           VARCHAR(50)
  pd_contract_date         DATE
  pd_stuff_advance_no      VARCHAR(50)
  pd_incoterm              VARCHAR(10)
  pd_lc_status             VARCHAR(30)
  pd_grade_requirement     VARCHAR(20)   -- AX_ONLY / AX_AM_CLAUSE / NONE
  pd_ax_min_pct            DECIMAL(5,2)
  pd_am_max_pct            DECIMAL(5,2)
  pd_carry_from_id         BIGINT        -- FK ke pd_id
  pd_sos_ref               BIGINT        -- FK ke SALES_ORDER_STAGING
  pd_status                VARCHAR(30)   NOT NULL
  pd_month                 CHAR(7)       NOT NULL  -- YYYY-MM
  pd_confirmed_by          BIGINT
  pd_confirmed_at          TIMESTAMPTZ
  pd_created_by            BIGINT        NOT NULL
  pd_created_at            TIMESTAMPTZ   DEFAULT NOW()
  pd_updated_at            TIMESTAMPTZ   DEFAULT NOW()

SALES_ORDER_STAGING
  sos_id                   BIGINT PK
  sos_contract_no          VARCHAR
  sos_contract_date        DATE
  sos_contract_sys_id      BIGINT        -- FK ke Orion SOH
  sos_customer_code        VARCHAR
  sos_customer_name        VARCHAR
  sos_item_code            VARCHAR
  sos_item_desc            VARCHAR
  sos_grade_code           VARCHAR
  sos_shade_code           VARCHAR
  sos_shade_name           VARCHAR
  sos_qty_ordered          DECIMAL(18,3)
  sos_qty_delivered        DECIMAL(18,3)
  sos_qty_remaining        DECIMAL(18,3)
  sos_deadline             DATE
  sos_ship_date            VARCHAR
  sos_merge_no             VARCHAR       -- dari FNC_MERGE_SO_MGT
  sos_term                 VARCHAR       -- CBD / non-CBD
  sos_rate                 DECIMAL       -- harga per kg
  sos_currency             VARCHAR
  sos_blocked_status       VARCHAR       -- Overdues / Delivery Blocked
  sos_outstanding_ar       DECIMAL
  sos_pallet_type          VARCHAR
  sos_end_use              VARCHAR
  sos_mix_flag             VARCHAR
  sos_annotation           VARCHAR
  sos_remarks              VARCHAR
  sos_etl_synced_at        TIMESTAMPTZ
  sos_pulled_to_demand_id  BIGINT        -- NULL=available, FK=sudah di-pull
```
