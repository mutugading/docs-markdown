# PRD — Custom Report Module (Phase 1)

| | |
|---|---|
| **Versi** | 1.0 |
| **Tanggal** | 12 September 2026 |
| **Phase** | 1 dari 2 — *Report Customize* |
| **Status** | Draft untuk review |
| **Platform** | Laravel + Oracle 11g |

---

## 1. Latar Belakang

Permintaan report baru dari user datang terus-menerus dengan volume tinggi. Pola kerja saat ini mengharuskan developer membuat halaman baru, controller, view, dan query untuk setiap report — sebuah proses yang memakan waktu dan tidak scalable. Akibatnya antrian permintaan report menumpuk dan menjadi bottleneck bagi tim.

Module ini memindahkan pembuatan report dari *coding* menjadi *konfigurasi*. Developer cukup mendefinisikan tiga hal: query SQL, parameter input, dan hak akses user. Sisanya — form input, tampilan tabel, dan export Excel — ditangani oleh engine secara generik.

---

## 2. Tujuan

| No | Tujuan | Ukuran Keberhasilan |
|---|---|---|
| G1 | Menghilangkan kebutuhan coding per report baru | Report baru dibuat tanpa deploy kode |
| G2 | Mempercepat delivery permintaan report | Dari hitungan hari menjadi hitungan jam |
| G3 | Menyeragamkan tampilan dan format output report | Satu format tabular dan satu format Excel untuk semua report |
| G4 | Memberikan kontrol akses report per user/role | Report hanya terlihat oleh yang berhak |

---

## 3. Scope

### 3.1 In Scope (Phase 1)

- Manajemen Report Group (kategori: Finance, HR, Attendance, Procurement, dll)
- Manajemen Report Definition — nama, deskripsi, query SQL, konfigurasi eksekusi
- Manajemen Parameter — 8 tipe input, termasuk dropdown yang bersumber dari query
- Form parameter yang di-generate otomatis dari definisi
- Tampilan hasil report di web dalam bentuk tabel dengan search, sort, dan pagination
- Download hasil ke file Excel (.xlsx) dengan formatting profesional
- Kontrol akses berbasis `spatie/laravel-permission`
- Admin UI untuk developer (input query dan parameter)

### 3.2 Out of Scope (ditunda ke Phase 2)

- Email alert terjadwal dan seluruh infrastruktur pendukungnya (scheduler, queue job, recipient management, email renderer)
- Audit log eksekusi report
- Preview / test run query di Admin UI
- Export ke format selain Excel (PDF, CSV)
- Chart atau visualisasi grafis
- Report dengan struktur non-tabular (pivot, crosstab, master-detail)

---

## 4. User Roles

| Role | Kemampuan |
|---|---|
| **Developer** | Akses penuh Admin UI: create/edit/delete report group, report definition, parameter, dan set status report. Satu-satunya role yang bisa menulis SQL. |
| **End User** | Melihat daftar report yang di-assign ke role-nya, mengisi parameter, melihat hasil, dan download Excel. Tidak punya akses ke Admin UI. |

Penetapan permission report ke role dilakukan melalui UI Spatie yang sudah ada — tidak ada layar baru untuk ini.

---

## 5. Functional Requirements

### 5.1 Report Group Management

| ID | Requirement |
|---|---|
| FR-1.1 | Developer dapat membuat, mengubah, dan menonaktifkan Report Group |
| FR-1.2 | Report Group memiliki nama, icon, urutan tampil, dan status aktif |
| FR-1.3 | Report Group tidak dapat dihapus jika masih memiliki report di dalamnya |

### 5.2 Report Definition Management

| ID | Requirement |
|---|---|
| FR-2.1 | Developer dapat membuat Report Definition dengan memilih group, mengisi nama, deskripsi, dan query SQL |
| FR-2.2 | Query SQL divalidasi saat disimpan — hanya statement `SELECT` yang diterima |
| FR-2.3 | Query yang mengandung keyword DML/DDL (`INSERT`, `UPDATE`, `DELETE`, `DROP`, `TRUNCATE`, `MERGE`, `ALTER`, `CREATE`, `EXECUTE`, `CALL`, `GRANT`, `REVOKE`, `BEGIN`, `DECLARE`, `DBMS_`, `UTL_`, `SYS.`) ditolak dengan pesan error yang jelas |
| FR-2.4 | Validasi dilakukan setelah SQL comment (`--` dan `/* */`) dihapus, agar keyword tidak bisa disembunyikan di dalam komentar |
| FR-2.5 | Developer dapat mengatur `max_rows` (default 1000) dan `timeout_sec` (default 30) per report |
| FR-2.6 | Report memiliki status `draft`, `active`, atau `inactive`. Hanya report `active` yang tampil bagi end user |
| FR-2.7 | Saat report dibuat, sistem otomatis membuat Spatie permission `report.view.{id}` |
| FR-2.8 | Saat report dihapus, permission terkait ikut terhapus |

