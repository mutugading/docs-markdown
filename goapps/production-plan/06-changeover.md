# 6. Changeover

## Konsep

Changeover adalah periode transisi antara dua WO berbeda di mesin yang sama.
Menggantikan sheet `03_COPlan` Excel. Bersifat **component-based** — durasi dan waste
dihitung dari komponen yang aktif berdasarkan perbedaan produk.

---

## Komponen Changeover (C1–C7)

| Kode | Komponen | Trigger | Durasi Default | Waste Default |
|---|---|---|---|---|
| BASE | Base time | Selalu ada | 30 menit | 8 kg |
| C1 | Denier change | Beda denier | +60 menit | +20 kg |
| C2 | Color family change | Beda color family | +90 menit | +30 kg |
| C3 | Shade direction | Gelap → terang | +60 menit | +25 kg |
| C4 | Filament count change | Beda filament | +45 menit | +15 kg |
| C5 | Twist direction change | S-twist ↔ Z-twist | +30 menit | +10 kg |
| C6 | Lot change (same product) | Produk sama, lot beda | +15 menit | +5 kg |
| C7 | Deep clean | Manual flag | +120 menit | +40 kg |

Sistem auto-detect komponen aktif. PPC bisa override (tambah/hapus komponen).

---

## Changeover Group

| Group | Durasi |
|---|---|
| MINOR | < 60 menit |
| MEDIUM | 60–120 menit |
| MAJOR | 120–240 menit |
| DEEP | > 240 menit |

---

## Context "Previously Running"

Saat PPC membuat plan baru, sistem selalu tampilkan:
- Produk yang **sedang running** di mesin tersebut
- Lot yang sedang jalan
- Estimasi kapan WO tersebut selesai

---

## Inline dengan Gantt

Changeover tidak dikelola di sheet terpisah. Muncul otomatis di Gantt sebagai bar merah
dashed di antara dua WO berbeda.

---

## Schema

```sql
CHANGEOVER_EVENT
  ce_id                    BIGSERIAL PK
  ce_from_wo_id            BIGINT        NOT NULL
  ce_to_wo_id              BIGINT        NOT NULL
  ce_machine_id            BIGINT        NOT NULL
  ce_duration_estimated    INT           -- menit
  ce_waste_estimated       DECIMAL(10,3) -- kg
  ce_group                 VARCHAR(10)   -- MINOR/MEDIUM/MAJOR/DEEP
  ce_duration_actual       INT
  ce_waste_actual          DECIMAL(10,3)
  ce_status                VARCHAR(20)   -- PLANNED/IN_PROGRESS/DONE
  ce_started_at            TIMESTAMPTZ
  ce_completed_at          TIMESTAMPTZ
  ce_notes                 TEXT

CHANGEOVER_COMPONENT
  cc_id                    BIGSERIAL PK
  cc_event_id              BIGINT        NOT NULL
  cc_component_code        CHAR(5)       NOT NULL  -- BASE/C1–C7
  cc_duration_applied      INT           NOT NULL
  cc_waste_applied         DECIMAL(10,3) NOT NULL
  cc_is_auto_detected      BOOLEAN       DEFAULT TRUE
  cc_override_by           BIGINT
  cc_override_at           TIMESTAMPTZ
```
