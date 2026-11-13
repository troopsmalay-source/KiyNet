# RADIUS runbook (Agent B)

All commands are for the private AAA network. Record the executed commands and
timestamps in the incident log. Never paste shared secrets into chat/tickets.

## 1. Primary RADIUS down

Symptoms: NAS logs timeout to primary, `Access-Reject` spikes, accounting lag.

1. Confirm the process: `systemctl status freeradius` on primary.
2. Check DB connectivity from primary: `psql -d kikinet_radius -c 'select 1'`.
3. If the primary cannot recover quickly, verify NAS clients still list the
   secondary (`/radius` on RouterOS) and that the secondary has the same
   `clients.conf` entries.
4. Confirm new sessions authenticate against the secondary:
   `radtest <lab-user> <lab-pass> <secondary-ip> 0 <shared-secret>`.
5. Do **not** restart the secondary while the primary is down.
6. After recovering the primary, verify it is **not** serving stale policy
   (compare `radreply` rate limit for one pilot user).

Existing sessions are not dropped by a RADIUS outage; new authentication and
CoA/Disconnect are affected.

## 2. Both RADIUS nodes unavailable

1. Declare a network incident; new PPPoE logins will fail.
2. Do not disable RADIUS on the NAS to "restore service" unless the network
   owner approves a local-secret fallback (it bypasses policy and accounting).
3. When restored, reconcile accounting: gaps are expected; mark affected days
   `estimated`, never invent usage.

## 3. Authentication reject spike

1. Check `radpostauth` for the reject reason and NAS source.
2. Distinguish: wrong secret (all users rejected from one NAS) vs policy
   (`Auth-Type := Reject` for suspended users) vs credential scheme mismatch.
3. If a single NAS IP changed, update `clients.conf` and `nas_devices`
   (`management_ip`, secret reference) through the review process.
4. A mass reject after a policy sync usually means the projection wrote an
   unintended `Auth-Type := Reject`; inspect `radius_sync_outbox` and
   `network_policy_audits` for the last command.

## 4. Accounting missing / lagging

1. Check NAS accounting settings (interim interval, RADIUS server reachability).
2. Compare `radacct` row count growth with `radius_accounting_samples`.
3. Duplicate/out-of-order/reset counters are expected to be flagged, not fixed
   by editing samples. Re-run the daily rollup for the affected date and inspect
   `completeness` (`estimated`/`gap`).
4. Missing Stop: run stale reconciliation; it marks the session stale and
   estimated, it does **not** write a fabricated stop time.

## 5. Shared secret rotation (one NAS at a time)

1. Generate a new secret in the secret manager; store a new reference.
2. Update the NAS `client` block on both RADIUS nodes (staged config), reload.
3. Update the NAS RADIUS entry, then reload the NAS config.
4. Verify one test login and accounting; then remove the old secret.
5. Never rotate all NAS simultaneously.

## 6. Policy rollback

1. Identify the last known-good `policy_version` in `network_policy_audits`.
2. Re-dispatch a new outbox command with that profile/policy (forward fix).
3. Do not delete outbox rows; superseded/failed history is audit evidence.
