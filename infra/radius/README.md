# FreeRADIUS — KikiNet AAA (Agent B)

FreeRADIUS performs PPPoE AAA for ISP-owned MikroTik NAS devices. Laravel owns
customer/subscription truth and writes a versioned policy projection through
`radius_sync_outbox`; it never implements the RADIUS protocol.

> **Status: specification-level, NOT lab-verified.** Nothing in this folder has
> been executed against a real FreeRADIUS/MikroTik pair yet. The version matrix
> below is still an open decision (see `docs/network/PHASE0-NETWORK-AUDIT.md`).

## 1. Version pinning

| Component | Target | Rule |
|---|---|---|
| FreeRADIUS | 3.2.x **or** 4.0.x — pick one | SQL schema and `mods-available/sql` must come from the *same* major/minor |
| PostgreSQL | same major as the business DB cluster | separate database `kikinet_radius`, separate role |
| RouterOS | exact model/version recorded per NAS | validate attributes/CoA per version |

Never copy `radcheck`/`radacct` DDL from a different FreeRADIUS major. The
vendor schema lives with the pinned package
(`raddb/mods-config/sql/main/postgresql/schema.sql`); KikiNet does **not**
re-create it in application migrations.

## 2. Database separation

- `kikinet_app` — Laravel business + network projection + accounting read model
  (tables from `2026_11_13_100000_create_network_and_accounting_tables.php`).
- `kikinet_radius` — FreeRADIUS-native tables only (`radcheck`, `radreply`,
  `radgroupcheck`, `radgroupreply`, `radusergroup`, `radacct`, `radpostauth`).

Roles:

| Role | Rights |
|---|---|
| `radiusd` | read auth tables, write `radacct`/`radpostauth` |
| `kikinet_projection` | SELECT/INSERT/UPDATE on `radcheck`/`radreply` only |
| `kikinet_analytics` | SELECT on `radacct` (read-only ingestion/sampling) |
| Laravel app role | **no** direct rights on `kikinet_radius` beyond `kikinet_projection` |

The application writes the RADIUS projection through the named connection
`RADIUS_DB_CONNECTION` (see `config/network.php` → `network.radius`). When the
connection is not set, `RadiusSqlProjectionGateway` refuses to write.

## 3. Network and firewall

- UDP 1812 (auth) and 1813 (accounting) accept packets **only** from registered
  NAS source IPs (`clients.conf.template`).
- Each NAS gets its own strong shared secret from the secret manager; the
  reference is stored in `nas_devices.radius_secret_reference`, never the value.
- RADIUS is reachable only over the private management/AAA network.
- Secondary node uses the same DB (or a streaming replica) and the same NAS
  client definitions; NAS devices list both servers.

## 4. PPPoE authentication decision (blocked)

A Laravel `bcrypt` hash is **not** automatically usable for CHAP/MS-CHAP.
Before enabling production auth, the lab must prove one of:

1. **PAP** with `Cleartext-Password` (requires the credential enrollment path to
   store a reversible, encrypted secret reference), or
2. **CHAP/MS-CHAP** with a FreeRADIUS-compatible digest scheme.

Until decided, the projection adapter only manages *authorization policy*
(`Auth-Type := Reject`, `Mikrotik-Rate-Limit`, `Framed-Pool`); credential rows
are left untouched (`network.radius.manage_credentials = false`).

## 5. Files

| File | Purpose |
|---|---|
| `clients.conf.template` | one `client` block per NAS, placeholders only |
| `mods-available/sql.template` | 3.2.x-style PostgreSQL `sql` module |
| `schema/README.md` | where to obtain the matching vendor schema |
| `lab/check-radius.sh` | smoke test: config check, radtest, accounting row probe |
| `runbook.md` | failover, reject spikes, accounting lag, secret rotation |

## 6. Minimum lab checklist before production

1. `radiusd -XC` passes with the pinned config.
2. `radtest` validates DB lookup and the chosen auth method.
3. Real PPPoE client authenticates through a test MikroTik.
4. `Access-Accept` carries the expected `Mikrotik-Rate-Limit` and the resulting
   session rate is measured up/down.
5. Suspended user (`Auth-Type := Reject`) is rejected.
6. Accounting Start/Interim/Stop arrives; Interim interval is the configured one.
7. NAS outage/recovery: accounting gap is observable, no fabricated usage.
8. Secondary node takeover works; primary recovery is tested.
9. A local PPP Secret cannot shadow a RADIUS username.
10. No secret appears in logs, `radpostauth`, or the repository.
