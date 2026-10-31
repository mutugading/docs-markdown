# E-Filing Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A new `Modules/Efiling` that files scanned documents under a per-type name, searches them by type fields, and indexes the ~584k legacy files in place.

**Architecture:** Standard module layering (Livewire → Service → Repository → Model) on `oracle_mgthris`. One pure class, `NamePattern`, owns building and parsing names; one class, `EfFileStorage`, is the only thing touching `Storage`. New files live on `minio_private`; legacy files stay on a read-only local disk `efiling_legacy` and are recorded by an artisan command.

**Tech Stack:** Laravel 12, Livewire 3, Flux UI Pro, nwidart/laravel-modules, Spatie Permission + Activitylog, Maatwebsite Excel, Pest 4 on SQLite.

**Spec:** `.docs-me/superpowers/specs/2026-10-13-efiling-module-design.md` — read it before Task 1; this plan only records the decisions the spec leaves open.

## Global Constraints

- Branch `feat/efiling-module` from `develop` (the current branch `fix/module-config-cache` has unrelated uncommitted work — do not carry it).
- All tables on `oracle_mgthris`, lower-case names `ef_*`, with the standard `migrationKey` / `DISABLE_MIGRATIONS` guard copied from `Modules/SelfService/database/migrations/2026_10_26_110000_create_hm_hier_approver_excl_table.php`. Every migration must run on SQLite.
- Primary keys: `$table->id()` (as `cm_menus` does), named `eft_id`, `eftf_id`, `efut_id`, `efd_id`, `efdf_id`, `efdv_id`. *(plan call — the spec only says "PK"; auto ids are what the bulk import needs.)*
- Audit columns `*_created_by`, `*_created_timestamp`, `*_modified_by`, `*_modified_timestamp` on every table; models set `CREATED_AT` / `UPDATED_AT` constants to them.
- Permissions (exact): `efiling-document-view`, `efiling-document-upload`, `efiling-document-edit`, `efiling-master-manage`.
- Route names prefix `dashboard.module-efiling.` *(plan call, house convention)*: `inbox`, `documents.index`, `documents.show`, `files.show`, `masters.types`, `masters.access`. URL prefix `dashboard/module-efiling`.
- Menu codes (exact, from spec): `efiling`, `efiling.inbox`, `efiling.search`, `efiling.masters`, `efiling.masters.types`, `efiling.masters.access`.
- Upload: PDF, JPG, PNG, max 40 MB each, from `Modules/Efiling/config/config.php` (`efiling.upload.mimes`, `efiling.upload.max_kb = 40960`). Livewire's global temp-upload rule (`config/livewire.php` `temporary_file_upload.rules`, default 12 MB) must be raised to `['required', 'file', 'max:40960']`; servers need `upload_max_filesize` ≥ 40M and `post_max_size` ≥ 45M (note it in the PR).
- New-file paths: inbox `efiling/inbox/{sys_id}/{uuid}.{ext}`; filed `efiling/{FOLDER}/{YEAR}/{NAME}.{ext}`, extra files `{NAME}-{n}.{ext}`, `n` from 2.
- Legacy disk `efiling_legacy`: local driver, root `env('EFILING_LEGACY_ROOT', '/mnt/efilling/Doc_Folder')`, in `config/filesystems.php`; add the key to `.env.example`. The app never writes, moves or deletes on it.
- Never `wire:navigate`. UI module components first, then Flux. FA5 icon names only.
- Tests: `tests/Feature/Efiling/` and `tests/Unit/Efiling/`; run `php artisan test --parallel <path>`. Activity log assertions via `Modules\Core\Models\MgtHris\Master\Logs\ActivityLog`.
- Commits: read `.github/COMMIT_CONVENTION.md` first; scope `efiling` (add it to the scope list in Task 1 if absent). Never push.

## Review Focus

1. **Two users file the same new name at the same moment** — the second hits the unique index; expected: their copy is deleted, their inbox row and file are untouched, they get "already filed — try again" and retrying attaches. Test in Task 5.
2. **A copy target already exists on MinIO** (stale object from an earlier failure) — expected: `EfFileStorage::copy` refuses rather than overwrites another document's file. Test in Task 5.
3. **Field values with spaces, lowercase, or file-unsafe characters** (`a/b`, `x:y`, `..`) in a pattern field or transaction number — expected: trimmed, unsafe rejected with a field error, never a path outside the type folder. Test in Task 2.
4. **Legacy share holds non-documents and odd layout** (`Thumbs.db`, `Desktop.ini`, `.temp`, a non-year subfolder like `CARPET PRODUCT/IND`, same name as `.pdf` and `.jpeg`) — expected: non-PDF/JPG/PNG skipped and counted, non-year subfolders skipped and listed, both extensions attach to one document. Test in Task 11.
5. **A user without the type guesses a document or file id in the URL** — expected: 404 on the document page (not a leak of its existence), 403 on `files.show`. Test in Tasks 8 and 9.

---

## File map

