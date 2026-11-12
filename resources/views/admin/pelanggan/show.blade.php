@extends('layouts.kinet')

@section('page-title', 'Detail Pelanggan')

@section('content')
    <x-page-heading :title="$pelanggan->nama_pelanggan" description="Detail informasi dan status layanan pelanggan.">
        <a class="btn btn-outline-secondary" href="{{ route('admin.pelanggan.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
        <a class="btn btn-primary" href="{{ route('admin.pelanggan.edit', $pelanggan) }}"><i class="bi bi-pencil"></i> Edit pelanggan</a>
    </x-page-heading>

    <section class="page-panel">
        <div class="row g-0">
            <div class="col-md-6 p-4 border-bottom">
                <span class="d-block mb-1 text-secondary small">Kode pelanggan</span>
                <strong class="table-primary-text">{{ $pelanggan->kode_pelanggan }}</strong>
            </div>
            <div class="col-md-6 p-4 border-bottom">
                <span class="d-block mb-1 text-secondary small">Status layanan</span>
                @php
                    $statusTone = match ($pelanggan->status) {
                        'aktif' => 'is-active',
                        'isolir' => 'is-danger',
                        'calon' => 'is-warning',
                        default => 'is-inactive',
                    };
                @endphp
                <span class="status-badge {{ $statusTone }}">{{ $pelanggan->status }}</span>
            </div>
            <div class="col-md-6 p-4 border-bottom">
                <span class="d-block mb-1 text-secondary small">Nomor HP</span>
                <strong>{{ $pelanggan->no_hp ?: '—' }}</strong>
            </div>
            <div class="col-md-6 p-4 border-bottom">
                <span class="d-block mb-1 text-secondary small">Email</span>
                <strong>{{ $pelanggan->email ?: '—' }}</strong>
            </div>
            <div class="col-12 p-4">
                <span class="d-block mb-1 text-secondary small">Alamat pemasangan</span>
                <strong>{{ $pelanggan->alamat_pasang }}</strong>
            </div>
        </div>
    </section>
@endsection
