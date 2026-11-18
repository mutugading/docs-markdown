# Peta sistem legacy TQM (Oracle Forms 6i, schema MGTDAT)

Sumber: folder source Oracle Forms `TQM` dan `TQM RND` di file server internal (dibaca 2026-11-18). Form dan report
diekstrak dengan `strings`, sedangkan angka diambil dari query read-only ke production (MGTDAT).
Berkas `*_DDMMYYYY.fmb` adalah cadangan manual. Yang dipetakan di sini hanya versi aktif
(nama tanpa tanggal, folder Master).

## L1. Dua aplikasi, satu basis data

| | **TQM** (Color Development System) | **TQM RND** (Sample Market Advice) |
|---|---|---|
| Login | `TQMCDM01`: `TQM_USERS.USER_TYPE IN ('CDM','SPB')` | `TQMRND01`: `USER_TYPE = 'SMA'` |
| Menu | `TQMCDM02` (11 tombol, hak akses per divisi) | tidak ada, login langsung ke `TQMRND02` |
| Dokumen inti | `MGT_CDM` no. `MGT-PD-NNN/YYYY` | `MGT_SMA_HEAD` no. `SMA-NNN/YYYY` |
| Isi | pengembangan **warna/shade** (masterbatch) atas permintaan customer | pengiriman **sampel benang** ke customer untuk trial (harga, HS code, berat) |
| Volume | 5.367 dok (2018–2026), ±950/tahun | 5.228 dok (2018–2026), ±800–1.000/tahun |
| Status | Request 419 · Approve 4.948 | Request 843 · Approve 4.385 |

Bagian yang dipakai bersama: tabel `TQM_USERS`, master `MGT_CDM_FB_STATUS` / `MGT_CDM_FB_COMMERCIALIZED`,
pola approval yang identik (lihat L4), dan form pengiriman `TQMCDM10`, yang mengambil sumber
**dari kedua sistem**. `TQM RND\BackUp\TQMRND04.fmb` adalah salinan `TQMCDM04` yang tidak dipakai
(masih berisi `cdm_revision`).

## L2. Alur TQM (CDM)

```
MKT/TQM            TQM                 MB (Masterbatch)       TQM / TFO / TWT          TQM                   Despatch          MKT
TQMCDM04 ──────▶ TQMCDM06 ───────────▶ TQMCDM12 ───────────▶ TQMCDM11 / TQMCDM07 ──▶ TQMCDM08 ──────────▶ TQMCDM10 ───────▶ TQMCDM09
MGT_CDM          MGT_CDM_SEND_SUPP     Accept from TQM       MGT_CDM_RECEIVED_SUPP   SEND_TO_MARKETING   SEND_TO_DES       Feedback
MGT-PD-xxx/YYYY  TQM/xxx/MM/YYYY       (CDMS_ACCEPT_DATE)    (+_DET, OK / NOT OK)    TQM/xxx/MM/YYYY     (+_DET)           (FB status,
Request→Approve  supplier, ref name,                         dev no, shade, TR no,   ke customer:                          commercialized)
                 POY kg, qty bobbin                          range dosing, MB LDR    shade card/bobbin
```

| Form | Judul | Tabel ditulis | Catatan |
|---|---|---|---|
| TQMCDM03 | User Color Development | `TQM_USERS` | admin user |
| TQMCDM04 | Color Development / Sample Development Format | `MGT_CDM` | ±80 kolom: spek **customer** dan **MGT** berpasangan (polymer, luster, cross section, denier, fastness, end use, matching, light source, dosing, priority) |
| TQMCDM05 | Register Color Master | `MGT_CDM_COLOR_MASTER` | ID warna = base color + urut (mis. 1→1001), 4.346 baris |
| TQMCDM06 | Send to Supplier (Superba) | `MGT_CDM_SEND_SUPP`, `MGT_CDM_SUPP`, `MGT_CDM_SHADE_SUPERBA` | 88% supplier = "MGT MasterBatch" (internal) |
| TQMCDM12 | Accept from TQM (divisi MB) | `MGT_CDM_SEND_SUPP` | salinan form 06 + tanggal terima |
| TQMCDM11 | Master Batch send to TQM (divisi MB) | `MGT_CDM_RECEIVED_SUPP(_DET)` | VS number |
| TQMCDM07 | Received from Supplier (TQM/TFO/TWT) | `MGT_CDM_RECEIVED_SUPP(_DET)` | status uji `OK` / `NOT OK` per dev no |
| TQMCDM08 | Sample Send to Marketing | `MGT_CDM_SEND_TO_MARKETING(_DET)` | subjek: Sending Shade Card / Bobbin |
| TQMCDM09 | Feedback from Marketing | `..._MARKETING_DET.CDMD_FB_*`, `CDMD_COMMERCIALIZED` | **tidak pernah diisi** (2.232 baris, semuanya NULL) |
| TQMCDM10 | Samples Send to (despatch) | `MGT_CDM_SEND_TO_DES(_DET)` | sumber: SEND_TO_MARKETING **atau** `MGT_SMA_HEAD`; 79% ternyata dari SMA |
| CDM001–009 | report (cetak format, surat, status) | — | CDM008 = rekap Excel MGTPD → supplier → terima → marketing |

