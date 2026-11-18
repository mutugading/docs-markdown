# Usulan Pemetaan Role E-Filing — dari 72 Kombinasi Akses Legacy

> Bahan diskusi dengan pemilik dokumen, 2026-10-14. Melengkapi PRD FR-02 (BR-6 s.d. BR-8).
> Sumber: `MGTAPPS.EFILL_USER_ACCESS` (113 user, 1.843 baris, 72 kombinasi), `EFILL_ADMIN_USER` (12),
> dan `EFILL_LOG_ACCESS` 12 bulan terakhir (view per jenis dan berkas yang di-*file* per user).
> Data dibaca dari DB development 192.168.0.7 (salinan produksi). **Belum ada yang diterapkan.**

---

## 1. Ringkasan

| | Legacy | Usulan |
|---|---|---|
| Unit pemberian akses | 1.843 baris user × jenis | **8 role Viewer + 1 komposit + 7 role Filer**, dan **51 grant langsung** untuk 22 user aktif |
| User yang dibawa | 113 | **49 user aktif** (berkegiatan 12 bulan terakhir); 64 user non-aktif tidak dibawa, minta ulang lewat atasan |
| Pemisahan lihat vs upload | tidak ada | role **Viewer** per keluarga + kemampuan **Filer** (upload) terpisah |
| Admin | 12 user `EFILL_ADMIN_USER` | 2–3 user `efiling-admin`; sisanya cukup role Viewer |

Hasil pengujian pada **49 user aktif**:
- **Grant langsung tetap 51 baris** (dari 22 user). Sebagian besar jenis yang sengaja tidak dimasukkan
  ke role karena sensitif atau jarang (MIS, DGSN, PIB, DONATION, CT, …).
- **566 penambahan akses**: jenis yang tidak dipegang user di legacy tetapi ikut terbawa karena satu
  keluarga dengan jenis yang ia pegang. Penambahan terbesar ada pada jenis "pasangan" yang di legacy
  sering terlupa diberikan: ASAL, PL, IPJV, POPAY, RPGRN, EPJV, IBJV, CN, EDN, EIN, SJGRN.
- **Hanya 9 pasangan user–jenis yang benar-benar dipakai 12 bulan terakhir tidak tercakup role**,
  dan semuanya tertangani lewat grant langsung.

Inilah keputusan utama untuk pemilik dokumen: **role per keluarga dokumen membuat akses sedikit lebih
lebar dari legacy, sebagai ganti kesederhanaan.** Jika ada keluarga yang tidak boleh melebar, keluarga
itu dipecah (lihat §6 Pertanyaan).

---

## 2. Keluarga dokumen → Role

Setiap role memberi kemampuan **View** atas jenis dalam keluarganya (lewat `efl_type_grant`
`scope_type = Category` ke Role). Kemampuan **Upload/Replace** diberikan oleh role pasangan
`EFL-FILER-*` (§3).

| Role (Viewer) | Keluarga | Jenis (kode transaksi) | Pemilik usulan* |
|---|---|---|---|
| `EFL-SALES` | Dokumen Penjualan & Pengiriman | CIN, LIN, SIN, LDN, LDN SALES, PDN, PDN SALES, FP SALES, KWI, CSR, SRC, JWDN, JWDN SALES, JWIN, WDN, SJWDN, EDN, EIN, PL (19) | Sales Admin / Despatch |
| `EFL-PURCHASE` | Pembelian | LPO, LCPO, LSPO, IPO, ICPO, POPAY, MCM (7) | Purchasing |
| `EFL-GRN` | Penerimaan Barang | CGRN, CHPGRN, LGRN, NSGRN, SGRN, RPGRN, SJGRN, CSUPPR, NSSUPPR, RSUPPR (10) | Material Control |
| `EFL-AP` | Hutang & Pembayaran | NSBR, SSBR, CSBR, JSBR, BPS, BPSCAP, BPJ, BPJCAP, ADVP, ADVPSCAP, ADVPJCAP, ADVIC, ADVBPCC, BPCC, CPS, CPSV, CPJ, CPCC, CAS, CASV, APURC, MCM (22) | Finance AP |
| `EFL-AR` | Piutang & Penerimaan | BR, BRCC, ADVBRCC, ADVR, BC, CRS, CRJ, CRCC (8) | Finance AR |
| `EFL-GL` | Jurnal | JV, JJV, TJV, RJV, AADJ, CN, ASAL, EBJV, EPJV, IBJV, IPJV (11) | Accounting |
| `EFL-PRODUCT` | Dokumen Produk | CARPET PRODUCT, ITEM CODE (2) | Marketing / Engineering |
| `EFL-FINANCE` | **Komposit** = SALES + PURCHASE + GRN + AP + AR + GL | 75 jenis | Finance Head |
| `EFL-BOARD` | Dokumen Board — `Restricted` | MBRCP (1) | Direksi |

\* Pemilik = yang menyetujui siapa mendapat role; dikonfirmasi di OI-07.

**Sengaja tidak dibuat role** (diberikan langsung per user, karena sensitif atau dipegang sangat sedikit orang):

