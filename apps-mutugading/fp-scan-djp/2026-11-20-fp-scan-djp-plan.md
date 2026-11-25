# DJP Faktur Pajak Scan Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** The existing `finance/fp-scan` page also accepts old DJP e-Faktur QR links and saves them to `FIN_SCAN_FP_HDR/DTL` + `MGT_FP_SCAN` exactly as `PKG_SCAN_FAKTUR_PAJAK` did, with no TMP tables and no package call.

**Architecture:** A new `DjpFakturPajakScanService` (fetch → parse XML → dedup → save header+lines → mirror) behind one new repository. The existing Livewire `FakturPajakScanner::processScan()` sends DJP links to it, and everything else to the current Coretax service. Results come back as the existing `ScanResult`.

**Tech Stack:** Laravel 12 Http client, SimpleXML, Livewire 3, Pest 4 (SQLite stand-in tables).

**Spec:** `.docs-me/superpowers/fp-scan-djp/2026-11-20-fp-scan-djp-design.md`

## Global Constraints

- Allowed DJP hosts come from `config('finance.fp_scanner.djp_hosts')`, default `['svc.efaktur.pajak.go.id']`. Scheme `http` or `https`, exact host match, **no redirects followed**, timeout `config('finance.fp_scanner.djp_timeout')` = 10 s.
- `FSFH_SYS_ID` = `TO_CHAR(SYSDATE,'YYYYMMDD') || MGTAPPS.fGet_Sys_Id_Val('FSFH_SYS_ID', user, 'fm0000')` (same SQL shape as `DraftEmployeeProvisioner::reserveFamilyId`). `FSFD_SYS_ID` = `'123'` (trigger replaces it).
- Mutu Gading NPWP = a row in `mgtapps.MST_PARAM_DATA` with `MPD_MPDK_KEY = 'NPWP_FP_MGT'` and `MPD_VALUE = npwpLawanTransaksi`.
- Prepopulate = `mgtdat.MGT_TAX_POPULATE.MTPP_FP_NO = FPS_FP_F` (dotted format).
- No `DB::` facade outside repositories; no changes to the scan report, the Coretax service or the package.
- User-facing messages are in Bahasa Indonesia, same tone as `ScanOutcomeEnum::label()`.

## Review Focus

1. A DJP link whose host is a look-alike (`svc.efaktur.pajak.go.id.evil.com`, `user@host`, IP) must be refused without fetching. Test in Task 1.
2. DJP site down, slow, or returning HTML/an empty body must show "gagal mengambil data DJP", save nothing, and not throw out of the page. Test in Task 2.
3. Invoice with zero `detailTransaksi` must still save the header with no lines. Test in Task 2.
4. Names with apostrophes (`PT. KHOLID'S`) must save as-is (bindings, not string SQL). Test in Task 2.
5. Mirror failure after the header was saved must leave no header/lines behind. Test in Task 2.

---

### Task 1: Read a DJP link (allowlist, fetch, parse, number formats)

**Files:**
- Modify: `Modules/Finance/config/config.php` (add `djp_hosts`, `djp_timeout` under `fp_scanner`)
- Create: `Modules/Finance/app/Services/Master/FpScan/DjpFakturPajakScanService.php`
- Test: `tests/Feature/Finance/FpScan/DjpFakturPajakScanTest.php`

**Interfaces:**
- Produces:
  - `isDjpLink(string $input): bool`: true only for an allowed host with http/https.
  - `fetch(string $url): ?array`: null on HTTP error, timeout, non-XML or missing `kdJenisTransaksi`; else `parse()` of the body.
  - `parse(string $xml): ?array`: `['header' => [kdjenistransaksi, fgpengganti (default '0'), nomorfaktur, tanggalfaktur (Y-m-d from d/m/Y), npwppenjual, namapenjual, alamatpenjual, npwplawantransaksi, namalawantransaksi, alamatlawantransaksi, jumlahdpp, jumlahppn, jumlahppnbm (default '0'), statusapproval, statusfaktur, referensi], 'lines' => list<[nama, hargasatuan, jumlahbarang, hargatotal, diskon, dpp, ppn, tarifppnbm, ppnbm]>]` (numbers default '0').
  - `static seriFormat(string $kd, string $fg, string $nomor): string`: `fGenNomorSeriFP`: `kd.fg.'.'.substr(n,0,3).'-'.substr(n,3,2).'.'.substr(n,5)`.
  - `static seventeenDigit(string $kd, string $fg, string $nomor): string`: the package's `FSFH_NOMORFAKTUR_F` conversion. Port this exactly:
    ```php
    $f = self::seriFormat($kd, $fg, $nomor);                    // e.g. 010.010-24.12345678
    $fg2 = ['0.' => '00', '1.' => '01'][substr($f, 2, 2)] ?? ''; // Oracle decode, null otherwise
    return substr($f, 0, 2).$fg2.'9'.substr($f, 5, 2).str_replace('.', '', substr($f, 8, 11));
    ```

