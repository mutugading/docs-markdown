# Phase C — Costing Calculation Engine: Brainstorm & Design Decision

> **Dokumen ini:** Ringkasan diskusi arsitektur untuk migrasi `pkg_yarn_calculation` (Oracle PL/SQL) ke Go service di `goapps-backend`. Dibuat sebagai bahan diskusi dengan developer sebelum eksekusi.
>
> **Audience:** Developer `goapps-backend` (Finance service)
> **Tanggal:** Juni 2026
> **Status:** 🟡 Menunggu keputusan desain

---

## Konteks: Apa yang akan dipindah?

Di sistem Oracle lama ada dua package utama yang menangani kalkulasi costing benang:

| Package Oracle | Fungsi |
|---|---|
| `MGTAPPS.pkg_yarn_calculation` | Engine kalkulasi utama — procedure `process()` yang loop semua formula per produk |
| `MGTAPPS.pkg_yarn_valuation` | Helper lookup — ambil MB name, chip rate, dozing, packing type, dll per produk |

Yang akan dimigrasi ke Go adalah **seluruh logic kalkulasi** ini, berjalan di `goapps-backend` (Finance service).

---

## Mapping: Oracle → Sistem Baru

| Konsep Oracle | Tabel/Layer Baru | Keterangan |
|---|---|---|
| `CST_YARN_LEFT` (product row) | `cost_product_master` | Master produk yang dihitung |
| `CST_YARN_TOP` (param definition) | `mst_parameter` | Definisi parameter |
| `CST_YARN_CALCULATION` (cell value) | `cost_product_parameter` | Nilai per param per produk |
| `CYC_FORMULA_TYPE` | `mst_parameter.formula_type` ← **kolom baru** | Tipe kalkulasi per param |
| `CYC_PROCESS_SEQ` | `mst_parameter.calc_seq` ← **kolom baru** | Urutan eksekusi |
| `CYC_DATA_VALUE` | `cpp_value_numeric` / `cpp_value_text` | Hasil kalkulasi |
| `CST_YARN_FILTER` (scope) | Request context (productSysIDs) | Filter produk yang dikalkulasi |
| `vIdMkt` / `vIdVal` (package var) | `pricing_type` enum: `MARKETING` / `VALUATION` | Mode harga |
| `vPeriodCostingGrpItem` | `period time.Time` parameter | Periode untuk rate lookup |

---

## Cara Kerja Oracle `process()` — Yang Perlu Ditiru

```
procedure process(pUserId, pPRS_TYPE, pCYFH_IS_EDIT):

  1. pRefrshValParam()          -- sync Yarn Rate / Multi-Yarn chain SEBELUM loop
  2. pCkYarnWithoutLoss()       -- null-kan loss formula untuk produk exempt
  3. pValuationProcess()        -- copy Marketing → Valuation records (jika REFRESH=Y)

  4. FOR EACH row IN CST_YARN_CALCULATION
        WHERE product IN (CST_YARN_FILTER)
        ORDER BY CYC_PROCESS_SEQ, CYC_LEFT_NO, CYC_TOP_NO:

       evaluate CYC_FORMULA_TYPE → hasilkan vDataValue
       UPDATE CYC_DATA_VALUE = vDataValue   ← update in-place per row
       COMMIT per row

  5. pkgYarnLeftTot.process()   -- agregat totals per LEFT record
```

**Kunci:** eksekusi **ordered by `PROCESS_SEQ`** — ini yang membuat formula bisa referensi hasil formula sebelumnya. Mirip topological sort.

---

## Formula Types yang Ada (12 jenis)

| Formula Type | Kompleksitas | Keterangan |
|---|---|---|
| `INITIAL_VALUE` | Rendah | Literal dari `CYC_FORMULA_SCRIPT` |
| `FROM_DATA` | Rendah | Copy value dari param lain (via `CST_YARN_SAME_ROWS`) |
| `FORMULA_DATA` | Sedang | Aritmatika: `op1 operator op2 operator op3` (via `CST_YARN_FORMULA_CALC`) |
| `IF_CONDITION` | Tinggi | Conditional branch (via `CST_YARN_IFCOND_HDR/DTL`) |
| `RAW_MATERIAL` | Tinggi | 3 sub-tipe: Store Rate / Captive Cost / Multi-Yarn |
| `FROM_MASTER_YARN` | Sedang | Lookup kolom dari `CST_MST_YARN` |
| `FROM_MASTER_MACHINE` | Sedang | Lookup kolom dari `CST_MST_MACHINE` |
| `FROM_BOX_BOBIN_COST` | Sedang | Lookup dari `CST_MST_BOX_BOBIN_COST` |
| `FROM_PRODUCT_GRADE` | Sedang | Lookup dari `CST_MST_PRODUCT_GRADE` |
| `FROM_MASTER_BATCH_DATA` | Tinggi | Lookup dari MB spinning master |
| `INTERMENGLING DATA` | Rendah | Lookup `CST_YARN_MST_INTERMINGLING` / 100 |
| `FROM_MARKETING_COST` | Sedang | Copy dari session Marketing ke Valuation |

---

