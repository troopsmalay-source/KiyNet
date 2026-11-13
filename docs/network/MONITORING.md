# Network monitoring & alerts (proposal)

Metrics below are proposals; thresholds must be tuned after a lab/pilot baseline.
No metric may contain a password, shared secret, raw token or unnecessary PII.

## Application (Laravel)

| Metric | Source | Alert |
|---|---|---|
| Outbox pending count / oldest age | `radius_sync_outbox` (`status`, `available_at`) | warn > 20 pending or > 10 min; critical on stage commands > 15 min |
| Outbox dead-letter count | `radius_sync_outbox.status = dead_letter` | critical ≥ 1 |
| Outbox attempts distribution | `attempts` | warn when a command reaches 50 % of max attempts |
| Stale processing locks | reclaimed count per minute | warn if > 0 repeatedly |
| Accounting ingest rate | `radius_accounting_samples` per minute | warn on 50 % drop vs same hour yesterday |
| Duplicate / out-of-order / reset samples | sample flags | warn on any sustained spike; critical on reset burst (NAS reboot loop) |
| Stale sessions | `radius_sessions.status = stale` | warn > 0 for > 2 h |
| Usage completeness | `customer_usage_daily.completeness` | warn > 2 % rows `estimated`, critical any `gap` for a billed day |
| Network API latency/error rate | route metrics | warn p95 > 800 ms, 5xx > 1 % |

## RADIUS (AAAH)

| Metric | Alert |
|---|---|
| Access-Accept / Access-Reject ratio per NAS | reject > 20 % for 5 min → page network on-call |
| Auth response latency | warn > 300 ms p95, critical > 1 s |
| SQL errors / pool exhaustion | critical |
| Accounting packets received per NAS | silence > 2× interim interval → warn; > 15 min → critical |
| Duplicate accounting packet rate | warn > 1 % |
| Both nodes down | critical + runbook §2 |

## MikroTik / ISP network (per NAS)

| Metric | Alert |
|---|---|
| Reachability (API/ICMP private) | 2 consecutive failures → warn; 10 min → critical |
| Active PPPoE sessions vs baseline | ±30 % change in 15 min |
| CPU / memory | warn 80 % sustained 10 min |
| Interface errors/drops | warn on sustained growth |
| Partner queue rate vs contract cap | report cap utilization; critical if cap not applied while partner traffic present |
| RouterOS API failure | warn; disconnect commands must remain pending |

## Dashboards (not yet implemented)

- Central: NAS health, active sessions, pending/dead-letter sync, reject spike,
  accounting lag.
- Partner (read-only, scoped): daily upload/download, peak throughput vs contract,
  completeness flag; no router/pool/queue details and no customer PII beyond the
  partner's own customers.

Frontend widgets are Agent B scope but were not delivered in this change set; the
reader/endpoints they need are in place.
