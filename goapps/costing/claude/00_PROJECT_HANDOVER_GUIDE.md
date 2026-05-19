# 📘 Project Handover Guide
## Costing Workflow Suite — Claude Team Plan Workflow

> **Audience:** IT Leader, Tech Lead, Development Team
> **Purpose:** Panduan end-to-end menggunakan Claude Team Plan untuk handling project IT secara proper

---

## 🎯 The Big Picture

Project IT yang kompleks (3-phase, lintas-departemen, multi-team) butuh **3 lapisan konteks** di Claude:

```
┌────────────────────────────────────────────────────────┐
│  LAYER 1: MASTER PROJECT (Claude Team Project)         │
│  System Prompt: identity, tech stack, coding standard  │
│  Knowledge: PRDs, ERDs, glossary, all .md files        │
│  Pengguna: SEMUA tim (IT Leader, Dev, QA, DevOps)      │
└────────────────────────────────────────────────────────┘
                           ↓
┌────────────────────────────────────────────────────────┐
│  LAYER 2: PHASE ADDENDUM (per chat conversation)       │
│  Append ke awal chat saat fokus 1 phase                │
│  Berisi: scope phase, gotchas, integration points      │
└────────────────────────────────────────────────────────┘
                           ↓
┌────────────────────────────────────────────────────────┐
│  LAYER 3: TASK CONTEXT (per chat message)              │
│  Issue link, PRD section, file yang sedang diedit      │
│  Granular sesuai task                                  │
└────────────────────────────────────────────────────────┘
```

---

## 📦 What's In This Package

| File | Layer | Pengguna | Frekuensi Update |
|------|-------|----------|------------------|
| `01_MASTER_PROJECT_PROMPT.md` | 1 | Semua tim | Quarterly (saat ada major change) |
| `02_PHASE_A_ADDENDUM.md` | 2 | Tim yang kerja di Phase A | Saat ada PRD update |
| `03_PHASE_B_ADDENDUM.md` | 2 | Tim yang kerja di Phase B | Saat ada PRD update |
| `04_PHASE_C_ADDENDUM.md` | 2 | Tim yang kerja di Phase C | Setelah PRD Phase C selesai |
| `PRD_PhaseA_ProductRequest.md` | Knowledge | Semua tim | Saat ada perubahan requirement |
| `PRD_PhaseB_ProductOrder.md` | Knowledge | Semua tim | Saat ada perubahan requirement |

---

## 🚀 Step-by-Step Setup

### Step 1 — Create Claude Project (1x setup, IT Leader)

1. Login ke Claude Team Plan
2. **Projects** → **+ New Project**
3. Name: `Costing Workflow Suite`
4. Description: `IT project untuk costing workflow — Phase A/B/C`
5. Click **Project Instructions** → paste isi `01_MASTER_PROJECT_PROMPT.md`
6. Click **Add Content** (knowledge base) → upload:
   - `PRD_PhaseA_ProductRequest.md`
   - `PRD_PhaseB_ProductOrder.md`
7. **Share with team:** invite semua anggota (IT Leader, Dev, QA, DevOps)

> ✅ Setelah ini, SEMUA tim punya context yang sama saat buka Project ini.

---

### Step 2 — Setup GitHub Repository (1x setup, Tech Lead)

```bash
# Buat repo (atau pakai existing)
gh repo create costing-workflow-suite --private

cd costing-workflow-suite

# Setup folder structure
mkdir -p docs/{phases,architecture,standards}
mkdir -p apps/{frontend,backend}
mkdir -p packages/{shared-types,shared-utils,db-migrations}
mkdir -p infrastructure/{docker,scripts}
mkdir -p .github/{ISSUE_TEMPLATE,workflows}

# Copy PRDs ke /docs/phases
cp /path/to/PRD_PhaseA_ProductRequest.md docs/phases/
cp /path/to/PRD_PhaseB_ProductOrder.md docs/phases/

# Commit
git add .
git commit -m "docs: initial PRD Phase A & B"
git push -u origin main
```

