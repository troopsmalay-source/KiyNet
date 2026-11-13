# 01 — Software Requirements Specification (SRS)

**Product:** KikiNet ISP Billing & Partner Management  
**Version:** 1.0.0  
**Status:** Target specification for implementation discovery and backlog grooming.

## 1. Purpose and scope
KikiNet is a multi-tenant operational platform for a central ISP and its downstream marketing/reseller partners. It manages customers, internet packages, subscriptions, invoices, payments, partner wholesale bandwidth contracts, central MikroTik PPPoE access, FreeRADIUS AAA, network accounting, bandwidth usage analytics, public invoice views, WhatsApp notifications, payment gateway events, AI-assisted support, and administrative tax records.

KikiNet is a **central ISP control plane**, not a customer-owned router manager. All partner customers authenticate through MikroTik gateways operated by the ISP central team. The partner is allowed to manage only its assigned customers and commercial workflow.

## 2. Goals
- One trusted source for customer, subscription, package, billing and partner ownership data.
- Automatically enable/suspend/restore PPPoE service when the business state changes.
- Enforce a per-customer speed profile and a separate aggregate capacity ceiling per partner.
- Provide accurate, auditable per-customer and per-partner traffic/session reporting.
- Keep partner financial/tax submissions measurable and reviewable by ISP central.
- Provide a no-login public invoice view without exposing customer records or internal APIs.
- Allow future access methods without redesigning the whole business domain.

## 3. Non-goals
- Full ERP, general ledger, inventory, procurement or payroll engine.
- ODC/ODP/OLT/ODN or general fiber network inventory/GIS.
- Partner-owned gateway/router control, partner RADIUS administration or partner firewall configuration.
- Implementing a new RADIUS server/protocol.
- Packet inspection, browsing history, DPI or per-application content analytics.
- Automatically filing tax returns to DJP/Coretax or Komdigi portals in MVP.
- Guaranteeing bandwidth availability beyond the service/wholesale contract and measured network capability.

## 4. Actors
- **Super Admin:** platform owner, unrestricted administration with audited privileged actions.
- **ISP Admin:** central ISP operations and business oversight.
- **Network Operator:** central-only router, RADIUS, session and queue operations.
- **Billing Officer:** central invoice/payment/reconciliation operations.
- **Tax Officer:** central tax rules, submissions and review.
- **Support Agent:** customer support with limited PII and no secret/network write access.
- **Mitra Admin:** partner organization admin; manages own staff and permitted partner data.
- **Mitra Billing:** partner customer invoices, payment recording where authorized, own reports and tax submission.
- **Mitra Operator:** customer/subscription operations, but no MikroTik/RADIUS/queue configuration.
- **Auditor:** read-only access to authorized reports and audit trail.
- **Public Invoice Viewer:** anonymous visitor with a valid, unexpired invoice access token.
- **System Integrations:** payment provider, WhatsApp provider, FreeRADIUS, MikroTik and AI provider.

## 5. Business rules
### 5.1 Ownership and tenancy
- A central customer has `partner_id = null`.
- A partner-owned customer has exactly one `partner_id`.
- Partner identity is derived from the authenticated user's `mitra_id`, not request data.
- Partner may never view, mutate, export or infer another partner's customer, invoice, payment, usage or tax submission.
- Partner cannot assign/reassign customers between partners. Only authorized central users may transfer ownership, with audit record and effective date.
- Historical invoice/payment/usage ownership should be snapshotted where needed to avoid old reports changing after transfer.

### 5.2 Customer, package and subscription
- A customer may have many historical subscriptions, but only one current active internet subscription per service unless a separately approved product model permits multiple.
- Subscription lifecycle: `draft`, `pending`, `active`, `grace`, `isolated`, `suspended`, `terminated`, `cancelled`.
- Business subscription status is authoritative. RADIUS policy is a projection of that state.
- Package stores customer speed (`download_mbps`, `upload_mbps`), price, activation rules, tax category and active status.
- A package change creates an auditable subscription change/policy version. Existing invoice history is immutable.
- PPPoE username is unique globally across all NAS and all partners.

### 5.3 Partner wholesale capacity
- Each partner has an effective-dated bandwidth contract: committed/maximum capacity if contractually agreed, billing cycle, price, status and purchase terms.
- Customer individual speed profile and partner aggregate cap are two distinct controls.
- Sum of advertised customer package ceilings may exceed partner aggregate cap (oversubscription) only if explicitly permitted in contract and visible to central admin.
- Partner cannot raise its capacity or change queue policy by changing customer package values.
- Capacity changes must go through central approval and a versioned network policy update.

