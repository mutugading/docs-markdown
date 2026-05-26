# 📌 Phase Addendum — Phase B: Product Order Management

> **Cara pakai:** Tambahkan ini ke awal chat ketika kerja fokus di Phase B.

---

## 🎯 PHASE B FOCUS

Sistem pengelolaan struktur BOM multi-level — dari raw material hingga finished goods. Single source of truth menggantikan file Excel 31.012 baris.

**Goals utama:**
1. Single source of truth BOM multi-level
2. Hilangkan Excel manual yang rawan duplikasi
3. Visualisasi tree + flow + drag-drop editor
4. Versioning lengkap dengan historical traceability
5. Costing orchestrator untuk calculation engine eksternal

**Critical constraint:** Sistem TIDAK menyimpan parameter cost atau cost result — itu di tabel eksternal. Sistem hanya orchestrator.

---

## 🗓 PHASE B SUB-PHASES (sesuai PRD Section 12)

| Sub-Phase | Scope | Notes |
|-----------|-------|-------|
| **B.1 — Foundation (MVP)** | Schema, CRUD API, master data integration, import Excel + converter, cycle detection | Required first |
| **B.2 — Visualization & Reporting** | BOM Tree (FR-8), Flow View read-only (FR-9), Materialized view, BOM Explosion (FR-10), Where-Used (FR-11), Export | Parallel with B.1 last sprint |
| **B.3 — Visual BOM Editor** | Flow Editor drag-drop (FR-17) dengan React Flow, Auto-layout (FR-18), Layout persistence | Setelah B.2 stable |
| **B.4 — Versioning & Audit** | Version history (FR-12), Compare versions (FR-13), Audit log UI | Bisa paralel dengan B.3 |
| **B.5 — Costing Orchestration** | Topological sort API (FR-19), Dependency resolution (FR-20), Batch orchestrator (FR-21), On-demand (FR-22), Status dashboard (FR-23) | **Prasyarat:** tabel eksternal siap |
| **B.6 — Enhancements (Optional)** | Minimap Flow Editor, Approval workflow, Public API | Backlog |

---

## 🔑 PHASE B KEY DECISIONS (BAKED IN PRD)

| Keputusan | Pilihan | Rasional |
|-----------|---------|----------|
| Storage model | Normalized single-level BOM + Materialized View untuk exploded | Update simple, query explosion cepat |
| Cycle handling | Detect + warn + allow override (dengan flag) | User experience > strict enforcement |
| Versioning | Setiap commit BOM change = version baru, partial unique index untuk 1 active | Audit-friendly |
| Polymorphic FK | Dual column `rm_product_sys_id` + `rm_master_item_id` dengan CHECK | Bisa pakai FK constraint database |
| Visual editor lib | React Flow (@xyflow/react) + dagre untuk auto-layout | MIT, mature, production-proven |
| Master data | Read-only FK, tidak duplicate | Single source of truth |
| Approval workflow | Out of scope (audit trail cukup) | Bisa ditambahkan future |
| Quantity / consumption | Out of scope | Bukan domain BOM structure |
| Cost calculation formula | Out of scope (external engine) | Sistem ini orchestrator saja |

---

## ⚠️ PHASE B GOTCHAS

### Materialized View — Stale Risk
- `product_order_exploded` di-refresh otomatis saat version commit ke active
- Tapi trigger bisa gagal → view jadi stale
- **Mitigation:**
  1. Expose endpoint manual refresh
  2. Scheduled refresh harian sebagai safety net
  3. Monitor `last_refreshed_at` di UI

### Cycle Detection Performance
- Recursive query pada graph besar bisa lambat
- Set safe max depth (default: 20)
- Real-time check saat drag connection di Flow Editor → harus FAST
- Final check saat commit version → bisa lebih thorough

### Polymorphic FK — Dual Column Approach
```sql
-- ✅ CORRECT
rm_product_sys_id BIGINT REFERENCES product_order(product_sys_id),
rm_master_item_id BIGINT REFERENCES master_item(item_id),
CHECK (
  (rm_product_sys_id IS NOT NULL AND rm_master_item_id IS NULL)
  OR
  (rm_product_sys_id IS NULL AND rm_master_item_id IS NOT NULL)
)

-- ❌ WRONG (no FK constraint possible)
rm_ref_id BIGINT NOT NULL,
rm_ref_type VARCHAR(10) NOT NULL  -- 'PRODUCT' or 'MASTER'
```

Tapi UNTUK API/JSON output, return sebagai `{rm_ref_type, rm_ref_id}` (flat polymorphic) untuk simplify client.

### Multi Yarn Edge Case
- 276 baris di data sumber: `RM_TYPE = Multi Yarn` dengan `RM_ITEM_CODE NULL`
- Hanya `RM_TOP_2` (deskripsi text) yang tersedia
- **Solusi:** kolom `rm_description` sebagai fallback bila item belum di master
- **Best practice:** lengkapi master item dulu, baru `rm_ref_id` selalu terisi

