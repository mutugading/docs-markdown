# Claude Workflow Guide — Developer
## Cara Pakai Claude untuk Coding dari PRD

> Simpan file ini di `docs/claude/CLAUDE_WORKFLOW_DEV.md`
> Versi: 1.0 | Last Updated: 2026-05

---

## Setup Sebelum Mulai

Setiap kali mulai coding task baru, lakukan ini:

1. Buka **Claude Project "Costing Workflow Suite"** (bukan chat biasa)
2. Mulai **chat baru** (jangan lanjut chat lama yang topiknya beda)
3. Paste **Phase Addendum** di awal chat (file `02_PHASE_A_ADDENDUM.md` untuk Phase A)
4. Baru kasih task context

Kenapa? Supaya Claude punya 3 lapisan context yang lengkap:
- Master Prompt (di Project) → tech stack, coding standard, terminology
- Phase Addendum (Anda paste) → scope phase, gotchas, integration rules
- Task context (Anda tulis) → spesifik task yang sedang dikerjakan

---

## Prompt Siap Pakai

### A. Mulai Implementasi Fitur Baru

```
[paste Phase A Addendum di sini]

---

Saya implement GitHub Issue #[nomor]: [judul issue]

PRD reference: FR-[N], Section [X.X]

Acceptance Criteria (dari issue):
- AC1: ...
- AC2: ...

Tech context saya:
- Branch: feature/phaseA-CWS-[id]-[nama]
- File yang akan saya sentuh: [list file kalau sudah tahu]

Sebelum coding, tolong:
1. Konfirmasi pemahaman kamu tentang requirement ini
2. Identifikasi file/module apa saja yang perlu dibuat/diubah
3. Flag kalau ada hal di PRD yang ambigu atau perlu klarifikasi dulu

Setelah konfirmasi, mulai dengan [bagian pertama yang paling fundamental].
```

---

### B. Generate Database Migration

```
Buatkan Prisma migration untuk perubahan schema berikut (Phase A):

Tabel: product_request
Perubahan: tambah kolom baru
  - Nama kolom: PR_expected_currency
  - Tipe: VARCHAR(3)
  - Constraint: NULL allowed
  - Check constraint: value harus NULL atau salah satu dari ('USD', 'IDR', 'EUR')

Naming convention kolom: prefix dari inisial nama tabel (PR_ untuk product_request)

Output:
1. Prisma schema update (bagian model ProductRequest yang berubah)
2. SQL migration raw (sebagai referensi)
3. Rollback SQL
```

---

### C. Nemu Gap Saat Coding — Draft GitHub Issue

Gunakan saat: Anda menemukan sesuatu yang tidak terdefinisi di PRD dan perlu eskalasi.

```
Saya sedang coding FR-[N] Phase A dan nemu hal yang tidak ada di PRD.

Situasi:
[jelaskan apa yang Anda temukan]

Kondisi existing system yang relevan:
[jelaskan tabel/kolom/logic yang sudah ada]

Bantu saya draft GitHub Issue dengan template "PRD Gap / Question":
1. Deskripsi gap yang jelas
2. Kondisi existing (sudah saya jelaskan di atas)
3. 2-3 opsi solusi yang reasonable
4. Assessment: apakah ini blocking implementasi saya atau tidak?
```

---

### D. Generate Unit Test

```
Buatkan unit test untuk fungsi/service berikut (Phase A):

[paste kode yang mau di-test]

Berdasarkan AC dari FR-[N]:
- AC1: ...
- AC2: ...

Test framework: Vitest
Coverage yang dibutuhkan:
- Happy path
- Edge cases dari AC
- Error cases (input invalid, not found, dll)

Ikuti naming: describe('[ServiceName]', () => { it('should [behavior]') })
```

---

### E. Code Review Sebelum Push PR

```
Tolong review kode ini sebelum saya buat PR (Phase A, FR-[N]):

[paste diff atau paste kode]

Cek terhadap:
1. Apakah implementation sesuai PRD FR-[N] AC?
2. Apakah ada violation coding standard dari Master Prompt?
3. Apakah ada security concern (input validation, SQL injection, auth bypass)?
4. Apakah ada performance concern?
5. Apakah test coverage adequate?

Format output:
🔴 MUST FIX — [issue]
🟡 SHOULD FIX — [issue]
🟢 SUGGESTION — [issue]
✅ GOOD — [hal yang sudah benar]
```

---

### F. Debug Error

```
Saya kena error ini di Phase A saat [konteks: e.g. submit request]:

Error:
[paste error message + stack trace]

Relevant code:
[paste kode yang relevan]

Yang sudah saya coba:
[list apa yang sudah dicoba]

Database schema terkait:
[paste schema kalau relevan]
```

---

### G. Generate PR Description

```
Saya selesai implement issue #[nomor].

Summary perubahan:
- [file 1]: [apa yang berubah]
- [file 2]: [apa yang berubah]
- [migration file]: [perubahan schema]

AC yang terpenuhi:
- [x] AC1
- [x] AC2

Tolong buatkan PR description yang mengikuti template PR project ini,
termasuk manual test scenario yang bisa dipakai reviewer.
```

---

## Kapan Buka Issue vs Langsung Tanya di Chat

| Situasi | Action |
|---------|--------|
| Gap di PRD yang **blocking** coding Anda | Buka GitHub Issue `prd-gap` + `blocking` |
| Gap di PRD yang **non-blocking** | Buka GitHub Issue `prd-gap`, lanjut coding dengan asumsi yang Anda tulis di issue |
| Pertanyaan teknis tentang cara implement | Tanya Claude di chat, tidak perlu issue |
| Existing tabel/kolom yang tidak ada di PRD | Buka GitHub Issue `prd-gap`, mention nama tabel/kolom yang sudah ada |
| Conflict antar requirement | Buka GitHub Issue `prd-gap` + `needs-decision` |

---

## Tips

**Jangan gabung beberapa FR dalam 1 PR.**
1 PR = 1 issue = 1 concern. Lebih mudah di-review dan di-rollback kalau ada masalah.

**Kalau gap non-blocking, tulis asumsi Anda di kode:**
```typescript
// ASSUMPTION: expected_currency NULL dianggap IDR (default)
// Ref: GitHub Issue #42 — menunggu keputusan IT Leader
const currency = request.PR_expected_currency ?? 'IDR';
```

**Sebelum coding, baca PRD section yang relevan dulu.**
Jangan rely 100% pada issue — issue adalah summary. Detail ada di PRD.

**Update Phase Addendum kalau nemu gotcha baru.**
Kalau Anda nemu sesuatu yang tricky dan tidak terdokumentasi, buka PR ke
`docs/claude/02_PHASE_A_ADDENDUM.md` supaya developer lain tidak kena hal yang sama.