### 5.3 Parameter Management

| ID | Requirement |
|---|---|
| FR-3.1 | Developer dapat mendefinisikan parameter dengan `param_key`, label, tipe input, wajib/opsional, nilai default, dan urutan tampil |
| FR-3.2 | Sistem mendukung 8 tipe input: `text`, `number`, `date`, `date_range`, `dropdown_static`, `dropdown_query`, `multi_select_static`, `multi_select_query` |
| FR-3.3 | `param_key` harus unik dalam satu report |
| FR-3.4 | Untuk tipe `dropdown_query` dan `multi_select_query`, developer mengisi source query yang mengembalikan dua kolom: value dan label |
| FR-3.5 | Source query juga divalidasi DML seperti query utama, dan hasilnya dibatasi 500 baris |
| FR-3.6 | Untuk tipe `dropdown_static` dan `multi_select_static`, developer mengisi opsi dalam format JSON |
| FR-3.7 | Tipe `date_range` menghasilkan dua binding di query: `{param_key}_start` dan `{param_key}_end` |
| FR-3.8 | Tipe multi-select otomatis di-expand menjadi beberapa named binding untuk klausa `IN` |

### 5.4 Report Execution — End User

| ID | Requirement |
|---|---|
| FR-4.1 | End user melihat daftar report yang dikelompokkan per Report Group, terfilter sesuai permission yang dimiliki |
| FR-4.2 | Saat report dipilih, sistem menampilkan form parameter yang di-generate dari definisi parameter, terurut sesuai `display_order` |
| FR-4.3 | Parameter dengan `is_required = true` wajib diisi sebelum report dapat dijalankan |
| FR-4.4 | Dropdown yang bersumber dari query memuat opsinya saat form dibuka |
| FR-4.5 | Saat dijalankan, query dieksekusi dengan parameter yang diisi user melalui koneksi Oracle read-only |
| FR-4.6 | Hasil query dibatasi `max_rows` melalui wrapper `ROWNUM` di level database |
| FR-4.7 | Jika query mengembalikan nol baris, sistem menampilkan pesan "Tidak ada data yang sesuai dengan parameter yang dipilih" |
| FR-4.8 | Jika jumlah baris mencapai `max_rows`, sistem menampilkan peringatan bahwa hasil terpotong |

### 5.5 Web View

| ID | Requirement |
|---|---|
| FR-5.1 | Hasil report ditampilkan sebagai tabel dengan header dari nama kolom hasil query |
| FR-5.2 | Seluruh hasil dimuat sekaligus ke browser (dibatasi `max_rows`), pagination dilakukan client-side |
| FR-5.3 | User dapat melakukan pencarian teks bebas di seluruh kolom |
| FR-5.4 | User dapat mengurutkan tabel berdasarkan kolom mana pun, ascending maupun descending |
| FR-5.5 | Kolom numerik ditampilkan rata kanan dengan pemisah ribuan |
| FR-5.6 | Kolom tanggal ditampilkan dengan format `dd/MM/yyyy` |
| FR-5.7 | Deteksi tipe kolom menggunakan logika yang sama dengan generator Excel, agar tampilan web dan file Excel konsisten |

### 5.6 Excel Download

| ID | Requirement |
|---|---|
| FR-6.1 | User dapat mengunduh hasil report sebagai file `.xlsx` |
| FR-6.2 | Isi file mengikuti kondisi tabel yang sedang tampil — filter pencarian dan urutan sort yang aktif ikut terbawa |
| FR-6.3 | Browser mengirim dataset yang sedang tampil ke server, server melakukan generate file |
| FR-6.4 | Nama file mengikuti pola `{nama_report_slug}_{YYYYMMDD}_{HHmmss}.xlsx` |
| FR-6.5 | Nilai sel yang diawali `=`, `+`, `-`, atau `@` diberi prefix apostrof untuk mencegah formula injection di Excel |

