# docs-markdown

Kumpulan dokumentasi dalam format Markdown untuk seluruh project **Mutugading**, mencakup berbagai jenis dokumen seperti PRD (Product Requirements Document), SRS (Software Requirements Specification), dan dokumentasi teknis lainnya.

---

## 📁 Struktur Direktori

```
docs-markdown/
├── README.md                    # Dokumen ini
├── templates/                   # Template dokumen standar
│   ├── PRD-template.md
│   └── SRS-template.md
├── apps-mutugading/             # Dokumentasi untuk project apps-mutugading
│   ├── README.md
│   ├── PRD/
│   └── SRS/
└── goapps/                      # Dokumentasi untuk project goapps
    ├── README.md
    ├── PRD/
    └── SRS/
```

---

## 📋 Daftar Project

| Project | Deskripsi | Direktori |
|---------|-----------|-----------|
| apps-mutugading | Aplikasi utama Mutugading | [apps-mutugading/](./apps-mutugading/) |
| goapps | Aplikasi berbasis Go untuk Mutugading | [goapps/](./goapps/) |

---

## 📝 Jenis Dokumen

- **PRD** – Product Requirements Document: Mendeskripsikan kebutuhan produk dari sudut pandang bisnis dan pengguna.
- **SRS** – Software Requirements Specification: Mendeskripsikan kebutuhan teknis dan fungsional sistem secara detail.
- **Docs** – Dokumentasi teknis umum: Panduan penggunaan, arsitektur sistem, API, dan lain-lain.

---

## 🚀 Cara Berkontribusi

1. Fork repository ini.
2. Buat branch baru dengan nama yang deskriptif (contoh: `feat/prd-apps-mutugading-v2`).
3. Gunakan template yang tersedia di folder [`templates/`](./templates/) sebagai titik awal dokumen baru.
4. Simpan dokumen di folder project yang sesuai, di bawah subfolder jenis dokumennya (misalnya `apps-mutugading/PRD/`).
5. Ajukan Pull Request dengan deskripsi yang jelas.

---

## 📐 Konvensi Penamaan File

- Gunakan format **kebab-case** untuk nama file: `nama-fitur-v1.md`
- Sertakan versi di nama file jika dokumen memiliki revisi: `prd-auth-v2.md`
- Tanggal opsional bisa ditambahkan di bagian dalam dokumen pada field metadata.

---

## 🔗 Referensi

- [Mutugading Organization](https://github.com/mutugading)
