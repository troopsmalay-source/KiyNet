# 07 — Test Strategy, Security & Operations

## 1. Test pyramid
- Unit tests: calculators, policy mapping, counter delta, billing period, tax report aggregation, permission decisions.
- Feature tests: controller/API request-validation, tenant boundaries, invoice lifecycle, public token, webhook.
- Contract tests: OpenAPI schema vs actual endpoint responses; interfaces vs fakes; event/outbox payload compatibility.
- Integration tests: PostgreSQL, Redis, payment sandbox, WhatsApp sandbox, FreeRADIUS lab, MikroTik lab.
- End-to-end tests: customer signup/activation -> invoice -> payment -> service restored; overdue -> isolate; partner views and reports.
- Load/chaos: concurrent PPPoE auth, accounting burst, RADIUS secondary failover, queue outage, NAS API timeout, DB reconnect.

## 2. Critical test vectors
### Tenant
- Mitra A cannot retrieve Mitra B by ID through index, show, update, export, relationship dropdown, nested resource or AI assistant.
- User without partner scope cannot access partner route.
- Partner cannot update `partner_id`, role, permission, invoice paid state, network profile, router ID or credential.

### Billing/payment
- Same invoice period generated twice => one invoice.
- Same Idempotency-Key + same payload => same result.
- Same key + changed payload => 409.
- Fake browser success redirect without provider webhook/query verification => still pending.
- Duplicate webhook event => only one state transition/payment event processing.
- Invalid signature/replay timestamp => rejected and audited.
- Partial/refund does not mutate original successful payment record.

### RADIUS/Accounting
- Known active credential => Access-Accept with expected policy.
- Unknown, wrong password, isolated, terminated => Access-Reject or approved isolation behavior.
- Correct NAS source/secret accepted; wrong source/secret rejected.
- Accounting Start creates session; duplicate start is idempotent.
- Interim sample delta = current cumulative - prior cumulative.
- Duplicate Interim does not add bytes twice.
- Out-of-order sample does not corrupt totals; mark/recompute interval.
- 32-bit wrap uses Gigawords in 64-bit counter.
- Counter reset/reboot is flagged; negative delta never silently counted.
- Missing Stop is stale-reconciled and marked estimated/incomplete.
- RouterOS local PPP Secret collision is detected before migration.

### Bandwidth
- Individual customer speed correct for upload and download direction.
- Partner aggregate cap enforced irrespective of sum of individual profile rates.
- Partner A traffic cannot enter Partner B aggregate queue.
- Queue placement, PCQ, FastTrack and hardware capacity tested on target RouterOS/hardware.
- Displayed throughput carries sample time and sampling interval.

### Public portal
- Expired/revoked/malformed token denied.
- Token not enumerable; brute-force rate limit active.
- Response contains only allowlisted invoice fields.
- No PPPoE data, phone/address, PII extras, internal note, partner financial details.

## 3. Security controls
- Laravel Policies/Form Requests for every mutating resource.
- Tenant scope at query/service plus policy; never depend only on hidden form fields or middleware role name.
- Password hashing for app users; appropriate protected credential format for PPPoE protocol and lab-verified auth method.
- Secrets in environment/secret manager; rotate any leaked secret; no plaintext values in logs or DB metadata.
- Rate limits on login, public invoice, token issue, webhook, assistant and test-send.
- Verify webhook signature over raw body; use replay protection and unique event IDs.
- Restrict file uploads by size/type/content and malware scan where possible.
- Validate outbound URLs and prevent SSRF through AI/webhook integrations.
- AI tools default read-only, tenant-bound and permission-aware; no direct `disconnect`, refund, delete or payment mutation tool.
- Audit privileged changes, exports, token generation, tax review, policy update and manual reconciliation.

## 4. Observability
Recommended metrics:
- API latency/error rate by route and request ID.
- Queue pending/failed/dead-letter count and oldest job age.
- Outbox command pending count, age, retry count and failures by command type.
- FreeRADIUS auth accept/reject, response latency, SQL failures, accounting event rate/lag.
- NAS reachability, CPU/memory/interface status, active PPP sessions, RouterOS API failure.
- Accounting samples duplicate/out-of-order/reset count; stale sessions; reconciliation gap.
- Per-partner current throughput, cap utilization and cap enforcement errors.
- Payment webhook signature errors, event lag and pending payment age.

Do not expose passwords, shared secrets, raw tokens, complete payment data or unnecessary PII in metrics/log labels.

## 5. Backup and recovery
- Encrypted daily PostgreSQL backup; retention policy agreed with client.
- WAL/PITR if RPO requires it; document actual RPO/RTO target after infrastructure discovery.
- Store backups outside primary VPS/account; test restore, not merely backup creation.
- Export RouterOS configs securely and monitor backup age.
- Back up FreeRADIUS config and SQL schema/policy state.
- Document credential rotation and RADIUS shared-secret rotation without disrupting all NAS at once.

## 6. Runbooks required before production
1. RADIUS primary down / secondary takeover.
2. Both RADIUS nodes unavailable.
3. NAS unreachable / RouterOS API error.
4. Customer unexpectedly rejected by RADIUS.
5. Payment received but network still isolated.
6. Accounting gap or incorrect usage counter.
7. Partner cap not enforcing / overrun.
8. Queue worker dead-letter and replay.
9. Database restore / point-in-time recovery.
10. Lost or leaked shared secret / API credential.

## 7. Release and migration controls
- CI: tests, Pint, static analysis if configured, frontend build, OpenAPI lint/validation.
- Staging: same PostgreSQL major version, FreeRADIUS major version, RouterOS version and queue/firewall pattern as target production.
- Schema change requires reviewed migration, backup and rollback. No destructive migration from an agent.
- Network writes disabled by default in local/dev/CI. Explicit `NETWORK_DRIVER=fake|radius` or equivalent safe config.
- Feature flags for RADIUS authentication, payment provider live mode, automatic isolation and partner queue enforcement.
- Pilot signoff needs business, finance/tax and network owner.