```
Modules/Efiling/
  module.json, composer.json                        (from module:make, trimmed)
  config/config.php                                 upload limits
  app/Enums/FieldKindEnum.php, DocumentStatusEnum.php
  app/Models/MgtHris/{EfType,EfTypeField,EfUserType,EfDocument,EfDocumentFile,EfDocumentValue}.php
  app/Support/NamePattern.php                       build/validate/parse names (pure)
  app/Interfaces/{EfTypeRepositoryInterface,EfUserTypeRepositoryInterface,EfDocumentRepositoryInterface}.php
  app/Repositories/{EloquentEfTypeRepository,EloquentEfUserTypeRepository,EloquentEfDocumentRepository}.php
  app/Data/FileDocumentData.php                     filing form → validated values
  app/Services/{EfTypeService,EfAccessService,EfDocumentService,EfFileStorage,LegacyImporter}.php
  app/Livewire/{InboxPage,DocumentSearchPage,DocumentShowPage,TypeMasterPage,AccessMasterPage}.php
  app/Http/Controllers/FileController.php
  app/Exports/EfAccessExport.php, app/Imports/EfAccessImport.php
  app/Jobs/{ExportEfAccessJob,ImportEfAccessJob}.php
  app/Console/Commands/ImportLegacyFiles.php
  app/Providers/{EfilingServiceProvider,RouteServiceProvider,RepositoryServiceProvider}.php
  database/migrations/2026_10_30_1000xx_*.php        tables, type seed, permissions+menu
  database/seeders/EfilingUserTypeSeeder.php
  resources/views/livewire/*.blade.php
  routes/web.php, routes/breadcrumbs.php
config/filesystems.php, config/livewire.php, config/logging.php, .env.example   (modified)
tests/Unit/Efiling/NamePatternTest.php
tests/Feature/Efiling/*.php
```

---

### Task 1: Module, tables, models, enums

