# Product Requirements Document
# Executive Dashboard System

**Dynamic Multi-Source Business Intelligence Platform**

---

## Document Information

| Field | Value |
|-------|-------|
| Document Title | Executive Dashboard System — PRD v1.0 |
| Project Code | EXEC-DASH-2026 |
| Document Type | Product Requirements Document |
| Owner | IT Leader / Solution Architect |
| Status | Draft for Review |
| Version | 1.0 |
| Target Audience | BOD, Finance, IT, Engineering |
| Primary Stakeholders | Board of Directors, CFO, Finance Manager |

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Background & Problem Statement](#2-background--problem-statement)
3. [Business Objectives & Success Criteria](#3-business-objectives--success-criteria)
4. [Scope](#4-scope)
5. [User Personas](#5-user-personas)
6. [Functional Requirements](#6-functional-requirements)
7. [Data Architecture](#7-data-architecture)
8. [Database Schema (DDL)](#8-database-schema-ddl)
9. [Technical Architecture](#9-technical-architecture)
10. [ETL & Data Pipeline](#10-etl--data-pipeline)
11. [Dynamic Chart Engine](#11-dynamic-chart-engine)
12. [Compare Modes Specification](#12-compare-modes-specification)
13. [Access Control](#13-access-control)
14. [Excel Upload Workflow](#14-excel-upload-workflow)
15. [API Specification](#15-api-specification)
16. [MVP Scope (Phase 1)](#16-mvp-scope-phase-1)
17. [Roadmap (Phase 2 & 3)](#17-roadmap-phase-2--3)
18. [Non-Functional Requirements](#18-non-functional-requirements)
19. [Risks & Mitigations](#19-risks--mitigations)
20. [Success Metrics](#20-success-metrics)
21. [Appendix](#21-appendix)

---

## 1. Executive Summary

Dokumen ini mendefinisikan kebutuhan untuk membangun **Executive Dashboard System**: sebuah platform business intelligence dinamis yang menyajikan KPI eksekutif (EBITDA, Net Profit, Sales, Inventory, Manpower, Expenses, Overtime, dan modul lainnya) kepada Board of Directors (BOD) dan management dengan data yang dikonsolidasikan dari Oracle ERP 11g, Oracle Laravel 11g, dan input manual via Excel.

Sistem ini dirancang dengan arsitektur **generic dan data-driven**: penambahan dashboard baru oleh tim IT dilakukan melalui konfigurasi data (registry dashboard + chart config JSON), tanpa deployment kode frontend baru. Drill-down hierarchical hingga 3 level, comparison modes (MoM, QoQ, YoY, YTD, Rolling 12M), dan dukungan multi-granularity (daily, monthly, quarterly, yearly) menjadi fitur inti.

**Phase 1 (MVP)** fokus pada implementasi engine dashboard generic dengan use case EBITDA + Net Profit. **Phase 2** menambahkan modul Sales, Inventory, HR. **Phase 3** mengintegrasikan kemampuan AI/LLM interactive Q&A dengan vector embeddings (pgvector). Arsitektur PostgreSQL warehouse disiapkan ready untuk pgvector sejak Phase 1.

### Key Decisions Summary

| Area | Keputusan |
|------|-----------|
| Data Warehouse | PostgreSQL 15+ dengan pgvector extension ready |
| Data Sources | Oracle ERP, Oracle Laravel, Excel Upload |
| Refresh Frequency | Setiap 4 jam (6x per hari) |
| Data Granularity | Daily, Monthly, Quarterly, Yearly (kolom grain) |
| Drill-Down Pattern | Navigate with breadcrumb, max 3 levels |
| Chart Engine | Dynamic via `dashboard_config` JSONB + ECharts |
| Compare Modes | MoM, QoQ, YoY, YTD, R12 (Budget defer) |
| Device Target | Responsive desktop & mobile |
| Backend API | Go service dengan JWT auth |
| Frontend | React + ECharts + TanStack Query |
| Caching | Redis TTL 30 menit |
| MVP Scope | EBITDA + Net Profit, engine generic |
| AI/LLM | Schema ready (pgvector), implementasi defer Phase 3 |
| Column Naming | Prefix convention per tabel (no alias query) |

---

## 2. Background & Problem Statement

### 2.1 Current State

Perusahaan saat ini mengoperasikan beberapa environment terpisah:

- **Oracle ERP 11g** sebagai sistem inti transaksi bisnis (production, finance, inventory, sales).
- **Aplikasi web Laravel** berjalan di atas Oracle 11g yang sama dengan schema terpisah.
- **Aplikasi React + Go** dengan PostgreSQL untuk modul operasional lainnya.

Saat ini, laporan eksekutif untuk BOD masih dihasilkan secara manual melalui Excel atau report static dari ERP.

### 2.2 Pain Points

- Laporan eksekutif tidak real-time, refresh manual oleh tim Finance/IT.
- Tidak ada drill-down interaktif — BOD harus minta breakdown manual dari Finance.
- Data tersebar di beberapa source, sulit dikonsolidasikan untuk view eksekutif.
- Penambahan KPI atau dashboard baru memerlukan effort development panjang.
- Tidak ada comparison view yang konsisten (MoM, YoY, YTD).
- Belum ada fondasi untuk integrasi AI/LLM yang menjadi roadmap strategis perusahaan.

### 2.3 Opportunity

Membangun platform dashboard executive yang menyatukan semua source data, menyediakan drill-down interaktif, dan dirancang dengan arsitektur yang siap mendukung AI conversational interface di masa depan. Platform ini akan memberikan visibility real-time kepada BOD dan mengurangi beban operasional tim Finance untuk reporting manual.

---

## 3. Business Objectives & Success Criteria

### 3.1 Business Objectives

1. Menyediakan single source of truth untuk laporan eksekutif lintas modul (Finance, Sales, Operations, HR).
2. Mengurangi waktu produksi laporan eksekutif bulanan dari hitungan hari menjadi instant access.
3. Memberi BOD kemampuan self-service untuk explore data tanpa intervensi tim Finance/IT.
4. Mempercepat penambahan dashboard baru: dari weeks of development menjadi hours of configuration.
5. Menyiapkan foundation data yang AI-ready untuk roadmap conversational analytics.

### 3.2 Success Criteria

| Metric | Target |
|--------|--------|
| Time to load dashboard (P95) | < 2 detik |
| Data freshness lag | Max 4 jam dari source transaction |
| Time to add new dashboard (IT) | < 4 jam (config only, no deployment) |
| BOD adoption rate (Phase 1) | ≥ 80% within 60 hari go-live |
| Manual report request reduction | ≥ 50% within 90 hari |
| Excel upload validation accuracy | ≥ 99% rejected on invalid data |
| System uptime SLA | 99.5% during business hours |

---

## 4. Scope

### 4.1 In Scope (MVP Phase 1)

- Generic dashboard engine dengan dynamic chart configuration.
- Dashboard EBITDA dan Net Profit (Finance MIS module).
- Drill-down navigation hingga 3 level (TYPE → GROUP_1 → GROUP_2 → GROUP_3).
- Compare modes: MoM, QoQ, YoY, YTD, Rolling 12 Months.
- Multi-granularity period (monthly, quarterly, yearly; daily-ready).
- ETL scheduler 4-jam-an dari Oracle ERP ke PostgreSQL.
- Excel upload dengan staging preview dan manual commit oleh requester.
- Admin panel untuk IT setup dashboard config.
- Responsive UI (desktop & mobile).
- RBAC integration dengan sistem authentication existing.
- Audit log untuk perubahan config dan upload data.

### 4.2 Out of Scope (Phase 1)

- Modul Sales, Inventory, HR, Manpower, OPEX (Phase 2).
- AI/LLM conversational interface (Phase 3, schema ready).
- Budget/Forecast scenario comparison (Phase 2).
- Dimension-level drill (per employee, per customer) — Phase 2.
- Email scheduled reports / PDF export (Phase 2).
- Multi-tenant / multi-company support.
- Mobile native app (responsive web cukup untuk MVP).

---

## 5. User Personas

### 5.1 BOD / Executive (Primary Viewer)

| Attribute | Detail |
|-----------|--------|
| Role | CEO, CFO, COO, Director |
| Primary Goal | Quick visibility ke performa perusahaan, decision making |
| Frequency | Daily check, weekly deep-dive |
| Device | Laptop, tablet, mobile (saat traveling) |
| Tech Skill | Low to medium — butuh UX yang intuitif |
| Key Need | KPI summary, trend, drill ke detail saat anomali, compare period |
| Pain Point | Tidak mau tunggu laporan manual, butuh instant answer |

### 5.2 Finance Manager / Department Head (Secondary Viewer & Uploader)

| Attribute | Detail |
|-----------|--------|
| Role | Finance Manager, Operations Manager, HR Manager |
| Primary Goal | Monitor metrik departemen, prepare data untuk BOD |
| Frequency | Daily / hourly |
| Device | Desktop |
| Tech Skill | Medium — familiar dengan Excel, reporting |
| Key Need | Upload data manual via Excel, validate sebelum commit, akses dashboard area-nya |
| Pain Point | Validasi data error-prone, butuh preview sebelum data masuk sistem |

### 5.3 IT Administrator (System Setup)

| Attribute | Detail |
|-----------|--------|
| Role | IT Leader, System Administrator, Database Administrator |
| Primary Goal | Setup dashboard baru, maintain ETL, troubleshoot data issue |
| Frequency | Weekly setup, daily monitoring |
| Device | Desktop |
| Tech Skill | High — SQL, JSON, system architecture |
| Key Need | Admin panel untuk CRUD dashboard config, ETL job monitoring, error log |
| Pain Point | Tidak mau hardcode setiap kali ada permintaan dashboard baru |

---

## 6. Functional Requirements

### 6.1 Dashboard Viewer (FR-VW)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-VW-01 | User dapat melihat list dashboard sesuai role aksesnya, dikelompokkan berdasarkan menu group (Finance, Sales, Operations, dll). | Must |
| FR-VW-02 | User dapat membuka dashboard dan melihat default view (chart + KPI summary). | Must |
| FR-VW-03 | Dashboard menampilkan period filter dengan preset (This Month, Last Month, This Quarter, YTD, Last 12 Months, Custom Range). | Must |
| FR-VW-04 | User dapat memilih compare mode (MoM, QoQ, YoY, YTD, Rolling 12M) dan dashboard re-render dengan comparison line/bar. | Must |
| FR-VW-05 | User dapat drill-down dari level 1 ke level 2 dan level 3 dengan klik bar/segment chart. | Must |
| FR-VW-06 | Breadcrumb navigation tersedia di atas chart untuk kembali ke level sebelumnya. | Must |
| FR-VW-07 | Dashboard menampilkan loading state, empty state, dan error state yang informatif. | Must |
| FR-VW-08 | User dapat melihat tooltip detail saat hover pada chart element. | Should |
| FR-VW-09 | Dashboard responsive — layout berubah sesuai viewport (desktop, tablet, mobile). | Must |
| FR-VW-10 | Dashboard menampilkan timestamp 'Data as of' untuk transparency freshness. | Must |
| FR-VW-11 | User dapat export view chart sebagai PNG image. | Should |
| FR-VW-12 | User dapat export data tabular sebagai Excel/CSV. | Should |

### 6.2 Admin Panel (FR-AD)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-AD-01 | IT Admin dapat melihat list semua dashboard config dengan status active/inactive. | Must |
| FR-AD-02 | IT Admin dapat membuat dashboard baru dengan form: code, title, type, group_1 filter, chart type, drill level, compare modes. | Must |
| FR-AD-03 | IT Admin dapat edit chart configuration JSON (dengan JSON editor + preview). | Must |
| FR-AD-04 | IT Admin dapat preview dashboard rendering sebelum activate. | Should |
| FR-AD-05 | IT Admin dapat assign role yang punya akses ke setiap dashboard. | Must |
| FR-AD-06 | IT Admin dapat group dashboard ke menu category (Finance, Sales, Operations, HR). | Must |
| FR-AD-07 | IT Admin dapat duplicate dashboard config sebagai starting template. | Should |
| FR-AD-08 | IT Admin dapat soft-delete (deactivate) dashboard tanpa hilangkan history. | Must |
| FR-AD-09 | IT Admin dapat view audit log perubahan dashboard config. | Should |

### 6.3 ETL Management (FR-ETL)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ETL-01 | Sistem menjalankan ETL job otomatis setiap 4 jam (00:00, 04:00, 08:00, 12:00, 16:00, 20:00). | Must |
| FR-ETL-02 | IT Admin dapat trigger ETL job manual ad-hoc. | Must |
| FR-ETL-03 | IT Admin dapat view ETL job log: status, start time, end time, rows affected, error message. | Must |
| FR-ETL-04 | Sistem mengirim notification (email/Slack) saat ETL job FAILED. | Should |
| FR-ETL-05 | Sistem mendukung UPSERT (INSERT ON CONFLICT) supaya rerun aman. | Must |
| FR-ETL-06 | Sistem mencatat source identity di setiap row fact untuk audit trail. | Must |
| FR-ETL-07 | Sistem dapat me-refresh materialized views setelah ETL selesai. | Must |

### 6.4 Excel Upload (FR-UP)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-UP-01 | User dapat download Excel template dengan struktur kolom standar dan data validation. | Must |
| FR-UP-02 | User dapat upload file Excel (.xlsx) — sistem validasi struktur kolom, tipe data, required field. | Must |
| FR-UP-03 | Sistem menampilkan preview data hasil parsing + summary (total rows, valid, invalid). | Must |
| FR-UP-04 | Sistem menampilkan error detail per row jika ada validation issue. | Must |
| FR-UP-05 | User dapat klik 'Confirm & Commit' untuk push data dari staging ke fact_metric. | Must |
| FR-UP-06 | User dapat cancel/discard upload session tanpa commit. | Must |
| FR-UP-07 | Sistem mencatat audit: siapa upload, file apa, kapan, berapa rows committed. | Must |
| FR-UP-08 | Excel upload dapat overwrite existing row dengan business key sama (UPSERT) dan mencatat replacement. | Must |

### 6.5 Access Control (FR-AC)

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-AC-01 | User login menggunakan credential existing (reuse Laravel/ERP authentication). | Must |
| FR-AC-02 | Sistem menerima JWT atau session token dari authentication service existing. | Must |
| FR-AC-03 | User hanya melihat dashboard yang role-nya di-assign di `DASHBOARD_CONFIG_ROLE`. | Must |
| FR-AC-04 | User tanpa role yang valid menerima 403 Forbidden, bukan blank page. | Must |
| FR-AC-05 | API endpoint memvalidasi role di setiap request, bukan hanya di UI. | Must |
| FR-AC-06 | Admin panel hanya accessible untuk role ADMIN / IT_LEADER. | Must |

---

## 7. Data Architecture

### 7.1 Design Philosophy

Arsitektur data didesain berdasarkan tiga prinsip:

1. **Single Long-Format Fact Table** — semua source data dikonsolidasikan ke satu tabel `FACT_METRIC` dengan shape yang sama. Tidak ada tabel per modul. Penambahan modul baru cukup INSERT dengan `FM_TYPE` berbeda.

2. **Atomic Granularity, Aggregated by Materialized Views** — fact data disimpan di level paling detail (group_3, daily/monthly), aggregation dilakukan via materialized view per level. Drill-down konsisten karena sumber sama.

3. **Audit-Traceable Multi-Source** — setiap row di `FACT_METRIC` reference ke `DATA_SOURCE` supaya jelas asal data (ERP procedure, Excel upload, dst).

### 7.2 Module Type Registry

Field `FM_TYPE` menjadi top-level container untuk semua modul.

| Code | Module Name | Granularity | Phase |
|------|-------------|-------------|-------|
| MIS | Management Information (EBITDA, P&L, Net Profit) | Monthly | 1 |
| SALES | Sales & Revenue | Daily / Monthly | 2 |
| INV | Inventory & Stock | Monthly | 2 |
| HR | Manpower, Attendance, Overtime | Daily / Monthly | 2 |
| OPEX | Operational Expenses | Monthly | 2 |
| PROD | Production Output & Efficiency | Daily / Monthly | 2 |

### 7.3 Hierarchical Structure

Setiap row di `FACT_METRIC` mengikuti hirarki 3-level yang opsional di level 2 dan 3:

| Field | Description | Required |
|-------|-------------|----------|
| `FM_TYPE` | Top container (MIS, SALES, dst) | Yes |
| `FM_GROUP_1` | Level 1 group (EBITDA, NET PROFIT, LOCAL SALES) | Yes |
| `FM_GROUP_2` | Level 2 sub-group (INCOME, PRODUCTION COST, dst) | Optional |
| `FM_GROUP_3` | Level 3 detail (LOCAL SALES, EXPORT SALES, dst) | Optional |

Drill-down navigation di UI mengikuti hirarki ini.

### 7.4 Period Granularity

Sistem mendukung multi-granularity dengan dua kolom kunci:

- `FM_PERIODE_GRAIN`: string yang menentukan tingkat granularity (`DAILY`, `MONTHLY`, `QUARTERLY`, `YEARLY`).
- `FM_PERIODE_DATE`: DATE field yang menyimpan tanggal aktual. Untuk monthly disimpan sebagai tanggal 1 bulan tersebut (`2025-01-01`). Untuk daily disimpan sebagai tanggal aktual (`2025-01-15`).

Contoh:

| TYPE | GROUP_1 | GRAIN | DATE | VALUE | UoM |
|------|---------|-------|------|-------|-----|
| MIS | EBITDA | MONTHLY | 2025-01-01 | 1,250,000 | USD |
| MIS | NET PROFIT | MONTHLY | 2025-01-01 | 850,000 | USD |
| HR | OVERTIME | DAILY | 2025-01-15 | 320 | HOURS |
| SALES | LOCAL SALES | DAILY | 2025-01-15 | 45,000 | USD |
| INV | STOCK VALUE | MONTHLY | 2025-01-01 | 5,400,000 | USD |

### 7.5 Sign Convention Handling

Data dari ERP menyimpan **income sebagai nilai negatif** dan **cost sebagai nilai positif** (accounting convention). Untuk display ke BOD, ini perlu di-flip supaya intuitif: income tampil positif, cost tampil negatif.

**Penanganan**: kolom `FM_VALUE` menyimpan raw value (sesuai accounting), kolom `FM_DISPLAY_VALUE` menyimpan value yang sudah di-flip berdasarkan business rule. Flipping rule disimpan sebagai logic di transformation view PostgreSQL, tidak di frontend. **Frontend selalu konsumsi `FM_DISPLAY_VALUE` untuk render.**

### 7.6 Audit & Traceability

Setiap row di `FACT_METRIC` mencatat:

- `FM_SOURCE_ID`: FK ke `DATA_SOURCE` — identifikasi asal data (ERP Oracle, Laravel, Excel upload).
- `FM_UPLOADED_BY`: User ID untuk Excel upload, NULL untuk auto ETL.
- `FM_LOADED_AT`: Timestamp kapan data masuk ke warehouse.

Kombinasi tiga field ini memungkinkan IT Admin menelusuri "data EBITDA Januari 2025 ini berasal dari mana?" dengan satu query.

---

## 8. Database Schema (DDL)

### 8.1 Naming Convention

Setiap tabel menggunakan **prefix yang diturunkan dari inisial nama tabel**. Setiap kolom dalam tabel menggunakan prefix yang sama. Manfaat: kolom-kolom unik secara global, query JOIN antar tabel tidak butuh alias, dan ambiguous column error tidak terjadi.

**Prefix Registry:**

| Prefix | Table Name | Purpose |
|--------|------------|---------|
| `FM_` | `FACT_METRIC` | Single fact table konsolidasi semua source |
| `FMD_` | `FACT_METRIC_DIMENSION` | Optional attribute (employee, dept) — defer Phase 2 |
| `DS_` | `DATA_SOURCE` | Registry source data (ERP, Laravel, Excel) |
| `DC_` | `DASHBOARD_CONFIG` | Master dashboard definition |
| `DCG_` | `DASHBOARD_CONFIG_GROUP` | Menu grouping untuk dashboard |
| `DCR_` | `DASHBOARD_CONFIG_ROLE` | Mapping dashboard ke role |
| `EJ_` | `ETL_JOB` | ETL job definition |
| `EJL_` | `ETL_JOB_LOG` | ETL execution log |
| `EU_` | `EXCEL_UPLOAD` | Excel upload session header |
| `EUS_` | `EXCEL_UPLOAD_STAGING` | Staging data sebelum commit |
| `ME_` | `METRIC_EMBEDDING` | Vector embedding untuk AI (Phase 3, kosong) |

### 8.2 FACT_METRIC (Core Fact Table)

```sql
CREATE TABLE FACT_METRIC (
  FM_METRIC_ID         BIGSERIAL PRIMARY KEY,
  FM_TYPE              VARCHAR(20)   NOT NULL,
  FM_GROUP_1           VARCHAR(100)  NOT NULL,
  FM_GROUP_2           VARCHAR(100),
  FM_GROUP_3           VARCHAR(100),
  FM_GROUP_1_ORDER     INT,
  FM_GROUP_2_ORDER     INT,
  FM_GROUP_3_ORDER     INT,
  FM_PERIODE_GRAIN     VARCHAR(10)   NOT NULL,
  FM_PERIODE_DATE      DATE          NOT NULL,
  FM_PERIODE_LABEL     VARCHAR(10)   NOT NULL,
  FM_VALUE             NUMERIC(20,4) NOT NULL,
  FM_DISPLAY_VALUE     NUMERIC(20,4),
  FM_UOM               VARCHAR(20),
  FM_SCENARIO          VARCHAR(20)   DEFAULT 'ACTUAL',
  FM_SOURCE_ID         INT           NOT NULL REFERENCES DATA_SOURCE(DS_SOURCE_ID),
  FM_DIMENSION_KEY     VARCHAR(200),
  FM_UPLOADED_BY       INT,
  FM_LOADED_AT         TIMESTAMP     NOT NULL DEFAULT NOW(),
  FM_IS_ACTIVE         BOOLEAN       DEFAULT TRUE,
  CONSTRAINT UQ_FM_BUSINESS_KEY UNIQUE
    (FM_TYPE, FM_GROUP_1, FM_GROUP_2, FM_GROUP_3,
     FM_PERIODE_GRAIN, FM_PERIODE_DATE, FM_SCENARIO, FM_DIMENSION_KEY)
);

CREATE INDEX IDX_FM_LOOKUP ON FACT_METRIC
  (FM_TYPE, FM_GROUP_1, FM_PERIODE_DATE);
CREATE INDEX IDX_FM_DATE_GRAIN ON FACT_METRIC
  (FM_PERIODE_GRAIN, FM_PERIODE_DATE);
```

### 8.3 DATA_SOURCE

```sql
CREATE TABLE DATA_SOURCE (
  DS_SOURCE_ID         SERIAL PRIMARY KEY,
  DS_SOURCE_CODE       VARCHAR(20)  UNIQUE NOT NULL,
  DS_SOURCE_NAME       VARCHAR(100) NOT NULL,
  DS_SOURCE_TYPE       VARCHAR(20)  NOT NULL,
  DS_CONNECTION_INFO   JSONB,
  DS_IS_ACTIVE         BOOLEAN DEFAULT TRUE,
  DS_CREATED_DATE      TIMESTAMP DEFAULT NOW()
);

INSERT INTO DATA_SOURCE (DS_SOURCE_CODE, DS_SOURCE_NAME, DS_SOURCE_TYPE) VALUES
  ('ERP_ORACLE',   'Oracle ERP 11g',     'ORACLE'),
  ('LARAVEL_DB',   'Oracle Laravel 11g', 'ORACLE'),
  ('EXCEL_UPLOAD', 'Excel Manual Upload','EXCEL');
```

### 8.4 DASHBOARD_CONFIG

```sql
CREATE TABLE DASHBOARD_CONFIG (
  DC_DASHBOARD_ID      SERIAL PRIMARY KEY,
  DC_DASHBOARD_CODE    VARCHAR(50) UNIQUE NOT NULL,
  DC_DASHBOARD_TITLE   VARCHAR(200) NOT NULL,
  DC_DESCRIPTION       TEXT,
  DC_FILTER_TYPE       VARCHAR(20) NOT NULL,
  DC_FILTER_GROUP_1    VARCHAR(100),
  DC_PERIODE_GRAIN     VARCHAR(10) NOT NULL,
  DC_DEFAULT_VIEW      VARCHAR(20),
  DC_CHART_CONFIG      JSONB NOT NULL,
  DC_LAYOUT_CONFIG     JSONB,
  DC_DRILL_ENABLED     BOOLEAN DEFAULT TRUE,
  DC_MAX_DRILL_LEVEL   INT DEFAULT 3,
  DC_COMPARE_MODES     JSONB,
  DC_DISPLAY_ORDER     INT,
  DC_GROUP_ID          INT REFERENCES DASHBOARD_CONFIG_GROUP(DCG_GROUP_ID),
  DC_IS_ACTIVE         BOOLEAN DEFAULT TRUE,
  DC_CREATED_BY        INT,
  DC_CREATED_DATE      TIMESTAMP DEFAULT NOW(),
  DC_UPDATED_DATE      TIMESTAMP
);
```

### 8.5 DASHBOARD_CONFIG_GROUP & DASHBOARD_CONFIG_ROLE

```sql
CREATE TABLE DASHBOARD_CONFIG_GROUP (
  DCG_GROUP_ID         SERIAL PRIMARY KEY,
  DCG_GROUP_CODE       VARCHAR(20) UNIQUE NOT NULL,
  DCG_GROUP_NAME       VARCHAR(100) NOT NULL,
  DCG_DISPLAY_ORDER    INT,
  DCG_ICON             VARCHAR(50),
  DCG_IS_ACTIVE        BOOLEAN DEFAULT TRUE
);

CREATE TABLE DASHBOARD_CONFIG_ROLE (
  DCR_ROLE_ID          SERIAL PRIMARY KEY,
  DCR_DASHBOARD_ID     INT NOT NULL REFERENCES DASHBOARD_CONFIG(DC_DASHBOARD_ID),
  DCR_ROLE_CODE        VARCHAR(50) NOT NULL,
  DCR_CREATED_DATE     TIMESTAMP DEFAULT NOW(),
  CONSTRAINT UQ_DCR UNIQUE (DCR_DASHBOARD_ID, DCR_ROLE_CODE)
);
```

### 8.6 ETL Tables

```sql
CREATE TABLE ETL_JOB (
  EJ_JOB_ID            SERIAL PRIMARY KEY,
  EJ_JOB_NAME          VARCHAR(100) NOT NULL,
  EJ_SOURCE_ID         INT NOT NULL REFERENCES DATA_SOURCE(DS_SOURCE_ID),
  EJ_TARGET_TYPE       VARCHAR(20),
  EJ_SCHEDULE_CRON     VARCHAR(50),
  EJ_ORACLE_PROCEDURE  VARCHAR(200),
  EJ_IS_ACTIVE         BOOLEAN DEFAULT TRUE,
  EJ_CREATED_DATE      TIMESTAMP DEFAULT NOW()
);

CREATE TABLE ETL_JOB_LOG (
  EJL_LOG_ID           BIGSERIAL PRIMARY KEY,
  EJL_JOB_ID           INT NOT NULL REFERENCES ETL_JOB(EJ_JOB_ID),
  EJL_STARTED_AT       TIMESTAMP NOT NULL,
  EJL_ENDED_AT         TIMESTAMP,
  EJL_STATUS           VARCHAR(20),
  EJL_ROWS_AFFECTED    INT,
  EJL_ERROR_MESSAGE    TEXT,
  EJL_TRIGGERED_BY     VARCHAR(50)
);
```

### 8.7 Excel Upload Tables

```sql
CREATE TABLE EXCEL_UPLOAD (
  EU_UPLOAD_ID         SERIAL PRIMARY KEY,
  EU_SOURCE_ID         INT NOT NULL REFERENCES DATA_SOURCE(DS_SOURCE_ID),
  EU_FILE_NAME         VARCHAR(255) NOT NULL,
  EU_FILE_SIZE         INT,
  EU_UPLOADED_BY       INT NOT NULL,
  EU_UPLOADED_AT       TIMESTAMP DEFAULT NOW(),
  EU_STATUS            VARCHAR(20),
  EU_TOTAL_ROWS        INT,
  EU_VALID_ROWS        INT,
  EU_COMMITTED_ROWS    INT,
  EU_ERROR_SUMMARY     JSONB
);

CREATE TABLE EXCEL_UPLOAD_STAGING (
  EUS_STAGING_ID         BIGSERIAL PRIMARY KEY,
  EUS_UPLOAD_ID          INT NOT NULL REFERENCES EXCEL_UPLOAD(EU_UPLOAD_ID),
  EUS_ROW_NUMBER         INT,
  EUS_TYPE               VARCHAR(20),
  EUS_GROUP_1            VARCHAR(100),
  EUS_GROUP_2            VARCHAR(100),
  EUS_GROUP_3            VARCHAR(100),
  EUS_PERIODE_GRAIN      VARCHAR(10),
  EUS_PERIODE_DATE       DATE,
  EUS_VALUE              NUMERIC(20,4),
  EUS_VALIDATION_STATUS  VARCHAR(20),
  EUS_VALIDATION_MSG     TEXT
);
```

### 8.8 Materialized Views (Aggregation)

```sql
CREATE MATERIALIZED VIEW MV_METRIC_G1 AS
  SELECT FM_TYPE, FM_GROUP_1, FM_PERIODE_GRAIN, FM_PERIODE_DATE,
         FM_SCENARIO, SUM(FM_DISPLAY_VALUE) AS MV_VALUE
  FROM FACT_METRIC
  WHERE FM_IS_ACTIVE = TRUE
  GROUP BY 1,2,3,4,5;

CREATE MATERIALIZED VIEW MV_METRIC_G2 AS
  SELECT FM_TYPE, FM_GROUP_1, FM_GROUP_2, FM_PERIODE_GRAIN,
         FM_PERIODE_DATE, FM_SCENARIO,
         SUM(FM_DISPLAY_VALUE) AS MV_VALUE
  FROM FACT_METRIC
  WHERE FM_IS_ACTIVE = TRUE
  GROUP BY 1,2,3,4,5,6;

-- G3 query langsung dari FACT_METRIC (sudah atomic)

CREATE OR REPLACE FUNCTION REFRESH_DASHBOARD_MVS()
RETURNS VOID AS $$
BEGIN
  REFRESH MATERIALIZED VIEW CONCURRENTLY MV_METRIC_G1;
  REFRESH MATERIALIZED VIEW CONCURRENTLY MV_METRIC_G2;
END;
$$ LANGUAGE plpgsql;
```

### 8.9 AI/LLM Schema (Phase 3 Ready)

Tabel berikut dibuat sejak Phase 1 dengan extension pgvector ter-enable, namun dibiarkan kosong sampai Phase 3 aktif.

```sql
CREATE EXTENSION IF NOT EXISTS vector;

CREATE TABLE METRIC_EMBEDDING (
  ME_EMBEDDING_ID      BIGSERIAL PRIMARY KEY,
  ME_METRIC_ID         BIGINT REFERENCES FACT_METRIC(FM_METRIC_ID),
  ME_DASHBOARD_ID      INT REFERENCES DASHBOARD_CONFIG(DC_DASHBOARD_ID),
  ME_TEXT_CONTENT      TEXT NOT NULL,
  ME_EMBEDDING         VECTOR(1536),
  ME_MODEL             VARCHAR(50),
  ME_CREATED_DATE      TIMESTAMP DEFAULT NOW()
);

CREATE INDEX IDX_ME_VECTOR ON METRIC_EMBEDDING
  USING ivfflat (ME_EMBEDDING vector_cosine_ops) WITH (lists = 100);
```

---

## 9. Technical Architecture

### 9.1 Stack Summary

| Layer | Technology | Justification |
|-------|------------|---------------|
| Source DB | Oracle 11g (ERP + Laravel schema) | Existing systems |
| Warehouse | PostgreSQL 15+ dengan pgvector | Modern features, AI-ready, decoupled from ERP |
| ETL Worker | Python 3.11 (cx_Oracle + psycopg2) | Mature library, easy debug, scriptable |
| Backend API | Go 1.21+ | Concurrency-friendly, existing stack |
| Cache | Redis 7 | Fast key-value, TTL support, industry standard |
| Frontend | React 18 + TypeScript | Existing stack, ecosystem |
| Chart Library | ECharts 5 | Powerful financial chart (waterfall, sunburst) |
| Data Fetching | TanStack Query v5 | Caching + stale-while-revalidate |
| UI Component | Tailwind CSS + shadcn/ui | Productivity, consistency |
| Auth | Reuse existing (JWT forward) | No duplication of identity |
| Scheduler | Cron / Linux systemd timer | Simple, observable, no extra dependency |

### 9.2 Component Responsibility

#### 9.2.1 PostgreSQL Warehouse
- Single source of truth untuk semua dashboard data.
- Menyimpan `FACT_METRIC` dan materialized views.
- Menyimpan `DASHBOARD_CONFIG` dan metadata terkait.
- pgvector extension siap pakai untuk Phase 3.

#### 9.2.2 ETL Worker (Python)
- Dijalankan via cron tiap 4 jam (00, 04, 08, 12, 16, 20).
- Memanggil stored procedure di Oracle untuk aggregate raw transaksi.
- Mengambil hasil aggregation dan UPSERT ke `FACT_METRIC`.
- Mencatat `ETL_JOB_LOG` dengan status, rows affected, durasi.
- Memanggil `REFRESH_DASHBOARD_MVS()` setelah selesai.
- Mengirim alert ke email/Slack jika job FAILED.

#### 9.2.3 Backend API (Go)
- Endpoint REST untuk dashboard viewer dan admin panel.
- Validasi JWT dari authentication service existing.
- Query ke PostgreSQL (atomic atau via materialized views).
- Cache result di Redis dengan TTL 30 menit.
- Invalidate cache setelah ETL selesai.
- Compute compare modes (MoM, YoY) menggunakan PostgreSQL window functions.

#### 9.2.4 Frontend (React)
- Generic dashboard renderer: terima `dashboard_code`, render sesuai config.
- Chart engine berbasis ECharts dengan mapping dari `DC_CHART_CONFIG` JSON.
- Drill navigation dengan breadcrumb.
- Responsive layout — desktop, tablet, mobile dengan Tailwind.
- State management ringan via Zustand atau React Context.

---

## 10. ETL & Data Pipeline

### 10.1 Schedule

| Time (WIB) | Job Type | Source | Notes |
|------------|----------|--------|-------|
| 00:00 | Full refresh active periods | Oracle ERP | Closing data harian terakhir |
| 04:00 | Delta update | Oracle ERP | Capture early morning transactions |
| 08:00 | Delta update | Oracle ERP + Laravel | Morning operations |
| 12:00 | Delta update | Oracle ERP + Laravel | Mid-day catch-up |
| 16:00 | Delta update | Oracle ERP + Laravel | Afternoon transactions |
| 20:00 | Delta update | Oracle ERP + Laravel | End of business day |

### 10.2 Pipeline Flow per Run

1. Cron trigger ETL worker Python script.
2. Worker connect ke Oracle, panggil stored procedure aggregation (contoh: `SP_DASHBOARD_MIS_REFRESH`).
3. Stored procedure di Oracle melakukan SUM/aggregate dari raw transaction tables ke struktur long-format.
4. Worker fetch hasil aggregation via cursor (chunked, 10k rows per batch).
5. Worker connect ke PostgreSQL, eksekusi UPSERT ke `FACT_METRIC` dengan `ON CONFLICT` clause.
6. Worker compute `FM_DISPLAY_VALUE` berdasarkan sign convention rule per group.
7. Setelah UPSERT selesai, worker panggil `REFRESH_DASHBOARD_MVS()` untuk refresh materialized views.
8. Worker trigger cache invalidation di Redis (`DEL pattern 'dashboard:*'`).
9. Worker insert `ETL_JOB_LOG` dengan status SUCCESS/FAILED, rows affected, duration.
10. Jika FAILED, worker kirim alert via email/Slack webhook.

### 10.3 Sign Convention Logic

```sql
-- Untuk MIS_TYPE = 'MIS' dan GROUP_1 = 'EBITDA':
-- INCOME: flip sign (raw negatif jadi display positif)
-- Semua COST/EXPENSE: keep as is, tampil negatif di waterfall

FM_DISPLAY_VALUE = CASE
  WHEN FM_TYPE = 'MIS' AND FM_GROUP_2 = 'INCOME' THEN -FM_VALUE
  WHEN FM_TYPE = 'MIS' AND FM_GROUP_2 LIKE '%COST%' THEN -FM_VALUE
  WHEN FM_TYPE = 'MIS' AND FM_GROUP_2 LIKE '%CONSUMPTION%' THEN -FM_VALUE
  WHEN FM_TYPE = 'MIS' AND FM_GROUP_2 = 'MANPOWER' THEN -FM_VALUE
  WHEN FM_TYPE = 'MIS' AND FM_GROUP_2 = 'OVERHEADS' THEN -FM_VALUE
  WHEN FM_TYPE = 'MIS' AND FM_GROUP_2 = 'SELLING COST' THEN -FM_VALUE
  WHEN FM_TYPE = 'MIS' AND FM_GROUP_2 = 'BAD DEBT EXP' THEN -FM_VALUE
  ELSE FM_VALUE
END;

-- EBITDA total = SUM(FM_DISPLAY_VALUE) untuk semua GROUP_2 di GROUP_1='EBITDA'
```

### 10.4 Error Handling

- **Oracle connection failure**: retry 3x dengan exponential backoff (10s, 30s, 90s), kemudian mark FAILED.
- **Partial failure**: transaction wrap per chunk, gagal di chunk N tidak rollback chunk 1 sampai N-1, log specific chunk.
- **Schema drift**: validate column existence di awal, gagal cepat dengan pesan jelas.
- **Duplicate key constraint**: log row yang konflik, tetap proses sisanya.

---

## 11. Dynamic Chart Engine

### 11.1 Concept

Frontend tidak menyimpan logic per dashboard. Sebaliknya, frontend punya satu generic chart renderer yang membaca `DC_CHART_CONFIG` JSON dan memilih komponen ECharts yang sesuai. Setup dashboard baru = INSERT row di `DASHBOARD_CONFIG` dengan chart_config yang tepat.

### 11.2 Supported Chart Types

| Type Code | Description | Best For |
|-----------|-------------|----------|
| `bar` | Vertical bar chart | Period comparison, ranking |
| `horizontal_bar` | Horizontal bar chart | Long category labels, top N |
| `stacked_bar` | Stacked bar chart | Component breakdown over time |
| `line` | Line chart | Trend over time, multi-series compare |
| `area` | Area chart | Cumulative trend, volume |
| `waterfall` | Waterfall chart | EBITDA flow, P&L bridge |
| `donut` | Donut/pie chart | Composition snapshot |
| `kpi_card` | KPI card dengan sparkline | Top-level summary metrics |
| `treemap` | Treemap | Hierarchical proportion |
| `heatmap` | Calendar heatmap | Daily metric over months |
| `scatter` | Scatter plot | Correlation between metrics |

### 11.3 Chart Config JSON Schema

```json
{
  "type": "waterfall",
  "primary_color": "#1F4E79",
  "positive_color": "#1d9e75",
  "negative_color": "#a32d2d",
  "total_color": "#534ab7",
  "x_axis_field": "group_2",
  "y_axis_field": "display_value",
  "x_axis_label": "Component",
  "y_axis_label": "Value (USD '000)",
  "show_data_labels": true,
  "show_total_bar": true,
  "number_format": "currency_thousands",
  "decimals": 1,
  "tooltip_format": "detail",
  "legend_position": "bottom",
  "grid_lines": "horizontal",
  "drill_to": "group_3",
  "empty_message": "No data for selected period"
}
```

### 11.4 Example: EBITDA Waterfall Config

```sql
INSERT INTO DASHBOARD_CONFIG (
  DC_DASHBOARD_CODE, DC_DASHBOARD_TITLE, DC_FILTER_TYPE,
  DC_FILTER_GROUP_1, DC_PERIODE_GRAIN, DC_DEFAULT_VIEW,
  DC_CHART_CONFIG, DC_DRILL_ENABLED, DC_MAX_DRILL_LEVEL,
  DC_COMPARE_MODES, DC_DISPLAY_ORDER, DC_GROUP_ID, DC_IS_ACTIVE
) VALUES (
  'EBITDA',
  'EBITDA Performance',
  'MIS',
  'EBITDA',
  'MONTHLY',
  'monthly',
  '{"type":"waterfall","x_axis_field":"group_2",
    "y_axis_field":"display_value","show_total_bar":true,
    "number_format":"currency_thousands"}'::JSONB,
  TRUE,
  3,
  '["MoM","QoQ","YoY","YTD","R12"]'::JSONB,
  1,
  (SELECT DCG_GROUP_ID FROM DASHBOARD_CONFIG_GROUP
   WHERE DCG_GROUP_CODE='FINANCE'),
  TRUE
);
```

### 11.5 Frontend Renderer Logic (Pseudo)

```javascript
// React component: <DashboardRenderer code='EBITDA' />
function DashboardRenderer({ code }) {
  const config = useDashboardConfig(code);
  const filters = useDashboardFilters(code);
  const data = useDashboardData(code, filters);

  const chartType = config.chart_config.type;
  const ChartComponent = CHART_REGISTRY[chartType];

  return (
    <DashboardLayout>
      <FilterBar dashboardCode={code} compareModes={config.compare_modes} />
      <Breadcrumb levels={filters.drillPath} />
      <ChartComponent
        data={data}
        config={config.chart_config}
        onDrill={(level, value) => drillDown(level, value)}
      />
    </DashboardLayout>
  );
}

const CHART_REGISTRY = {
  waterfall: WaterfallChart,
  bar: BarChart,
  line: LineChart,
  donut: DonutChart,
  kpi_card: KPICard,
  // ... mapping ke ECharts wrapper components
};
```

---

## 12. Compare Modes Specification

### 12.1 Supported Modes

| Mode | Description | Calculation |
|------|-------------|-------------|
| MoM | Month over Month | Current month vs previous month |
| QoQ | Quarter over Quarter | Current quarter vs previous quarter |
| YoY | Year over Year | Same period current year vs last year |
| YTD | Year to Date | Jan-current month current year vs last year |
| R12 | Rolling 12 months | Last 12 months sum vs previous 12 months |
| DoD | Day over Day (daily only) | Today vs yesterday |
| WoW | Week over Week (daily only) | This week vs last week |

### 12.2 SQL Implementation Pattern

```sql
-- Contoh: MoM dan YoY untuk EBITDA monthly
WITH BASE AS (
  SELECT FM_TYPE, FM_GROUP_1, FM_PERIODE_DATE,
         SUM(FM_DISPLAY_VALUE) AS BS_VALUE
  FROM FACT_METRIC
  WHERE FM_TYPE = 'MIS'
    AND FM_GROUP_1 = 'EBITDA'
    AND FM_PERIODE_GRAIN = 'MONTHLY'
    AND FM_IS_ACTIVE = TRUE
  GROUP BY 1, 2, 3
)
SELECT FM_TYPE, FM_GROUP_1, FM_PERIODE_DATE,
       BS_VALUE AS CURRENT_VALUE,
       LAG(BS_VALUE, 1) OVER (ORDER BY FM_PERIODE_DATE) AS MOM_PREV,
       LAG(BS_VALUE, 12) OVER (ORDER BY FM_PERIODE_DATE) AS YOY_PREV,
       ROUND(
         (BS_VALUE - LAG(BS_VALUE, 1) OVER (ORDER BY FM_PERIODE_DATE))
         / NULLIF(LAG(BS_VALUE, 1) OVER (ORDER BY FM_PERIODE_DATE), 0)
         * 100, 2
       ) AS MOM_PCT,
       ROUND(
         (BS_VALUE - LAG(BS_VALUE, 12) OVER (ORDER BY FM_PERIODE_DATE))
         / NULLIF(LAG(BS_VALUE, 12) OVER (ORDER BY FM_PERIODE_DATE), 0)
         * 100, 2
       ) AS YOY_PCT
FROM BASE
ORDER BY FM_PERIODE_DATE DESC;
```

### 12.3 UI Display Pattern

- Compare mode dropdown di FilterBar, default disesuaikan grain dashboard (monthly → MoM).
- KPI Card menampilkan: current value (besar), delta absolute (kecil), delta % (kecil + warna hijau/merah).
- Line/Bar chart menambah series 'previous period' dengan style berbeda (dashed line atau lighter color).
- Tooltip menampilkan kedua nilai: current vs comparison + delta.

---

## 13. Access Control

### 13.1 Authentication

Sistem tidak mengelola identity sendiri. Authentication dilakukan oleh service existing (Laravel atau ERP). Dashboard backend menerima JWT atau forwarded session token, dan memvalidasi claim (user_id, role) di setiap request.

### 13.2 Authorization Model

Authorization berbasis role-to-dashboard mapping yang disimpan di `DASHBOARD_CONFIG_ROLE`.

```sql
-- Contoh role mapping:
INSERT INTO DASHBOARD_CONFIG_ROLE (DCR_DASHBOARD_ID, DCR_ROLE_CODE) VALUES
  ((SELECT DC_DASHBOARD_ID FROM DASHBOARD_CONFIG WHERE DC_DASHBOARD_CODE='EBITDA'), 'CEO'),
  ((SELECT DC_DASHBOARD_ID FROM DASHBOARD_CONFIG WHERE DC_DASHBOARD_CODE='EBITDA'), 'CFO'),
  ((SELECT DC_DASHBOARD_ID FROM DASHBOARD_CONFIG WHERE DC_DASHBOARD_CODE='EBITDA'), 'FINANCE_MANAGER'),
  ((SELECT DC_DASHBOARD_ID FROM DASHBOARD_CONFIG WHERE DC_DASHBOARD_CODE='NET_PROFIT'), 'CEO'),
  ((SELECT DC_DASHBOARD_ID FROM DASHBOARD_CONFIG WHERE DC_DASHBOARD_CODE='NET_PROFIT'), 'CFO');
```

### 13.3 Request Authorization Flow

1. Frontend kirim request dengan `Authorization` header berisi JWT.
2. Go API middleware decode JWT, extract user_id dan role_code.
3. Untuk request dashboard data: cek apakah role user ada di `DASHBOARD_CONFIG_ROLE` untuk dashboard_code yang diminta.
4. Jika tidak ada: response 403 dengan message "You do not have access to this dashboard".
5. Jika ada: proceed query dan response data.
6. Setiap request di-log di access log dengan user_id, dashboard, timestamp, status.

### 13.4 Admin Panel Authorization

Admin panel di-restrict ke role: ADMIN, IT_LEADER, SYSTEM_ADMIN. Endpoint admin (`/admin/*`) divalidasi di middleware terpisah.

---

## 14. Excel Upload Workflow

### 14.1 User Flow

1. User membuka modul 'Data Upload' di dashboard.
2. User memilih dashboard target (contoh: EBITDA) dari dropdown.
3. Sistem menyediakan tombol 'Download Template' — Excel kosong dengan kolom standar dan data validation.
4. User mengisi template, save sebagai `.xlsx`, lalu klik 'Upload File'.
5. Sistem parsing file, validasi struktur dan tipe data, simpan ke `EXCEL_UPLOAD_STAGING`.
6. Sistem menampilkan preview screen: total rows, valid rows, invalid rows, error detail per row.
7. User dapat: (a) Cancel/Discard, (b) Download Error Report, (c) Confirm & Commit.
8. Jika user Confirm: sistem UPSERT data dari staging ke `FACT_METRIC`, update `EXCEL_UPLOAD.EU_STATUS = 'COMMITTED'`.
9. Sistem trigger materialized view refresh dan cache invalidation.
10. Dashboard target langsung menampilkan data baru.

### 14.2 Excel Template Structure

Template Excel mengikuti shape `FACT_METRIC`. Kolom mandatory:

| Column | Type | Required | Validation |
|--------|------|----------|------------|
| `TYPE` | String | Yes | Must match `DC_FILTER_TYPE` (MIS, SALES, dst) |
| `GROUP_1` | String | Yes | Max 100 chars |
| `GROUP_2` | String | No | Max 100 chars |
| `GROUP_3` | String | No | Max 100 chars |
| `GROUP_1_ORDER` | Int | No | Default 1 |
| `GROUP_2_ORDER` | Int | No | Default 1 |
| `GROUP_3_ORDER` | Int | No | Default 1 |
| `PERIODE_GRAIN` | String | Yes | `DAILY` / `MONTHLY` / `QUARTERLY` / `YEARLY` |
| `PERIODE` | String/Date | Yes | YYYYMM (monthly) atau YYYY-MM-DD (daily) |
| `VALUE` | Numeric | Yes | Decimal allowed, negative allowed |
| `UOM` | String | No | USD/HOURS/PCS/PERSON |
| `SCENARIO` | String | No | Default `ACTUAL` |

### 14.3 Validation Rules

- File extension harus `.xlsx` (`.xls` dan `.csv` di Phase 2).
- File size max 10 MB.
- Header row harus exact match dengan template.
- `TYPE` harus exist di `DASHBOARD_CONFIG.DC_FILTER_TYPE`.
- `PERIODE_GRAIN` harus salah satu dari enum yang valid.
- `VALUE` harus numeric, tidak boleh NULL.
- Duplicate business key dalam satu file akan ditandai sebagai error.
- Total rows max 50,000 per upload.

### 14.4 Audit Trail

Setiap upload session dicatat di `EXCEL_UPLOAD` dengan: file name, file size, uploaded by, uploaded at, status (`PENDING`/`PREVIEW`/`COMMITTED`/`CANCELLED`/`FAILED`), total rows, valid rows, committed rows, error summary JSON. Data yang di-commit ke `FACT_METRIC` otomatis mencatat `FM_UPLOADED_BY` dan `FM_SOURCE_ID = 3` (EXCEL_UPLOAD).

---

## 15. API Specification

### 15.1 Base Configuration

- **Base URL**: `/api/v1`
- **Authentication**: Bearer JWT in `Authorization` header
- **Content-Type**: `application/json`
- **Error format**: `{ "error": { "code": "...", "message": "..." } }`

### 15.2 Viewer Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/dashboards` | List dashboard accessible by current user role |
| GET | `/dashboards/:code/config` | Get dashboard config (chart type, drill, compare modes) |
| GET | `/dashboards/:code/data` | Get dashboard data with filter params |
| GET | `/dashboards/:code/compare` | Get comparison data (MoM/YoY/etc) |
| GET | `/dashboards/:code/drill` | Drill data ke level lebih dalam |
| GET | `/dashboards/:code/export/png` | Export chart sebagai PNG |
| GET | `/dashboards/:code/export/excel` | Export data tabular sebagai Excel |

#### Example: GET /dashboards/EBITDA/data

```http
GET /api/v1/dashboards/EBITDA/data
  ?period_from=2025-01-01
  &period_to=2025-12-31
  &grain=MONTHLY
  &level=1
  &compare=YoY
```

Response 200:

```json
{
  "dashboard_code": "EBITDA",
  "data_as_of": "2026-05-26T16:00:00Z",
  "grain": "MONTHLY",
  "level": 1,
  "compare": "YoY",
  "data": [
    {
      "periode": "2025-01-01",
      "label": "Jan 2025",
      "value": 1250000,
      "compare_value": 1100000,
      "delta_abs": 150000,
      "delta_pct": 13.64
    }
  ]
}
```

### 15.3 Admin Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/admin/dashboards` | List all dashboard configs |
| POST | `/admin/dashboards` | Create new dashboard config |
| PUT | `/admin/dashboards/:id` | Update dashboard config |
| DELETE | `/admin/dashboards/:id` | Soft delete (deactivate) |
| GET | `/admin/etl/jobs` | List ETL jobs |
| GET | `/admin/etl/logs` | List ETL execution logs |
| POST | `/admin/etl/trigger/:job_id` | Manually trigger ETL job |
| GET | `/admin/uploads` | List Excel uploads (all users) |
| GET | `/admin/audit` | Get config change audit log |

### 15.4 Upload Endpoints

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/upload/template/:dashboard_code` | Download Excel template |
| POST | `/upload/parse` | Upload file, parse, save to staging |
| GET | `/upload/:upload_id/preview` | Get staging data preview + validation |
| POST | `/upload/:upload_id/commit` | Commit staging ke fact_metric |
| POST | `/upload/:upload_id/cancel` | Cancel/discard staging |
| GET | `/upload/:upload_id/errors` | Download error report |

### 15.5 Future AI Endpoint (Phase 3 Placeholder)

| Method | Path | Purpose |
|--------|------|---------|
| POST | `/ai/query` | Natural language query → SQL/data result |
| POST | `/ai/explain` | Explain anomaly/trend untuk metric |
| GET | `/ai/suggestions` | Suggested questions berdasarkan context |

---

## 16. MVP Scope (Phase 1)

### 16.1 Deliverables

1. PostgreSQL warehouse setup dengan schema lengkap (FM, DC, DS, EJ, EU, ME tables).
2. pgvector extension installed (kosong, ready untuk Phase 3).
3. ETL worker Python untuk EBITDA + Net Profit dari Oracle ERP, schedule 4 jam-an.
4. Oracle stored procedure untuk aggregate EBITDA dan Net Profit ke long format.
5. Materialized views level G1, G2, dan refresh function.
6. Go API service dengan endpoint viewer (dashboards, data, compare, drill) dan admin (CRUD config, ETL log).
7. Redis cache layer dengan TTL 30 menit.
8. React frontend: dashboard viewer + admin panel dengan responsive layout.
9. ECharts integration: waterfall, bar, line, KPI card chart types.
10. Drill-down navigation dengan breadcrumb.
11. Compare modes: MoM, QoQ, YoY, YTD, R12.
12. Excel upload workflow lengkap (template, parse, preview, commit).
13. RBAC integration dengan Laravel/ERP authentication.
14. Seed data: dashboard config untuk EBITDA dan NET_PROFIT.
15. Operations: monitoring dashboard, log alerting (email/Slack).

### 16.2 Phase 1 Dashboards

| Code | Title | Chart Type | Drill Level | Compare |
|------|-------|-----------|-------------|---------|
| EBITDA | EBITDA Performance | Waterfall + KPI | 3 | MoM, QoQ, YoY, YTD, R12 |
| NET_PROFIT | Net Profit Trend | Bar + Line | 2 | MoM, YoY, YTD |

### 16.3 Timeline Estimate

| Phase | Duration | Activity |
|-------|----------|----------|
| Sprint 1-2 | 2 weeks | Schema design, PostgreSQL setup, Oracle procedure |
| Sprint 3-4 | 2 weeks | ETL worker, materialized views, ETL logging |
| Sprint 5-6 | 2 weeks | Go API: viewer endpoints + auth integration |
| Sprint 7-8 | 2 weeks | React generic chart engine + EBITDA dashboard |
| Sprint 9 | 1 week | Drill-down + compare modes UI |
| Sprint 10 | 1 week | Excel upload flow + admin panel |
| Sprint 11 | 1 week | Responsive polish + mobile testing |
| Sprint 12 | 1 week | UAT, bug fixes, go-live preparation |

**Total estimasi MVP: 12 minggu (3 bulan)** dengan tim 1 backend, 1 frontend, 1 DBA/ETL, 1 PM/UAT.

---

## 17. Roadmap (Phase 2 & 3)

### 17.1 Phase 2: Module Expansion (3-4 bulan setelah Phase 1)

- Sales dashboard (daily + monthly, breakdown by region, customer segment).
- Inventory dashboard (stock value, aging, turnover ratio).
- HR dashboard (manpower, attendance, overtime daily).
- OPEX dashboard (operational expenses by department).
- Production dashboard (output, efficiency, downtime).
- Budget/Forecast scenario comparison (Actual vs Budget vs Forecast).
- `FACT_METRIC_DIMENSION` aktif untuk attribute-level drill (per employee, per customer).
- Scheduled email/PDF report ke BOD.
- Annotation feature — user dapat add notes pada anomaly point.
- Mobile native app (kalau adoption tinggi).

### 17.2 Phase 3: AI/LLM Integration (6 bulan setelah Phase 1)

- Generate text representation dari `FACT_METRIC` dan `DASHBOARD_CONFIG`.
- Generate embeddings via OpenAI/local model, simpan di `METRIC_EMBEDDING`.
- Natural language Q&A: "Mengapa EBITDA Januari turun?" → LLM analyze data + return insight.
- Anomaly explanation: AI auto-detect outlier dan generate explanation candidate.
- Forecast suggestion berbasis historical pattern.
- Voice input (mobile).
- Conversational drill: "Show me sales trend dari customer A bulan lalu" → dashboard auto-config.

---

## 18. Non-Functional Requirements

### 18.1 Performance

| Metric | Target |
|--------|--------|
| Dashboard initial load (P95) | < 2 detik |
| Drill-down click response (P95) | < 1 detik |
| Filter change re-render (P95) | < 1.5 detik |
| Excel upload parsing (10k rows) | < 30 detik |
| ETL job duration (full refresh) | < 15 menit |
| API throughput | ≥ 100 req/sec concurrent |
| Cache hit rate | ≥ 70% untuk dashboard data |

### 18.2 Scalability

- Sistem harus mendukung pertumbuhan ke 50+ dashboard tanpa rebuild.
- Fact table harus mendukung 100 juta+ rows dengan query masih < 2 detik (via partitioning + index).
- Concurrent user target: 200 (BOD + management + finance team).
- ETL harus support parallel job (multiple modul refresh bersamaan).

### 18.3 Security

- HTTPS mandatory untuk semua traffic.
- JWT signed dengan RS256, expire 8 jam, refresh token 30 hari.
- SQL injection prevention via parameterized queries.
- XSS prevention via React default escaping + Content-Security-Policy header.
- Sensitive data (Oracle credentials) disimpan di environment variable atau secret manager, bukan di code.
- Audit log untuk: login, config change, data upload, data export.
- Excel upload virus scan (ClamAV atau equivalent).

### 18.4 Availability

- Uptime target 99.5% business hours (Mon-Fri 6 AM - 10 PM WIB).
- Planned maintenance window: Minggu 00:00-04:00 WIB.
- Backup PostgreSQL: full backup daily, WAL archiving for point-in-time recovery.
- Disaster recovery: RTO 4 jam, RPO 1 jam.

### 18.5 Maintainability

- Code coverage backend ≥ 70% untuk critical paths.
- API documentation auto-generated (OpenAPI/Swagger).
- Database schema versioned via Flyway atau Liquibase.
- Logging structured (JSON) dengan correlation ID per request.
- Monitoring via Prometheus + Grafana atau equivalent existing.

### 18.6 Usability

- Dashboard dapat diakses tanpa training untuk BOD.
- Loading state, error state, empty state harus informatif dan actionable.
- Touch-friendly untuk tablet/mobile (minimum tap target 44x44px).
- Color blind safe palette untuk chart (test dengan simulator).

---

## 19. Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| Oracle 11g end-of-support — security patch tidak tersedia | High | Medium | Decoupling ke PostgreSQL untuk dashboard. Roadmap upgrade Oracle terpisah dari project ini. |
| ETL job overload ke Oracle, ganggu transaksi production | Medium | High | Aggregate via stored procedure (bukan raw SELECT), schedule di off-peak hours, monitor query plan. |
| Inconsistency angka antara dashboard dan ERP report | Medium | High | Single fact table dengan audit trail (`FM_SOURCE_ID`). Compute logic terdokumentasi. Reconciliation report bulanan. |
| BOD tidak adopt — masih request manual report | Medium | High | Co-design dengan CFO/CEO di awal. Training session. Mobile-friendly. Quick win dashboard di MVP. |
| Excel upload data conflict dengan ETL data | Medium | Medium | UPSERT logic dengan last-write-wins + audit trail. Warning UI saat overwrite. |
| Performance degradasi seiring data growth | Medium | Medium | Partition fact table by year, archive data > 3 tahun, monitor query plan, scale Postgres vertically/read replica. |
| pgvector belum dipakai di Phase 1 — over-engineered | Low | Low | Schema-ready saja, no runtime cost. Avoids migration pain di Phase 3. |
| Security: JWT compromise / replay attack | Low | High | Short expiry (8 jam), refresh token rotation, audit log, anomaly detection. |
| Team knowledge gap di Go atau ECharts | Medium | Medium | Training di awal sprint, pair programming, code review wajib. |

---

## 20. Success Metrics

### 20.1 Adoption Metrics

- Number of unique users per week (target: 25 dalam 30 hari, 50 dalam 60 hari).
- Number of dashboard views per week (target: 200 dalam 30 hari).
- Active BOD members (target: 100% dari BOD aktif mengakses minimal 1x per minggu dalam 60 hari).

### 20.2 Performance Metrics

- Dashboard load time P95 < 2 detik (target capai dalam 30 hari go-live).
- ETL success rate ≥ 99% (max 1 failure per 100 runs).
- API error rate < 0.5%.

### 20.3 Business Impact Metrics

- Reduction in manual report requests ke Finance team (baseline measurement vs 90-day post-launch).
- Time-to-decision metric: survey BOD setelah 60 hari — apakah decision making lebih cepat?
- Number of new dashboards added in 6 months (target: 10+ dengan setup < 4 jam each).

### 20.4 Quality Metrics

- Bug count post go-live: < 5 critical, < 20 major dalam 30 hari pertama.
- Excel upload validation accuracy: > 99% invalid data ditolak di staging, < 1% slip through.
- Data reconciliation accuracy: 100% match dengan source ERP untuk audit period.

---

## 21. Appendix

### 21.1 Sample Source Data Reference

Berdasarkan file `ebitda_dashboard.xlsx` yang dilampirkan, struktur source data mengikuti shape:

| Field | Type | Sample |
|-------|------|--------|
| MFMG_TYPE | String | MIS |
| MFMG_GROUP_1 | String | EBITDA / NET PROFIT |
| MFMG_GROUP_2 | String | INCOME / PRODUCTION COST / MANPOWER / dst (11 categories) |
| MFMG_GROUP_3 | String | LOCAL SALES / CHIPS COST / dst (detailed line items) |
| MFMG_GROUP_1_ORDER | Int | 1 |
| MFMG_GROUP_2_ORDER | Int | 1-11 |
| MFMG_GROUP_3_ORDER | Int | 1-N per group_2 |
| PERIODE | String | 202301 (YYYYMM) |
| VALUE | Decimal | 1234567.89 (signed) |

### 21.2 EBITDA Component Reference (from sample data)

GROUP_2 untuk EBITDA module:

- **INCOME** (LOCAL SALES, EXPORT SALES, POP CORN SALES, SALES RETURN, OTHER INCOME)
- **PRODUCTION COST** (CHIPS COST, RECYCLE, BOUGHT OUT POY/FDY, STOCK ADJUSTMENT)
- **COLOR CONSUMPTION (SPG)** (MASTERBATCH BLACK/COLOUR, OWN MASTERBATCH)
- **MATERIAL CONSUMPTION** (SPANDEX, CHEMICALS, SUPERBA, PAPER TUBES, SPARES)
- **R&D MASTERBATCH PRODUCTION** (DYES, PIGMENTS, WIP VALUATION)
- **ENERGY COST** (POWER PLN, MASTERBATCH POWER)
- **PROCUREMENT COST**
- **MANPOWER**
- **OVERHEADS**
- **SELLING COST**
- **BAD DEBT EXP**

### 21.3 Chart Config Examples for All Phase 1 Dashboards

**EBITDA Dashboard Config:**

```json
{
  "type": "waterfall",
  "positive_color": "#1d9e75",
  "negative_color": "#a32d2d",
  "total_color": "#534ab7",
  "x_axis_field": "group_2",
  "y_axis_field": "display_value",
  "show_total_bar": true,
  "number_format": "currency_thousands",
  "decimals": 1,
  "drill_to": "group_3",
  "secondary_chart": {
    "type": "kpi_card",
    "metrics": ["current", "mom", "yoy"]
  }
}
```

**Net Profit Dashboard Config:**

```json
{
  "type": "bar",
  "primary_color": "#1F4E79",
  "comparison_color": "#85B7EB",
  "x_axis_field": "periode",
  "y_axis_field": "display_value",
  "show_data_labels": true,
  "number_format": "currency_thousands",
  "trend_line": true,
  "secondary_chart": {
    "type": "line",
    "overlay": true,
    "series": "yoy_previous"
  }
}
```

### 21.4 Glossary

| Term | Definition |
|------|------------|
| BOD | Board of Directors — primary user of dashboard |
| MIS | Management Information System — Finance reporting module |
| EBITDA | Earnings Before Interest, Tax, Depreciation, Amortization |
| ETL | Extract, Transform, Load — data pipeline process |
| RBAC | Role-Based Access Control |
| MV | Materialized View — pre-computed query result |
| UPSERT | INSERT or UPDATE on conflict (PostgreSQL ON CONFLICT clause) |
| MoM/QoQ/YoY | Month/Quarter/Year over Year comparison |
| YTD | Year to Date |
| R12 | Rolling 12 Months |
| JWT | JSON Web Token — authentication token format |
| pgvector | PostgreSQL extension for vector similarity search (AI/RAG) |
| RAG | Retrieval Augmented Generation — LLM with grounded context |

### 21.5 Reference Architecture Diagram

Arsitektur sistem mengikuti pola layered berikut:

- **Sources Layer**: Oracle ERP, Oracle Laravel, Excel files
- **Transformation Layer**: Oracle stored procedures + Python ETL worker
- **Warehouse Layer**: PostgreSQL dengan `FACT_METRIC`, materialized views, `dashboard_config`
- **Serving Layer**: Redis cache + Go API + Auth service
- **Client Layer**: React dashboard viewer + admin panel (responsive)
- **Future Layer (Phase 3)**: pgvector embeddings + LLM Q&A endpoint

---

*End of Document*
