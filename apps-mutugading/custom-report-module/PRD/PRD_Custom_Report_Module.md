# PRD & Technical Spec — Custom Report Module
## PT Mutu Gading Tekstil | Laravel + Oracle 11g

---

## 1. Overview & Arsitektur

### Latar Belakang

Tingginya permintaan report baru dari berbagai departemen menyebabkan bottleneck di tim developer. Module ini hadir sebagai **Query-Driven Report Engine**: satu module yang mengabstraksi pembuatan report menjadi konfigurasi query + parameter, tanpa perlu membuat halaman baru per report.

### Fitur Utama

| Fitur | Deskripsi |
|---|---|
| Report Group | Kategorisasi report: Finance, HR, Attendance, Procurement, dll |
| Report Definition | Define nama, query SQL, parameter input, dan akses user |
| Dynamic Parameter Form | Form input di-generate otomatis dari definisi parameter |
| Excel Download | Output tabular ke file .xlsx dengan formatting professional |
| Email Alert | Jadwal kirim hasil query ke email (body table dan/atau attachment) |
| Email Styled | Mode tampilan email: tabular (flat) atau styled (grouped + subtotal) |
| Akses Berbasis Permission | Integrasi dengan spatie/laravel-permission |

### Konsep Inti: Unified Report–Alert Model

Report dan Alert adalah **entitas yang sama** (`RPT_DEFINITIONS`). Yang membedakan hanya sumber parameter dan trigger eksekusi:

| Aspek | Report Manual | Alert Scheduled |
|---|---|---|
| Parameter source | User input saat runtime | Fixed values di `RPT_ALERT_PARAM_VALUES` |
| Trigger | User klik "Generate" | Laravel Scheduler → Queue Job |
| Output | Excel download | Email (body / attachment / keduanya) |

### Alur Eksekusi

**Report Manual:**
```
User → Pilih Report → Form Parameter (auto-generated) → Submit
→ ReportQueryService (DML guard → bind params → ROWNUM wrap → Oracle read-only)
→ Dataset → ReportExcelService → Excel Download
```

**Alert Scheduled:**
```
Laravel Scheduler (setiap menit) → Cek RPT_ALERT_CONFIGS.next_run_at <= NOW
→ Dispatch AlertExecutionJob → Load fixed params dari RPT_ALERT_PARAM_VALUES
→ ReportQueryService → Dataset
→ ReportExcelService (jika has_attachment) → Excel file
→ EmailRenderer (TabularRenderer / StyledRenderer) → HTML body
→ Laravel Mail → Kirim ke RPT_ALERT_RECIPIENTS → Update next_run_at → Log
```

### Stack Teknologi

| Layer | Teknologi |
|---|---|
| Backend | Laravel (PHP) |
| Database | Oracle 11g via yajra/laravel-oci8 |
| Excel | maatwebsite/laravel-excel (PhpSpreadsheet) |
| Access Control | spatie/laravel-permission |
| Scheduler | Laravel Queue + Cron |
| Email | Laravel Mail (Blade template) |

### Batasan & Keamanan

- Semua query dieksekusi via **dedicated read-only Oracle user** (`rpt_readonly`)
- Query divalidasi sebelum disimpan: **DML forbidden** (INSERT, UPDATE, DELETE, DROP, dll)
- Hasil query dibatasi dengan `ROWNUM <= max_rows` di level Oracle
- Akses report dikontrol per-permission Spatie: `report.view.{report_id}`
- Admin UI (input SQL) hanya bisa diakses oleh developer (role terpisah)

---

## 2. Database Schema

### Column Prefix Convention

| Tabel | Prefix |
|---|---|
| `RPT_GROUPS` | `RG_` |
| `RPT_DEFINITIONS` | `RD_` |
| `RPT_PARAMETERS` | `RP_` |
| `RPT_ALERT_CONFIGS` | `RAC_` |
| `RPT_ALERT_PARAM_VALUES` | `RAPV_` |
| `RPT_ALERT_RECIPIENTS` | `RAR_` |
| `RPT_ALERT_LOGS` | `RAL_` |

### Relasi Antar Tabel

```
RPT_GROUPS (1) ──────────── (N) RPT_DEFINITIONS
RPT_DEFINITIONS (1) ──────── (N) RPT_PARAMETERS
RPT_DEFINITIONS (1) ──────── (N) RPT_ALERT_CONFIGS
RPT_ALERT_CONFIGS (1) ─────── (N) RPT_ALERT_PARAM_VALUES
RPT_ALERT_CONFIGS (1) ─────── (N) RPT_ALERT_RECIPIENTS
RPT_ALERT_CONFIGS (1) ─────── (N) RPT_ALERT_LOGS
```

Akses user dikontrol via **spatie/laravel-permission** — tidak ada tabel `RPT_USER_ACCESS` terpisah. Saat report dibuat, otomatis di-register permission: `report.view.{RD_ID}`.

### RPT_GROUPS

