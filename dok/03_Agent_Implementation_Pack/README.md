# KikiNet — Agent Implementation Pack

**Baseline:** repository `https://github.com/troopsmalay-source/KiyNet.git`  
**Spec version:** 1.0.0  
**Date:** 13 November 2026  
**Target:** two independent coding agents with human review and controlled integration.

## What this pack is
This is an implementation specification, not a claim that the repository has already been fully audited or that the network design has been tested against production routers. It defines the target product, domain boundaries, database target, API contract, roles, tests, operational rules, and task split for two agents.

## Locked product decisions
- ISP central owns and operates all MikroTik PPPoE gateways/NAS devices. Partners do not configure routers, RADIUS, pools, queues, or firewall.
- Initial access method is PPPoE. Other access methods must be extensible but are out of MVP scope.
- FreeRADIUS is the AAA server; do not implement RADIUS protocol/server from scratch.
- Full session accounting and bandwidth analytics per customer and per partner.
- PostgreSQL remains the primary business database to preserve the existing `.env.example` and PostgreSQL-specific query usage found in the repository. FreeRADIUS SQL integration must use a separately privileged database/schema/role.
- Laravel remains a modular monolith. Avoid premature microservices.
- Public customer invoice page has no user login; access uses a random high-entropy token, stored as a hash, with expiry/revocation/rate limits.
- Finance remains deliberately limited to operational invoices, payments, partner settlement/commission if in scope, and tax administration/reporting. This is not a full accounting/ERP or tax filing engine.
- Tax records support configurable PPh 21, PPN and annual Komdigi-related obligations. The exact legal obligation type and formulas must be approved by the client's tax/legal adviser before production use.
- No ODC/ODP/OLT inventory or general network inventory module.

## Repository baseline observed
The publicly visible files show Laravel 12, PHP ^8.2, Vite, Tailwind, Alpine.js, Axios and DataTables. The `.env.example` uses PostgreSQL. Existing web routes and controllers already sketch customers, partners, internet packages, subscriptions, invoices and billing. `User` already has `role_id`, `mitra_id`, and role helpers. A database-backed invoice flow exists beside a mock JSON/session billing repository. Some customer controller logic scopes by `mitra_id`, but authorization is not yet consistently centralized.

**Audit limitation:** this assessment is based on public files/pages available during preparation, not a full local clone, complete migrations/schema dump, test run, or production environment. Phase 0 MUST clone the exact target branch and inspect every migration, model, route, policy, test, database driver, and existing screen before coding. Never infer uninspected columns.

## Document index
1. `docs/01-SRS.md` — Software Requirements Specification and acceptance criteria.
2. `docs/02-ARCHITECTURE.md` — runtime architecture, deployment, integration boundaries and failure behavior.
3. `docs/03-DATABASE-ERD.md` — entity model, ownership, key fields, constraints and accounting strategy.
4. `docs/04-API-CONTRACT.md` — API conventions, endpoints, idempotency, webhooks and error model.
5. `docs/05-PERMISSION-MATRIX.md` — roles, permissions, tenant and ownership enforcement.
6. `docs/06-IMPLEMENTATION-ROADMAP.md` — phases, dependencies, sequencing and Definition of Done.
7. `docs/07-TEST-AND-OPERATIONS.md` — tests, observability, security, backup and runbooks.
8. `docs/08-AGENT-WORK-SPLIT.md` — ownership, merge rules, shared contracts and conflict resolution.
9. `docs/agent-prompts/AGENT-A-BUSINESS.md` — copy-paste assignment for Agent A.
10. `docs/agent-prompts/AGENT-B-NETWORK.md` — copy-paste assignment for Agent B.
11. `contracts/openapi.yaml` — starting OpenAPI 3.1 API definition. Extend it only through reviewed contract changes.
12. `database/SCHEMA-REGISTER.md` — canonical table inventory and ownership.
13. `infra/radius/README.md` and `infra/mikrotik/README.md` — network integration requirements.
14. `diagrams/` — Mermaid architecture/sequence diagrams.

## How to start the two agents safely
1. Human creates a clean working branch and executes Phase 0 repository audit.
2. Agent A owns the shared application foundation and business domain. Agent B is blocked from editing shared files until the interface/contract PR has been merged.
3. Merge the foundation contract first: database schema register, service interfaces/DTOs, response envelope, API route bootstrap and OpenAPI baseline.
4. Start Agent A and Agent B in separate branches/worktrees from the same merged commit.
5. Agent A owns business/billing/tenant/portal/tax modules. Agent B owns network/RADIUS/MikroTik/accounting/analytics modules.
6. Integrate only through the interfaces and events defined in the spec. Never have both agents edit the same migration, model, route bootstrap or service contract.
7. Human runs the complete test suite, migration review, static analysis and staging network tests before deployment.

## Non-negotiable safety rules
- Never run `migrate:fresh`, `db:wipe`, truncate, destructive reset or a production migration from an AI agent.
- Migrations must be additive and reversible where possible. Destructive cleanup requires a separate reviewed migration and backup.
- No production MikroTik API actions from local/dev/test agents. Use fakes or a lab router.
- Do not store router shared secrets or PPPoE passwords in plaintext logs, prompts, commits or responses.
- Never expose RADIUS ports or RouterOS API publicly; use private networking/VPN, firewall allowlists and unique secrets.
- Never accept `mitra_id` from request as authority. Resolve tenant scope from authenticated user and server-side relationships.
- Never treat RADIUS token abilities or role labels alone as row-level authorization. Apply Policies/scopes to every resource.
- Do not calculate daily usage by summing repeated cumulative `radacct` counters. Persist deduplicated accounting samples and derive deltas.
- Do not invent tax formulas or classify annual Komdigi obligations without formal client/tax-adviser confirmation.

## Baseline commands (verify against exact repo)
```bash
composer install
cp .env.example .env
php artisan key:generate
# configure PostgreSQL connection before migrating
php artisan migrate:status
php artisan test
npm install
npm run build
```
Do not run an initial migration until Phase 0 confirms the existing database is disposable/local and the migration plan is reviewed.
