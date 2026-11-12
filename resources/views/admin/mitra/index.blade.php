@extends('layouts.kinet')

@section('page-title', 'Data Mitra')

@push('styles')
    <link href="{{ asset('assets/css/mitra-management.css') }}?v=3" rel="stylesheet">
@endpush

@section('content')
    <div class="mitra-module">
        <nav class="dashboard-breadcrumb mitra-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Beranda</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Data Mitra</span>
        </nav>
        <section class="mitra-page-card">
            <x-page-heading title="Data Mitra" description="Kelola mitra, informasi kontak, dan pelanggan yang mereka tangani.">
                <a class="btn btn-primary" href="{{ route('admin.mitra.create') }}"><i class="bi bi-person-plus"></i> Tambah mitra</a>
            </x-page-heading>

            <section class="mitra-summary-grid" aria-label="Ringkasan data mitra">
                <button class="mitra-summary-card is-selected" type="button" data-mitra-status-card="" aria-pressed="true">
                    <span class="mitra-summary-icon is-blue"><i class="bi bi-people"></i></span>
                    <span class="mitra-summary-copy"><span>Total mitra</span><strong>{{ number_format($summary['total'], 0, ',', '.') }}</strong><small>Semua mitra terdaftar</small></span>
                    <i class="bi bi-arrow-up-right mitra-summary-arrow" aria-hidden="true"></i>
                </button>
                <button class="mitra-summary-card" type="button" data-mitra-status-card="aktif" aria-pressed="false">
                    <span class="mitra-summary-icon is-green"><i class="bi bi-person-check"></i></span>
                    <span class="mitra-summary-copy"><span>Mitra aktif</span><strong>{{ number_format($summary['active'], 0, ',', '.') }}</strong><small>Dapat mengelola pelanggan</small></span>
                    <i class="bi bi-arrow-up-right mitra-summary-arrow" aria-hidden="true"></i>
                </button>
                <button class="mitra-summary-card" type="button" data-mitra-status-card="nonaktif" aria-pressed="false">
                    <span class="mitra-summary-icon is-gray"><i class="bi bi-person-dash"></i></span>
                    <span class="mitra-summary-copy"><span>Mitra nonaktif</span><strong>{{ number_format($summary['inactive'], 0, ',', '.') }}</strong><small>Akses pengelolaan dihentikan</small></span>
                    <i class="bi bi-arrow-up-right mitra-summary-arrow" aria-hidden="true"></i>
                </button>
                <div class="mitra-summary-card mitra-summary-total">
                    <span class="mitra-summary-icon is-purple"><i class="bi bi-person-lines-fill"></i></span>
                    <span class="mitra-summary-copy"><span>Pelanggan dikelola</span><strong>{{ number_format($summary['customers'], 0, ',', '.') }}</strong><small>Terhubung ke mitra</small></span>
                </div>
            </section>

            <section class="page-panel mitra-table-panel" aria-label="Daftar mitra">
                <div class="mitra-table-heading">
                    <div>
                        <h3>Daftar mitra</h3>
                        <p>Cari mitra berdasarkan kode, nama, penanggung jawab, atau nomor HP.</p>
                    </div>
                    <label class="mitra-status-filter">
                        <span>Status mitra</span>
                        <select class="form-select" id="mitraStatusFilter" aria-label="Filter status mitra">
                            <option value="">Semua status</option>
                            <option value="aktif">Aktif</option>
                            <option value="nonaktif">Nonaktif</option>
                        </select>
                    </label>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle js-data-table mitra-table" data-server-side="true" data-table-type="mitra" data-ajax-url="{{ route('admin.mitra.index') }}">
                        <thead><tr><th>Kode mitra</th><th>Nama mitra</th><th>Penanggung jawab</th><th>Nomor HP</th><th>Email</th><th>Pelanggan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>
        </section>
    </div>
@endsection
