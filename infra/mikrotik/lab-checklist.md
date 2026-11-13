# MikroTik lab checklist (Agent B)

Fill this in for the exact lab device before claiming any behavior in a PR.

## Device

| Field | Value |
|---|---|
| Model | |
| RouterOS version | |
| Architecture / CPU | |
| Uplink capacity | |
| FastTrack | enabled / disabled |
| Test date | |

## Auth

- [ ] PAP success + failure captured (`/log print where topics~"radius"`).
- [ ] CHAP/MS-CHAP result recorded (or explicitly out of scope until credential
      enrollment is decided).
- [ ] Suspended user (`Auth-Type := Reject`) receives reject.
- [ ] Wrong shared secret / unknown NAS rejected.
- [ ] Local PPP Secret shadowing tested and documented.

## Speed policy

- [ ] `Mikrotik-Rate-Limit` applied; measured download/upload with a real client.
- [ ] Direction (rx/tx vs upload/download) confirmed and written down.
- [ ] Burst/queue-type interaction documented.

## Partner aggregate cap

- [ ] Queue tree/PCQ enforces the contract cap with N customers saturating.
- [ ] Partner A traffic never enters partner B queue.
- [ ] CPU/drop counters recorded at cap.
- [ ] Sampling interval of queue stats defined and matched to `router_queue_samples`.

## Accounting

- [ ] Start/Interim/Stop rows observed in `radacct`.
- [ ] Interim interval equals NAS/DB configuration.
- [ ] Gigawords observed (large counter) or simulated test vector attached.
- [ ] NAS reboot/counter reset behavior observed.
- [ ] NAS ↔ RADIUS outage: gap visible, no fabricated usage.

## Disconnect / CoA

- [ ] Disconnect-Message / CoA capability recorded for this version.
- [ ] `disconnect` command path tested with the API enabled **in lab**.
- [ ] Result of a disconnect command with API unreachable: stays pending.
