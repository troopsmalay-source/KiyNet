# FreeRADIUS deployment notes (target)

## Intended role
FreeRADIUS performs PPPoE AAA. RouterOS NAS is its RADIUS client. Laravel owns customer/subscription business state and writes versioned network projections via outbox; it does not implement RADIUS protocol.

## Version discipline
Pin FreeRADIUS version and use exactly matching SQL schema/sample queries. FreeRADIUS 3.x and 4.x configuration/schema conventions differ; do not copy SQL tables/query definitions across major versions without validation.

## Database and security
- Use PostgreSQL RADIUS database/schema (`kikinet_radius`) separate from business schema (`kikinet_app`) or at minimum separate schema/roles.
- FreeRADIUS DB role needs only required auth and accounting privileges.
- Laravel network adapter writes required auth/projection tables through a narrowly privileged role; it reads accounting through read-only role or normalized ingestion path.
- RADIUS UDP 1812 (auth) and 1813 (accounting) accept packets only from known NAS source IPs.
- Each NAS has a unique strong shared secret. Keep secret in secret manager/config injection, not committed YAML or normal DB column.
- Use private IP/VPN; never expose RADIUS/RouterOS management ports to public internet.

## Minimum lab checklist
1. `radtest` or equivalent validates DB lookup and PAP behavior.
2. Real PPPoE client logs in through test MikroTik.
3. Test each auth type selected by ISP; validate secret storage capability.
4. Confirm `Access-Accept` attributes and resulting interface/session profile.
5. Validate accounting Start/Interim/Stop and NAS outage behavior.
6. Validate secondary server and SQL failover/recovery.
7. Validate policy update and Disconnect/CoA support on target RouterOS.
8. Confirm local PPP Secret does not shadow matching RADIUS user.

## SQL and counter notes
Use standard tables only when schema matches pinned FreeRADIUS version. For `radacct`, calculate 64-bit octets using gigawords. Build custom normalized sample and idempotency tables outside vendor-owned schema rather than altering `radacct` unexpectedly.

## Production controls
- systemd service health and restart policy;
- primary/secondary monitoring;
- DB connection/latency and RADIUS response metrics;
- firewall allowlists;
- configuration test and change review before reload;
- encrypted backup of config and RADIUS DB;
- alert on auth reject spike, SQL failure, accounting lag and NAS silence.
