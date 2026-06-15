# Panduan Claude Code — Generate Code dari PRD
## PT Mutu Gading Tekstil | IT Leader Guide

> Panduan ini untuk Indra sebagai IT Leader yang ingin generate code
> dari PRD menggunakan Claude Code, tanpa background web development.
> Version: 1.0 | Juni 2026

---

## Konsep Dasar

Claude Code adalah coding agent yang berjalan di terminal. Dia bisa:
- Baca seluruh struktur repo dan file yang ada
- Buat file baru, edit file existing
- Jalankan command (test, lint, build)
- Tahu konvensi repo dari `CLAUDE.md`

Yang Indra lakukan: **define apa yang mau dibuat** (dari PRD).
Yang Claude lakukan: **implementasi teknisnya**.

---

## Step 1 — Install Claude Code

Buka terminal (PowerShell di Windows):

```powershell
# Install Node.js dulu jika belum ada
# Download dari: https://nodejs.org (pilih LTS)

# Install Claude Code
npm install -g @anthropic-ai/claude-code

# Verifikasi
claude --version
```

---

## Step 2 — Login ke Anthropic

```powershell
claude login
# Browser akan terbuka → login dengan akun Claude Indra
# Setelah login, kembali ke terminal
```

---

## Step 3 — Siapkan Repo

```powershell
# Clone repo yang akan dikerjakan (contoh: production-plan)
git clone https://github.com/mutugading/production-plan
cd production-plan

# Atau kalau repo belum ada, buat folder kosong dulu
mkdir production-plan
cd production-plan
git init
```

---

## Step 4 — Pastikan CLAUDE.md Ada di Repo

`CLAUDE.md` adalah "instruksi permanen" untuk Claude Code.
Tanpa ini, Claude tidak tahu konvensi project Indra.

Untuk project Go baru (Production Plan), buat file `CLAUDE.md` dengan isi minimal:

```markdown
# CLAUDE.md — Production Plan System

## Stack
- Go 1.24, Clean Architecture + DDD
- PostgreSQL 18 via pgx v5
- gRPC + gRPC-Gateway
- Ikuti pattern yang sama dengan mutugading/goapps-backend

## Konvensi
- Layer: domain → application → infrastructure → delivery
- Domain layer TIDAK BOLEH import infrastructure
- golangci-lint WAJIB 0 error
- Semua error di-return, tidak panic
- Test wajib untuk setiap business logic baru

## Referensi
- PRD: docs-markdown/goapps/production-plan/PRD/
- Pattern reference: mutugading/goapps-backend
```

---

## Step 5 — Mulai Claude Code

```powershell
# Dari dalam folder repo
cd production-plan
claude
```

Indra akan masuk ke sesi interaktif. Tampilan seperti ini:

```
Claude Code v1.x.x
Working in: /path/to/production-plan

>
```

---

## Step 6 — Generate Code dari PRD

Ini bagian utamanya. Ada beberapa cara:

### A. Minta Claude baca PRD dulu

```
> Baca PRD di docs/PRD_ProductionPlan.md dan jelaskan
  pemahamanmu tentang sistem ini sebelum kita mulai coding
```

Claude akan summarize PRD — pastikan pemahamannya benar sebelum lanjut.

### B. Generate satu bagian dulu (tidak sekaligus)

Jangan minta generate semua sekaligus. Mulai dari yang paling fundamental:

```
> Berdasarkan PRD section 2 (Data Model), buatkan:
  1. Domain entity untuk ProductionDemand
  2. Repository interface untuk ProductionDemand
  3. PostgreSQL migration untuk tabel production_demands

  Ikuti pattern Clean Architecture seperti di CLAUDE.md.
  Tunjukkan dulu rencana file apa saja yang akan dibuat,
  konfirmasi sebelum eksekusi.
```

### C. Review sebelum eksekusi

Claude akan tunjukkan rencana sebelum buat file:
```
Saya akan membuat:
- internal/domain/demand/entity.go
- internal/domain/demand/repository.go
- migrations/001_create_production_demands.sql

Lanjut?
```

Ketik `yes` atau `y` untuk lanjut.

### D. Iterasi per layer

Setelah domain selesai, lanjut ke layer berikutnya:

```
> Domain ProductionDemand sudah ada. Sekarang buat
  application layer: handler untuk CreateDemand dan ListDemands.
  Sertakan unit test.
```

---

## Step 7 — Review Hasil

Setelah Claude generate, Indra perlu review dari sisi **requirement**:

