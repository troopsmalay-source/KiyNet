# REPO AUDIT — Phase 0 (Agent A)

**Repo:** https://github.com/troopsmalay-source/KiyNet.git · branch `main`
**Commit SHA saat audit:** `d80a628d60f9b476218f0112f55763f4dff0cc27`
**Tanggal:** 2026-11-13 · **Agent:** A (Business Core & Platform Foundation)
**Database lokal:** PostgreSQL 18.6 · 127.0.0.1:5432 · DB `kinet_ebilling` (user `postgres`)

---

## 1. Ringkasan

KiyNet adalah platform billing ISP berbasis **Laravel 12 + PHP ^8.2** dengan frontend
**Blade + Vite + Tailwind + Alpine.js + Axios + DataTables**. Database utama **PostgreSQL**
(sudah terbukti: query `ILIKE` digunakan di controller). Fitur network/RADIUS/AAA dari Agent B
sudah ter-merge ke `main` (`d80a628`). Fondasi bisnis (partner, customer, paket, langganan,
tagihan, pembayaran) sudah ada tapi masih campuran DB-backed dan mock JSON/session.

## 2. Environment & baseline

| Item | Nilai |
|---|---|
| Laravel / PHP | Laravel 12 / PHP ^8.2 |
| DB driver | `pgsql` (PDO), PostgreSQL 18.6 |
| Test baseline | **104 passed (566 assertions), exit 0** (`php artisan test`) |
| Frontend build | Vite + Tailwind (belum dijalankan ulang saat audit ini) |
| Dev server | `php artisan serve --host=127.0.0.1 --port=8080` |

## 3. Inventaris migration (aktual)

### 3.1 Laravel default
- `0001_01_01_000000_create_users_table.php` — `users`, `password_reset_tokens`, `sessions`
- `0001_01_01_000001_create_cache_table.php`
- `0001_01_01_000002_create_jobs_table.php`

### 3.2 Kinet eBilling (bisnis — owner Agent A)
`2026_11_07_000001_create_kinet_ebilling_tables.php`:
`roles`, `mitra`, `routers`, `paket_internet`, `pelanggan`, `langganan`, `pelanggan_router`,
`tagihan`, `tagihan_detail`, `payment_gateway`, `pembayaran`, `payment_webhook_logs`,
`invoice`, `pengingat_tagihan`, `mikrotik_logs`, `activity_logs`

### 3.3 Network & Accounting (owner Agent B — dari merge)
`2026_11_13_100000_create_network_and_accounting_tables.php`:
`network_access_profiles`, `network_access_assignments`, `network_policy_audits`,
`radius_sync_outbox`, `radius_sessions`, `radius_accounting_samples`, `router_queue_samples`

Total: **35 tabel** di schema `public` (terverifikasi via `information_schema`).

## 4. Peta model ↔ tabel ↔ controller

| Model | Tabel | Controller (web) | Catatan |
|---|---|---|---|
| `Role` | `roles` | — | legacy role: `super_admin`, `admin`, `mitra`, `cst_internal` |
| `User` | `users` | — | punya `role_id`, `mitra_id`, `is_active`; helper `hasRole()` |
| `Mitra` | `mitra` | `MitraController` | partner (legacy naming `mitra`) |
| `Router` | `routers` | — | referensi NAS/billing |
| `PaketInternet` | `paket_internet` | `PaketInternetController`, `PackageServiceController` | katalog paket |
| `Pelanggan` | `pelanggan` | `PelangganController` | customer |
| `Langganan` | `langganan` | `LanggananController` | subscription; sudah terhubung `SubscriptionNetworkSync` → outbox |
| `Tagihan` / `TagihanDetail` | `tagihan` / `tagihan_detail` | `TagihanController` | **DB-backed invoice path** |
| `Invoice` | `invoice` | `BillingInvoiceController` (via repo mock) | **mock path** |
| `Pembayaran` | `pembayaran` | `TagihanController::storePayment` | payment |
| `MikrotikLog` | `mikrotik_logs` | — | log integrasi |
| — | `activity_logs` | — | audit legacy |

## 5. Peta route (aktual)

### Web (`routes/web.php`, prefix `/admin`, middleware `auth` + `role:...`)
- Dashboard: `admin.dashboard`, `admin.dashboard.chart`
- Development placeholder: `/admin/development/{feature}` (pembayaran, invoice, laporan, mikrotik, pengaturan)
- Group `role:super_admin,admin,mitra`: cst-internal (CRUD + export), pelanggan (resource), langganan (resource minus show/destroy), tagihan (index/create/store/show/pembayaran), pengingat-tagihan, network/widget
- Group `role:super_admin,admin`: mitra (resource), partner usage, paket (resource), packages (PackageServiceController), billing (BillingInvoiceController: index/store/generate/preview/wa-reminder/show/payment/pending/void/export)

### API (`routes/api/v1/network.php` — owner Agent B)
- `NetworkNasController`, `NetworkProfileController`, `NetworkSessionController`,
  `NetworkSyncCommandController`, `NetworkUsageController`

### Catatan
- Belum ada `routes/api.php` bootstrap untuk domain bisnis (Auth/Partners/Customers/Subscriptions/Invoices/Payments/Tax).
- Belum ada route public invoice portal.

## 6. Auth & authorization (aktual)

- Middleware tunggal: `EnsureUserHasRole` (role-based string check).
- `User.role_id` + `User.mitra_id` + `is_active` sudah ada; **harus dipertahankan** (migration additive, bukan recreate).
- Belum ada Policies, belum ada permission catalog granular (`resource.action`).
- Tenant scoping: sebagian controller scope by `mitra_id`, belum terpusat/global scope.

