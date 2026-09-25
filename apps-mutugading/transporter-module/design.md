# design.md — Transporter v1: rancangan teknis

Yang **tidak** ada di sini: DDL kolom-per-kolom, rumus, dan pemetaan migrasi —
semuanya di `spec.md`. Berkas ini tentang bentuk kode: siapa memanggil siapa,
apa yang jadi tanggung jawab lapisan mana, dan di mana batasnya.

---

## 1. Struktur modul

```
Modules/Transporter/
├── app/
│   ├── Console/Commands/      PullDeliveryNotesCommand, ScanDeliveryDocumentsCommand
│   ├── Data/
│   │   ├── Master/            CarrierData, RateCardData, RateLineData, ChargeTypeData,
│   │   │                      ServiceCategoryData, PostingAccountData, DocumentTypeData
│   │   ├── Transaction/       OrderData, OrderDnData, OrderCostData, GrnPullCandidateData
│   │   ├── Expense/           AdditionalExpenseData, AdditionalExpenseLineData
│   │   ├── Provision/         ProvisionData, JournalPreviewData, JournalPreviewLineData
│   │   └── Billing/           BillData, BillLineData, TaxProfileData
│   ├── Enums/
│   │   ├── Master/            CarrierTypeEnum, ServiceTypeEnum, RateTypeEnum, DocumentStatusEnum
│   │   └── Transaction/       TransactionTypeEnum, TransactionStatusEnum,
│   │                          AdditionalExpenseStatusEnum, ProvisionStatusEnum,
│   │                          BillLineTypeEnum, DifferenceTypeEnum, PostingTypeEnum,
│   │                          PostingStatusEnum, OrderSourceEnum
│   ├── Exports/               5 export class (§F-08)
│   ├── Interfaces/            satu per repository, cermin folder Repositories
│   ├── Jobs/
│   │   ├── Transaction/       GenerateTransportOrders, GenerateChipOrders
│   │   ├── Provision/         PostProvisionJournal
│   │   ├── Billing/           PostBillJournal
│   │   ├── Document/          ScanDeliveryDocuments
│   │   └── Report/            Export* (5)
│   ├── Livewire/
│   │   ├── Master/            CarrierPage, RateCardPage, RateLinePanel, ChipRateMatrixPage,
│   │   │                      ChargeTypePage, ServiceCategoryPage, PostingAccountPage,
│   │   │                      DocumentTypePage, GrnChipMonitorPage
│   │   ├── Transaction/       OrderListPage, OrderFormPage, OrderDetailPage,
│   │   │                      ApprovalInboxPage, DeliveryNoteStagePage, GrnChipReadyPage
│   │   ├── Expense/           AdditionalExpensePage, AdditionalExpenseFormPage,
│   │   │                      ExpenseWaitingApprovalPage, ExpenseUnbilledPage
│   │   ├── Provision/         ProvisionPage, JournalPreviewModal
│   │   ├── Billing/           BillListPage, BillFormPage, BillMatchingPanel, DocumentHoldPage
│   │   └── Report/            5 halaman parameter report
│   ├── Models/
│   │   ├── MgtHris/           22 model (owned, read-write)
│   │   └── MgtDat/            ± 10 model read-only ke Orion
│   ├── Providers/             TransporterServiceProvider, RepositoryServiceProvider,
│   │                          RouteServiceProvider
│   ├── Repositories/          Eloquent*, cermin Interfaces
│   ├── Services/
│   │   ├── Master/            CarrierService, RateCardService, ChargeTypeService, …
│   │   ├── Transaction/       OrderService, OrderNumberService, OrderApprovalService,
│   │   │                      DeliveryNoteStageService, ChipPullService
│   │   ├── Pricing/           YarnRateCalculator, ChipRateCalculator, RateCardResolver
│   │   ├── Expense/           AdditionalExpenseService
│   │   ├── Provision/         ProvisionService, ProvisionJournalBuilder
│   │   ├── Billing/           BillService, BillMatchingService, TaxProfileService,
│   │   │                      BillJournalBuilder
│   │   ├── Document/          DocumentScanService, DocumentGateService
│   │   └── Erp/               TransporterPostingService  (tipis; membungkus Core)
│   └── Support/               DueDateHelper, GrossUpHelper
├── config/config.php          → config('transporter.*')
├── database/migrations/       23 tabel (ke-23 `transp_carrier_alias`, D-20) + index + seeder sequence
├── database/seeders/          TransporterPermissionSeeder, TransporterMenuSeeder,
│                              TransporterMasterSeeder (charge type, service category,
│                              posting account, document type)
├── resources/views/livewire/  cermin Livewire/
├── routes/web.php, breadcrumbs.php
├── module.json                requires: Core, Auth, UI
└── vite.config.js
```