> 💡 Sekarang PRD ada di GitHub sebagai single source of truth. Semua perubahan PRD = via PR.

---

### Step 3 — IT Leader Workflow (Brainstorming & PRD)

**Scenario 1: Refine PRD existing**

Buka chat baru di Costing Workflow Suite Project:

```
[Chat input]
Saya mau revisi PRD Phase A — perlu tambah requirement untuk
mobile push notification. Tolong:
1. Identifikasi section PRD yang perlu di-update
2. Draft perubahan dalam markdown
3. Highlight impact ke section lain
```

Claude punya konteks lengkap (Master Prompt + PRD di knowledge base) → respond dengan referensi ke FR/section yang tepat.

Setelah approved, IT Leader push perubahan ke GitHub:

```bash
git checkout -b docs/phaseA-push-notification
# edit docs/phases/PRD_PhaseA_ProductRequest.md
git add docs/phases/PRD_PhaseA_ProductRequest.md
git commit -m "docs(phaseA): add mobile push notification requirement (FR-12.1)"
git push origin docs/phaseA-push-notification
gh pr create --title "PRD update: push notification" --body "Tambah FR-12.1"
```

**Scenario 2: Create GitHub Issues dari PRD**

Pakai **Claude Code** (CLI):

```bash
cd costing-workflow-suite
claude
```

Lalu di Claude Code:

```
Baca docs/phases/PRD_PhaseA_ProductRequest.md. Untuk setiap FR di Section 6,
buat GitHub Issue dengan format:
- Title: [Phase A] FR-XX: <description>
- Labels: phase-a, feature, sprint-block-<N>
- Body: copy AC dari PRD + tech notes

Gunakan gh CLI. Confirm dulu sebelum eksekusi.
```

Claude Code akan list semua issue yang akan dibuat, minta confirm, baru execute. Bisa puluhan issue ter-create dalam menit.

---

### Step 4 — Dev Team Workflow (Coding from PRD)

**Tiap developer:**

1. **Buka Claude Project** "Costing Workflow Suite" (sudah shared)
2. **Mulai chat baru** untuk task spesifik
3. **Paste Phase Addendum** ke awal chat (mis. `02_PHASE_A_ADDENDUM.md` jika kerja Phase A)
4. **Tambahkan task context:**

```
Saya kerja di [Phase A] [Issue #42]: "Implement Submit Request Form (FR-1)".

PRD reference: docs/phases/PRD_PhaseA_ProductRequest.md, section 6.1 FR-1.

Tech context:
- React 18 + TypeScript + Tailwind
- Backend: Node.js + Express
- DB: PostgreSQL via Prisma ORM

Mulai dengan:
1. Buat backend endpoint POST /api/product-requests
2. Buat Prisma schema untuk product_request table
3. Buat React form component dengan validasi
4. Buat unit test untuk service layer

Confirm pendekatan dulu sebelum coding.
```

Claude punya context lengkap → bisa generate code yang sesuai standard.

**Saat siap commit:**

```bash
git checkout -b feature/phaseA-CWS-42-submit-request-form
# ...coding...

# Pakai Claude Code untuk bantu commit yang well-formatted
claude
> "Review uncommitted changes saya, buatkan commit message conventional commit yang proper"
> "Buatkan PR description yang reference issue #42 dan FR-1"
```

---

### Step 5 — QA Workflow

QA Engineer buka chat di Project yang sama:

```
Buatkan test plan untuk Phase A FR-1 (Submit Request).

Berdasarkan acceptance criteria:
[paste AC dari PRD atau dari issue]

Output yang saya butuh:
1. Happy path test cases (Gherkin format)
2. Edge cases (validation failures, race conditions)
3. Negative test cases (invalid input, permission denied)
4. Performance test scenarios (response time < 800ms target)
```

Claude generate test plan yang reference PRD secara akurat. QA save ke `docs/qa/PhaseA_FR1_TestPlan.md`.

