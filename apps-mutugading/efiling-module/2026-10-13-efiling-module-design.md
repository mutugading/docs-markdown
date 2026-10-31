# E-Filing Module — Design

**Date:** 2026-10-13
**Module:** `Modules/Efiling` (new)
**Replaces:** the legacy PHP E-Filing app. New files go to MinIO; existing files are indexed where they are.

## Why

The legacy app stores files in a folder on its own server. A user uploads into a
personal temporary folder, previews the file (mostly PDF), renames it to the
transaction number printed on the paper, then moves it into a shared
`{type}/{year}` folder. The only thing you can search on is the file name, so
there is no way to answer questions like "which of these documents are still
drafts and which are already signed?".

**Success:** a user finds any filed document in seconds by type, year,
transaction number, the type's own fields (status, supplier, amount, date…) or
a word in its note, without browsing folders.

## Decisions

| Topic | Decision |
|---|---|
| Legacy files | Indexed in place, not copied. An import command records each file on the legacy share as a filed document; the app serves it read-only from there. See **Legacy import**. |
| Document types | Owned by the new module (`ef_types`), seeded once from `MGTAPPS.EFILL_TYPE_DATA`, then managed only here. |
| Access | By type only. New table `ef_user_types`, keyed on the user's `hmemd_sys_id`. No per-document sharing. |
| Draft vs signed | One document record holds several files side by side. Status is a per-type field, not a lifecycle. |
| Metadata | Fields defined per type (`text`, `number`, `date`, `select`), stored one row per value. No tags; free text goes in the note. |
| Transaction number | Typed by the user from the paper; never generated. |
| File name | Built from a per-type name pattern (e.g. `MCM-{source_type}-{trans_no}`). The built name (no extension) is the document's identity, unique per type + year. Filing under an existing name attaches the file to that document. |
| Shared prefixes | Allowed. Two types may use the same prefix (`LDN`, `LDN SALES`); the folder comes from the type, so they never collide. |
| Storage | `minio_private` disk. Every preview and download goes through the app. |
| Search inside PDF / OCR | Out of scope. |

## Data

All tables on `oracle_mgthris`, with the `EF_` prefix, audit columns
(`*_created_by`, `*_created_timestamp`, `*_modified_by`, `*_modified_timestamp`)
and the standard `migrationKey` / `DISABLE_MIGRATIONS` guard. Every migration
must run on SQLite (CI).

### `ef_types` — document types
| Column | Notes |
|---|---|
| `eft_id` | PK |
| `eft_code` | unique, e.g. `SHP` |
| `eft_name` | display name |
| `eft_folder` | storage folder name, e.g. `MCM`, `LDN SALES` (legacy folder name) |
| `eft_name_pattern` | file name pattern, e.g. `SHP-{trans_no}`, `MCM-{source_type}-{trans_no}` |
| `eft_is_active` | inactive types cannot receive new documents |