`Modules/Transporter/tests/` **tidak dibuat** — lihat `gap.md` C-6.
Test di `tests/Unit/Transporter/`, `tests/Feature/Transporter/`, `tests/Integration/Transporter/`.

---

## 2. Aliran lapisan

```
Livewire Component
   │  boot(Service $s)          ← injeksi lewat boot(), bukan constructor
   ▼
Service                          ← seluruh aturan bisnis ada di sini
   │  konstruktor menerima RepositoryInterface
   ▼
Repository (Eloquent*)           ← satu-satunya yang menyentuh Model
   ▼
Model                            ← Eloquent, punya $connection dan $searchable
```

Yang **selalu** di service, tidak pernah di Livewire maupun repository:

| Aturan | Service |
|---|---|
| Maker ≠ approver | `OrderApprovalService`, `AdditionalExpenseService` |
| `BUYER_BORNE` / `INTERNAL` tidak pernah berprovisi (F-01.17) | `ProvisionService::eligibleOrders()` |
| Satu induk = satu provisi (F-06.9) | `ProvisionService::create()` |
| Gerbang kelengkapan dokumen (F-06.4) | `DocumentGateService::blockingDeliveryNotes()` |
| Penolakan posting bila `HMEMD_USER_ORION` kosong (F-05.10) | `TransporterPostingService::resolveOrionUser()` |
| Pemilihan rate card berdasarkan **tanggal transaksi** (F-01.5) | `RateCardResolver::resolve()` |

---

## 3. Entitas inti dan hubungannya

```
transp_carrier ──1:N── transp_rate_card ──1:N── transp_rate_line
      │                      │
      │   (tca_type: VENDOR | BUYER_BORNE | INTERNAL)
      │
      └──1:N── transp_order ──1:N── transp_order_dn
                    │        └─1:N── transp_order_cost
                    │        └─1:N── transp_additional_expense ──1:N── _line
                    │        └─1:1── transp_provision ──1:N── transp_provision_dn
                    │                       │
                    │                       └──N:1── transp_bill_line ──N:1── transp_bill
                    └─ tro_source: MANUAL | GRN_PULL | AUTO_DN | (GATE, fase berikutnya)

transp_dn_stage           staging surat jalan dari OT_INVOICE_HEAD, dipurge setelah diproses
transp_grn_pull_attempt   satu baris per percobaan generate TPCHP
transp_document_scan      satu baris per surat jalan yang dikontrol dokumennya
transp_posting_log        satu baris per posting ke ERP — kunci idempotensi
transp_migration_exception  baris yang dibuang/diperbaiki saat migrasi, beserta alasannya
transp_payment            dibuat, tidak dipakai di v1
```

Tiga hal yang membedakan ini dari skema lama dan harus dijaga:

1. **`transp_provision` punya `UNIQUE (TRP_ORDER_ID)`.** Ini yang menegakkan
   "satu induk = satu provisi". Baris legacy yatim (C-04, 331 baris) masuk dengan
   `TRP_ORDER_ID = NULL` — Oracle mengizinkan banyak NULL pada unique index, jadi
   tidak bentrok. Jangan diganti jadi `NOT NULL`.
