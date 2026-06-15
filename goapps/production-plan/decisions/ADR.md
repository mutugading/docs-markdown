# Architecture Decision Records (ADR)
## Production Plan System — PT Mutu Gading Tekstil

> ADR adalah catatan keputusan teknis signifikan yang diambil selama development,
> terutama ketika implementasi perlu berbeda dari atau tidak tercakup di PRD.
>
> **Siapa yang bisa buka ADR:** Developer
> **Siapa yang approve:** IT Leader (Indra)
> **Format nama file:** ADR-NNN_judul-singkat.md

---

## Template ADR

```
## ADR-[NNN]: [Judul Singkat]

**Status:** Proposed | Accepted | Deprecated | Superseded by ADR-XXX
**Tanggal:** YYYY-MM-DD
**Author:** [nama developer]
**Reviewer:** IT Leader

### Konteks
[Jelaskan situasi yang memaksa keputusan ini.
Apa yang ditemukan saat implementasi? Apa constraint teknisnya?]

### PRD Reference
[Section/FR mana di PRD yang terdampak atau tidak ter-cover]

### Opsi yang Dipertimbangkan

**Opsi A: [nama opsi]**
- Kelebihan: ...
- Kekurangan: ...

**Opsi B: [nama opsi]**
- Kelebihan: ...
- Kekurangan: ...

### Keputusan
[Opsi mana yang dipilih dan kenapa]

### Konsekuensi
- Dampak ke PRD: [perlu update section mana]
- Dampak ke ERD: [perlu update tabel/kolom mana]
- Dampak ke scope: [apakah ada yang masuk/keluar dari MVP]

### Action Items
- [ ] Update PRD section [X] — PR #[nomor]
- [ ] Update ERD tabel [X] — PR #[nomor]
- [ ] Notify stakeholder terdampak
```

---

## Log ADR

| # | Judul | Status | Tanggal | Author |
|---|-------|--------|---------|--------|
| ADR-001 | _(belum ada)_ | — | — | — |

---

## Panduan Penggunaan

### Kapan harus buka ADR?

Buka ADR ketika developer menemukan salah satu kondisi berikut:

| Kondisi | Contoh |
|---------|--------|
| PRD tidak cover kasus yang ditemukan | "PRD tidak specify deadline intermediate kalau ada 2 FG" |
| Implementasi teknis perlu berbeda dari yang dibahas di PRD | "PostgreSQL tidak support cara query yang di-assume PRD" |
| Ada trade-off signifikan yang perlu diketahui IT Leader | "Fitur X bisa dibuat, tapi akan impact performance Y" |
| Library/approach yang dipilih punya implikasi jangka panjang | "Kalau pakai library A, nanti susah ganti ke B" |

### Kapan TIDAK perlu ADR?

- Detail implementasi kecil yang tidak impact business logic
- Pilihan naming convention internal
- Refactoring yang tidak mengubah behavior

### Alur ADR

```
Developer temukan gap/decision point
         ↓
Buka GitHub Issue label: "adr-needed" + "prd-gap"
         ↓
Draft ADR (minimal: konteks, opsi, rekomendasi)
         ↓
IT Leader review dan pilih opsi
         ↓
ADR status → Accepted
         ↓
Update PRD/ERD via PR (jika diperlukan)
         ↓
Developer lanjut implementasi
```

### Aturan Penting

1. **Developer tidak boleh lanjut implementasi** untuk hal yang butuh ADR sebelum IT Leader approve
2. **Semua ADR masuk ke changelog PRD** — PRD selalu reflect kondisi aktual
3. **ADR tidak bisa dihapus** — hanya bisa di-Deprecated atau Superseded
4. **Response SLA:** IT Leader respond ADR Proposed dalam 1 hari kerja (kalau blocking)

---

*Source: github.com/mutugading/docs-markdown/goapps/production-plan/decisions/ADR.md*
*Version: 1.0 | Juni 2026 | Owner: IT Leader*