**Files:**
- Create: the module via `php artisan module:make Efiling`, then delete generated files this plan does not use (api routes, example controller/views, assets). Keep providers.
- Create: `Modules/Efiling/database/migrations/2026_10_30_100000_create_ef_tables.php` (all six tables in one migration, key `ef_tables`), models, enums, `config/config.php`.
- Modify: `config/filesystems.php` (add `efiling_legacy`), `config/livewire.php` (20 MB rule), `.env.example` (`EFILING_LEGACY_ROOT=/mnt/efilling/Doc_Folder`), `modules_statuses.json` (`"Efiling": true`, if module:make didn't).
- Test: `tests/Feature/Efiling/SchemaTest.php`

**Interfaces — Produces:**
- `FieldKindEnum: string { Text='text', Number='number', Date='date', Select='select' }` with `label()`, `options()`, `valueColumn(): string` (`efdv_text|efdv_number|efdv_date`; Select → `efdv_text`).
- `DocumentStatusEnum: string { Inbox='inbox', Filed='filed' }` with `label()`, `badgeVariant()`.
- Models (namespace `Modules\Efiling\Models\MgtHris`), relations:
  - `EfType`: `fields(): HasMany` (active, ordered by `eftf_seq`), `allFields(): HasMany`, `documents(): HasMany`.
  - `EfTypeField`: `type(): BelongsTo`; casts `eftf_kind` → `FieldKindEnum`, `eftf_options` → `array`, `eftf_is_required`/`eftf_is_active` → `bool`.
  - `EfUserType`: `type(): BelongsTo`, `employee(): BelongsTo` (HmEmpData via `efut_hmemd_sys_id`).
  - `EfDocument`: `type()`, `files(): HasMany`, `values(): HasMany`; cast `efd_status` → `DocumentStatusEnum`, `efd_filed_at` → datetime; `scopeAccessibleBy(Builder $q, string $sysId)` = `whereIn('efd_eft_id', EfUserType::select('efut_eft_id')->where('efut_hmemd_sys_id', $sysId))`; `scopeFiled()`.
  - `EfDocumentFile`: `document(): BelongsTo`; `isLegacy(): bool` (`efdf_disk === 'efiling_legacy'`).
  - `EfDocumentValue`: `document()`, `field()`; `value(): mixed` reads the column for the field's kind.
- `EfDocument`, `EfDocumentFile`, `EfDocumentValue` use `LogsActivityWithDescription` with `$logAttributes` naming user-facing columns (name, trans no, note, status / label, original name / text, number, date).

Column decisions beyond the spec: `eft_folder` **unique** (two types sharing a folder would let paths collide); `eft_code` 30, `eft_name` 100, `eft_folder` 100, `eft_name_pattern` 200; `efd_name` 255, `efd_trans_no` 100; `efdf_path` 500, `efdf_original_name` 255; `efdv_number` decimal(18,4); `efdv_date` date; `efdv_text` 1000; flags as integer 0/1. Indexes exactly as the spec lists plus `ef_documents(efd_owner_sys_id, efd_status)` and `ef_documents(efd_trans_no)`. No FK from `efd_owner_sys_id` (legacy rows use `SYSTEM`).

- [ ] **Step 1: Write the failing test**

```php
it('creates every ef table on sqlite', function () {
    foreach (['ef_types', 'ef_type_fields', 'ef_user_types', 'ef_documents', 'ef_document_files', 'ef_document_values'] as $t) {
        expect(Schema::connection('oracle_mgthris')->hasTable($t))->toBeTrue();
    }
});

it('treats inbox rows (null identity) as non-duplicates but rejects a second filed name', function () {
    $type = EfType::create([...SHP, 'eft_folder' => 'SHP', 'eft_name_pattern' => 'SHP-{trans_no}']);
    EfDocument::create(['efd_status' => 'inbox', 'efd_owner_sys_id' => 'HR1']);
    EfDocument::create(['efd_status' => 'inbox', 'efd_owner_sys_id' => 'HR1']);
    EfDocument::create(['efd_eft_id' => $type->eft_id, 'efd_year' => 2026, 'efd_name' => 'SHP-1', 'efd_status' => 'filed', 'efd_owner_sys_id' => 'HR1']);
    expect(fn () => EfDocument::create(['efd_eft_id' => $type->eft_id, 'efd_year' => 2026, 'efd_name' => 'SHP-1', 'efd_status' => 'filed', 'efd_owner_sys_id' => 'HR1']))
        ->toThrow(QueryException::class);
});

it('limits accessibleBy to granted types', function () { /* two types, grant one, expect only its doc */ });
```

- [ ] **Step 2:** Run `php artisan test --parallel tests/Feature/Efiling/SchemaTest.php` — FAIL (tables/classes missing).
- [ ] **Step 3:** Generate the module, write the migration, models, enums, config, disk and env key.
- [ ] **Step 4:** Run the same command — PASS. Also `ACTIVITY_LOGGER_DB_CONNECTION= php artisan test --parallel tests/Feature/Efiling` — PASS.
- [ ] **Step 5:** `vendor/bin/pint --dirty`; commit `feat(efiling): add module, tables and models`.

---

### Task 2: `NamePattern`

**Files:**
- Create: `Modules/Efiling/app/Support/NamePattern.php`
- Test: `tests/Unit/Efiling/NamePatternTest.php`

**Interfaces — Produces:**
- `NamePattern::validate(string $pattern, array $fieldKinds): array` — `$fieldKinds` is `code => FieldKindEnum`; returns a list of error strings (empty = valid). Errors: `{trans_no}` missing or more than once; unknown code; code whose kind is not text/select; literal text containing an unsafe character.
- `NamePattern::placeholders(string $pattern): array` — field codes used (excluding `trans_no`), in order.
- `NamePattern::build(string $pattern, string $transNo, array $values): string` — trims each value; throws `InvalidArgumentException` naming the placeholder when a value is blank or unsafe.
- `NamePattern::isSafe(string $value): bool` — false for empty, `.`/`..`, or any of `/ \ : * ? " < > |` and control chars.
- `NamePattern::parse(string $pattern, string $name): ?array` — exact match of a name against the pattern (`{trans_no}` → `\d+`, field → `[^-]+`, literals quoted); returns `['trans_no' => ..., code => value, ...]` or null. Used by the legacy import.

- [ ] **Step 1: Write the failing test**

```php
it('builds MCM names', fn () => expect(NamePattern::build('MCM-{source_type}-{trans_no}', ' 2026010002 ', ['source_type' => 'ADVIC']))->toBe('MCM-ADVIC-2026010002'));
it('builds plain names', fn () => expect(NamePattern::build('SHP-{trans_no}', '2026000123', []))->toBe('SHP-2026000123'));
it('rejects unsafe values', fn () => expect(fn () => NamePattern::build('SHP-{trans_no}', '12/34', []))->toThrow(InvalidArgumentException::class));
it('rejects dot-dot', fn () => expect(NamePattern::isSafe('..'))->toBeFalse());
it('requires trans_no exactly once', function () {
    expect(NamePattern::validate('SHP-', []))->not->toBeEmpty()
        ->and(NamePattern::validate('{trans_no}-{trans_no}', []))->not->toBeEmpty();
});
it('rejects unknown and non-text codes', function () {
    expect(NamePattern::validate('X-{nope}-{trans_no}', []))->not->toBeEmpty()
        ->and(NamePattern::validate('X-{amt}-{trans_no}', ['amt' => FieldKindEnum::Number]))->not->toBeEmpty()
        ->and(NamePattern::validate('MCM-{source_type}-{trans_no}', ['source_type' => FieldKindEnum::Select]))->toBe([]);
});
it('parses a legacy name back', function () {
    expect(NamePattern::parse('MCM-{source_type}-{trans_no}', 'MCM-BPS-2026020214'))->toBe(['source_type' => 'BPS', 'trans_no' => '2026020214'])
        ->and(NamePattern::parse('MCM-{source_type}-{trans_no}', 'EIN-1 BEKAERT'))->toBeNull();
});
```

- [ ] **Step 2:** `php artisan test --parallel tests/Unit/Efiling/NamePatternTest.php` — FAIL.
- [ ] **Step 3:** Implement (static methods, no dependencies).
- [ ] **Step 4:** Same command — PASS.
- [ ] **Step 5:** Commit `feat(efiling): build and parse document names from a type pattern`.

---

### Task 3: Types — seed from legacy, service, Type master page

**Files:**
- Create: `database/migrations/2026_10_30_100100_seed_ef_types_from_legacy.php` (key `ef_types_seed`), `Interfaces/EfTypeRepositoryInterface.php`, `Repositories/EloquentEfTypeRepository.php`, `Services/EfTypeService.php`, `Livewire/TypeMasterPage.php` + view, `Providers/RepositoryServiceProvider.php` (bind all three repositories as they appear), routes + breadcrumb for `masters.types`.
- Test: `tests/Feature/Efiling/TypeMasterTest.php`

**Seed migration:** if `Schema::connection('oracle_mgtapps')->hasTable('efill_type_data')` is false → return. For each row not already present by `eft_code`: `eft_code = etd_sys_id`, `eft_name = etd_description`, `eft_folder = etd_sys_id`, `eft_name_pattern = trim(etd_format_file, '"\' ').'{trans_no}'`, active. (Legacy `PDN` has prefix `PDN` with no dash — keep as is.) `down()` deletes nothing.

**Interfaces — Produces:**
- `EfTypeService::saveType(array $data, ?int $id): EfType` — validates pattern via `NamePattern::validate` against the type's active fields; error key `pattern`.
- `EfTypeService::saveField(int $typeId, array $data, ?int $fieldId): EfTypeField` — enforces the lock rules: if the field has stored values, `eftf_code` and `eftf_kind` cannot change and an option still used by a value cannot be removed (error key `options`, message names the option).
- `EfTypeService::deactivateField(int $fieldId): void` — refuses when the code is a placeholder of the type's pattern.
- `EfTypeService::hasDocuments(int $typeId): bool` — drives the editor's warning "Existing documents keep their names; only new filings use the new pattern."
- `EfTypeService::activeTypesFor(string $sysId): Collection` — active types the user is granted, for dropdowns.
- `TypeMasterPage`: list of types (search, paginate), drawer with type form + field list (add/edit/deactivate, reorder by `eftf_seq`). Gate `permission:efiling-master-manage`.

- [ ] **Step 1: Write the failing tests** — names and core assertions:
  - `it('saves a type with a valid pattern')`; `it('rejects a pattern without {trans_no}')` → `assertHasErrors('pattern')`.
  - `it('allows changing the pattern of a type that has documents and keeps old names')` — after save, the existing document's `efd_name` unchanged.
  - `it('locks kind and code of a field with values')`; `it('refuses to remove a select option in use')`; `it('allows adding a select option in use')`.
  - `it('refuses to deactivate a field used in the pattern')`.
  - `it('blocks the page without efiling-master-manage')` → 403.
- [ ] **Step 2:** Run `php artisan test --parallel tests/Feature/Efiling/TypeMasterTest.php` — FAIL.
- [ ] **Step 3:** Implement migration, repository, service, page.
- [ ] **Step 4:** Same command — PASS.
- [ ] **Step 5:** Commit `feat(efiling): manage document types, fields and name patterns`.

---

### Task 4: Access — repository, service, grant seeder

**Files:**
- Create: `Interfaces/EfUserTypeRepositoryInterface.php`, `Repositories/EloquentEfUserTypeRepository.php`, `Services/EfAccessService.php`, `database/seeders/EfilingUserTypeSeeder.php`.
- Test: `tests/Feature/Efiling/AccessTest.php`

**Interfaces — Produces:**
- `EfAccessService::typeIdsFor(string $sysId): array<int>`; `canAccessType(string $sysId, int $typeId): bool`; `grant(string $sysId, array $typeIds): int`; `revoke(string $sysId, array $typeIds): int`; `sync(string $sysId, array $typeIds): array{granted:int, revoked:int}`.
- Seeder: reads `Modules\Core\Models\MgtApps\Efiling\EfillUserAccess` (skip with a message if the table is unreachable), maps `eua_user_id` → `HmEmpData::where('hmemd_user_id', ...)` (global scope = active only) → `hmemd_sys_id`, `eua_etd_sys_id` → `EfType.eft_code`; inserts missing pairs only; prints counts and lists unmatched users/types via `$this->command->warn()`. It also gives each mapped employee the direct permission `efiling-document-view` (only view — upload is granted through roles), skipping it if not yet seeded.

- [ ] **Step 1: Write the failing tests**
  - `it('grants, revokes and syncs types')`.
  - `it('seeds grants from legacy access, skipping unknown users and types')` — fixture: `efill_user_access` rows for one active employee (`hmemd_user_id` match), one unknown user, one unknown type; expect one `ef_user_types` row and the employee has `efiling-document-view`.
  - `it('re-runs the seeder without duplicating')` — run twice, same count.
- [ ] **Step 2:** Run `php artisan test --parallel tests/Feature/Efiling/AccessTest.php` — FAIL.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Same command — PASS.
- [ ] **Step 5:** Commit `feat(efiling): type access and seeding from legacy grants`.

---

### Task 5: Storage and filing service

**Files:**
- Create: `Services/EfFileStorage.php`, `Data/FileDocumentData.php`, `Interfaces/EfDocumentRepositoryInterface.php`, `Repositories/EloquentEfDocumentRepository.php` (CRUD part; search arrives in Task 7), `Services/EfDocumentService.php`.
- Test: `tests/Feature/Efiling/FilingTest.php`

**Interfaces — Produces:**
- `EfFileStorage` (only class touching `Storage`; disk names as constants `NEW = 'minio_private'`, `LEGACY = 'efiling_legacy'`):
  - `storeInbox(UploadedFile $file, string $sysId): string` → path.
  - `filedPath(EfType $type, int $year, string $name, int $n, string $ext): string` — `n === 1` → clean name.
  - `copy(string $from, string $to): void` — throws `RuntimeException` if `$to` exists or the copy fails. Never overwrites.
  - `delete(string $disk, string $path): bool` — no-op returning false for `LEGACY`; logs and returns false on failure.
  - `temporaryUrl(string $path, bool $download, string $downloadName): string` — 5 minutes; `ResponseContentDisposition` set when downloading.
  - `legacyResponse(string $path, bool $download, string $name): StreamedResponse` — `abort(404, 'The legacy file is no longer on the file server.')` when missing.
- `FileDocumentData::fromForm(array $form, EfType $type): self` — properties `typeId, year, transNo, values (code => raw), note, label`; `static rules(EfType $type): array` builds per-field rules: required if `eftf_is_required` or a placeholder; `numeric` for Number; `date` for Date; `Rule::in(options)` for Select; `max:1000` text. Keys `form.fields.{code}`.
- `EfDocumentService`:
  - `upload(array $files, string $sysId): Collection<EfDocument>` — one inbox doc + file row each.
  - `preview(FileDocumentData $data): array{name:string, existing:?EfDocument}` — for the live name + "will be added to X" message.
  - `file(int $inboxDocId, FileDocumentData $data, string $sysId): EfDocument` — the algorithm below.
  - `discard(int $inboxDocId, string $sysId): void` — only owner, only `inbox`.
  - `nextFileNumber(EfDocument $doc): int` — max over its files of: basename == name → 1, `name-k` → k, otherwise 1; plus 1; min 2.

**`file()` algorithm** (copy → commit → delete, from the spec):
1. Load inbox doc owned by `$sysId` with status inbox, else `ModelNotFoundException`. Check `canAccessType`. Build name with `NamePattern::build` (catch → `ValidationException` on `form.trans_no` / `form.fields.{code}`).
2. `existing` = filed doc with (type, year, name). `n = existing ? nextFileNumber(existing) : 1`. `target = filedPath(...)`.
3. `copy(inboxPath, target)` — failure → toast-able `RuntimeException`, nothing changed.
4. `DB::connection('oracle_mgthris')->transaction(...)`: new → update inbox doc to filed with identity, `efd_filed_at/by`, note; update its file row path/label. Existing → move the file row to `existing` (update `efdf_efd_id`, path, label), append note if given *(plan call: new note replaces only when non-blank)*, delete the inbox doc. Then upsert values for every submitted field (blank non-required → delete the value row).
5. On any exception in 4: `delete(NEW, target)`, rethrow. A unique-index violation is rethrown as `RuntimeException('This name was just filed by someone else — press File again to add to it.')`.
6. After commit: `delete(NEW, inboxPath)`; on failure log `warning` with the path.

- [ ] **Step 1: Write the failing tests** (Storage::fake('minio_private'); `buildTemporaryUrlsUsing` stub)
  - `it('files a new number under the clean name')` — path `efiling/SHP/2026/SHP-2026000123.pdf` exists, inbox path gone, status filed.
  - `it('attaches to an existing name as -2 and overwrites values')` — Status Draft → Signed; inbox row deleted; second file at `…-2.pdf`.
  - `it('keeps ADVIC and BPS with the same number apart')` — two docs, both clean names.
  - `it('keeps LDN and LDN SALES apart')` — two docs, folders `LDN/` and `LDN SALES/`.
  - `it('leaves the inbox untouched when the copy fails')` — pre-create the target object → `RuntimeException`, doc still inbox, inbox file still there, target untouched (Review Focus 2).
  - `it('removes the copy when the transaction fails')` — insert a conflicting filed doc between preview and file via a model `creating` hook or by pre-inserting with the same identity under a raw insert after `existing` lookup; assert target deleted, inbox intact, message says "press File again" (Review Focus 1).
  - `it('rejects a missing required field and a select value outside options')` — no storage change.
  - `it('rejects a transaction number with a slash')` — error on `form.trans_no` (Review Focus 3).
  - `it('discards only own inbox docs')`.
- [ ] **Step 2:** `php artisan test --parallel tests/Feature/Efiling/FilingTest.php` — FAIL.
- [ ] **Step 3:** Implement.
- [ ] **Step 4:** Same command — PASS.
- [ ] **Step 5:** Commit `feat(efiling): file inbox uploads under their type name`.

---

### Task 6: Inbox page

**Files:**
- Create: `Livewire/InboxPage.php`, `resources/views/livewire/inbox-page.blade.php`, route `inbox` (`permission:efiling-document-upload`), breadcrumb `E-Filing > Inbox`.
- Test: `tests/Feature/Efiling/InboxPageTest.php`

**Behaviour:** left: upload (`WithFileUploads`, multiple, rules from config) + list of own inbox docs; right: preview (`<iframe>` for PDF, `<img>` for images, `src = route('dashboard.module-efiling.files.show', $fileId)`) and the form. Type select = `EfTypeService::activeTypesFor`. Changing type re-renders fields. `updated()` on type/year/trans_no/fields recomputes `EfDocumentService::preview` and shows `{name}.{ext}` and, when `existing`, a callout "This will be added to existing document {name}". `file()` → `butter-success`, select next inbox item; `RuntimeException` → `butter-error` + `Log::error`. `discard()` behind `x-ui::modal.confirmation`.

- [ ] **Step 1: Write the failing tests**
  - `it('uploads two files as two inbox documents')`.
  - `it('shows only my inbox')`.
  - `it('previews the built name and the attach warning')` — `assertSee('MCM-ADVIC-2026010002.pdf')`, `assertSee('will be added to existing document')`.
  - `it('files from the page and toasts')` — `assertDispatched('butter-success')`.
  - `it('lists only granted active types')`.
- [ ] **Step 2:** Run — FAIL. **Step 3:** Implement. **Step 4:** Run — PASS.
- [ ] **Step 5:** Commit `feat(efiling): inbox page to upload, preview and file`.

---

### Task 7: Search

**Files:**
- Modify: `Repositories/EloquentEfDocumentRepository.php` (+ interface) — `search(string $sysId, array $filters, int $perPage = 20): LengthAwarePaginator`.
- Create: `Livewire/DocumentSearchPage.php` + view, route `documents.index` (`permission:efiling-document-view`), breadcrumb `E-Filing > Documents`.
- Test: `tests/Feature/Efiling/SearchTest.php`

**Filters** (`$filters` keys): `type_id`, `year`, `name` *(plan call: one box, `UPPER(efd_name) LIKE UPPER('%x%')` — covers the spec's transaction-number starts-with and also finds irregular legacy names)*, `note` (`UPPER LIKE %x%`), `fields` = `code => value | ['from'=>..,'to'=>..]`, applied only when `type_id` is set. Each field filter is `whereExists` on `ef_document_values` joined to the field id: Select exact, Text `UPPER LIKE`, Number/Date `>= from`, `<= to` (either side optional). Always `filed()->accessibleBy($sysId)`, eager `type`, `values.field`, `withCount('files')`, order `efd_filed_at desc`. Wildcards in user input escaped.

- [ ] **Step 1: Write the failing tests**
  - `it('filters by select value')`, `it('filters by number range')`, `it('filters by date range')`, `it('filters by note case-insensitively')`, `it('finds by part of the name')`.
  - `it('shows nothing for a type without a grant')`.
  - `it('ignores field filters until one type is picked')`.
- [ ] **Step 2:** Run — FAIL. **Step 3:** Implement. **Step 4:** Run — PASS.
- [ ] **Step 5:** Commit `feat(efiling): search filed documents by type fields`.

---

### Task 8: Document page — values, add file, rename, history

**Files:**
- Modify: `Services/EfDocumentService.php` — add `updateValues`, `addFile`, `rename`.
- Create: `Livewire/DocumentShowPage.php` + view, route `documents.show/{document}` (`permission:efiling-document-view`), breadcrumb trail ending in `{efd_name}`.
- Test: `tests/Feature/Efiling/DocumentPageTest.php`

**Interfaces — Produces:**
- `updateValues(int $docId, array $values, ?string $note, string $sysId): void` — requires `efiling-document-upload` + type access; rejects (validation error) any key that is a placeholder of the type's pattern.
- `addFile(int $docId, UploadedFile $file, ?string $label, string $sysId): EfDocumentFile` — store straight to `filedPath(..., nextFileNumber($doc), ...)` with `put`, never overwriting (existence check first).
- `rename(int $docId, string $transNo, array $nameValues, string $sysId): EfDocument` — requires `efiling-document-edit`; refuses (validation error `rename`) if any file `isLegacy()`, or if the new name is taken in the same type+year; otherwise copy every file to its new path (same `n` suffix as before), transaction updates `efd_name`, `efd_trans_no`, name-field values and file paths; on failure delete the copies made; after commit delete the old objects. Builds from the type's **current** pattern.
- Page: mount loads `EfDocument::filed()->accessibleBy($sysId)->findOrFail($id)` → 404 for other types (Review Focus 5). History = `ActivityLog` rows where (`subject_type` = EfDocument and id = doc) or (EfDocumentFile, ids of its files) or (EfDocumentValue, ids of its values), newest first, paginated 20.

- [ ] **Step 1: Write the failing tests**
  - `it('404s a document of a type I cannot access')`.
  - `it('edits non-name values with upload permission and logs it')` — `ActivityLog` row for the value change.
  - `it('refuses name fields in updateValues')`.
  - `it('adds a file as the next number')` — doc with `NAME.pdf` and `NAME-2.pdf` → `NAME-3.pdf`.
  - `it('renames MCM ADVIC to BPS and moves every file')` — old paths gone, new paths present, history entry.
  - `it('refuses a rename onto a taken name')`, `it('refuses a rename without efiling-document-edit')`, `it('refuses to rename a document with legacy files')`.
  - `it('leaves the document unchanged when a rename copy fails')` — pre-create one target.
- [ ] **Step 2:** Run — FAIL. **Step 3:** Implement. **Step 4:** Run — PASS.
- [ ] **Step 5:** Commit `feat(efiling): document page with values, extra files, rename and history`.

---

### Task 9: File preview and download

**Files:**
- Create: `Http/Controllers/FileController.php` (`show(Request $request, EfDocumentFile $file)`), route `files/{file}` named `files.show` behind `auth` only — the controller decides.
- Test: `tests/Feature/Efiling/FileControllerTest.php`

**Rule:** inbox doc → allowed only to `efd_owner_sys_id` holding `efiling-document-upload`; filed doc → `efiling-document-view` + `canAccessType`; else `abort(403)`. MinIO → `redirect()->away(EfFileStorage::temporaryUrl(...))`; legacy → `legacyResponse(...)`. `?download=1` → attachment named `{efd_name}{-n}.{ext}` (inbox: original name).

- [ ] **Step 1: Write the failing tests**
  - `it('redirects the owner to a signed url for an inbox file')`.
  - `it('403s someone else\'s inbox file')`, `it('403s a filed file of a type without a grant')` (Review Focus 5).
  - `it('streams a legacy file')` — `Storage::fake('efiling_legacy')`, `assertOk()`, header `content-type: application/pdf`.
  - `it('404s a legacy file that is gone')` — `assertNotFound()`, `assertSee('no longer on the file server')`.
- [ ] **Step 2:** Run — FAIL. **Step 3:** Implement. **Step 4:** Run — PASS.
- [ ] **Step 5:** Commit `feat(efiling): serve previews and downloads through access checks`.

---

### Task 10: Access master page with Excel import/export

**Files:**
- Create: `Livewire/AccessMasterPage.php` + view, `Exports/EfAccessExport.php`, `Imports/EfAccessImport.php`, `Jobs/ExportEfAccessJob.php`, `Jobs/ImportEfAccessJob.php`, route `masters.access` (`permission:efiling-master-manage`), breadcrumb.
- Test: `tests/Feature/Efiling/AccessMasterTest.php`

Copy the shape of Core's `EfilingAccessManager` / `EfilingAccessService::import|export` / `EfilingAccessImport` / `EfilingAccessExport` / `EfilingAccessRowData` (read them first), retargeted: rows keyed on **NIK** (not legacy user id) → `hmemd_sys_id`; type column = `eft_code`; action `GRANT|REVOKE`. Export: columns NIK, Name, Type Code, Type Name; NIK via `FormatsNikForExport` + `AfterSheet` `applyNikColumnAsText(..., 'A', firstDataRow: 2)`. Jobs notify with `ReportStatusNotification`. Page: pick an employee, tick types, save (`EfAccessService::sync`); copy access from another employee.

- [ ] **Step 1: Write the failing tests**
  - `it('saves an employee\'s types')`.
  - `it('imports grant and revoke rows and reports unknown NIK or type')`.
  - `it('exports NIK as text')` — cell `A2` data type `TYPE_STRING` for NIK `1234`.
  - `it('blocks without efiling-master-manage')`.
- [ ] **Step 2:** Run — FAIL. **Step 3:** Implement. **Step 4:** Run — PASS.
- [ ] **Step 5:** Commit `feat(efiling): access master with excel import and export`.

---

### Task 11: Legacy import command

**Files:**
- Create: `Services/LegacyImporter.php`, `Console/Commands/ImportLegacyFiles.php` (`efiling:import-legacy {--type=} {--year=} {--dry-run}`), log channel `efiling_legacy_import` in `config/logging.php` (daily, `storage/logs/efiling-legacy-import.log`).
- Test: `tests/Feature/Efiling/LegacyImportTest.php`

**Interfaces — Produces:**
- `LegacyImporter::run(?string $typeCode, ?int $year, bool $dryRun, ?callable $progress = null): array` — report keys `imported`, `attached`, `skipped_existing`, `skipped_extension`, `no_trans_no`, `no_year_folder`, `skipped_subfolders` (list), `folders_without_type` (list).

**Algorithm:**
1. Top-level directories of `efiling_legacy` → match `eft_folder` (active or not). Unmatched → `folders_without_type`.
2. Per type folder: `files($folder)` = no-year files (year from `lastModified()`, counted in `no_year_folder`); `directories($folder)`: `^\d{4}$` → year folder, else → `skipped_subfolders`. Honour `--type` (by `eft_code`) and `--year`.
3. Keep only extensions `pdf|jpg|jpeg|png` (case-insensitive); else `skipped_extension`.
4. Per 1,000 files, one transaction on `oracle_mgthris`, query builder only (no model events, so no per-row activity log):
   - Drop paths already in `ef_document_files` (`efdf_disk = efiling_legacy`, `whereIn efdf_path`).
   - Name = basename without extension. Group by (type, year, name) — `x.pdf` + `x.jpeg` → one document (Review Focus 4).
   - Existing docs for those identities → attach (`attached`); others insert `ef_documents` (`filed`, owner/filed_by `SYSTEM`, `efd_filed_at` = newest mtime in the group, `efd_trans_no` per step 5), then re-select their ids by identity.
   - Insert `ef_document_files`: `efdf_disk`, `efdf_path` (relative), `efdf_original_name` = basename, `efdf_size` from `size()`, `efdf_mime` from extension map, `efdf_label = 'legacy'`.
   - Insert `ef_document_values` from `NamePattern::parse` for Select fields whose value is in the options (MCM `source_type`).
5. `efd_trans_no`: prefix = pattern text before the first `{`; if name matches `^{preg_quote(prefix)}(.*-)?(\d+)$` take group 2, else null (`no_trans_no`).
6. `--dry-run`: same walk and counts, no writes. Console progress per folder/year; log each chunk to the channel; one `activity()` summary entry at the end with the report.

- [ ] **Step 1: Write the failing tests** (`Storage::fake('efiling_legacy')`, put tiny files)
  - `it('imports clean, MCM and irregular names')` — `SHP/2026/SHP-1.pdf` → trans_no `1`; `MCM/2026/MCM-BPS-2026020214.pdf` → value `BPS` (MCM has select `source_type` with `BPS`); `EIN/2025/EIN-5 BEKAERT.pdf` → name kept, trans_no null.
  - `it('adds nothing on a re-run')`.
  - `it('skips folders without a type and non-year subfolders and lists them')` — `GRN_MERGE/…`, `CARPET PRODUCT/IND/…`.
  - `it('skips Thumbs.db and .temp files')`, `it('imports a no-year file using its modified year')`.
  - `it('puts x.pdf and x.jpeg in one document')`.
  - `it('attaches a legacy file to a matching document filed in the new app')`.
  - `it('writes nothing on --dry-run')` — `artisan('efiling:import-legacy', ['--dry-run' => true])->assertSuccessful()`, zero rows.
- [ ] **Step 2:** Run — FAIL. **Step 3:** Implement. **Step 4:** Run — PASS.
- [ ] **Step 5:** Commit `feat(efiling): index legacy files in place with efiling:import-legacy`.

---

### Task 12: Permissions, roles and sidebar

**Files:**
- Create: `database/migrations/2026_10_30_100200_add_efiling_permissions_and_menu.php` (key `efiling_permissions_and_menu`), modelled on `Modules/SelfService/database/migrations/2026_10_26_100200_add_hier_releaser_permissions_and_menu.php` and `Modules/Core/database/migrations/2026_09_16_090000_sync_sidebar_menus_with_permissions.php`.
- Test: `tests/Feature/Efiling/MenuMigrationTest.php`

**Content:** `Permission::firstOrCreate` the four permissions (guard `web`); give all four to `Super Admin` if the role exists *(plan call; other roles via Role management)*. Menus (upsert on `code`, parent by code, skip missing parent):

| code | parent | name | type | icon | route | gate |
|---|---|---|---|---|---|---|
| `efiling` | — | E-Filing | module | `fas fa-folder-open` | — | — |
| `efiling.inbox` | `efiling` | Inbox | item | `fas fa-inbox` | `dashboard.module-efiling.inbox` | `efiling-document-upload` |
| `efiling.search` | `efiling` | Documents | item | `fas fa-search` | `dashboard.module-efiling.documents.index` | `efiling-document-view` |
| `efiling.masters` | `efiling` | Masters | group | `fas fa-folder` | — | — |
| `efiling.masters.types` | `efiling.masters` | Document Types | item | `fas fa-tags` | `dashboard.module-efiling.masters.types` | `efiling-master-manage` |
| `efiling.masters.access` | `efiling.masters` | User Access | item | `fas fa-user-lock` | `dashboard.module-efiling.masters.access` | `efiling-master-manage` |

Labels must match the page headings and breadcrumbs from Tasks 3, 6, 7, 10. End with `MenuTreeService::clearAllCache()` and `forgetCachedPermissions()`. `down()` removes the menu rows only.

- [ ] **Step 1: Write the failing test** — `it('is idempotent')`: run the migration's `up()` twice (`(require $path)->up()`), expect 6 `cm_menus` rows with `code like 'efiling%'`, 4 permissions, Super Admin has `efiling-document-view`, `efiling.inbox` gated on exactly `efiling-document-upload`.
- [ ] **Step 2:** Run — FAIL. **Step 3:** Implement. **Step 4:** Run — PASS.
- [ ] **Step 5:** Full suite `php artisan test --parallel` and `vendor/bin/pint --test` — both green.
- [ ] **Step 6:** Commit `feat(efiling): permissions and sidebar entries`.

---

## After the plan

- Draft the PR to `.docs-me/PR_DRAFT.md` from the template (screenshots of inbox, search, document page, type editor). Note: raise `upload_max_filesize` to ≥ 40M and `post_max_size` to ≥ 45M, mount the share at `EFILING_LEGACY_ROOT` on every app server, then run in order: migrate → `php artisan db:seed --class="Modules\Efiling\Database\Seeders\EfilingUserTypeSeeder"` → set MCM's `source_type` field and pattern in the type editor → `php artisan efiling:import-legacy --dry-run` → `php artisan efiling:import-legacy`.
- The MCM setup must happen **before** the import, or MCM legacy documents get no `source_type` value.