### Cycle Override Bukan Anomali — Tapi Tracked
- Bila user override cycle warning, set `cycle_override = true` di version
- Audit log catat user dan reason
- Cost calculation tetap bisa jalan tapi mungkin punya behavior khusus (depend on external engine)

### Performance Targets — JANGAN COMPROMISE
- BOM explosion (depth ≤ 10): < 2 detik
- Where-used: < 2 detik
- Search/list (paginated): < 1 detik untuk 50K records
- Concurrent refresh MV: tidak boleh block read

---

## 📊 DATA MODEL — PHASE B QUICK REFERENCE

**Entitas inti:**
- `product_order` — main entity, 1 record = 1 FG variant
- `product_order_version` — snapshot struktur BOM (draft/active/superseded)
- `product_order_component` — daftar komponen langsung per version (normalized)
- `product_order_exploded` — **materialized view** hasil flatten rekursif
- `product_order_version_layout` — posisi visual (x,y) untuk Flow Editor
- `audit_log` — semua write operations

**Field penting yang sering bingung:**
- `product_sys_id` (BIGSERIAL) — pengganti `FG_LEFT_NO` di Excel lama, generated
- `product_code` (VARCHAR UNIQUE) — pengganti `FG_TOP_2`, mis. `TCM0000001-6378-01-MEEREBAH`
- `current_version_id` — pointer ke active version di product_order
- `rm_type` values: `Store Rate`, `Captive Cost`, `Multi Yarn`, `Uneven Packing`
- `rm_ref_type`: `PRODUCT` (FK ke product_order) atau `MASTER` (FK ke master_item)

**Constraint penting:**
- UNIQUE (product_sys_id, version_no)
- Partial unique index: hanya 1 version dengan status=active per product_sys_id
- CHECK constraint untuk polymorphic FK (lihat di atas)

**Indeks wajib:**
- UNIQUE INDEX (product_order.product_code)
- INDEX (product_order_version.product_sys_id, status)
- INDEX (product_order_component.version_id, sequence_no)
- INDEX (product_order_component.rm_ref_type, rm_ref_id) — for where-used
- INDEX (product_order_exploded.product_sys_id, level)
- UNIQUE INDEX (layout.version_id, node_ref_type, node_ref_id)

---

## 🎨 FLOW EDITOR (FR-17) — IMPLEMENTATION GUIDE

### Library Stack
- **React Flow (@xyflow/react)** — canvas, pan/zoom, custom node/edge
- **dagre** — auto-layout hierarchical (simpler)
- **elkjs** — alternative, lebih powerful untuk graph besar

### UI Layout
```
┌─────────────────────────────────────────────────────────┐
│ Toolbar: [Undo] [Redo] [Auto-layout] [Validate] [Save]  │
├──────────┬──────────────────────────────────┬───────────┤
│          │                                  │           │
│  PALETTE │           CANVAS                 │ INSPECTOR │
│          │                                  │           │
│  [RM]    │   ●──→●        ●──→●            │  Item:    │
│  [Capt.] │       ↓                          │  Type:    │
│  [Multi] │       ●──→●                      │  Cyl:     │
│          │                                  │  Shade:   │
│          │                                  │           │
└──────────┴──────────────────────────────────┴───────────┘
```

### Interaction Patterns
- **Drag from palette** → new node at drop position
- **Drag from output port to input port** → create edge (component relationship)
- **Click node** → select, inspector shows attributes
- **Delete key** → remove node (with confirm if has connections)
- **Validate button** → cycle detection + master data ref check

### Mapping Canvas → Schema
- 1 node = 1 product_order (existing or new)
- 1 edge = 1 row di product_order_component (parent = node tujuan, rm_ref = node sumber)
- Position (x, y) → product_order_version_layout
- Convergent flow (many edges into 1 node) → auto-map sebagai komponen multiple dengan sub_sequence

### Save Draft Transaction
Saat user save draft, write ke 3 tabel dalam 1 transaction:
1. `product_order` (jika ada perubahan attribute)
2. `product_order_component` (delete old + insert new untuk version draft)
3. `product_order_version_layout` (positions)

### UX Considerations
- Graph bisa SANGAT besar (depth 13, ratusan node)
- Flow Editor sebaiknya fokus pada subset (mis. "2 level up + 2 level down dari selected node")
- Untuk full exploration, pakai BOM Tree (vertical) atau Flow View read-only

---

## 💰 COSTING ORCHESTRATION (FR-19 to FR-24)

**Penting:** Phase B = orchestrator. Calculation engine = EXTERNAL.

### Apa yang Phase B lakukan
1. Topological sort: tentukan urutan node yang harus di-cost (level terdalam dulu)
2. Dependency resolution: untuk tiap node, return daftar komponen + klasifikasi (MASTER/PRODUCT)
3. Trigger calculation engine eksternal dengan data ini
4. Write hasil ke tabel cost result eksternal
5. Track status: pending, success, failed (with reason)

### Apa yang Phase B TIDAK lakukan
- Mengambil/cache cost master price
- Menghitung formula cost (weighted, yield factor, dll)
- Menyimpan parameter cost
- Menyimpan cost result definition