**Yang Indra cek:**
- ✅ Apakah field/kolom sudah sesuai PRD?
- ✅ Apakah business rule sudah terimplementasi?
- ✅ Apakah nama entity/fungsi masuk akal secara bisnis?

**Yang developer cek (review teknis):**
- Apakah pattern sudah benar?
- Apakah ada edge case yang terlewat?
- Apakah test coverage adequate?

---

## Step 8 — Commit Hasil

```powershell
# Masih di dalam sesi Claude Code, atau buka terminal baru
git add .
git commit -m "feat(demand): add ProductionDemand domain entity and repository"
git push origin feat/production-demand
```

Atau minta Claude yang buatkan commit message:

```
> Buatkan conventional commit message yang sesuai
  untuk perubahan yang baru saja kita buat
```

---

## Prompt Templates Siap Pakai

### Untuk generate domain entity baru:
```
Berdasarkan PRD section [X], buatkan domain entity untuk [NamaEntity].
Ikuti pattern Clean Architecture di CLAUDE.md.
Field-field yang dibutuhkan: [list field dari PRD]
Business rules yang harus ada: [list rule dari PRD]
Tunjukkan rencana file dulu sebelum eksekusi.
```

### Untuk generate migration:
```
Buatkan PostgreSQL migration untuk tabel [nama_tabel].
Kolom-kolomnya: [list kolom dari PRD/ERD]
Gunakan column prefix convention: [prefix_] untuk setiap kolom.
Sertakan rollback migration (down).
```

### Untuk generate repository:
```
Buatkan repository interface dan implementasinya untuk [NamaEntity].
Method yang dibutuhkan: Create, GetByID, List (dengan pagination), Update, Delete.
Database: PostgreSQL via pgx v5.
```

### Untuk generate gRPC handler:
```
Berdasarkan proto definition untuk [ServiceName],
buatkan gRPC delivery handler.
Mapping: [nama method proto] → [nama application handler]
Sertakan proper error mapping ke gRPC status codes.
```

### Untuk debug error:
```
Saya kena error ini:
[paste error message]

File yang relevan: [nama file]
Yang sudah saya coba: [apa yang sudah dicoba]

Tolong identifikasi penyebab dan solusinya.
```

---

## Tips Penting

### 1. Satu task per sesi
Jangan campur beberapa hal dalam satu permintaan.
Buruk: "Buatkan semua entity, repository, handler, dan test sekaligus"
Baik: "Buatkan domain entity ProductionDemand dulu"

### 2. Selalu konfirmasi sebelum eksekusi
Tambahkan di akhir prompt: "Tunjukkan rencana file dulu sebelum eksekusi"

### 3. Kalau hasil tidak sesuai
```
> Ini belum sesuai PRD. Di PRD section 3.2 disebutkan bahwa
  [jelaskan apa yang seharusnya]. Tolong revisi.
```

### 4. Kalau Claude "lupa" konteks
```
> Ingat kita pakai Go + Clean Architecture. Jangan gunakan
  framework/pattern lain yang tidak ada di CLAUDE.md.
```

### 5. Keluar dari sesi Claude Code
```
> exit
```
atau tekan `Ctrl+C`

---

## Urutan Ideal Generate Code dari PRD (Go Project)

```
1. CLAUDE.md          → tulis dulu sebelum mulai
2. Proto definition   → API contract (kalau pakai gRPC)
3. Database migration → schema dulu
4. Domain entities    → business object
5. Repository interface → kontrak data access
6. Repository implementation → actual DB queries
7. Application handlers → business logic / use cases
8. Delivery (gRPC)    → handler yang dipanggil client
9. Main / DI wiring   → sambungkan semua layer
10. Unit tests        → validate business logic
```

Jangan loncat urutan — domain harus ada sebelum application,
repository interface harus ada sebelum implementation.

---

## Troubleshooting Umum

| Masalah | Solusi |
|---------|--------|
| Claude tidak tahu konvensi project | Pastikan `CLAUDE.md` ada dan lengkap |
| Claude generate kode yang salah pattern | Tunjukkan contoh dari repo `goapps-backend` |
| Claude stuck / tidak merespons | Tekan `Ctrl+C`, restart dengan `claude` |
| File ter-generate di tempat yang salah | Sebutkan path eksplisit di prompt |
| Golangci-lint error setelah generate | Minta Claude: "Fix semua golangci-lint errors" |

---

*Source: github.com/mutugading/docs-markdown/claude/CLAUDE_CODE_GUIDE_ITLDR.md*
*Version: 1.0 | Juni 2026 | Owner: IT Leader*
