# E-Filing legacy archive on MinIO — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** An env toggle points the `efiling_legacy` disk at the mounted share or at a folder on the existing MinIO; preview/download redirects to a signed link when the disk can make one.

**Architecture:** Config-only switch on the disk definition; `EfFileStorage::legacyResponse()` gains a redirect branch. `LegacyImporter`, the command, and existing DB rows are untouched — rows store disk name + path relative to the share root.

**Tech Stack:** Laravel 12 filesystem (local / s3 drivers), Pest 4.

**Spec:** `.docs-me/superpowers/specs/2026-11-17-efiling-legacy-on-minio-design.md`

## Global Constraints

- Toggle: `EFILING_LEGACY_STORAGE=share|minio`, default `share`.
- `minio` mode reuses `MINIO_ACCESS_KEY_ID`, `MINIO_SECRET_ACCESS_KEY`, `MINIO_DEFAULT_REGION`, `MINIO_ENDPOINT`; bucket `EFILING_LEGACY_BUCKET` (default `MINIO_PRIVATE_BUCKET`), prefix `EFILING_LEGACY_PREFIX` (default `efiling-legacy`) as the disk `root`.
- Legacy disk is never written; no migration; no change to `ef_document_files`.
- Signed link lifetime: same `LINK_TTL_MINUTES` (5) as new files.
- User-facing "file server" wording becomes "legacy archive".

## Review Focus

1. Legacy file missing from MinIO → still the friendly 404, not a dead signed link. (Task 1 test: 404 with `buildTemporaryUrlsUsing` set.)
2. Download of a legacy file in minio mode → link carries `attachment; filename="…"`. (Task 1 test.)
3. Share mode keeps streaming exactly as today. (Existing `streams a legacy file` test stays green.)
4. Importer against s3 disk: `directories()`/`files()`/`lastModified()` work through the prefix — covered by Laravel; verified manually by `efiling:import-legacy --dry-run` after the flip (Task 2 step).
5. Default env (`share`) changes nothing on deploy — Task 2 test asserts the config default driver is `local`.

---

### Task 1: Redirect legacy files when the disk can sign links

**Files:**
- Modify: `Modules/Efiling/app/Services/EfFileStorage.php` (`legacyResponse`, `temporaryUrl`)
- Modify: `Modules/Efiling/resources/views/livewire/document-show-page.blade.php:86,238,255` (wording)
- Test: `tests/Feature/Efiling/FileControllerTest.php`

**Interfaces:**
- Produces: `legacyResponse(string $path, bool $download, string $name): Symfony\Component\HttpFoundation\Response` (was `StreamedResponse`).

- [ ] **Step 1: Write the failing tests** (append to `FileControllerTest.php`)

```php
it('redirects a legacy file to a signed url when the disk can sign', function () {
    Storage::fake('efiling_legacy');
    Storage::disk('efiling_legacy')->buildTemporaryUrlsUsing(fn ($path, $exp, $opts) => 'https://minio.test/legacy/'.$path.'?'.http_build_query($opts));
    Storage::disk('efiling_legacy')->put('Old/a.pdf', '%PDF-1.4 x');
    $file = efFcFile(DocumentStatusEnum::Filed, 'efiling_legacy', 'Old/a.pdf');
    app(EfAccessService::class)->grant('HRAAA1', [$file->document->efd_eft_id]);

    $this->actingAs($this->emp)->get(route('dashboard.module-efiling.files.show', $file))
        ->assertRedirect('https://minio.test/legacy/Old/a.pdf?');
    $this->actingAs($this->emp)->get(route('dashboard.module-efiling.files.show', [$file, 'download' => 1]))
        ->assertRedirectContains('a.pdf')->assertRedirectContains('attachment');
});

it('404s a missing legacy file even when the disk can sign', function () {
    Storage::fake('efiling_legacy');
    Storage::disk('efiling_legacy')->buildTemporaryUrlsUsing(fn () => 'https://minio.test/x');
    $file = efFcFile(DocumentStatusEnum::Filed, 'efiling_legacy', 'Old/gone.pdf');
    app(EfAccessService::class)->grant('HRAAA1', [$file->document->efd_eft_id]);

    $this->actingAs($this->emp)->get(route('dashboard.module-efiling.files.show', $file))->assertNotFound();
});
```

Also change the existing `'no longer on the file server'` expectation to `'no longer in the legacy archive'`.

- [ ] **Step 2: Run** `php artisan test --parallel tests/Feature/Efiling/FileControllerTest.php` — expect the redirect test and the wording assertion to FAIL.

- [ ] **Step 3: Implement.** In `legacyResponse`: keep `abort_unless(exists, 404, 'The legacy file is no longer in the legacy archive.')`; then if `$disk->providesTemporaryUrls()` return `redirect()->away(...)` built the same way as `temporaryUrl()`; else stream as today. Avoid duplicating the URL logic: give `temporaryUrl()` an optional `string $disk = self::NEW` last parameter and call it with `self::LEGACY`. Update blade wording "stay(s) on the file server" → "stay(s) in the legacy archive"; class/model docblocks "mounted share" → "legacy archive (share or MinIO)".

- [ ] **Step 4: Run** the same command — all PASS.

- [ ] **Step 5: Commit** `feat(efiling): serve legacy files by signed link when stored on MinIO`

### Task 2: Env toggle for the legacy disk

**Files:**
- Modify: `config/filesystems.php:87-95`
- Modify: `.env.example` (next to `EFILING_LEGACY_ROOT`)
- Test: `tests/Feature/Efiling/FileControllerTest.php` (one config test)

- [ ] **Step 1: Write the failing test**

```php
it('keeps the legacy disk on the share unless switched to minio', function () {
    expect(config('filesystems.disks.efiling_legacy.driver'))->toBe('local');
});
```
(Passes today; it guards the default. The minio branch is pure config — verified by Step 4.)

- [ ] **Step 2: Implement.** `'efiling_legacy' => env('EFILING_LEGACY_STORAGE', 'share') === 'minio' ? [s3 array] : [current local array]`. s3 array: driver `s3`, key/secret/region/endpoint from `MINIO_*`, `bucket` = `env('EFILING_LEGACY_BUCKET', env('MINIO_PRIVATE_BUCKET'))`, `root` = `env('EFILING_LEGACY_PREFIX', 'efiling-legacy')`, `use_path_style_endpoint` true, `visibility` private, `throw` false. Comment above it: one-time move, how to flip back. `.env.example`: add `EFILING_LEGACY_STORAGE=share`, `EFILING_LEGACY_BUCKET=`, `EFILING_LEGACY_PREFIX=efiling-legacy`.

- [ ] **Step 3: Run** `php artisan test --parallel tests/Feature/Efiling` and `vendor/bin/pint --dirty` — all green.

- [ ] **Step 4: Manual check (dev, if a copy exists):** `EFILING_LEGACY_STORAGE=minio php artisan efiling:import-legacy --dry-run` lists folders and reports `skipped_existing` for already-imported files.

- [ ] **Step 5: Commit** `feat(efiling): env toggle to read the legacy archive from MinIO`
