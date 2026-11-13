# Desain Cap Agregat per Mitra — Bukti Desain untuk Lab

Status: **desain, belum terbukti di lab.** Dokumen ini adalah checklist bukti
yang harus dijalankan sebelum cap agregat dianggap produksi-ready.

## 1. Keputusan desain

| Aspek | Keputusan | Alasan |
|---|---|---|
| Posisi cap | Queue tree di NAS RouterOS per mitra | Cap terjadi di titik agregasi alami, tidak per per-customer (itu sudah dari `Mikrotik-Rate-Limit`) |
| Identitas mitra | Address-list per mitra, di-populate dari proyeksi DB (langganan), bukan prefix username | Mencegah spoofing identitas via username |
| Mangle marking | `mark-packet` pada `chain=forward`, dua arah (up/down) | Packet-mark stabil untuk queue tree |
| Isolasi antar mitra | Queue tree terpisah per mitra | Trafik mitra A tidak boleh masuk queue mitra B (diuji) |
| Bukti pengukuran | Sampling counter `bytes` queue via API ke `router_queue_samples` | Rate dihitung dari delta counter, bukan counter instan |

## 2. Topologi uji

```
[PPPoE Client A1/A2 (mitra A)] ---+
[PPPoCE Client B1 (mitra B)]  ---+--- [NAS RouterOS lab] --- [Traffic Gen] --- WAN
                                  |
                          [FreeRADIUS 3.2 + PostgreSQL]
```

- NAS: RouterOS versi target, konfigurasi dari `infra/mikrotik/pppoe-radius.rsc.template`
  + `partner-cap-queue.rsc.template`.
- Router billing/traffic-gen: iPerf3 atau sejenis untuk menghasilkan beban.

## 3. Vektor uji cap agregat

| # | Uji | Kriteria lolos |
|---|---|---|
| C1 | Sum rate per-customer > cap | Trafik agregat mitra tidak melewati `max-limit`, drop teramati di queue |
| C2 | Isolasi mitra | Trafik mitra A tidak pernah masuk queue mitra B (`/queue tree print stats`) |
| C3 | Per-customer limit tetap benar | `Mikrotik-Rate-Limit` tiap sesi tetap berlaku dua arah meski cap aktif |
| C4 | FastTrack on vs off | Queue tree tetap efektif; jika FastTrack bypass queue, dokumentasikan syarat disable |
| C5 | CPU NAS saat cap | CPU < ambang yang ditetapkan saat line-rate; tidak ada error interface |
| C6 | Sampling interval | Interval `network:sample-queues` (default 5 menit) sama dengan `sample_interval_seconds` di DB; delta rate konsisten dengan `/queue tree print stats` |
| C7 | Gap sampling | Scheduler mati 2 interval → tidak ada rate fabrikasi di `router_queue_samples` (rate 0, bukan spike) |

## 4. Bukti yang wajib dilampirkan ke PR

1. Output `/queue tree print stats` sebelum/sesudah cap untuk C1, C2.
2. Screenshot/log `iPerf3` menunjukkan throughput ter-cap.
3. Query `router_queue_samples` untuk jendela uji (raw dan derived rate).
4. `/system resource print` saat line-rate untuk C5.
5. Catatan versi RouterOS + hardware model NAS.
