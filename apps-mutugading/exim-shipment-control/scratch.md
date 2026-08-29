# Scratch notes — Shipment Control

Raw notes, not yet folded into PRD/spec/schema. Recorded 2026-08-29.

## Import: starts with Shipment Planning

New step in front of the import flow:

1. **Import Shipment Planning** — the user fills in the header for the import provision:
   customer, ETD, and the rest of the current provision header fields.
2. Under the header, the user adds **vendor details**.
3. Under that, **items are listed per PO**, and those items are what the
   **estimated PIB** is calculated from.
4. The user **uploads the PDF document** for the planning, and it is stored with
   the record.
5. **Provision then pulls this planning data** instead of the user re-keying the
   header, and from that point the flow continues exactly as it does today.

Open questions:
- Does one planning record map to exactly one provision, or can a provision pull
  from several plannings?
- Is planning editable after a provision has pulled it?
- Does planning need its own status/approval, or is it free-form until pulled?

## Export: starts with Shipping Instruction

New step in front of the export flow:

1. **Shipping Instruction** holds:
   - contract no.
   - freight vendor + agreed price
   - EMKL vendor + agreed price
   - customer info
   - other instructions (free text)
   - the **uploaded PDF document**, stored with the record
2. If the deal is agreed, the instruction moves to **Final**.
3. If not, it is **cancelled** — and a cancellation fee may apply.
4. Along the way the instruction can pick up **extra costs**. Each extra cost
   needs **its own separate approval** (not rolled into the main approval).
5. Once everything is settled, the **whole instruction is approved**, and the
   next step pulls from it.

Note: the step that pulls from the approved instruction (PRD) is **not in this
app yet** — it still lives in the legacy application.

Open questions:
- Can an extra cost be added after the instruction is already Final/approved?
  If so, does that reopen the instruction or stay a side record?
- Is the cancellation fee a fixed amount, a percentage, or free entry?
- Who approves extra costs — the same approver chain as the instruction, or a
  different one?
- Freight and EMKL: always exactly one vendor each, or can there be several?

## Document upload (both flows)

Both starting screens — Import Shipment Planning and Export Shipping Instruction
— take a **PDF upload** so the source document is kept with the record.

Open questions:
- One file per record, or several?
- Is the upload required before the record can be submitted/finalised?
- Where do the files live — MinIO (as with other uploads here) or local disk?
- Can the file be replaced after approval, and should old versions be kept?
