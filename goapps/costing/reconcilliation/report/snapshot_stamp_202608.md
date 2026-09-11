# Calc Snapshot Stamp — Periode 202608

| Field | Value |
|---|---|
| Server `NOW()` (Postgres) | `2026-09-09 09:51:43.564881+07` |
| Oracle `SYSDATE` | `2026-09-09 09:51:44` |
| Oracle target | `ALTHARA` (production), Oracle 11.2.0.4 |
| Postgres target | `goapps` @ localhost:25432 (SSH tunnel), PostgreSQL 18.1 |
| Mode | READ-ONLY (`default_transaction_read_only=on`) |

## 1. `cst_product_cost` 202608 — baris aktif (`cpc_status <> 'SUPERSEDED'`)

| calculation_type | status | rows | products | jobs | calculated_at (min) | calculated_at (max) |
|---|---|---:|---:|---:|---|---|
| ACTUAL | APPROVED | 4189 | 4189 | 1 | 2026-09-09 08:54:09.2475+07 | 2026-09-09 08:54:09.2475+07 |
| ACTUAL | CALCULATED | 13422 | 13422 | 1 | 2026-09-09 09:01:29.150658+07 | 2026-09-09 09:04:39.301821+07 |
| FORECAST | APPROVED | 4189 | 4189 | 1 | 2026-09-09 08:54:09.2475+07 | 2026-09-09 08:54:09.2475+07 |
| FORECAST | CALCULATED | 13422 | 13422 | 1 | 2026-09-09 09:05:31.691279+07 | 2026-09-09 09:07:48.687664+07 |
| SELLING | APPROVED | 4189 | 4189 | 1 | 2026-09-09 08:54:09.2475+07 | 2026-09-09 08:54:09.2475+07 |
| SELLING | CALCULATED | 13422 | 13422 | 1 | 2026-09-09 09:10:59.612291+07 | 2026-09-09 09:13:10.407045+07 |

## 2. Job id yang menghasilkan angka 202608

| job_id | type | status | rows | calculated_at |
|---|---|---|---:|---|
| 102 | SELLING | APPROVED | 4189 | 2026-09-09 08:54:09.2475+07 |
| 102 | FORECAST | APPROVED | 4189 | 2026-09-09 08:54:09.2475+07 |
| 102 | ACTUAL | APPROVED | 4189 | 2026-09-09 08:54:09.2475+07 |
| 103 | ACTUAL | CALCULATED | 13422 | 2026-09-09 09:01:29.150658+07 |
| 104 | FORECAST | CALCULATED | 13422 | 2026-09-09 09:05:31.691279+07 |
| 105 | SELLING | CALCULATED | 13422 | 2026-09-09 09:10:59.612291+07 |

## 3. Baseline untuk verifikasi ulang di akhir pekerjaan

```
ACTIVE_ROWS_202608=52833 DISTINCT_COST_ID=52833 MAX_CALC_AT=2026-09-09 09:13:10.407045+07 INPUT_HASHES=52833
SUPERSEDED_ROWS_202608=25989
RM_COST_202608=350
MB_COST_202608=12567
```

> **PERINGATAN.** 202608 di goapps **di-recompute hari ini 2026-09-09 08:54–09:04 WIB**,
> yaitu ~45 menit sebelum snapshot ini diambil. Versi sebelumnya (17.611 baris ACTUAL)
> dijadikan SUPERSEDED dari run 2026-09-05. Klaim "periode closed, tidak bergerak"
> tidak berlaku untuk sisi goapps. Angka di laporan ini adalah run 2026-09-09.

---

## 4. VERIFIKASI ULANG DI AKHIR PEKERJAAN (wajib per aturan prompt)

Diambil **2026-09-09 16:28–16:29 WIB**, yaitu **6,6 jam** setelah baseline (09:51).

### 4.1 Sisi goapps — STABIL, nol recompute

| Metrik | Baseline (09:51) | Akhir (16:28) | Status |
|---|---:|---:|---|
| `ACTIVE_ROWS_202608` | 52.833 | **52.833** | ✅ sama |
| `DISTINCT_COST_ID` | 52.833 | **52.833** | ✅ sama |
| `INPUT_HASHES` | 52.833 | **52.833** | ✅ sama |
| `MAX_CALC_AT` | 2026-09-09 09:13:10.407045 | **sama** | ✅ tidak bergerak |
| `SUPERSEDED_ROWS_202608` | 25.989 | **25.989** | ✅ sama |
| `RM_COST_202608` | 350 | **350** | ✅ sama |
| `MB_COST_202608` | 12.567 | **12.567** | ✅ sama |
| Job id aktif | 102, 103, 104, 105 | **sama** | ✅ tidak ada job baru |
| Baris dihitung setelah 09:13 | — | **0** | ✅ |

**Tidak ada recompute di sisi goapps selama seluruh sesi recon.** Seluruh angka
di laporan ini berasal dari satu run yang konsisten (job 102–105,
2026-09-09 08:54–09:13).

### 4.2 Sisi legacy — BERGERAK, dan perlu dibaca per track

| Tabel | Fase 0 (09:46) | Fase 5 (≈14:30) | **Akhir (16:29)** | Drift |
|---|---:|---:|---:|---|
| `_CUR` **MARKETING** (→ SELLING) | 14.635 | 14.665 | **14.667** | **+32**, terakhir diubah **15:25:01**, 40 baris diubah hari ini |
| `_CUR` **VALUATION** (→ ACTUAL) | 7.505 | 7.505 | **7.505** | **0** — terakhir diubah 2026-09-02 |
| `CST_YARN_LEFT` | — | 22.195 | **22.197** | +2 |
| `CST_MST_BATCH_HEAD` | 4.325 | 4.325 | **4.328** | **+3** |
| `CST_MST_BATCH_ITEM` | 22.589 | 22.589 | **22.604** | **+15** |
| `CST_MST_BATCH_SPIN` | 2.791 | 2.791 | **2.799** | **+8** |
| `CST_GRP_CONSUMP_HEAD` @202608 | 924 | 924 | **924** | **0** |

### 4.3 Kesimpulan integritas snapshot

Premis prompt *"Periode 202608 sudah closed di kedua sisi. Legacy tidak bergerak
lagi"* **tidak akurat untuk legacy**, tapi dampaknya terbatas dan bisa dipetakan
dengan tepat:

| Track / scope | Stabil? | Konsekuensi untuk laporan |
|---|---|---|
| **ACTUAL** (legacy VALUATION) | ✅ **stabil** | angka ACTUAL bisa dipakai sebagai final |
| **RM cost 202608** | ✅ **stabil** | angka Fase 2 bisa dipakai sebagai final |
| **SELLING** (legacy MARKETING) | ❌ bergerak (+32, terakhir 15:25) | **provisional** — butuh tanggal cut-off |
| **MB recipe / spin master** | ❌ bergerak (+3 head, +15 item, +8 spin) | Fase 3 & 4 **provisional** |

Yang penting: **track ACTUAL — yang menjadi target toleransi <0,1% dan angka
utama laporan — sepenuhnya stabil di kedua sisi.** Yang bergerak adalah track
SELLING dan master MB/spin, dan ketiganya sudah dilaporkan terpisah, bukan
digabung ke angka utama.
