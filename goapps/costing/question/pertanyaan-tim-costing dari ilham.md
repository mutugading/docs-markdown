# Permintaan Konfirmasi Perhitungan — Blok "For sale of AX Grade only" (Baris 81–94)

**Kepada:** Tim Finance & Costing
**Perihal:** Beberapa baris pada cost sheet belum bisa dihitung otomatis oleh sistem baru
**Tanggal:** September 2026

---

## 1. Kenapa kami minta bantuan

Sistem baru dibuat untuk menghasilkan cost sheet yang sama persis dengan file Excel yang selama ini
Bapak/Ibu pakai — baris per baris, angka per angka. Sebagian besar baris sudah cocok.

Namun ada satu blok baris, yaitu bagian **"For sale of AX Grade only"** (baris 81 sampai 94), yang
angkanya **muncul di sheet Excel, tetapi cara menghitungnya tidak pernah tercatat di mana pun**.

Waktu kami telusuri sistem lama (Oracle), perhitungan untuk baris-baris ini **tidak ditemukan di sana**.
Dugaan kami yang paling masuk akal: angka-angka ini dulu dihitung **langsung di dalam file Excel**, atau
diisi manual oleh yang mengerjakan, sehingga tidak pernah ikut terbawa ke sistem. Dugaan ini diperkuat
oleh satu temuan: pada sheet sumber file contoh, kolom di belakang baris **82** dan **83** kosong untuk
semua produk — artinya angkanya memang **diketik belakangan secara manual**.

Kami juga sudah memeriksa langsung ke basis data produksi (September 2026) untuk memastikan ini bukan
sekadar dugaan. Hasilnya: untuk **seluruh 12.567 baris biaya** pada periode terakhir, tidak ada satu pun
produk yang punya angka pada baris 85–92 dan 94 — jadi ini memang **belum pernah terisi sejak awal**,
bukan kasus khusus beberapa produk saja.

Akibatnya, untuk baris-baris tersebut sistem baru saat ini hanya menampilkan tanda **"-"** (strip),
karena kami tidak mau menampilkan angka yang belum jelas asal-usulnya.

Yang kami butuhkan dari Bapak/Ibu adalah **pengetahuan praktisnya**: baris ini angkanya dari mana, pakai
rumus apa, atau memang diisi tangan. Tidak perlu penjelasan teknis — cukup dijawab dengan bahasa
sehari-hari sesuai cara kerja Bapak/Ibu selama ini.

> Catatan: tidak ada yang salah dengan cara kerja selama ini. Excel memang fleksibel, dan wajar kalau
> sebagian perhitungan hidup di dalam file. Kami hanya perlu "memindahkannya" ke sistem baru.

---

## 2. Ringkasan baris yang terdampak

| Baris | Label persis di sheet | Tampil di sistem baru sekarang | Yang kami butuhkan |
|---|---|---|---|
| 81 | `81.Cost lessQL,CO,Frwd.` | Angka **keluar, tapi tidak cocok** dengan Excel (selisih -0,016 s/d +0,68) | Konfirmasi rumus yang benar |
| 82 | `82.NSBC SP.` | `-` | Asal angkanya |
| 83 | `83.Addl. NSBC Loss.` | `-` | Konfirmasi/koreksi dugaan rumus kami (lihat bagian 4) |
| 84 | `84.Domestic Cost AX grd only.` | `-` | **Sudah terpecahkan**, tinggal menunggu baris 83 |
| 85 | `85.R-AX..` | `-` | Asal angkanya |
| 86 | `86.R-AE./A9/A.` | `-` | Asal angkanya |
| 87 | `87.R-BC.` | `-` | Asal angkanya |
| 88 | `88.R NS SP.` | `-` | Asal angkanya |
| 89 | `89.R NS difference.` | `-` | Asal angkanya |
| 90 | `90.B/C SP.` | `-` | Asal angkanya |
| 91 | `91.R NS loss.` | `-` | Asal angkanya |
| 92 | `92.R BC loss.` | `-` | Asal angkanya |
| 94 | `94.Addl Val Loss.` | `-` | Asal angkanya |

