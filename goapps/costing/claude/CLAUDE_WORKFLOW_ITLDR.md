# Claude Workflow Guide — IT Leader
## Cara Pakai Claude untuk Update PRD & Sambungkan ke GitHub

> Simpan file ini di `docs/claude/CLAUDE_WORKFLOW_ITLDR.md`
> Versi: 1.0 | Last Updated: 2026-05

---

## Konsep Dasar

Setiap update apapun ke project, alurnya selalu:

```
Pikiran Anda
    ↓
Cerita ke Claude (di Project chat)
    ↓
Claude draft perubahan (markdown)
    ↓
Anda review, copy, paste ke file
    ↓
Push ke GitHub sebagai PR
    ↓
Tim diskusi di PR / Issue
    ↓
Merge → jadi official
```

Claude bukan yang push ke GitHub. Claude tugasnya **draft** — Anda yang decide dan push.

---

## Prompt Siap Pakai

### A. Tambah Kolom Baru di Tabel

Gunakan saat: Anda mau menambah informasi baru di salah satu tabel Phase A/B.

```
Saya mau tambah kolom baru di Phase A.

Tabel: product_request
Kolom baru: expected_currency
Kebutuhan bisnis: customer ada yang request quote dalam USD, IDR, atau EUR.
                  Field ini optional — tidak semua request butuh ini.

Naming convention: gunakan prefix kolom dari inisial nama tabel
(product_request → prefix PR_)

Tolong draft:
1. Baris kolom baru di tabel Section 7.1.1 (format tabel markdown yang sama)
2. Dampak ke FR mana saja yang perlu diupdate
3. Satu baris untuk Change Log (versi +0.1 dari versi saat ini)

Jangan output seluruh PRD — cukup bagian yang berubah saja.
```

---

### B. Tambah Tabel Baru

Gunakan saat: ada entitas baru yang perlu disimpan.

```
Saya mau tambah tabel baru di Phase A.

Konteks bisnis: kami perlu tracking SLA per request — kapan deadline
tiap stage, dan apakah sudah breach atau belum.

Tolong:
1. Tentukan nama tabel yang tepat (snake_case, konsisten dengan tabel lain)
2. Tentukan prefix kolom dari inisial nama tabel baru itu
3. Cek ke prefix registry — apakah ada collision dengan tabel existing?
4. Draft definisi tabel lengkap: kolom, tipe, constraint, notes
5. Draft relasi ke tabel product_request
6. Tentukan section di PRD mana ini masuk (Section 7.1.X baru)
7. Draft baris Change Log

Tabel existing dan prefix-nya (untuk cek collision):
- product_request → PR_
- request_type → RT_
- routing_rule → RR_
- routing_draft → RD_
- routing_draft_component → RDC_
- request_comment → RC_
- request_comment_edit_history → RCEH_
- request_mention → RM_
- attachment → AT_
- user_role_mapping → URM_
- notification → NT_
- user_notification_preference → UNP_
- audit_log → AL_
```

---

### C. Update Requirement / FR yang Sudah Ada

Gunakan saat: ada perubahan pada Acceptance Criteria atau behavior yang sudah terdefinisi.

```
Saya mau update FR-1 (Submit Request) di Phase A.

Perubahan: saat ini field urgency_level hanya low/medium/high.
Bisnis minta tambah level "critical" dengan behavior khusus:
bila urgent=critical, notifikasi langsung ke Engineering Lead
tanpa perlu tunggu triage normal.

Tolong draft:
1. Update FR-1 Acceptance Criteria yang perlu berubah
2. Update FR-3 (Routing) jika ada dampak ke routing logic
3. Update FR-10/11 (Notification) untuk tambah trigger baru
4. Update data model jika ada perubahan (kolom urgency_level: VARCHAR constraint)
5. Flag: apakah ini ada impact ke Phase B atau C?
6. Draft Change Log entry

Output hanya bagian yang berubah, bukan seluruh PRD.
```

---

### D. Merespons Gap yang Ditemukan Developer

Gunakan saat: developer buka GitHub Issue dengan label `prd-gap`.

```
Developer buka GitHub Issue #[nomor] dengan konten berikut:

---
[paste isi issue dari GitHub]
---

Tolong bantu saya:
1. Analisa: apakah ini genuine gap di PRD, atau sudah tercakup di section lain?
2. Kalau genuine gap: berikan 2-3 opsi solusi dengan trade-off masing-masing
3. Rekomendasikan opsi mana yang paling align dengan design philosophy PRD kita
4. Draft update PRD untuk menutup gap ini (bagian yang perlu diubah saja)
5. Draft reply yang bisa saya post di GitHub Issue untuk acknowledge developer
```

---

### E. Merespons Komentar Developer di PR

Gunakan saat: developer kasih comment di PR dan Anda perlu respons yang well-considered.

```
Ada PR comment dari developer di PR #[nomor]:

---
[paste comment developer]
---

Context PR ini: [deskripsi singkat apa yang diubah di PR]

Bantu saya:
1. Apakah concern developer valid?
2. Kalau valid: apa implikasinya ke PRD dan design kita?
3. Draft respons yang bisa saya post di PR comment
4. Kalau perlu update PRD: draft bagian yang perlu diubah
```

