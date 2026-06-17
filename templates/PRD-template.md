---
title: "PRD – [Nama Sistem / Fitur]"
project: "[Nama Project]"
repo: "[nama-repo]"
stack: "[Laravel+Oracle | Go+PostgreSQL | Next.js | Full-stack]"
version: "0.1"
status: "Draft"
author: "[Nama]"
date: "YYYY-MM-DD"
last_updated: "YYYY-MM-DD"
related_prd: []
related_erd: ""
---

# PRD – [Nama Sistem / Fitur]
## PT Mutu Gading Tekstil

> ⚠️ **Sebelum mengisi template ini, pastikan semua item di checklist ini sudah clear:**
>
> - [ ] Stakeholder utama sudah diidentifikasi
> - [ ] Problem statement sudah divalidasi ke user langsung
> - [ ] Stack teknologi sudah ditentukan
> - [ ] Integrasi dengan sistem lain sudah dipetakan
> - [ ] Open items tidak ada yang blocking (atau sudah dicatat di Section 11)
>
> **Jika ada yang belum clear → jangan lanjut. Lakukan brainstorming dulu.**

---

## 1. Executive Summary

> 2–4 kalimat: apa yang dibangun, kenapa dibutuhkan, dampak bisnis utamanya.
> Harus bisa dibaca oleh non-technical stakeholder (Management, Finance, PPC).

[isi di sini]

---

## 2. Background & Problem Statement

### 2.1 Konteks Bisnis

> Jelaskan posisi sistem ini dalam operasional PT Mutu Gading.
> Bagaimana proses saat ini berjalan? Siapa yang terlibat?

[isi di sini]

### 2.2 Masalah yang Diselesaikan

> Masalah spesifik yang dialami user saat ini.
> Lebih baik pakai data atau contoh konkret jika ada.

| # | Masalah | Dampak |
|---|---------|--------|
| 1 | | |
| 2 | | |

### 2.3 Stakeholders

> Siapa saja yang terlibat? Apa kebutuhan utama mereka?
> ⚠️ Validasi ke user langsung sebelum finalize section ini.

| Stakeholder | Dept | Kebutuhan Utama | Akses |
|-------------|------|-----------------|-------|
| | | | |

---

## 3. Goals & Non-Goals

### 3.1 Goals (In-Scope)

> Apa yang AKAN dibangun. Spesifik dan terukur.

1.
2.
3.

### 3.2 Non-Goals (Out-of-Scope)

> Apa yang TIDAK dibangun. Penting untuk kontrol scope.

- ...
- ...

### 3.3 Success Metrics

| Metrik | Baseline Saat Ini | Target |
|--------|-------------------|--------|
| | | |

---

## 4. User Stories

> Format: Sebagai [persona], saya ingin [aksi], agar [manfaat].
> Prioritas: P0 (must have MVP) / P1 (should have) / P2 (nice to have)

| ID | Persona | User Story | Prioritas |
|----|---------|-----------|-----------|
| US-01 | | Sebagai ..., saya ingin ..., agar ... | P0 |
| US-02 | | | P1 |

---

## 5. Functional Requirements

> Setiap FR harus punya: deskripsi, input, output, business rules, dan kondisi error.
> Reference ke User Story yang relevan.
> ⚠️ Jika business rule belum clear → tandai dengan [TBD] dan masukkan ke Section 11.

### FR-01: [Nama Requirement]

- **User Story:** US-XX
- **Deskripsi:** [apa yang sistem lakukan]
- **Input:** [data apa yang masuk]
- **Output / Result:** [apa yang terjadi setelahnya]
- **Business Rules:**
  - BR-1: ...
  - BR-2: ...
- **Kondisi Error:**
  - Jika [kondisi] → [response sistem]
- **Prioritas:** P0 / P1 / P2

### FR-02: [Nama Requirement]

[...]

---

## 6. State Machine (jika ada lifecycle)

> Wajib diisi untuk modul yang punya status/lifecycle.
> Setiap transition harus punya: trigger, actor, dan kondisi.
> ⚠️ Validasi state machine ke user sebelum finalize — salah state machine = refactor besar.

