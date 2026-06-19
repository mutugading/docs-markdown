# 1. Overview & Tujuan

## Latar Belakang

PT Mutu Gading Tekstil saat ini mengelola seluruh proses production planning secara manual menggunakan Microsoft Excel. File planning aktual (`PL_F26_R-000_R-0003_FIN.xls`) berisi 19 sheet yang saling terhubung via formula — setara dengan sebuah sistem informasi yang dibangun di Excel. Proses ini memiliki keterbatasan fundamental:

- **Tidak ada single source of truth** — data tersebar di multiple files, rentan inkonsistensi
- **Tidak ada visibility real-time** — PPC tidak bisa tahu aktual produksi sampai laporan manual masuk
- **Tidak ada enforcement rules** — over-production, RM fence, dan grade requirement tidak ter-enforce otomatis
- **Koordinasi manual** — WO, approval, dan notifikasi sepenuhnya via WhatsApp dan verbal
- **Carry-forward error-prone** — rekonsiliasi awal bulan memakan waktu dan rentan missing data

## Tujuan Sistem

Sistem PPC (Production Planning & Control) dirancang sebagai **pengganti lengkap** untuk proses Excel planning saat ini, mencakup tiga area produksi: Spinning (SPG), Texturising (TXT), dan Twisting (TWT).

**Tujuan utama:**

1. Satu sistem terintegrasi untuk seluruh siklus planning: demand → plan → WO → actual
2. Real-time visibility produksi via ETL dari bobbin tracking system (Oracle 11g)
3. Enforce RM fence dan over-production threshold otomatis
4. Digital carry-forward dan awal bulan workflow
5. Auto-kalkulasi Balance for Sale untuk manajemen komoditi

## Scope

### Dalam Scope

- Layer 1: Production Demand — pengganti `02_Sales_Plan`, `CF_EX`, `NEW_EX`, `LOCAL`
- Layer 2: Production Plan Item — pengganti `04_datewise`
- Layer 3: Work Order — pengganti MLR (`PRD_TXT_MLR_ENTRY`)
- Changeover planning — pengganti `03_COPlan`
- Carry-forward workflow awal bulan — pengganti `PREV_PLAN`
- Balance for Sale dashboard — pengganti `00_SUMMARY` dan `SUMMARY vs ORDER`
- ETL dari bobbin tracking Oracle 11g (ASPTXT, ASPSPG, ASPAK schemas)
- Integrasi dengan ERP Orion via `SALES_ORDER_STAGING`
- Integrasi dengan Costing Module (CPM_ product master dari Phase B)

### Luar Scope

- Production execution real-time — Phase 1: `WO_EXECUTION` ada tapi bukan real-time execution system
- Write-back ke ERP Orion — Phase 2
- Delivery / shipping management
- Inventory management (baca stok dari Orion, tidak write)
- Costing dan margin calculation (dikerjakan di Costing Workflow Suite terpisah)

## Area Produksi

| Area | Kode | Produk | Keterangan |
|---|---|---|---|
| Spinning | SPG | POY (Partially Oriented Yarn) | Intermediate captive — output masuk TXT sebagai RM |
| Texturising | TXT | DTY/PTY/ATY/ACY | Produk jadi utama, ada grading AX–JLT |
| Twisting | TWT | Twisted yarn | Produk jadi, sama grading dengan TXT |

## Definisi Istilah

| Istilah | Definisi |
|---|---|
| **Demand** | Komitmen bisnis — customer butuh X kg sebelum tanggal Z. Owner: PPC/Sales |
| **Plan Item** | Rencana produksi — kita akan produksi X kg di mesin Y. Owner: PPC |
| **Work Order (WO)** | Instruksi produksi konkret per mesin per lot. Owner: PPC |
| **Lot** | Identitas batch produksi, key: `item_code + shade_code` |
| **Doff** | Satu siklus penggulungan bobbin di mesin. Basis unit produksi SPG |
| **Bobbin** | Unit fisik benang per posisi mesin. 1 doff = N bobbin |
| **Carry-forward** | Demand yang belum terpenuhi di bulan lalu, dibawa ke bulan ini |
| **Balance for Sale** | Net stok AX yang available untuk dijual: stok + running + MTS − committed |
| **Common Lot** | Lot gabungan dari sisa bobbin lintas lot, di-register di ERP dengan identity baru |
| **Grade AM** | Bukan grade produksi — grouping warehouse dari A9+A untuk delivery |
| **MLR** | Machine Line Record — form existing yang digantikan oleh WO di sistem baru |
