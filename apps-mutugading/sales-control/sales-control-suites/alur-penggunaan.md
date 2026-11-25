# Alur Penggunaan — Kontrol Harga Minimum (Sales Control)

Panduan pemakaian untuk Finance, Marketing, dan BOD. Ditulis untuk orang yang memakai aplikasi,
bukan untuk yang membangunnya. Untuk sisi teknis, lihat `design.md` dan `spec.md` di folder yang sama.

Tampilan aplikasi masih berbahasa Inggris, jadi nama menu, tombol, dan status di dokumen ini
dibiarkan apa adanya supaya cocok dengan yang terlihat di layar.

---

## 1. Apa yang dikerjakan fitur ini

Perusahaan punya batas harga jual terendah per item. Sebelum ini, batas itu dijaga dengan mengubah
data master di Orion, dan tidak ada catatan siapa yang menurunkan batas, kapan, atau atas
persetujuan siapa. Penjualan yang di bawah batas juga tidak tercatat.

Sekarang ada dua alur, dan keduanya berakhir pada satu dokumen bertanda tangan:

- **Finance mendaftarkan harga minimum.** Dibuat sebagai draft, dicetak jadi formulir bernomor,
  ditandatangani BOD, hasil scan diunggah, baru kemudian berlaku.
- **Marketing meminta pengecualian.** Kalau satu sales order harus dijual di bawah batas, aplikasi
  menunjukkan baris mana yang di bawah dan selisihnya berapa. Marketing menandai barisnya, memberi
  alasan, lalu lewat alur cetak → tanda tangan → unggah → setujui, terbitlah pengecualian yang
  berlaku hanya untuk baris itu.

Yang menghitung dan memutuskan angka adalah paket Oracle (`PKG_MGT_PRICE_CTRL`). Aplikasi hanya
menampilkan hasilnya dan mengurus dokumennya. Jadi kalau angka di layar terasa aneh, yang perlu
diperiksa adalah data di Oracle, bukan perhitungan di aplikasi.

---

## 2. Peran dan hak akses

Ada empat peran. Satu orang boleh punya lebih dari satu peran, **kecuali** pasangan maker–approver
di alur yang sama: pemohon tidak bisa menyetujui permohonannya sendiri. Aplikasi menolaknya, bukan
hanya menyembunyikan tombolnya.

| Peran | Bisa melakukan |
|---|---|
| **Min Price - Maker** | Membuat dan mengubah draft harga minimum, impor Excel, mengirim untuk persetujuan, mencetak formulir |
| **Min Price - Approver** | Mencetak formulir, mengunggah scan, menyetujui / menolak, mem-*void* aturan yang sudah disetujui |
| **Sales Exception - Requester** | Melihat cek harga, mengajukan pengecualian, mencetak formulirnya |
| **Sales Exception - Approver** | Mengunggah scan, menyetujui / menolak / mem-*void* pengecualian, membuka laporan |

Kalau tombol yang Anda cari tidak ada di layar, hampir selalu karena perannya belum diberikan.
Minta ke tim IT, jangan dicari di menu lain.

---

## 3. Cara masuk ke menu

**Dashboard → Finance → Sales Control.**

Halaman Sales Control adalah beranda fitur ini. Di situ ada delapan halaman dan empat angka
ringkasan ("ada yang menunggu saya atau tidak"). Halaman yang perannya tidak Anda punya tidak akan
muncul, jadi kalau halaman terlihat kosong, artinya belum ada hak akses — bukan berarti rusak.

Angka ringkasan disimpan sementara selama lima menit. Jadi kalau baru menyimpan data lalu angkanya
belum berubah, tunggu sebentar atau buka langsung halaman datanya.

---

## 4. Alur A — Menetapkan harga minimum

Pelaku: **Min Price - Maker** lalu **Min Price - Approver**.

### Langkah 1 — Buat draft (Maker)

Buka **Minimum Price Master**, klik tombol tambah.

Yang diisi:

| Isian | Keterangan |
|---|---|
| Scope Level | **Item** untuk satu kode item, atau **All Items** untuk semua item |
| Scope Value | Kode item, kalau scope-nya Item |
| Grade 1 / Grade 2 | Grade yang dibatasi. Boleh dikosongkan kalau berlaku untuk semua grade |
| UOM | Satuan |
| Min Price (USD) | Harga terendah dalam USD |
| Tolerance % | Kelonggaran, kalau ada |
| Valid From / Valid To | Masa berlaku. Valid To boleh dikosongkan berarti berlaku terus |
| Remarks | Catatan, sebaiknya diisi alasan penetapan |

