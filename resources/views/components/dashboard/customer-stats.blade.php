@props(['stats'])

<section class="dash-section" aria-labelledby="customer-stats-heading">
    <header class="dash-section-head">
        <div>
            <h2 id="customer-stats-heading">Ringkasan pelanggan</h2>
            <p>Distribusi status layanan seluruh pelanggan terdaftar.</p>
        </div>
        <a class="dash-link" href="{{ route('admin.cst-internal.index') }}">
            Kelola pelanggan <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </header>

    <div class="dash-stat-grid dash-cols-6">
        <x-dashboard.stat-tile
            label="Total Pelanggan"
            :value="number_format($stats['total'], 0, ',', '.')"
            meta="Seluruh pelanggan terdaftar"
            tone="neutral"
            icon="bi-people"
        />
        <x-dashboard.stat-tile
            label="Aktif"
            :value="number_format($stats['aktif'], 0, ',', '.')"
            meta="Layanan berjalan normal"
            tone="success"
            icon="bi-person-check"
        />
        <x-dashboard.stat-tile
            label="Isolir"
            :value="number_format($stats['isolir'], 0, ',', '.')"
            meta="Layanan dibatasi sementara"
            tone="warning"
            icon="bi-person-lock"
        />
        <x-dashboard.stat-tile
            label="Menunggak"
            :value="number_format($stats['menunggak'], 0, ',', '.')"
            meta="Punya tagihan belum lunas"
            tone="danger"
            icon="bi-exclamation-octagon"
        />
        <x-dashboard.stat-tile
            label="Pending Registrasi"
            :value="number_format($stats['pending'], 0, ',', '.')"
            meta="Menunggu registrasi / aktivasi"
            tone="info"
            icon="bi-hourglass-split"
        />
        <x-dashboard.stat-tile
            label="Churn"
            :value="number_format($stats['churn'], 0, ',', '.')"
            meta="Berhenti berlangganan"
            tone="muted"
            icon="bi-person-dash"
        />
    </div>
</section>
