# 05 — Permission & Tenant Matrix

## 1. Roles
Preserve existing role names/IDs during migration. Map legacy values into these capability profiles rather than assuming IDs. Recommended roles:
- `super_admin`
- `isp_admin`
- `network_operator`
- `billing_officer`
- `tax_officer`
- `support_agent`
- `mitra_admin`
- `mitra_billing`
- `mitra_operator`
- `auditor`

Existing roles such as `admin`, `mitra`, `cst_internal` must be mapped in a migration table and kept compatible until all user assignments are confirmed.

## 2. Permission matrix
Legend: R=read, C=create, U=update, D=delete/cancel, A=approve/review, X=execute privileged network action, O=own-tenant only.

| Domain/action | Super Admin | ISP Admin | Network Operator | Billing Officer | Tax Officer | Support | Mitra Admin | Mitra Billing | Mitra Operator | Auditor |
|---|---|---|---|---|---|---|---|---|---|---|
| User/role administration | R/C/U/D | R/C/U | - | - | - | - | O-R/C/U staff | O-R staff | - | R (audit scope) |
| All partner records | R/C/U/D | R/C/U | R | R | R | limited R | O-R/U | O-R | O-R | R |
| Partner approve/suspend | A/U | A/U | - | - | - | - | - | - | - | R |
| All customers | R/C/U | R/C/U | limited R | R | limited R | scoped R | O-R/C/U | O-R/U limited | O-R/C/U limited | R |
| Assign/transfer partner | X | X | - | - | - | - | - | - | - | R |
| Packages and central price | R/C/U/D | R/C/U | R | R | R | R | R allowed | R allowed | R allowed | R |
| Subscription status | X | X | network-related read | billing-related update | - | limited R | O-R/U allowed | O-R/U allowed | O-R/U allowed | R |
| Invoices | R | R | - | R/C/U/void | R | limited R | O-R | O-R/C/U | O-R | R |
| Payment and reconciliation | R | R | - | R/C/U/refund per approval | R | limited R | O-R | O-R/C/U where configured | O-R | R |
| Wholesale bandwidth contract | R/C/U/A | R/C/U/A | R | R | - | - | O-R/request | O-R/request | O-R | R |
| NAS/router configuration | R/C/U/D/X | R/C/U/X | R/U/X | - | - | - | - | - | - | R (redacted) |
| RADIUS credentials/policy | R/C/U/X | R/U/X | R/U/X | - | - | - | - | - | - | R metadata only |
| Session disconnect | X | X | X | - | - | - | - | - | - | R |
| Customer/partner usage | all R | all R | all R | aggregate R | aggregate R | scoped R | O-R | O-R | O-R limited | R |
| Tax categories and rules | R/C/U | R/U | - | R | R/C/U | - | - | - | - | R |
| Tax submissions | all R | all R | - | R | R/C/U/A | - | O-R/C/U/submit | O-R/C/U/submit | O-R | R |
| Public invoice link | all manage | all manage | - | R/C/revoke | - | limited create | O-R/C/revoke | O-R/C/revoke | - | R metadata |
| WhatsApp templates/integration | R/C/U/X | R/U/X | - | X templates | - | send approved template | O-send approved | O-send approved | O-send approved | R |
| AI assistant | R | R | network read only | billing read only | tax read only | scoped read | O read | O read | O read | R read |
| Audit logs | all R | central R | network R | billing R | tax R | own/support R | own tenant limited | own tenant limited | own tenant limited | R read-only |

## 3. Permission naming convention
Use `resource.action`, examples:
`customer.view`, `customer.create`, `customer.update`, `customer.transfer`, `subscription.activate`, `invoice.generate`, `invoice.void`, `payment.reconcile`, `payment.refund`, `network.device.manage`, `network.session.disconnect`, `network.profile.manage`, `network.sync.retry`, `tax.submission.review`, `audit.view`.

Keep permissions granular. Avoid a broad `admin` boolean in controllers.

## 4. Mandatory tenant enforcement
- Central roles can query all partners according to permissions.
- A partner user must have `users.partner_id` (or canonical legacy `mitra_id`) resolved from server-side auth context.
- Partner list queries apply a global Eloquent scope or explicit repository scope. Policies must re-check resource ownership for show/update/delete/action routes.
- Never trust route/body/query `partner_id` or `mitra_id` for deciding authorization.
- For resources without direct `partner_id`, traverse and verify ownership through customer/subscription/invoice relationship.
- Cross-tenant object access should return 404 (or consistent non-disclosing behavior), and create/update should reject foreign related IDs.
- Exports, background jobs, notifications and AI retrieval must carry tenant scope explicitly and re-check at execution time.
- Partner roles must have zero network write permission. Any partner request for speed/capacity is a request object routed to central approval.

## 5. Sensitive fields
- PPPoE password, router shared secret, API secret, webhook signing secret: never list/read/export; secret can be replaced or rotated but not retrieved.
- Customer PII: support role gets minimum fields; auditor sees masked fields unless purpose demands otherwise.
- Public invoice view: only whitelist fields. Never serialize Eloquent model wholesale.
- Tax documents: access scoped to partner submission and tax officer/authorized central reviewer.

## 6. Policy test cases
1. Mitra A GET `/customers/{id_of_mitra_B}` => 404/denied.
2. Mitra A PATCH its customer with `partner_id=mitra_B` => 422/403; no ownership change.
3. Mitra A GET `/invoices` => only own invoices.
4. Mitra A export request with arbitrary partner ID => ignored/rejected; still own tenant only.
5. Mitra admin cannot call any `/network/*` write endpoint.
6. Support agent cannot read PPPoE password or router credentials.
7. Tax officer can review partner submission but cannot change NAS/policy.
8. Auditor cannot mutate anything.
9. Background job for Mitra A cannot process payload containing Mitra B ID unless central privileged actor and explicit approved transfer flow.
10. A revoked public invoice token stops working immediately.