## Gap: Apa yang Belum Ada

File `formula_engine.go` yang sudah dibuat sebelumnya **bukan** equivalent Oracle package. Itu adalah satu pure function besar `Calculate()` yang hardcode semua kalkulasi sekaligus. Yang belum ada:

```
❌ FormulaType enum + handler map (dispatcher)
❌ Execution loop ordered by calc_seq — iterasi per-param seperti Oracle process()
❌ mst_parameter tidak punya: formula_type, calc_seq (kolom baru dibutuhkan)
❌ Per-param evaluation: EvaluateParam(param_code, product) yang bisa dipanggil satuan
❌ Pre-process hooks: RefreshValParam(), CheckWithoutLoss()
❌ Post-process hook: aggregasi totals
```

---

## Keputusan Desain Utama: Option A vs Option B

### Option A — Formula Hardcode di Go

Setiap `param_code` punya dedicated handler function di Go. `mst_parameter` hanya menyimpan `formula_type` dan `calc_seq` untuk routing, bukan logic formula.

```go
// Engine dispatch berdasarkan formula_type yang dibaca dari DB
handlers := map[FormulaType]HandlerFunc{
    FormulaTypeArithmetic:    handleArithmetic,    // grade weights, packing cost, dll
    FormulaTypeRawMaterial:   handleRawMaterial,   // store/captive/multi-yarn
    FormulaTypeIntermingling: handleIntermingling, // lookup master
    FormulaTypeMasterLookup:  handleMasterLookup,  // yarn/machine/packing master
    FormulaTypeIfCondition:   handleIfCondition,   // conditional
    FormulaTypeFromMarketing: handleFromMarketing, // copy dari Marketing session
}

// Eksekusi loop — data-driven ordering
params, _ := repo.GetCalculatedParams(ctx, productID) // ordered by calc_seq
for _, param := range params {
    handler := handlers[param.FormulaType]
    result  := handler(ctx, param, valuesAccumulator)
    valuesAccumulator[param.ParamCode] = result
}
```

**Plus:**
- Type-safe — compiler tangkap error formula
- Mudah unit-test — pure function, no DB
- Performa maksimal, zero overhead eval
- Complex logic (captive chain, IF_CONDITION) natural di Go
- Cocok dengan golangci-lint + testify yang sudah ada di repo

**Minus:**
- Tambah parameter baru = tulis Go + redeploy
- Finance/Engineering tidak bisa self-service ubah formula

---

### Option B — Data-Driven: Formula sebagai String di DB

