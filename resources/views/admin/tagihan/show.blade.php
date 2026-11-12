@extends('layouts.kinet')

@section('page-title', 'Detail Tagihan')

@push('styles')
    <link href="{{ asset('assets/css/tagihan-management.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <div class="tagihan-module">
        <nav class="dashboard-breadcrumb tagihan-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Beranda</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <a href="{{ route('admin.tagihan.index') }}">Tagihan</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">{{ $tagihan->no_invoice }}</span>
        </nav>
        <x-page-heading title="Detail tagihan" description="Periksa rincian invoice dan riwayat pembayaran pelanggan.">
            <a class="btn btn-outline-secondary" href="{{ route('admin.tagihan.index') }}"><i class="bi bi-arrow-left"></i> Kembali ke tagihan</a>
        </x-page-heading>

        <section class="page-panel tagihan-detail-panel">
            <div class="tagihan-detail-heading">
                <div>
                    <span class="tagihan-detail-label">Nomor invoice</span>
                    <h3>{{ $tagihan->no_invoice }}</h3>
                    <p>{{ $tagihan->pelanggan->kode_pelanggan }} · {{ $tagihan->pelanggan->nama_pelanggan }}</p>
                </div>
                <span class="status-badge {{ $tagihan->status === 'lunas' ? 'is-active' : ($tagihan->status === 'dibatalkan' ? 'is-inactive' : 'is-warning') }}">
                    {{ ['belum_bayar' => 'Belum dibayar', 'sebagian' => 'Dibayar sebagian', 'lunas' => 'Lunas', 'draft' => 'Draft', 'dibatalkan' => 'Dibatalkan'][$tagihan->status] ?? $tagihan->status }}
                </span>
            </div>
            <div class="tagihan-facts">
                <div><span>Periode</span><strong>{{ \Illuminate\Support\Carbon::createFromFormat('!Y-m', $tagihan->periode)->locale('id')->translatedFormat('F Y') }}</strong></div>
                <div><span>Tanggal invoice</span><strong>{{ $tagihan->tanggal_tagihan->format('d M Y') }}</strong></div>
                <div><span>Jatuh tempo</span><strong>{{ $tagihan->jatuh_tempo->format('d M Y') }}</strong></div>
                <div><span>Masa tenggang sampai</span><strong>{{ $tagihan->akhir_masa_tenggang->format('d M Y') }}</strong></div>
                <div><span>Paket</span><strong>{{ $tagihan->langganan->paketInternet->nama_paket }}</strong></div>
            </div>
            <div class="table-responsive">
                <table class="table align-middle tagihan-line-table">
                    <thead><tr><th>Deskripsi</th><th class="text-end">Qty</th><th class="text-end">Harga</th><th class="text-end">Subtotal</th></tr></thead>
                    <tbody>
                        @foreach ($tagihan->detail as $detail)
                            <tr><td>{{ $detail->deskripsi }}</td><td class="text-end">{{ number_format($detail->qty, 0, ',', '.') }}</td><td class="text-end">Rp {{ number_format((float) $detail->harga_satuan, 0, ',', '.') }}</td><td class="text-end">Rp {{ number_format((float) $detail->subtotal, 0, ',', '.') }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="tagihan-totals">
                <div><span>Subtotal</span><strong>Rp {{ number_format((float) $tagihan->subtotal, 0, ',', '.') }}</strong></div>
                <div><span>Diskon</span><strong>− Rp {{ number_format((float) $tagihan->diskon, 0, ',', '.') }}</strong></div>
                <div><span>Denda</span><strong>Rp {{ number_format((float) $tagihan->denda, 0, ',', '.') }}</strong></div>
                <div class="is-total"><span>Total tagihan</span><strong>Rp {{ number_format((float) $tagihan->total_tagihan, 0, ',', '.') }}</strong></div>
                <div><span>Total dibayar</span><strong>Rp {{ number_format((float) $tagihan->total_dibayar, 0, ',', '.') }}</strong></div>
                <div class="is-remaining"><span>Sisa tagihan</span><strong>Rp {{ number_format(max(0, (float) $tagihan->total_tagihan - (float) $tagihan->total_dibayar), 0, ',', '.') }}</strong></div>
            </div>
        </section>

        <section class="page-panel tagihan-history-panel">
            <div class="tagihan-form-intro">
                <h3>Riwayat pembayaran</h3>
                <p>Pembayaran manual yang dicatat akan langsung diverifikasi dan mengurangi sisa tagihan.</p>
            </div>
            @if ($tagihan->pembayaran->isNotEmpty())
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Tanggal</th><th>Metode</th><th>No. referensi</th><th>Status</th><th class="text-end">Nominal</th></tr></thead>
                        <tbody>
                            @foreach ($tagihan->pembayaran as $payment)
                                <tr><td>{{ $payment->dibayar_at?->format('d M Y H:i') ?? '—' }}</td><td>Transfer manual</td><td>{{ $payment->nomor_referensi ?: '—' }}</td><td><span class="status-badge is-active">Berhasil</span></td><td class="text-end">Rp {{ number_format((float) $payment->nominal, 0, ',', '.') }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="tagihan-empty-history">Belum ada pembayaran untuk invoice ini.</p>
            @endif
        </section>

        @if (!in_array($tagihan->status, ['lunas', 'dibatalkan'], true))
            <section class="page-panel tagihan-payment-panel">
                <div class="tagihan-form-intro">
                    <h3>Catat pembayaran</h3>
                    <p>Sisa tagihan saat ini Rp {{ number_format(max(0, (float) $tagihan->total_tagihan - (float) $tagihan->total_dibayar), 0, ',', '.') }}.</p>
                </div>
                <form method="POST" action="{{ route('admin.tagihan.pembayaran.store', $tagihan) }}" class="tagihan-form-body">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="nominal">Nominal pembayaran (Rp) <span class="text-danger">*</span></label>
                            <input class="form-control" id="nominal" type="number" name="nominal" min="1" step="1" max="{{ (float) $tagihan->total_tagihan - (float) $tagihan->total_dibayar }}" value="{{ old('nominal') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="nomor_referensi">Nomor referensi</label>
                            <input class="form-control" id="nomor_referensi" name="nomor_referensi" maxlength="120" value="{{ old('nomor_referensi') }}" placeholder="Opsional">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="dibayar_at">Tanggal pembayaran <span class="text-danger">*</span></label>
                            <input class="form-control" id="dibayar_at" type="datetime-local" name="dibayar_at" value="{{ old('dibayar_at', now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                    </div>
                    <div class="tagihan-form-footer">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Simpan pembayaran</button>
                    </div>
                </form>
            </section>
        @endif
    </div>
@endsection