2. **Nama vendor tidak disimpan** (F-01.2). `transp_carrier` hanya menyimpan
   `TCA_SUPP_CODE`; nama dibaca dari `OM_SUPPLIER` saat ditampilkan.
   Satu-satunya pengecualian: `BUYER_BORNE` punya `TCA_LABEL` sendiri.
3. **Status dokumen tidak menempel di baris DN.** Ia tinggal di
   `transp_document_scan`, satu baris per surat jalan, dengan riwayat utuh.

---

## 4. Approval — satu mesin, dua pemakai

`OrderApprovalService` dan `AdditionalExpenseService` memakai trait
`Modules\Transporter\Services\Concerns\EnforcesMakerChecker`:

```php
trait EnforcesMakerChecker
{
    /**
     * @throws AuthorizationException  bila pelaku = pembuat, atau permission tidak ada
     */
    protected function assertCanApprove(
        string $creatorNik,
        string $actorNik,
        string $permission,
    ): void;
}
```

Aturannya (§4.3, §8):

| Aksi | Siapa | Dicek di |
|---|---|---|
| `Submitted → Approved` transaksi angkutan | user **lain**, punya `transporter.approve-transaction` | `OrderApprovalService` |
| `Submitted → Rejected` transaksi angkutan | idem, **wajib alasan** | idem |
| `Approved → Submitted` (unapprove) | approver yang sama, **hanya selama belum diprovisi** | idem |
| Approve additional expense | user **lain di tim yang sama** (despatch untuk yarn, stores untuk chip), punya `transporter.approve-additional-expense` | `AdditionalExpenseService` |

**Berlaku untuk ketiga jalur pembuatan** — manual, `GRN_PULL`, `AUTO_DN` — tanpa
pengecualian. Hasil generate otomatis masuk `Submitted`, tidak pernah `Approved`
(F-03.8, F-09.8).

**Data migrasi tidak lewat mesin ini.** C-17: baris historis ditulis langsung ke
status akhirnya dengan `TRO_APPROVED_BY = 'MIGRATION'`. Script migrasi memanggil
repository, bukan service.

---

## 5. Kalkulasi tarif — dua jalur yang sengaja dipisah

```php
interface RateCalculatorInterface
{
    /** @return array<int, OrderCostData>  satu baris per rate line yang berlaku */
    public function calculate(RateCard $card, float $qtyKg, Carbon $orderDate): array;
}
```

| Implementasi | Untuk | Rumus |
|---|---|---|
| `YarnRateCalculator` | `TPDN`, `TPSVC` | Berjenjang menurut `TRL_PRIORITY`: baris `W` = flat, baris `Q` = `max_cap × rate`, dengan `is_overflow` berarti `(qty − truck_cap)`. Baris overflow dilewati bila `qty ≤ truck_cap` |
| `ChipRateCalculator` | `TPCHP` | `gross_weight × rate`. Titik. Tanpa prioritas, tanpa kelebihan muatan (F-09.9) |

Dipisah karena di sistem lama keduanya bercampur dan jalur chip diam-diam melewati
logika prioritas — lihat §3.5 PRD. Menyatukannya akan mengundang bug yang sama kembali.

`RateCardResolver::resolve(Carrier $c, ServiceType $t, array $key, Carbon $date)`
memilih rate card yang **berlaku pada `$date`**, bukan yang aktif hari ini.
Ini yang membuat provisi periode lampau bisa direproduksi (F-01.5, uji R-12).

---

## 6. Posting GL

### Yang diangkat ke Core (T012)

```
Modules/LcControl/app/Services/Erp/JournalVoucherPostingService.php
Modules/LcControl/app/Repositories/Erp/EloquentJournalVoucherRepository.php
Modules/LcControl/app/Interfaces/Erp/JournalVoucherRepositoryInterface.php
Modules/LcControl/app/Data/Erp/JournalVoucherData.php
Modules/LcControl/app/Data/Erp/JournalVoucherLineData.php
        ↓  pindah, namespace jadi Modules\Core\…\Erp
Modules/Core/app/Services/Erp/, Repositories/Erp/, Interfaces/Erp/, Data/Erp/
```