---

### F. Generate GitHub Issues dari PRD

Gunakan saat: sprint planning — mau buat batch GitHub Issues dari PRD.

```
Dari PRD Phase A, Section 6 (Functional Requirements),
tolong generate daftar GitHub Issues untuk Sprint Block 1.

Sprint Block 1 mencakup FR: FR-1, FR-2, FR-19 (partial).

Untuk tiap FR, output dalam format ini:

---
ISSUE TITLE: [Phase A] FR-[N]: [deskripsi singkat]
LABELS: phase-a, feature, sprint-block-1
BODY:
**Phase:** A
**PRD Reference:** FR-[N], Section [X]
**Sprint Block:** 1

**User Story:**
Sebagai [persona], saya ingin [aksi], agar [manfaat].

**Acceptance Criteria:**
- [ ] AC1
- [ ] AC2

**Technical Notes:**
- [dari PRD]

**Estimate:** S/M/L/XL
---

Kalau 1 FR terlalu besar untuk 1 issue, split jadi sub-issues.
```

Setelah dapat output, bisa eksekusi via Claude Code:

```bash
# Di terminal, pakai gh CLI
gh issue create \
  --title "[Phase A] FR-1: Submit Product Request" \
  --label "phase-a,feature,sprint-block-1" \
  --body "[paste body dari output Claude]"
```

---

### G. Check Dampak Perubahan ke Phase Lain

Gunakan saat: sebelum merge PRD update, mau pastikan tidak ada yang kelewat.

```
Saya mau merge perubahan PRD ini ke GitHub:

[paste draft perubahan PRD]

Sebelum merge, tolong lakukan impact analysis:
1. Apakah ada dampak ke Phase B? (terutama routing_draft schema & integration)
2. Apakah ada dampak ke Phase C? (parameter pending state, future integration)
3. Apakah ada dampak ke data model lain di Phase A sendiri?
4. Apakah ada API contract yang berubah (endpoint, request/response shape)?
5. Apakah perlu update di GLOSSARY jika ada term baru?

Output: checklist yang bisa saya jadikan PR description.
```

---

## Alur Lengkap: Tambah Kolom Baru (Step by Step)

Ini contoh konkret dari awal sampai PR merged.

### Step 1 — Chat dengan Claude

Gunakan Prompt A di atas. Claude output potongan markdown.

### Step 2 — Buka file PRD di local

```bash
cd costing-workflow-suite
git checkout develop
git pull origin develop
git checkout -b docs/phaseA-add-expected-currency
code docs/phases/PRD_PhaseA_ProductRequest.md
```

### Step 3 — Paste perubahan dari Claude

Cari section yang tepat di PRD, paste baris baru dari output Claude.
Juga update Change Log di bagian bawah PRD.

### Step 4 — Commit

```bash
git add docs/phases/PRD_PhaseA_ProductRequest.md
git commit -m "docs(phaseA): add PR_expected_currency to product_request (#42)"
git push origin docs/phaseA-add-expected-currency
```

### Step 5 — Buat PR

```bash
gh pr create \
  --base develop \
  --title "docs(phaseA): add expected_currency field to product_request" \
  --label "prd-update,phase-a" \
  --body "## Apa yang Berubah
Tambah kolom \`PR_expected_currency\` di tabel \`product_request\`.

## Kenapa
Support multi-currency customer request (USD/IDR/EUR).

## Section yang Berubah
- Section 7.1.1 — tambah kolom baru
- Section 6.1 FR-1 — kolom masuk ke optional fields
- Change Log — versi 1.1

## Impact ke Phase Lain
- [ ] Tidak ada impact ke Phase B/C

Closes #42"
```

### Step 6 — Tunggu review

Developer baca PR, komentar jika ada concern.
Anda respons via PR comment (pakai Prompt E kalau perlu bantu Claude dulu).

### Step 7 — Merge

Setelah approved, Anda atau Tech Lead merge:

```bash
gh pr merge [nomor-pr] --squash --delete-branch
```

---

## Tips

**Jangan pernah edit PRD langsung di `main` atau `develop`.**
Selalu via branch + PR, sekecil apapun perubahannya.
Ini yang bikin history PRD Anda bisa ditelusuri kapanpun.

**Kalau developer minta keputusan cepat (blocking):**
Issue dengan label `blocking` + `needs-decision` = sinyal untuk Anda respons hari itu.
Jangan biarkan developer nganggur karena nunggu keputusan.

**Kalau perubahan kecil (typo, wording):**
Tetap buat PR, tapi bisa langsung approve sendiri dan merge
(tidak perlu tunggu review kalau hanya kosmetik).

**Update Claude Project knowledge base setelah PRD di-merge:**
Re-upload `PRD_PhaseA_ProductRequest.md` ke Claude Project
supaya semua tim dapat versi terbaru saat chat.
```
