<section class="cst-card bill-metrics-card">
    <div class="bill-metrics">
    <div class="bill-metric bill-metric--default">
        <div class="bill-metric-label"><i class="bi bi-cash-stack me-1"></i>Piutang Aktif</div>
        <div class="bill-metric-value">Rp {{ number_format($stats['piutang_total'], 0, ',', '.') }}</div>
        <div class="bill-metric-sub">{{ $stats['piutang_count'] }} invoice belum lunas</div>
    </div>
    <div class="bill-metric bill-metric--info">
        <div class="bill-metric-label"><i class="bi bi-file-earmark-text me-1"></i>Terbit Bulan Ini</div>
        <div class="bill-metric-value">{{ $stats['month_count'] }} lembar</div>
        <div class="bill-metric-sub">Rp {{ number_format($stats['month_total'], 0, ',', '.') }}</div>
    </div>
    <div class="bill-metric bill-metric--ok">
        <div class="bill-metric-label"><i class="bi bi-check2-circle me-1"></i>Lunas</div>
        <div class="bill-metric-value bill-metric-value--ok">{{ $stats['paid_ratio'] }}%</div>
        <div class="bill-metric-sub">Rp {{ number_format($stats['paid_total'], 0, ',', '.') }}</div>
    </div>
    <div class="bill-metric bill-metric--danger">
        <div class="bill-metric-label"><i class="bi bi-exclamation-triangle me-1"></i>Overdue</div>
        <div class="bill-metric-value bill-metric-value--danger">{{ $stats['overdue_count'] }}</div>
        <div class="bill-metric-sub">Rp {{ number_format($stats['overdue_total'], 0, ',', '.') }}</div>
    </div>
    <div class="bill-metric bill-metric--warn">
        <div class="bill-metric-label"><i class="bi bi-hourglass-split me-1"></i>Pending</div>
        <div class="bill-metric-value bill-metric-value--warn">{{ $stats['pending_count'] }}</div>
        <div class="bill-metric-sub">Rp {{ number_format($stats['pending_total'], 0, ',', '.') }}</div>
    </div>
    </div>
</section>
