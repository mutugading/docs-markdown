# TQM — DECISIONS

Keputusan teknis untuk membangun PRD TQM di goapps. Keputusan bisnis user (K1–K9) ada di
`PRD.md §3a`, jangan disalin ke sini.
Status: `PROPOSED` (usulan, perlu konfirmasi) · `ACCEPTED` · `SUPERSEDED by TQ-xx`.
Semua TQ-01..TQ-07 disepakati bersama pemilik proyek pada 2026-11-19.

### TQ-01 Modul di dalam service `finance`, prefix `tqm_sample`
Status: ACCEPTED
Menurut PM, TQM terlalu kecil untuk service sendiri, jadi TQM dibangun sebagai modul di
`goapps-backend/services/finance` dengan DB `finance_db`. Tidak ada service, DB, port, atau manifest k8s baru.
Semua nama memakai prefix `tqm_sample` / `tqmsample` supaya aplikasi TQM lain di masa depan bisa mendapat
prefix `tqm_<app>` sendiri:
- Tabel `tqm_sample_*`. Migrasi melanjutkan penomoran migrasi finance.
- Go `internal/{domain,application,infrastructure/postgres,delivery/grpc}/tqmsample*`.
- Proto `goapps-shared-proto/finance/v1/tqm_sample_*.proto` (package `finance.v1`).
- Role IAM `TQM_SAMPLE_*` (huruf besar, sesuai `chk_role_code_format`).
- Permission `finance.tqmsample.<entity>.<action>` (4 segmen tanpa underscore, sesuai `chk_permission_code_format`).
- Menu TQM tetap menu top-level sendiri, bukan di bawah Finance, karena penggunanya (TQM, MB, MKT) bukan user finance.
Yang ditolak: service `tqm` sendiri (dibatalkan PM) dan berbagi tabel `cost_product_request` (lihat TQ-02).

### TQ-02 Tabel `tqm_sample_*` sendiri, tidak berbagi tabel CPR
Status: ACCEPTED
`tqm_sample_request` (kolom bersama: id, type `COLOR|SAMPLE`, no, status, revision, customer, parent_id,
source_request_id, requester, approver, audit) + `tqm_sample_request_color` (spek customer vs MGT, 1:1) +
`tqm_sample_request_advice` (header kirim tipe SAMPLE, 1:1) + `tqm_sample_advice_item` (+ properti teknis K7) +
`tqm_sample_lab_trial` / `tqm_sample_lab_result` (varian) + `tqm_sample_shipment` (+ `_item`) +
`tqm_sample_feedback` (per item/varian) + `tqm_sample_request_history` (status + snapshot JSON per revisi) +
`tqm_sample_attachment`. Master: `tqm_sample_lookup`, `tqm_sample_color`, `tqm_sample_supplier`.
Tabel tipe SAMPLE memakai kata `advice` (dari Sample Market Advice) supaya tidak muncul `tqm_sample_sample_*`.

Alasan tidak memakai `cost_product_request`: status CPR dikunci CHECK + state machine sendiri; `cpr_title` dan
`cpr_product_classification` NOT NULL; nomor CPR per bulan (`REQ-YYYYMM-NNNN`), TQM per tahun (K5); setiap query
CPR harus menyaring baris TQM; dari 8 tabel anak CPR hanya attachment dan status history yang berguna, sisanya
(spec, routing, fill task, level assignment) akan selalu kosong; satu request TQM bisa melahirkan beberapa CPR
(satu per item, TQ-06). Tabel anak CPR (`cost_attachment`, `cost_request_status_history`) tidak dipakai bersama.
Penyimpanan file lampiran memakai kode storage finance yang sudah ada.

