# Accounting algorithm (Agent B)

Implementation: `app/Domain/Accounting/Services/*`, tests in
`tests/Feature/Network/AccountingIngestorTest.php`,
`tests/Unit/Network/CounterNormalizerTest.php`,
`tests/Unit/Network/IntervalAllocatorTest.php`,
`tests/Feature/Network/UsageRollupTest.php`.

## 1. Counter math

RADIUS sends a 32-bit octet counter plus a Gigawords high word:

```
total = gigawords * 4294967296 + octets          (input and output independently)
delta = total_now - total_previous               (only when ordered and >= 0)
```

`CounterNormalizer` rejects negative inputs and never returns a negative delta.
A negative delta means the NAS counter reset/rebooted: the sample is stored,
flagged `counter_reset_detected = true`, `delta_valid = false`, delta = 0, and the
new total becomes the baseline. It is never converted into "traffic".

## 2. Test vectors

| Vector | Input (prev → now) | Expected |
|---|---|---|
| plain | 1 000 → 1 500 | delta 500, valid |
| wrap | `octets 4294967295, gw 0` → `octets 1536, gw 1` | delta 1 537, valid |
| reset | 5 000 000 000 → 1 200 | delta 0, invalid, reset flagged |
| gigawords | 0 → `octets 100, gw 1` | total 4 294 967 396 |

## 3. Event identity and idempotency

```
dedupe_key = sha256(nas_device_id | acct_session_id | acct_unique_id |
                   event_type | event_timestamp | input_total | output_total)
```

`radius_accounting_samples.dedupe_key` is unique. A duplicate Start/Interim/Stop
returns the stored sample with `duplicate = true` and changes nothing (the unique
violation race path also resolves to the stored row).

Session identity: `(nas_device_id, acct_session_id, start_at)`. A reused
`Acct-Session-Id` with a different `Acct-Start-Time` is a **new** session row.
Interim/Stop without an exact start match attach to the newest open session for
that NAS + session id instead of creating a duplicate.

## 4. Ordering

`session.last_sample_at` is the ordered cursor.

- event time `>= last_sample_at`: delta validated against the session baseline;
  baseline and cursor advance.
- event time `< last_sample_at`: sample is kept (`out_of_order = true`,
  `delta_valid = false`, delta 0) and the baseline is **not** rewound. The daily
  rollup excludes it and marks the affected day `estimated`.

## 5. Missing Stop

`StaleSessionReconciler` marks sessions idle beyond
`network.accounting.stale_after_minutes` (default 90) as `stale`,
`estimated_close = true`, `close_reason = missing_stop`. `stop_at` stays `null`;
no stop time and no usage is fabricated.

## 6. Daily rollup

Per customer/day:

1. load sessions overlapping the UTC day;
2. take the last sample before the day (interval boundary) and the first sample
   after the day (closing a straddling interval);
3. for every valid sample, allocate the delta across days proportionally to the
   seconds in each day (`IntervalAllocator`), deterministic and byte-exact;
4. a sample with an interval that cannot be measured (first sample of a session)
   assigns its bytes to the sample's day and marks the day estimated when bytes
   are non-zero;
5. any invalid/out-of-order sample in the day, any midnight-straddling interval,
   any still-open or stale session ⇒ `completeness = estimated`;
6. zero samples for the day ⇒ previous row is removed and `null` is returned.

Partner rollup sums `customer_usage_daily` for the partner and takes
`peak_download_mbps`/`peak_upload_mbps` **only** from `router_queue_samples`.
Customer RADIUS bytes and router queue rates are separate measurements and are
never added together. Without queue samples, partner `completeness = estimated`
(the aggregate cap cannot be proven).

## 7. Known limitations

- Customer peak throughput uses per-sample delta/interval (RADIUS evidence), not
  router queue sampling. It is `null` when fewer than two usable samples exist.
- 32-bit counter wrap *without* Gigawords cannot be distinguished from a reset
  after the fact; flagged as reset, never guessed.
- Rollups are explicit calls (no scheduler entry yet — see
  `CONTRACT-REQUESTS-B.md`).
- Time buckets are UTC; Asia/Jakarta day boundaries need a product decision
  (billing date vs UTC). Current implementation is UTC.
- Partitioning `radius_accounting_samples` by month is planned, not implemented
  (needs measured volume first).