| Kolom | Tipe | Keterangan |
|---|---|---|
| `RG_ID` | NUMBER(10) PK | Auto-increment via sequence |
| `RG_NAME` | VARCHAR2(100) | Nama group |
| `RG_ICON` | VARCHAR2(50) | Tabler icon name |
| `RG_DISPLAY_ORDER` | NUMBER(5) | Urutan tampil |
| `RG_IS_ACTIVE` | NUMBER(1) | 0 = non-aktif, 1 = aktif |

### RPT_DEFINITIONS

| Kolom | Tipe | Keterangan |
|---|---|---|
| `RD_ID` | NUMBER(10) PK | |
| `RD_GROUP_ID` | NUMBER(10) FK | → RPT_GROUPS.RG_ID |
| `RD_NAME` | VARCHAR2(200) | Nama report |
| `RD_DESCRIPTION` | CLOB | Deskripsi opsional |
| `RD_QUERY_TEXT` | CLOB | Raw SQL. Hanya SELECT. Gunakan `:param_key` untuk binding. |
| `RD_MAX_ROWS` | NUMBER(10) | Default 1000. Di-wrap ROWNUM di eksekusi. |
| `RD_TIMEOUT_SEC` | NUMBER(5) | Default 30 detik |
| `RD_STATUS` | VARCHAR2(20) | `draft` / `active` / `inactive` |
| `RD_CREATED_BY` | NUMBER(10) | FK ke users |

### RPT_PARAMETERS

| Kolom | Tipe | Keterangan |
|---|---|---|
| `RP_ID` | NUMBER(10) PK | |
| `RP_REPORT_ID` | NUMBER(10) FK | → RPT_DEFINITIONS.RD_ID (CASCADE DELETE) |
| `RP_PARAM_KEY` | VARCHAR2(50) | Harus match `:param_key` di query |
| `RP_LABEL` | VARCHAR2(100) | Label yang tampil di form |
| `RP_INPUT_TYPE` | VARCHAR2(30) | text / number / date / date_range / dropdown_static / dropdown_query / multi_select_static / multi_select_query |
| `RP_IS_REQUIRED` | NUMBER(1) | 0/1 |
| `RP_DEFAULT_VALUE` | VARCHAR2(500) | Nilai default opsional |
| `RP_SOURCE_QUERY` | CLOB | Untuk `dropdown_query` / `multi_select_query` |
| `RP_STATIC_OPTIONS` | CLOB | JSON array untuk `dropdown_static` |
| `RP_DISPLAY_ORDER` | NUMBER(5) | Urutan tampil di form |

### RPT_ALERT_CONFIGS

| Kolom | Tipe | Keterangan |
|---|---|---|
| `RAC_ID` | NUMBER(10) PK | |
| `RAC_REPORT_ID` | NUMBER(10) FK | → RPT_DEFINITIONS.RD_ID |
| `RAC_NAME` | VARCHAR2(200) | Nama alert |
| `RAC_CRON_EXPR` | VARCHAR2(50) | Standard 5-part cron. E.g. `0 7 * * 1-5` |
| `RAC_IS_ACTIVE` | NUMBER(1) | 0/1 |
| `RAC_BODY_STYLE` | VARCHAR2(20) | `none` / `tabular` / `styled` |
| `RAC_HAS_ATTACHMENT` | NUMBER(1) | 1 = attach Excel ke email |
| `RAC_BODY_CONFIG` | CLOB | JSON konfigurasi warna dan grouping |
| `RAC_EMAIL_SUBJECT` | VARCHAR2(500) | Subject email |
| `RAC_EMAIL_INTRO` | CLOB | Teks pembuka di atas tabel |
| `RAC_NEXT_RUN_AT` | TIMESTAMP | Dihitung ulang setelah setiap run |
| `RAC_LAST_RUN_AT` | TIMESTAMP | Timestamp run terakhir |

### Notes Oracle 11g

- Tidak ada native JSON type di Oracle 11g — semua JSON disimpan sebagai **CLOB**
- Eloquent model menggunakan custom cast `App\Casts\ClobJson` (bukan `'array'` standar)
- Auto-increment menggunakan **SEQUENCE + TRIGGER**

---

## 3. Parameter System

### Input Types

| `input_type` | UI Component | Oracle Binding |
|---|---|---|
| `text` | Text input | `:param_key` = string |
| `number` | Number input | `:param_key` = numeric string |
| `date` | Date picker | `:param_key` = `TO_DATE('2026-01-15','YYYY-MM-DD')` |
| `date_range` | 2 date pickers | `:param_key_start` dan `:param_key_end` |
| `dropdown_static` | Select dari JSON | `:param_key` = satu nilai |
| `dropdown_query` | Select dari query | `:param_key` = satu nilai |
| `multi_select_static` | Multi-select dari JSON | Expanded ke `IN (:key_0, :key_1, ...)` |
| `multi_select_query` | Multi-select dari query | Expanded ke `IN (:key_0, :key_1, ...)` |

### Contoh Query Per Tipe