| Jenis | Alasan | Pemegang legacy (aktif) |
|---|---|---|
| BUPOT, PIB, SPTNP, SPPBMCP | Pajak & kepabeanan | 4 user |
| MIS, DESPMARGIN, DONATION | Laporan manajemen / margin | 10 user |
| DGSN, CT, CNG, PLN, SHP | Jenis kecil tanpa pemetaan ERP — **status perlu ditinjau (OI-17)** | ≤ 6 user |

Bila nanti jumlah pemegangnya tumbuh, dibuat role `EFL-TAX` / `EFL-MGMT` tanpa perubahan kode,
cukup data.

`MCM` (bukti transfer) muncul di dua keluarga (Purchasing dan AP) karena di legacy dibuka dari dua sisi;
satu jenis boleh di-grant dari lebih dari satu role.

---

## 3. Kemampuan Upload: role Filer

Di legacy siapa pun yang punya akses jenis bisa juga upload, pindah, dan hapus. Data 12 bulan:
hanya **26 dari 113** user yang benar-benar mem-*file* dokumen. Usulan: upload dipisah.

| Role | Kemampuan | Jumlah user aktif yang mem-*file* keluarga ini (12 bln) |
|---|---|---|
| `EFL-FILER-SALES` | Upload + Replace atas keluarga SALES | 3 |
| `EFL-FILER-PURCHASE` | … PURCHASE | 13 |
| `EFL-FILER-GRN` | … GRN | 1 |
| `EFL-FILER-AP` | … AP | 13 |
| `EFL-FILER-AR` | … AR | 7 |
| `EFL-FILER-GL` | … GL | 13 |
| `EFL-FILER-TAX` | … jenis pajak (grant per jenis) | 2 |

Filer tanpa Viewer tidak bermakna. Role Filer selalu diberikan bersama Viewer keluarga yang sama.
**Delete** tidak masuk role mana pun: hanya `efiling-admin` (legacy: 70 penghapusan setahun oleh 4 user).

Role ini adalah **role Spatie biasa**. Permission fitur (`efiling-view`, `efiling-upload`,
`efiling-dossier-view`, …) dipasang ke role, dan grant jenis (`efl_type_grant`) menunjuk ke role yang sama.
Jadi satu kali assign role = akses fitur + akses jenis.

---

## 4. Admin & pengecualian khusus

| Legacy | Usulan |
|---|---|
| 12 user `EFILL_ADMIN_USER` (8 aktif) | **`efiling-admin`** hanya untuk 2–3 orang yang benar-benar mengelola (IT + Accounting Head). Sisanya mendapat `EFL-FINANCE` atau role sesuai pekerjaan. Data menunjukkan beberapa admin (mis. `MANHAR`, `Pabasker`, `0686`) hampir tidak memegang grant jenis dan bergantung pada status admin untuk melihat Payable/Sales; mereka diberi role Viewer yang sesuai, bukan admin |
| `AUDITOR` + `EFILL_USER_YEAR` 2023–2025 | `EFL-FINANCE` dengan **jendela tahun** `year_from`/`year_to` dan `valid_until` di grant langsung, bukan di role. Auditor eksternal berikutnya: grant sementara yang kedaluwarsa otomatis |
| Grup `INVOICE BREAKDOWN` read-only | Permission `efiling-dossier-numbers-only` tanpa role Viewer |
| `MBRCP` 3 user (tidak aktif 12 bln) | `EFL-BOARD`, diberikan eksplisit; `efiling-admin` **tidak** otomatis melihat |

---

## 5. Pemetaan per kombinasi (72)

Kolom: **User** = pemegang kombinasi; **Aktif** = berkegiatan 12 bulan; **Role** = hasil usulan;
**Grant langsung** = jenis yang tetap diberikan per user; **+Melebar** = jumlah jenis yang bertambah
karena role. Kombinasi diurutkan dari yang terlebar.

