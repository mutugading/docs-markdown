# Gap Log — PRD vs Implementasi Aktual
## Production Plan System — PT Mutu Gading Tekstil

> Dokumen ini mencatat semua perbedaan antara PRD yang ditulis
> dan implementasi aktual di kode. Bukan berarti PRD salah —
> ini adalah natural evolution dari requirement ke implementasi.
>
> **Update oleh:** Developer (saat temukan gap)
> **Approve oleh:** IT Leader (Indra)
> **Review berkala:** Setiap sprint review

---

## Active Gaps

| ID | PRD Section | PRD Intent | Implementasi Aktual | Alasan | Status | Approved By | Tanggal |
|----|-------------|-----------|--------------------|---------|---------| ------------|---------|
| _(belum ada gap)_ | | | | | | | |

---

## Resolved Gaps (Sudah di-update ke PRD)

| ID | PRD Section | Gap | Resolusi | PR Update PRD | Tanggal |
|----|-------------|-----|----------|---------------|---------|
| _(belum ada)_ | | | | | |

---

## Template Entry Gap Baru

```
| G-[NNN] | Section [X.X] FR-[N] | [apa yang PRD bilang] | [apa yang diimplementasikan] | [kenapa berbeda] | Pending/Accepted/Resolved | [nama] | YYYY-MM-DD |
```

---

## Panduan Penggunaan

### Kapan tambah entry ke Gap Log?

Tambahkan entry ketika ada **perbedaan nyata** antara:
- Apa yang tertulis di PRD (acceptance criteria, business rule, data model)
- Apa yang diimplementasikan di kode

### Kategori gap yang umum terjadi

**Simplification (paling umum)**
PRD mendefinisikan fitur A, developer implementasi versi lebih simple dulu untuk MVP.
```
PRD:  "Auto-calculate intermediate deadline dari semua FG terkait"
Impl: "Deadline intermediate diinput manual dulu, auto-calc Phase 2"
```

**Technical constraint**
Implementasi perlu berbeda karena constraint teknis yang tidak terpikirkan saat PRD.
```
PRD:  "Real-time update qty WO saat actual production masuk"
Impl: "Polling setiap 30 detik — WebSocket terlalu kompleks untuk MVP"
```

**Scope creep (perlu di-flag)**
Developer tambah fitur yang tidak ada di PRD.
```
PRD:  Tidak ada mention export Excel
Impl: Developer tambah export Excel "karena user pasti butuh"
→ Ini harus di-approve IT Leader dulu sebelum diimplementasikan
```

**PRD ambiguity**
PRD tidak cukup detail, developer buat asumsi.
```
PRD:  "Sistem kirim notifikasi saat WO selesai"
Impl: Developer asumsikan email saja, padahal user butuh in-app juga
→ Buka ADR untuk clarify
```

### Status gap

| Status | Artinya |
|--------|---------|
| **Pending** | Developer sudah catat, menunggu review IT Leader |
| **Accepted** | IT Leader sudah approve gap ini boleh ada |
| **Rejected** | IT Leader minta implementasi sesuai PRD |
| **Resolved** | PRD sudah di-update untuk reflect implementasi aktual |

### Aturan Penting

1. **Gap "Pending" tidak boleh dibiarkan > 2 hari kerja** — IT Leader harus respond
2. **Gap "Rejected"** → developer harus revisi implementasi sesuai PRD
3. **Gap "Accepted"** → PRD harus di-update dalam sprint yang sama
4. **Scope creep selalu Rejected** kecuali ada diskusi dan approve IT Leader lebih dulu

---

*Source: github.com/mutugading/docs-markdown/goapps/production-plan/decisions/GAP_LOG.md*
*Version: 1.0 | Juni 2026 | Owner: IT Leader*
