# 📌 Phase Addendum — Phase A: Product Request

> **Cara pakai:** Tambahkan ini ke awal chat ketika kerja fokus di Phase A.
> Atau buat **sub-Project** terpisah di Claude Team Plan untuk Phase A dengan addendum ini di-append ke Master Prompt.

---

## 🎯 PHASE A FOCUS

Modul ticketing untuk Product Request — menghubungkan Marketing, Process Engineering, dan departemen-departemen pemilik parameter cost.

**Goals utama:**
1. Visibilitas: Marketing bisa lihat di mana request mereka
2. Accountability: setiap kewajiban terlihat owner-nya
3. Audit trail: setiap event tercatat — no more "sapa salahnya"

**Critical constraint:** Phase A standalone-ready, TANPA harus menunggu Phase B atau C live.

---

## 🗓 SPRINT BLOCKS (sesuai PRD Section 11)

| Block | Scope | Estimasi |
|-------|-------|----------|
| **1 — Foundation** | DB schema, SSO integration, user_role_mapping admin UI, FR-1 Submit Request, FR-2 State Machine | 2-3 weeks |
| **2 — Lifecycle & Routing** | FR-3 Hybrid Routing + FR-19 Admin UI, FR-4 Review & Classification, FR-7/8 Routing Draft CRUD | 2-3 weeks |
| **3 — Communication** | FR-5 Comments (rich-text + attachment), FR-6 Activity Timeline, FR-10/11/12 Notifications | 2-3 weeks |
| **4 — Visibility & Reporting** | FR-13 My Workspace, FR-14/15/16 Dashboards, FR-17 Export & API | 2 weeks |
| **5 — Admin & Hardening** | FR-18/19/20 Admin UI, FR-21 Audit Trail UI, UAT, performance test, security review | 2-3 weeks |

**Total estimasi:** 10-14 weeks (2.5-3.5 bulan)

---

## 🔑 PHASE A KEY DECISIONS (BAKED IN PRD)

| Keputusan | Pilihan | Rasional |
|-----------|---------|----------|
| Customer master | Free-text dengan opsi autocomplete | CRM integration future enhancement |
| Triage owner | Engineering Lead (Tier=Dept Lead + Functional=Engineering) | Single role, configurable via routing_rule |
| Rich-text editor | Tiptap (primary) atau Lexical (alternative) | Body disimpan sebagai JSONB + plaintext copy |
| Attachment storage | S3-compatible object storage | Max 25 MB/file, mime whitelist |
| SLA enforcement | Tidak ada auto-escalation | Hanya due date informatif dengan warna |
| Comment deletion | Tidak boleh — immutable history | Admin bisa "hide" dengan reason |
| Recosting flow | Out of scope | Bukan request bisnis, ada di Phase B atau external |

---

## ⚠️ PHASE A GOTCHAS

### Routing Draft = Shadow Entity
- Schema HARUS mirror Phase B `product_order` dan `product_order_component`
- Tapi MORE LENIENT — `rm_ref_text` adalah free-text bila intermediate product belum ada
- Saat Phase B live: kolom `linked_product_order_id` jadi FK ke Phase B
- Promotion to Product Order = atomic operation (transactional)

### Classification Dual-Confirmation
- Marketing mark `marketing_classification` saat submit
- Engineering verify, isi `verified_classification`
- Bila override (verified ≠ marketing) → wajib `classification_override_reason`
- UI harus tampilkan kedua-nya untuk transparency

### State Machine Variants
Dua workflow berbeda berdasarkan request_type:

**Full Flow (Development/Sample):**
```
DRAFT → SUBMITTED → UNDER_REVIEW → ROUTING_DEFINED →
PARAMETER_PENDING → PARAMETER_COMPLETE → COSTING_DONE →
QUOTED → CLOSED
```

**Shortcut (Quote inquiry + existing costing):**
```
DRAFT → SUBMITTED → UNDER_REVIEW → QUOTE_READY → QUOTED → CLOSED
```