---

### Step 6 — Code Review Workflow

Reviewer buka chat dengan **Phase Addendum + diff**:

```
Phase A code review. PR #87, implements FR-1 Submit Request.

Diff:
```diff
[paste diff atau pakai gh pr diff 87 | pbcopy]
```

Tolong review terhadap:
1. Compliance dengan PRD FR-1 acceptance criteria
2. Coding standard (Master Prompt rules)
3. Test coverage adequate
4. Security concerns (input validation, SQL injection, etc)
5. Performance concerns

Format output: 🔴 MUST FIX / 🟡 SHOULD FIX / 🟢 SUGGESTION / ✅ GOOD
```

---

## 🔄 Daily Workflow Cheatsheet

| Aktivitas | Tool | How |
|-----------|------|-----|
| Brainstorming requirement | Claude (web) | Buka project chat, diskusi natural |
| Generate/update PRD | Claude (web) | Output markdown, push ke GitHub |
| Buat GitHub Issues dari PRD | Claude Code | CLI, batch operation |
| Coding new feature | Claude (web) atau Claude Code | Web untuk discussion-heavy, Code untuk hands-on |
| Code review | Claude (web) | Paste diff, dapat structured feedback |
| Write test plan | Claude (web) | Dari AC, generate Gherkin |
| Generate CI/CD config | Claude Code | Baca repo structure, generate YAML |
| Update CHANGELOG | Claude Code | Read merged PRs, append release notes |
| Debug production issue | Claude (web) | Paste error log + relevant code |
| Onboarding new dev | Claude (web) | Dev join project, langsung ada context |

---

## 📊 Konversi PRD: Word → Markdown

PRD Anda yang Word, sekarang sudah saya konversi ke markdown dengan format sama.

**Keuntungan markdown:**
1. **Version controllable** — diff per kata di GitHub
2. **PR-able** — perubahan PRD via review process
3. **Searchable** — `git grep "FR-1"` langsung
4. **Tool-friendly** — bisa di-read Claude, GitHub Copilot, dll
5. **Renderable** — GitHub render markdown jadi UI yang readable

**Saat update PRD ke depannya:**

Option A — Manual edit di markdown:
```bash
# Edit di VS Code atau editor favorit
code docs/phases/PRD_PhaseA_ProductRequest.md
```

Option B — Lewat Claude:
```
Tolong update PRD Phase A section 6.1 FR-1.
Tambahkan field baru: "expected_quote_currency" (USD/IDR/EUR).
Update juga data model section 7.1.1 untuk reflect kolom baru.

Output: full section yang berubah dalam markdown.
```

Lalu paste ke file, commit, push.

---

## ⚠️ Anti-Patterns to Avoid

❌ **Setiap dev pakai chat tanpa Project** → context drift, inconsistency

❌ **PRD masih di Word/Google Doc setelah project mulai** → susah version control, dev pakai versi outdated

❌ **System prompt di-copy paste tiap chat** → tidak konsisten, makan token

❌ **Tidak ada Phase Addendum saat lintas-phase** → Claude bisa konfuse antara Phase A vs B

❌ **Issue di GitHub tidak link ke FR/section PRD** → dev coding tanpa context PRD

❌ **Skip code review by Claude** → miss issue yang human reviewer juga miss

❌ **Generate code 100+ lines tanpa baca PRD section yang relevan** → fitur tidak match acceptance criteria

---

## 🎓 Tips untuk IT Leader

### Tip 1: Update Master Prompt periodic
Tiap quarter, review Master Prompt dan update:
- Tech stack changes (library upgrade, new tool)
- Lessons learned (gotchas yang ketemu di field)
- Process improvements

### Tip 2: Track usage di Claude Team Plan admin
Lihat seat usage, message volume, monitor heavy users (mungkin perlu training tambahan).

### Tip 3: Pakai "Style" di Claude
Set Style di Settings untuk match tone Anda:
- "Concise & technical" untuk tim engineering
- "Explanatory" saat onboarding

