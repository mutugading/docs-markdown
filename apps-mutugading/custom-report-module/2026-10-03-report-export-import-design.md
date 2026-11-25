# Report definitions: export and import

## Purpose

Move report definitions, with their parameters, between environments. The usual case: a report is built
and tested on dev, then brought to production. One file can carry several reports.

## Out of scope

- Access grants (`report.view.{id}`), run history, creator and timestamps. None of these mean anything in
  another environment.
- Creating groups on import.
- Backup or "copy a report within one environment".

## File format

One JSON file:

```json
{
  "version": 1,
  "reports": [
    {
      "group": { "name": "Finance", "icon": "fas fa-coins" },
      "name": "Outstanding Invoices",
      "description": "...",
      "query": "SELECT ...",
      "max_rows": 1000,
      "timeout_sec": 60,
      "status": "active",
      "shared_history": true,
      "parameters": [
        {
          "key": "status",
          "label": "Status",
          "input_type": "dropdown_static",
          "is_required": true,
          "default_value": null,
          "source_query": null,
          "static_options": [{ "value": "1", "text": "Open" }]
        }
      ]
    }
  ]
}
```

- Parameters are listed in display order. Import renumbers them 1, 2, 3.
- The group is carried by name (and icon, for information only). Ids are never exported.
- An unknown `version` is rejected with a clear message.
- Queries and options are plain JSON strings: quotes, backslashes, brackets and line breaks survive the round
  trip unchanged. Export encodes with `JSON_THROW_ON_ERROR` (invalid UTF-8 fails loudly instead of writing a
  broken file) and `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT` so the file stays
  readable. Import strips a leading byte-order mark before parsing.

## Export

- The Definitions list gets a checkbox per row and an "Export selected" button. It downloads one `.json` file.
- Super Admin only, like the rest of the page. With nothing ticked the button does nothing.

## Import

1. An "Import" button on the Definitions page opens a modal with a file upload.
2. The file is read and checked, then a **preview** is shown. Nothing is written yet. Each report is marked:
   - **New**: no existing report has this group name and report name.
   - **Update**: exactly one existing report has this group name and report name.
   - **Problem**: with the reason shown. See the list below.
3. The Import button in the preview is disabled while any report is a Problem. The file is all-or-nothing, so
   fixing the file (or the environment) and uploading again is the way forward.
4. On confirm, every report is written in **one transaction**.

### Matching

A report matches on **group name + report name**, compared case-insensitively. Names are not unique in the
database, so if two existing reports match, that report is a Problem ("matches more than one existing report").

### What a write does

- **New:** created with status **Draft**, whatever the file says. Nothing goes live until someone reviews it.
- **Update:** the report's fields are replaced from the file, **except status**, which keeps its current value.
  Replacing a live report's status silently would be surprising. Parameters are matched by key: keys in the file
  are updated or added, keys missing from the file are deleted.
- Access grants and run history are never touched. A new report has no access until it is granted on the Access page.

### Problems (each blocks the import)

- Group name not found in this environment (create the group first).
- Two existing reports match.
- The query is not a SELECT, or contains a forbidden keyword.
- A parameter fails the same rules as the edit form: key format, input type, duplicate key within the report,
  static options not a valid list.
- A `dropdown_query` / `multi_select_query` source query is unsafe, or fails its test run against this environment.
- The file is not valid JSON, has an unknown `version`, or is missing required fields.

### Reuse

Writing goes through `ReportDefinitionService::saveWithParameters`, so import applies exactly the checks the
form applies, including the test run of source queries. Planning (the preview) validates without writing.
Parameter rules come from the same DTO rules the form's modal uses.

## Components

- `ReportTransferService` (new, `Modules/Report/app/Services`): `export(array $ids): array`,
  `plan(array $payload): ImportPlan`, `apply(ImportPlan $plan): int`.
- `ReportDefinitionManager` + its view: checkboxes, "Export selected", Import modal with preview and confirm.
  Uses `WithFileUploads`.
- Download is a streamed JSON response from the component.

## Error handling

- A malformed or unreadable file shows one error in the modal; no preview.
- A failure during apply rolls everything back and names the report that failed, as the form does for a parameter.

## Tests (Pest, `--parallel`)

- Export then import into an empty state recreates the reports and parameters in order (round trip).
- Export contains no ids, creator, access or runs.
- A query full of awkward characters (`'`, `"`, `\`, `( ) [ ] { }`, `%`, `:name` binds, `--` comments, CRLF line
  breaks, accented letters) comes back from export and import byte for byte.
- A file that starts with a byte-order mark imports normally.
- Matching: New, Update, case-insensitive match, two matches is a Problem.
- Update keeps the existing status and removes a parameter missing from the file; New is created as Draft.
- Unknown group is a Problem and blocks the whole file.
- A forbidden keyword, a bad parameter and a failing source query are each a Problem; nothing is written.
- Malformed JSON and unknown version are rejected.
- Access grants and run history survive an update.
- The page controls are Super Admin only.
