# Canonical Schema Register (target)

This register defines table ownership. Exact names must be reconciled with existing migrations during Phase 0; do not blindly create duplicate tables.

| Bounded context | Tables (target) | Owner agent |
|---|---|---|
| Identity | users, roles, permissions, role_permissions, user_permissions | A (foundation) |
| Partner | partners/mitras, partner_users (only if multi-membership), partner_bandwidth_contracts, partner_bandwidth_contract_events, partner_package_offers | A |
| Customer | customers, customer_contacts | A |
| Catalog | internet_packages/paket_internet | A |
| Subscription | subscriptions/langganans, subscription_events, subscription_changes | A |
| Billing | invoices/tagihans (canonical mapping), invoice_items/tagihan_details, payments/pembayarans, payment_events, payment_refunds, payment_adjustments | A |
| Public invoice | invoice_access_tokens | A |
| Tax | tax_categories, tax_periods, tax_submissions, tax_submission_items, tax_documents | A |
| Network projection | nas_devices, network_access_profiles, network_access_assignments, radius_sync_outbox, network_policy_audits | B (after interface freeze) |
| Network accounting | radius_sessions, radius_accounting_samples, customer_usage_daily, partner_usage_daily, router_queue_samples | B |
| RADIUS native schema | radcheck, radreply, radgroupcheck, radgroupreply, radusergroup, radacct, radpostauth (version/schema-dependent) | B / FreeRADIUS install |
| Notifications | notification_outbox, notification_attempts | A (foundation) |
| Integration | integration_webhook_events | A (foundation) |
| Audit | audit_logs | A (foundation) |

**Ownership rule:** only the owner agent may edit migrations/models for their bounded context. Shared identity/contract migrations are authored once by Agent A in foundation phase and merged before parallel branches start. Agent B must request a contract change rather than editing shared tables independently.