Selain blok di atas, ada **dua isian lagi** yang berkaitan dengan baris 81 dan sampai sekarang belum
pernah ditentukan nilainya. Di sistem keduanya tercatat dengan nama **"% Add Top 95"** dan
**"Top 95 X % Add"**, dan statusnya masih *menunggu masukan pengguna* — artinya dari awal memang
dirancang untuk diisi oleh tim costing, bukan dihitung sendiri oleh sistem. Pertanyaannya ada di
**bagian 3** paling bawah.

Baris **93 `93.Std loss as above.`** dan **95 `95.Domestic cost with uneven packing.`** secara rumus
sudah siap. (Catatan internal tim IT: keduanya masih menunggu pemasangan di server produksi, jadi
kalau sekarang masih tampil `-`, itu urusan kami — bukan sesuatu yang perlu Bapak/Ibu jawab.)

Tentang baris 84: kami sudah memastikan bahwa

> **baris 84 = baris 67 + baris 83**

Pengecekan pada dua produk di file contoh: `2,075 + 0,231 = 2,306` dan `2,393 + 0,100 = 2,493` — cocok
persis. Jadi **begitu baris 83 terjawab, baris 84 langsung ikut selesai** tanpa pertanyaan tambahan.

---

## 3. Pertanyaan per baris

Untuk setiap baris di bawah, mohon dijawab singkat saja. Kalau jawabannya "saya juga tidak tahu, itu
warisan dari file lama", itu pun jawaban yang sangat membantu — supaya kami tahu harus mencari ke mana.

### Baris 81 — `81.Cost lessQL,CO,Frwd.`

Ini kasus yang berbeda dari yang lain: sistem **sudah punya rumus**, tetapi hasilnya **meleset** dari
angka Excel (selisih antara -0,016 sampai +0,68). Jadi di sini kami tidak perlu rumus baru, kami perlu
konfirmasi rumus yang benar.

1. Dari namanya, baris ini seharusnya = biaya **dikurangi** Quality Loss, Change Over, dan Forwarding.
   Betul demikian?
2. Kalau betul, angka awal yang dikurangi itu diambil dari **baris nomor berapa** di sheet yang sama?
3. Komponen mana saja yang dikurangi, dan dari **baris nomor berapa** masing-masing? (misalnya
   `63.Change Over Loss (4).` dan `66.Forwarding Cost.`)
4. Apakah "QL" yang dimaksud adalah gabungan dari baris 76 dan 77, atau hanya salah satunya?
5. Apakah ada komponen yang **ditambahkan kembali** setelah pengurangan?

### Baris 82 — `82.NSBC SP.`

Pada kedua produk di file contoh, nilainya **1** untuk dua-duanya. Karena tidak ada variasi sama sekali,
kami benar-benar tidak bisa menyimpulkan apa pun dari data — angka berapa pun yang menghasilkan 1 akan
"cocok". Jadi kami sepenuhnya bergantung pada penjelasan Bapak/Ibu.

1. Angka ini **hasil hitungan**, **tarif tetap (standar perusahaan)**, atau **diketik manual** per produk?
2. Kalau tarif tetap: apakah selalu **1**, atau berbeda per grade / per tipe produk (POY, PTY, TTY, dsb.)?
3. Kalau hasil hitungan: dari **baris nomor berapa** saja di sheet ini angkanya diambil?
4. Apakah nilainya berhubungan dengan `74. STD SP BC.` atau `73. STD SP AX.`?
5. "SP" di sini artinya **Selling Price**, **Special Product**, atau istilah lain?

### Baris 83 — `83.Addl. NSBC Loss.`

Ini baris **prioritas tertinggi**. Dugaan kami ada di bagian 4 di bawah — mohon dibaca dan
dikonfirmasi/dikoreksi. Pertanyaan pokoknya:

1. Angka ini **hasil hitungan**, **tarif tetap**, atau **diketik manual**?
2. Kalau hitungan: dari **baris nomor berapa** saja angkanya diambil? Apakah `67.Domestic Cost. (AX~AM).`
   ikut dipakai?