### External Dependencies Contract (FR-24)
3 tabel eksternal yang Phase B butuh:
| Tabel | Operasi Phase B | Konteks |
|-------|----------------|---------|
| Master RM price | READ only | Lookup cost komponen MASTER |
| Parameter cost | READ only | Input ke calculation engine |
| Cost result | INSERT/UPDATE | Output kalkulasi (atas nama engine) |

**Aturan:** Jika tabel-tabel ini belum siap, FR-19 sampai FR-23 di-defer.

### Batch vs On-Demand
- **Batch (FR-21):** Per period, hitung semua product aktif. Skip yang parameter-nya missing, lanjut yang lain. Report failed di akhir.
- **On-Demand (FR-22):** Pilih product + period. Sistem preview: berapa node akan dihitung baru, berapa reuse. Overwrite cost result yang sama period (audit log catat).

---

## 📥 EXCEL IMPORT (FR-14)

### Format Baru (Simplified)
1 baris di Excel = 1 komponen langsung (down from ~31K to ~13-15K rows estimasi)

Kolom: `FG_TOP_2`, `FG_ITEM_CODE`, `FG_CYL_TYPE_CODE`, `FG_SHADE_CODE`, `SEQUENCE_NO`, `SUB_SEQUENCE`, `SUB_TYPE`, `RM_TYPE`, `RM_REF_TYPE`, `RM_REF_CODE`, `RM_DESCRIPTION`

### Converter Lama → Baru
Logic:
1. Untuk tiap `FG_TOP_2` di file lama, identifikasi baris dengan `RM_SEQUENCE` max → komponen langsung
2. Baris `RM_SEQUENCE` < max = hasil flatten intermediate → skip (akan muncul sebagai entri sendiri)
3. Multi Yarn: row dengan `RM_SUB_SEQUENCE` not null → pertahankan
4. Ignore: `RM_LEFT_NO_INTO`, `RM_SUB_SEQUENCE_REAL` (redundant)

### Validation Pre-Commit
- `FG_TOP_2` konsisten attribute-nya di seluruh baris
- `(FG_TOP_2, SEQUENCE_NO, SUB_SEQUENCE)` unique
- `RM_REF_TYPE = PRODUCT` → `RM_REF_CODE` ada di file atau di DB
- `RM_REF_TYPE = MASTER` → `RM_REF_CODE` ada di master_item
- Cycle detection lintas-file sebelum commit

### Dry-Run Mode
Sebelum commit final, tampilkan preview:
- Total rows
- Valid rows
- Error rows (with reason)
- Cycle warnings (with paths)
- Master data refs yang tidak ditemukan

---

## 🔗 INTEGRATION WITH PHASE A

Phase A punya **routing_draft** sebagai shadow entity. Saat Phase B live:

### Migration Job
Untuk setiap `routing_draft` dengan status `DRAFT` atau `LOCKED`:
1. Generate record `product_order` di Phase B
2. Generate `product_order_version` (status=draft initially)
3. Generate `product_order_component` dari `routing_draft_component`
4. Resolve `rm_ref_text` → resolve ke `rm_product_sys_id` atau `rm_master_item_id`
5. Bila ada free-text yang tidak bisa di-resolve, flag untuk manual review
6. Update `routing_draft.linked_product_order_id` = new product_sys_id
7. Update `routing_draft.status` = `PROMOTED`

### After Phase B Live
- Phase A tetap punya routing_draft (untuk draft baru saat Engineering masih design)
- "Promote to Product Order" button di Phase A UI = trigger migration job untuk 1 draft
- Promotion eksplisit oleh user, BUKAN auto

---

## 🎯 SUCCESS METRICS PHASE B

- 100% data Excel sumber ter-import tanpa loss (kecuali yang valid error)
- Storage reduction ≥50% vs Excel lama
- BOM explosion + where-used yang dulu manual via Excel → < 5 detik di sistem
- Process Engineering laporkan waktu update BOM turun ≥70%
- Zero kasus inkonsistensi antar produk yang share intermediate

---

## 💬 COMMON QUESTIONS PER ROLE

**IT Leader:**
- "Bisa skip Phase B.5 dulu?" → Bisa, kalau tabel eksternal belum siap. B.1-B.4 cukup untuk BOM management.
- "Risiko terbesar?" → Master data tidak lengkap saat import awal. Mitigasi: dry-run + manual review.

**Backend Dev:**
- "Bagaimana implement BOM explosion?" → Recursive CTE atau materialized view (preferred for performance)
- "Trigger MV refresh?" → On version status change ke active, plus scheduled fallback

**Frontend Dev:**
- "React Flow custom node?" → Yes, custom node per type (FG/Intermediate/RM) dengan visual berbeda
- "Performance dengan ratusan node?" → Limit visible subset, lazy expand, virtualization

**QA:**
- "Cycle detection test cases?" → Direct cycle (A→A), 2-level (A→B→A), deep (A→B→C→D→A), false positive (parallel paths)
- "Import test data?" → Sample Excel dengan 100, 1000, 10000 rows; edge cases dari PRD Section 5

---

*Phase B Addendum v1.0 — Append to Master Prompt saat fokus Phase B*