- [ ] **Step 1: Write failing tests** with this fixture XML (put it in the test file as a heredoc `DJP_XML`): kd `01`, fg `0`, nomor `0102412345678`, tanggal `15/10/2026`, npwpLawanTransaksi `017522863528000`, namaPenjual `PT. KHOLID'S`, two `detailTransaksi`.
  - `seriFormat('01','0','0102412345678')` → `'010.010-24.12345678'`
  - `seventeenDigit('01','0','0102412345678')` → `'01009102412345678'`; `seventeenDigit('01','1', …)` starts `'0101'`
  - `parse(DJP_XML)['header']['tanggalfaktur']` → `'2026-10-15'`; `count(lines)` → 2; `namapenjual` → `"PT. KHOLID'S"`
  - `isDjpLink` true for `http://svc.efaktur.pajak.go.id/validasi/faktur/abc`; false for `http://svc.efaktur.pajak.go.id.evil.com/x`, `http://x@evil.com/`, `http://10.0.0.1/`, `ftp://svc.efaktur.pajak.go.id/`, and a Coretax `#` string
  - `fetch` with `Http::fake([... => Http::response(DJP_XML)])` returns the parsed array; with `Http::response('<html>', 200)` returns null; with `Http::response('', 500)` returns null; `Http::assertNothingSent()` after `isDjpLink` false

- [ ] **Step 2: Run** `php artisan test --parallel --processes=4 tests/Feature/Finance/FpScan/DjpFakturPajakScanTest.php`. Expect FAIL (class missing).

- [ ] **Step 3: Implement** the five methods. Use `parse_url` for host/scheme and reject when `user` is set. `Http::timeout(...)->withoutRedirecting()->get()`. Parse with `simplexml_load_string($body, options: LIBXML_NONET)` inside `libxml_use_internal_errors(true)`.

- [ ] **Step 4: Run the tests.** Expect PASS.

- [ ] **Step 5: Commit** `feat(finance): read DJP faktur pajak links for the scanner`

### Task 2: Save a DJP invoice (headers, lines, mirror, duplicate)

**Files:**
- Create: `Modules/Finance/app/Models/MgtApps/Master/FpScan/FinScanFpHdr.php`, `FinScanFpDtl.php` (`oracle_mgtapps`, tables `fin_scan_fp_hdr` / `fin_scan_fp_dtl`, string keys, `$timestamps = false`, `hasMany` details)
- Create: `Modules/Finance/app/Interfaces/Master/FpScan/FinScanFpDjpRepositoryInterface.php`, `Modules/Finance/app/Repositories/Master/FpScan/EloquentFinScanFpDjpRepository.php`
- Modify: `Modules/Finance/app/Providers/RepositoryServiceProvider.php` (bind)
- Modify: `DjpFakturPajakScanService.php` (add `scan`)
- Modify: `tests/Helpers/Finance/` (new `FpScanDjpHelpers.php` creating SQLite stand-ins: `fin_scan_fp_hdr`, `fin_scan_fp_dtl`, `mst_param_data` on `oracle_mgtapps`; `mgt_fp_scan`, `mgt_tax_populate` on `oracle_mgtdat`)
- Test: same test file

**Interfaces:**
- Repository:
  - `findByNumber(string $kd, string $fg, string $nomor): ?FinScanFpHdr`
  - `nextHeaderId(string $userId): string`: Oracle SQL from Global Constraints; SQLite fallback `Ymd` + 4-digit count+1 (`ponytail:` test-only path)
  - `createWithLines(array $header, array $lines): FinScanFpHdr`: one `oracle_mgtapps` transaction
  - `isMgtNpwp(string $npwp): bool`, `inPrepopulate(string $seri): bool`, `mirrorExists(string $seri): bool`
  - `nextMirrorId(): int`: `mgtdat.mgt_fp_scan_seq.NEXTVAL`, SQLite fallback max+1
  - `markSynch(string $sysId, string $flag): void`: sets `FSFH_SYNCH_MGTDAT` + `_DT`
  - `deleteWithLines(string $sysId): void`
- Mirror row goes through the existing `MgtFpScanRepositoryInterface::create(array)`.
- Service: `scan(string $url, array $context): ScanResult` (context same as Coretax: `user_id`, `full_name`, `user_area`). Outcomes reuse `ScanOutcomeEnum`:
  - fetch null → `InvalidFormat` with message `'Gagal mengambil data dari DJP. Coba scan ulang.'`
  - existing header → `Duplicate`, meta `scanned_by` (`FSFH_CREATED_BY`), `scanned_at`, `nomorfaktur` (seri)
  - saved → `Saved`, meta `djp` = `['header' => …, 'lines' => …, 'for_mgt' => bool, 'prepopulate' => bool, 'seri' => …]`