**Name pattern.** Literal text plus placeholders. `{trans_no}` is the typed
transaction number and must appear exactly once. `{<field_code>}` is the value of
one of the type's own fields (`text` or `select` only). Any field used in the
pattern is required when filing, whatever its `eftf_is_required` says. Values
are inserted as typed, after trimming; a value containing `/`, `\` or other
characters not safe in a file name is rejected. Examples:

| Type | Pattern | Built name |
|---|---|---|
| SHP | `SHP-{trans_no}` | `SHP-2026000123` |
| MCM | `MCM-{source_type}-{trans_no}` | `MCM-ADVIC-2026010002`, `MCM-BPS-2026020214` |
| LDN | `LDN-{trans_no}` | `LDN-2026000001` |
| LDN SALES | `LDN-{trans_no}` | `LDN-2026000001` (different folder, different document) |

For MCM, `source_type` is a `select` field (`ADVIC` = advance payment, `BPS` =
normal payment, …). MCM is a bank transfer statement for a payment; ADVIC and BPS
statements with the same number are different documents, which is why the
source type is part of the identity.

The pattern can be edited at any time. A change applies to new filings only:
existing documents keep their stored `efd_name` and files. Because identity is
the stored name, a document filed under the old pattern is not matched by a
filing under the new one. The type editor warns about this when the type
already has documents.

A seeding migration copies `EFILL_TYPE_DATA` once, matching on code so a re-run
changes nothing. It sets `eft_folder` to the legacy code (`etd_sys_id`, which is the legacy
folder name) and `eft_name_pattern` to `{prefix}{trans_no}` from the legacy
`etd_format_file` (without its quotes). Types like MCM get their field and
pattern set afterwards in the type editor. The migration is skipped when the
legacy table is not reachable (CI).

### `ef_type_fields` — per-type field definitions
| Column | Notes |
|---|---|
| `eftf_id` | PK |
| `eftf_eft_id` | FK → `ef_types` |
| `eftf_code` | unique within the type, e.g. `status`, `supplier` |
| `eftf_label` | shown on forms and filters |
| `eftf_kind` | `text` \| `number` \| `date` \| `select` (backed enum `FieldKindEnum`) |
| `eftf_options` | `VARCHAR2(1000)` JSON array, `select` only, e.g. `["Draft","Signed"]` |
| `eftf_is_required` | required when filing |
| `eftf_seq` | display order |

Removing a field is a soft delete (`eftf_is_active = 0`), so values that are
already stored stay readable in the history.

Once a field has stored values (*my call*): its kind and code are read-only,
`select` options can be added but an option still in use cannot be removed, and
a field used in the name pattern cannot be deactivated.

### `ef_user_types` — access
| Column | Notes |
|---|---|
| `efut_hmemd_sys_id` | user (`HmEmpData` PK) |
| `efut_eft_id` | FK → `ef_types` |

Unique on the pair. A grant is only ever created or dropped.

**Starting grants.** `EfilingUserTypeSeeder` (in `Modules/Efiling/database/seeders`,
run by hand once after deploy) copies `MGTAPPS.EFILL_USER_ACCESS` into this table.
It maps `eua_user_id` → `HmEmpData.hmemd_user_id` → `hmemd_sys_id` and
`eua_etd_sys_id` → `ef_types.eft_code`. Rows with no active employee or no
matching type are skipped and listed in the command output. It inserts only
missing pairs, so a re-run adds what is new and removes nothing.

Core's existing E-Filing Access page (`EfilingAccessManager`, which writes the
legacy `EFILL_USER_ACCESS`) is left as it is. The two access lists are separate
after the seeder runs.

### `ef_documents`
| Column | Notes |
|---|---|
| `efd_id` | PK |
| `efd_eft_id` | FK → `ef_types`; null while still in the inbox |
| `efd_year` | 4-digit year; null while still in the inbox |
| `efd_trans_no` | printed transaction number, as typed; null while still in the inbox |
| `efd_name` | built name without extension, e.g. `MCM-ADVIC-2026010002`; null while still in the inbox |
| `efd_status` | `inbox` \| `filed` (backed enum `DocumentStatusEnum`) |
| `efd_owner_sys_id` | uploader (`hmemd_sys_id`) |
| `efd_note` | `VARCHAR2(1000)`, free text |
| `efd_filed_at`, `efd_filed_by` | set when filed |

Unique index on (`efd_eft_id`, `efd_year`, `efd_name`), which is the document's
identity. The inbox rows hold nulls, which neither Oracle nor SQLite treats as
duplicates. `efd_trans_no` is kept separately and indexed for the search filter.

### `ef_document_files`
| Column | Notes |
|---|---|
| `efdf_id` | PK |
| `efdf_efd_id` | FK → `ef_documents` |
| `efdf_label` | optional free label, e.g. `draft`, `signed` |
| `efdf_original_name`, `efdf_mime`, `efdf_size` | from the upload |
| `efdf_disk` | `minio_private` for new files, `efiling_legacy` for imported ones |
| `efdf_path` | object key / path relative to the disk root, e.g. `MCM/2026/MCM-ADVIC-2026010002.pdf` |

Unique on (`efdf_disk`, `efdf_path`), which is what makes the legacy import
safe to re-run.

### `ef_document_values`
| Column | Notes |
|---|---|
| `efdv_efd_id` | FK → `ef_documents` |
| `efdv_eftf_id` | FK → `ef_type_fields` |
| `efdv_text` | for `text` and `select` |
| `efdv_number` | for `number` |
| `efdv_date` | for `date` |

Unique on (`efdv_efd_id`, `efdv_eftf_id`). Indexed on (`efdv_eftf_id`,
`efdv_text`), (`efdv_eftf_id`, `efdv_number`) and (`efdv_eftf_id`, `efdv_date`)
so each filter is an indexed lookup.

### History
`ef_documents`, `ef_document_files` and `ef_document_values` use
`LogsActivityWithDescription`. Changing Status from Draft to Signed, adding a
file and filing all show up on the document's history panel, read through
`Modules\Core\Models\MgtHris\Master\Logs\ActivityLog`. No custom history table.

## Flow

### 1. Inbox (`efiling.inbox`)
- Upload one or more files: PDF, JPG or PNG, up to 40 MB each (values held in
  `config/efiling.php`). Each file creates its own `inbox` document, owned by the
  uploader, stored at `efiling/inbox/{sys_id}/{uuid}.{ext}`.
- The list shows only the user's own inbox documents.
- Selecting one opens a preview next to a filing form: type (only the types the
  user has access to), year (defaults to the current year), transaction number
  (typed from the paper), the type's fields (re-rendered when the type changes),
  note, file label. Under the form, the name that will be built from the type's
  pattern is shown live, e.g. `MCM-ADVIC-2026010002.pdf`.
- **File** validates the form, builds the name, then:
  - **No existing document** with that type, year and name: the inbox document
    becomes `filed`, its values are saved, and the file moves to
    `efiling/{FOLDER}/{YEAR}/{NAME}.{ext}`, the same clean name the legacy app
    used.
  - **A document already exists:** the file attaches to it as one more file and
    the inbox row is deleted. Its stored name is `{NAME}-{n}.{ext}`, where `n`
    starts at 2 and is one more than the highest number already used on that
    document. Field values the user entered overwrite the existing ones, which
    is how a Draft becomes Signed when the signed scan is filed. The form says
    beforehand "this will be added to existing document X" (the lookup runs
    whenever the built name changes).
  - Order of work (*my call*): copy the object to its final path first, then
    run the database work in one transaction, then delete the inbox object. If
    the copy fails, nothing in the database has changed. If the transaction
    fails, the copy is deleted and the inbox object is still there. A failed
    delete of the inbox object only leaves an orphan, which is logged. In
    both the new and the attach case, the file is never lost.
- **Discard** deletes an inbox document and its stored file. Only allowed while
  the document is still in the inbox.

### 2. Search (`efiling.documents.index`)
- Lists filed documents, limited to the user's types.
- Filters: type, year, transaction number (starts-with), note (case-insensitive
  `LIKE`). Once one type is picked, its fields appear as filters too: exact
  match for `select`, `LIKE` for `text`, a from–to range for `number` and
  `date`. Each field filter is a `whereExists` against `ef_document_values`.
- Paginated, eager-loaded (type, file count, values).

### 3. Document page (`efiling.documents.show`)
- Header: type, year, number, field values (editable by users with
  `efiling-document-upload` and access to the type), note.
- File list with an inline preview of the selected file. Add another file here
  directly, with an optional label. It is stored with the same naming rule
  (`{NAME}-{n}.{ext}`).
- Users with `efiling-document-edit` can also change the transaction number or
  a field used in the name pattern (e.g. MCM's source type). That is a rename:
  the name is rebuilt from the document type's current pattern; if another
  document of the same type and year already has that name, the change is
  refused (no merging). Otherwise every file is copied to its new name, the
  rows are updated in one transaction, then the old objects are deleted. The
  same copy → commit → delete order as filing, so a failure leaves the
  document as it was. The rename shows in the history.
- History panel from the activity log.

### Preview and download
One route, `efiling.files.show` (`?download=1` for an attachment response),
handled by a thin controller that:
1. Loads the file and its document.
2. Allows the request if the user owns an inbox document, or has access to the
   filed document's type. Otherwise it returns 403.
3. For `minio_private`: redirects to `temporaryUrl(...)` with a 5-minute
   lifetime, the same pattern as `ShortLeaveAttachmentService`. For
   `efiling_legacy`: streams the file from the mounted share
   (`Storage::disk('efiling_legacy')->response()` / `->download()`), or returns
   404 with a clear message if the file is no longer there.

The page renders PDFs in an `<iframe>` (the browser's own viewer) and images in
an `<img>`. No pdf.js.

## Legacy import

The legacy share (`/mnt/efilling/Doc_Folder`, ~584k files, ~436 GB, almost all
PDF) stays where it is and remains the legacy app's archive. The new app reads it
through a read-only local disk `efiling_legacy` (root from
`EFILING_LEGACY_ROOT`, in `config/filesystems.php`). Every server that runs the
app or the import must mount the share at that path.

**What exists on the share** (surveyed 2026-10-30):

- 97 top-level folders. Each legacy type's folder is its code (`etd_sys_id`),
  including the ones with spaces (`LDN SALES`, `FP SALES`, `CARPET PRODUCT`).
- ~570k files at `{FOLDER}/{YEAR}/{file}`; ~13k sit directly under a folder
  with no year.
- Folders with no matching type: `GRN_MERGE` (~44k), `KWITANSI_BREAKDOWN`
  (~13k), `MERGE_FILE` (~2k), `ITEM_CODE`, `WRC`, `NULL`. `GRN_MERGE` and
  `MERGE_FILE` hold merged copies of documents that also exist in their own
  type folder.
- ~97% of names are `{prefix}{number}` or the type's compound form
  (`MCM-BPS-…`, `SJGRN-LGRN-…`, `PL-LDN-…`). ~15k are irregular:
  `…_MERGE`, `EIN-… BEKAERT`, `ADVBRCC-…, …`, `BUPOT-…-…-…HLN…`.

**Command:** `php artisan efiling:import-legacy {--type=} {--year=} {--dry-run}`

- Walks `{FOLDER}/{YEAR}/*` for each folder whose name matches an `ef_types.eft_folder`.
  Folders with no type are skipped and listed, not imported.
- Each file becomes one **filed** document with one file row
  (`efdf_disk = efiling_legacy`, `efdf_path` relative to the root):
  - `efd_eft_id` from the folder, `efd_year` from the year folder.
  - `efd_name` = the file name without its extension, as is. It is not parsed
    or rebuilt, so irregular names import fine and keep showing their original
    name.
  - `efd_trans_no` = the trailing digit run of the name when the name matches
    `^{type prefix}(.*-)?\d+$`, otherwise null. Search by number then works for
    the ~97% clean names; the rest are found by type, year or name.
  - MCM: when the name is `MCM-{X}-{digits}`, `X` is stored as the
    `source_type` field value if MCM has that field and `X` is one of its
    options. Other types get no field values.
  - `efd_owner_sys_id`, `efd_filed_by` = `SYSTEM`; `efd_filed_at` = the file's
    modified time.
  - Size and mime from the file; label `legacy`.
- Files at `{FOLDER}/{file}` (no year folder) are imported with `efd_year`
  taken from the file's modified time and are listed in the report.
  *(my call)*
- Re-runnable: a file already recorded (`efdf_disk` + `efdf_path`) is skipped.
  If a document with the same type, year and name already exists (for example
  one filed in the new app), the legacy file attaches to it instead of
  creating a second document.
- Bulk inserts in chunks of 1,000 per transaction, no activity log per row (one
  summary entry per run), its own log channel `efiling_legacy_import`, per
  `.docs-me/StandardCronJobCreation.md`. Progress per folder/year on the
  console. `--dry-run` counts and reports without writing.
- Ends with a report: imported, skipped (already there), no trans number,
  no year folder, folders with no type.

Legacy documents behave like any other filed document: searchable, viewable,
new files can be added (those go to MinIO), values can be set. Two exceptions:
legacy files are never moved, deleted or renamed by the app, and renaming a
document that has legacy files is refused.

*ponytail:* the import is run by hand. If the legacy app keeps receiving files
during the transition, re-run it (or schedule it nightly); it only adds what is
new.

## Masters

| Page | Route | Gate |
|---|---|---|
| Types + field editor | `efiling.masters.types` | `efiling-master-manage` |
| User access | `efiling.masters.access` | `efiling-master-manage` |

The access page supports Excel import and export (NIK column handled per
`FormatsNikForExport`), modelled on Core's existing E-Filing Access page.

## Permissions and menus

Names follow the repo's `{module}-{entity}-{action}` convention (*my call*).

- `efiling-document-view` gates search and the document page.
- `efiling-document-upload` gates the inbox, adding files and editing values
  that are not part of the name.
- `efiling-document-edit` gates renaming a filed document (changing its
  transaction number or a field used in its name pattern).
- `efiling-master-manage` gates the master pages.

The type-level check (`ef_user_types`) is applied on top of these, in the
repositories through a single `scopeAccessibleBy($sysId)` on `EfDocument`.

An idempotent migration seeds the permissions and the sidebar rows (module
`efiling` → items `efiling.inbox`, `efiling.search`, group `efiling.masters` →
`efiling.masters.types`, `efiling.masters.access`). Each row is gated on exactly
its route's permission, and the migration calls
`MenuTreeService::clearAllCache()`.

## Code layout

Standard module layout, following the project's layered pattern:
Livewire → Service → Repository → Model.

- Models: `EfType`, `EfTypeField`, `EfUserType`, `EfDocument`, `EfDocumentFile`, `EfDocumentValue`
- Repositories + interfaces: `EfType`, `EfUserType`, `EfDocument` (the document repository owns search and its value filters)
- Services: `EfTypeService`, `EfAccessService`, `EfDocumentService` (upload, file, attach, discard, update values), `EfFileStorage` (paths, move, temporary URL, the only class touching `Storage`)
- Livewire: `InboxPage`, `DocumentSearchPage`, `DocumentShowPage`, `TypeMasterPage`, `AccessMasterPage`
- Controller: `FileController@show`
- Enums: `FieldKindEnum`, `DocumentStatusEnum`
- DTO: `FileDocumentData`, which validates the fixed fields and builds per-type rules from `ef_type_fields`

## Error handling

- Validation errors stay on the form. A value that doesn't fit its field's kind
  (a non-numeric amount, a `select` value not in the options) is rejected
  before anything is saved.
- A duplicate number is not an error: it attaches to the existing document, and
  the user is told before saving.
- A storage failure during filing rolls the database back and leaves the file
  in the inbox. The user gets a `butter-error` toast and the error is logged.
- Orphaned inbox objects (an upload that never created a row) are not
  auto-cleaned in v1. *ponytail:* add a daily cleanup command for
  `efiling/inbox` objects without a row if they start piling up.

## Testing (Pest, SQLite, `Storage::fake('minio_private')`)

- Filing a new number moves the file to the type/year path and sets `filed`.
- Filing an existing type, year and name attaches the file as `{NAME}-2`,
  overwrites the given values and deletes the inbox row.
- MCM: `ADVIC` and `BPS` with the same number are two documents; the first file
  of each is stored under the clean name `MCM-{source}-{no}.pdf`.
- LDN and LDN SALES with the same number are two documents in two folders.
- The pattern rejects a field value with `/`. Changing a pattern leaves
  existing documents' names alone.
- Renaming (new source type or number) moves all files, is refused when the
  new name is taken, and leaves the document unchanged when a copy fails.
- A storage failure while moving leaves the document in `inbox` with its file.
- Search by `select`, a `number` range, a `date` range and note.
- The pattern builder (unit): placeholders replaced, `{trans_no}` required
  exactly once, unknown field codes rejected.
- Access: a user without the type sees no documents; `files.show` returns 403
  for someone else's inbox file and for a type without a grant.
- A required field missing, or a `select` value outside its options, is rejected.
- The menu/permission migration is idempotent (run it twice, same row count).
- Legacy import (on a faked local disk): clean, MCM and irregular names; a
  re-run adds nothing; a folder with no type is skipped; a matching
  new-app document gets the legacy file attached; `files.show` streams a legacy
  file and 404s a missing one.
- The grant seeder maps users and types, skips unmatched rows and is safe to
  re-run.

## Out of scope (v1)

- Copying legacy files into MinIO.
- Importing the non-type folders (`GRN_MERGE`, `KWITANSI_BREAKDOWN`,
  `MERGE_FILE`, `ITEM_CODE`, `WRC`).
- Parsing extra data (supplier, amounts) out of irregular legacy names.
- Full-text search inside PDFs, OCR.
- Sharing one document with a single user.
- Free-form tags (covered by `select` fields and the note).
- File version history beyond keeping files side by side.
- Automatic cleanup of orphaned inbox uploads.