CLOSED punya sub-status: `won`, `lost`, `cancelled`, `on_hold`

### Permission Matrix
2-dimensional: **Tier × Functional Role**

| Tier | Capability |
|------|-----------|
| User | Create/view own + assigned, comment, attach, mark classification |
| Department Lead | + triage assign, monitor dept dashboard, eskalasi |
| Manager | + cross-department analytics, override status (with reason) |
| Admin | + configure routing rules, request types, user mapping |

Functional Role menentukan: default landing, dashboard scope, request types yang bisa dibuat.

### Notification Fatigue Prevention
- Per-trigger preference (email/in-app/both/none)
- Daily digest option untuk notif non-urgent
- @mention always immediate (tidak bisa di-digest)

---

## 📊 DATA MODEL — PHASE A QUICK REFERENCE

**Entitas inti:**
- `product_request` — entry-point ticket
- `request_type` — configurable types
- `routing_rule` — admin-configurable routing
- `routing_draft` + `routing_draft_component` — shadow entity untuk Phase B
- `request_comment` + `request_comment_edit_history` + `request_mention`
- `attachment` — generic, mutually exclusive request vs comment
- `user_role_mapping` — SSO user → Tier + Functional Role
- `notification` + `user_notification_preference`
- `audit_log` — cross-cutting

**Penting:**
- `attachment` punya CHECK constraint: exactly one of (request_id, comment_id) non-null
- `request_no` format: `REQ-YYYYMM-NNNN` (sequential, regenerable)
- `body_richtext` (JSONB) + `body_plaintext` (TEXT) untuk comment — plaintext untuk search & notif
- Audit log retention: 5 tahun (TBD dengan legal)

---

## 🔌 PHASE A INTEGRATION POINTS

### Input (yang Phase A pakai)
- **SSO/IAM:** untuk authentication + department info
- **Master Item (read-only):** untuk customer_code autocomplete dan FK validation
- **Master Customer (optional):** bila ada CRM, untuk autocomplete customer_name
- **Email Gateway:** outbound notification
- **Object Storage:** attachment upload

### Output (yang Phase A expose)
- **Read-only API** untuk BI tools (Metabase/Superset/Power BI)
- **Webhook events** (future): untuk Phase C subscribe ke state changes
- **Export endpoints:** CSV/Excel untuk dashboard

### Future Integration (saat Phase B/C live)
- **Phase B:** Routing Draft promote → Product Order
- **Phase C:** Parameter task abstract → form entry konkret

---

## 🎯 SUCCESS METRICS PHASE A

Pasca-launch (3-6 bulan), expect:
- ≥80% request product baru di-create via sistem (bukan email/WA)
- Survey internal "tahu di mana request saya?" ≥4/5
- Cycle time submitted → quoted turun ≥30%
- Bottleneck report ter-generate kapan saja
- Zero ambiguitas "sapa salah" saat request terlambat

---

## 💬 COMMON QUESTIONS PER ROLE

**IT Leader bertanya:**
- "Apa risiko jika scope di-expand?" → Lihat PRD Section 10 + impact analysis
- "Kapan release?" → Tergantung sprint block velocity, baseline 10-14 weeks

**Backend Dev bertanya:**
- "State transition rules?" → FR-2 + state machine diagram di docs
- "Bagaimana implement polymorphic FK?" → Dual-column dengan CHECK constraint (lihat Master Prompt)

**Frontend Dev bertanya:**
- "Rich-text editor mana?" → Tiptap primary, Lexical alternative
- "Bagaimana handle @mention?" → Saat user type `@`, query user dengan permission view atas request

**QA bertanya:**
- "Critical user flows?" → Submit request, Triage assignment, Promote routing draft, Notification preferences
- "Edge cases?" → Race condition saat 2 PIC verify bersamaan, attachment upload gagal di tengah, SSO timeout

---

*Phase A Addendum v1.0 — Append to Master Prompt saat fokus Phase A*