**Dipindah tanpa mengubah perilaku.** LcControl memakai kelas Core lewat `use` baru;
tidak ada shim, tidak ada alias. RK-06: jalankan seluruh test sebelum dan sesudah, dan
buka halaman `dashboard/lc.journal-voucher` + `payment-voucher` secara manual.

Yang **ditambahkan** ke service bersama:

| Tambahan | Kenapa |
|---|---|
| Parameter `string $tranCode` eksplisit (menggantikan `tranCodeForLocation()` yang selalu `JV`/`JJV`) | Transporter memposting `TPJV`, bukan `JV` |
| `assertNotAlreadyPosted(string $tranCode, string $idempotencyKey)` — cek lintas `FT_UNPOSTED_` / `FT_CUR_` / `FT_PRV_TRANS_HEADER` | PRD §4.4 butir 3; satu-satunya butir §4.4 yang benar-benar belum ada |

Yang **tidak** ditambahkan: baris `FT_TXN_AUTH`. Lihat `gap.md` C-1 dan `DECISIONS.md` D-01.

### Alur posting

```
Livewire: pilih baris → Preview jurnal (tidak menulis apa pun)
    │  ProvisionJournalBuilder / BillJournalBuilder → JournalPreviewData
    │  user melihat Dr/Cr, akun, IDR, USD, kurs yang dipakai
    ▼
Tombol Post → dispatch PostProvisionJournal (queue: high)
    ▼
Job:
   1. Kunci baris sumber (status → Posting)
   2. transp_posting_log: insert baris Pending dengan payload lengkap
   3. resolveOrionUser()  → tolak dengan pesan jelas bila HMEMD_USER_ORION kosong
   4. assertNotAlreadyPosted()
   5. DB::connection('oracle_mgtdat')->transaction(fn () => $core->post(...))
   6. transp_posting_log → Success + nomor dokumen, atau Failed + pesan
   7. Notifikasi ke pemosting
```

Kalau langkah 5 gagal, **seluruh transaksi Oracle di-rollback** dan baris sumber
kembali ke status sebelumnya. Tidak ada `commit` di tengah — itu yang memperbaiki
T-07 dan menghilangkan alasan lahirnya 30+ tabel backup manual.

**Idempotensi ada di `transp_posting_log`, bukan di `th_flex_10`.** `th_flex_10` tetap
diisi (kompatibilitas laporan), tapi bukan lagi yang menentukan.

---

## 7. Job & command terjadwal

| Nama | Jenis | Queue | Log channel | Idempotensi |
|---|---|---|---|---|
| `PullDeliveryNotesCommand` (`transporter:pull-delivery-notes`) | command terjadwal harian | — | `transporter_dn_pull` | `UNIQUE (TST_DN_SYS_ID)` |
| `GenerateTransportOrders` | job, dipicu user | `high` | `transporter_generate` | baris staging ber-`TST_ORDER_ID` dilewati |
| `GenerateChipOrders` | job, dipicu user (**tidak pernah otomatis**, F-09.2) | `high` | `transporter_generate` | GRN yang sudah punya order dilewati |
| `PostProvisionJournal` | job | `high` | `transporter_posting` | `transp_posting_log` |
| `PostBillJournal` | job | `high` | `transporter_posting` | `transp_posting_log` |
| `ScanDeliveryDocumentsCommand` (`transporter:scan-delivery-documents`) | command terjadwal | `default` | `transporter_doc_scan` | status `Pending` saja yang diperiksa |
| `Export*` (5) | job | `low` | `finance_report` | — |

`ScanDeliveryDocuments` punya satu aturan yang tidak boleh dilanggar (F-10.3b):

> Kalau mount `Doc_Folder` tidak tersedia, job **gagal dengan bersih tanpa mengubah
> status apa pun**, catat ke log channel, kirim notifikasi. Menandai berkas sebagai
> hilang karena mount sedang down akan menahan tagihan secara keliru.