---

## 6. Database Schema

Phase 1 membutuhkan **tiga tabel**. Seluruh kolom mengikuti konvensi prefix berbasis inisial nama tabel, sehingga nama kolom unik secara global dan query tidak memerlukan alias tabel.

| Tabel | Prefix | Fungsi |
|---|---|---|
| `RPT_GROUPS` | `RG_` | Kategori report |
| `RPT_DEFINITIONS` | `RD_` | Definisi report dan query |
| `RPT_PARAMETERS` | `RP_` | Parameter input per report |

### 6.1 RPT_GROUPS

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `RG_ID` | NUMBER(10) | N | Primary key, sequence + trigger |
| `RG_NAME` | VARCHAR2(100) | N | Nama group |
| `RG_ICON` | VARCHAR2(50) | Y | Nama icon Tabler |
| `RG_DISPLAY_ORDER` | NUMBER(5) | N | Default 0 |
| `RG_IS_ACTIVE` | NUMBER(1) | N | Default 1. Check constraint 0/1 |
| `RG_CREATED_AT` | TIMESTAMP | N | Default CURRENT_TIMESTAMP |
| `RG_UPDATED_AT` | TIMESTAMP | Y | |

### 6.2 RPT_DEFINITIONS

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `RD_ID` | NUMBER(10) | N | Primary key |
| `RD_GROUP_ID` | NUMBER(10) | N | FK → `RG_ID`, on delete restrict |
| `RD_NAME` | VARCHAR2(200) | N | Nama report |
| `RD_DESCRIPTION` | CLOB | Y | |
| `RD_QUERY_TEXT` | CLOB | N | Raw SQL, hanya SELECT, named binding `:param_key` |
| `RD_MAX_ROWS` | NUMBER(10) | N | Default 1000 |
| `RD_TIMEOUT_SEC` | NUMBER(5) | N | Default 30 |
| `RD_STATUS` | VARCHAR2(20) | N | Check: `draft` / `active` / `inactive` |
| `RD_CREATED_BY` | NUMBER(10) | N | FK ke tabel users |
| `RD_CREATED_AT` | TIMESTAMP | N | |
| `RD_UPDATED_AT` | TIMESTAMP | Y | |

Index: `RD_GROUP_ID`, `RD_STATUS`

### 6.3 RPT_PARAMETERS

| Kolom | Tipe | Null | Keterangan |
|---|---|---|---|
| `RP_ID` | NUMBER(10) | N | Primary key |
| `RP_REPORT_ID` | NUMBER(10) | N | FK → `RD_ID`, on delete cascade |
| `RP_PARAM_KEY` | VARCHAR2(50) | N | Harus cocok dengan `:param_key` di query |
| `RP_LABEL` | VARCHAR2(100) | N | Label di form |
| `RP_INPUT_TYPE` | VARCHAR2(30) | N | Check constraint 8 nilai |
| `RP_IS_REQUIRED` | NUMBER(1) | N | Default 0 |
| `RP_DEFAULT_VALUE` | VARCHAR2(500) | Y | |
| `RP_SOURCE_QUERY` | CLOB | Y | Untuk tipe `*_query` |
| `RP_STATIC_OPTIONS` | CLOB | Y | JSON array untuk tipe `*_static` |
| `RP_DISPLAY_ORDER` | NUMBER(5) | N | Default 0 |
| `RP_CREATED_AT` | TIMESTAMP | N | |
| `RP_UPDATED_AT` | TIMESTAMP | Y | |

Unique: (`RP_REPORT_ID`, `RP_PARAM_KEY`) — Index: `RP_REPORT_ID`

### 6.4 Catatan Oracle 11g

Oracle 11g tidak memiliki tipe data JSON native, sehingga `RP_STATIC_OPTIONS` disimpan sebagai CLOB. Di sisi Eloquent, kolom ini menggunakan custom cast `App\Casts\ClobJson` — bukan cast `'array'` bawaan — karena driver `yajra/laravel-oci8` dalam konfigurasi tertentu mengembalikan CLOB sebagai objek OCI-Lob, bukan string PHP.

Auto-increment menggunakan kombinasi SEQUENCE dan BEFORE INSERT TRIGGER, karena IDENTITY column baru tersedia mulai Oracle 12c.

---

## 7. Parameter System

### 7.1 Tipe Input dan Binding

