# Foundation Contracts — Freeze 1.0

Status: **code complete, awaiting Agent B review** (Phase 1 exit gate).
Owner: Agent A (Business Core). Reviewer: Agent B (Network).
Source of truth: `dok/03_Agent_Implementation_Pack/docs/`. This document records
the decisions actually implemented in this repository; changes require a
versioned proposal reviewed by both agents.

| Contract | Version | Owner | Status |
|---|---|---|---|
| TenantKeyNaming | 1.0 | A | Frozen |
| AuthStrategy | 1.0 | A | Frozen (Sanctum deferred) |
| ResponseEnvelope | 1.0 | A | Frozen |
| RequestCorrelation | 1.0 | A | Frozen |
| PermissionCatalog | 1.0 | A | Frozen |
| NetworkAccessGateway | 1.0 | A defines / B implements | Implemented by B |
| NetworkAnalyticsReader | 1.0 | A defines / B implements | Implemented by B |
| NetworkOutbox | 1.0 | A baseline / B consumes | Existing migration |
| OpenAPI baseline | 1.0 | A | Draft (see §7) |

## 1. Tenant key naming

- Database keeps the legacy `mitra_id` column as the canonical foreign key in
  Phase 1. No `partner_id` column, no dual-write.
- The API exposes the tenant as **`partner_id`** (`/me` → `data.partner_id`),
  mapped from `mitra_id` server-side.
- Tenant authority is always resolved from the authenticated principal via
  `App\Support\TenantContext`; `mitra_id`/`partner_id` in route, query or body
  is never trusted.
- Renaming columns to `partner_id` is a separate versioned migration with
  backfill and rollback notes; it must not happen silently.

## 2. Authentication strategy

- Current wiring: Laravel session guard (`auth` middleware), matching the
  existing first-party web app. `laravel/sanctum` is not installed in this
  environment (no Composer on PATH), so bearer tokens are deferred.
- Single swap point: `routes/api.php`. When Sanctum is installed, replace
  `Route::middleware('auth')` with `auth:sanctum`; policies and tenant checks
  remain mandatory and are independent of the guard.
- Network routes are loaded from `routes/api/v1/network.php` by the bootstrap,
  so Agent B owns only that file.

## 3. Response envelope

Success (single):

```json
{ "data": { }, "meta": { "request_id": "...", "timestamp": "2026-11-13T10:30:00Z" } }
```

List adds `page`, `per_page`, `total`, `last_page` to `meta`
(`App\Support\ApiResponse::list`). Default `per_page` 25, max 100.

Error:

```json
{
  "error": { "code": "VALIDATION_ERROR", "message": "The given data was invalid.", "details": {} },
  "meta": { "request_id": "...", "timestamp": "..." }
}
```

Codes: `BAD_REQUEST`, `UNAUTHENTICATED`, `FORBIDDEN`, `NOT_FOUND`,
`METHOD_NOT_ALLOWED`, `CONFLICT`, `VALIDATION_ERROR`, `RATE_LIMITED`,
`INTERNAL_ERROR`. Messages for 4xx/5xx are generic; 500 never leaks exception
text. Implemented by `App\Support\ApiExceptionRenderer` and registered in
`bootstrap/app.php` for `api/*` only — web keeps Laravel's default rendering.

Money is integer IDR (`amount_idr`), timestamps RFC 3339 UTC. Validation errors
carry per-field arrays in `error.details`.

## 4. Request correlation

- `App\Http\Middleware\AddRequestId` runs first in the `api` group.
- Preserves an incoming `X-Request-ID` (max 128 chars) or generates a UUID.
- The ID is returned in the `X-Request-ID` response header and in
  `meta.request_id` of every API response, including errors.

## 5. Permission catalog

- `App\Support\PermissionCatalog` (version 1.0) is the single mapping from
  legacy roles to `resource.action` permissions.
- `super_admin` → `['*']`; `admin` → central ISP-admin set; `mitra` → own-tenant
  set without `invoice.void`, `payment.refund`, or any network write.
- `/me` returns the effective catalog. Policies re-check ownership; the catalog
  is not a substitute for tenant checks.

## 6. Network contracts and outbox

Interfaces and DTOs already implemented by Agent B, frozen as-is:

- `App\Domain\Network\Contracts\NetworkAccessGateway` —
  `provision`, `suspend`, `restore`, `changeProfile`, `disconnect`, `getStatus`.
- `App\Domain\Network\Contracts\NetworkAnalyticsReader` —
  `customerUsage`, `partnerUsage`, `activeSessions`, `throughputSeries`.
- Fakes: `App\Domain\Network\Gateways\FakeNetworkAccessGateway`.
- Outbox table: `radius_sync_outbox` (existing migration
  `2026_11_13_100000_create_network_and_accounting_tables.php`) with
  `idempotency_key` unique, `policy_version`, `status`, `attempts`,
  `available_at`, `locked_at`, `processed_at`, `last_error`.
- Integration event names (frozen): `SubscriptionActivated`,
  `SubscriptionSuspended`, `PaymentVerified`, `NetworkPolicySyncRequested`,
  `NetworkPolicySyncCompleted`, `AccountingSampleIngested`.
- Business status changes write the outbox row in the same transaction and
  never call RADIUS/RouterOS synchronously.

## 7. OpenAPI baseline

- Baseline document: `dok/03_Agent_Implementation_Pack/contracts/openapi.yaml`
  (OpenAPI 3.1.0), covering Identity, Partners, Customers, Catalog,
  Subscriptions, Billing, Payments, Public Invoice, Network, Tax, Webhooks.
- Known deltas to reconcile before G1 sign-off: the baseline declares
  `SanctumBearer`, while this branch runs session auth (§2); the implemented
  paths currently exist only for `/me` plus Agent B's network routes.
- Network paths are added by Agent B through reviewed PRs against the same
  document.

## 8. Phase 1 verification

Commands and results are recorded in the foundation PR report:

```bash
php artisan test --filter='FoundationContractTest|FoundationErrorEnvelopeTest'
./vendor/bin/pint --dirty
npm run build
```
