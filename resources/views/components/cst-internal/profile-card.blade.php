@props(['customer'])

@php
    $wa = preg_replace('/^0/', '62', preg_replace('/\D/', '', (string) $customer['no_hp']));
    $hasCoordinates = filled($customer['latitude'] ?? null) && filled($customer['longitude'] ?? null);
    $mapUrl = $hasCoordinates
        ? 'https://www.google.com/maps?q='.$customer['latitude'].','.$customer['longitude']
        : null;
@endphp

<article class="cst-card" aria-labelledby="cst-profile-heading">
    <header class="cst-card-head">
        <span class="cst-card-icon"><i class="bi bi-person-vcard" aria-hidden="true"></i></span>
        <div>
            <h3 id="cst-profile-heading">Profil Pelanggan</h3>
            <p>Identitas, alamat pemasangan, dan catatan internal</p>
        </div>
    </header>
    <div class="cst-card-body">
        <dl class="cst-facts">
            <div class="cst-fact cst-fact--full">
                <dt>Nomor Pelanggan</dt>
                <dd>
                    <code class="cst-code-chip">{{ $customer['id'] }}</code>
                    <span class="cst-lock"><i class="bi bi-lock" aria-hidden="true"></i> read-only</span>
                </dd>
            </div>
            <div class="cst-fact">
                <dt>Nama Lengkap</dt>
                <dd>{{ $customer['nama'] }}</dd>
            </div>
            <div class="cst-fact">
                <dt>No. HP / WhatsApp</dt>
                <dd><a href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener">{{ $customer['no_hp'] }}</a></dd>
            </div>
            <div class="cst-fact cst-fact--full">
                <dt>Email</dt>
                <dd>{{ $customer['email'] ?: '—' }}</dd>
            </div>
            <div class="cst-fact cst-fact--full">
                <dt>Alamat Pemasangan</dt>
                <dd>{{ $customer['alamat'] }}</dd>
            </div>
            <div class="cst-fact">
                <dt>Wilayah Layanan</dt>
                <dd>{{ $customer['wilayah'] }}</dd>
            </div>
            <div class="cst-fact">
                <dt>POP / Cluster</dt>
                <dd>{{ $customer['cluster'] }}</dd>
            </div>
            <div class="cst-fact">
                <dt>ODP</dt>
                <dd>{{ $customer['odp'] }}</dd>
            </div>
            <div class="cst-fact">
                <dt>Mitra Pengelola</dt>
                <dd>{{ $customer['mitra'] }}</dd>
            </div>
            <div class="cst-fact cst-fact--full">
                <dt>Koordinat Lokasi</dt>
                <dd>
                    @if ($hasCoordinates)
                        {{ number_format((float) $customer['latitude'], 4, ',', '.') }},
                        {{ number_format((float) $customer['longitude'], 4, ',', '.') }}
                        <a href="{{ $mapUrl }}" target="_blank" rel="noopener" style="margin-left: 6px; font-size: 9.5px; font-weight: 600;">
                            <i class="bi bi-geo-alt" aria-hidden="true"></i> Lihat di peta
                        </a>
                    @else
                        Belum dipetakan
                    @endif
                </dd>
            </div>
        </dl>

        @if (filled($customer['catatan'] ?? null))
            <div class="cst-note">
                <span><i class="bi bi-sticky" aria-hidden="true"></i> Catatan khusus</span>
                <p>{{ $customer['catatan'] }}</p>
            </div>
        @endif
    </div>
</article>