## L3. Alur TQM RND (SMA)

```
TQM / TQM-TWT / TQM-TFO                  MKT                     Despatch                 MKT / TQM
TQMRND02 header (MGT_SMA_HEAD) ───────▶ cetak TQMRND01.rep ───▶ TQMCDM10 / MGT_DES_SMPL ─▶ TQMRND02 item feedback
 + item (MGT_SMA_ITEM: nama, qty,        (MKT) / TQMRND02.rep                              SMAI_FB_STATUS,
   harga, HS code, net weight)           (lainnya)                                         SMAI_COMMERCIALIZED
 + detail properti (MGT_SMA_ITEM_DETAIL:                                                    (6 dari 21.097 item terisi)
   tenacity, elongation, twist, OPU ...)  ← hanya 97 baris, berhenti 2021
```

Header berisi customer, agen, kontak, negara, alamat kirim, alasan kirim (FREE OF CHARGE / trial),
biaya asuransi dan domestik, estimasi harga jual, total invoice USD, serta "To" (approver ditulis
sebagai teks bebas berisi 2–4 nama orang yang dipisah "/"). `SMAH_DEPT`: TQM, TQM/TWT, TQM/TFO.
Item sering dipakai sebagai catatan bebas ("NOTE :", "- Customer wants less hairy...").

## L4. Pola yang sama di kedua sistem

- Status dokumen: `ON PROGRESS` (draft) → `Request` → tombol **Approve** → `Approve`. Tombol yang sama
  berganti label menjadi **Amendment**: status kembali ke `Request` dan `REVISION + 1`. Tidak ada
  approver per level, tidak ada reject, dan tidak ada log (siapa pun yang punya akses form bisa approve).
- Penomoran: `MAX(SUBSTR(no))+1` per tahun, tanpa sequence/lock (rawan nomor ganda; ada nomor
  `TQM/724/XI/2026` dua kali di `MGT_CDM_RECEIVED_SUPP`).
- Hak akses menu (`TQMCDM02.pkg.set_view`, `:global.c_divn`):
  IT = semua · TQM = semua kecuali menu MB · MKT = CDM + Feedback · MB = Send to TQM + Accept from TQM · TFO/TWT = Received from Supplier.
- Password `TQM_USERS` disimpan sebagai teks UPPERCASE.

## L5. Keterkaitan nyata (data production)

| Ukuran | Angka |
|---|---|
| MGT-PD yang lanjut ke Send to Supplier | 2.357 / 5.367 (44%) |
| MGT-PD yang sampai ke Send to Marketing | 1.611 / 5.367 (30%) |
| Send to Supplier tanpa MGT-PD valid | 61 / 2.578 |
| Hasil uji supplier tercatat OK / NOT OK / kosong | 615 / 280 / 2.666 |
| Despatch TQMCDM10: sumber SMA / CDM / lain | 3.974 / 7 / 1.069 |
| Feedback customer terisi (CDM / SMA) | 0 / 6 |

## L6. Kualitas data yang perlu dibersihkan saat migrasi

- `CDM_MGT_TYPE` berupa teks bebas: Pom-Pom / POM-POM / POMPOM, Strip / STRIP / 2 STRIP(S), dll.
- `CDM_PRODUCT` berisi nama warna (GREY, BROWN) atau kode shade (SHN08), campur.
- `SMAH_TO` berisi daftar nama approver sebagai teks.
- Tabel sisa: `MGT_CDM_RECEIVED_SUPP_17112023` (backup), `MGT_CDM_COLOR_TEMP` (staging import 2021),
  `MGT_CDM_SEND_TO_MARK_DET`, dan `..._MARKETING_SHAD` (7 baris, 2020).
- Di luar lingkup: `CK01.rep` (Color Kitchen `MGT_CK_*`) ikut tersimpan di folder TQM, tapi itu
  modul tersendiri.
