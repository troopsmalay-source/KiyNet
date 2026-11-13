# Rencana Lab AAA End-to-End (PPPoE + FreeRADIUS + RouterOS)

Status: rencana pra-produksi. Semua vektor di bawah wajib hijau sebelum go-live.

## 1. Pilihan metode auth PPPoE

| Kandidat | Keputusan | Alasan |
| --- | --- | --- |
| PAP | **Ya (diaktifkan)** | Satu-satunya metode yang membawa password cleartext ke FreeRADIUS sehingga `Cleartext-Password`/hash di `radcheck` bisa divalidasi penuh. Karena RADIUS secret + LAN lab terkontrol, risiko sniffing dapat diterima. |
| CHAP | Ya (sebagai pembanding) | Tidak mengirim password, tapi memaksa plaintext password di sisi server (hashable ke NT-hash saja bila pakai MS-CHAP). Berguna untuk membuktikan parsing `CHAP-Challenge`. |
| MS-CHAPv2 | Tidak di lab pertama | Butuh `nt-password` + MPPE keys; menambah kompleksitas tanpa kebutuhan bisnis saat ini. Upgrade path didokumentasikan di `infra/radius/README.md`. |

Keputusan: **PAP sebagai jalur utama** (selaras dengan kolom `pppoe_username` + password snapshot pada `langganan`), CHAP sebagai vektor pengujian negatif.

## 2. Versi FreeRADIUS

- FreeRADIUS **3.2.x** (Debian 12 / Ubuntu 24.04 paket `freeradius`), modul `rlm_sql` + driver `rlm_sql_postgresql`.
- Backend auth: **PostgreSQL** (satu sumber kebenaran dengan aplikasi; lihat `radius_sql_projection` di migrasi).
- Alasan versi: 3.2 adalah cabang stabil saat ini, schema `radcheck/radreply/radacct` kompatibel dengan SQL template di `infra/radius/mods-available/sql.template`.

## 3. Topologi uji RouterOS

```
[Client CHR/WinBox]  <--PPPoE-->  [RouterOS CHR 7.x (NAS)]
                                     |  radius client (pppoe-radius.rsc.template)
                                     v
                       [FreeRADIUS 3.2 (systemd, paket distro di VPS aaPanel)]
                                     |  rlm_sql (psql)
                                     v
                            [PostgreSQL: radcheck, radacct, radius_sql_projection]
                                     ^
        [App kikinet + worker outbox]  (menulis radcheck/projection, membaca radacct snapshot)
```

NAS diprovision via `/ppp aaa` + `/radius` dari `infra/mikrotik/pppoe-radius.rsc.template`; cap agregat partner via `partner-cap-queue.rsc.template`.

## 4. Vektor uji wajib (gate produksi)

### 4.1 Autentikasi
- V1: PPPoE login PAP kredensial valid di `radcheck` → session UP, `radacct` tercatat dengan `Acct-Session-Id`.
- V2: password salah → Access-Reject, tidak ada baris `radacct`.
- V3: username tidak ada → Access-Reject; rate-limit reject di FreeRADIUS aktif.
- V4: langganan `ditangguhkan`/`berhenti` (worker outbox menghapus/menandai `radcheck`) → login baru ditolak, session aktif diputus (COA atau disconnect packet).

### 4.2 Otorisasi / atribut
- V5: `Framed-IP-Address` statik dari `ip_static` terprovision saat IP diisi; pool dinamis saat kosong.
- V6: `Mikrotik-Rate-Limit` dari `NetworkAccessProfile::rateLimit()` diterapkan; verifikasi speedtest dua arah sesuai `:upload/:download`.
- V7: `Mikrotik-Wireless-Comment`/`Mikrotik-Address-List` isolasi (profile `is_isolation=true`) → client terisolasi.

### 4.3 Accounting
- V8: Interim-Update tiba sesuai `Acct-Interim-Interval`; `AccountingIngestor` dedup (satu session = satu baris, akumulasi octet naik monoton).
- V9: Stop record → `stop_at` terisi; `UsageRollupService` menghasilkan baris `customer_usage_daily` yang sama dengan hitungan manual.
- V10: NAS mati tanpa Stop → `ReconcileStaleSessions` menandai session stale setelah threshold; tidak double-count.

### 4.4 Sinkronisasi lifecycle (outbox)
- V11: activate di admin → baris `radius_sync_outbox` `queued` → worker `ProcessNetworkOutbox` applied → `radcheck` berisi kredensial.
- V12: change-package → rate-limit baru terkirim, outbox terkunci (idempotent, tidak double-apply).
- V13: suspend/restore → reject untuk suspend, login sukses kembali untuk restore.
- V14: worker gagal (FreeRADIUS down) → retry/backoff, status `failed` setelah max attempt, alert log.

### 4.5 Cap agregat partner
- V15: 25 client di bawah 1 partner mencapai agregat cap; throughput agregat tidak melebihi `partner-cap-queue.rsc.template` walau per-client unlimited.
- V16: sampling `CollectQueueSamples` menuliskan `router_queue_samples` dan dedup by (partner, nas, target, bucket) tidak dobel.

### 4.6 Keamanan API
- V17: endpoint `routes/api/v1/network.php` tanpa token → 401; partner A tidak bisa membaca data partner B (scope test).
- V18: envelope error konsisten (`ApiEnvelope`), tidak ada kebocoran stack trace.

## 5. Definisi selesai (gate produksi)
Seluruh V1–V18 hijau dalam 2 run berturut-turut, evidence (screenshot CLI RouterOS + query psql) diarsipkan di `docs/network/lab-evidence/`.

## 6. Catatan deployment VPS aaPanel

Semua komponen berjalan native di satu VPS aaPanel (tanpa container):

- **FreeRADIUS**: `apt install freeradius freeradius-postgresql`; kelola via `systemctl status freeradius`; `radiusd -XC` untuk validasi config.
- **PostgreSQL**: instance aaPanel; database `kikinet_app` dan `kikinet_radius` terpisah dengan role per `infra/radius/README.md`.
- **Scheduler Laravel**: cron aaPanel/crontab: `* * * * * cd /www/wwwroot/kikinet && php artisan schedule:run >> /dev/null 2>&1`. Ini menjalankan outbox worker tiap menit, sampling queue, reconcile, dan rollup.
- **Queue worker** (jika memakai `queue:work`): Supervisor dari aaPanel, atau cukup cron scheduler di atas bila semua job sudah berbasis `Schedule::command`.
- **Firewall**: aaPanel Firewall buka UDP 1812/1813 hanya untuk IP NAS terdaftar; RADIUS hanya lewat jaringan privat/management.
- **Log**: rotasi log via aaPanel; `radius.log` dan `radwtmp` dipantau untuk reject spike.
