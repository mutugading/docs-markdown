# 4. Layer 2 — Production Plan Item

## Konsep

Production Plan Item adalah **rencana produksi** — kita akan produksi X kg produk Y di mesin Z.
Living document bulanan yang bisa diubah kapan saja. Menggantikan `04_datewise` Excel.

- **Demand** = komitmen ke customer (tidak bisa diubah sembarangan)
- **Plan Item** = rencana internal PPC (fleksibel, bisa di-rearrange)

---

## Tipe Plan Item

| Type | Deskripsi | Contoh |
|---|---|---|
| `FG_DELIVERY` | Produk jadi untuk fulfill demand customer | PTY 150/48 DSD NI untuk kontrak export |
| `INTERMEDIATE` | Produk intermediate (SPG → TXT) — cascade dari plan item induk | POY 250/48 RND sebagai RM untuk PTY |
| `MTS` | Make to Stock — tidak ada demand spesifik | ATY 900/288 untuk stok komoditi |

---

## Relasi ke Demand

```
Demand-driven:
  ppi_demand_id NOT NULL, ppi_parent_item_id NULL

Cascade-driven (intermediate/captive):
  ppi_demand_id NULL, ppi_parent_item_id NOT NULL
  → Plan PTY 150/48 → cascade create plan POY 250/48 (SPG)
```

**Constraint:** salah satu dari `ppi_demand_id` atau `ppi_parent_item_id` harus diisi.

Produk sama tapi demand berbeda = plan item terpisah. WO boleh di-merge lintas plan item
jika produk + mesin + timing berdekatan (via `WO_PLAN_ITEM_LINK`).

---

## RM Source

| Nilai | Deskripsi |
|---|---|
| `STORE` | RM dari inventory gudang |
| `CAPTIVE` | RM dari output WO upstream running paralel (SPG → TXT live feeding) |
| `MIXED` | Kombinasi STORE dan CAPTIVE |

**Hard RM fence per plan item.** Warning di 85%, block jika melebihi limit + 1 doff (TXT).

---

## Deadline Intermediate

Dihitung mundur dari deadline FG:
```
Plan PTY 150/48 deadline = 30 Juni
Lead time POY → PTY = 3 hari
→ Plan POY 250/48 deadline = 27 Juni
```

---

## UI — Gantt View

**Sumbu Y:** daftar mesin (grouped by TXT / SPG / TWT)
**Sumbu X:** tanggal (per hari, satu bulan)

- Bar warna: Contract running (teal) / Planned (biru) / MTS (amber) / Changeover (merah dashed)
- Drag-resize bar untuk ubah tanggal/durasi
- Klik bar → side panel detail + WO terkait
- Context "currently running" selalu tampil saat buat plan baru di mesin

---

## Plan Item Lifecycle

```
DRAFT → CONFIRMED → IN_PROGRESS → COMPLETED → CLOSED
```

---

## Schema

```sql
PRODUCTION_PLAN_ITEM
  ppi_id                    BIGSERIAL PK
  ppi_cpm_product_sys_id    BIGINT        NOT NULL
  ppi_type                  VARCHAR(20)   NOT NULL  -- FG_DELIVERY / INTERMEDIATE / MTS
  ppi_demand_id             BIGINT        -- FK ke PRODUCTION_DEMAND (nullable)
  ppi_parent_item_id        BIGINT        -- FK ke ppi_id (nullable)
  -- CHECK: ppi_demand_id IS NOT NULL OR ppi_parent_item_id IS NOT NULL
  ppi_qty_target            DECIMAL(18,3) NOT NULL
  ppi_deadline              DATE          NOT NULL
  ppi_rm_source             VARCHAR(10)   -- STORE / CAPTIVE / MIXED
  ppi_sequence              INT           NOT NULL DEFAULT 0
  ppi_status                VARCHAR(20)   NOT NULL
  ppi_machine_group_id      BIGINT        NOT NULL
  ppi_preferred_machine_id  BIGINT
  ppi_month                 CHAR(7)       NOT NULL  -- YYYY-MM
  ppi_notes                 TEXT
  ppi_created_by            BIGINT        NOT NULL
  ppi_created_at            TIMESTAMPTZ   DEFAULT NOW()
  ppi_updated_at            TIMESTAMPTZ   DEFAULT NOW()

PRODUCTION_PLAN_LOG
  ppl_id                    BIGSERIAL PK
  ppl_plan_item_id          BIGINT        NOT NULL
  ppl_field_changed         VARCHAR(50)   NOT NULL
  ppl_value_before          TEXT
  ppl_value_after           TEXT
  ppl_changed_by            BIGINT        NOT NULL
  ppl_changed_at            TIMESTAMPTZ   DEFAULT NOW()
  ppl_reason                TEXT
```
