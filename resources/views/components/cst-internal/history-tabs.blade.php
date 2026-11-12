@props(['customer'])

@php
    $invoiceTone = fn (string $status) => match ($status) {
        'Lunas' => 'green',
        'Menunggak' => 'red',
        'Terbit' => 'blue',
        default => 'gray',
    };
    $ticketTone = fn (string $status) => match ($status) {
        'Resolved' => 'green',
        'In Progress' => 'blue',
        'Open' => 'red',
        default => 'gray',
    };
    $isolationTone = fn (string $status) => match (true) {
        str_contains($status, 'Dipulihkan') => 'green',
        str_contains($status, 'Gagal'), str_contains($status, 'Ditunda'), str_contains($status, 'Ditangguhkan') => 'amber',
        str_contains($status, 'Diisolasi') => 'red',
        default => 'gray',
    };
    $tabs = [
        ['id' => 'invoice', 'label' => 'Riwayat Invoice', 'icon' => 'bi-receipt', 'count' => count($customer['invoices'])],
        ['id' => 'payment', 'label' => 'Riwayat Pembayaran', 'icon' => 'bi-credit-card', 'count' => count($customer['payments'])],
        ['id' => 'package', 'label' => 'Perubahan Paket', 'icon' => 'bi-arrow-up-right-circle', 'count' => count($customer['package_changes'])],
        ['id' => 'isolation', 'label' => 'Isolasi &amp; Pemulihan', 'icon' => 'bi-shield-exclamation', 'count' => count($customer['isolation_logs'])],
        ['id' => 'ticket', 'label' => 'Tiket Gangguan', 'icon' => 'bi-life-preserver', 'count' => count($customer['tickets'])],
    ];
@endphp

