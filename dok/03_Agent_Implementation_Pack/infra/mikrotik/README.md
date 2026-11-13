# MikroTik central gateway operations (target)

## Ownership
All MikroTik PPPoE concentrators/NAS devices are owned and managed by KikiNet ISP central. Partners have no RouterOS/API/Winbox/SSH credentials and cannot change queue, IP pool, firewall, RADIUS or routing.

## NAS onboarding data
For each device record: NAS ID/code, RouterOS model/version, management private IP, NAS identifier, site/POP label, PPPoE service profile, address pools, RADIUS Primary/Secondary targets, unique shared secret reference, accounting enabled, interim interval, RouterOS API secret reference, monitoring and maintenance window.

## Connectivity
- Use management VPN/private network.
- Restrict RouterOS API to central management service IP only.
- Restrict RADIUS ports on server firewall to NAS addresses only.
- Use different secrets per NAS and rotation procedure.
- Never make RouterOS management reachable to partner or public internet.

## PPPoE migration checklist
- Export and securely back up router config.
- Inventory local PPP Secrets and duplicate usernames.
- Create unique global KikiNet PPPoE usernames.
- Test RADIUS lookup before enabling it for live pilot users.
- Remove/disable only confirmed duplicate local secrets during approved cutover.
- Enable accounting and interim update; confirm actual traffic counters.
- Verify IP pool, DNS, profile and client session behavior.

## Bandwidth policy
- Per customer: speed profile delivered through RADIUS attribute after lab verification.
- Per partner: separate aggregate queue group / hierarchy; do not assume a per-session rate attribute enforces partner total.
- Decide queue architecture after testing queue tree, PCQ, FastTrack and hardware forwarding effects on target device.
- Document upload/download perspective and ensure no rate direction inversion.
- Queue names/IDs are generated from trusted internal partner mapping, not client-supplied labels.

## Operational actions
Provision, suspend, restore, change profile and disconnect must be invoked through central service with operator authorization, reason, idempotency key and audit entry. API timeout results in pending/failed sync state, never a false success. CoA/Disconnect support and attributes must be verified for RouterOS version. For IP pool/route change, plan disconnect and re-auth if needed.
