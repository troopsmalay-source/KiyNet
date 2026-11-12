@props(['stats'])

<section class="dash-section" aria-labelledby="partner-stats-heading">
    <header class="dash-section-head">
        <div>
            <h2 id="partner-stats-heading">Ringkasan kemitraan</h2>
            <p>Status kerja sama, kelengkapan dokumen, dan kewajiban bandwidth mitra.</p>
        </div>
        <a class="dash-link" href="{{ route('admin.mitra.index') }}">
            Kelola mitra <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </header>

    <div class="dash-stat-grid dash-cols-4">
        <x-dashboard.stat-tile
            label="Mitra Aktif"
            :value="number_format($stats['aktif'], 0, ',', '.')"
            meta="Kontrak berjalan"
            tone="success"
            icon="bi-people-fill"
        />
        <x-dashboard.stat-tile
            label="Menunggu Verifikasi"
            :value="number_format($stats['menungguVerifikasi'], 0, ',', '.')"
            meta="Dokumen / akun belum lengkap"
            tone="info"
            icon="bi-patch-check"
            :mock="true"
        />
        <x-dashboard.stat-tile
            label="Kontrak Segera Berakhir"
            :value="number_format($stats['kontrakSegeraBerakhir'], 0, ',', '.')"
            meta="Kurang dari 30 hari"
            tone="warning"
            icon="bi-file-earmark-text"
            :mock="true"
        />
        <x-dashboard.stat-tile
            label="Tagihan Bandwidth Tertunggak"
            :value="number_format($stats['tagihanBandwidthTertunggak'], 0, ',', '.') . ' mitra'"
            :meta="'Rp ' . number_format($stats['tagihanBandwidthNominal'], 0, ',', '.') . ' belum dibayar'"
            tone="danger"
            icon="bi-router"
            :mock="true"
        />
    </div>
</section>
