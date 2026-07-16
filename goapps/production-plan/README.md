# PRD — PPC Production Planning System

> **Status:** Draft v1.1 · Juli 2026
> **Owner:** Indra (IT Lead, PT Mutu Gading Tekstil)
> **Stack:** Go + PostgreSQL · Service baru di `goapps-backend`
> **Development:** 3 phase · ETA ~9 bulan

---

## Daftar Isi

| # | Halaman | Keterangan |
|---|---|---|
| 1 | [Overview & Tujuan](01-overview.md) | Latar belakang, scope, area produksi, definisi istilah |
| 2 | [User & Role](02-user-role.md) | 6 role, approval matrix, auto-approve, notifikasi |
| 3 | [Layer 1 — Production Demand](03-layer1-demand.md) | Demand management, carry-forward, LOV Orion, MTS |
| 4 | [Layer 2 — Production Plan Item](04-layer2-plan-item.md) | Living document, Gantt view, RM fence, cascade |
| 5 | [Layer 3 — Work Order](05-layer3-work-order.md) | WO lifecycle, dual approval, TQM, suggest qty |
| 6 | [Changeover](06-changeover.md) | Component-based C1–C7, auto-detect, inline Gantt |
| 7 | [Packing & Grading](07-packing-grading.md) | Grade hierarchy, packing data sync, common lot |
| 8 | [Integrasi & ETL](08-integrasi-etl.md) | Oracle summary tables → ETL Go → PostgreSQL |
| 9 | [Master Data & Config](09-master-data.md) | Product PPC Config, Machine Master, Lot Master |
| 10 | [Balance for Sale & Dashboard](10-balance-for-sale.md) | BFS formula, commodity watch, morning review |
| 11 | [Phase Plan & Roadmap](11-phase-plan.md) | 3 phase, timeline, dependensi |
| 12 | [Schema Lengkap](12-schema.md) | PostgreSQL DDL semua tabel — siap untuk developer |
| 13 | [Daily Performance](13-daily-performance.md) | Efficiency, waste, downtime/idle, shift entry operator, export Excel |

---

## Referensi Dokumen Terkait

| Dokumen | Lokasi | Keterangan |
|---|---|---|
| Design Decisions | ClickUp Doc `2kzmeddw-2138` | 193 keputusan desain yang sudah dikunci |
| ETL Spec | ClickUp Doc `2kzmeddw-2758` | Query Oracle & summary tables spec |
| Phase Plan | ClickUp Doc `2kzmeddw-2778` | Detail phase development |
| Oracle DDL | `PPC_ORACLE_DDL.sql` | DDL 3 summary tables di MGTDAT |
| Oracle Procedures | `PPC_ORACLE_PROCEDURES.sql` | 3 refresh procedures |

---

## Open Items

| # | Item | Phase | Status |
|---|---|---|---|
| 1 | BOM Phase B schema → `WO_RM_ALLOCATION` Phase 2 | Phase 2 | ETA 2–3 hari |
| 2 | Validasi definisi break metrics TXT (original vs inspection) dgn tim produksi | Phase 1 | Open |
| 3 | Sumber resmi data Overtime (HR) — sementara input manual `AREA_SHIFT_LOG` | Phase 2 | Open |
| 4 | Denier produk untuk theoretical calc — konfirmasi field di CPM_ master | Phase 1 | Open |

---

## Changelog

| Versi | Tanggal | Perubahan |
|---|---|---|
| v1.1 | Juli 2026 | **Halaman 13 baru**: Daily Performance (efficiency, waste, downtime/idle, shift entry operator) — hasil analisa daily report Excel TXT/TWT/SPG. Revisi: hal 2 (scope Operator), hal 5 (dual qty SPG doffed vs transferred, `wo_prod_category`), hal 8 (catatan pemakaian GROSS vs TRANSFERRED), hal 10 (Daily Performance Dashboard + export Excel), hal 11 (scope phase), hal 12 (7 tabel baru + kolom baru) |
| v1.0 | Juni 2026 | Draft awal dari sesi brainstorming |

> **Konvensi versioning:** file di-replace langsung di repo (git = version control);
> versi dokumen dicatat di sini, bukan lewat nama file.

---

*Dokumen ini di-generate dari sesi brainstorming di Claude AI — Juni 2026*