3. Apakah persentase grade (baris `25.AX.` sampai `30.C.`) ikut masuk ke dalam perhitungan ini?
4. Apakah angkanya **berbeda per grade / per tipe produk**, atau satu aturan untuk semua?

### Baris 85–92 — blok `R-*`

`85.R-AX..`, `86.R-AE./A9/A.`, `87.R-BC.`, `88.R NS SP.`, `89.R NS difference.`, `90.B/C SP.`,
`91.R NS loss.`, `92.R BC loss.`

Blok ini tampaknya satu kesatuan, jadi pertanyaannya kami gabung:

1. Huruf **"R"** di depan nama-nama ini singkatan dari apa? (Rate? Realisasi? Recovery? Revised?)
2. Apakah `85.R-AX..`, `86.R-AE./A9/A.`, dan `87.R-BC.` adalah **harga jual per grade**, **persentase
   recovery per grade**, atau hal lain?
3. Angka-angka ini **diinput manual per produk**, diambil dari **daftar harga/tarif standar**, atau
   **dihitung** dari baris lain di sheet ini?
4. Kalau dari daftar standar: daftar itu **per grade**, **per tipe produk**, atau **per shade/warna**?
   Dan siapa yang memelihara daftar tersebut?
5. Apakah `88.R NS SP.` dan `90.B/C SP.` punya hubungan dengan `73. STD SP AX.` / `74. STD SP BC.`?
6. Apakah `89.R NS difference.` adalah selisih antara dua baris di atasnya? Kalau ya, **baris mana
   dikurangi baris mana**?
7. Apakah `91.R NS loss.` dan `92.R BC loss.` dihitung dari baris 88–90, mirip pola pada baris 76 dan 77?

### Baris 94 — `94.Addl Val Loss.`

1. Angka ini **hasil hitungan**, **tarif tetap**, atau **diketik manual**?
2. Kalau hitungan: apakah dihitung dari blok `R-*` di atasnya (baris 85–92), atau dari baris lain?
3. Apa bedanya dengan `93.Std loss as above.` yang sudah berjalan? Apakah 94 adalah **tambahan di atas**
   93, atau **pengganti** 93 untuk kasus tertentu?
4. Apakah baris ini selalu terisi, atau hanya muncul pada kondisi tertentu (misalnya produk trial,
   uneven packing, atau grade mix tertentu)?

### Tambahan — "% Add Top 95" dan "Top 95 X % Add"

Dua isian ini menempel pada baris 81. Yang kedua sudah jelas cara hitungnya:

> **Top 95 X % Add  =  Baris 81  ×  % Add Top 95  ÷  100**

Jadi begitu **satu angka** (`% Add Top 95`) ditentukan, keduanya langsung jalan.

1. Istilah **"Top 95"** di sini maksudnya apa? Apakah merujuk ke 95% tertinggi dari sesuatu, ke baris 95
   pada sheet, atau ke istilah lain di luar sheet ini?
2. **Berapa persen** angka yang seharusnya dipakai?
3. Apakah persentase itu **sama untuk semua produk**, atau berbeda per tipe produk / per periode?
4. Siapa yang menentukan dan memelihara angka tersebut?

---

## 4. Dugaan kami untuk baris 83 — mohon dikonfirmasi atau dikoreksi

Kami mencoba menebak rumus baris 83 dari dua produk yang ada di file contoh. Kami sampaikan hasilnya
apa adanya, **beserta keterbatasannya**.

### Apa yang sudah kami singkirkan

Kami menduga baris 83 mungkin memakai `74. STD SP BC.`. Setelah dicoba secara menyeluruh, **tidak ada
satu pun rumus berbasis baris 74 yang cocok**. Alasannya sederhana: nilai baris 74 bergerak **naik**
antar dua produk (1,1 lalu 0,65 — arah berlawanan dengan jawabannya), sementara jawaban baris 83
bergerak **turun** (0,231 lalu 0,100). Arahnya tidak sejalan, jadi baris 74 hampir pasti bukan sumbernya.

### Dugaan yang cocok

Satu-satunya pola yang cocok dengan kedua produk adalah gagasan berikut, dalam bahasa bisnis:

