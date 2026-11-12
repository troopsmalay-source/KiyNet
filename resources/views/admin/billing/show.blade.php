@extends('layouts.kinet')

@section('page-title', 'Invoice '.$invoice['no_invoice'])

@push('styles')
<link href="{{ asset('assets/css/billing.css') }}" rel="stylesheet">
<style>
    .invoice-paper {
        max-width: 840px;
        margin: 0 auto;
        background: var(--bill-card-bg, #fff);
        border: 1px solid var(--bill-border, #e4e8f0);
        border-radius: 12px;
        padding: 2.5rem;
        position: relative;
        overflow: hidden;
        box-shadow: var(--bill-shadow);
    }

    .invoice-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-28deg);
        font-size: 5rem;
        font-weight: 800;
        opacity: .08;
        pointer-events: none;
        letter-spacing: .2em;
        white-space: nowrap;
    }

    .invoice-watermark--paid { color: #10b981; }
    .invoice-watermark--unpaid { color: #ef4444; }

    .invoice-header img { max-height: 54px; }
    .invoice-legal { font-size: .75rem; color: #64748b; line-height: 1.5; }
    .invoice-label { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; font-weight: 600; }
    .invoice-table th, .invoice-table td { padding: .6rem .5rem; border-bottom: 1px solid #e2e8f0; }
    .invoice-table thead th { background: #f1f5f9; font-size: .72rem; text-transform: uppercase; color: #475569; }

    .qr-box {
        width: 96px;
        height: 96px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #f8fafc;
        font-size: .65rem;
        color: #64748b;
        text-align: center;
        padding: .3rem;
    }

    .audit-log { font-size: .82rem; }
    .audit-log li { padding: .35rem 0; border-bottom: 1px dashed #e2e8f0; }

    @media print {
        body { background: #fff !important; }
        .app-sidebar, .app-header, .sidebar-backdrop, .no-print { display: none !important; }
        .app-main { margin: 0 !important; padding: 0 !important; }
        .page-content { padding: 0 !important; }
        .invoice-paper { border: none; box-shadow: none; max-width: 100%; padding: 1rem; }
        @page { size: A4; margin: 12mm; }
    }
</style>
@endpush

@section('content')
<div class="billing-module no-print d-flex gap-2 mb-3">
    <a href="{{ route('admin.billing.index') }}" class="bill-btn"><i class="bi bi-arrow-left"></i> Kembali</a>
    <button class="bill-btn bill-btn--primary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak / Simpan PDF</button>
</div>

<div class="invoice-paper">
    <div class="invoice-watermark invoice-watermark--{{ $invoice['status'] === 'paid' ? 'paid' : 'unpaid' }}">
        {{ $invoice['status'] === 'paid' ? 'LUNAS' : 'UNPAID' }}
    </div>

    <div class="invoice-header d-flex justify-content-between align-items-start mb-4">
        <div>
            <img src="{{ asset('assets/images/kinet-logo.svg') }}" alt="kiynet" onerror="this.style.display='none'">
            <div class="invoice-legal mt-2">
                PT Kreasi Indokarya Network<br>
                Jl. Merdeka No. 88, Kota<br>
                Telp: 061-1234-5678 &middot; support@kiynet.id
            </div>
        </div>
        <div class="text-end">
            <div class="fs-4 fw-bold">INVOICE</div>
            <div class="font-monospace">{{ $invoice['no_invoice'] }}</div>
            <span class="bill-badge bill-badge--{{ $invoice['status'] }} mt-1">{{ $statusLabel }}</span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="invoice-label">Ditagihkan kepada</div>
            <div class="fw-semibold fs-5">{{ $invoice['nama'] }}</div>
            <div class="small text-muted">{{ $invoice['no_cst'] }}</div>
            <div class="small text-muted"><i class="bi bi-whatsapp"></i> {{ $invoice['no_hp'] }}</div>
        </div>
        <div class="col-md-6">
            <div class="row">
                <div class="col-6">
                    <div class="invoice-label">Tanggal Terbit</div>
                    <div>{{ \Carbon\Carbon::parse($invoice['tanggal_terbit'])->translatedFormat('d F Y') }}</div>
                    <div class="invoice-label mt-2">Jatuh Tempo</div>
                    <div>{{ \Carbon\Carbon::parse($invoice['tanggal_jatuh_tempo'])->translatedFormat('d F Y') }}</div>
                </div>
                <div class="col-6">
                    <div class="invoice-label">Rute Layanan</div>
                    <div class="small">
                        <i class="bi bi-router"></i> {{ $invoice['router'] ?? '-' }}<br>
                        PPPoE: <code>{{ $invoice['pppoe_username'] ?? '-' }}</code><br>
                        {{ $invoice['wilayah'] ?? '-' }} &middot; {{ $invoice['odp'] ?? '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <table class="table invoice-table mb-4">
        <thead>
            <tr><th>#</th><th>Deskripsi</th><th class="text-center">Qty</th><th class="text-end">Harga</th></tr>
        </thead>
        <tbody>
            @foreach ($invoice['items'] as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item['nama'] }}</td>
                    <td class="text-center">{{ $item['qty'] }}</td>
                    <td class="text-end">Rp {{ number_format($item['harga'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="row justify-content-between">
        <div class="col-md-5">
            <div class="qr-box mb-2">
                <i class="bi bi-qr-code fs-1"></i><br>Verifikasi keaslian invoice
            </div>
            <div class="small text-muted">Scan untuk verifikasi: <code class="small">{{ $qr_payload }}</code></div>
        </div>
        <div class="col-md-6">
            <table class="table table-sm">
                <tbody>
                    <tr><td>Subtotal</td><td class="text-end">Rp {{ number_format($invoice['subtotal'], 0, ',', '.') }}</td></tr>
                    @if ($invoice['diskon'] > 0)
                        <tr><td>Diskon / Prorata</td><td class="text-end">- Rp {{ number_format($invoice['diskon'], 0, ',', '.') }}</td></tr>
                    @endif
                    @if ($invoice['biaya_admin'] > 0)
                        <tr><td>Biaya Admin</td><td class="text-end">Rp {{ number_format($invoice['biaya_admin'], 0, ',', '.') }}</td></tr>
                    @endif
                    @if ($invoice['ppn'] > 0)
                        <tr><td>PPN 11%</td><td class="text-end">Rp {{ number_format($invoice['ppn'], 0, ',', '.') }}</td></tr>
                    @endif
                    <tr class="fs-5 fw-bold">
                        <td>Total</td>
                        <td class="text-end">Rp {{ number_format($invoice['total'], 0, ',', '.') }}</td>
                    </tr>
                    @if ($invoice['status'] === 'paid')
                        <tr><td>Dibayar via</td><td class="text-end">{{ ucfirst($invoice['metode_pembayaran']) }} &middot; {{ \Carbon\Carbon::parse($invoice['tanggal_bayar'])->translatedFormat('d M Y') }}</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @if (filled($invoice['audit_log'] ?? null))
        <hr>
        <div class="invoice-label mb-2">Riwayat / Audit Log</div>
        <ul class="list-unstyled audit-log mb-0">
            @foreach (array_reverse($invoice['audit_log']) as $log)
                <li>
                    <span class="font-monospace small text-muted">{{ $log['waktu'] }}</span>
                    <strong class="mx-2">{{ $log['aksi'] }}</strong>
                    <span>{{ $log['catatan'] }}</span>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="text-center invoice-legal mt-4">
        Invoice ini sah dan diproses oleh sistem kiynet eBilling. Terima kasih atas kepercayaan Anda.
    </div>
</div>
@endsection
