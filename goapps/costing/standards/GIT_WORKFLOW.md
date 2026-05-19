# Git Workflow Guide
## Costing Workflow Suite

> **Audience:** Semua tim (IT Leader, Tech Lead, Developer, QA)
> **Versi:** 1.0 | Last Updated: 2026-05

---

## Branch Structure

```
main          ← production-ready, protected
  └── develop ← staging, protected
        └── docs/[deskripsi]       ← PRD update
        └── feature/[phase]-[id]-[deskripsi]  ← implementasi fitur
        └── fix/[phase]-[id]-[deskripsi]      ← bugfix
        └── chore/[deskripsi]                 ← config, tooling
```

**Branch protection rules:**
- `main` dan `develop` — tidak bisa push langsung, harus via PR
- PR ke `main` → minimal 1 approval dari **IT Leader atau Tech Lead**
- PR ke `develop` → minimal 1 approval dari siapapun di tim

---

## Skenario 1: IT Leader Mau Update PRD

### Langkah-langkah

**Step 1 — Cerita ke Claude dulu (di Claude Project)**

Buka Claude Project "Costing Workflow Suite", mulai chat baru:

```
Saya mau tambah field baru di tabel product_request (Phase A).
Field: expected_currency — untuk menandai mata uang yang diharapkan customer (USD/IDR/EUR).

Tolong draft:
1. Perubahan di Section 7.1.1 (kolom baru di tabel product_request)
2. Perubahan di Section 6.1 FR-1 (tambah ke optional fields)
3. Update Change Log
```

Claude akan output potongan markdown yang siap di-paste.

**Step 2 — Edit PRD di GitHub**

```bash
# Buat branch baru dari develop
git checkout develop
git pull origin develop
git checkout -b docs/phaseA-add-expected-currency

# Edit file PRD
# Bisa pakai VS Code, atau langsung di GitHub web editor
code docs/phases/PRD_PhaseA_ProductRequest.md

# Setelah edit, commit
git add docs/phases/PRD_PhaseA_ProductRequest.md
git commit -m "docs(phaseA): add expected_currency field to product_request (FR-1)"
git push origin docs/phaseA-add-expected-currency
```

**Step 3 — Buat Pull Request**

```bash
gh pr create \
  --base develop \
  --title "docs(phaseA): add expected_currency field" \
  --body "Menambah field expected_currency di product_request untuk support multi-currency customer. Closes #[nomor issue kalau ada]"
```

Atau via GitHub web: buka repo → klik "Compare & pull request".

**Step 4 — Tim review di PR**

Developer baca PR dan bisa komentar langsung di baris yang berubah:

```
💬 Dev comment di baris kolom expected_currency:
"Tabel currency_master sudah ada di sistem ERP dengan kode CURR_CODE.
Apakah kita FK ke sana atau tetap ENUM (USD/IDR/EUR)?"
```

**Step 5 — Diskusi dan revisi**

IT Leader bisa bawa komentar developer ke Claude:

```
Dev bilang currency_master sudah ada di ERP.
Apakah sebaiknya FK ke tabel itu, atau kita pakai ENUM dulu di Phase A
dan defer integrasi ke later?
```

Claude bantu analisa trade-off → IT Leader putuskan → update PR.

**Step 6 — Merge**

Setelah semua approved: **IT Leader atau Tech Lead** yang merge ke `develop`.

---

## Skenario 2: Developer Nemu Gap di PRD

Misalnya developer sedang coding dan nemu sesuatu yang tidak terdefinisi di PRD.

**Step 1 — Buka GitHub Issue**

Gunakan template **"PRD Gap / Question"**:

```
GitHub → Issues → New Issue → "📋 PRD Gap / Question"
```

Isi dengan jelas:
- Apa yang tidak ada di PRD
- Kondisi existing system yang relevan
- Opsi yang mungkin (kalau bisa)
- Apakah blocking atau non-blocking

**Step 2 — IT Leader bawa ke Claude**

IT Leader buka Claude Project, paste isi issue:

```
Developer buka issue ini di GitHub:
[paste isi issue]

Bantu saya:
1. Analisa opsi mana yang paling proper
2. Draft update PRD untuk menutup gap ini
```

**Step 3 — IT Leader update PRD via PR**

Sama seperti Skenario 1 — buat branch `docs/`, edit PRD, buat PR, mention issue yang terkait:

```bash
git commit -m "docs(phaseA): clarify routing_draft behavior on cancel (closes #42)"
```

---

## Skenario 3: Developer Mau Implement Setelah PRD Di-merge