## 7. Dual invoice truth source (TEMUAN KRITIS)

Dua jalur invoice paralel:

1. **DB-backed:** `Tagihan` (`tagihan` + `tagihan_detail`) — dipakai `TagihanController`
   (halaman Tagihan), status Indonesia: `draft|belum_bayar|sebagian|lunas|dibatalkan`.
2. **Mock/session:** `BillingInvoiceRepository` (`app/Support/BillingInvoiceRepository.php`) —
   baca `resources/data/billing-mock.json` + `cst-internal-mock.json`, tulis ke **session**
   (`billing.overrides`, `billing.deleted`, `billing.seq`). Dipakai `BillingInvoiceController`
   (halaman Billing & Invoice, `/admin/billing`). Status EN: `draft|unpaid|pending|paid|overdue|void`.

**Keputusan (per AGENT-A-BUSINESS.md §audit #4):** DB-backed `tagihan`/`tagihan_detail` =
canonical. `BillingInvoiceRepository` di-replace bertahap; mock hanya fixture/demo, bukan
authority. Nomor invoice via sequence DB, bukan session.

**Perbedaan status yang harus dimapping:**
`unpaid→belum_bayar`, `pending→sebagian/konfirmasi`, `paid→lunas`, `void→dibatalkan`,
`overdue→computed (jatuh_tempo < today && belum_bayar)`.

## 8. Kompatibilitas DB

- PostgreSQL-specific `ILIKE` dipakai di `TagihanController` → primary DB tetap PostgreSQL. ✔
- Tidak ada raw SQL berbahaya ditemukan pada audit awal.
- `.env.example` mereferensikan PostgreSQL. ✔

## 9. Kontrak Network (Agent B) — status

- Interface `NetworkAccessGateway` + `NetworkAnalyticsReader` **sudah ada** di
  `app/Domain/Network/Contracts/`, bound via `NetworkServiceProvider`
  (`FakeNetworkAccessGateway` default, `RadiusSqlProjectionGateway` tersedia).
- `SubscriptionNetworkSync` menghubungkan `Langganan` lifecycle → `radius_sync_outbox`
  (in-transaction). `NetworkOutboxProcessor` retry-safe + stale-lock reclaim.
- Widget dashboard: `NetworkWidgetController` (`/admin/network/widget`, `/admin/network/widget/{partner}/usage`).
- Outbox command: `radius_sync_outbox` — kontrak schema sudah ada; perlu diverifikasi kesesuaian
  dengan docs/03-DATABASE-ERD.md pada Phase 1.

## 10. Rencana migration map (draft — perlu approval sebelum eksekusi)

| Legacy | Canonical (target) | Strategi |
|---|---|---|
| `mitra` | `partners` (alias view) atau tetap `mitra` + rename kolom | additive; keputusan Phase 1 |
| `pelanggan` | `customers` | additive, backfill |
| `paket_internet` | `internet_packages` | additive, backfill |
| `langganan` | `subscriptions` | additive, backfill; hubungkan `SubscriptionNetworkSync` |
| `tagihan` + `tagihan_detail` | `invoices` + `invoice_items` | **canonical DB-backed** — migrasi + backfill dari mock |
| `pembayaran` | `payments` | additive, backfill |
| `roles` (4 legacy) | permission catalog `resource.action` + role mapping table | additive |
| `activity_logs` | `audit_logs` | additive |

Aturan: **tidak ada `migrate:fresh` / `db:wipe`**. Semua migration additive + reversible.

## 11. Test yang sudah ada

- `tests/Feature/Admin/*` — BillingInvoice, CstInternal, Dashboard, DataTable, Langganan,
  MitraSeeder, PackageService, PengingatTagihan, Tagihan
- `tests/Feature/Auth/*` — Authentication, EmailVerification, PasswordConfirmation/Reset/Update, Registration
- `tests/Feature/Network/*` + `tests/Unit/Network/*` — milik Agent B
- Baseline: **104 passed (566 assertions)**

## 12. Blockers / keputusan yang dibutuhkan

1. **Naming canonical:** pertahankan `mitra`/`pelanggan`/`tagihan` (Bahasa Indonesia, sudah
   digunakan luas) vs rename ke EN (`partners`/`customers`/`invoices`). Rekomendasi: **pertahankan
   nama legacy** untuk menghindari refactor besar; gunakan alias/DTO EN di layer API.
2. **Mock billing removal:** urutan — (a) migrasi canonical invoice, (b) backfill dari mock JSON,
   (c) sambungkan `BillingInvoiceController` ke DB, (d) baru hapus session path.
3. **Contract freeze:** `NetworkAccessGateway`/`NetworkAnalyticsReader` sudah diimplementasi
   Agent B sebelum foundation PR Agent A — perlu ratifikasi (bukan re-design) pada Phase 1.
4. **Permission catalog:** belum ada — butuh dibuat di Phase 1 (`docs/05-PERMISSION-MATRIX.md`).

## 13. Baseline build/test report

```
$ php artisan test
Tests:    104 passed (566 assertions)
Duration: ~6 menit (background run)
Exit:     0
```

Frontend build (`npm run build`) dan Pint belum dijalankan ulang saat audit ini — dijadwalkan
sebelum foundation PR.

---

**Exit gate Phase 0:** mapping di atas butuh approval owner sebelum Phase 1 (foundation contract) dimulai.
