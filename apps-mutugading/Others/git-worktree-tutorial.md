# 📚 Tutorial Lengkap: Git Worktree untuk Workflow Release

> **Konteks Project**: `mutugading/apps-mutugading`
> **Tanggal**: 14 Februari 2026
> **Versi Terakhir**: v0.21.4

---

## Daftar Isi

- [Masalah yang Dihadapi](#masalah-yang-dihadapi)
- [Perbandingan Solusi](#perbandingan-solusi)
- [Apa itu Git Worktree?](#apa-itu-git-worktree)
- [Konsep Dasar](#konsep-dasar)
- [Tutorial Step-by-Step](#tutorial-step-by-step)
  - [Skenario: Release Branch dari Develop](#skenario-release-branch-dari-develop)
  - [Skenario: Hotfix Mendadak](#skenario-hotfix-mendadak)
  - [Skenario: Code Review PR Orang Lain](#skenario-code-review-pr-orang-lain)
- [Command Reference](#command-reference)
- [Edge Cases & Troubleshooting](#edge-cases--troubleshooting)
- [Best Practices](#best-practices)
- [Workflow Cheat Sheet](#workflow-cheat-sheet)

---

## Masalah yang Dihadapi

### Situasi Saat Ini

```
Developer     Branch                          Status
─────────────────────────────────────────────────────────
Kamu          fix/role-permission-approval     🔨 WIP (7 files changed)
Daffa         feat/a                           ✅ Done, PR merged ke develop
Mike          fix/b                            🔨 WIP
```

**Problem**: Daffa sudah selesai dan PR-nya sudah masuk ke `develop`. Sekarang kamu harus:

1. Checkout ke `develop`
2. Create branch `release/xxx`
3. Push release branch
4. Merge `release/xxx` ke `main`

**Tapi**, kamu sedang punya banyak perubahan di branch `fix/role-permission-approval-level`:

```
Modified:
  - Modules/Mis/app/Interfaces/Transactions/Overtime/Approval/OvertimeApprovalRepositoryInterface.php
  - Modules/Mis/app/Livewire/Transactions/Overtime/Approval/OvertimeApproval.php
  - Modules/Mis/app/Repositories/Transactions/Overtime/Approval/EloquentOvertimeApprovalRepository.php
  - Modules/Mis/app/Services/Transactions/Overtime/Approval/OvertimeApprovalService.php
  - Modules/Mis/app/Services/Transactions/Overtime/OvertimePlanningService.php
  - Modules/Mis/resources/views/livewire/transactions/overtime/approval/overtime-approval.blade.php

Untracked:
  - Modules/Core/app/Services/WorkflowPermissionService.php
```

Kalau kamu langsung `git checkout develop`, Git akan menolak atau worse — **perubahan kamu bisa hilang!**

---

## Perbandingan Solusi

| Metode | Pros | Cons |
|--------|------|------|
| **`git stash`** | Simple, built-in | Ribet kalau banyak file, stash bisa konflik saat pop, file untracked harus di-add dulu, mudah lupa stash mana yang mana |
| **`git clone` kedua** | Fully isolated | Duplikat seluruh repo (boros disk), harus setup ulang `.env`, `vendor/`, `node_modules/`, config IDE |
| **`git worktree`** ⭐ | Tidak mengganggu WIP, share `.git` database, ringan & cepat, bisa buka 2 IDE sekaligus | Perlu awareness soal shared state, dependency perlu install ulang di worktree baru |

> [!IMPORTANT]
> **Git Worktree adalah solusi terbaik** untuk kasus ini karena kamu bisa tetap bekerja di branch `fix/role-permission-approval-level` tanpa gangguan, sambil mengurus release di folder terpisah.

---

## Apa itu Git Worktree?

Git Worktree memungkinkan kamu punya **multiple working directory** yang terhubung ke satu repository Git yang sama. Bayangkan punya 2-3 folder project, tapi semuanya share history Git yang sama.

### Analogi Sederhana

```
Tanpa Worktree:
┌─────────────────────────────────┐
│  apps-mutugading/               │  ← Cuma bisa 1 branch di sini
│  ├── .git/                      │
│  ├── Modules/                   │
│  └── ...                        │
└─────────────────────────────────┘

Dengan Worktree:
┌─────────────────────────────────┐
│  apps-mutugading/               │  ← Branch: fix/role-permission (WIP kamu)
│  ├── .git/                      │  ← Shared Git database
│  ├── Modules/                   │
│  └── ...                        │
└──────────────┬──────────────────┘
               │ (linked)
┌──────────────┴──────────────────┐
│  apps-mutugading-release/       │  ← Branch: develop (untuk release)
│  ├── .git → (pointer ke atas)   │  ← BUKAN duplikat, hanya pointer
│  ├── Modules/                   │
│  └── ...                        │
└─────────────────────────────────┘
```

### Keunggulan Utama

1. **Zero gangguan** — WIP kamu di branch utama tetap aman
2. **Hemat disk** — Tidak clone ulang, hanya file working directory yang baru
3. **Shared history** — Commit, tag, remote, semua sync otomatis
4. **Instant switch** — Tinggal buka terminal/IDE di folder lain

---

## Konsep Dasar

### Terminologi

| Istilah | Penjelasan |
|---------|-----------|
| **Main Worktree** | Folder utama tempat kamu clone repo (`apps-mutugading/`) |
| **Linked Worktree** | Folder tambahan yang dibuat via `git worktree add` |
| **Bare Repository** | Repo tanpa working directory (advanced, optional) |

### Rules Penting

1. **Satu branch = satu worktree** — Branch yang sudah di-checkout di satu worktree tidak bisa di-checkout di worktree lain
2. **Shared state** — Stash, config global, hooks, remote dibagikan antar worktree
3. **Isolated state** — Index (staging area), HEAD, working directory, per-worktree config terpisah untuk setiap worktree
4. **Jangan hapus folder manual** — Selalu gunakan `git worktree remove`, jangan `rm -rf`

---

## Tutorial Step-by-Step

### Skenario: Release Branch dari Develop

Ini adalah skenario persis yang kamu hadapi sekarang.

#### Step 1: Verifikasi Status Saat Ini

```bash
# Cek branch aktif dan perubahan
git status

# Output yang diharapkan:
# On branch fix/role-permission-approval-level
# Changes not staged for commit:
#   modified: Modules/Mis/app/...
# Untracked files:
#   Modules/Core/app/Services/WorkflowPermissionService.php
```

```bash
# Cek worktree yang sudah ada
git worktree list

# Output:
# /home/home/hom/PhpstormProjects/apps-mutugading  16f86ea [fix/role-permission-approval-level]
```

#### Step 2: Buat Worktree Baru untuk Branch `develop`

```bash
# Format: git worktree add <path> <branch>
# Buat folder di LUAR project utama, sejajar dengannya

git worktree add ../apps-mutugading-release develop
```

**Penjelasan:**
- `../apps-mutugading-release` = Path folder baru (sejajar dengan project utama)
- `develop` = Branch yang ingin di-checkout di worktree baru

**Hasil:**

```
PhpstormProjects/
├── apps-mutugading/                  ← Main worktree (fix/role-permission, WIP kamu aman)
└── apps-mutugading-release/          ← Linked worktree (develop, untuk release)
```

#### Step 3: Verifikasi Worktree

```bash
git worktree list

# Output:
# /home/home/hom/PhpstormProjects/apps-mutugading          16f86ea [fix/role-permission-approval-level]
# /home/home/hom/PhpstormProjects/apps-mutugading-release   062f345 [develop]
```

#### Step 4: Pindah ke Worktree Release dan Lakukan Release

```bash
# Pindah ke folder worktree release
cd ../apps-mutugading-release

# Pastikan develop up-to-date
git pull origin develop

# Ambil short hash commit terbaru untuk nama release
RELEASE_HASH=$(git rev-parse --short HEAD)
echo "Release hash: $RELEASE_HASH"

# Buat branch release dari develop
git checkout -b release/$RELEASE_HASH

# Push release branch ke remote
git push origin release/$RELEASE_HASH
```

#### Step 5: Merge Release ke Main

```bash
# Checkout ke main (masih di folder worktree release)
git checkout main

# Pastikan main up-to-date
git pull origin main

# Merge release branch ke main
git merge release/$RELEASE_HASH

# Push main ke remote
git push origin main

# (Optional) Buat tag
git tag -a v0.22.0 -m "Release v0.22.0"
git push origin v0.22.0
```

> [!TIP]
> Kamu juga bisa melakukan merge via **GitHub Pull Request** instead of command line — buat PR dari `release/xxx` ke `main` di GitHub.

#### Step 6: Kembali ke WIP Kamu

```bash
# Kembali ke main worktree
cd ../apps-mutugading

# Verifikasi — semua WIP kamu masih aman!
git status

# Output tetap sama:
# On branch fix/role-permission-approval-level
# Changes not staged for commit: ...
```

**WIP kamu 100% tidak tersentuh! 🎉**

#### Step 7: Cleanup — Hapus Worktree Release

```bash
# Setelah selesai, hapus worktree yang sudah tidak diperlukan
git worktree remove ../apps-mutugading-release

# Verifikasi
git worktree list
# Output: hanya main worktree yang tersisa
```

> [!CAUTION]
> Jangan pernah menghapus folder worktree secara manual dengan `rm -rf`. Selalu gunakan `git worktree remove` agar metadata Git tetap bersih.

---

### Skenario: Hotfix Mendadak

Ada bug kritis di production dan kamu harus fix ASAP, tapi WIP kamu belum siap di-commit.

```bash
# 1. Buat worktree untuk hotfix (dari main/production)
git worktree add ../apps-mutugading-hotfix -b hotfix/critical-bug main

# 2. Pindah dan fix
cd ../apps-mutugading-hotfix
# ... lakukan perbaikan ...
git add .
git commit -m "hotfix: fix critical production bug"
git push origin hotfix/critical-bug

# 3. Buat PR ke main dan selesaikan merge

# 4. Kembali ke WIP
cd ../apps-mutugading

# 5. Cleanup
git worktree remove ../apps-mutugading-hotfix
```

---

### Skenario: Code Review PR Orang Lain

Kamu ingin review PR Daffa secara lokal tanpa mengganggu pekerjaan.

```bash
# 1. Fetch branches terbaru
git fetch origin

# 2. Buat worktree untuk review
git worktree add ../apps-mutugading-review origin/feat/a

# 3. Pindah ke worktree dan review
cd ../apps-mutugading-review
# ... test, jalankan, cek kode ...

# 4. Kembali dan cleanup
cd ../apps-mutugading
git worktree remove ../apps-mutugading-review
```

---

## Command Reference

### Perintah Dasar

```bash
# ═══════════════════════════════════════
# MEMBUAT WORKTREE
# ═══════════════════════════════════════

# Worktree dari branch yang sudah ada
git worktree add <path> <branch>
git worktree add ../my-release develop

# Worktree dengan membuat branch baru
git worktree add -b <new-branch> <path> <start-point>
git worktree add -b release/abc123 ../my-release develop

# Worktree dari remote branch (detached HEAD)
git worktree add <path> origin/<branch>

# ═══════════════════════════════════════
# MELIHAT WORKTREE
# ═══════════════════════════════════════

# List semua worktree
git worktree list

# List dalam format machine-readable
git worktree list --porcelain

# ═══════════════════════════════════════
# MENGHAPUS WORKTREE
# ═══════════════════════════════════════

# Hapus worktree (harus clean — tidak ada uncommitted changes)
git worktree remove <path>

# Force hapus (meskipun ada uncommitted changes)
git worktree remove --force <path>

# ═══════════════════════════════════════
# MAINTENANCE
# ═══════════════════════════════════════

# Bersihkan referensi worktree yang sudah rusak/terhapus manual
git worktree prune

# Preview apa yang akan di-prune
git worktree prune --dry-run

# Lock worktree agar tidak di-prune (untuk external drive, dll)
git worktree lock <path> --reason "Di external drive"

# Unlock worktree
git worktree unlock <path>
```

---

## Edge Cases & Troubleshooting

### 1. ❌ Error: Branch Sudah Di-Checkout

```
fatal: 'develop' is already checked out at '/home/home/hom/PhpstormProjects/apps-mutugading'
```

**Penyebab**: Satu branch hanya bisa di-checkout di satu worktree.

**Solusi**:
```bash
# Opsi A: Checkout ke branch lain di main worktree dulu
git checkout fix/role-permission-approval-level
git worktree add ../release develop

# Opsi B: Gunakan -b untuk buat branch baru dari develop
git worktree add -b release/abc123 ../release develop
# Ini membuat branch baru release/abc123 DARI develop
# sehingga tidak konflik dengan develop yang di-checkout di tempat lain
```

### 2. ❌ Error: Path Sudah Ada

```
fatal: '/home/.../apps-mutugading-release' already exists
```

**Solusi**:
```bash
# Cek apakah worktree sudah ada
git worktree list

# Jika path sudah ada tapi bukan worktree, hapus dulu
rm -rf ../apps-mutugading-release
git worktree prune
git worktree add ../apps-mutugading-release develop
```

### 3. ❌ Error: Cannot Remove Worktree with Uncommitted Changes

```
fatal: cannot remove worktree: has uncommitted changes
```

**Solusi**:
```bash
# Opsi A: Commit atau stash perubahan di worktree
cd ../apps-mutugading-release
git stash
cd ../apps-mutugading
git worktree remove ../apps-mutugading-release

# Opsi B: Force remove (hati-hati, perubahan hilang!)
git worktree remove --force ../apps-mutugading-release
```

### 4. ⚠️ Dependency & Environment Files

Worktree baru **tidak memiliki**:
- `vendor/` (Composer dependencies)
- `node_modules/` (NPM dependencies)
- `.env` (Environment config)
- File hasil build lainnya

**Kenapa?** Karena file-file ini ada di `.gitignore`, jadi tidak di-track Git.

**Solusi — untuk worktree yang hanya dipakai release/merge:**

```bash
# TIDAK perlu install dependencies kalau hanya merge dan push!
# Worktree untuk release cukup digunakan untuk:
# - git checkout
# - git merge
# - git push
# TIDAK PERLU menjalankan aplikasi
```

**Solusi — jika perlu menjalankan app di worktree (testing/review):**

```bash
cd ../apps-mutugading-release

# Copy .env dari main worktree
cp ../apps-mutugading/.env .env

# Install dependencies
composer install
npm install

# (Jika menggunakan Laravel)
php artisan key:generate  # Skip jika .env sudah ada key
```

> [!WARNING]
> Hati-hati jika `.env` mengandung konfigurasi yang berbeda per branch (misal database name berbeda). Pastikan kamu menyesuaikan `.env` sesuai kebutuhan.

### 5. ⚠️ Shared Stash

`git stash` adalah **shared** antar semua worktree. Jika kamu stash di worktree A dan pop di worktree B, itu akan work tapi bisa membingungkan.

```bash
# Di worktree A (fix/role-permission)
git stash push -m "WIP role permission"

# Di worktree B (develop) — STASH INI JUGA TERLIHAT!
git stash list
# stash@{0}: On fix/role-permission-approval-level: WIP role permission

# JANGAN pop stash ini di worktree yang salah!
```

**Best practice**: Hindari menggunakan stash saat bekerja dengan worktree. Itu justru alasan kuta menggunakan worktree — supaya tidak perlu stash.

### 6. ⚠️ Folder Worktree Terhapus Manual

Jika kamu atau seseorang menghapus folder worktree dengan `rm -rf`:

```bash
# Git masih menyimpan referensi ke worktree yang sudah hilang
git worktree list
# Akan menunjukkan worktree yang sudah tidak ada

# Bersihkan referensi yang stale
git worktree prune

# Verifikasi
git worktree list
```

### 7. ⚠️ IDE & Editor Behavior

- **PhpStorm/IntelliJ**: Bisa membuka worktree sebagai project terpisah. Cukup `File → Open` dan pilih folder worktree.
- **VS Code**: Setiap worktree bisa dibuka di window terpisah. Git extension akan mengenali worktree sebagai repo normal.
- **Terminal**: Setiap terminal session bisa `cd` ke worktree berbeda dan bekerja independen.

> [!TIP]
> Di PhpStorm, kamu bisa buka 2 window — satu untuk WIP kamu, satu lagi untuk worktree release. Kerjakan release tanpa menutup atau mengganggu project WIP.

### 8. ⚠️ Git Hooks

Git hooks (`pre-commit`, `pre-push`, dll.) adalah **shared** antar semua worktree karena tersimpan di `.git/hooks/`. Jika kamu punya hook yang berat (misal running tests), hook tersebut juga akan jalan di worktree baru.

### 9. ⚠️ Submodules

Jika project menggunakan Git submodules, kamu perlu init ulang di worktree baru:

```bash
cd ../apps-mutugading-release
git submodule update --init --recursive
```

### 10. ⚠️ Worktree + Rebase/Reset

Hati-hati melakukan `git rebase` atau `git reset --hard` di worktree yang branch-nya juga dipakai sebagai base di worktree lain. Ini bisa menyebabkan state yang tidak konsisten.

```bash
# JANGAN lakukan ini:
# Di worktree A: git rebase develop (dimana develop di-checkout di worktree B)
# Bisa menyebabkan confusing state

# AMAN:
# Rebase branch kamu sendiri yang tidak di-checkout di worktree lain
```

---

## Best Practices

### 1. Naming Convention untuk Folder Worktree

```bash
# Format: <project-name>-<purpose>
apps-mutugading                     # Main worktree (development)
apps-mutugading-release             # Untuk proses release
apps-mutugading-hotfix              # Untuk hotfix
apps-mutugading-review              # Untuk code review
apps-mutugading-experiment          # Untuk eksperimen
```

### 2. Lokasi Folder

Letakkan worktree **sejajar** dengan main project, bukan di dalamnya:

```bash
# ✅ BENAR — sejajar
PhpstormProjects/
├── apps-mutugading/           # main
└── apps-mutugading-release/   # worktree

# ❌ SALAH — di dalam project
apps-mutugading/
├── .git/
├── Modules/
└── _worktrees/                # JANGAN begini!
    └── release/
```

### 3. Jangan Biarkan Worktree Menumpuk

```bash
# Cek berkala
git worktree list

# Hapus yang sudah tidak dipakai
git worktree remove ../apps-mutugading-release

# Bersihkan referensi stale
git worktree prune
```

### 4. Untuk Release Flow — Worktree Cukup Temporary

Untuk use case release, worktree bersifat sementara:

```
Buat worktree → Pull develop → Create release branch → Merge ke main → Hapus worktree
```

Total waktu: **~2 menit**, tanpa mengganggu WIP!

### 5. Commit WIP Sebelum Membuat Worktree (Optional tapi Recommended)

Meskipun worktree tidak mengganggu WIP, ada baiknya buat WIP commit sebelumnya:

```bash
# Di main worktree, sebelum membuat worktree
git add .
git commit -m "wip: save progress before release"

# Nanti setelah selesai, bisa amend atau squash
git reset --soft HEAD~1  # Undo WIP commit, perubahan kembali ke staging
```

---

## Workflow Cheat Sheet

### 🚀 Quick Release (Kasus Kamu Saat Ini)

```bash
# ════════════════════════════════════════════════════════
# STEP 1: Buat worktree (dari main worktree kamu)
# ════════════════════════════════════════════════════════
git worktree add ../apps-mutugading-release develop

# ════════════════════════════════════════════════════════
# STEP 2: Pindah dan lakukan release
# ════════════════════════════════════════════════════════
cd ../apps-mutugading-release
git pull origin develop
HASH=$(git rev-parse --short HEAD)
git checkout -b release/$HASH
git push origin release/$HASH

# ════════════════════════════════════════════════════════
# STEP 3: Merge ke main (via command line)
# ════════════════════════════════════════════════════════
git checkout main
git pull origin main
git merge release/$HASH --no-ff
git push origin main

# ATAU: Buat PR di GitHub dari release/$HASH ke main

# ════════════════════════════════════════════════════════
# STEP 4: Kembali dan cleanup
# ════════════════════════════════════════════════════════
cd ../apps-mutugading
git worktree remove ../apps-mutugading-release

# ════════════════════════════════════════════════════════
# STEP 5: (Optional) Sync develop kamu
# ════════════════════════════════════════════════════════
git fetch origin
# Lanjut kerja di fix/role-permission-approval-level 🎉
```

### 🔥 Quick Hotfix

```bash
git worktree add ../apps-mutugading-hotfix -b hotfix/nama-fix main
cd ../apps-mutugading-hotfix
# ... fix bug ...
git add . && git commit -m "hotfix: deskripsi fix"
git push origin hotfix/nama-fix
# Buat PR ke main
cd ../apps-mutugading
git worktree remove ../apps-mutugading-hotfix
```

### 👀 Quick Review

```bash
git fetch origin
git worktree add ../apps-mutugading-review origin/feat/branch-yang-di-review
cd ../apps-mutugading-review
# ... review kode ...
cd ../apps-mutugading
git worktree remove ../apps-mutugading-review
```

---

## Otomatisasi Setup Worktree (Best Practices)

Seperti yang Anda alami, ketika membuat worktree baru untuk review atau testing kode yang memerlukan aplikasi berjalan lokal, folder `vendor` dan `node_modules` tidak akan ikut terbawa karena diabaikan oleh Git (`.gitignore`). Error **"Failed opening required vendor/autoload.php"** adalah hal yang sangat wajar karena dependensi PHP belum terinstall di worktree baru tersebut.

Meng-copy `.env` dan menjalankan `composer install` serta `npm install` setiap kali membuat worktree baru sangat memakan waktu. **Best practice**-nya adalah membuat Bash script sederhana untuk mengotomatisasi proses berulang ini.

Kami telah menyiapkan 2 script di dalam project Anda (`apps-mutugading/scripts/`) yang bisa langsung Anda gunakan:

### 1. Script Setup (`scripts/setup-worktree.sh`)

Script ini akan otomatis:
1. Membuat worktree baru (atau checkout jika branch sudah ada).
2. Menyalin `.env` dari project utama ke worktree baru.
3. Menjalankan `composer install` dan `npm install`.

**Cara Penggunaan:**
Buka terminal di root project (`apps-mutugading`), lalu jalankan:
```bash
./scripts/setup-worktree.sh nama-branch-pr
```

### 2. Script Cleanup (`scripts/cleanup-worktree.sh`)

Setelah selesai me-review PR, Anda perlu menghapus folder worktree agar tidak memenuhi disk.

**Cara Penggunaan:**
```bash
./scripts/cleanup-worktree.sh nama-branch-pr
```
Script ini akan menghapus worktree beserta foldernya dengan aman, lalu akan menawarkan Opsi apakah Anda ingin sekalian menghapus branch lokalnya.

---

## FAQ

### Q: Apakah worktree memakan banyak disk space?
**A**: Tidak sebanyak clone. Worktree hanya membuat copy dari working directory (source files). `.git` database (history, objects, dll.) di-share. Untuk project ini yang source code-nya ~ratusan MB, itu jauh lebih ringan dari clone penuh. Tapi ingat dependensi (`vendor`, `node_modules`) tetap akan ter-copy jika di-install.

### Q: Bisa buat berapa worktree?
**A**: Tidak ada hard limit. Tapi disarankan max 2-3 aktif sekaligus untuk menjaga kejelasan.

### Q: Apakah `composer install` / `npm install` perlu di worktree baru?
**A**: **Hanya jika kamu perlu menjalankan aplikasi** di worktree tersebut. Untuk release/merge, TIDAK perlu.

### Q: Bagaimana kalau saya lupa menghapus worktree?
**A**: Tidak ada dampak buruk selain folder menghabiskan disk space. Kamu bisa hapus kapan saja dengan `git worktree remove`.

### Q: Apakah worktree aman untuk CI/CD?
**A**: Ya! Perubahan yang kamu push dari worktree manapun akan ter-detect oleh CI/CD karena semuanya share remote yang sama.

### Q: Bisa pakai worktree di Windows?
**A**: Ya, Git Worktree support di semua OS. Path-nya tinggal disesuaikan.

---

## Diagram Alur Lengkap

```
┌─────────────────────────────────────────────────────────────────┐
│                    WORKFLOW RELEASE DENGAN WORKTREE              │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Main Worktree                    Linked Worktree               │
│  (apps-mutugading/)               (apps-mutugading-release/)    │
│                                                                 │
│  [fix/role-permission] 🔨         [develop]                     │
│  │ WIP kamu tetap aman            │                             │
│  │ Tidak perlu stash              │ git pull origin develop     │
│  │ Tidak perlu commit             │ git checkout -b release/xx  │
│  │                                │ git push origin release/xx  │
│  │                                │ git checkout main           │
│  │                                │ git merge release/xx        │
│  │                                │ git push origin main        │
│  │                                │                             │
│  │                                └──── selesai!                │
│  │                                                              │
│  │  ← git worktree remove ../apps-mutugading-release            │
│  │                                                              │
│  │ Lanjut kerja WIP ✨                                          │
│  ▼                                                              │
└─────────────────────────────────────────────────────────────────┘
```

---

> [!NOTE]
> Tutorial ini dibuat berdasarkan analisis langsung pada repository `mutugading/apps-mutugading` dan dokumentasi resmi Git. Semua contoh menggunakan path dan branch name yang sesuai dengan project ini.