### 5.4 Billing and payment
- An invoice is generated from a subscription and billing period; invoice number is unique and immutable.
- Invoice line items snapshot name, quantity, unit amount, discount, tax basis, tax amount and total at issue time.
- Monetary database values use integer IDR (rupiah) or explicit decimal minor units; avoid float.
- Payment gateway webhooks are signature-verified, replay-protected and idempotent by provider event ID.
- `paid` is only set after verified successful payment. Browser redirect is never authoritative.
- Partial payments, refunds and adjustments must be explicit ledger/payment records; do not mutate historical payment amounts.
- Payment and service activation must be coordinated via transactional outbox, not one long transaction spanning external network/API calls.

### 5.5 Overdue/suspension
- Define due date, grace period and suspension policy centrally.
- Suspension worker atomically changes business state and creates an outbox command. Network adapter then rejects new PPPoE authentication and disconnects active session where configured.
- Restoration follows verified payment, active subscription and successful policy synchronization.
- Walled garden/captive portal is not automatic in RADIUS; MVP may use reject/disconnect. If walled garden is later implemented, it requires a separate firewall/routing spec.
- Failed network action must remain visible as `sync_failed`/retry state, not pretend that service was isolated/restored.

### 5.6 RADIUS/PPPoE
- FreeRADIUS is the AAA server; RouterOS NAS requests authentication and sends accounting.
- Primary and secondary RADIUS nodes should be supported.
- Use PostgreSQL SQL module and a separate `kikinet_radius` database/schema + least-privilege DB role. Confirm supported FreeRADIUS version and exact vendor attributes in lab.
- Local PPP Secret records on MikroTik can take precedence over RADIUS for matching usernames; migration checks for duplicates are mandatory.
- Password scheme must be tested with actual PPPoE auth method. Bcrypt hashes cannot simply be assumed to support CHAP/MS-CHAP. Keep secrets protected and never log them.
- RADIUS policy must carry explicit partner/customer identity and policy version through a trusted mapping; do not infer ownership from a username prefix.

### 5.7 Accounting and analytics
- Store standard RADIUS accounting records (`radacct`) and a separate normalized, deduplicated sample/event store.
- Convert counters to 64-bit: `gigawords * 2^32 + octets`.
- Each Interim-Update is a cumulative snapshot. Per-interval traffic is the delta from previous valid sample. Handle duplicate, stale, out-of-order, counter reset, missing Stop and session restart.
- Use unique `(nas_id, acct_session_id, sample_timestamp, event_type/provider_event_key)` or equivalent deduplication key.
- Daily/monthly usage reports are derived from deltas and session boundaries, not sum of cumulative snapshots.
- Partner usage is a rollup of only its customers' data, cross-checked with aggregate RouterOS queue samples; do not double-count RADIUS customer deltas and aggregate queue counters.
- Distinguish volume (bytes/GB during a period) from throughput (Mbps at a sample/time). Display sample time and sampling interval.
- Accounting is not DPI and does not report visited websites/apps.

### 5.8 Tax administration
- Support configurable tax records/categories for PPh 21, PPN and annual Komdigi-related obligation.
- Allow tax period, calculation basis, configurable rate/reference, gross/net/tax amounts, withholding/deposit references, document attachments, submission status, reviewer and audit history.
- Do not hard-code legal rates or annual obligation formula until confirmed by tax/legal adviser.
- Annual Komdigi classification must be resolved (e.g. BHP Telekomunikasi, USO, PNBP or another applicable obligation). The app records/reports it; no official e-filing claim.

### 5.9 Public invoice portal
- No customer account/login.
- Use 256-bit random or equivalent high-entropy token in URL; store only SHA-256/HMAC hash, not raw token.
- Token is invoice-specific, revocable, expiry-controlled, rate-limited and not enumerable.
- Display only limited data: invoice number, masked customer name, period, amount, due date, status, approved payment instructions and payment CTA.
- Do not include PPPoE username/password, address, phone, network usage or partner internal notes.
- Robots noindex, no third-party trackers on token page, no token in analytics/referrer logs if avoidable; set restrictive Referrer-Policy.

## 6. Functional requirements
Priority values: P0 = must for pilot; P1 = important near-term; P2 = later.