### TQ-03 Penomoran lewat tabel counter
Status: ACCEPTED
`tqm_sample_doc_counter(doc_kind, period_key, last_no)` dengan PK (doc_kind, period_key). Nomor diambil di
transaksi yang sama dengan perubahan status: `INSERT ... ON CONFLICT DO UPDATE SET last_no = last_no + 1
RETURNING last_no`. `doc_kind`: `MGT_PD` (COLOR), `SMA` (SAMPLE), `TQM_SUPP` (kirim ke lab), `TQM_RECV`
(hasil lab), `TQM_MKT` (kirim ke marketing). `period_key` = tahun. `TQM/NNN/MM/YYYY` memakai bulan
romawi sesuai legacy (`TQM/770/XI/2026`), tetapi counternya tetap per tahun. Nomor tidak dipakai
ulang. Seed awal dari Oracle saat cut-over (K8, Q042). Terpisah dari `cost_request_no_counter` CPR karena
formatnya berbeda. Yang ditolak: Postgres SEQUENCE per tahun (sulit di-seed dan di-reset per tahun) dan
`MAX()+1` (penyebab nomor ganda di legacy, L4).

### TQ-04 Approval = cek role di dalam modul TQM
Status: ACCEPTED
Approve/Reject hanya boleh untuk user ber-role `TQM_SAMPLE_APPROVER` (Head of TQM, K6), dicek lewat permission
`finance.tqmsample.request.approve`. Setiap keputusan dicatat di `tqm_sample_request_history` + audit log.
Yang ditolak: IAM workflow. Client workflow CPR (`iamclient/workflow_client.go`) masih stub no-op, jadi tidak ada
pola yang bisa ditiru, dan satu level approval tidak butuh engine workflow. Kalau nanti butuh multi-level,
buat TQ baru.

### TQ-05 Customer dari salinan master customer di finance; shade sebagai teks
Status: ACCEPTED
**Customer:** finance mendapat tabel customer sendiri yang disinkron dari Oracle `OM_CUSTOMER`, meniru sync
customer di ppc (`ppc/internal/infrastructure/oracle/customer_source.go`, ±210 baris) dan pola sync shade
finance. Baris MANUAL tidak ditimpa sync. Dengan begitu finance tidak bergantung pada ppc (task Q004).
Yang disimpan di TQM: `customer_code` + snapshot nama. Prospek yang belum ada di master: `customer_code` kosong +
nama teks (PRD F1.3).

**Shade:** disimpan sebagai `shade_code` + `shade_name` teks, dengan `shade_id` opsional kalau user memilih shade
dari master `cost_erp_shade`. Sama dengan CPR (`cps_shade_id` atau `cps_shade_custom_text`). Shade baru hasil lab
cukup diketik dan tidak perlu menunggu finance. TQM **tidak** membuat shade di master. Shade masuk master lewat
jalur normal (dibuat di Orion, lalu sync) saat masuk produksi, supaya master costing tidak penuh shade trial yang
tidak pernah dipesan.

### TQ-06 Ajukan Costing = use case CPR dipanggil langsung dalam satu transaksi
Status: ACCEPTED
Karena satu service, TQM memanggil use case create CPR di finance langsung (bukan gRPC), dengan pengisi feedback
(K9) sebagai requester, di transaksi yang sama dengan penyimpanan tautan. `tqm_sample_feedback.cpr_request_id` =
FK ke `cost_product_request`. Ada unique constraint satu CPR aktif per item. Shade dikirim sebagai `shade_id` kalau
ada, kalau tidak sebagai teks (TQ-05). Status CPR dibaca lewat join saat tampil, tidak disalin. Perilaku CPR tidak
diubah. F6.2/F6.3 di sisi CPR menjadi task terpisah (Q034) yang dikoordinasikan dengan pemilik CPR.

### TQ-07 Impor master dari Oracle lewat CLI sekali jalan
Status: ACCEPTED
Akun Oracle: **MGTDAT**, sama seperti sync Oracle finance yang sudah ada (blok `oracle:` di `config.yaml`,
kredensial lewat env `ORACLE_USER`/`ORACLE_PASSWORD`). CLI hanya menjalankan SELECT.
CLI `services/finance/cmd/import-tqm-sample-master` (pola sama dengan `cmd/sync-mb-active`) membaca `MGTDAT.MGT_CDM_COLOR_MASTER`, `MGT_CDM_BASE_COLOR`,
`MGT_CDM_SUPPLIER`, dan tabel lookup kecil (L1) memakai akun Oracle read-only. Teks bebas legacy
dikumpulkan menjadi CSV usulan pemetaan lookup untuk direview TQM, tanpa menulis otomatis ke lookup.
