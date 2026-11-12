@props(['customer'])

<article class="cst-card" aria-labelledby="cst-service-heading">
    <header class="cst-card-head">
        <span class="cst-card-icon"><i class="bi bi-router" aria-hidden="true"></i></span>
        <div>
            <h3 id="cst-service-heading">Data Layanan &amp; Konfigurasi Jaringan</h3>
            <p>Kredensial PPPoE, paket, dan gateway MikroTik</p>
        </div>
    </header>
    <div class="cst-card-body">
        <div class="cst-credential">
            <div class="cst-credential-row">
                <span>Username PPPoE</span>
                <code>{{ $customer['pppoe_username'] }}</code>
            </div>
            <div class="cst-credential-row">
                <span>Password PPPoE</span>
                <span class="cst-secret">
                    <code id="cst-pppoe-secret" data-secret-value="{{ $customer['pppoe_password'] }}" data-revealed="false">••••••••</code>
                    <button type="button" class="cst-secret-toggle" data-toggle-secret="cst-pppoe-secret"
                        aria-label="Tampilkan password PPPoE" aria-pressed="false">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </span>
            </div>
        </div>

        <dl class="cst-facts">
            <div class="cst-fact">
                <dt>Paket Internet</dt>
                <dd>
                    {{ $customer['paket'] }}
                    <span class="cst-sub">{{ $customer['bandwidth'] }}</span>
                </dd>
            </div>
            <div class="cst-fact">
                <dt>Profil / Rate Limit</dt>
                <dd>
                    {{ $customer['profile'] }} · {{ $customer['rate_limit'] }}
                    <span class="cst-sub">Queue: {{ $customer['queue'] }}</span>
                </dd>
            </div>
            <div class="cst-fact">
                <dt>Router / Gateway ISP</dt>
                <dd>
                    {{ $customer['router'] }}
                    <span class="cst-sub">{{ $customer['router_ip'] }} · MikroTik</span>
                </dd>
            </div>
            <div class="cst-fact">
                <dt>Wilayah / ODP</dt>
                <dd>
                    {{ $customer['wilayah'] }}
                    <span class="cst-sub">{{ $customer['odp'] }}</span>
                </dd>
            </div>
        </dl>

        <div class="cst-mini-tiles">
            <div class="cst-mini-tile">
                <span>Tanggal aktivasi</span>
                <strong>{{ $customer['tanggal_aktivasi'] ?: 'Belum aktivasi' }}</strong>
            </div>
            <div class="cst-mini-tile">
                <span>Jatuh tempo tagihan</span>
                <strong>{{ $customer['jatuh_tempo'] ?: '—' }}</strong>
            </div>
        </div>
    </div>
</article>