Draft belum berlaku. Selama statusnya `DRAFT`, tidak ada sales order yang terpengaruh — jadi aman
menyiapkan banyak baris dulu.

**Kalau barisnya banyak**, pakai tombol **Import** dan unggah file Excel. Format kolomnya ada di
bagian 8 dokumen ini. Baris yang salah akan dilaporkan per nomor baris, dan baris yang benar tetap
masuk sebagai draft.

### Langkah 2 — Kirim untuk persetujuan (Maker)

Tandai draft yang mau diajukan — boleh banyak sekaligus — lalu klik **Submit for Approval** dan isi
alasan pengajuan.

Semua draft yang ditandai jadi **satu permohonan**, satu formulir, satu tanda tangan. Aplikasi
memberi nomor permohonan berformat **`MPL-2026-0001`**. Catat nomor itu; semua langkah berikutnya
memakainya.

Setelah dikirim, statusnya masih `DRAFT` di daftar aturan — aturan baru berubah jadi `APPROVED`
setelah permohonannya disetujui, bukan saat dikirim.

### Langkah 3 — Cetak formulir

Buka **Minimum Price Approvals**, cari nomor permohonannya, klik **Print**.

Aplikasi menghasilkan PDF berisi nomor permohonan dan semua baris harga di dalamnya. Setelah
dicetak, status permohonan menjadi `PRINTED` dan **isinya terkunci**. Ini disengaja: jejak kertas
sudah mulai, jadi perubahan bukan lagi "edit" tapi "revisi" (lihat langkah 6).

Formulir boleh dicetak ulang berkali-kali; statusnya tetap `PRINTED`.

### Langkah 4 — Tanda tangan BOD

Di luar aplikasi. Formulir dicetak, ditandatangani, lalu di-scan.

### Langkah 5 — Unggah scan dan setujui (Approver)

Di **Minimum Price Approvals**, klik tombol persetujuan pada permohonan yang `PRINTED`. Yang diisi:

| Isian | Keterangan |
|---|---|
| Signed Document | Hasil scan. Format PDF / JPG / PNG, maksimal 10 MB |
| BOD Document No | Nomor dokumen dari BOD |
| BOD Document Date | Tanggal dokumen BOD |
| BOD Signer | Nama penanda tangan |

Scan bisa diunggah lebih dulu lewat tombol **Upload Scan**, tanpa harus tahu nomor dokumen BOD-nya.
Berkas disimpan di penyimpanan tertutup dan dihitung sidik digitalnya (*hash*), sehingga nanti bisa
dibuktikan bahwa berkas yang tersimpan belum pernah ditukar. Tombol verifikasi di halaman itu
memeriksanya kapan saja.

Klik **Approve**. Status permohonan jadi `APPROVED`, dan **semua aturan di dalamnya langsung
berlaku**. Sejak titik ini sales order akan diperiksa terhadap batas itu.

### Langkah 6 — Kalau ada yang salah

| Situasi | Yang dilakukan | Akibatnya |
|---|---|---|
| BOD menolak | **Reject** + alasan | Permohonan jadi `REJECTED`. Tidak bisa dilanjutkan; buat permohonan baru |
| Ada salah isi, formulir sudah dicetak | **Revise** | Muncul baris baru dengan nomor permohonan sama, revisi +1, status `DRAFT`. Revisi sebelumnya jadi `VOID`. Cetak dan tanda tangan ulang |
| Belum dicetak, mau ditarik | **Cancel** | Permohonan jadi `CANCELLED` |
| Sudah disetujui, ternyata keliru | **Void** + alasan | Permohonan jadi `VOID` dan aturannya tidak berlaku lagi |

### Langkah 7 — Mengubah harga yang sudah berlaku

Jangan diedit. Pakai tombol **Supersede** pada aturan yang `APPROVED`.

Aplikasi menutup aturan lama (`Valid To` diisi satu hari sebelum aturan baru mulai) dan membuat draft
baru yang isinya sudah tersalin, siap disesuaikan. Draft baru itu harus melewati alur persetujuan
lagi dari langkah 2.

Alasannya: keputusan lama harus tetap bisa dijelaskan bertahun-tahun kemudian. Kalau baris lama
ditimpa, tidak ada lagi cara menjawab "kenapa bulan Maret harganya segitu".

Tidak ada tombol hapus di fitur ini. Yang salah di-*void*, tidak dihapus.

