# MikroTik PPPoE gateway (Agent B)

All MikroTik PPPoE concentrators/NAS devices are owned and operated by KikiNet
ISP central. Partners receive **no** RouterOS, Winbox, SSH or API credentials and
cannot change pools, queues, firewall, RADIUS or routing.

> **Status: templates only, not lab-verified.** Queue/PCQ behavior, FastTrack
> interaction and CoA/Disconnect support must be measured on the exact
> RouterOS version/model before production.

## 1. NAS onboarding record

Capture in `nas_devices` (+ the deployment runbook):

- `code`, `name`, site/POP label, model, exact RouterOS version;
- private management IP, management VPN, `nas_identifier`;
- RouterOS API port/secret **reference** (`api_secret_reference`) — never value;
- RADIUS secret **reference** (`radius_secret_reference`) — never value;
- PPPoE service/profile, address pools, DNS;
- RADIUS primary/secondary targets;
- accounting enabled + interim interval (default 300s);
- monitoring + maintenance window.

## 2. Connectivity rules

- RouterOS API (8728/8729) reachable only from the central management service
  (private VPN/network). Never expose it to the internet or to partners.
- RADIUS (1812/1813 UDP) only from NAS source IPs on the private AAA network.
- Use a dedicated API user with the minimum policy for the operations used
  (`/ppp/active`, `/ppp/secret`, `/queue` reads; disconnect only after approval).
- Application never logs passwords, shared secrets or API credentials.

## 3. PPPoE migration checklist

1. Back up and export the router config (encrypted, off-device).
2. Inventory local PPP Secrets and usernames; detect duplicates **before**
   enabling RADIUS (`/ppp/secret/print where name=...`).
3. Create globally unique KikiNet usernames (`KN-<partner>-<id>`).
4. Enable RADIUS auth for **one pilot user** first; local secrets can shadow
   RADIUS, so disable/remove only confirmed duplicates during approved cutover.
5. Enable accounting and interim updates; confirm counters appear in `radacct`.
6. Verify IP pool, DNS, profile and re-authentication behavior.

## 4. Bandwidth policy

Two independent mechanisms — never assume one enforces the other:

| Level | Mechanism | Verified by |
|---|---|---|
| Per customer | `Mikrotik-Rate-Limit` reply attribute (rx/tx) | real PPPoE session rate test, both directions |
| Per partner aggregate | queue tree / PCQ hierarchy on a partner-scoped mark | lab measurement of aggregate throughput and isolation |

- RouterOS `rx/tx` is from the router's perspective: rx = client upload,
  tx = client download. `config/network.php` uses `:uploadM/:downloadM`; this
  direction **must** be re-verified in lab before production.
- Partner queue names/IDs are generated from trusted internal partner mapping,
  never from a client-supplied label.
- FastTrack can bypass queues — test with it enabled and disabled.
- Record router CPU/throughput ceiling; a cap that the hardware cannot enforce is
  a false claim.

Templates: `pppoe-radius.rsc.template`, `partner-cap-queue.rsc.template`.

## 5. Operational actions

| Action | Path | Notes |
|---|---|---|
| Provision | outbox → RADIUS projection | policy attributes only; credential enrollment separate |
| Suspend | outbox → `Auth-Type := Reject` | affects next authentication, not the live session |
| Restore | outbox → remove Reject | verify next login |
| Change profile | outbox → rewrite `Mikrotik-Rate-Limit` | live session may need re-auth |
| Disconnect | outbox → RouterOS API | **disabled** until verified; stays pending |

API timeout = command pending/failed, never "applied". `MikrotikLog`/audit rows
record the attempt result.
