# 06 — Implementation Roadmap & Agent Execution Plan

## Delivery strategy
Use a contract-first, two-agent parallel workflow. The two agents should not begin wide implementation in independent branches until the foundation contracts have been merged. All phases require human review for security-sensitive and network-changing code.

## Phase 0 — Repository audit and safe baseline (human + Agent A)
**Objective:** know the exact current code/schema and protect existing functionality.

Tasks:
1. Clone exact repo/branch and record commit SHA.
2. Inventory `composer.json`, `package.json`, PHP/Laravel version, service providers, `bootstrap/app.php`, `routes/*`, config, middleware, auth scaffolding, tests, frontend build.
3. Inventory every migration and inspect actual schema with `php artisan migrate:status` and a sanitized schema dump. Do not run migration against production.
4. Map model/table/route/controller/view ownership and all existing modules.
5. Trace customer CRUD, partner CRUD, package, subscription, `TagihanController`, `BillingInvoiceRepository`, payment, and any mock JSON/session behavior end-to-end.
6. Identify DB syntax compatibility (PostgreSQL `ILIKE`, JSON operators, raw SQL); verify `.env.example` and CI service DB.
7. Baseline: `composer install`, `php artisan test`, Pint, `npm install`, `npm run build` in isolated environment.
8. Generate a current ERD from actual migrations; compare with target ERD.
9. Propose migration map: existing `mitras`, `paket_internet`, `langganans`, `tagihans`, `tagihan_details`, `pembayarans` (exact names must be verified) to canonical domain entities.
10. Identify any secrets in repo and report/rotate if ever committed; do not reproduce secrets.

Deliverables: `docs/REPO_AUDIT.md`, current ERD, route inventory, migration mapping, baseline test/build report, list of compatibility decisions.

Exit gate: no coding beyond isolated fixes until current billing path and schema mapping are approved.

## Phase 1 — Foundation and interface freeze (Agent A owner, Agent B reviewer)
**Objective:** define shared contracts before parallel coding.

Tasks:
1. Preserve current frontend and web auth; add API route registration consistent with Laravel 12 bootstrap.
2. Decide naming conventions and canonical `partner_id` vs existing `mitra_id`; do not dual-write indefinitely.
3. Decide whether `routes/api.php` uses Sanctum bearer tokens, stateful SPA or both. Use cookie/session+CSRF for first-party same-site browser as appropriate; bearer for mobile/external services.
4. Establish API response envelope, request ID middleware, pagination, validation conventions and OpenAPI.
5. Establish permission catalog, legacy role mapping, policy patterns and tenant context resolver.
6. Freeze DB register, interfaces/DTOs and event names: `SubscriptionActivated`, `SubscriptionSuspended`, `PaymentVerified`, `NetworkPolicySyncRequested`, `NetworkPolicySyncCompleted`, `AccountingSampleIngested`.
7. Define `NetworkAccessGateway` and `NetworkAnalyticsReader` interfaces plus fake adapters.
8. Create outbox table and integration event contract only once.
9. Establish CI checks: tests, static analysis, Pint, route contract/OpenAPI validation.

Deliverables: contract PR, API bootstrap, shared interfaces/DTOs, response/resource conventions, permission catalog, OpenAPI baseline.

Exit gate: Agent B confirms interface/schema contract; one merge commit is the base for both branches.

## Phase 2 — Business core (Agent A primary)
**Tasks:** identity/roles/policies; partner/user management; customer; package; subscription lifecycle; partner wholesale capacity contract and request flow; invoice canonicalization; line snapshots; payment intents/webhooks/reconciliation; public invoice token portal; tax administration; notification outbox; reports and audit logs.

Required acceptance:
- all partner records are tenant isolated;
- invoice generation idempotent and immutable;
- payment webhook signed, replay-protected and idempotent;
- no mock/session billing source remains authoritative;
- service transitions enqueue outbox only after DB commit;
- public invoice token cannot expose other records.

Agent B may build network adapter independently after Phase 1, but not create duplicate customer/subscription models or change core API contracts.

## Phase 3 — Network AAA and MikroTik integration (Agent B primary)
**Tasks:** FreeRADIUS lab, PostgreSQL SQL schema, NAS registration and secret management, PPPoE auth, speed profiles, customer/partner network assignment projection, outbox worker adapter, RouterOS API adapter, disconnect/restore, accounting Start/Interim/Stop ingestion, session normalization, counter delta processing, queue sample collection, partner aggregate cap proof, secondary RADIUS.

Required acceptance:
- all RADIUS behavior tested on actual agreed RouterOS version and lab topology;
- correct rate direction/units confirmed;
- local PPP Secret duplicate check exists;
- accounting duplicate/out-of-order/reset behavior tested;
- Partner aggregate bandwidth cap verified independently from per-customer speed;
- no real router credentials in repository or logs.

## Phase 4 — Analytics, integrations and hardening (shared)
- Materialize customer/partner daily usage summaries.
- Verify daily totals against synthetic accounting test vectors and lab counters.
- Payment and WhatsApp adapters with retry/dead-letter monitoring.
- AI assistant read-only retrieval with tenant filters and refusal to execute privileged network/payment actions.
- Load testing, backup restore, security review, penetration test of tenant/object endpoints, queue failure test, failover exercise.

## Phase 5 — Pilot, migration and rollout (human approval)
1. Snapshot/backup DB and router configurations.
2. Pilot with test NAS and controlled internal test customers.
3. Compare current customer list, packages, due dates, invoice totals and payments with imported records.
4. Assign globally unique PPPoE usernames; identify duplicate local PPP Secrets.
5. Run parallel accounting observation; compare volume results; define acceptable reconciliation tolerance.
6. Migrate limited group; retain rollback path; do not mass-disable legacy access until sign-off.
7. Gradually move partner customers; monitor RADIUS response, auth rejects, accounting lag, network cap and payment service lifecycle.
8. Expand in batches; maintain incident runbook and escalation owner.

## Suggested release gates
- G0: exact repository audit and tests known.
- G1: shared API/DB/permission contracts merged.
- G2: business MVP with fake network adapter passes.
- G3: network lab passes auth/accounting/rate/suspend/restore.
- G4: integrated pilot passes billing-to-network lifecycle.
- G5: security/backup/rollback acceptance approved.

## Change control
Any change to field names, endpoint payloads, outbox command, events, permissions or network policy DTO requires:
1. written change note;
2. contract update and version;
3. both agent owners review;
4. tests updated;
5. no unannounced schema edits across branches.