---

## 8. Penanganan error

| Kelas kesalahan | Bentuk | Yang dilihat user |
|---|---|---|
| Validasi input | Livewire validation / DTO `#[Rule]` | Pesan inline di field |
| Pelanggaran aturan bisnis | `DomainException` dari service | Toast `butter-error` dengan pesan service, apa adanya |
| Otorisasi (maker=checker, permission) | `AuthorizationException` | 403 atau toast, tergantung titik |
| Kegagalan posting GL | `transp_posting_log` status `Failed` + notifikasi | Halaman posting menampilkan alasannya, bukan "gagal" saja |
| Kegagalan validasi generate chip | **Peringatan di layar sebelum generate**, bukan error sesudahnya (F-09.5) | Baris ditandai, tombol generate mati untuk baris itu |

Pesan service ditulis dalam **bahasa Indonesia** dan menyebut nilai yang berlaku,
bukan nilai yang di-hardcode — T-21 lahir persis dari pesan yang tidak ikut diperbarui.

---

## 9. Konfigurasi

`Modules/Transporter/config/config.php` → dibaca sebagai `config('transporter.*')`:

```php
return [
    'name' => 'Transporter',

    // F-09.6 — pagar kewajaran di level GRN, bukan batas per jenis truk (§5.8.1)
    'chip' => [
        'max_gross_weight_kg' => env('TRANSPORTER_CHIP_MAX_GROSS_KG', 60000),
    ],

    // F-10.3c — beda antara staging dan produksi
    'document_scan' => [
        'root'          => env('TRANSPORTER_DOC_ROOT', '/mnt/efilling/Doc_Folder'),
        'path_pattern'  => '{root}/{type}/{year}/{type}-{number}.pdf',
        'shadow_mode'   => env('TRANSPORTER_DOC_SHADOW', false),   // F-10.12
    ],

    // F-05.7, F-05.8 — akun & kode transaksi dari data, bukan literal (T-09)
    'gl' => [
        'provision_tran_code' => env('TRANSPORTER_PROVISION_TRAN_CODE', 'TPJV'),
        'bill_tran_code'      => env('TRANSPORTER_BILL_TRAN_CODE', 'TJV'),
        'legacy_tran_codes'   => ['JV'],   // data historis, harus tetap terbaca (§3.7.4a butir 4)
    ],

    // F-01.3a — deprecated, default mati, nol vendor aktif memakainya
    'gross_up' => [
        'enabled' => env('TRANSPORTER_GROSS_UP_ENABLED', false),
        'rate'    => 0.02,
    ],

    'delivery_note' => [
        'pullable_types' => ['LDN', 'JWDN', 'PDN'],   // F-03.9 — EDN & WDN tidak
    ],
];
```

Akun GL sendiri **tidak** di config — ia di tabel `transp_posting_account` dengan
tanggal berlaku, supaya perubahan kebijakan akuntansi tidak butuh deploy (F-05.7).

---

## 10. Yang sengaja tidak dibangun di v1

| Tidak dibangun | Tapi tempatnya disiapkan |
|---|---|
| Payment voucher `BPS`/`BPJ` (§5.11) | Tabel `transp_payment` dan nilai `BPS` di `PostingTypeEnum` |
| Alur gerbang & loading (§5.12) | Nilai `GATE` di `OrderSourceEnum`, kolom nullable `TRO_GATE_IN_AT`, `TRO_GATE_OUT_AT`, `TRO_GATE_VERIFIED_BY` |
| Beberapa truk per transaksi chip (§5.8.1) | Kolom `TRO_IS_MULTI_TRUCK` + `TRO_TRUCK_COUNT_NOTE`, diisi peringatan F-09.14 supaya baris yang perlu dipecah nanti sudah tertandai |
| Migrasi konsumen hilir ke sumber baru | Compatibility view (§4.5), yang memang jembatan sementara (CL-6) |