| # | User | Aktif | Jenis legacy | Role usulan | Grant langsung | +Melebar |
|---|---:|---:|---|---|---|---:|
| K01 | 1 | 1 | 74 jenis — semua kecuali 17: BUPOT, CARPET PRODUCT, CNG, CT, DESPMARGIN, DGSN, DONATION, IBJV, IPJV, ITEM CODE, MBRCP, PLN, RPGRN, SHP, SJGRN, SPPBMCP, SPTNP | FINANCE | MIS, PIB | 4 |
| K02 | 2 | 2 | 68 jenis — semua kecuali 23: BUPOT, CARPET PRODUCT, CNG, CRS, CSUPPR, CT, DESPMARGIN, DGSN, DONATION, IBJV, IPJV, ITEM CODE, MBRCP, NSSUPPR, PIB, PLN, RPGRN, RSUPPR, SHP, SIN, SJGRN, SPPBMCP, SPTNP | FINANCE | MIS | 9 |
| K03 | 1 | 1 | 67 jenis — semua kecuali 24: ASAL, BUPOT, CARPET PRODUCT, CNG, CSUPPR, CT, DESPMARGIN, DGSN, EBJV, EPJV, IBJV, IPJV, ITEM CODE, MBRCP, NSSUPPR, PIB, PLN, POPAY, RPGRN, RSUPPR, SHP, SJGRN, SPPBMCP, SPTNP | FINANCE | DONATION, MIS | 11 |
| K04 | 1 | 1 | 67 jenis — semua kecuali 24: ASAL, BUPOT, CARPET PRODUCT, CNG, CRS, CSUPPR, CT, DESPMARGIN, DGSN, DONATION, IBJV, IPJV, ITEM CODE, MBRCP, NSSUPPR, PIB, PLN, RPGRN, RSUPPR, SHP, SIN, SJGRN, SPPBMCP, SPTNP | FINANCE | MIS | 10 |
| K05 | 2 | 1 | 66 jenis — semua kecuali 25: ASAL, BUPOT, CARPET PRODUCT, CNG, CRS, CSUPPR, CT, DESPMARGIN, DGSN, DONATION, EBJV, IBJV, IPJV, ITEM CODE, MBRCP, NSSUPPR, PIB, PLN, POPAY, RPGRN, RSUPPR, SHP, SJGRN, SPPBMCP, SPTNP | FINANCE | MIS | 11 |
| K06 | 1 | 0 | 65 jenis — semua kecuali 26: ASAL, BUPOT, CARPET PRODUCT, CNG, CRS, CSUPPR, CT, DESPMARGIN, DGSN, DONATION, EPJV, IBJV, IPJV, ITEM CODE, MBRCP, NSSUPPR, PIB, PLN, POPAY, RPGRN, RSUPPR, SHP, SIN, SJGRN, SPPBMCP, SPTNP | FINANCE | MIS | 12 |
| K07 | 2 | 0 | 65 jenis — semua kecuali 26: ASAL, BUPOT, CARPET PRODUCT, CNG, CRS, CSUPPR, CT, DESPMARGIN, DGSN, DONATION, EBJV, EPJV, IBJV, IPJV, ITEM CODE, MBRCP, NSSUPPR, PIB, PLN, RPGRN, RSUPPR, SHP, SIN, SJGRN, SPPBMCP, SPTNP | FINANCE | MIS | 12 |
| K08 | 3 | 1 | 64 jenis — semua kecuali 27: ASAL, BUPOT, CARPET PRODUCT, CNG, CRS, CSUPPR, CT, DESPMARGIN, DGSN, DONATION, EBJV, EPJV, IBJV, IPJV, ITEM CODE, MBRCP, NSSUPPR, PIB, PLN, POPAY, RPGRN, RSUPPR, SHP, SIN, SJGRN, SPPBMCP, SPTNP | FINANCE | MIS | 13 |
| K09 | 1 | 1 | 63 jenis — semua kecuali 28: BUPOT, CARPET PRODUCT, CASV, CNG, CPS, CPSV, CRS, CT, DESPMARGIN, DGSN, DONATION, EBJV, EPJV, IBJV, IPJV, ITEM CODE, MBRCP, MIS, PIB, PLN, POPAY, RPGRN, RSUPPR, SHP, SIN, SJGRN, SPPBMCP, SPTNP | FINANCE | — | 13 |
| K10 | 1 | 1 | 61 jenis — semua kecuali 30: ASAL, BC, BPJCAP, BUPOT, CARPET PRODUCT, CN, CPCC, CPJ, CRCC, CRJ, CSR, CT, DESPMARGIN, EPJV, ITEM CODE, JSBR, KWI, LDN SALES, MBRCP, MIS, PDN SALES, PIB, PL, POPAY, RJV, RPGRN, SHP, SPPBMCP, SPTNP, SRC | FINANCE | CNG, DGSN, DONATION, PLN | 19 |
| K11 | 1 | 1 | 59 jenis — semua kecuali 32: ASAL, BC, BPJCAP, BUPOT, CARPET PRODUCT, CN, CPCC, CPJ, CRCC, CRJ, CSR, CT, DESPMARGIN, EBJV, EPJV, ITEM CODE, JSBR, KWI, LDN SALES, MBRCP, MIS, PDN SALES, PIB, PL, POPAY, RJV, RPGRN, SHP, SIN, SPPBMCP, SPTNP, SRC | SALES + PURCHASE + GRN + AP + AR + GL | CNG, DGSN, DONATION, PLN | 21 |
| K12 | 1 | 1 | 42 jenis — semua kecuali 49: AADJ, ADVBRCC, ADVPJCAP, ADVR, APURC, ASAL, BC, BPCC, BPJ, BPJCAP, BR, BRCC, BUPOT, CARPET PRODUCT, CAS, CASV, CGRN, CHPGRN, CN, CNG, CPCC, CPJ, CPS, CPSV, CRCC, CRJ, CRS, CT, DGSN, DONATION, ITEM CODE, KWI, LDN SALES, LGRN, MBRCP, MCM, NSGRN, PDN, PDN SALES, PL, PLN, POPAY, RJV, RPGRN, SGRN, SHP, SJGRN, SJWDN, WDN | SALES + PURCHASE + GRN + AP + GL | DESPMARGIN, MIS, PIB, SPPBMCP, SPTNP | 31 |
| K13 | 1 | 1 | 41 jenis — semua kecuali 50: AADJ, ADVBPCC, ADVBRCC, ADVPJCAP, ADVR, APURC, ASAL, BC, BPJ, BPJCAP, BUPOT, CARPET PRODUCT, CGRN, CN, CNG, CPCC, CPJ, CRCC, CRJ, CSR, CSUPPR, DESPMARGIN, DONATION, EBJV, EDN, EIN, EPJV, FP SALES, IPJV, ITEM CODE, JJV, JSBR, MBRCP, MIS, NSSUPPR, PDN, PIB, PL, PLN, POPAY, RJV, RPGRN, RSUPPR, SGRN, SIN, SJGRN, SJWDN, SPPBMCP, SPTNP, SRC | SALES + PURCHASE + GRN + AP + AR + GL | CT, DGSN, SHP | 38 |
| K14 | 1 | 1 | 40 jenis — semua kecuali 51: AADJ, ADVBPCC, ADVBRCC, ADVPJCAP, ADVR, ASAL, BC, BPJ, BPJCAP, BUPOT, CARPET PRODUCT, CGRN, CN, CNG, CPCC, CPJ, CRCC, CRJ, CSR, CSUPPR, DESPMARGIN, DONATION, EDN, EIN, ICPO, IPO, ITEM CODE, JJV, JSBR, LCPO, LDN, LSPO, MBRCP, MCM, MIS, NSSUPPR, PDN, PIB, PL, PLN, POPAY, RJV, RPGRN, RSUPPR, SGRN, SHP, SJGRN, SJWDN, SPPBMCP, SPTNP, SRC | SALES + GRN + AP + AR + GL | CT, DGSN, LPO | 33 |
| K15 | 1 | 1 | 37 jenis — semua kecuali 54: AADJ, ADVBPCC, ADVBRCC, ADVIC, ADVPJCAP, ADVR, APURC, ASAL, BC, BPCC, BPJ, BPJCAP, BPSCAP, BUPOT, CIN, CN, CNG, CPCC, CPJ, CRCC, CRJ, CSUPPR, CT, DGSN, DONATION, EBJV, EDN, EIN, EPJV, IBJV, IPJV, ITEM CODE, JJV, JSBR, JWDN, JWDN SALES, JWIN, KWI, LDN, LIN, MBRCP, NSSUPPR, PDN, PIB, PL, PLN, POPAY, RJV, RSUPPR, SHP, SJWDN, SPPBMCP, SPTNP, WDN | SALES + PURCHASE + GRN + AP + AR + GL + PRODUCT | DESPMARGIN, MIS | 43 |
| K16 | 1 | 1 | 37 jenis — semua kecuali 54: AADJ, ADVBPCC, ADVBRCC, ADVIC, ADVPJCAP, ADVPSCAP, ADVR, APURC, ASAL, BC, BPCC, BPJ, BPJCAP, BPSCAP, BR, BRCC, BUPOT, CARPET PRODUCT, CAS, CASV, CGRN, CNG, CPCC, CPJ, CPS, CPSV, CRCC, CRJ, CRS, CSUPPR, DESPMARGIN, DGSN, DONATION, EDN, EIN, ITEM CODE, JSBR, LGRN, MBRCP, MIS, NSGRN, NSSUPPR, PIB, PL, PLN, POPAY, RJV, RPGRN, RSUPPR, SGRN, SHP, SJGRN, SPPBMCP, SPTNP | SALES + PURCHASE + AP + GL | CHPGRN, CT | 23 |
| K17 | 1 | 1 | 36 jenis — semua kecuali 55: AADJ, ADVBPCC, ADVBRCC, ADVPJCAP, ADVR, BC, BPCC, BPJCAP, BRCC, CARPET PRODUCT, CGRN, CHPGRN, CN, CNG, CPCC, CPJ, CRCC, CRJ, CRS, CSR, CSUPPR, CT, DESPMARGIN, DGSN, DONATION, EDN, EPJV, FP SALES, ICPO, IPJV, IPO, ITEM CODE, KWI, LCPO, LGRN, LPO, LSPO, MBRCP, MIS, NSGRN, NSSUPPR, PDN, PDN SALES, PL, PLN, POPAY, RJV, RPGRN, RSUPPR, SGRN, SHP, SJGRN, SPPBMCP, SPTNP, SRC | SALES + AP + GL | BR, BUPOT, PIB | 19 |
| K18 | 1 | 0 | 33 jenis — semua kecuali 58: AADJ, ADVBPCC, ADVBRCC, ADVPJCAP, ADVR, APURC, ASAL, BC, BPJ, BPJCAP, BUPOT, CARPET PRODUCT, CGRN, CN, CNG, CPCC, CPJ, CRCC, CRJ, CSR, CSUPPR, DESPMARGIN, DGSN, DONATION, EBJV, EDN, EIN, EPJV, FP SALES, IBJV, ICPO, IPJV, IPO, ITEM CODE, JJV, JSBR, LCPO, LSPO, MBRCP, MCM, MIS, NSSUPPR, PDN, PIB, PL, PLN, POPAY, RJV, RPGRN, RSUPPR, SGRN, SHP, SIN, SJGRN, SJWDN, SPPBMCP, SPTNP, SRC | SALES + GRN + AP + AR + GL | CT, LPO | 39 |
| K19 | 1 | 0 | 30 jenis — semua kecuali 61: AADJ, ADVBPCC, ADVBRCC, ADVIC, ADVP, ADVPSCAP, ADVR, APURC, ASAL, BPCC, BPJ, BPS, BPSCAP, BUPOT, CARPET PRODUCT, CIN, CNG, CPJ, CRS, CSUPPR, CT, DESPMARGIN, DGSN, DONATION, EBJV, EDN, EIN, EPJV, FP SALES, IBJV, ICPO, IPJV, IPO, ITEM CODE, JJV, JV, JWDN, JWDN SALES, JWIN, LCPO, LDN, LIN, LSPO, MBRCP, MCM, NSBR, NSSUPPR, PDN, PIB, PLN, POPAY, RPGRN, RSUPPR, SHP, SIN, SJGRN, SPPBMCP, SPTNP, SSBR, TJV, WDN | SALES + GRN + AP + AR + GL | LPO, MIS | 42 |
| K20 | 1 | 1 | 28 jenis — semua kecuali 63: AADJ, ADVBPCC, ADVBRCC, ADVPJCAP, ADVR, APURC, ASAL, BC, BPJ, BPJCAP, BUPOT, CARPET PRODUCT, CGRN, CHPGRN, CIN, CN, CPCC, CPJ, CRCC, CRJ, CSR, CSUPPR, DESPMARGIN, EBJV, EDN, EIN, EPJV, FP SALES, ICPO, IPJV, IPO, ITEM CODE, JJV, JSBR, JWDN, JWDN SALES, JWIN, KWI, LDN, LDN SALES, LGRN, LIN, MBRCP, MIS, NSGRN, NSSUPPR, PDN, PDN SALES, PIB, PL, POPAY, RJV, RPGRN, RSUPPR, SGRN, SHP, SIN, SJGRN, SJWDN, SPPBMCP, SPTNP, SRC, WDN | PURCHASE + AP + AR + GL | CNG, CT, DGSN, DONATION, PLN | 24 |
| K21 | 1 | 0 | 27 jenis — semua kecuali 64: AADJ, ADVBPCC, ADVIC, ADVPJCAP, ASAL, BPCC, BPJCAP, BUPOT, CARPET PRODUCT, CAS, CASV, CGRN, CHPGRN, CIN, CN, CNG, CPCC, CPJ, CPS, CPSV, CRCC, CRJ, CRS, CSR, CSUPPR, CT, DESPMARGIN, DGSN, DONATION, EBJV, EPJV, FP SALES, IBJV, IPJV, ITEM CODE, JSBR, JWDN, JWDN SALES, JWIN, KWI, LDN SALES, LGRN, LIN, MBRCP, MIS, NSGRN, NSSUPPR, PDN SALES, PIB, PL, PLN, POPAY, RJV, RPGRN, RSUPPR, SGRN, SHP, SIN, SJGRN, SJWDN, SPPBMCP, SPTNP, SRC, WDN | SALES + PURCHASE + AP + AR + GL | — | 39 |
| K22 | 1 | 1 | 20 jenis — semua kecuali 71: AADJ, ADVBPCC, ADVBRCC, ADVIC, ADVR, APURC, ASAL, BPS, BUPOT, CARPET PRODUCT, CAS, CGRN, CHPGRN, CIN, CNG, CPCC, CPJ, CPS, CPSV, CRCC, CRS, CSBR, CSR, CSUPPR, CT, DESPMARGIN, DGSN, DONATION, EBJV, EDN, EIN, EPJV, FP SALES, IBJV, ICPO, IPJV, IPO, ITEM CODE, JWDN, JWDN SALES, JWIN, KWI, LCPO, LDN, LDN SALES, LGRN, LIN, LPO, LSPO, MBRCP, MIS, NSGRN, NSSUPPR, PDN, PDN SALES, PIB, PL, PLN, POPAY, RPGRN, RSUPPR, SGRN, SHP, SIN, SJGRN, SJWDN, SPPBMCP, SPTNP, SRC, TJV, WDN | AP + AR + GL | — | 21 |
| K23 | 1 | 1 | 18 jenis — semua kecuali 73: ADVBPCC, ADVBRCC, ADVIC, ADVPJCAP, ADVR, APURC, ASAL, BC, BPCC, BPJCAP, BPS, BPSCAP, BR, BRCC, BUPOT, CARPET PRODUCT, CAS, CASV, CIN, CN, CNG, CPCC, CPJ, CPS, CPSV, CRCC, CRJ, CRS, CSBR, CSR, CSUPPR, CT, DESPMARGIN, DGSN, DONATION, EDN, EIN, EPJV, FP SALES, IBJV, ICPO, IPJV, IPO, ITEM CODE, JWDN, JWDN SALES, JWIN, KWI, LCPO, LDN, LDN SALES, LIN, LPO, LSPO, MBRCP, MCM, MIS, NSBR, NSSUPPR, PDN, PDN SALES, PL, PLN, POPAY, RJV, RSUPPR, SHP, SIN, SJWDN, SPPBMCP, SPTNP, SRC, TJV | GRN + AP + GL | PIB, WDN | 27 |
| K24 | 1 | 0 | ADVIC, ADVP, ADVPSCAP, BPS, BPSCAP, BRCC, CSBR, JV, LCPO, LPO, LSPO, MCM, NSBR, SSBR, TJV | PURCHASE + AP + GL | BRCC | 25 |
| K25 | 4 | 2 | CIN, CSR, FP SALES, JWDN, JWDN SALES, JWIN, KWI, LDN, LDN SALES, LIN, PDN, PDN SALES, SIN, SRC, WDN | SALES | — | 4 |
| K26 | 2 | 2 | CIN, CSR, FP SALES, JWDN, JWDN SALES, JWIN, KWI, LDN, LDN SALES, LIN, PDN, PDN SALES, SRC, WDN | SALES | — | 5 |
| K27 | 1 | 1 | CIN, FP SALES, JWDN, JWDN SALES, JWIN, KWI, LDN, LDN SALES, LIN, PDN, PDN SALES, SIN, WDN | SALES | — | 6 |
| K28 | 2 | 0 | CIN, FP SALES, JWDN, JWDN SALES, JWIN, KWI, LDN, LDN SALES, LIN, PDN, PDN SALES, WDN | SALES | — | 7 |
| K29 | 1 | 0 | CIN, JWDN, JWDN SALES, JWIN, KWI, LDN, LDN SALES, LIN, PDN, PDN SALES, WDN | SALES | — | 8 |
| K30 | 1 | 1 | APURC, BPJ, CAS, CPCC, CPJ, CRCC, CRJ, JJV, JSBR, RJV | AP + AR + GL | — | 31 |
| K31 | 1 | 1 | CGRN, CHPGRN, CSUPPR, LGRN, NSGRN, NSSUPPR, RPGRN, RSUPPR, SGRN, SJGRN | GRN | — | 0 |
| K32 | 1 | 1 | ADVBRCC, ADVR, BC, BPJ, BR, BRCC, JJV, RJV | AR + GL | BPJ | 12 |
| K33 | 1 | 0 | CGRN, CHPGRN, CSUPPR, LGRN, NSGRN, NSSUPPR, RSUPPR, SGRN | GRN | — | 2 |
| K34 | 1 | 1 | CIN, DGSN, ICPO, JWDN, JWDN SALES, JWIN, LDN, LIN | SALES | DGSN, ICPO | 13 |
| K35 | 1 | 1 | ADVP, BPS, EBJV, EPJV, IBJV, IPJV, JV | AP + GL | — | 26 |
| K36 | 1 | 0 | CGRN, CHPGRN, LGRN, NSGRN, RPGRN, SGRN, SJGRN | GRN | — | 3 |
| K37 | 1 | 0 | CIN, EDN, JWDN, JWDN SALES, JWIN, LDN, LIN | SALES | — | 12 |
| K38 | 3 | 2 | ICPO, IPO, LCPO, LPO, LSPO, MCM, POPAY | PURCHASE | — | 0 |
| K39 | 1 | 1 | CARPET PRODUCT, DESPMARGIN, IBJV, IPJV, MIS, SIN | GL + PRODUCT | DESPMARGIN, MIS, SIN | 10 |
| K40 | 2 | 1 | ICPO, IPO, LCPO, LPO, LSPO, POPAY | PURCHASE | — | 1 |
| K41 | 1 | 1 | JWDN, JWDN SALES, JWIN, LDN, LDN SALES, LIN | SALES | — | 13 |
| K42 | 5 | 1 | CGRN, CHPGRN, LGRN, NSGRN, SGRN | GRN | — | 5 |
| K43 | 2 | 2 | ICPO, IPO, LCPO, LPO, LSPO | PURCHASE | — | 2 |
| K44 | 1 | 0 | ICPO, LCPO, LPO, LSPO, POPAY | PURCHASE | — | 2 |
| K45 | 1 | 1 | LCPO, LPO, LSPO, MCM, POPAY | PURCHASE | — | 2 |
| K46 | 1 | 0 | CARPET PRODUCT, CSR, POPAY, SRC | SALES + PRODUCT | POPAY | 18 |
| K47 | 1 | 0 | CGRN, CHPGRN, LGRN, NSGRN | GRN | — | 6 |
| K48 | 1 | 1 | CIN, FP SALES, SIN, SJWDN | SALES | — | 15 |
| K49 | 2 | 2 | LCPO, LPO, LSPO, MCM | PURCHASE | — | 3 |
| K50 | 2 | 2 | LCPO, LPO, LSPO, POPAY | PURCHASE | — | 3 |
| K51 | 1 | 0 | CSR, POPAY, SRC | SALES | POPAY | 17 |
| K52 | 1 | 0 | EBJV, EPJV, POPAY | GL | POPAY | 9 |
| K53 | 2 | 0 | EDN, LDN SALES, PL | SALES | — | 16 |
| K54 | 1 | 1 | EDN, LDN, LDN SALES | SALES | — | 16 |
| K55 | 1 | 0 | IBJV, IPJV, SIN | GL | SIN | 9 |
| K56 | 1 | 0 | ICPO, IPO, LSPO | PURCHASE | — | 4 |
| K57 | 3 | 0 | LCPO, LPO, LSPO | PURCHASE | — | 4 |
| K58 | 1 | 0 | LDN, LDN SALES, PL | SALES | — | 16 |
| K59 | 2 | 0 | CARPET PRODUCT, POPAY | PRODUCT | POPAY | 1 |
| K60 | 1 | 0 | CARPET PRODUCT, SIN | PRODUCT | SIN | 1 |
| K61 | 1 | 1 | CIN, SSBR | — | CIN, SSBR | 0 |
| K62 | 1 | 0 | EDN, LDN | SALES | — | 17 |
| K63 | 1 | 0 | ICPO, LSPO | PURCHASE | — | 5 |
| K64 | 1 | 0 | JV, SSBR | — | JV, SSBR | 0 |
| K65 | 1 | 0 | LDN, LDN SALES | SALES | — | 17 |
| K66 | 1 | 0 | NSBR, TJV | — | NSBR, TJV | 0 |
| K67 | 8 | 3 | CARPET PRODUCT | PRODUCT | — | 1 |
| K68 | 1 | 0 | CRS | — | CRS | 0 |
| K69 | 8 | 0 | ITEM CODE | PRODUCT | — | 1 |
| K70 | 1 | 0 | LSPO | — | LSPO | 0 |
| K71 | 3 | 0 | MBRCP | BOARD | — | 0 |
| K72 | 2 | 1 | SIN | — | SIN | 0 |
---