<article class="cst-card" aria-labelledby="cst-history-heading">
    <div class="cst-tabs-head">
        <div>
            <h3 id="cst-history-heading">Riwayat &amp; Aktivitas</h3>
            <p>Tagihan, pembayaran, paket, isolasi, dan tiket gangguan pelanggan.</p>
        </div>
    </div>

    <ul class="nav nav-tabs cst-nav-tabs" role="tablist">
        @foreach ($tabs as $tab)
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="cst-{{ $tab['id'] }}-tab" type="button" role="tab"
                    data-bs-toggle="tab" data-bs-target="#cst-{{ $tab['id'] }}-pane"
                    aria-controls="cst-{{ $tab['id'] }}-pane" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                    <i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i>
                    {!! $tab['label'] !!}
                    <span class="cst-count">{{ $tab['count'] }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content cst-tab-content">
        <div class="tab-pane fade show active" id="cst-invoice-pane" role="tabpanel" aria-labelledby="cst-invoice-tab" tabindex="0">
            @if ($customer['invoices'])
                <div class="table-responsive">
                    <table class="table cst-mini-table">
                        <thead>
                            <tr><th scope="col">No. Invoice</th><th scope="col">Periode</th><th scope="col">Jatuh Tempo</th><th scope="col" class="text-end">Nominal</th><th scope="col">Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($customer['invoices'] as $invoice)
                                <tr>
                                    <td><span class="cst-mono">{{ $invoice['no'] }}</span></td>
                                    <td>{{ $invoice['periode'] }}</td>
                                    <td>{{ $invoice['jatuh_tempo'] }}</td>
                                    <td class="text-end">Rp {{ number_format($invoice['nominal'], 0, ',', '.') }}</td>
                                    <td><span class="cst-pill cst-pill--{{ $invoiceTone($invoice['status']) }}">{{ $invoice['status'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="cst-mini-empty"><i class="bi bi-receipt" aria-hidden="true"></i> Belum ada invoice untuk pelanggan ini.</div>
            @endif
        </div>

        <div class="tab-pane fade" id="cst-payment-pane" role="tabpanel" aria-labelledby="cst-payment-tab" tabindex="0">
            @if ($customer['payments'])
                <div class="table-responsive">
                    <table class="table cst-mini-table">
                        <thead>
                            <tr><th scope="col">Tanggal Bayar</th><th scope="col">Metode</th><th scope="col">Referensi</th><th scope="col" class="text-end">Nominal</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($customer['payments'] as $payment)
                                <tr>
                                    <td>{{ $payment['tanggal'] }}</td>
                                    <td><span class="cst-pill cst-pill--blue">{{ $payment['metode'] }}</span></td>
                                    <td><span class="cst-mono">{{ $payment['referensi'] }}</span></td>
                                    <td class="text-end">Rp {{ number_format($payment['nominal'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="cst-mini-empty"><i class="bi bi-credit-card" aria-hidden="true"></i> Belum ada pembayaran tercatat.</div>
            @endif
        </div>

        <div class="tab-pane fade" id="cst-package-pane" role="tabpanel" aria-labelledby="cst-package-tab" tabindex="0">
            @if ($customer['package_changes'])
                <div class="table-responsive">
                    <table class="table cst-mini-table">
                        <thead>
                            <tr><th scope="col">Tanggal</th><th scope="col">Paket Lama</th><th scope="col">Paket Baru</th><th scope="col">Petugas</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($customer['package_changes'] as $change)
                                <tr>
                                    <td>{{ $change['tanggal'] }}</td>
                                    <td>{{ $change['from'] }}</td>
                                    <td><strong style="color: #1d4ed8; font-weight: 650;">{{ $change['to'] }}</strong></td>
                                    <td>
                                        {{ $change['by'] }}
                                        <span class="cst-sub">{{ $change['note'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="cst-mini-empty"><i class="bi bi-arrow-up-right-circle" aria-hidden="true"></i> Belum ada perubahan paket.</div>
            @endif
        </div>

        <div class="tab-pane fade" id="cst-isolation-pane" role="tabpanel" aria-labelledby="cst-isolation-tab" tabindex="0">
            @if ($customer['isolation_logs'])
                <div class="table-responsive">
                    <table class="table cst-mini-table">
                        <thead>
                            <tr><th scope="col">Timestamp</th><th scope="col">Tipe</th><th scope="col">Status</th><th scope="col">Respon Gateway</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($customer['isolation_logs'] as $isolation)
                                <tr>
                                    <td>{{ $isolation['at'] }}</td>
                                    <td>{{ $isolation['type'] }}</td>
                                    <td><span class="cst-pill cst-pill--{{ $isolationTone($isolation['status']) }}">{{ $isolation['status'] }}</span></td>
                                    <td>
                                        <span class="cst-mono">{{ $isolation['response'] }}</span>
                                        <span class="cst-sub">{{ $isolation['note'] }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="cst-mini-empty"><i class="bi bi-shield-check" aria-hidden="true"></i> Tidak ada riwayat isolasi. Layanan berjalan normal.</div>
            @endif
        </div>

        <div class="tab-pane fade" id="cst-ticket-pane" role="tabpanel" aria-labelledby="cst-ticket-tab" tabindex="0">
            @if ($customer['tickets'])
                <div class="table-responsive">
                    <table class="table cst-mini-table">
                        <thead>
                            <tr><th scope="col">No. Tiket</th><th scope="col">Laporan Keluhan</th><th scope="col">Dibuat</th><th scope="col">Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($customer['tickets'] as $ticket)
                                <tr>
                                    <td><span class="cst-mono">{{ $ticket['no'] }}</span></td>
                                    <td>
                                        {{ $ticket['issue'] }}
                                        @if ($ticket['closed_at'])
                                            <span class="cst-sub">Selesai {{ $ticket['closed_at'] }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $ticket['created_at'] }}</td>
                                    <td><span class="cst-pill cst-pill--{{ $ticketTone($ticket['status']) }}">{{ $ticket['status'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="cst-mini-empty"><i class="bi bi-life-preserver" aria-hidden="true"></i> Tidak ada tiket gangguan.</div>
            @endif
        </div>
    </div>
</article>