Tambah kolom `formula_script` ke `mst_parameter`. Go evaluasi string expression saat runtime menggunakan library seperti [`expr-lang/expr`](https://github.com/expr-lang/expr).

```sql
-- mst_parameter tambah kolom:
ALTER TABLE mst_parameter
  ADD COLUMN formula_type   VARCHAR(30),  -- 'ARITHMETIC', 'RM_RATE', 'HARDCODED', ...
  ADD COLUMN formula_script TEXT,          -- "AX_WT * AE_PERC / AX_PERC"
  ADD COLUMN calc_seq       INTEGER;       -- urutan eksekusi

-- Contoh data:
-- AE_WT    | ARITHMETIC | "AX_WT * AE_PERC / AX_PERC"    | 20
-- RM_NORMS | ARITHMETIC | "1.0 / (1.0 - WASTE_PCT / 100)" | 10
-- RM_RATE  | HARDCODED  | "handleRawMaterial"             | 50  ← Go handler
-- MB_COST  | ARITHMETIC | "MB_RATE * MB_DOZING_PCT / 100" | 30
```

**Plus:**
- Formula arithmetic sederhana bisa diubah tanpa redeploy
- Formula visible di DB — bisa di-audit Finance/Engineering
- Tambah param baru: cukup INSERT ke `mst_parameter`
- Lebih dekat spirit Oracle (formula tersimpan di data)

**Minus:**
- Butuh expression evaluator library (dependency tambahan)
- Formula complex tetap hardcode di Go → hybrid anyway
- Runtime error — salah ketik formula di DB baru ketahuan saat run, bukan saat compile
- Lebih sulit di-test — harus seed DB dulu sebelum test
- Security risk jika ada bug di evaluator

---

## Perbandingan Ringkas

| Aspek | Option A | Option B |
|---|---|---|
| Ubah formula | PR + redeploy | Edit DB (jika arithmetic) |
| Siapa yang bisa ubah | Developer | Developer (+ Finance jika ada UI admin) |
| Testability | Mudah — pure function | Lebih sulit — butuh DB seed |
| Type safety | Compile-time | Runtime only |
| Kompleksitas implementasi | Rendah | Sedang–Tinggi |
| Formula complex (captive, IF) | Di Go handler | Tetap di Go handler |
| Dependency tambahan | Tidak | Ya (evaluator library) |

---

## Pertanyaan Kunci untuk Diskusi

> **Siapa yang akan mengubah formula costing, dan seberapa sering?**

- Kalau jawabannya **hanya developer via PR** → pilih **Option A**. Option B tidak memberikan nilai tambah nyata karena kamu tetap butuh migration SQL setiap ubah `formula_script`, efektifnya sama dengan ubah Go code tapi tanpa compile-time safety.

- Kalau jawabannya **Finance/Engineering bisa self-service** → Option B worth it, tapi perlu scope tambahan: UI admin untuk edit formula, validasi syntax sebelum save, audit log perubahan formula.

---

## Rekomendasi Sementara

**Mulai dengan Option A, tapi buat struktur yang bisa evolve ke Option B.**

Konkretnya:
1. Tambah kolom `formula_type` dan `calc_seq` ke `mst_parameter` — **tanpa** `formula_script` dulu
2. Engine loop data-driven berdasarkan `calc_seq` dari DB
3. Dispatch ke Go handler berdasarkan `formula_type`
4. Logic kalkulasi tetap di Go — type-safe, testable
5. Kalau nanti butuh self-service formula, tambah `formula_script` bertahap **hanya untuk** `ARITHMETIC` type yang sederhana

Dengan ini: execution order fleksibel dari DB, tapi kalkulasi tetap aman di Go.

---

## Perubahan Schema yang Dibutuhkan (Minimal)

```sql
-- Tambah ke mst_parameter (migration baru)
ALTER TABLE mst_parameter
  ADD COLUMN formula_type  VARCHAR(30) DEFAULT 'HARDCODED',
  ADD COLUMN calc_seq      INTEGER     NOT NULL DEFAULT 0;

-- formula_type values:
-- 'ARITHMETIC'      — simple math, nanti bisa evolve ke formula_script
-- 'RM_RATE'         — raw material rate (store/captive/multi-yarn)
-- 'MASTER_LOOKUP'   — lookup dari master table (yarn, machine, packing)
-- 'INTERMINGLING'   — lookup intermingling master / 100
-- 'IF_CONDITION'    — conditional logic
-- 'FROM_MARKETING'  — copy dari Marketing ke Valuation
-- 'INITIAL_VALUE'   — literal / input dari user
-- 'HARDCODED'       — special case, Go handler spesifik

-- Index untuk performa execution loop
CREATE INDEX idx_mst_parameter_calc_seq ON mst_parameter(calc_seq, formula_type)
  WHERE param_category = 'CALCULATED';
```

---

## Struktur File yang Perlu Dibuat di `goapps-backend`

```
internal/finance/costing/
├── domain.go           -- types: FormulaType, ParamValues, ProductCostInput/Result
├── engine.go           -- CostingEngine struct, Run(ctx, productID, period) loop
├── dispatcher.go       -- handlers map[FormulaType]HandlerFunc
├── handlers/
│   ├── arithmetic.go   -- handleArithmetic (grade weights, packing, utilities)
│   ├── raw_material.go -- handleRawMaterial (store rate / captive / multi-yarn)
│   ├── master.go       -- handleMasterLookup (yarn, machine, packing master)
│   ├── intermingling.go-- handleIntermingling
│   └── conditional.go  -- handleIfCondition
├── runner.go           -- RunProduct(), RunBatch() dengan topological order
├── pre_process.go      -- RefreshValParam(), CheckWithoutLoss()
├── engine_test.go      -- unit tests per handler
└── service.go          -- CostingService (wires repo + engine)
```

---

## Execution Order (Topological) untuk RunBatch

```
Seq 1: POY, FDY, SDY          — tidak ada upstream dependency
Seq 2: PTY, DTY, ATY          — depends on POY (captive RM)
Seq 3: TTY, TCM, TCY          — depends on PTY
Seq 4: MEL                    — depends on PTY (+ MB rate)
```

Mirrors `pValuationProcess()` di Oracle yang juga process POY dulu sebelum PTY.

---

## Files yang Sudah Ada (Jangan Dihapus)

| File | Status | Catatan |
|---|---|---|
| `formula_engine.go` | Perlu refactor | Jadikan `handlers/arithmetic.go` — logic kalkulasinya sudah benar, tinggal dipecah per handler |
| `formula_engine_service.go` | Perlu refactor | Jadikan `service.go` + `runner.go` |
| `formula_engine_test.go` | Keep | Test cases sudah valid, tinggal adjust import path |
| `01_seed_mst_parameter.sql` | Keep + extend | Tambah `formula_type` dan `calc_seq` ke INSERT statement |
| `costing_phase_c_master_data.xlsx` | Keep | Template input data, sudah lengkap 120 params |

---

## Next Steps Setelah Diskusi

- [ ] Sepakati Option A atau B (atau hybrid)
- [ ] Buat migration: tambah `formula_type` + `calc_seq` ke `mst_parameter`
- [ ] Update seed SQL dengan nilai `formula_type` dan `calc_seq` per param
- [ ] Buat `engine.go` dengan execution loop
- [ ] Buat `dispatcher.go` + folder `handlers/`
- [ ] Refactor `formula_engine.go` → pecah ke per-handler
- [ ] Integrasi dengan `goapps-backend` Finance service (gRPC endpoint + DB repo)

---

*Dibuat dari sesi diskusi Claude AI — Juni 2026*
*Repo referensi: `mutugading/docs-markdown`*