## 6. Penerapan per user aktif (49)

Kolom **Mem-*file*** = keluarga dokumen yang benar-benar diunggah user ini dalam 12 bulan; usulan role
Filer mengikutinya.

| User ID | Dept | Admin legacy | Jenis legacy | Role Viewer | Mem-*file* (→ role Filer) | Grant langsung | +Melebar |
|---|---|---|---:|---|---|---|---:|
| FIN20 | - |  | 74 | FINANCE | GL | MIS, PIB | 4 |
| FIN13 | - |  | 68 | FINANCE | AP, AR, GL | MIS | 9 |
| FIN16 | - |  | 68 | FINANCE | — | MIS | 9 |
| 0334 | FIN | Ya | 67 | FINANCE | — | DONATION, MIS | 11 |
| FIN23 | - |  | 67 | FINANCE | AP, AR, GL | MIS | 10 |
| 13138 | - |  | 66 | FINANCE | AP, AR, GL | MIS | 11 |
| FIN12 | - |  | 64 | FINANCE | AP, GL | MIS | 13 |
| AUDITOR | - |  | 63 | FINANCE | — | — | 13 |
| 2070 | FIN |  | 61 | FINANCE | SALES, PURCHASE, AP | CNG, DGSN, DONATION, PLN | 19 |
| 2641 | - |  | 59 | SALES + PURCHASE + GRN + AP + AR + GL | — | CNG, DGSN, DONATION, PLN | 21 |
| 1943 | FIN |  | 42 | SALES + PURCHASE + GRN + AP + GL | — | DESPMARGIN, MIS, PIB, SPPBMCP, SPTNP | 31 |
| 2443 | FIN | Ya | 41 | SALES + PURCHASE + GRN + AP + AR + GL | — | CT, DGSN, SHP | 38 |
| RCS1283 | - |  | 40 | SALES + GRN + AP + AR + GL | SALES, AP, AR, GL | CT, DGSN, LPO | 33 |
| 1023 | FIN |  | 37 | SALES + PURCHASE + AP + GL | SALES, GL | CHPGRN, CT | 23 |
| 1437 | FIN |  | 37 | SALES + PURCHASE + GRN + AP + AR + GL + PRODUCT | PURCHASE, AP, GL | DESPMARGIN, MIS | 43 |
| 0699 | FIN |  | 36 | SALES + AP + GL | AP, GL, TAX | BR, BUPOT, PIB | 19 |
| 0399 | FIN |  | 28 | PURCHASE + AP + AR + GL | PURCHASE, AP | CNG, CT, DGSN, DONATION, PLN | 24 |
| FIN17 | - |  | 20 | AP + AR + GL | AP, AR, GL | — | 21 |
| FIN15 | - | Ya | 18 | GRN + AP + GL | AP, GL, TAX | PIB, WDN | 27 |
| 18180 | - |  | 15 | SALES | — | — | 4 |
| 24205 | - |  | 15 | SALES | — | — | 4 |
| 15158 | - |  | 14 | SALES | — | — | 5 |
| 99050 | - |  | 14 | SALES | — | — | 5 |
| 18182 | - |  | 13 | SALES | — | — | 6 |
| FIN11 | - |  | 10 | AP + AR + GL | AP, AR, GL | — | 31 |
| RCS0038 | MC |  | 10 | GRN | GRN | — | 0 |
| 0686 | IT | Ya | 8 | SALES | — | DGSN, ICPO | 13 |
| FIN3 | - |  | 8 | AR + GL | AP, AR, GL | BPJ | 12 |
| 2543 | - |  | 7 | PURCHASE | PURCHASE | — | 0 |
| 2593 | - |  | 7 | PURCHASE | PURCHASE | — | 0 |
| 2768 | - |  | 7 | AP + GL | — | — | 26 |
| 12157 | - |  | 6 | SALES | — | — | 13 |
| 1999 | - |  | 6 | PURCHASE | PURCHASE | — | 1 |
| SINTIA | FIN | Ya | 6 | GL + PRODUCT | — | DESPMARGIN, MIS, SIN | 10 |
| 0135 | - |  | 5 | PURCHASE | PURCHASE | — | 2 |
| 0521 | MC |  | 5 | GRN | — | — | 5 |
| 2518 | - |  | 5 | PURCHASE | PURCHASE | — | 2 |
| 2861 | MC |  | 5 | PURCHASE | PURCHASE | — | 2 |
| 1126 | - |  | 4 | PURCHASE | PURCHASE | — | 3 |
| 2074 | MC |  | 4 | PURCHASE | PURCHASE | — | 3 |
| 2592 | MC |  | 4 | PURCHASE | PURCHASE | — | 3 |
| 2842 | MC |  | 4 | PURCHASE | PURCHASE | — | 3 |
| Pabasker | FIN | Ya | 4 | SALES | — | — | 15 |
| 0617 | MC |  | 3 | SALES | — | — | 16 |
| 1949 | IT | Ya | 2 | — | — | CIN, SSBR | 0 |
| 1399 | - |  | 1 | PRODUCT | — | — | 1 |
| MANHAR | FIN | Ya | 1 | — | — | SIN | 0 |
| SUSHANT | MKT |  | 1 | PRODUCT | — | — | 1 |
| VINEESH | TFO |  | 1 | PRODUCT | — | — | 1 |
> Kolom Dept kosong (`-`) berarti `MST_USERS.USER_DEPT` tidak terisi di legacy; departemen sebenarnya
> diambil dari `HmEmpData` saat import.

