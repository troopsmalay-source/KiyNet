# Agent B → Agent A contract requests

Everything below is a shared-file change that Agent B **did not** make. Each item
is a small, reviewable change owned by Agent A at contract freeze.

## 1. Register the network service provider

`bootstrap/providers.php`:

```php
use App\Providers\NetworkServiceProvider;

return [
    App\Services\... // existing lines
    App\Providers\AppServiceProvider::class,
    NetworkServiceProvider::class,
];
```

Binds `NetworkAccessGateway` (`fake` by default, `radius` when configured) and
`NetworkAnalyticsReader`. Until registered, controllers type-hint the concrete
reader/dispatcher and tests register the provider manually; production must use
the provider so the driver/config stays injectable.

## 2. Include the network route file

`routes/api.php` (created by Agent A) or the API bootstrap:

```php
Route::middleware(['auth:sanctum'])
    ->prefix('v1')
    ->group(base_path('routes/api/v1/network.php'));
```

The file already prefixes `network` and names routes `api.network.*`. It
deliberately has no auth middleware of its own (bearer vs session remains Agent A's
decision); every controller returns `401` when `$request->user()` is not a `User`.

Network endpoints implemented:

| Method | Path |
|---|---|
| GET | `/api/v1/network/sessions` |
| POST | `/api/v1/network/sessions/{session}/disconnect` |
| GET | `/api/v1/network/customers/{customer}/usage` |
| GET | `/api/v1/network/partners/{partner}/usage` |
| GET | `/api/v1/network/partners/{partner}/throughput` |
| GET | `/api/v1/network/sync-commands` |
| POST | `/api/v1/network/sync-commands/{command}/retry` |
| GET/POST | `/api/v1/network/nas`, PATCH `/nas/{nas}` |
| GET/POST | `/api/v1/network/profiles`, PATCH `/profiles/{profile}` |

Response envelope is produced by `App\Domain\Network\Support\ApiEnvelope` until
your shared helper exists; the JSON shape already matches
`docs/04-API-CONTRACT.md` (`data` + `meta.request_id/timestamp`).

## 3. Emit the business transition into the outbox

On subscription activate / suspend / restore / package change, inside the same DB
transaction that saves business state:

```php
$command = new NetworkAccessCommand(
    subscriptionId: $subscription->id,
    customerId: $subscription->customer_id,
    partnerId: $subscription->partner_id,
    username: $subscription->pppoe_username,
    nasDeviceId: $assignment?->nas_device_id,
    profileCode: $profileCode,
    policyVersion: $subscription->current_policy_version,
    desiredState: 'active' | 'suspended',
    correlationId: (string) Str::uuid(),
    idempotencyKey: 'sub:'.$subscription->id.':'.$subscription->current_policy_version.':activate',
);

app(NetworkOutboxDispatcher::class)->enqueueAccessCommand($command, 'provision');
```

Rules:

- `idempotency_key` must be stable per business transition; the same key with a
  different payload throws `IdempotencyConflict` (map to `409`).
- No password/secret in the payload — `NetworkAccessCommand::toPayload()` already
  strips it. Credential enrollment is a separate protected flow.
- Suspend/restore/change-profile map to `suspend`/`restore`/`change_profile`.
- The assignment row (`network_access_assignments`) is the network projection;
  business code should not write it directly.

## 4. Run the consumer and rollups

Queue worker (recommended command wrapping `NetworkOutboxProcessor`):

```php
// App\Console\Commands\ProcessNetworkOutbox
$processor = app(NetworkOutboxProcessor::class);
$processor->reclaimStaleLocks();
$processor->processPending(100);
```

Schedule:

```php
Schedule::command('network:outbox')->everyMinute()->withoutOverlapping();
Schedule::command('network:reconcile-stale')->hourly();
Schedule::command('network:rollup-usage')->dailyAt('01:15');
```

Agent B has not created these command classes yet (they need your queue/scheduler
conventions). The services are ready: `NetworkOutboxProcessor`,
`StaleSessionReconciler`, `UsageRollupService`.

## 5. Permission catalog mapping

Temporary: `config('network.central_roles')` = `super_admin, admin, network_operator`
and `config('network.partner_roles')` = `mitra, mitra_admin, mitra_billing, mitra_operator`.
Replace with your catalog:

- writes: `network.device.manage`, `network.profile.manage`,
  `network.sync.retry`, `network.session.disconnect`
- reads: `network.device.view`, `network.session.view`,
  `network.usage.customer`, `network.usage.partner`, `network.sync.view`

Partner roles get **no** network write permission; partner reads are always scoped
by `users.mitra_id`.

## 6. Environment variables (add to `.env.example` when approved)

```dotenv
NETWORK_DRIVER=fake                 # fake | radius  (never radius in CI)
RADIUS_DB_CONNECTION=               # named Laravel connection to kikinet_radius
ROUTEROS_API_ENABLED=false          # stays false until lab-verified
NETWORK_OUTBOX_MAX_ATTEMPTS=8
NETWORK_OUTBOX_RETRY_BASE=30
NETWORK_OUTBOX_RETRY_MAX=3600
NETWORK_OUTBOX_STALE_LOCK=10
NETWORK_ACCOUNTING_VERSION=1
NETWORK_SESSION_STALE_MINUTES=90
NETWORK_ACCOUNTING_MIN_INTERIM=30
NETWORK_RATE_LIMIT_TEMPLATE=:uploadM/:downloadM
```

`NETWORK_DRIVER=radius` is rejected unless `RADIUS_DB_CONNECTION` is a valid
connection; the projection adapter throws `NetworkProjectionUnavailable`
otherwise. Local/CI therefore cannot write RADIUS policy.

## 7. Schema register additions

Add to `database/SCHEMA-REGISTER.md` (B-owned): `nas_devices`,
`network_access_profiles`, `network_access_assignments`, `radius_sync_outbox`,
`network_policy_audits`, `radius_sessions`, `radius_accounting_samples`,
`customer_usage_daily`, `partner_usage_daily`, `router_queue_samples`.

Migration: `2026_11_13_100000_create_network_and_accounting_tables.php`
(additive, reversible, no shared table altered). No FK exists to
`pelanggan`/`langganan`/`mitra`; IDs are stored without cross-context FKs.

## 8. Open product decisions needed from Agent A

1. Canonical `subscription_id`/`customer_id` values when business tables are
   renamed (current assignments store numeric IDs).
2. Whether the network API exposes numeric IDs or UUIDs.
3. Who calls suspend on overdue invoices (billing lifecycle → outbox) and the
   exact `reason_code` values.
4. Timezone policy for daily usage (currently UTC buckets).
