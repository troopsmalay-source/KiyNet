@extends('layouts.kinet')

@section('page-title', 'Paket Internet')

@push('styles')
    <link href="{{ asset('assets/css/paket-management.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <div class="paket-module">
        <nav class="dashboard-breadcrumb paket-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Beranda</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Paket Internet</span>
        </nav>

        <section class="paket-page-card">
            <x-page-heading title="Paket Internet" description="Kelola pilihan paket, kecepatan, dan harga layanan KiNet.">
                <a class="btn btn-primary" href="{{ route('admin.paket.create') }}"><i class="bi bi-plus-lg"></i> Tambah paket</a>
            </x-page-heading>

            <section class="paket-summary-grid" aria-label="Ringkasan paket internet">
                <button class="paket-summary-card is-selected" type="button" data-paket-status-card="" aria-pressed="true">
                    <span class="paket-summary-icon is-blue"><i class="bi bi-wifi"></i></span>
                    <span class="paket-summary-copy"><span>Total paket</span><strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong><small>Semua paket terdaftar</small></span>
                    <i class="bi bi-arrow-up-right paket-summary-arrow" aria-hidden="true"></i>
                </button>
                <button class="paket-summary-card" type="button" data-paket-status-card="1" aria-pressed="false">
                    <span class="paket-summary-icon is-green"><i class="bi bi-check-circle"></i></span>
                    <span class="paket-summary-copy"><span>Paket aktif</span><strong>{{ number_format($summary['active'], 0, ',', '.') }}</strong><small>Tersedia untuk pelanggan</small></span>
                    <i class="bi bi-arrow-up-right paket-summary-arrow" aria-hidden="true"></i>
                </button>
                <button class="paket-summary-card" type="button" data-paket-status-card="0" aria-pressed="false">
                    <span class="paket-summary-icon is-gray"><i class="bi bi-pause-circle"></i></span>
                    <span class="paket-summary-copy"><span>Paket nonaktif</span><strong>{{ number_format($summary['inactive'], 0, ',', '.') }}</strong><small>Tidak tersedia untuk pelanggan baru</small></span>
                    <i class="bi bi-arrow-up-right paket-summary-arrow" aria-hidden="true"></i>
                </button>
                <div class="paket-summary-card paket-summary-total">
                    <span class="paket-summary-icon is-purple"><i class="bi bi-person-lines-fill"></i></span>
                    <span class="paket-summary-copy"><span>Total langganan</span><strong>{{ number_format($summary['subscriptions'], 0, ',', '.') }}</strong><small>Langganan tercatat</small></span>
                </div>
            </section>

            <section class="page-panel paket-table-panel" aria-label="Daftar paket internet">
                <div class="paket-table-heading">
                    <div>
                        <h3>Daftar paket</h3>
                        <p>Cari paket berdasarkan kode, nama, atau deskripsi.</p>
                    </div>
                    <label class="paket-status-filter">
                        <span>Status paket</span>
                        <select class="form-select" id="paketStatusFilter" aria-label="Filter status paket">
                            <option value="">Semua status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>
                    </label>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle js-data-table paket-table" data-server-side="true" data-table-type="paket" data-ajax-url="{{ route('admin.paket.index') }}">
                        <thead><tr><th>Kode paket</th><th>Nama paket</th><th>Unduh</th><th>Unggah</th><th>Harga/bulan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>
        </section>
    </div>
@endsection