---

## 7. Cara pengisian di sistem baru

1. Seeder/migrasi membuat role `EFL-*` beserta permission fitur dan grant kategori/jenisnya
   (idempoten, kunci = nama role).
2. Admin mengunggah **Excel penugasan** (user → role, user → grant langsung, jendela tahun, berlaku
   sampai) lewat halaman Grant (FR-02 BR-4). File awal disusun dari tabel §6 setelah disetujui
   pemilik — modul **tidak** membaca tabel legacy.
3. Setiap penambahan user baru: atasan meminta role, admin meng-assign. Tidak ada lagi
   pemberian per jenis kecuali pengecualian.
4. Tinjauan berkala (usulan per 6 bulan): laporan "grant tidak terpakai" dari `efl_access_log` — role
   atau grant yang tidak dipakai 6 bulan diusulkan dicabut.

---

## 8. Pertanyaan untuk pemilik dokumen

| # | Pertanyaan | Dampak bila "tidak" |
|---|---|---|
| Q-1 | Apakah boleh pemegang dokumen Sales lokal juga melihat dokumen ekspor (EDN, EIN, PL) dan jobwork (JWDN/JWIN/WDN)? | `EFL-SALES` dipecah jadi `SALES`, `EXPORT`, `JOBWORK`; lebih banyak role per user |
| Q-2 | Apakah jurnal ekspor-impor (EBJV, EPJV, IBJV, IPJV) boleh satu keluarga dengan jurnal umum? | `EFL-GL` dipecah jadi `GL` dan `EXIMJV` |
| Q-3 | Apakah `MCM` (bukti transfer) boleh dilihat Purchasing? | `MCM` keluar dari `EFL-PURCHASE`; pemegang MCM di Purchasing butuh grant langsung |
| Q-4 | Siapa 2–3 orang `efiling-admin`? | — |
| Q-5 | 64 user non-aktif: tidak dibawa (usulan) atau dibawa dengan `valid_until` 3 bulan? | — |
| Q-6 | DGSN, CT, CNG, PLN, SHP, ASAL: masih dipakai? (terkait OI-17) | Jenis dipensiunkan, grant langsung berkurang |
| Q-7 | MIS / DESPMARGIN dipegang 10 user aktif lewat grant langsung — perlu jadi role `EFL-MGMT`? | Tetap grant langsung |
