# 04 — API Contract

## 1. API principles
- Base path `/api/v1`; JSON only except file uploads/downloads.
- Authentication: Sanctum. First-party browser app keeps session cookie + CSRF when same-site; external/mobile clients use bearer tokens. Token abilities are supplementary: Policies and tenant ownership checks remain mandatory.
- `Accept: application/json`; `Content-Type: application/json` for JSON body.
- Dates/timestamps: RFC 3339/ISO-8601 UTC (`2026-11-13T10:30:00Z`); UI renders Asia/Jakarta.
- Money: integer IDR (`amount_idr`), never float.
- Rates: Mbps numeric decimal with explicit unit field when ambiguity exists.
- Bytes: integer bytes. Throughput: numeric Mbps with sample timestamp/interval.
- Pagination: `page`, `per_page` (default 25, max 100); sorting whitelist only.
- Filtering/search: explicit query params. All list endpoints enforce tenant scope before filtering.
- Public invoice endpoints use opaque token; never require customer account.
- Mutating payment/invoice/webhook operations use idempotency keys.

## 2. Response envelope
Success:
```json
{
  "data": { "id": "..." },
  "meta": { "request_id": "01J...", "timestamp": "2026-11-13T10:30:00Z" }
}
```
List:
```json
{
  "data": [],
  "meta": { "request_id": "...", "page": 1, "per_page": 25, "total": 0, "last_page": 1 }
}
```
Error:
```json
{
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": { "email": ["The email is invalid."] }
  },
  "meta": { "request_id": "..." }
}
```
Do not expose stack traces, SQL, secrets, provider payloads or internal hostnames.

## 3. HTTP semantics and status codes
- `200` read/update/action accepted and completed synchronously.
- `201` resource created.
- `202` asynchronous command accepted (`network sync`, queued export, notification).
- `204` successful delete/revoke with no body.
- `400` malformed request; `401` unauthenticated; `403` forbidden; `404` absent or outside tenant scope (avoid ID enumeration); `409` conflict/stale state/idempotency mismatch; `422` validation/business-rule failure; `429` rate limit; `500` unexpected internal error; `502/503` integration temporarily unavailable.

## 4. Endpoint catalogue
### Identity
| Method | Path | Permission | Purpose |
|---|---|---|---|
| POST | `/auth/token` | public rate-limited | Issue Sanctum mobile/integration token |
| DELETE | `/auth/token` | authenticated | Revoke current token |
| GET | `/me` | authenticated | Identity, roles, partner scope, permissions |
| GET | `/permissions` | authenticated | UI capability discovery only; not authorization |

### Partners and users
| Method | Path | Permission | Purpose |
|---|---|---|---|
| GET/POST | `/partners` | partner.view/create | List/create partner (central only for create) |
| GET/PATCH | `/partners/{partner}` | partner.view/update | Partner detail/update; tenant-bound |
| GET/POST | `/partners/{partner}/users` | partner.users.manage | Manage only own partner staff |
| GET/POST | `/partners/{partner}/bandwidth-contracts` | partner.contract.view / partner.contract.manage | View/create centrally approved contract |
| POST | `/partners/{partner}/bandwidth-contracts/{contract}/capacity-change-requests` | partner.contract.request | Partner asks for capacity change |

### Customers, packages and subscriptions
| Method | Path | Permission | Purpose |
|---|---|---|---|
| GET/POST | `/customers` | customer.view/create | Tenant-scoped list/create |
| GET/PATCH | `/customers/{customer}` | customer.view/update | Tenant-scoped details/update |
| POST | `/customers/{customer}/transfer` | customer.transfer | Central ownership transfer with audit |
| GET | `/internet-packages` | package.view | List allowed packages |
| POST/PATCH | `/internet-packages` or `/{package}` | package.manage | Central package administration |
| GET/POST | `/subscriptions` | subscription.view/create | List/create subscriptions |
| GET/PATCH | `/subscriptions/{subscription}` | subscription.view/update | Read/update permitted fields |
| POST | `/subscriptions/{subscription}/activate` | subscription.activate | Activate and enqueue network provision |
| POST | `/subscriptions/{subscription}/suspend` | subscription.suspend | Suspend and enqueue network action |
| POST | `/subscriptions/{subscription}/restore` | subscription.restore | Restore after preconditions |
| POST | `/subscriptions/{subscription}/change-package` | subscription.change_package | Effective-dated change and policy version |

### Billing / payments
| Method | Path | Permission | Purpose |
|---|---|---|---|
| GET/POST | `/invoices` | invoice.view/create | List/generate invoices |
| GET | `/invoices/{invoice}` | invoice.view | Invoice details under tenant scope |
| POST | `/invoices/{invoice}/void` | invoice.void | Void with reason and audit |
| POST | `/invoices/{invoice}/access-tokens` | invoice.public_link.create | Create public token; return raw token once |
| DELETE | `/invoice-access-tokens/{token}` | invoice.public_link.revoke | Revoke token |
| GET | `/public/invoices/{token}` | public | Limited public invoice projection |
| POST | `/invoices/{invoice}/payment-intents` | payment.create | Create provider payment intent; idempotent |
| POST | `/webhooks/payments/{provider}` | provider signature | Receive signed payment callback |
| GET | `/payments` | payment.view | Scoped payment listing |
| POST | `/payments/{payment}/refunds` | payment.refund | Request/record refund if provider supports |
| POST | `/payments/{payment}/reconcile` | payment.reconcile | Manual reconciliation, audited |

