# Scope Boundary — Pembagian Tanggung Jawab
## Production Plan System — PT Mutu Gading Tekstil

> Dokumen ini mendefinisikan secara eksplisit siapa mengerjakan apa
> dalam development Production Plan System. Tidak ada grey area —
> setiap aktivitas jelas siapa owner-nya.
>
> **Tujuan:** Mencegah tumpang tindih, memastikan kontrol IT Leader
> terhadap requirement dan business logic.

---

## Prinsip Dasar

```
IT Leader (Indra) = Arsitek
  → Define APA yang dibangun dan KENAPA
  → Kontrol requirement, business logic, data model

Developer = Kontraktor
  → Define BAGAIMANA cara membangunnya
  → Kontrol technical implementation, pattern, performance
```

**Aturan emas:**
- Developer tidak boleh ubah business logic tanpa approval IT Leader
- IT Leader tidak perlu approve detail teknis (library, pattern, naming internal)
- Semua perubahan requirement → PR ke docs-markdown (reviewed IT Leader)
- Semua perubahan code → PR ke repo code (reviewed developer lain)

---

## Matriks Tanggung Jawab

### Dokumentasi

| Aktivitas | IT Leader | Developer | Notes |
|-----------|-----------|-----------|-------|
| Tulis PRD | **Owner** | Reviewer | IT Leader generate via Claude Chat |
| Update PRD | **Owner** | Contributor (via ADR/Gap) | Semua update PRD via PR |
| Tulis ERD | **Owner** | Reviewer | IT Leader generate via Claude Chat |
| Update ERD | **Owner** | Contributor | Sama seperti PRD |
| Buat ADR | Approver | **Owner** | Developer draft, IT Leader approve |
| Update Gap Log | Approver | **Owner** | Developer catat, IT Leader approve |
| Tulis CLAUDE.md | **Owner** | Reviewer | IT Leader tulis, developer bisa suggest |
| Tulis SCOPE_BOUNDARY.md | **Owner** | — | IT Leader saja |

---

### Code Generation

| Aktivitas | IT Leader | Developer | Notes |
|-----------|-----------|-----------|-------|
| Database migration | **Generate** | Review | IT Leader generate via Claude Code |
| Domain entities | **Generate** | Review | IT Leader generate via Claude Code |
| Repository interface | **Generate** | Review | IT Leader generate via Claude Code |
| Repository implementation | — | **Owner** | Developer yang handle |
| Application handlers (use case) | Generate (awal) | **Review + Refine** | IT Leader draft, developer sempurnakan |
| gRPC proto definition | — | **Owner** | Developer yang handle |
| gRPC delivery handler | — | **Owner** | Developer yang handle |
| Main.go / DI wiring | — | **Owner** | Developer yang handle |
| Unit tests | Generate (awal) | **Review + Add** | IT Leader generate dari AC, developer tambah edge case |
| Integration tests | — | **Owner** | Developer yang handle |
| CI/CD pipeline | — | **Owner** | Developer yang handle |
| Infrastructure / K8s | — | **Owner** | Developer yang handle |

---

### Review & Approval

| Aktivitas | IT Leader | Developer | Notes |
|-----------|-----------|-----------|-------|
| PR ke docs-markdown | **Approve** | Author | Semua doc changes |
| PR code — business logic | **Approve** | Author + Peer review | Kalau menyangkut business rule |
| PR code — technical only | Notify saja | **Approve** | Pattern, refactor, performance |
| ADR | **Approve** | Author | Developer draft, IT Leader decide |
| Gap Log update | **Approve** | Author | Developer catat, IT Leader approve |
| Go/No-go deploy | **Approve** | Recommend | IT Leader final say untuk production |

---

### Komunikasi

| Situasi | Developer action | IT Leader action |
|---------|-----------------|-----------------|
| Temukan gap di PRD | Buka GitHub Issue (label: prd-gap) | Respond dalam 1 hari kerja |
| Butuh keputusan teknis signifikan | Draft ADR, minta review | Approve/reject dalam 1 hari kerja |
| Implementasi selesai satu feature | Buat PR, notify IT Leader | Review dari sisi requirement |
| Ada blocking issue | Flag di ClickUp task (priority: urgent) | Respond hari itu |
| Mau tambah fitur di luar PRD | Buka GitHub Issue dulu | Approve/reject sebelum coding |

---

## Yang TIDAK Boleh Dilakukan Developer Tanpa Approval IT Leader

1. ❌ Ubah business rule yang ada di PRD
2. ❌ Tambah field/kolom baru di tabel utama
3. ❌ Hapus atau rename field yang sudah ada
4. ❌ Tambah fitur baru yang tidak ada di PRD
5. ❌ Ubah state machine / lifecycle WO/Plan Item
6. ❌ Ubah integrasi dengan Oracle ERP
7. ❌ Deploy ke production tanpa sign-off IT Leader

---

## Yang Boleh Developer Lakukan Tanpa Approval IT Leader

1. ✅ Pilih library/package yang digunakan
2. ✅ Refactor internal code (selama behavior tidak berubah)
3. ✅ Optimasi query / performance
4. ✅ Tambah index di database
5. ✅ Ubah nama variable/function internal
6. ✅ Tambah unit test
7. ✅ Setup CI/CD pipeline
8. ✅ Infrastructure dan deployment config

---

## Alur Kerja Standar per Feature

```
1. IT Leader generate domain entity + migration via Claude Code
            ↓
2. IT Leader push ke branch feat/[feature-name] di repo
            ↓
3. IT Leader buat PR → assign ke developer untuk review
            ↓
4. Developer review: pattern benar? edge case? test coverage?
            ↓
5. Developer tambah repository impl + gRPC handler
   (di branch yang sama atau branch terpisah)
            ↓
6. Developer buat PR → IT Leader review dari sisi business logic
            ↓
7. Merge ke develop setelah semua approved
            ↓
8. IT Leader update ClickUp task status
```

---

## Eskalasi

Kalau ada ketidaksepakatan antara IT Leader dan developer:

1. Developer buka GitHub Issue dengan detail posisi masing-masing
2. Diskusi di issue — coba resolve dalam 24 jam
3. Kalau tidak resolve → meeting singkat (30 menit max)
4. IT Leader punya final say untuk business logic
5. Developer punya final say untuk technical implementation

---

*Source: github.com/mutugading/docs-markdown/goapps/production-plan/SCOPE_BOUNDARY.md*
*Version: 1.0 | Juni 2026 | Owner: IT Leader*
