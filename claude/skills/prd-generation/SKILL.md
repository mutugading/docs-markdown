---
name: prd-generation
description: "Use this skill whenever the user asks to create, write, generate, or brainstorm a PRD (Product Requirements Document) or SRS (Software Requirements Specification) for any project at PT Mutu Gading Tekstil. Triggers include: 'buat PRD', 'generate PRD', 'tulis PRD', 'buat SRS', 'dokumentasikan requirement', 'buatkan spec', 'saya mau buat sistem', 'brainstorming fitur baru'. This skill enforces a checklist-first approach — Claude must clarify all open items before generating the document, and must push the final output to GitHub docs-markdown repo."
---

# PRD Generation Skill
## PT Mutu Gading Tekstil

## Purpose

Generate PRD (Product Requirements Document) yang lengkap, konsisten, dan siap dipakai developer. Skill ini memastikan:
1. Semua informasi yang dibutuhkan sudah clear sebelum dokumen ditulis
2. Format konsisten dengan template PT Mutu Gading
3. Output langsung di-push ke GitHub docs-markdown

---

## Step 1: Clarification Checklist (WAJIB sebelum generate)

Sebelum menulis satu baris pun, Claude HARUS memastikan semua item berikut sudah clear. Jika belum → tanya dulu, jangan assume.

### 1.1 Identity & Scope
- [ ] **Nama sistem/fitur** — sudah jelas dan tidak ambigu?
- [ ] **Project parent** — ini bagian dari project mana? (apps-mutugading / goapps-costing / goapps-dashboard / production-plan / new?)
- [ ] **Stack teknologi** — Laravel+Oracle? Go+PostgreSQL? Next.js? Full-stack?
- [ ] **Repo target** — code di repo mana? docs di path mana di docs-markdown?

### 1.2 Problem & Stakeholder
- [ ] **Problem statement** — sudah divalidasi ke user langsung? Atau masih asumsi?
- [ ] **Stakeholders** — siapa yang pakai sistem ini? Sudah diidentifikasi semua?
- [ ] **User pain points** — ada data/contoh konkret, atau hanya estimasi?

### 1.3 Requirement Clarity
- [ ] **Functional requirements** — sudah ada gambaran fitur utamanya?
- [ ] **State machine** — kalau ada lifecycle/status, sudah terpetakan semua state dan transitionnya?
- [ ] **Business rules** — ada aturan bisnis yang kompleks? Sudah divalidasi ke domain expert?
- [ ] **Integration** — perlu integrasi ke Oracle ERP? Phase lain? Sistem existing?

### 1.4 Technical Constraints
- [ ] **Oracle 11g constraints** (jika Laravel) — sudah dipahami? (no JSON, no IDENTITY, BOOLEAN → NUMBER(1))
- [ ] **Column prefix** — sudah ada prefix registry atau perlu dibuat baru?
- [ ] **Data volume** — estimasi jumlah records, concurrent users?

### 1.5 Open Items
- [ ] Ada pertanyaan yang belum terjawab dan blocking? → Catat di Section 11 PRD
- [ ] Ada asumsi yang belum divalidasi? → Tandai [TBD] di draft

**Jika ada item yang belum clear → STOP dan brainstorm dulu dengan user.**
**Jangan generate PRD yang setengah-setengah — lebih baik tanya dulu.**

---

## Step 2: Template & Format Rules

Gunakan template dari:
```
github.com/mutugading/docs-markdown/templates/PRD-template.md
```

### Wajib diisi (tidak boleh kosong):
- Section 1: Executive Summary
- Section 2: Background & Problem Statement
- Section 3: Goals & Non-Goals
- Section 5: Functional Requirements (minimal FR utama)
- Section 7: Data Model (dengan prefix registry)
- Section 11: Open Items (walaupun kosong, tetap ada)

### Boleh diisi partial di draft:
- Section 4: User Stories (bisa diisi setelah FR)
- Section 6: State Machine (kalau ada lifecycle)
- Section 8: Integration Points
- Section 13: Acceptance Criteria

### Rules spesifik per stack:

**Laravel + Oracle 11g:**
- Semua kolom WAJIB pakai column prefix (lihat column-prefix-naming skill)
- Tidak ada JSON type → gunakan CLOB
- Tidak ada IDENTITY → gunakan SEQUENCE + TRIGGER
- Tidak ada BOOLEAN → gunakan NUMBER(1)
- Cantumkan Oracle schema: MGTHRIS atau MGTAPPS

**Go + PostgreSQL:**
- Kolom pakai BIGSERIAL untuk PK
- TIMESTAMPTZ untuk semua timestamp
- JSONB untuk JSON data
- Cantumkan service mana (finance / iam / baru)
- Prefix registry tetap wajib untuk konsistensi

**Next.js (Frontend only):**
- Fokus ke UI component list, API endpoint yang dibutuhkan
- State management approach (TanStack Query / Zustand)
- Tidak perlu data model, cukup API contract

---

## Step 3: Output Rules

### Nama file
```
PRD_[NamaModul].md
```
Contoh: `PRD_ProductionPlan.md`, `PRD_ShortLeaveModule.md`

### Path di GitHub
```
# Untuk goapps projects:
goapps/[project-name]/PRD/PRD_[NamaModul].md

# Untuk apps-mutugading:
apps-mutugading/PRD/PRD_[NamaModul].md

# Untuk project baru:
goapps/[project-name]/PRD/PRD_[NamaModul].md
```

### Setelah generate
1. Push ke GitHub: `"Push PRD ke GitHub"`
2. Buat Quick Links task di ClickUp list yang relevan
3. **Jangan** buat ClickUp doc — GitHub adalah source of truth

---

## Step 4: Versioning

| Status | Version | Artinya |
|--------|---------|---------|
| Initial brainstorm | 0.1 | Draft awal, banyak TBD |
| Semua FR draft ada | 0.5 | Belum validated |
| Stakeholder validated | 1.0 | Siap untuk development |
| Ada perubahan signifikan | 1.x | Update setelah feedback |

---

## Step 5: Tanda PRD Siap untuk Development

PRD boleh dipakai developer untuk mulai coding jika:
- [ ] Semua FR utama (P0) sudah clear dan tidak ada [TBD]
- [ ] State machine sudah divalidasi ke domain expert
- [ ] Data model sudah punya prefix registry lengkap
- [ ] Open Items section kosong atau semua Closed
- [ ] Integration points sudah dikonfirmasi ke tim terkait
- [ ] Status di header = "Final" atau "Approved"

---

## Common Mistakes to Avoid

| Mistake | Kenapa Buruk | Yang Benar |
|---------|-------------|-----------|
| Generate PRD tanpa validasi problem ke user | PRD tidak relevant ke kebutuhan nyata | Brainstorm dulu, validasi ke stakeholder |
| State machine dibuat tanpa validasi ke domain expert | Salah state = refactor besar saat development | Tanya ke PPC/Finance/HR terkait lifecycle |
| Data model tanpa column prefix | Inkonsisten dengan konvensi existing | Selalu apply column-prefix-naming skill |
| Open items dibiarkan kosong tapi sebenarnya ada | Developer buat asumsi sendiri → gap | Catat semua uncertainty di Section 11 |
| PRD di ClickUp Docs | Duplikasi, sync manual, rawan outdated | Push ke GitHub docs-markdown saja |
| Generate semuanya sekaligus dari brief yang belum jelas | Token habis, output tidak akurat | Tanya dulu, generate bertahap |

---

## Integration with Other Skills

Saat generate PRD, otomatis juga apply:
- **column-prefix-naming** — untuk semua data model di PRD
- **prd-generation** (this skill) — untuk format dan checklist

---

## Quick Reference: Paths per Project

| Project | Code Repo | PRD Path di docs-markdown |
|---------|-----------|--------------------------|
| HRIS (Laravel) | apps-mutugading | apps-mutugading/PRD/ |
| Custom Report | apps-mutugading | apps-mutugading/custom-report-module/PRD/ |
| Costing Suite | goapps-backend/frontend | goapps/costing/PRD/ |
| Dashboard BOD | goapps-backend/frontend | goapps/dashboard/PRD/ |
| Production Plan | [baru] | goapps/production-plan/PRD/ |
| Project baru | [baru] | goapps/[nama]/PRD/ atau apps-mutugading/[nama]/PRD/ |