| Tipe | UI | Binding ke Oracle |
|---|---|---|
| `text` | Text input | `:param_key` |
| `number` | Number input | `:param_key` |
| `date` | Date picker | `:param_key` |
| `date_range` | Dua date picker | `:param_key_start` dan `:param_key_end` |
| `dropdown_static` | Select, opsi dari JSON | `:param_key` |
| `dropdown_query` | Select, opsi dari source query | `:param_key` |
| `multi_select_static` | Multi-select, opsi dari JSON | `IN (:key_0, :key_1, ...)` |
| `multi_select_query` | Multi-select, opsi dari source query | `IN (:key_0, :key_1, ...)` |

### 7.2 Multi-select Expansion

Developer menulis satu placeholder di query:

```sql
WHERE DEPT_ID IN (:dept_id)
```

Ketika user memilih nilai `10`, `20`, dan `30`, sistem mengubahnya menjadi:

```sql
WHERE DEPT_ID IN (:dept_id_0, :dept_id_1, :dept_id_2)
```

dengan binding `dept_id_0 = 10`, `dept_id_1 = 20`, `dept_id_2 = 30`.

Jika user tidak memilih apa pun pada parameter opsional, sistem mengikat satu nilai sentinel yang tidak akan cocok dengan data apa pun, sehingga query tetap valid dan mengembalikan nol baris.

### 7.3 Batasan

Oracle membatasi klausa `IN` maksimal 1000 elemen (ORA-01795). Untuk multi-select dengan opsi yang sangat banyak, developer perlu merancang ulang query menggunakan subquery atau temporary table.

---

## 8. Arsitektur Teknis

### 8.1 Alur Eksekusi

```
End User
   │
   ├─ Pilih report (terfilter Spatie permission)
   ├─ Isi form parameter (di-generate dari RPT_PARAMETERS)
   └─ Submit
         │
         ▼
   ReportQueryService
         ├─ guardDml()      — tolak DML/DDL setelah strip comment
         ├─ buildQuery()    — expand multi-select, wrap ROWNUM
         └─ runQuery()      — eksekusi via koneksi oracle_readonly
         │
         ▼
      Dataset (array assoc)
         │
         ├─────────────► Web View (client-side table: search, sort, paginate)
         │                        │
         │                        └─ klik Download
         │                                 │
         │                    POST dataset terfilter ke server
         │                                 ▼
         └─────────────────────────► ReportExport (maatwebsite) → .xlsx
```

### 8.2 Komponen

| Komponen | Tanggung Jawab |
|---|---|
| `ReportQueryService` | Validasi DML, expansion multi-select, ROWNUM wrapping, eksekusi query, fetch opsi dropdown |
| `ParameterFormBuilder` | Membangun schema form dari `RPT_PARAMETERS`, memuat opsi dropdown query |
| `ReportExcelService` | Menerima dataset dan nama report, menghasilkan file Excel |
| `ReportExport` | Export class maatwebsite dengan seluruh formatting |
| `RptDefinitionObserver` | Sinkronisasi Spatie permission saat report dibuat/dihapus |

### 8.3 Koneksi Database

Query report dieksekusi melalui koneksi Oracle terpisah bernama `oracle_readonly`, menggunakan user database yang hanya memiliki privilege `SELECT`. Koneksi ini berbeda dari koneksi utama aplikasi.

```sql
CREATE USER rpt_readonly IDENTIFIED BY "<password>";
GRANT CREATE SESSION TO rpt_readonly;
GRANT SELECT ON <schema>.<table> TO rpt_readonly;
```

### 8.4 Format Output Excel

| Elemen | Spesifikasi |
|---|---|
| Font | Arial |
| Header row | Size 11, bold, teks putih, background `#2C3E50` |
| Data row | Size 10, warna selang-seling `#FFFFFF` dan `#F2F3F4` |
| Border | Thin, `#DEE2E6` |
| Border bawah header | Medium, `#1A252F` |
| Freeze pane | Baris 1 |
| Auto-filter | Aktif pada baris header |
| Lebar kolom | Otomatis menyesuaikan isi |
| Format angka | `#,##0` untuk integer, `#,##0.00` untuk desimal |
| Print setup | Landscape, fit to 1 page wide |
| Header/footer cetak | Nama report dan tanggal / nomor halaman |

---

## 9. Non-Functional Requirements

### 9.1 Keamanan