### Tip 4: Knowledge base hygiene
- Maksimal 5-10 file utama di Project knowledge base
- File besar (>500 lines) pertimbangkan split per section
- Update file di knowledge base saat ada perubahan signifikan (jangan stale)

### Tip 5: Backup strategy
- Master Prompt + Phase Addendums di GitHub `/docs/claude/`
- PRDs di GitHub `/docs/phases/`
- Jangan rely solely on Claude Project — jika ada disaster, rebuild dari GitHub

---

## 📈 Maturity Model

Di mana team Anda sekarang? Roadmap improvement:

**Level 1 — Ad-hoc** (Anda mungkin di sini sekarang)
- PRD di Word/Google Doc
- Tiap dev pakai Claude personal
- Inkonsistensi tinggi

**Level 2 — Shared Context** (Goal jangka pendek, ~1 minggu)
- Master Project setup
- PRD di markdown + GitHub
- Tim shared Project

**Level 3 — Process Discipline** (Goal jangka menengah, ~1 bulan)
- Phase Addendum dipakai konsisten
- Issue auto-link ke PRD section
- PR description WAJIB reference FR

**Level 4 — Automation** (Goal jangka panjang, ~3 bulan)
- Claude Code untuk routine tasks (commit, PR, CHANGELOG)
- CI/CD include Claude review automated
- Metrics tracking (cycle time per PR, defect rate)

**Level 5 — Continuous Improvement** (Mature state)
- Retrospective tiap sprint termasuk Claude usage review
- Master Prompt iterative refinement based on data
- Cross-project pattern sharing

---

## ❓ FAQ

**Q: Berapa lama setup awal?**
A: 1-2 hari untuk IT Leader (Master Prompt + GitHub + onboarding session). Tim sudah bisa produktif minggu pertama.

**Q: Cocok untuk team size berapa?**
A: 3-30 orang. Di bawah 3 mungkin overkill. Di atas 30 perlu sub-project per modul.

**Q: Bagaimana jika tech stack pindah di tengah jalan?**
A: Update Master Prompt section "Tech Stack", commit ke GitHub, notify team. Claude Project knowledge base auto-update saat file di-upload ulang.

**Q: PRD masih bisa di-edit manual setelah jadi markdown?**
A: Bisa banget. Markdown = plain text. Edit di VS Code, GitHub web editor, atau via Claude.

**Q: Bagaimana handle confidentiality untuk PRD?**
A: Claude Team Plan punya privacy policy yang OK untuk most enterprise use. Untuk extra security, jangan upload data yang super-sensitive (mis. customer secret, password) ke knowledge base.

**Q: Bisa pakai untuk project lain selain costing?**
A: Yes — replicate struktur. Tiap project: 1 Master Prompt + N Phase Addendums + PRD markdown.

---

## 🔗 References & Next Steps

**Sekarang:**
1. Setup Claude Project pakai `01_MASTER_PROJECT_PROMPT.md`
2. Upload PRD markdown ke knowledge base
3. Setup GitHub repo dengan struktur di Master Prompt
4. Onboarding session dengan team (1 jam)

**Minggu 1:**
- Tim mulai pakai Project untuk task harian
- Buat 5-10 GitHub Issues sample (dari PRD Phase A Sprint Block 1)
- First PR yang full process (issue → branch → coding → review → merge)

**Minggu 2-4:**
- Sprint Block 1 Phase A development
- Collect feedback tim, refine Master Prompt
- Document lessons learned

**Bulan 2-3:**
- Continue Phase A sprints
- Start prep PRD Phase C (workshop dengan departemen)
- Setup automation (CI/CD, Claude Code workflows)

**Bulan 3+:**
- Phase A go-live
- Phase B kick-off (PRD sudah final)
- Phase C PRD development paralel

---

*Project Handover Guide v1.0 — Costing Workflow Suite*
*Created with Claude Team Plan workflow best practices*
