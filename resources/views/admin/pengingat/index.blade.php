@extends('layouts.kinet')

@section('page-title', 'Pengingat Tagihan')

@push('styles')
    <link href="{{ asset('assets/css/pengingat-management.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <div class="pengingat-module">
        <nav class="dashboard-breadcrumb pengingat-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Beranda</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Pengingat Tagihan</span>
        </nav>

        <section class="pengingat-page-card">
            <x-page-heading title="Pengingat Tagihan" description="Pantau pelanggan dan tagihan yang mendekati tanggal jatuh tempo.">
                <span class="pengingat-today"><i class="bi bi-calendar3"></i> Hari ini, {{ \Illuminate\Support\Carbon::parse($today)->locale('id')->translatedFormat('d F Y') }}</span>
            </x-page-heading>

            <section class="pengingat-tabs" role="tablist" aria-label="Filter waktu jatuh tempo">
                <button class="pengingat-tab is-selected" type="button" role="tab" aria-selected="true" data-pengingat-days="">
                    <span>Semua pelanggan</span><strong>{{ number_format($counts['all'], 0, ',', '.') }}</strong>
                </button>
                <button class="pengingat-tab" type="button" role="tab" aria-selected="false" data-pengingat-days="7">
                    <span>7 hari lagi jatuh tempo</span><strong>{{ number_format($counts[7], 0, ',', '.') }}</strong>
                </button>
                <button class="pengingat-tab" type="button" role="tab" aria-selected="false" data-pengingat-days="3">
                    <span>3 hari lagi jatuh tempo</span><strong>{{ number_format($counts[3], 0, ',', '.') }}</strong>
                </button>
                <button class="pengingat-tab" type="button" role="tab" aria-selected="false" data-pengingat-days="1">
                    <span>1 hari lagi jatuh tempo</span><strong>{{ number_format($counts[1], 0, ',', '.') }}</strong>
                </button>
            </section>

            <section class="page-panel pengingat-table-panel" aria-label="Data pengingat tagihan">
                <div class="pengingat-table-heading">
                    <div>
                        <h3 data-pengingat-heading>Semua pelanggan</h3>
                        <p>Pengingat jatuh tempo hanya menampilkan tagihan yang belum lunas atau dibayar sebagian.</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle js-data-table pengingat-table" data-server-side="true" data-table-type="pengingat" data-ajax-url="{{ route('admin.pengingat.index') }}">
                        <thead><tr><th>No. invoice</th><th>Kode pelanggan</th><th>Nama pelanggan</th><th>No. telepon</th><th>Paket</th><th>Jatuh tempo</th><th>Sisa tagihan</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>
        </section>
    </div>
@endsection
