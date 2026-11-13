# Phase 0 — Network / AAA repository audit (Agent B)

| Item | Value |
|---|---|
| Repository | `https://github.com/troopsmalay-source/KiyNet.git` |
| Branch cloned | `main` |
| Base commit | `cdfe19bff4194266964009dec54e8244adfccbca` (`docs: add daily task split for 2-person team`) |
| History available | 4 commits (shallow clone depth 50) |
| Working branch | `feature/network-radius-accounting` (from the base commit above) |
| Audit date | 2026-11-13 |
| Scope | network access, RADIUS, MikroTik, accounting, analytics |

This audit covers what Agent B needs. It is **not** a full business-domain audit
(Agent A owns Phase 0 for shared/business modules).

## 1. Runtime actually observed

| Item | Value |
|---|---|
| PHP (local agent machine) | 8.2.33 ZTS, `pdo_sqlite` + `pdo_mysql`, **no `pdo_pgsql`**, no PostgreSQL server |
| Composer | not installed on the machine; downloaded `composer-stable.phar` (2.11.2) to install dependencies |
| Laravel | `laravel/framework ^12.0`, PHP constraint `^8.2` |
| Lockfile issue | `laravel/pint v1.32.1` requires PHP `^8.3`; `composer install` needs `--ignore-platform-req=php` locally. CI/host PHP must be ≥ 8.3 for Pint |
| Frontend | Vite 7, Tailwind 3 + `@tailwindcss/vite` 4, Alpine, Axios, DataTables |
| DB target | `.env.example` + `phpunit.xml` use `pgsql` (`kinet_ebilling`, `kinet_ebilling_test`) |
| API scaffold | **none**: no `routes/api.php`, no `install:api`, no Sanctum dependency, no `api` route registration in `bootstrap/app.php` |
| Auth | Breeze session auth on the `web` guard; `EnsureUserHasRole` alias `role:` |
| Roles seeded | `super_admin`, `admin`, `mitra` only |

### Baseline test status (honest)

`php artisan test` cannot run on this host as configured:

- `phpunit.xml` forces `DB_CONNECTION=pgsql`, and `pdo_pgsql` is not installed;
- `config/database.php` opens SQLite with `PDO::SQLITE_OPEN_READONLY`, so the
  SQLite fallback cannot migrate either.

Observed result with the SQLite fallback: **56 failed / 3 passed**, all failures
`attempt to write a readonly database`. That is an environment limitation, not a
code regression. Agent B tests therefore use a dedicated writable SQLite
connection (`tests/Feature/Network/NetworkTestCase.php`) and run the real
migrations. The shared suite must be run by the owner on PostgreSQL before merge.

## 2. Existing network-related schema (verbatim from migrations)

| Table | Network-relevant columns | Owner going forward |
|---|---|---|
| `routers` | `nama_router`, `ip_address`, `api_port`, `username`, `password_encrypted` (encrypted cast), `jenis` (`pppoe`/`hotspot`), `status`, `last_connected_at` | pre-existing shared; **not edited by Agent B** |
| `mikrotik_logs` | `router_id`, `pelanggan_id`, `aksi`, `status`, `request_data`, `response_data` | pre-existing shared; not edited |
| `pelanggan_router` | `pelanggan_id`, `router_id`, `service_name`, `secret_name`, `remote_address`, `status_sinkronisasi` | pre-existing shared; not edited |
| `langganan` | `pppoe_username` (unique), `pppoe_password_encrypted` (encrypted cast), `ip_static`, `profile_router`, lifecycle status | Agent A (subscription truth) |
| `paket_internet` | `download_mbps`, `upload_mbps`, `harga` | Agent A (catalog) |

There is **no** RADIUS/NAS/accounting/outbox/profile table, no `subscriptions`
canonical table, and no partner bandwidth contract table.

## 3. Mapping to network-owned targets

| Target (schema register) | Source | Decision |
|---|---|---|
| `nas_devices` | `routers` (+ new fields) | New network-owned registry; `routers` stays untouched. Link by an additive `routers.nas_device_id` only through an agreed contract PR |
| `network_access_assignments` | `langganan` (`pppoe_username`) | One current assignment per subscription; subscription truth stays Agent A |
| `network_access_profiles` | `paket_internet` speed columns | Network profile is versioned and vendor-attribute-bearing; catalog price stays Agent A |
| `radius_sync_outbox` | new | Created by Agent B after interface freeze |
| `radius_sessions`, `radius_accounting_samples` | new | Fed by RADIUS accounting |
| `customer_usage_daily`, `partner_usage_daily`, `router_queue_samples` | new | Rollups/samples; never a second customer master |

Attribute naming in business tables is Indonesian (`mitra_id`, `pelanggan_id`,
`langganan_id`); the canonical network targets use English names per spec. The
assignment stores `subscription_id`/`customer_id`/`partner_id` as numeric IDs
**without** FKs across bounded contexts, so Agent A can rename/migrate without a
cross-owned migration. Foreign keys are used only inside the network schema.

## 4. Gaps confirmed (all addressed or explicitly blocked)

| # | Gap | State |
|---|---|---|
| 1 | No RADIUS projection/accounting tables | **done** (additive migration) |
| 2 | No accounting normalization/dedupe | **done** (`AccountingIngestor`) |
| 3 | No daily usage aggregates | **done** (`UsageRollupService`) |
| 4 | No outbox consumer/retry/policy version guard | **done** (`NetworkOutboxDispatcher/Processor`) |
| 5 | No NAS registry/secret references | **done** (`nas_devices`, refs only) |
| 6 | No network API routes | **done** (`routes/api/v1/network.php`, not yet included — Agent A) |
| 7 | No FreeRADIUS config/runbooks | **done as templates**, no lab evidence |
| 8 | No router queue sampling collector | **blocked** (needs lab RouterOS + SNMP/API sampling design) |
| 9 | No RouterOS API client | **blocked** (disconnect stays pending by design) |
| 10 | No PPPoE credential enrollment | **blocked** (auth method undecided) |
| 11 | Partner aggregate cap | **blocked** (needs lab measurement) |

## 5. Open decisions (human + network owner)

1. **Auth method**: PAP + `Cleartext-Password` vs CHAP/MS-CHAP digest. A Laravel
   `bcrypt` hash is not automatically usable for CHAP.
2. **FreeRADIUS major**: 3.2.x or 4.0.x (schema/config are not interchangeable).
3. **Target RouterOS**: model + exact version per NAS, PPPoE topology, IP pools,
   L2/L3 transport, expected concurrent sessions.
4. **Lab availability**: a real lab router + RADIUS VM is required before any
   behavior claim (rate direction, CoA/Disconnect, PCQ cap).
5. **Canonical identifier exposure**: network API currently returns numeric IDs as
   strings; if Agent A moves to UUIDs, the DTO/controller mapping changes.
6. **`routers` vs `nas_devices`**: keep both (current) or add a link column later.
7. **Test database**: CI must provide PostgreSQL for the shared suite.

## 6. Safety observations

- Existing `Router::$casts` and `Langganan::$casts` already encrypt credential
  columns at rest; the seeded demo password `ChangeMe123!` must never reach
  production.
- No secrets were found in tracked files during this audit; `.env` is ignored.
- No RADIUS/RouterOS port is exposed by the Laravel app; all management is
  described as private/VPN-only in this branch.
