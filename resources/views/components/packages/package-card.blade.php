@php
    $kategoriLabels = \App\Support\PackageServiceRepository::KATEGORI;
    $siklusLabels = \App\Support\PackageServiceRepository::SIKLUS;

    $bandwidth = number_format((float) ($package['download_mbps'] ?? 0), ((float) $package['download_mbps'] == (int) $package['download_mbps'] ? 0 : 1));
    $upload = number_format((float) ($package['upload_mbps'] ?? 0), ((float) $package['upload_mbps'] == (int) $package['upload_mbps'] ? 0 : 1));

    $diskonNominal = 0;
    if (($package['diskon_jenis'] ?? null) === 'persen') {
        $diskonNominal = (float) ($package['harga'] ?? 0) * ((float) $package['diskon_value'] / 100);
    } elseif (($package['diskon_jenis'] ?? null) === 'nominal') {
        $diskonNominal = (float) $package['diskon_value'];
    }
    $hargaFinal = max(0, (float) ($package['harga'] ?? 0) - $diskonNominal);

    $safePackage = htmlspecialchars(json_encode($package), ENT_QUOTES, 'UTF-8');
@endphp

<article class="pkg-card{{ empty($package['is_active']) ? ' pkg-card--off' : '' }}" data-pkg-id="{{ $package['id'] }}">
    <div class="pkg-card-head">
        <div class="pkg-card-title">
            <h3>{{ $package['nama'] }}</h3>
            <code>{{ $package['id'] }}</code>
            <div class="pkg-badges">
                @if (! empty($package['is_active']))
                    <span class="pkg-badge pkg-badge--active"><i class="bi bi-check-circle-fill"></i> Aktif</span>
                @else
                    <span class="pkg-badge pkg-badge--inactive"><i class="bi bi-pause-circle-fill"></i> Nonaktif</span>
                @endif
                <span class="pkg-badge pkg-badge--{{ $package['kategori'] }}">
                    @if ($package['is_custom'] ?? false)
                        <i class="bi bi-star-fill"></i> Paket Khusus
                    @else
                        {{ $kategoriLabels[$package['kategori']] ?? $package['kategori'] }}
                    @endif
                </span>
            </div>
        </div>
    </div>

    <div class="pkg-bw">
        <div class="pkg-bw-item">
            <i class="bi bi-arrow-down"></i>
            <strong>{{ $bandwidth }}</strong>
            <small>Mbps</small>
        </div>
        <span class="pkg-bw-sep"></span>
        <div class="pkg-bw-item">
            <i class="bi bi-arrow-up"></i>
            <strong>{{ $upload }}</strong>
            <small>Mbps</small>
        </div>
    </div>

    <div class="pkg-net">
        <div class="pkg-net-item">
            <span>PPP Profile</span>
            <strong>{{ $package['mikrotik_profile'] }}</strong>
        </div>
        <div class="pkg-net-item">
            <span>Rate-limit</span>
            <strong>{{ $package['rate_limit'] }}</strong>
        </div>
        <div class="pkg-net-item">
            <span>Address Pool</span>
            <strong>{{ $package['ip_pool'] ?: '-' }}</strong>
        </div>
        <div class="pkg-net-item">
            <span>Queue Priority</span>
            <strong>P{{ $package['queue_priority'] }}</strong>
        </div>
    </div>

    <div class="pkg-price">
        <div class="pkg-price-main">
            <strong>Rp {{ number_format($hargaFinal, 0, ',', '.') }} <small>/ {{ $siklusLabels[$package['siklus_tagihan']] ?? $package['siklus_tagihan'] }}</small></strong>
        </div>
        <div class="pkg-price-side">
            @if ($diskonNominal > 0)
                <span>Diskon</span>
                <em>Rp {{ number_format($diskonNominal, 0, ',', '.') }}</em>
            @endif
        </div>
    </div>

    @if ($package['biaya_instalasi'] > 0 || $package['biaya_addon'] > 0)
        <p class="pkg-price-main" style="margin: 0; font-size: 9.5px; color: var(--pkg-muted);">
            @if ($package['biaya_instalasi'] > 0)
                Instalasi: Rp {{ number_format($package['biaya_instalasi'], 0, ',', '.') }}
            @endif
            @if ($package['biaya_addon'] > 0)
                {{ $package['biaya_instalasi'] > 0 ? '·' : '' }} Add-on: Rp {{ number_format($package['biaya_addon'], 0, ',', '.') }}/bln
            @endif
        </p>
    @endif

    @if (! empty($package['fup_enabled']))
        <span class="pkg-fup">
            <i class="bi bi-speedometer2"></i>
            FUP {{ number_format((float) $package['fup_limit_gb'], 0, ',', '.') }} GB
            @if ($package['fup_throttle_rx'])
                &rarr; Throttle {{ rtrim(rtrim(number_format($package['fup_throttle_rx'], 1, '.', ''), '0'), '.') }} Mbps
            @endif
        </span>
    @else
        <span class="pkg-fup pkg-fup--unlimited">
            <i class="bi bi-infinity"></i> Unlimited No FUP
        </span>
    @endif

    <footer class="pkg-card-foot">
        <form class="pkg-toggle" method="POST" action="{{ route('admin.packages.toggle', $package['id']) }}">
            @csrf
            <span class="pkg-toggle-label">{{ $package['is_active'] ? 'Aktif' : 'Nonaktif' }}</span>
            <button class="pkg-switch" type="submit" role="switch" aria-checked="{{ ! empty($package['is_active']) ? 'true' : 'false' }}" aria-label="Toggle status {{ $package['nama'] }}"></button>
        </form>
        <div class="pkg-actions">
            <button class="pkg-btn pkg-btn--ghost pkg-btn--icon" type="button" data-pkg-open="edit" data-pkg="{{ $safePackage }}" title="Edit Paket" aria-label="Edit {{ $package['nama'] }}">
                <i class="bi bi-pencil"></i>
            </button>
            <form method="POST" action="{{ route('admin.packages.destroy', $package['id']) }}" style="display:inline">
                @csrf
                @method('DELETE')
                <button class="pkg-btn pkg-btn--danger pkg-btn--icon" type="submit" title="Hapus" aria-label="Hapus {{ $package['nama'] }}" onclick="return confirm('Hapus paket {{ $package['nama'] }}? (soft delete)')">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </footer>
</article>
