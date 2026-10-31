# Email pemberitahuan — perpindahan halaman CI Project

> Draf untuk dikirim ke koordinator CI, PIC departemen, dan manajemen sebelum cut-over.
> Bagian bertanda `[...]` harus diisi dulu (tanggal, nama, kontak).

---

**Kepada:** Koordinator CI, PIC Departemen, Manajemen
**Tembusan:** [atasan / IT Support]
**Perihal:** Halaman CI Project Savings pindah ke Apps Mutu Gading — berlaku mulai [tanggal]

---

Selamat pagi Bapak/Ibu,

Halaman **CI Project Savings** (daftar proyek continuous improvement dan pencatatan saving bulanan)
dipindahkan dari aplikasi lama `mgthris` ke aplikasi **Apps Mutu Gading**. Perpindahan ini dilakukan
karena aplikasi lama akan dihentikan, sekaligus untuk memperbaiki pengaturan hak akses dan proses
persetujuan yang selama ini masih lemah.

**Mulai [tanggal], halaman lama tidak lagi digunakan.** Alamat baru:

`https://apps.mutugading.com:15039` → menu **Finance → Transaction → CI Project**

Data tidak berpindah ke mana pun. Seluruh proyek dan saving yang sudah tercatat tetap ada dan langsung
terlihat di halaman baru — tidak ada yang perlu diinput ulang.

## Yang berubah dan perlu diperhatikan

**1. Proyek sekarang melalui persetujuan.**
Proyek yang baru didaftarkan berstatus **Draft**. Statusnya tidak menghambat pekerjaan apa pun: proyek
tetap bisa diedit oleh departemennya dan tetap bisa diisi saving bulanan seperti biasa sambil menunggu.
Setelah disetujui, proyek tidak bisa lagi diubah langsung — untuk mengubahnya, proyek harus diajukan
sebagai **Amend** lebih dulu disertai alasan, lalu disetujui kembali. Tujuannya agar perubahan pada
proyek yang sudah berjalan selalu ada jejaknya.
Seluruh proyek yang sudah ada saat ini otomatis berstatus **Approved** — tidak perlu disetujui ulang.

**2. Persetujuan bulan kini punya halaman sendiri.**
Dulu hanya satu tombol. Sekarang setiap bulan saving adalah satu baris data dengan status
Draft → Approved, lengkap dengan siapa yang menyetujui dan kapan.

**3. Membuka kembali bulan yang sudah ditutup harus disertai alasan**, dan alasan itu tercatat serta
terlihat oleh semua orang. Sebelumnya bulan yang sudah ditutup bisa terbuka lagi tanpa catatan apa pun.

**4. Hak akses tidak lagi diatur lewat tabel master.**
Siapa yang boleh melihat semua departemen, menyetujui, atau menghapus, sekarang diatur lewat role
aplikasi. **Konsekuensinya: role harus diberikan terlebih dahulu sebelum tanggal cut-over.** Bila belum,
pengguna yang selama ini berstatus admin akan kehilangan akses pada hari pertama. Daftar penerima role
mohon dikonfirmasi ke [koordinator CI / IT].

**5. Daftar proyek kini dibagi per halaman.**
Tidak lagi menampilkan seluruh baris sekaligus, supaya halaman tetap ringan. Bila perlu melihat semua
data sekaligus, gunakan fitur export.

**6. Export tidak langsung terunduh.**
File diproses di belakang layar; setelah selesai akan muncul notifikasi berisi tautan unduh. Tiga jenis
export tetap tersedia: horizontal, vertikal, dan pivot tahun × bulan.

**7. Nilai dropdown bisa dikelola sendiri.**
Group, status, departemen, frequency, dan lain-lain sekarang punya halaman perawatan sendiri. Tidak
perlu lagi meminta IT untuk menambahkannya.

**8. Menghapus proyek kini berupa hak akses tersendiri**, dan hanya berlaku untuk proyek berstatus Draft.
Proyek yang sudah disetujui tidak dapat dihapus.

## Yang tidak berubah

- Cara mengisi saving tetap sama: ketik langsung di baris proyek, tersimpan otomatis.
- Tiga kolom bulan berjalan, filter departemen/group/status, pencarian, dan detail saving per proyek
  tetap seperti sebelumnya.
- Angka yang tampil sama persis dengan aplikasi lama — sudah diverifikasi terhadap data sebenarnya.
- Proyek ACTIVE tetap otomatis berubah menjadi FINISH ketika tanggal saving-end terlewat.

## Yang kami mohon dari Bapak/Ibu

1. **Sebelum [tanggal]:** konfirmasi daftar PIC tiap departemen beserta hak aksesnya kepada [nama/kontak].
2. **Setelah [tanggal]:** gunakan alamat baru di atas. Bookmark halaman lama mohon diperbarui.
3. Bila menemukan angka atau perilaku yang berbeda dari aplikasi lama, mohon segera sampaikan ke
   [nama/kontak] agar dapat kami periksa.

Sesi pengenalan singkat dapat kami adakan bila diperlukan — silakan hubungi [nama/kontak].

Terima kasih atas perhatian dan kerja samanya.

Hormat kami,
[Nama]
[Jabatan] — [Departemen]
PT. Mutu Gading Tekstil
