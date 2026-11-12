@props(['billing', 'revenue'])

<section class="dash-section" aria-labelledby="billing-heading">
    <header class="dash-section-head">
        <div>
            <h2 id="billing-heading">Billing &amp; keuangan</h2>
            <p>Indikator invoice berjalan dan komposisi pendapatan retail vs wholesale.</p>
        </div>
        <a class="dash-link" href="{{ route('admin.tagihan.index') }}">
            Buka tagihan <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </header>

    <div class="dash-stat-grid dash-cols-5">
        <x-dashboard.stat-tile
            label="Invoice Terbit"
            :value="number_format($billing['invoiceTerbit'], 0, ',', '.')"
            meta="Total invoice aktif"
            tone="neutral"
            icon="bi-receipt"
        />
        <x-dashboard.stat-tile
            label="Lunas"
            :value="number_format($billing['lunas'], 0, ',', '.')"
            meta="Pembayaran selesai"
            tone="success"
            icon="bi-check2-circle"
        />
        <x-dashboard.stat-tile
            label="Belum Lunas"
            :value="number_format($billing['belumLunas'], 0, ',', '.')"
            meta="Termasuk dibayar sebagian"
            tone="warning"
            icon="bi-hourglass-split"
        />
        <x-dashboard.stat-tile
            label="Jatuh Tempo Hari Ini"
            :value="number_format($billing['jatuhTempoHariIni'], 0, ',', '.')"
            meta="Perlu penagihan hari ini"
            tone="danger"
            icon="bi-calendar-event"
        />
        <x-dashboard.stat-tile
            label="Total Piutang Aktif"
            :value="'Rp ' . number_format($billing['piutangAktif'], 0, ',', '.')"
            meta="Sisa tagihan belum dibayar"
            tone="primary"
            icon="bi-wallet2"
        />
    </div>

    <article class="dash-card dash-chart-card">
        <div class="dash-card-head">
            <div>
                <h3>Komposisi pendapatan</h3>
                <p>Pendapatan pelanggan retail/internal dibanding penjualan bandwidth mitra.</p>
            </div>
            <div class="dash-range" role="group" aria-label="Filter periode pendapatan">
                <button type="button" class="dash-range-btn is-active" data-revenue-range="month" aria-pressed="true">Bulan Ini</button>
                <button type="button" class="dash-range-btn" data-revenue-range="3m" aria-pressed="false">3 Bulan</button>
                <button type="button" class="dash-range-btn" data-revenue-range="ytd" aria-pressed="false">YTD</button>
            </div>
        </div>
        <div class="dash-chart-wrap">
            <canvas id="revenueComparisonChart" role="img" aria-label="Grafik pendapatan retail dan wholesale"></canvas>
        </div>
        <p class="dash-note">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            Penjualan bandwidth mitra masih memakai data contoh. TODO: sambungkan ke endpoint ledger wholesale.
        </p>
    </article>
</section>
