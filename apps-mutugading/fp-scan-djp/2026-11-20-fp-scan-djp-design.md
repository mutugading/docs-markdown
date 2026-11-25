# DJP Faktur Pajak scan on the existing scanner page — design (approved 2026-11-20)

## Why
Some suppliers still issue old DJP e-Faktur invoices. Legacy `Finance_File/2V1_00003` handles them; move it into
the Finance module so it can be retired. Line items and legacy tables `FIN_SCAN_FP_HDR/DTL` must be kept (option B).

## Goals / non-goals
- Goal: scanning a DJP QR link on `finance/fp-scan` saves the invoice exactly as `PKG_SCAN_FAKTUR_PAJAK.fInsDt` did.
- Non-goal: scan report changes (stays Coretax-only), Bukti Potong mail, XML file on disk, TMP tables, the package.
- Anti-goal: server fetching arbitrary URLs; silently wrong tax rows.

## Approaches considered
- A. Store DJP into `FIN_SCAN_FP_CORETAX` — one report, but loses line items. Rejected.
- B. Write `FIN_SCAN_FP_HDR/DTL` + `MGT_FP_SCAN` from PHP, no TMP table, no package. **Picked by user.**

## Design
- Same input box; a DJP link (host from config allowlist, https) goes to the DJP path, else Coretax path.
- Fetch with Laravel Http (timeout), parse XML (`resolveFakturPajak` root, `detailTransaksi` repeated).
- HDR: id `YYYYMMDD||fGet_Sys_Id_Val('FSFH_SYS_ID', user, 'fm0000')`; `FSFH_FGPENGGANTI` default 0;
  `FSFH_TANGGALFAKTUR` from `d/m/Y`; amounts default 0; `FSFH_NOMORFAKTUR_OLD` = nomorFaktur;
  `FSFH_NOMORFAKTUR_F` = package's 17-digit conversion of `kd fg . nnn-yy.nnnnnnnn`
  (`kd` + `0.`→`00` / `1.`→`01` + `9` + yy + 8-digit tail);
  `FSFH_STS_DATA` = 1 if buyer NPWP in `MST_PARAM_DATA` key `NPWP_FP_MGT`, else 2. `FSFH_FSFTH_SYS_ID` null.
- DTL: one row per `detailTransaksi`, `FSFD_SEQ_NO` 1..n, id via trigger (`'123'`).
- MGT_FP_SCAN only if (buyer is MGT or `FPS_FP_F` in `mgt_tax_populate.MTPP_FP_NO`) and `FPS_FP_F` not already there;
  FPS_FP = nomorFaktur, FPS_FP_NO = last 8, FPS_FP_F = `fGenNomorSeriFP`, masa/tahun from date, url = scanned link.
  HDR then `FSFH_SYNCH_MGTDAT` = `I` / `E`. Failure in mirror → delete HDR/DTL (saga, like Coretax).
- Duplicate: existing HDR with same kd + fg + nomorFaktur → "already scanned" with who/when.
- Not-for-MGT invoices are saved with STS_DATA 2 (as legacy); Coretax still rejects them. (Builder call.)
- Info card shows header, line items, For Mgt / Not For Mgt, Exist / Not Exist in prepopulate.
- No `reIns_MgtDat` catch-up sweep. Add if not-for-MGT invoices later appear in prepopulate and need mirroring.

## Check
One Pest test with `Http::fake`: XML parse, 17-digit conversion, refused host, duplicate.
