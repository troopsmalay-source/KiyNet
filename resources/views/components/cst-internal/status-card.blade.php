@props(['customer'])

<article class="cst-card" aria-labelledby="cst-control-heading">
    <header class="cst-card-head">
        <span class="cst-card-icon"><i class="bi bi-toggle-on" aria-hidden="true"></i></span>
        <div>
            <h3 id="cst-control-heading">Kontrol &amp; Log Status Pelanggan</h3>
            <p>Perubahan status dan jejak audit penanganan</p>
        </div>
    </header>
    <div class="cst-card-body">
        <div class="cst-status-panel">
            <div class="cst-status-now">
                <span>Status aktif saat ini</span>
                <x-cst-internal.status-badge :status="$customer['status']" size="lg" />
                <small>Berlaku sejak {{ $customer['status_since'] }}</small>
            </div>
            <button type="button" class="cst-btn cst-btn--primary" data-bs-toggle="modal" data-bs-target="#cstStatusModal">
                <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Ubah Status Pelanggan
            </button>
            <p class="cst-hint">Alasan atau catatan wajib diisi. Setiap perubahan dicatat pada timeline di bawah.</p>
        </div>

        <h4 class="cst-subhead">Timeline perubahan status</h4>
        @if (filled($customer['status_logs'] ?? []))
            <ul class="cst-timeline">
                @foreach ($customer['status_logs'] as $log)
                    <li>
                        <span class="cst-timeline-dot" aria-hidden="true"></span>
                        <div class="cst-timeline-head">
                            <x-cst-internal.status-badge :status="$log['from']" />
                            <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            <x-cst-internal.status-badge :status="$log['to']" />
                            <time datetime="{{ $log['at'] }}">{{ $log['at'] }}</time>
                        </div>
                        <p>{{ $log['note'] }}</p>
                        <small>Oleh {{ $log['by'] }}</small>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="cst-empty">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                Belum ada perubahan status yang tercatat.
            </div>
        @endif
    </div>
</article>