> **Biaya domestik di-"gross-up" kembali seolah-olah seluruh batch adalah grade AX.**
>
> Karena kenyataannya hanya sebagian produksi yang grade AX, dan sisanya (AE, A9, A, B, C) hanya
> menghasilkan sebagian dari nilai AX, maka biaya per unit AX menjadi lebih tinggi. Selisih kenaikan
> itulah yang muncul sebagai baris 83.

Dalam bentuk hitungan kasar:

```
Baris 83  =  Baris 67 (Domestic Cost)  ×  ( 100 ÷ ( %AX + %non-AX × r ) − 1 )
```

di mana `r` adalah **tingkat pemulihan nilai (recovery) untuk bagian non-AX**.

Ketika kami hitung mundur `r` dari kedua produk **secara terpisah**, hasilnya:

| Produk | Nilai `r` yang diperlukan |
|---|---|
| Produk contoh 1 | ≈ **0,599** |
| Produk contoh 2 | ≈ **0,599** |

Dua produk yang berbeda menghasilkan angka yang praktis identik, dan sangat dekat ke angka bulat
**0,60 (60%)**. Ini membuat kami menduga ada **tarif baku 60%** yang dipakai perusahaan untuk bagian
non-AX.

### Kenapa kami TIDAK langsung memakainya

**Ini masih dugaan kami, bukan fakta.** Ada dua alasan kami berhenti dan bertanya:

1. Ada **empat variasi rumus berbeda** yang sama-sama cocok dengan kedua produk contoh. Dengan hanya dua
   sampel, kami tidak bisa membedakan mana yang benar. Memilih salah satu berarti menebak.
2. Kalau rumus yang kami pilih ternyata keliru, sistem akan menghasilkan **angka yang terlihat wajar
   tetapi salah** — jenis kesalahan yang paling berbahaya, karena tidak akan ketahuan saat dilihat
   sekilas, dan baru terasa setelah ikut terpakai di penetapan harga.

### Pertanyaan konkret untuk baris 83

1. Apakah gambaran "gross-up biaya domestik ke basis 100% AX" ini **benar** menggambarkan maksud baris 83?
   Kalau tidak, seperti apa maksud sebenarnya?
2. Apakah benar ada **tarif pemulihan sekitar 60%** untuk bagian non-AX?
3. Kalau ya: apakah **60% itu tarif tetap perusahaan**, atau angka yang bisa berubah per periode?
4. Apakah tarif tersebut **sama untuk semua grade non-AX** (AE, A9, A, B, C diperlakukan sama), atau
   **berbeda per grade** — misalnya AE lebih tinggi dari C?
5. Apakah tarifnya berbeda antar **tipe produk** (POY, PTY, TTY, dsb.)?
6. Apakah grade **C** ikut dihitung sebagai non-AX di sini, atau diperlakukan sebagai waste/terpisah?

---

## 5. Bantuan paling cepat

Kalau menjelaskan rumus dari ingatan terasa berat, ada cara yang **jauh lebih cepat dan lebih akurat**
bagi kami:

> **Kirimkan cost sheet Excel yang sudah terisi lengkap untuk 2–3 produk TAMBAHAN,
> dengan persentase AX yang BERBEDA-BEDA satu sama lain.**

Yang paling ideal:

- minimal satu produk dengan **%AX rendah**, misalnya di kisaran **50–60%**;
- satu produk dengan **%AX menengah**, misalnya 75–85%;
- satu produk dengan **%AX tinggi**, misalnya di atas 95%;
- dan kalau memungkinkan, **tipe produk yang berbeda** (POY dan PTY/TTY).

Kalau membantu, berikut beberapa kode produk yang menurut data sistem punya **%AX rendah** — persis
rentang yang paling kami butuhkan. Bapak/Ibu boleh memilih dari daftar ini, atau produk lain mana pun
yang lebih mudah diakses:

| %AX | Contoh kode produk |
|---|---|
| 48% | `CSTPTY2606005060`, `CSTPTY2606005097` |
| 50% | `CSTPTY2606001190`, `CSTPTY2606004439`, `CSTPTY2606006457` |
| 51% | `CSTPTY2606004512`, `CSTPTY2606004513` |
| 55% | `CSTPTY2606001620`, `CSTPTY2606003635`, `CSTPTY2606005689` |
| 60% | `CSTPTY2606001191`, `CSTPTY2606002083`, `CSTPTY2606005158` |

