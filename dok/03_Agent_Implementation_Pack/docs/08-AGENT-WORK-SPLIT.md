# 08 — Two-Agent Work Split & Merge Governance

## 1. Principle
Two agents work in separate branches/worktrees. Agent A owns shared app foundations and business source of truth. Agent B owns network adapter, FreeRADIUS projection, MikroTik and accounting. Both must work from a shared interface freeze commit. Human owner arbitrates scope and merges.

## 2. Agent A — Business & Platform Core
**Owns:**
- Identity, legacy role mapping, permission catalog, Policies, partner tenant resolver.
- API bootstrap, response envelope, request ID middleware, OpenAPI baseline and shared request/response DTO conventions.
- Partner, customer, package, subscription, wholesale contract.
- Canonical DB-backed invoices, invoice items, payment events/reconciliation/refund records.
- Public invoice token portal.
- Tax administration and partner submission/review.
- Notification outbox and WhatsApp/payment provider abstractions.
- Audit logs, reports that depend on business tables, frontend business UI.

**May create/edit:** `app/Domain/Identity`, `Partner`, `Customer`, `Catalog`, `Subscription`, `Billing`, `Tax`, `Portal`, `Notification`, corresponding migrations/models/tests/controllers, shared route/bootstrap only during Phase 1, OpenAPI shared schemas.

**Must not:** implement RADIUS daemon, write RouterOS configuration commands, create network-specific duplicate customer/subscription tables, set partner queue policies, retrieve router secrets.

## 3. Agent B — Network, RADIUS & Accounting
**Owns after contract freeze:**
- FreeRADIUS lab configuration, SQL schema integration, NAS allowlist/secret reference plan.
- `NetworkAccessGateway` concrete adapters: RADIUS policy projection and RouterOS API actions.
- Network profile and assignment tables (as owned by network context), network outbox consumer, policy versioning and network audit.
- Session normalization, accounting sample dedupe/deltas, customer/partner usage aggregation.
- Router queue/throughput sampling and aggregate partner cap mechanism.
- RADIUS primary/secondary and failover testing/runbooks.
- Network-specific endpoints under `routes/api/v1/network.php` and network UI widgets/dashboards.

**May create/edit:** `app/Domain/Network`, `app/Domain/Accounting`, `infra/radius`, `infra/mikrotik`, corresponding network migrations/models/tests, `routes/api/v1/network.php`, network-specific OpenAPI path additions via reviewed PR.

**Must not:** edit `User`, `Role`, `Permission`, `Customer`, `Subscription`, `Invoice`, `Payment` models/migrations; edit `bootstrap/app.php`, shared API response middleware, shared service interfaces without approval; change business status rules or partner assignment; add partner router controls.

## 4. Shared interface contract (freeze in Phase 1)
### `NetworkAccessGateway`
- `provision(NetworkAccessCommand $command): NetworkCommandResult`
- `suspend(NetworkAccessCommand $command): NetworkCommandResult`
- `restore(NetworkAccessCommand $command): NetworkCommandResult`
- `changeProfile(NetworkAccessCommand $command): NetworkCommandResult`
- `disconnect(NetworkDisconnectCommand $command): NetworkCommandResult`
- `getStatus(NetworkAccessQuery $query): NetworkAccessStatus`

Command contains internal subscription/customer/partner IDs, username (not password), NAS/pool reference where approved, profile code/version, target desired state, correlation ID, idempotency key. Password material must use an isolated credential enrollment path and secret reference; never include plaintext in general event payloads.

### `NetworkAnalyticsReader`
- `customerUsage(CustomerUsageQuery $query): UsageSummary`
- `partnerUsage(PartnerUsageQuery $query): UsageSummary`
- `activeSessions(SessionQuery $query): PaginatedSessions`
- `throughputSeries(ThroughputQuery $query): ThroughputSeries`

All read methods require an explicit trusted scope object (central or one partner ID) created server-side. Do not accept untrusted tenant scope from HTTP input.

## 5. File ownership map
| Shared area | Owner | Other agent rule |
|---|---|---|
| composer.json / package.json | A | B requests dependency by PR |
| bootstrap/app.php | A | B cannot edit |
| routes/api.php / route bootstrap | A | B only adds its included network route file |
| API envelope / middleware / errors | A | B consumes, proposes changes |
| OpenAPI shared components | A | B adds network paths via PR after freeze |
| User/Role/Permission/Customer/Subscription/Invoice/Payment | A | B read-only through contracts |
| Network schema/models/adapters | B | A consumes interface only |
| Network API path file | B | A registers include during foundation |
| Shared outbox schema | A creates baseline; B may propose additive fields via contract PR | no divergent migration |
| Test fixtures/shared factories | respective owner; shared fixture factory owner A | no conflicting edits |

## 6. Branch and PR strategy
1. `main` is protected; no direct agent commits.
2. `feature/foundation-contracts` by Agent A; Agent B review only. Merge before parallel work.
3. Branch `feature/business-core` for Agent A.
4. Branch `feature/network-radius-accounting` for Agent B from exact merged foundation commit.
5. Use separate worktrees/agent workspaces to avoid simultaneous filesystem edits.
6. PR title identifies domain + ticket, e.g. `[BUSINESS] Canonical invoice persistence`, `[NETWORK] Interim accounting dedupe`.
7. Each PR describes migrations, endpoints, permission impact, tests, operational effects, config/env additions, rollback plan.
8. Do not merge a PR while another agent's dependency contract is changing. Rebase only after contract owner approves.
9. Human merges sequentially after tests; resolve conflicts by file owner, not whichever agent finishes first.

## 7. Daily coordination artifact
Maintain `docs/agent-coordination.md` with:
- current shared contract commit SHA;
- files owned/modified by each agent;
- endpoints/schema changed;
- blocked assumptions;
- pending decisions;
- tests run and results;
- PR references.

## 8. Conflict resolution
1. Interface/API/schema conflict => freeze related work; Agent A owns business contract; Agent B owns network implementation details.
2. Tenant ownership or payment/billing conflict => Agent A proposes; human approves.
3. RouterOS/FreeRADIUS behavior conflict => Agent B proposes based on lab evidence; human network owner approves.
4. Performance/capacity conflict => produce measured data; no guesswork.
5. If both agents edited shared file, revert one branch and port changes via owner-approved PR. Do not hand-resolve broad generated diffs blindly.

## 9. Definition of Done for each agent
- code follows existing conventions after Phase 0;
- migration additive, indexed, reversible where possible and reviewed;
- authorization and tenant tests included;
- unit/feature/integration tests pass;
- OpenAPI updated for endpoint changes;
- no credentials/secrets in code/logs;
- queue retry, idempotency and failure behavior defined;
- UI handles loading/empty/error/permission denied;
- manual QA steps and deployment/env changes documented;
- PR does not modify files owned by other agent without explicit contract approval.
