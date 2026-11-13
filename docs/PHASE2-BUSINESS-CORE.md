# Phase 2 — Business Core (Agent A)

Status: implemented on `feature/business-core`, built on the Phase 1 contract
freeze. Domain rules follow `dok/03_Agent_Implementation_Pack/docs/`.

## Endpoint map (implemented)

All routes are under `/api/v1`, session-authenticated unless marked public.
Responses use the Phase 1 envelope; errors use `ApiExceptionRenderer`.

| Area | Endpoints |
|---|---|
| Identity | `GET /me` |
| Partners | `GET/POST /partners`, `GET/PATCH /partners/{partner}` |
| Partner staff | `GET/POST /partners/{partner}/users`, `PATCH /partners/{partner}/users/{user}` |
| Wholesale contract | `GET/POST /partners/{partner}/bandwidth-contracts`, `PATCH /partners/{partner}/bandwidth-contracts/{contract}`, `POST .../capacity-change-requests` |
| Customers | `GET/POST /customers`, `GET/PATCH /customers/{customer}`, `POST /customers/{customer}/transfer` |
| Catalog | `GET /internet-packages`, `GET /internet-packages/{package}`, `POST /internet-packages`, `PATCH /internet-packages/{package}` |
| Subscriptions | `GET/POST /subscriptions`, `GET/PATCH /subscriptions/{subscription}`, `POST .../activate`, `.../suspend`, `.../restore`, `.../change-package` |
| Invoices | `GET/POST /invoices`, `GET /invoices/{invoice}`, `POST /invoices/{invoice}/void`, `POST /invoices/{invoice}/access-tokens`, `DELETE /invoice-access-tokens/{token}` |
| Payments | `GET /payments`, `POST /invoices/{invoice}/payment-intents`, `POST /payments/{payment}/refunds`, `POST /payments/{payment}/reconcile` |
| Public portal | `GET /public/invoices/{token}` (public, throttle 30/min) |
| Webhooks | `POST /webhooks/payments/{provider}` (public, HMAC signature, throttle 60/min) |
| Tax | `GET/POST /tax/submissions`, `GET/PATCH /tax/submissions/{submission}`, `POST .../submit`, `POST .../review`, `GET /reports/partners/{partner}/tax` |
| Reports & audit | `GET /reports/billing`, `GET /audit-logs` |
| Integrations | `POST /integrations/whatsapp/test` (fake adapter in non-production) |

## Guarantees

- **Tenant isolation** — partner scope comes from `TenantContext`; foreign
  resources return 404, never 403-with-existence. Request-supplied `mitra_id`/
  `partner_id` never moves a customer; transfer requires central
  `customer.transfer` and writes an audit row.
- **Invoice idempotency** — `Idempotency-Key` is required; the same key returns
  the original invoice, a different key for the same subscription+period is
  `409`, and unique `(langganan_id, periode)` protects the database. Line items
  snapshot the subscription price at generation time and are never recomputed
  from the mutable package catalog.
- **Payment webhook** — HMAC-SHA256 over raw bytes, provider event id unique
  (`payment_webhook_logs`), amount checked against the intent, replay returns
  `already_processed` without double-crediting, invoice totals recalculated
  from successful payments only.
- **Public invoice token** — 64-char random token, SHA-256 hash only in
  `invoice_access_tokens`, expiry (30 days default, `billing.public_token_days`),
  revocable, strict field allowlist (no phone/address/NIK/PPPoE), `no-store`,
  `noindex`, `no-referrer`.
- **Subscription → network** — business status and `SubscriptionNetworkSync`
  outbox write share one transaction; network calls are never synchronous
  inside the request. Actions answer `202` when a command is queued, `200`
  when no PPPoE projection is required.
- **Tax** — no hardcoded legal formula: gross and tax amounts are submitted and
  reviewed by an officer; unique per partner+category+period; officers review,
  partners can only submit their own tenant.
- **Audit** — privileged changes write to the legacy `activity_logs` table
  (the schema register's `audit_logs` mapping from `docs/REPO_AUDIT.md`),
  searchable centrally via `GET /audit-logs`.
- **Notifications** — transactional `notification_outbox` with unique
  idempotency key; invoice generation enqueues `invoice_created`. Provider
  access goes through `WhatsAppGateway` (fake adapter bound by default).

## Migrations

Additive only: `2026_11_13_200000_phase2_business_core.php` adds
`tagihan.idempotency_key/voided_at/void_reason`, `pembayaran.idempotency_key`,
and creates `invoice_access_tokens`, `subscription_events`,
`partner_bandwidth_contracts(+events)`, `tax_categories/submissions/items`,
`notification_outbox/attempts`, `payment_refunds`. No destructive migration,
no `migrate:fresh`.

## Known gaps (not Phase 2 blockers)

- OpenAPI baseline in `dok/.../contracts/openapi.yaml` still lists the Phase 1
  subset; this document is the current path inventory.
- Refunds are recorded (`requested`) but provider refund execution belongs to
  the Phase 4 payment adapter.
- Tax document upload endpoints are not implemented (no contract path yet).
- Network usage reports stay under Agent B's `/network/*` endpoints.
- Sanctum bearer tokens remain deferred (Phase 1 decision).