Satu saja dari rentang 48–55% sudah sangat membantu; kalau bisa dua sekaligus, lebih baik lagi.

Yang penting: baris 81 sampai 94 pada file itu **sudah terisi angkanya** seperti biasa Bapak/Ibu
kerjakan. Tidak perlu ada penjelasan tambahan — cukup file-nya saja.

### Kenapa sampel tambahan sangat membantu

Analoginya seperti menarik garis lurus. Dengan **dua titik**, banyak sekali garis yang bisa lewat
keduanya — kita tidak bisa memastikan yang mana. Dengan **empat atau lima titik yang tersebar**, hanya
satu garis yang benar-benar melewati semuanya, dan sisanya langsung gugur dengan sendirinya.

Saat ini kami hanya punya dua produk contoh, dan kebetulan keduanya punya %AX yang **relatif tinggi**.
Empat kandidat rumus yang kami temukan menghasilkan angka yang hampir sama persis pada %AX tinggi —
perbedaannya baru terlihat jelas saat %AX-nya rendah. Itulah sebabnya kami khusus meminta satu produk
dengan **%AX sekitar 50–60%**: satu contoh seperti itu saja sudah cukup untuk memisahkan mana rumus yang
benar dan mana yang kebetulan cocok.

Dengan sampel tambahan ini, kami bisa **memverifikasi sendiri** tanpa Bapak/Ibu harus menyusun ulang
rumusnya dari ingatan. Hasil verifikasinya tetap akan kami kirim kembali untuk disetujui sebelum dipakai.

---

## 6. Urutan prioritas

Kalau waktu terbatas, mohon didahulukan sesuai urutan ini:

### Prioritas 1 — Baris 83 (dan otomatis baris 84)

Nilai tertingginya: **satu jawaban membuka dua baris sekaligus**, karena baris 84 sudah kami pastikan
sama dengan baris 67 + baris 83. Cukup konfirmasi bagian 4 di atas — atau kirim sampel produk tambahan
seperti di bagian 5.

### Prioritas 2 — Baris 81

Ini satu-satunya baris yang **saat ini menampilkan angka yang SALAH**, bukan strip. Baris lain yang
kosong setidaknya jujur bahwa datanya belum ada; baris 81 berpotensi menyesatkan karena angkanya keluar
dan terlihat wajar. Karena itu risikonya lebih mendesak daripada baris yang masih kosong.

### Prioritas 3 — Baris 82

Perlu dikonfirmasi, tapi kalau memang nilainya selalu **1**, penyelesaiannya cepat.

### Prioritas 4 — "% Add Top 95"

Hanya perlu **satu angka persentase**. Begitu angkanya ada, dua isian sekaligus langsung terisi tanpa
pekerjaan tambahan — jadi ini yang paling ringan usahanya dibanding hasilnya.

### Prioritas 5 — Baris 85–92 dan 94 (blok `R-*` dan Addl Val Loss)

Prioritas paling rendah, karena baris-baris ini berada di bagian pelengkap yang **tidak ikut tercetak**
pada sheet final. Jadi tidak menghambat pemakaian sehari-hari, dan bisa ditangani belakangan.

---

## 7. Cara menjawab

Tidak perlu format khusus. Silakan pilih yang paling nyaman:

- balas dokumen ini dengan catatan di bawah tiap pertanyaan, atau
- jawab lewat pesan/email singkat dengan menyebut **nomor barisnya**, atau
- kirim file Excel contoh (bagian 5) — ini yang paling membantu, dan
- kalau memang ada baris yang jawabannya *"diisi manual, tidak ada rumusnya"*, mohon disampaikan apa
  adanya. Itu jawaban yang sah dan tetap berguna: berarti baris tersebut memang bukan hasil rumus, dan
  kami akan membahas di internal bagaimana sebaiknya angka itu masuk ke sistem.

Terima kasih banyak atas waktu dan bantuannya.
