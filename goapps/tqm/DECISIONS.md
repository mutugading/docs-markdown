# TQM — DECISIONS

Keputusan teknis untuk membangun PRD TQM di goapps. Keputusan bisnis user (K1–K9) ada di
`PRD.md §3a`, jangan disalin ke sini.
Status: `PROPOSED` (usulan, perlu konfirmasi di Q000) · `ACCEPTED` · `SUPERSEDED by TQ-xx`.

### TQ-01 Service `tqm` baru dengan DB Postgres sendiri
Status: PROPOSED
Service `goapps-backend/services/tqm`, database `tqm_db`, dengan struktur yang meniru `ppc`
(domain/application/infrastructure/delivery). Port dev diusulkan memakai nomor berikutnya yang kosong
(gRPC 50054+, HTTP 8083+, Postgres 5438), dan di Q001 dicek dulu terhadap semua `config.yaml` dan
`docker-compose.yml` supaya tidak bentrok. Dasar: K2.

### TQ-02 Satu tabel request + tabel detail per tipe
Status: PROPOSED
`tqm_request` (kolom bersama: id, type, no, status, revision, customer, parent_id, source_request_id,
requester, approver, audit) + `tqm_request_color` (spek customer vs MGT, 1:1) + `tqm_request_sample`
(header kirim, 1:1) + `tqm_sample_item` (+ kolom properti teknis K7) + `tqm_lab_trial` /
`tqm_lab_result` (varian) + `tqm_shipment` (+ item) + `tqm_feedback` (per item/varian) +
`tqm_request_history` (status + snapshot JSON per revisi). Alasannya: daftar, status, approval, dan
laporan cukup memakai satu tabel tanpa UNION. Yang ditolak: tabel terpisah per tipe (PRD §3).

### TQ-03 Penomoran lewat tabel counter
Status: PROPOSED
`tqm_doc_counter(doc_kind, period_key, last_no)` dengan PK (doc_kind, period_key). Nomor diambil di
transaksi yang sama dengan perubahan status: `INSERT ... ON CONFLICT DO UPDATE SET last_no = last_no + 1
RETURNING last_no`. `doc_kind`: `MGT_PD` (COLOR), `SMA` (SAMPLE), `TQM_SUPP` (kirim ke lab), `TQM_RECV`
(hasil lab), `TQM_MKT` (kirim ke marketing). `period_key` = tahun. `TQM/NNN/MM/YYYY` memakai bulan
romawi sesuai legacy (`TQM/770/XI/2026`), tetapi counternya tetap per tahun. Nomor tidak dipakai
ulang. Seed awal dari Oracle saat cut-over (K8, Q042). Yang ditolak: Postgres SEQUENCE per tahun
(sulit di-seed dan di-reset per tahun) dan `MAX()+1` (penyebab nomor ganda di legacy, L4).

### TQ-04 Approval lewat IAM workflow, seperti CPR
Status: PROPOSED
Saat submit, service membuat instance workflow IAM (`iam/v1/workflow.proto`) dan menyimpan `wfl_instance_id`.
Approver = role `tqm.approver` (Head of TQM, K6). Kalau workflow IAM terlalu berat untuk satu level,
alternatifnya cek role langsung di service. Diputuskan di Q000 setelah membaca workflow CPR.

### TQ-05 Customer dari master customer `ppc`, shade dari master shade `finance`
Status: PROPOSED
Customer dipilih lewat gRPC `ppc.v1` ListCustomers (sumber OM_CUSTOMER). Yang disimpan di TQM:
`customer_code` + snapshot nama, supaya dokumen lama tidak berubah kalau master berubah. Prospek yang
belum ada di master: `customer_code` kosong + nama teks (PRD F1.3). Shade untuk COLOR/varian lab mengacu
ke `finance.v1` Shade (`shade_code` + snapshot nama). Shade baru dari hasil lab (PRD F3.3) dibuat lewat
CreateShade (MANUAL), dengan syarat pemilik finance setuju; kalau tidak, cukup disimpan di TQM dulu.

### TQ-06 Ajukan Costing = gRPC CreateCostProductRequest, idempoten per item
Status: PROPOSED
TQM memanggil `finance.v1.CostProductRequestService/CreateCostProductRequest` dengan identitas user
pengisi feedback (K9), lalu menyimpan `cpr_request_id` + `cpr_request_no` di `tqm_feedback`. Ada unique
constraint satu CPR aktif per item. Kalau panggilan gagal, tidak ada yang tersimpan dan user bisa
mencoba lagi. Status CPR dibaca saat tampil (GetCostProductRequest), tidak disalin. Tidak ada perubahan
pada finance di fase 3a. F6.2/F6.3 di sisi CPR menjadi task terpisah.

### TQ-07 Impor master dari Oracle lewat CLI sekali jalan
Status: PROPOSED
Perintah `tqm import-master` membaca `MGTDAT.MGT_CDM_COLOR_MASTER`, `MGT_CDM_BASE_COLOR`,
`MGT_CDM_SUPPLIER`, dan tabel lookup kecil (L1) memakai akun Oracle read-only. Teks bebas legacy
dikumpulkan menjadi CSV usulan pemetaan lookup untuk direview TQM, tanpa menulis otomatis ke lookup.
Akun yang dipakai dikonfirmasi di Q000.
