# 2. User & Role

## Daftar Role

| Role | Bagian | Deskripsi |
|---|---|---|
| **PPC** | PPIC / Production Planning | Owner utama sistem. Buat demand, plan, WO, carry-forward |
| **PC** | Process Control | Approve parameter teknis WO (speed, nozzle, oil, dll) |
| **PM** | Production Manager | Approve WO overall, cancel, override over-production |
| **Marketing** | Marketing | Approve MTS demand. Receive notifikasi Balance for Sale |
| **Management** | Direksi / Manager senior | View dashboard, manage commodity watchlist |
| **Operator** | Tim produksi | Input shift log per mesin per shift: produksi, waste, downtime/idle, breaks, activity, parameter actual |

---

## Scope per Role

### PPC
- Pull SO dari Orion ke demand (LOV)
- Input demand manual (MTS, Sample, Contract belum di Orion)
- Process carry-forward awal bulan (5 aksi)
- Create / edit Plan Item
- Generate, submit, cancel (DRAFT) Work Order
- Set RM allocation per WO
- Input actual qty WO (fallback jika ETL gagal)
- Input actual changeover
- Resolve plan change flag

### PC (Process Control)
- Review dan approve / reject parameter teknis WO
- Suggest adjustment parameter

### PM (Production Manager)
- Approve / reject WO overall
- Cancel WO status APPROVED+
- Approve override over-production block
- Emergency reorder
- Buka kembali qty FINAL setelah 24 jam

### Marketing
- Approve / reject MTS demand
- Receive notifikasi Balance for Sale komoditi

### Management
- View Balance for Sale dashboard
- Manage commodity watchlist
- Receive periodic summary Balance for Sale

### Operator
- Input parameter actual (`WO_EXECUTION`)
- Input shift log per mesin per shift (lihat halaman 13):
  qty produksi (prefill suggest ETL), posisi & waktu running,
  waste per kategori, downtime/idle + reason, breaks, doff count, activity note
- Report issue WO

---

## Approval Matrix

| Action | PPC | PC | PM | Mgmt | Marketing | Operator |
|---|---|---|---|---|---|---|
| Pull SO dari Orion ke demand | ✓ | | | | | |
| Input demand manual | ✓ | | | | | |
| Input MTS demand | ✓ | | | | | |
| Approve MTS demand | | | | | ✓ | |
| Process carry-forward awal bulan | ✓ | | | | | |
| Create / edit plan item | ✓ | | | | | |
| Generate WO | ✓ | | | | | |
| Submit WO | ✓ | | | | | |
| Approve parameter teknis WO | | ✓ auto 4j | | | | |
| Approve WO overall | | | ✓ auto 4j | | | |
| Reject WO | | ✓ | ✓ | | | |
| Cancel WO (DRAFT) | ✓ | | | | | |
| Cancel WO (APPROVED+) | | | ✓ | | | |
| Override over-production block | | | ✓ | | | |
| Emergency reorder | | | ✓ | | | |
| Manage commodity watchlist | | | | ✓ | | |
| Buka kembali qty FINAL (>24 jam) | | | ✓ | | | |
| Input parameter actual execution | | | | | | ✓ |
| Input actual produksi per shift | ✓ | | | | | ✓ |
| Input shift log (waste/downtime/activity) | ✓ | | | | | ✓ |
| Resolve plan change flag | ✓ | | ✓ | | | |

---

## Auto-Approval

| Approval | Timer | Trigger |
|---|---|---|
| PC — parameter teknis | **4 jam** setelah WO disubmit | Notifikasi email + in-app. Auto-approve jika tidak ada response |
| PM — WO overall | **4 jam** setelah PC approve | Notifikasi email + in-app. Auto-approve jika tidak ada response |

Reject selalu manual. Jika di-reject, WO kembali ke PPC dengan catatan.

---

## Notifikasi

| Event | Notified to |
|---|---|
| WO submitted | PC + PM |
| PC approved | PM |
| WO approved (keduanya) | PPC |
| WO rejected | PPC |
| MTS demand diajukan | Marketing |
| MTS approved/rejected | PPC |
| RM fence warning (mendekati limit) | PPC |
| RM fence blocked (melebihi limit) | PPC + PM |
| SO Orion belum di-pull | PPC |
| Balance for Sale komoditi berubah signifikan | Management + Marketing |
| Demand deadline terlambat dari kontrak | Management |
| Shift log belum lengkap (H+1) | Operator + PPC |

**Channel MVP:** Email + in-app notification
**Channel Phase 2:** WhatsApp (tambahan)
