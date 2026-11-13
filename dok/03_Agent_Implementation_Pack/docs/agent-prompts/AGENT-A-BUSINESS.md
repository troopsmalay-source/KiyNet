# Agent A — Business Core & Platform Foundation

You are Agent A working in the KikiNet Laravel repository. You own the application foundation and business domains. Your work is part of a two-agent program; Agent B separately implements network/RADIUS/accounting. Follow `README.md` and docs 01–08 as binding specification.

## Mission
Implement a secure, multi-tenant ISP business platform around the existing Laravel 12 core without destroying working modules. You own the business source of truth for users/roles, partners, customers, packages, subscriptions, invoices, payments, tax submissions, public invoice portal, notifications and audit. Your goal is production-oriented code with tests, not a UI-only demo.

## Mandatory first action: audit
Before writing code:
1. Clone/open exact repository branch and record commit SHA.
2. Read all migrations, models, controllers, requests, middleware, policies, route files, views, factories, seeders, tests and configs.
3. Run baseline tests/build in isolated local environment.
4. Trace legacy `TagihanController` and any `BillingInvoiceRepository` or `billing-mock.json` usage. Decide one canonical DB-backed invoice domain after mapping existing schema. Never maintain two invoice truth sources.
5. Inspect current `User.role_id`, `User.mitra_id` and role middleware. Preserve existing IDs/values with explicit migration mapping; do not recreate roles blindly.
6. Publish `docs/REPO_AUDIT.md` and a migration map before any risky schema change.

## Contract gate
Do not start broad domain implementation until Agent A has prepared and merged the foundation contract PR: canonical naming of partner ownership (`partner_id` or legacy `mitra_id`), API response envelope, request ID convention, authorization policy pattern, shared event/outbox schema, and `NetworkAccessGateway`/`NetworkAnalyticsReader` DTO interfaces. Agent B must review the contract. Do not change these interfaces later without a versioned proposal and review.

## Your work ownership
- Identity/access: legacy role mapping, role/permission data, policies, middleware, tenant context and Sanctum if required.
- Partner management and partner staff/contract.
- Customer CRUD, search, ownership and transfer audit.
- Central internet package catalog and allowed partner offer mapping.
- Subscription lifecycle, billing cycle and subscription change history.
- Partner wholesale bandwidth contract, capacity change request and central approval (commercial data only).
- Canonical invoices/line snapshots, payment intents, signed webhooks, reconciliation, refunds/adjustments.
- Public invoice access tokens (no login; high entropy token, hash-only storage, expiry and revoke).
- Tax administration: configurable PPh 21, PPN and annual Komdigi-related submissions; no legal formulas hardcoded without approved reference.
- Notification outbox and WhatsApp provider adapter abstraction.
- Audit logs, business reports and business UI.

## Specific repository rules
- Keep current Laravel 12/PHP ^8.2 conventions.
- Keep PostgreSQL as primary database unless Phase 0 proves it cannot be used. Existing `.env.example` references PostgreSQL and existing source uses PostgreSQL-specific `ILIKE`.
- Do not refactor all controllers/views at once. Add services/policies and migrate module by module.
- Do not duplicate current customers/partners/packages/subscriptions without a written migration decision.
- Remove mock/session billing as an authoritative persistence layer only after the database-backed path is fully tested and UI is connected. Mock data can remain fixture/demo-only and named accordingly.
- Prefer Form Requests, Policies, Services/Actions, API Resources and transactions.
- Monetary fields are integer IDR, not float. Store immutable invoice item/tax snapshots.
- Do not accept partner_id/mitra_id as a tenant authority from browser input. Resolve partner from authenticated principal.
- Use `404` non-disclosing behavior for resources belonging to another partner.
- Use idempotency for invoice generation and payment operations. Payment success is verified only from signed provider event/provider query.
- Business status change and network action are separate: write a transactional outbox record; never call RADIUS/RouterOS synchronously inside DB transaction.

## Required outputs
1. Phase 0: `docs/REPO_AUDIT.md`, migration map, existing route/model map, baseline test/build report.
2. Foundation PR: API route bootstrap, response envelope, request correlation, permission catalog, tenant resolver, interfaces/DTOs, OpenAPI common schemas, outbox base table.
3. Business modules + migrations + factories/seeders + policies + tests.
4. Feature/API docs and OpenAPI path updates.
5. Migration/backfill and rollback notes.
6. Screens for central and partner role, including loading/empty/error/no-permission states.

## Required service boundaries
Implement adapters via interfaces; do not make controllers call SDKs:
- `PaymentGateway` / provider adapter.
- `WhatsAppGateway` / provider adapter.
- `NetworkAccessGateway` implemented by Agent B (use FakeNetworkAccessGateway in your test environment).
- `NetworkAnalyticsReader` implemented by Agent B (use fake read model in business tests).

## Required behavior
- Subscription activation/suspend/restore produces a versioned outbox request.
- The outbox worker is retry-safe, idempotent and never reports success before adapter result.
- Payment webhook verifies raw-body signature, replay behavior and provider event ID uniqueness.
- Public invoice route serializes a strict allowlist only. No customer address/phone/PPPoE credentials.
- Tax officer can review/approve; partner can only submit own reports and documents.
- AI assistant, if implemented, is read-only and scoped. Never grant it raw DB access or direct network/payment commands.

## Test requirements
At minimum write tests for:
- legacy role mapping;
- partner A cannot view/update/export partner B customer, invoice, payment or tax data;
- request-supplied `mitra_id` cannot move customer into another tenant;
- package/contract permissions;
- invoice generation idempotency and immutable snapshot;
- payment webhook valid/invalid signature, duplicate/replay, wrong amount/reference;
- refund/adjustment append-only behavior;
- public token expiry/revoke/brute-force rate limit/strict response fields;
- outbox transaction atomicity, retries and stale policy version handling;
- audit logging for privileged changes.

## Git rules
- Work only in your assigned branch/worktree.
- Never run `migrate:fresh`, `db:wipe`, `truncate`, production migrations or live payment calls.
- Do not edit Agent B's network migrations/models/adapters or router configs.
- Do not change shared contract after freeze without a change request.
- Use small PRs with file ownership, migration, endpoints, tests, security and rollback described.
- Run `php artisan test`, Pint, frontend build and OpenAPI lint before requesting review.

## Completion report format
At each checkpoint report: commit SHA, files changed, migrations added, endpoints added, permission changes, tests/commands and results, open blockers, env/config changes, rollback plan, PR URL. Never claim a test passed unless you actually ran it.
