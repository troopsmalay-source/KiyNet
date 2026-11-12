@extends('layouts.kinet')

@section('page-title', 'Langganan')

@push('styles')
    <link href="{{ asset('assets/css/langganan-management.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <div class="langganan-module">
        <nav class="dashboard-breadcrumb langganan-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Beranda</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Langganan</span>
        </nav>

        <section class="langganan-page-card">
            <x-page-heading title="Langganan" description="Kelola paket berlangganan, masa layanan, dan status koneksi pelanggan.">
                <a class="btn btn-primary" href="{{ route('admin.langganan.create') }}"><i class="bi bi-plus-lg"></i> Tambah langganan</a>
            </x-page-heading>

            <section class="langganan-summary-grid" aria-label="Ringkasan langganan">
                <button class="langganan-summary-card is-selected" type="button" data-langganan-status-card="" aria-pressed="true">
                    <span class="langganan-summary-icon is-blue"><i class="bi bi-router"></i></span>
                    <span class="langganan-summary-copy"><span>Total langganan</span><strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong><small>Semua langganan tercatat</small></span>
                    <i class="bi bi-arrow-up-right langganan-summary-arrow" aria-hidden="true"></i>
                </button>
                <button class="langganan-summary-card" type="button" data-langganan-status-card="aktif" aria-pressed="false">
                    <span class="langganan-summary-icon is-green"><i class="bi bi-check-circle"></i></span>
                    <span class="langganan-summary-copy"><span>Aktif</span><strong>{{ number_format($summary['active'], 0, ',', '.') }}</strong><small>Layanan sedang berjalan</small></span>
                    <i class="bi bi-arrow-up-right langganan-summary-arrow" aria-hidden="true"></i>
                </button>
                <button class="langganan-summary-card" type="button" data-langganan-status-card="menunggu" aria-pressed="false">
                    <span class="langganan-summary-icon is-orange"><i class="bi bi-hourglass-split"></i></span>
                    <span class="langganan-summary-copy"><span>Menunggu</span><strong>{{ number_format($summary['waiting'], 0, ',', '.') }}</strong><small>Menunggu aktivasi</small></span>
                    <i class="bi bi-arrow-up-right langganan-summary-arrow" aria-hidden="true"></i>
                </button>
                <button class="langganan-summary-card" type="button" data-langganan-status-card="ditangguhkan" aria-pressed="false">
                    <span class="langganan-summary-icon is-gray"><i class="bi bi-pause-circle"></i></span>
                    <span class="langganan-summary-copy"><span>Ditangguhkan</span><strong>{{ number_format($summary['paused'], 0, ',', '.') }}</strong><small>Layanan dihentikan sementara</small></span>
                    <i class="bi bi-arrow-up-right langganan-summary-arrow" aria-hidden="true"></i>
                </button>
            </section>

            <section class="page-panel langganan-table-panel" aria-label="Daftar langganan">
                <div class="langganan-table-heading">
                    <div>
                        <h3>Daftar langganan</h3>
                        <p>Cari kode/nama pelanggan, paket, atau username PPPoE.</p>
                    </div>
                    <label class="langganan-status-filter">
                        <span>Status langganan</span>
                        <select class="form-select" id="langgananStatusFilter" aria-label="Filter status langganan">
                            <option value="">Semua status</option>
                            <option value="menunggu">Menunggu</option>
                            <option value="aktif">Aktif</option>
                            <option value="ditangguhkan">Ditangguhkan</option>
                            <option value="berhenti">Berhenti</option>
                        </select>
                    </label>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle js-data-table langganan-table" data-server-side="true" data-table-type="langganan" data-ajax-url="{{ route('admin.langganan.index') }}">
                        <thead><tr><th>Kode pelanggan</th><th>Nama pelanggan</th><th>Paket</th><th>Aktivasi</th><th>Jatuh tempo</th><th>Harga/bulan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>
        </section>
    </div>
@endsection
