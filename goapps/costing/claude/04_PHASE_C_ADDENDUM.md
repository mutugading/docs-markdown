# 📌 Phase Addendum — Phase C: Parameter Entry & Validation

> **Status:** PRD belum dimulai. Addendum ini berdasarkan referensi dari Phase A PRD.
> **Cara pakai:** Gunakan sebagai starting point saat IT Leader siap kick-off Phase C.

---

## 🎯 PHASE C FOCUS (PLANNED)

Form entry parameter cost per departemen dengan workflow validasi dan co-ownership. Phase C mengisi gap yang di Phase A masih abstract: `PARAMETER_PENDING` state.

**Goals utama (asumsi):**
1. Form entry konkret per departemen (Produksi, R&D, Finance, dll)
2. Validation rules per parameter type (field-level)
3. Co-ownership workflow: multiple departemen punya parameter berbeda untuk 1 product
4. Approval workflow per departemen
5. Integration dengan Phase B untuk attach ke product_sys_id

---

## ⚠️ PRD BELUM ADA — JANGAN ASUMSIKAN

Sebelum kick-off Phase C, IT Leader perlu produce PRD yang menjawab:

### Pertanyaan Mendasar
1. **Parameter apa saja per departemen?**
   - Produksi: yield rate, machine hour rate, scrap rate, dll?
   - R&D: development cost, sample qty, dll?
   - Finance: labor rate, overhead rate, dll?
   - Inventaris lengkap per departemen perlu dikumpulkan

2. **Field-level validation rules?**
   - Range constraints (mis. yield rate antara 0-100%)
   - Cross-field validation (mis. start_date < end_date)
   - Business rules per parameter type

3. **Workflow approval?**
   - Single-step (Dept Lead approve) atau multi-step?
   - Bisa di-revisi setelah approve atau immutable?
   - Bagaimana handle rejection (notification, comment, re-submit)?

4. **Periodisasi parameter**
   - Effective-dated atau period-based (monthly/quarterly)?
   - Backdating allowed?
   - History: bisa lihat parameter past period?

5. **Co-ownership rules**
   - 1 parameter bisa ada multiple owner?
   - Conflict resolution (mis. 2 user edit bersamaan)?
   - Notification ke co-owner saat ada perubahan?

6. **Integration dengan Phase A**
   - State transition: kapan exactly `PARAMETER_PENDING` → `PARAMETER_COMPLETE`?
   - Apakah semua parameter harus filled, atau ada minimum required?
   - Bagaimana handle parameter yang optional vs wajib?

7. **Integration dengan Phase B**
   - Parameter attached ke `product_sys_id` atau ke level lain (cyl_type, item_code)?
   - Bagaimana inherit parameter dari intermediate product?

---

## 🎨 PROPOSED ARCHITECTURE (UNTUK BAHAN DISKUSI)

### Entitas Inti (Proposal)
```
parameter_definition
  - param_def_id (PK)
  - functional_role (Produksi/RND/Finance/...)
  - param_name
  - param_code (unique)
  - data_type (number/string/date/boolean)
  - validation_rules (JSONB)
  - is_required
  - applies_to (product_sys_id / cyl_type_id / item_code_pattern)

parameter_entry
  - entry_id (PK)
  - param_def_id (FK)
  - product_sys_id (FK Phase B, nullable bila applies_to lain)
  - period (effective_from, effective_to)
  - value (JSONB — adapts ke data_type)
  - entered_by (user_id)
  - entered_at
  - status (DRAFT, SUBMITTED, APPROVED, REJECTED)

parameter_approval
  - approval_id (PK)
  - entry_id (FK)
  - approver_user_id
  - decision (APPROVE / REJECT)
  - reason
  - approved_at

parameter_entry_history
  - snapshot saat ada perubahan
```

### State Machine (Proposal)
```
DRAFT → SUBMITTED → UNDER_REVIEW → APPROVED
                                 → REJECTED → DRAFT (re-submit)
```

### Integration Event
Saat semua required parameter approved untuk 1 product_request (Phase A):
- Trigger event `parameters.complete` dengan payload `{request_id, product_sys_id, period}`
- Phase A listener: transition state `PARAMETER_PENDING` → `PARAMETER_COMPLETE`

---

## 🔗 DEPENDENCIES (CRITICAL)

Phase C **TIDAK BISA** kick-off sebelum:
1. ✅ Phase A live (state machine sampai PARAMETER_PENDING harus working)
2. ✅ Phase B live (product_sys_id available untuk attach parameter)
3. ✅ Tabel external "parameter_cost" dan "cost_result" sudah didefinisikan (lihat Phase B FR-24)
4. ✅ Calculation engine eksternal sudah identifikasi (REST/queue/direct DB write)
5. ✅ Workshop dengan tiap departemen untuk inventarisir parameter

---

## 📋 PRE-KICKOFF CHECKLIST

Sebelum mulai PRD Phase C:

- [ ] Inventarisir parameter cost per departemen (workshop dengan Produksi, R&D, Finance, dll)
- [ ] Klarifikasi calculation engine: apa input, apa output, format-nya
- [ ] Definisikan period model (monthly/quarterly/effective-dated)
- [ ] Klarifikasi approval workflow (single-step vs multi-step per departemen)
- [ ] Klarifikasi co-ownership rules
- [ ] Buat ERD draft untuk parameter_definition + parameter_entry
- [ ] Buat draft state machine
- [ ] Identifikasi UX patterns dari Phase A (apa yang reusable)
- [ ] Gather user stories per departemen
- [ ] Definisikan success metrics

---

## 💡 INSIGHTS DARI PHASE A & B

### Apa yang sudah established
- **Tier × Functional Role matrix** — Phase C harus consistent
- **Audit trail pattern** — pakai approach yang sama (audit_log table)
- **Notification system** — reuse infrastructure dari Phase A
- **SSO integration** — sudah ready
- **Rich-text editor untuk comment** — bisa reuse Tiptap

### Yang berbeda di Phase C
- **Form-heavy UI** — beda dari Phase A (ticket-style) dan Phase B (visual)
- **Approval workflow** — explicit, beda dari Phase A yang state-driven
- **Periodisasi data** — Phase A/B tidak punya konsep period yang ketat
- **Multi-department coordination** — Phase A punya triage tapi Phase C lebih konkret

---

## 🚧 RECOMMENDED APPROACH

### Step 1: PRD Development (4-6 weeks)
- Workshop per departemen
- Draft PRD dengan template yang sama (consistency dengan Phase A & B)
- Review dengan stakeholders
- Sign-off

### Step 2: Foundation
- Schema parameter_definition (configurable per dept)
- Seeding data dari hasil workshop
- Basic CRUD parameter entry

### Step 3: Workflow
- Submission → approval flow
- Multi-step approval bila perlu
- Rejection + re-submit

### Step 4: Integration
- Listener untuk Phase A state transition
- Trigger ke calculation engine
- Cost result write-back

### Step 5: Reporting
- Parameter completion status per request
- Department workload dashboard
- Approval bottleneck analytics

---

## ❓ OPEN QUESTIONS (UNTUK IT LEADER)

1. Apakah Phase C akan jadi modul terpisah atau bagian dari Phase A's expanded scope?
2. Calculation engine: sudah ada, in-development, atau perlu build?
3. Period definition: align dengan accounting period existing?
4. Migration strategy: legacy parameter data dari Excel/system lama?
5. Role mapping: apakah tiap user di functional role tertentu bisa input parameter untuk multiple product, atau scoped?

---

*Phase C Addendum v0.1 (Draft) — Update setelah PRD Phase C tersedia*