---

## 5. Alur B — Menjual di bawah harga minimum

Pelaku: **Sales Exception - Requester** lalu **Sales Exception - Approver**.

### Langkah 1 — Cek dulu (Requester)

Buka **Minimum Price Check**. Isi:

- **Transaction Code** — `ESC`, `LSC`, atau `STA`
- **SO Number** — nomor sales order-nya

Klik cek. Aplikasi menampilkan setiap baris SO beserta batas minimumnya, harga jual setelah
dikonversi, dan selisihnya kalau di bawah batas.

Halaman ini **tidak menyimpan apa pun**. Melihat bukan memutuskan, jadi silakan dipakai sesering
perlu tanpa khawatir mengotori catatan audit.

Kalau paket Oracle sedang tidak bisa menjawab, halaman akan mengatakannya dan **tidak menyimpulkan
apa-apa** tentang baris mana pun. Ini disengaja: lebih baik tidak tahu daripada semua baris terlihat
aman padahal kurs gagal dibaca.

Baris yang sudah punya pengecualian disetujui diberi label nomor permohonannya, dan tidak
ditawarkan lagi. Tapi kalau setelah disetujui kursnya diturunkan atau kuantitasnya dinaikkan, baris
itu kembali terlihat belum terlindungi — karena pengecualiannya memang tidak lagi cocok.

### Langkah 2 — Ajukan pengecualian (Requester)

Dari halaman cek, klik **Request Exception**, atau buka **Price Exception Request** lalu isi SO-nya.

Tandai baris yang mau diajukan (ada tombol untuk menandai semua baris yang di bawah batas), lalu isi
alasan bisnisnya.

**Tidak ada kolom harga di formulir ini, dan memang tidak akan pernah ada.** Kurs dan kuantitas
diambil langsung dari sales order. Alasannya praktis: trigger di Oracle mencocokkan angka
persetujuan dengan angka di SO secara persis, jadi satu angka yang diketik ulang akan menghasilkan
persetujuan yang sudah ditandatangani, terlihat benar, tapi tetap memblokir SO-nya. Ini penyebab
tiket support nomor satu di alur seperti ini, jadi peringatannya juga ditulis di halamannya.

Setelah disimpan, aplikasi memberi nomor **`MPO-2026-0001`**.

### Langkah 3 sampai 5 — Sama seperti alur A

Cetak formulir → tanda tangan BOD → unggah scan dan isi tiga kolom BOD → **Approve**, tapi di
halaman **Price Exception Approvals**.

Setelah disetujui, SO tersebut bisa di-approve di Orion untuk baris-baris itu — pada kurs dan
kuantitas yang persis disetujui.

Tombol **Reject**, **Revise**, **Cancel**, dan **Void** bekerja sama seperti alur A.

---

## 6. Arti tiap status

**Status aturan harga minimum**

| Status | Arti |
|---|---|
| `DRAFT` | Sudah dicatat, belum berlaku. Tidak ada SO yang terpengaruh |
| `APPROVED` | Berlaku. SO diperiksa terhadap aturan ini |
| `VOID` | Dibatalkan. Tetap tersimpan sebagai riwayat |

**Status permohonan** (dipakai alur A maupun alur B)

| Status | Arti | Langkah berikutnya |
|---|---|---|
| `DRAFT` | Baru diajukan | Cetak formulirnya |
| `PRINTED` | Sudah dicetak, isi terkunci | Unggah scan yang sudah ditandatangani |
| `APPROVED` | Disetujui dan berlaku | — |
| `REJECTED` | Ditolak | Buat permohonan baru |
| `CANCELLED` | Ditarik sebelum dicetak | Buat permohonan baru |
| `VOID` | Dibatalkan, atau digantikan revisi berikutnya | Lihat revisi terbaru |

Yang boleh diubah setelah `PRINTED` hanya berkas scan, tiga kolom dokumen BOD, dan alasan penolakan.
Itu mencatat tanda tangannya, bukan isi permohonannya.

---

## 7. Laporan

| Laporan | Menjawab | Filter |
|---|---|---|
| **Active Minimum Prices** | Batas apa yang berlaku pada satu tanggal, dan nomor permohonan yang mengesahkannya | Satu tanggal, bukan rentang |
| **Exceptions Granted** | Penjualan di bawah batas yang disetujui pada satu periode, lengkap dengan tautan ke scan bertanda tangannya | Rentang tanggal |
| **Minimum Price Rejections** | Baris SO yang diblokir, dengan jumlah percobaannya | Rentang tanggal |