Header values: `FSFH_CREATED_BY` = user_id, `FSFH_CREATED_TIMESTAMP` = now, `FSFH_NOMORFAKTUR_F` = `seventeenDigit`, `FSFH_NOMORFAKTUR_OLD` = nomor, `FSFH_STS_DATA` = `'1'` if `isMgtNpwp` else `'2'`, `_X` columns and `FSFH_FSFTH_SYS_ID` null. Lines: `FSFD_SYS_ID` `'123'`, `FSFD_SEQ_NO` 1..n.

Mirror (only if `for_mgt || prepopulate`): if `mirrorExists(seri)` → `markSynch('E')`; else create `fps_sys_id` nextMirrorId, `fps_fp` nomor, `fps_fp_no` last 8 of nomor, `fps_fp_f` seri, `fps_cr_uid` user_id, `fps_cr_dt` now, `fps_masa` `m`, `fps_tahun` `Y`, `fps_user_area`, `fps_fp_url` url, `fps_fsfh_sys_id`, then `markSynch('I')`. Any throw in mirror → `deleteWithLines` then rethrow.

- [ ] **Step 1: Write failing tests** (`Http::fake` with `DJP_XML`, helper tables, `mst_param_data` row `NPWP_FP_MGT` / `017522863528000`):
  - saves: 1 header with `fsfh_nomorfaktur_f = '01009102412345678'`, `fsfh_sts_data = '1'`, 2 lines seq 1,2; 1 `mgt_fp_scan` row with `fps_fp_f = '010.010-24.12345678'`, `fps_fp_url` = the link; header `fsfh_synch_mgtdat = 'I'`; outcome `Saved`
  - scanning again → `Duplicate`, still 1 header
  - buyer not MGT, not in prepopulate → header saved `sts_data '2'`, 0 mirror rows, synch null
  - buyer not MGT but `mgt_tax_populate.mtpp_fp_no = seri` → mirror row created
  - mirror row already present → header `synch 'E'`, still 1 mirror row
  - XML with no `detailTransaksi` → header saved, 0 lines
  - `Http::response('', 500)` → `InvalidFormat`, 0 headers
  - mirror failure (drop `mgt_fp_scan` table before scan) → throws, 0 headers, 0 lines

- [ ] **Step 2: Run** the test file. Expect FAIL.

- [ ] **Step 3: Implement** models, repository, binding, `scan()`.

- [ ] **Step 4: Run** the test file, then `ACTIVITY_LOGGER_DB_CONNECTION= php artisan test --parallel --processes=4 tests/Feature/Finance`. Expect PASS.

- [ ] **Step 5: Commit** `feat(finance): save scanned DJP faktur pajak to the legacy tables`

### Task 3: Wire into the scanner page

**Files:**
- Modify: `Modules/Finance/app/Livewire/Master/FpScan/FakturPajakScanner.php`: inject `DjpFakturPajakScanService` in `boot()`; in `processScan()`, if `isDjpLink(trim($scanInput))` call `djp->scan()` instead of `service->scan()`. `emitResult` is unchanged.
- Modify: `Modules/Finance/resources/views/livewire/master/fp-scan/faktur-pajak-scanner.blade.php`: when `$lastMeta['djp']` is set, show in the Informasi card the seri, seller, buyer, DPP/PPN/PPnBM, status approval/faktur, referensi, badges "For Mgt"/"Not For Mgt" and "Ada di Prepopulate"/"Tidak ada di Prepopulate", and a `x-ui::table` of lines (No, Nama, Harga Satuan, Jumlah, Harga Total, Diskon, DPP, PPN, Tarif PPnBM, PPnBM). Use `wire:key` on rows and `number_format(…, 2, ',', '.')`.
- Test: same test file

- [ ] **Step 1: Write failing test:** `Livewire::actingAs(ciProjectUser('D01', ['finance-faktur_pajak-scan']))->test(FakturPajakScanner::class)->set('scanInput', $link)` → `assertDispatched('butter-success')`, `assertSee('010.010-24.12345678')`, `assertSee('For Mgt')`, 1 header row.
- [ ] **Step 2: Run.** Expect FAIL.
- [ ] **Step 3: Implement** the component branch and the Blade block.
- [ ] **Step 4: Run** the test file, then `vendor/bin/pint --dirty`. Expect PASS / clean.
- [ ] **Step 5: Commit** `feat(finance): accept DJP links on the faktur pajak scanner page`
