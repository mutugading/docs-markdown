# 9. Master Data & Config

## Product Master

Sistem PPC tidak maintain product master sendiri. Reference ke **Costing Module**
(`cost_product_master`, prefix `CPM_`):

```
CPM_product_sys_id    -- PK, dipakai di ppc_cpm_product_sys_id
CPM_item_code*        -- item code Orion (wajib NOT NULL untuk masuk planning)
CPM_grade_code*       -- grade code Orion (wajib NOT NULL)
CPM_shade_code*       -- shade code Orion (wajib NOT NULL)
```

### PRODUCT_PPC_CONFIG (extension PPC)

```sql
PRODUCT_PPC_CONFIG
  ppc_id                   BIGSERIAL PK
  ppc_cpm_product_sys_id   BIGINT        NOT NULL UNIQUE
  ppc_is_commodity_watch   BOOLEAN       DEFAULT FALSE
  ppc_price_sell           DECIMAL(12,2)
  ppc_machine_group_id     BIGINT
  ppc_yield_std            DECIMAL(5,3)
  ppc_buffer_rm_pct        DECIMAL(5,3)
  ppc_ax_yield_pct         DECIMAL(5,3)  -- historical %AX (0.75–0.84)
  ppc_updated_at           TIMESTAMPTZ   DEFAULT NOW()
```

### PRODUCT_MACHINE_CAPACITY

Dari data sheet `01_PROD` Excel planning aktual:

```sql
PRODUCT_MACHINE_CAPACITY
  pmc_id                   BIGSERIAL PK
  pmc_cpm_product_sys_id   BIGINT        NOT NULL
  pmc_machine_id           BIGINT        NOT NULL
  pmc_speed                DECIMAL(8,2)
  pmc_no_of_positions      INT
  pmc_prod_per_day         DECIMAL(10,3)
  pmc_efficiency_pct       DECIMAL(5,2)
  pmc_draw_ratio           DECIMAL(6,3)
  pmc_updated_at           TIMESTAMPTZ   DEFAULT NOW()
  UNIQUE (pmc_cpm_product_sys_id, pmc_machine_id)
```

---

## Machine Master

```sql
MACHINE_MASTER
  machine_id               BIGSERIAL PK
  machine_no               VARCHAR(10)   NOT NULL UNIQUE  -- dari TXTMACH.MACH_NO
  machine_area             CHAR(3)       NOT NULL         -- TXT / SPG / TWT
  machine_line             VARCHAR(20)
  machine_group_id         BIGINT
  machine_doff_weight_kg   DECIMAL(8,3)  -- TXT-1:1200, TXT-2:600
  machine_is_active        BOOLEAN       DEFAULT TRUE
  machine_orion_code       VARCHAR(30)

MACHINE_GROUP
  group_id                 BIGSERIAL PK
  group_name               VARCHAR(50)   NOT NULL
  group_area               CHAR(3)       NOT NULL
```

**MACH_DEPT konfirmasi:** `MACH_DEPT='TXT'` = mesin TXT, `MACH_DEPT='TWT'` = mesin TWT.

---

## Lot Master

Lot dibuat PPC saat create WO:

```sql
LOT_MASTER
  lm_lot_no                VARCHAR(30)   PK
  lm_item_code             VARCHAR(30)   NOT NULL
  lm_shade_code            VARCHAR(20)   NOT NULL
  lm_std_weight_full       DECIMAL(8,4)  NOT NULL  -- kg, untuk kalkulasi TRN_STS=0
  lm_std_weight_unfull     DECIMAL(8,4)  NOT NULL  -- kg, untuk kalkulasi TRN_STS=1
  lm_notes                 TEXT
  lm_created_by_ppc        BIGINT        NOT NULL
  lm_created_at            TIMESTAMPTZ   DEFAULT NOW()
  lm_updated_at            TIMESTAMPTZ   DEFAULT NOW()
```

**Lot key:** `item_code + shade_code`. Grade code tidak masuk key lot.
**Ratio validasi:** Full: 78–80%, Unfull: 20–22% dari total bobbin.

> ⚠️ **TRN_STS TXT/TWT:** 0=Full, 1=Unfull (kebalikan dari DOFF_OPTION SPG: 1=Full, 2=Unfull)

---

## Overrun Threshold Config

```sql
OVERRUN_THRESHOLD_CONFIG
  otc_id                   BIGSERIAL PK
  otc_level                VARCHAR(20)   NOT NULL  -- SYSTEM/MACHINE_GROUP/PRODUCT_TYPE/PRODUCT/WO
  otc_ref_id               BIGINT
  otc_threshold_unit       CHAR(4)       NOT NULL  -- PCT / DOFF
  otc_warning_value        DECIMAL(10,3) NOT NULL
  otc_block_value          DECIMAL(10,3) NOT NULL
  otc_notes                TEXT
  otc_is_active            BOOLEAN       DEFAULT TRUE
  otc_updated_at           TIMESTAMPTZ   DEFAULT NOW()
```

**Default:** Warning 3%, Block 6% (PCT). TXT = DOFF unit (1 doff = 1,200 kg TXT-1 / 600 kg TXT-2).

---

## Shift Config

| Shift | Jam |
|---|---|
| Shift 1 | 06.00 – 14.00 |
| Shift 2 | 14.00 – 22.00 |
| Shift 3 | 22.00 – 06.00 (+1) |