"Active Minimum Prices" memakai satu tanggal karena pertanyaannya memang hanya punya jawaban dalam
bentuk itu — "batas apa yang berlaku sepanjang Maret" tidak punya jawaban tunggal kalau harganya
berubah di tengah bulan.

Laporan rejections juga memuat dua panel pemantauan, dan keduanya **hanya pemantauan, bukan
kontrol**:

- **Gross vs net** — bahan angka untuk keputusan "apakah validasi sebaiknya pakai harga net".
  Belum jadi aturan.
- **Aturan yang tumpang tindih** — satu-satunya tempat di mana dua aturan yang saling bertabrakan
  akan kelihatan.

Kolom kurs di laporan adalah kurs yang tersimpan saat kejadian, bukan kurs hari ini. Itu satu-satunya
cara menjawab "kenapa yang bulan Maret gagal" setelah tabel kurs berubah.

---

## 8. Format file Excel untuk impor

Baris pertama harus berisi nama kolom berikut, dengan urutan ini:

| Kolom | Wajib | Contoh |
|---|---|---|
| `scope_level` | ya | `ITEM` atau `ALL` |
| `scope_value` | ya kalau `scope_level` = `ITEM` | kode item, maksimal 20 karakter |
| `grade_code_1` | tidak | |
| `grade_code_2` | tidak | |
| `uom_code` | ya | `KG` |
| `min_price_usd` | ya | `1.85` |
| `tolerance_pct` | tidak | `0` |
| `valid_from` | ya | `2026-01-01` |
| `valid_to` | tidak | kosong = berlaku terus |
| `remarks` | tidak | alasan penetapan |

Semua baris masuk sebagai `DRAFT`, jadi impor tidak pernah langsung mengubah apa yang berlaku.
Proses impor dan ekspor dijalankan di belakang (queue); hasilnya diberitahukan lewat notifikasi,
termasuk tautan unduh untuk ekspor.

---

## 9. Hal yang paling sering ditanyakan

**Sudah saya setujui, kok SO masih diblokir?**
Cek kurs dan kuantitas barisnya di SO. Trigger mencocokkannya persis dengan yang disetujui — kalau
setelah persetujuan kursnya diturunkan atau kuantitasnya dinaikkan, pengecualian itu tidak lagi
cocok. Buka Minimum Price Check untuk melihat status baris tersebut sekarang.

**Kenapa saya tidak bisa menyetujui pengajuan saya sendiri?**
Karena memang dilarang. Kalau satu orang perlu punya dua peran itu, berarti ada yang salah di
pengaturan peran, dan aplikasi menolaknya dengan jelas daripada meloloskannya diam-diam.

**Kenapa penyetuju berupa peran, bukan orang tertentu?**
Supaya selalu ada lebih dari satu orang yang bisa menyetujui. Kalau dikunci ke satu nama, seluruh
sales order perusahaan berhenti ketika orang itu cuti.

**Saya salah isi harga tapi formulirnya sudah dicetak.**
Pakai **Revise**. Nomor permohonannya tetap, revisinya naik satu, dan formulirnya perlu dicetak dan
ditandatangani ulang.

**Aturan lama mau dihapus.**
Tidak ada penghapusan. Pakai **Void** kalau memang salah, atau **Supersede** kalau harganya berubah.

**Laporan rejections kosong.**
Wajar untuk saat ini — lihat bagian 10.

---

## 10. Batasan saat ini

- **Trigger Oracle belum dipasang.** Selama belum, tidak ada SO yang benar-benar diblokir, dan
  laporan rejections akan kosong (halamannya menjelaskan hal ini). Trigger dipasang setelah UAT,
  sebagai perubahan terpisah — supaya Marketing sudah punya cara mengajukan pengecualian sebelum
  dindingnya berdiri.
- **Scope level "Item Group" belum aktif.** Formulir hanya menawarkan **Item** dan **All Items**.
  Apakah pengelompokan item di Orion cocok dipakai sebagai dasar batas harga masih jadi pertanyaan
  untuk Indra dan Finance.
- **Yang diperiksa hanya transaksi `ESC`, `LSC`, dan `STA`.** Daftarnya dibaca dari tabel konfigurasi
  Oracle, jadi trigger dan aplikasi selalu sepakat tentang dokumen mana yang diperiksa.
- **Validasi memakai harga gross.** Perbandingan gross dan net sudah dipantau di laporan rejections,
  tapi belum menjadi aturan.