**Step 1 — Buat feature branch dari develop**

```bash
git checkout develop
git pull origin develop
git checkout -b feature/phaseA-CWS-42-expected-currency-field
```

Naming: `feature/[phase]-[issue-id]-[deskripsi-singkat]`

**Step 2 — Coding**

Developer buka Claude Project, paste Phase Addendum Phase A, lalu:

```
Saya implement issue #42: tambah field expected_currency di product_request.

PRD reference: FR-1, Section 7.1.1.
Expected behavior: field optional, nilai: USD / IDR / EUR, default NULL.

Tolong buatkan:
1. Prisma migration untuk tambah kolom
2. Update TypeScript type ProductRequest
3. Update POST /api/product-requests untuk terima field ini
4. Unit test untuk validasi nilai enum
```

**Step 3 — Commit dan PR**

```bash
git add .
git commit -m "feat(phaseA): add expected_currency field to product_request (#42)"
git push origin feature/phaseA-CWS-42-expected-currency-field

gh pr create \
  --base develop \
  --title "feat(phaseA): add expected_currency field (#42)"
```

**Step 4 — Code Review**

Reviewer (Tech Lead atau IT Leader) review pakai Claude:

```
Review PR ini untuk Phase A (FR-1).
[paste diff]

Cek:
1. Apakah implementation sesuai PRD?
2. Apakah ada security/validation concern?
3. Apakah test coverage cukup?
```

---

## Quick Reference: Siapa Bisa Apa

| Aksi | IT Leader | Tech Lead | Developer | QA |
|------|-----------|-----------|-----------|-----|
| Merge PRD ke `develop` | ✅ | ✅ | ❌ | ❌ |
| Merge PRD ke `main` | ✅ | ✅ | ❌ | ❌ |
| Merge code ke `develop` | ✅ | ✅ | setelah 1 approval | ❌ |
| Buka Issue (gap/bug/feature) | ✅ | ✅ | ✅ | ✅ |
| Buat PR (docs/feature/fix) | ✅ | ✅ | ✅ | ❌ |
| Review PR | ✅ | ✅ | ✅ | ✅ |

---

## Commit Message Convention

Format: `type(scope): deskripsi singkat (#issue)`

| Type | Kapan dipakai |
|------|--------------|
| `docs` | Update PRD, README, dokumentasi |
| `feat` | Fitur baru |
| `fix` | Bugfix |
| `refactor` | Restrukturisasi kode tanpa fitur baru |
| `test` | Tambah/update test |
| `chore` | Config, tooling, dependency |
| `migration` | Database migration |

Scope pakai nama phase: `phaseA`, `phaseB`, `phaseC`, atau nama modul.

**Contoh:**
```
docs(phaseA): add expected_currency to product_request FR-1 (#42)
feat(phaseA): implement expected_currency field (#42)
fix(phaseA): validate currency enum before save (#55)
migration(phaseA): add expected_currency column to product_request
```

---

## Label Convention di GitHub

| Label | Warna | Dipakai untuk |
|-------|-------|--------------|
| `prd-gap` | 🟡 Yellow | Issue yang nemu gap/ambigu di PRD |
| `needs-decision` | 🔴 Red | Butuh keputusan IT Leader sebelum lanjut |
| `phase-a` | 🔵 Blue | Scope Phase A |
| `phase-b` | 🟣 Purple | Scope Phase B |
| `phase-c` | 🟢 Green | Scope Phase C |
| `feature` | ⬜ White | Implementasi fitur |
| `bug` | 🔴 Red | Bug |
| `blocking` | 🔴 Red | Blocking development |
| `prd-update` | 🟠 Orange | PR yang mengubah PRD |

---

## Tips

**Buat IT Leader:**
- Pantau Issues dengan label `needs-decision` — ini yang butuh perhatian Anda
- Kalau developer comment di PR dan butuh keputusan, bawa ke Claude dulu sebelum reply

**Buat Developer:**
- Kalau gap **blocking** → buka Issue langsung, tag IT Leader
- Kalau gap **non-blocking** → bisa tetap coding dengan asumsi, tulis asumsi di Issue, lanjut
- Jangan merge PRD sendiri walau sudah approved — itu hak IT Leader/Tech Lead
- Satu PR untuk satu concern (jangan gabung fitur berbeda)

**Buat semua:**
- Diskusi teknis di Issue/PR comment — bukan di WhatsApp/email
- Kalau diskusi panjang di luar GitHub, simpan kesimpulannya ke Issue comment