### States

| State | Deskripsi | Actor yang bisa di state ini |
|-------|-----------|------------------------------|
| DRAFT | | |
| SUBMITTED | | |
| ... | | |

### Transitions

| Dari | Ke | Trigger | Actor | Kondisi |
|------|----|---------|-------|---------|
| DRAFT | SUBMITTED | Klik Submit | User | Form valid |
| ... | | | | |

```
[Diagram ASCII atau deskripsi flow]
DRAFT → SUBMITTED → ... → CLOSED
                  ↘ REJECTED
```

---

## 7. Data Model

> Column prefix naming convention WAJIB diikuti.
> ⚠️ Untuk Oracle 11g: tidak ada IDENTITY, JSON → CLOB, BOOLEAN → NUMBER(1).
> ⚠️ Untuk PostgreSQL: BIGSERIAL PK, TIMESTAMPTZ, JSONB OK.

### 7.1 Prefix Registry

| Prefix | Table Name | Category |
|--------|-----------|---------|
| XXX_ | [nama_tabel] | Core / Master / Config / Audit |

### 7.2 Tabel Utama

#### [nama_tabel] (XXX_)

| Column | Type | Notes |
|--------|------|-------|
| XXX_id | BIGSERIAL PK | |
| XXX_[field] | [type] | |
| XXX_created_at | TIMESTAMPTZ | |
| XXX_updated_at | TIMESTAMPTZ | |

> Catatan constraint, index, atau unique key yang penting.

---

## 8. Integration Points

> Sistem mana saja yang berinteraksi dengan modul ini?
> ⚠️ Konfirmasi ke tim terkait sebelum finalize — asumsi integrasi sering jadi sumber masalah.

| # | Sistem | Arah | Pattern | Notes |
|---|--------|------|---------|-------|
| INT-1 | Oracle ERP (Orion) | Read | Direct query / ETL | Tabel apa? |
| INT-2 | [sistem lain] | Read/Write | | |

### Detail per Integration Point

#### INT-1: [nama integrasi]

- **Trigger:** [kapan terjadi]
- **Data yang dibaca/ditulis:** [field apa]
- **Error handling:** [kalau gagal]

---

## 9. Non-Functional Requirements

| Kategori | Requirement | Target |
|----------|-------------|--------|
| Performance | Response time list (paginated) | < 1 detik |
| Performance | Response time detail | < 1.5 detik |
| Scalability | Max concurrent users | [isi] |
| Scalability | Max records | [isi] |
| Security | Auth | JWT / SSO Oracle |
| Security | Audit trail | Spatie Activitylog / audit_log table |
| Availability | Uptime | 99.5% |

---

## 10. MVP Scope

> Apa yang masuk MVP dan apa yang dibuang ke Phase 2+?
> Lebih sedikit lebih baik untuk MVP — fokus pada P0.

| Fitur | Prioritas | MVP? | Notes |
|-------|-----------|------|-------|
| | P0 | ✅ | |
| | P1 | ✅ | |
| | P2 | ❌ Phase 2 | |

---

## 11. Open Items

> ⚠️ Semua yang belum clear dicatat di sini.
> Jangan lanjut development untuk item yang masih open dan blocking.
> Setiap item harus punya owner dan target resolved date.

| # | Pertanyaan / Open Item | Owner | Target Date | Status |
|---|------------------------|-------|-------------|--------|
| OI-01 | | | | Open |

---

## 12. Risks & Mitigasi

| Risiko | Likelihood | Impact | Mitigasi |
|--------|-----------|--------|---------|
| | Tinggi/Sedang/Rendah | Tinggi/Sedang/Rendah | |

---

## 13. Acceptance Criteria

> Format Given/When/Then untuk setiap FR utama.
> Ini yang akan dipakai untuk QA testing.

| FR | Skenario | Given | When | Then |
|----|---------|-------|------|------|
| FR-01 | Happy path | | | |
| FR-01 | Error case | | | |

---

## 14. Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 0.1 | YYYY-MM-DD | [Nama] | Initial draft |

---

*Template: github.com/mutugading/docs-markdown/templates/PRD-template-mutugading.md*
