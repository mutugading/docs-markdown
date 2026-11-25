# E-Filing legacy archive on MinIO — short design

**Why:** the legacy share (`Doc_Folder`, mounted at `/mnt/efilling`) is being copied once to a new
MinIO. The app should read legacy files from there, and files that haven't been indexed yet should
still be picked up.

**Goals:** imported rows keep working without a data update; Dev-Ops can re-run
`efiling:import-legacy` against MinIO to index anything new.
**Non-goals:** a scheduled sync; making legacy files editable (renaming or deleting them).
**Anti-goals:** a migration that rewrites `ef_document_files`; any write to the legacy bucket.

## Approaches
- **A (picked):** repoint the `efiling_legacy` disk at MinIO. Rows store `efdf_disk` plus a path
  relative to the share root, so nothing in the database changes. Switching back is just env.
- B (rejected): move the rows to `minio_private` under `efiling/…`. It needs a data update over
  every row, ties the archive to the upload bucket, removes the read-only protection, and can't be
  undone through env.

## Changes
1. `config/filesystems.php` → `efiling_legacy` chooses its driver from a toggle,
   `EFILING_LEGACY_STORAGE=share|minio` (default `share`, so nothing changes until it's flipped):
   - `share`: today's local disk on `EFILING_LEGACY_ROOT`.
   - `minio`: an `s3` disk on the same MinIO, reusing `MINIO_*` credentials and endpoint;
     bucket `EFILING_LEGACY_BUCKET` (default `MINIO_PRIVATE_BUCKET`), folder inside it
     `EFILING_LEGACY_PREFIX` (default `efiling-legacy`, passed as the disk `root`).
   Add the three new vars to `.env.example`.
2. `EfFileStorage::legacyResponse()` → keep the friendly 404, then: if the disk
   `providesTemporaryUrls()`, redirect to a 5-minute link (same as new files); otherwise stream
   it as it does now. Return type widens to `Response`; `FileController` is unchanged.
3. Comments that say "mounted share" / "file server" get updated to "legacy archive (MinIO)".
4. One test: in `minio` mode a legacy row gets a redirect; in `share` mode it streams; a missing file returns 404.

## Rules for the copy (Dev-Ops)
- `{bucket}/{EFILING_LEGACY_PREFIX}/` = contents of `Doc_Folder` (`{FOLDER}/{YEAR}/{NAME}.ext`), so the paths already in the database match.
- Keep modified times (`mc mirror --preserve`). Files with no year folder take their year from that time.
- After the switch, run `efiling:import-legacy --dry-run`, then a real run. Rows that are already indexed get skipped.

## Left to the builder
Exact env names and the test layout.