| ID | Requirement |
|---|---|
| NFR-1.1 | Query dieksekusi dengan user Oracle yang hanya memiliki privilege SELECT |
| NFR-1.2 | Seluruh parameter diikat sebagai named binding — tidak ada string concatenation nilai parameter ke dalam query |
| NFR-1.3 | Admin UI hanya dapat diakses oleh role developer |
| NFR-1.4 | Pesan error query yang detail hanya ditampilkan kepada developer; end user menerima pesan generik tanpa detail struktur database |
| NFR-1.5 | Nilai sel yang berpotensi diinterpretasi sebagai formula Excel disanitasi sebelum ditulis ke file |

### 9.2 Performa

| ID | Requirement |
|---|---|
| NFR-2.1 | Hasil query dibatasi `max_rows` di level database melalui `ROWNUM`, bukan di level aplikasi |
| NFR-2.2 | Opsi dropdown dari source query dibatasi 500 baris |
| NFR-2.3 | Query yang melebihi `timeout_sec` dihentikan dan dilaporkan sebagai kegagalan |
| NFR-2.4 | Report dengan hasil mendekati 1000 baris harus tetap responsif di web view |

### 9.3 Maintainability

| ID | Requirement |
|---|---|
| NFR-3.1 | Skema database mengikuti konvensi prefix kolom yang berlaku di organisasi |
| NFR-3.2 | Penambahan tipe input parameter baru tidak memerlukan perubahan pada `ReportQueryService` |
| NFR-3.3 | Struktur tabel Phase 1 tidak menghalangi penambahan tabel alert pada Phase 2 |

---

## 10. Acceptance Criteria

Phase 1 dinyatakan selesai ketika seluruh kriteria berikut terpenuhi:

1. Developer dapat membuat report baru lengkap — group, definisi, query, dan parameter — tanpa melakukan deployment kode.
2. Query yang mengandung statement DML ditolak saat disimpan, termasuk ketika keyword disembunyikan di dalam SQL comment.
3. Form parameter ter-generate otomatis dan menampilkan seluruh 8 tipe input dengan benar, termasuk dropdown yang opsinya berasal dari query.
4. Parameter `date_range` menghasilkan dua date picker dan mengikat dua binding terpisah ke query.
5. Parameter multi-select dengan tiga nilai terpilih menghasilkan klausa `IN` dengan tiga named binding.
6. End user hanya melihat report yang permission-nya telah di-assign ke role miliknya.
7. Hasil report tampil sebagai tabel yang dapat dicari, diurutkan, dan dipaginasi tanpa request tambahan ke server.
8. File Excel yang diunduh berisi data sesuai filter dan urutan yang sedang aktif di web view.
9. File Excel memiliki seluruh formatting sesuai spesifikasi bagian 8.4.
10. Report yang mengembalikan nol baris menampilkan pesan yang sesuai, bukan tabel kosong tanpa keterangan.
11. Report yang hasilnya terpotong oleh `max_rows` menampilkan peringatan kepada user.

---

## 11. Dependencies

| Komponen | Kebutuhan |
|---|---|
| `maatwebsite/excel` | Generate file Excel |
| `spatie/laravel-permission` | Kontrol akses report |
| `yajra/laravel-oci8` | Driver Oracle untuk Laravel |
| Oracle user `rpt_readonly` | Harus dibuat oleh DBA sebelum development dimulai |
| Library tabel client-side | Untuk search, sort, dan pagination di browser |

---

## 12. Rencana Phase 2

Phase 2 menambahkan kemampuan email alert terjadwal di atas fondasi Phase 1. Report yang sama dapat dijadwalkan untuk dieksekusi otomatis dan hasilnya dikirim ke daftar penerima.

Perbedaan utama dengan Phase 1 terletak pada sumber parameter: pada report manual parameter diisi user saat runtime, sedangkan pada alert parameter didefinisikan sebagai nilai tetap.

Cakupan Phase 2 mencakup empat tabel tambahan untuk konfigurasi alert, nilai parameter tetap, daftar penerima, dan log eksekusi. Di sisi aplikasi ditambahkan scheduler berbasis cron expression, queue job untuk eksekusi, serta email renderer dengan dua mode tampilan — tabular sederhana dan styled dengan grouping, subtotal, dan grand total.

Struktur Phase 1 sudah dirancang agar penambahan ini bersifat aditif: tidak ada perubahan pada tiga tabel yang ada, dan tabel alert cukup melakukan foreign key ke `RD_ID`.