| ID | Requirement | Priority | Acceptance condition |
|---|---|---:|---|
| FR-001 | User authentication and central/partner roles | P0 | Login, logout, inactive account block, permission tests pass |
| FR-002 | Tenant-safe partner access | P0 | Partner A cannot read/update/export Partner B records; negative tests pass |
| FR-003 | Customer CRUD and search | P0 | Central and partner scopes enforced server-side |
| FR-004 | Partner profile and staff | P0 | Partner admin manages only own permitted staff |
| FR-005 | Internet package management | P0 | Central creates packages; partner may select only assigned/allowed packages |
| FR-006 | Customer subscription lifecycle | P0 | Effective dates/status and PPPoE credential linkage are audited |
| FR-007 | Partner bandwidth contract | P0 | Central defines cap and effective period; partner cannot self-increase |
| FR-008 | Invoice generation | P0 | Unique immutable invoice snapshot and idempotent generation |
| FR-009 | Payment records and webhook | P0 | Signature/replay/idempotency and reconciliation tested |
| FR-010 | Suspend/restore automation | P0 | Business event creates network outbox; failed jobs visible/retriable |
| FR-011 | PPPoE RADIUS authentication | P0 for network pilot | Enabled subscriber accepted; disabled/bad credentials rejected in lab |
| FR-012 | Accounting Start/Interim/Stop | P0 for network pilot | Sessions and deduped delta samples recorded correctly |
| FR-013 | Per-customer speed profile | P0 | Correct vendor attribute / MikroTik policy confirmed in lab |
| FR-014 | Partner aggregate bandwidth cap | P0 for commercial launch | Lab test verifies aggregate cap and no cross-partner leak |
| FR-015 | Customer and partner usage reports | P0 | Byte totals and throughput clearly differentiated and reconciled |
| FR-016 | Public invoice portal | P0 | Valid token sees limited invoice; expired/revoked/guessed token denied |
| FR-017 | WhatsApp notifications | P1 | Template, queue retry, provider status and opt-out rules implemented |
| FR-018 | Payment gateway integration | P0 | Provider adapter and signed webhook validated |
| FR-019 | Tax administration/submission | P1 | Periodic report, documents, central review, audit history |
| FR-020 | AI support assistant | P2 | Read-only, scoped, cited internal knowledge; no autonomous payment/network writes |
| FR-021 | Router health/basic network operations | P1 | Central-only device status and approved health metrics; no ODC/ODP module |
| FR-022 | Audit logs | P0 | Sensitive actions attributed to user/system with before/after metadata |
| FR-023 | CSV/XLSX exports | P1 | Exports respect tenant scope and permissions; sensitive export audited |
| FR-024 | Secondary RADIUS failover | P1 | Lab verifies fallback auth/accounting and policy synchronization |

## 7. Non-functional requirements
- Security: least privilege, MFA-ready for privileged users, CSRF protection for web, request validation, output escaping, rate limit, encrypted secrets, audit trail.
- Tenant isolation: all partner queries scoped at query/service/policy layer; do not rely only on UI filtering.
- Reliability: queued network actions with retry/backoff, dead-letter visibility, idempotent command processing.
- Performance: paginated tables; indexed tenant/date/status columns; summary tables for analytics; avoid live full-table aggregation on dashboard.
- Scalability: modular monolith and separable RADIUS/NAS nodes. Plan for queue and DB tuning based on measured concurrency.
- Observability: structured logs, correlation IDs, queue failure alerts, RADIUS/NAS health checks.
- Maintainability: service/domain boundaries, request DTOs/form requests, policies, tests, OpenAPI.
- Accessibility/UX: responsive role-specific interface, clear status labels, Indonesian locale, IDR and Asia/Jakarta display.
- Privacy: minimize customer PII; restrict public invoice data; configurable retention for network accounting subject to legal/contract obligations.

## 8. MVP release gates
1. Full code/migration audit complete and baseline tests pass.
2. One canonical database-backed billing domain selected; mock/session invoice path disabled or adapted.
3. Tenant isolation and permission negative tests pass.
4. RADIUS lab: accept/reject, speed profile, interim accounting, disconnect/restore, failover.
5. Partner aggregate cap lab-tested with actual topology and RouterOS version.
6. Payment webhook idempotency/replay tests pass.
7. Backup/restore rehearsal passes.
8. Pilot customer migration reconciled and rollback plan approved.
