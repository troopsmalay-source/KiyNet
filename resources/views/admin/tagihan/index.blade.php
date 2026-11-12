@extends('layouts.kinet')

@section('page-title', 'Tagihan')

@push('styles')
    <link href="{{ asset('assets/css/tagihan-management.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <div class="tagihan-module">
        <nav class="dashboard-breadcrumb tagihan-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Beranda</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Tagihan</span>
        </nav>

        <section class="tagihan-page-card">
            <x-page-heading title="Tagihan" description="Buat invoice langganan, pantau jatuh tempo, dan catat pembayaran pelanggan.">
                <a class="btn btn-primary" href="{{ route('admin.tagihan.create') }}"><i class="bi bi-plus-lg"></i> Buat tagihan</a>
            </x-page-heading>

            <section class="tagihan-summary-grid" aria-label="Ringkasan tagihan">
                <button class="tagihan-summary-card is-selected" type="button" data-tagihan-status-card="" aria-pressed="true">
                    <span class="tagihan-summary-icon is-blue"><i class="bi bi-receipt"></i></span>
                    <span class="tagihan-summary-copy"><span>Total tagihan</span><strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong><small>Semua invoice tercatat</small></span>
                </button>
                <button class="tagihan-summary-card" type="button" data-tagihan-status-card="belum_bayar" aria-pressed="false">
                    <span class="tagihan-summary-icon is-orange"><i class="bi bi-hourglass-split"></i></span>
                    <span class="tagihan-summary-copy"><span>Belum dibayar</span><strong>{{ number_format($summary['unpaid'], 0, ',', '.') }}</strong><small>Menunggu pembayaran</small></span>
                </button>
                <button class="tagihan-summary-card" type="button" data-tagihan-status-card="sebagian" aria-pressed="false">
                    <span class="tagihan-summary-icon is-purple"><i class="bi bi-pie-chart"></i></span>
                    <span class="tagihan-summary-copy"><span>Dibayar sebagian</span><strong>{{ number_format($summary['partial'], 0, ',', '.') }}</strong><small>Masih ada sisa tagihan</small></span>
                </button>
                <button class="tagihan-summary-card" type="button" data-tagihan-status-card="lunas" aria-pressed="false">
                    <span class="tagihan-summary-icon is-green"><i class="bi bi-check-circle"></i></span>
                    <span class="tagihan-summary-copy"><span>Lunas</span><strong>{{ number_format($summary['paid'], 0, ',', '.') }}</strong><small>Pembayaran telah selesai</small></span>
                </button>
            </section>

            <section class="page-panel tagihan-table-panel" aria-label="Daftar tagihan">
                <div class="tagihan-table-heading">
                    <div>
                        <h3>Daftar tagihan</h3>
                        <p>Cari nomor invoice, pelanggan, atau paket internet.</p>
                    </div>
                    <label class="tagihan-status-filter">
                        <span>Status tagihan</span>
                        <select class="form-select" id="tagihanStatusFilter" aria-label="Filter status tagihan">
                            <option value="">Semua status</option>
                            <option value="draft">Draft</option>
                            <option value="belum_bayar">Belum dibayar</option>
                            <option value="sebagian">Dibayar sebagian</option>
                            <option value="lunas">Lunas</option>
                            <option value="dibatalkan">Dibatalkan</option>
                        </select>
                    </label>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle js-data-table tagihan-table" data-server-side="true" data-table-type="tagihan" data-ajax-url="{{ route('admin.tagihan.index') }}">
                        <thead><tr><th>No. invoice</th><th>Kode pelanggan</th><th>Nama pelanggan</th><th>Paket</th><th>Periode</th><th>Jatuh tempo</th><th>Total</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>
        </section>
    </div>
@endsection