### Network and accounting (central-only writes)
| Method | Path | Permission | Purpose |
|---|---|---|---|
| GET/POST | `/network/nas` | network.device.view/manage | Register NAS; secret references only |
| GET/PATCH | `/network/nas/{nas}` | network.device.view/manage | Update metadata/state |
| GET | `/network/sessions` | network.session.view | Search active/history sessions; tenant projection only for authorized central support |
| POST | `/network/sessions/{session}/disconnect` | network.session.disconnect | Central disconnect; reason required; 202 async |
| GET | `/network/customers/{customer}/usage` | network.usage.customer | Usage series/summary |
| GET | `/network/partners/{partner}/usage` | network.usage.partner | Partner aggregate usage |
| GET | `/network/partners/{partner}/throughput` | network.usage.partner | Queue/traffic sample series |
| GET | `/network/sync-commands` | network.sync.view | Outbox/sync state |
| POST | `/network/sync-commands/{command}/retry` | network.sync.retry | Retry dead/failed command |
| GET | `/network/profiles` | network.profile.view | List speed/isolation profiles |
| POST/PATCH | `/network/profiles` or `/{profile}` | network.profile.manage | Central profile management |

### Tax, notification, reports, audit
| Method | Path | Permission | Purpose |
|---|---|---|---|
| GET/POST | `/tax/submissions` | tax.view/create | Partner or central tax submission |
| GET/PATCH | `/tax/submissions/{submission}` | tax.view/update | Scope, amend draft, review metadata |
| POST | `/tax/submissions/{submission}/submit` | tax.submit | Submit partner report |
| POST | `/tax/submissions/{submission}/review` | tax.review | Central review/approve/reject/request changes |
| GET | `/reports/partners/{partner}/tax` | tax.report.partner | Measurable tax rollup |
| GET | `/reports/billing` | report.billing | Invoice/payment totals |
| GET | `/reports/network-usage` | report.network | Customer/partner usage |
| GET | `/audit-logs` | audit.view | Search privileged changes |
| POST | `/integrations/whatsapp/test` | integration.whatsapp.test | Central test send, audited/rate-limited |
| POST | `/assistant/query` | assistant.use | Scoped read-only AI answer; no direct mutations |

## 5. Request examples
Create customer:
```json
{
  "customer_code": "KN-C000125",
  "full_name": "Nama Pelanggan",
  "phone": "+62812xxxxxxx",
  "email": null,
  "service_address_text": "Alamat instalasi",
  "partner_id": null
}
```
For partner request, `partner_id` is ignored/rejected as authoritative input; server assigns authenticated user's partner. Central user may assign only with `customer.assign_partner` permission.

Create subscription:
```json
{
  "customer_id": "cus_opaque_id",
  "package_id": "pkg_opaque_id",
  "pppoe_username": "KN-P012-000231",
  "start_date": "2026-12-01",
  "billing_day": 1
}
```
Do not accept PPPoE password in general customer/subscription PATCH endpoints. Credential enrollment uses a separately protected operation and secret handling policy.

Suspend:
```json
{
  "reason_code": "INVOICE_OVERDUE",
  "reason_note": "Invoice November melewati masa tenggang",
  "effective_at": "2026-11-14T00:00:00+07:00"
}
```
Response `202` returns `command_id`, `desired_state`, `sync_status=pending`.

## 6. Idempotency and concurrency
- Require `Idempotency-Key` for invoice generation, payment intent creation, payment webhook processing, suspend/restore command and external mutation retries.
- Key scope includes actor/provider + operation + target. Store request payload hash. Same key+same hash returns prior result; same key+different hash returns 409.
- Use optimistic locking/version for subscription/network policy. Stale mutation returns 409.
- Invoice generation must have unique `(subscription_id, period_start, period_end, invoice_kind)` or equivalent constraint.

## 7. Payment webhook contract
- Verify provider signature on raw request bytes before parsing business payload.
- Validate timestamp/nonce or provider event replay semantics.
- Persist event ID and payload hash under unique constraint before processing.
- Return provider-required acknowledgement quickly; enqueue processing.
- Do not trust browser redirect, query string or client-submitted `paid=true`.
- Reconcile pending/unknown state through provider query API and manual exception queue.

## 8. Public invoice security
- Raw token at least 32 random bytes; URL-safe encoding; only hash persisted.
- Endpoint rate-limited by token hash and IP; no sequential invoice lookup.
- Token expiry configurable and revocable; one token maps to one invoice.
- Use `Cache-Control: no-store`, `X-Robots-Tag: noindex`, restrictive `Referrer-Policy`, CSP, no third-party analytics.

## 9. OpenAPI
`../contracts/openapi.yaml` is a starter machine-readable contract. It intentionally represents key stable endpoint groups, not every eventual response field. Agent A extends schemas in sync with this document and creates contract tests. Agent B may add network schemas only through review and must keep `/network/*` endpoint semantics stable.