**date_range:**
```sql
WHERE WORK_DATE BETWEEN TO_DATE(:work_date_start, 'YYYY-MM-DD')
                    AND TO_DATE(:work_date_end,   'YYYY-MM-DD')
```

**multi_select:**
```sql
WHERE DEPT_ID IN (:dept_id)
-- ReportQueryService otomatis expand ke IN (:dept_id_0, :dept_id_1, ...)
```

---

## 4. Email Alert & body_config

### Dua Mode Tampilan Email Body

| `body_style` | Deskripsi |
|---|---|
| `none` | Tidak ada tabel di body. Hanya attachment. |
| `tabular` | Tabel flat sederhana. Alternating row colors. |
| `styled` | Tabel grouped dengan subtotal per group dan grand total. |

### body_config JSON — styled

```json
{
  "header_bg": "#1a5276",
  "header_color": "#ffffff",
  "row_odd_bg": "#ffffff",
  "row_even_bg": "#ebf5fb",
  "group_by_col": "DEPARTMENT_NAME",
  "group_header_bg": "#2980b9",
  "group_header_color": "#ffffff",
  "subtotal_cols": ["OVERTIME_HOURS", "OVERTIME_AMOUNT"],
  "subtotal_bg": "#d6eaf8",
  "subtotal_label": "Subtotal",
  "show_grand_total": true,
  "grand_total_bg": "#1a5276",
  "grand_total_color": "#ffffff",
  "grand_total_label": "Grand Total"
}
```

> ⚠️ Mode `styled` — query **HARUS mengandung `ORDER BY group_by_col`**

---

## 5. Laravel Implementation

### Struktur File

```
app/
├── Casts/
│   └── ClobJson.php
├── Exports/
│   └── ReportExport.php
├── Models/
│   ├── RptGroup.php
│   ├── RptDefinition.php
│   ├── RptParameter.php
│   ├── RptAlertConfig.php
│   ├── RptAlertParamValue.php
│   ├── RptAlertRecipient.php
│   └── RptAlertLog.php
└── Services/
    ├── ReportQueryService.php
    └── ReportExcelService.php
```

### Config Database — Read-Only Oracle Connection

```php
'oracle_readonly' => [
    'driver'   => 'oracle',
    'host'     => env('DB_ORACLE_HOST', env('DB_HOST')),
    'port'     => env('DB_ORACLE_PORT', '1521'),
    'database' => env('DB_ORACLE_DATABASE', env('DB_DATABASE')),
    'username' => env('RPT_DB_READONLY_USER', 'rpt_readonly'),
    'password' => env('RPT_DB_READONLY_PASSWORD'),
    'charset'  => 'AL32UTF8',
],
```

### Excel Output Format

| Area | Style |
|---|---|
| Font | Arial |
| Header | Size 11, bold, putih, background #2C3E50 |
| Data rows | Size 10, alternating #FFFFFF / #F2F3F4 |
| Freeze pane | Row 1 (header) |
| Print | Landscape, fit to 1 page wide |

---

## 6. Developer Guide & Security

### Cara Membuat Report Baru

1. Buat Report Group (jika belum ada)
2. Buat Report Definition — tulis SQL query dengan `:param_key` bindings
3. Definisikan Parameters — satu entri per `:param_key`
4. Assign Permission — set status `active`, assign `report.view.{id}` ke role
5. (Opsional) Buat Alert Config dengan fixed param values dan recipients

### Oracle Read-Only User Setup

```sql
CREATE USER rpt_readonly IDENTIFIED BY "your_strong_password";
GRANT CREATE SESSION TO rpt_readonly;
GRANT SELECT ON mgthris.hm_employee    TO rpt_readonly;
GRANT SELECT ON mgthris.hm_attendance  TO rpt_readonly;
GRANT SELECT ON mgtdat.erp_transactions TO rpt_readonly;
```

### Security Checklist

- [ ] User Oracle `rpt_readonly` hanya punya `SELECT`
- [ ] Koneksi `oracle_readonly` menggunakan credential terpisah dari app utama
- [ ] DML validator aktif — setiap query lewat `ReportQueryService::guardDml()`
- [ ] Admin UI (SQL editor) hanya bisa diakses role `developer` atau `super-admin`
- [ ] `ROWNUM` wrap selalu diterapkan
- [ ] Temp file Excel dihapus segera setelah email terkirim

### Troubleshooting

| Error | Solusi |
|---|---|
| ORA-01795: max expressions 1000 | Multi-select > 1000 item — gunakan temporary table |
| ORA-01017: invalid username/password | Cek credential `RPT_DB_READONLY_*` di `.env` |
| Eloquent array cast return null untuk CLOB | Gunakan `App\Casts\ClobJson::class`, bukan `'array'` |
| Email body table tidak terkelompok | Query tidak punya `ORDER BY group_by_col` |
| next_run_at tidak diupdate | Pastikan queue worker berjalan: `php artisan queue:work` |

---

*Dokumen ini di-generate dari ClickUp — Custom Report Module PRD & Technical Spec*
*Source: ClickUp / IT Project / Custom Report Module*
