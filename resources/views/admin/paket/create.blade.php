@extends('layouts.kinet')

@section('page-title', 'Tambah Paket')

@push('styles')
    <link href="{{ asset('assets/css/paket-management.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <div class="paket-module">
        <nav class="dashboard-breadcrumb paket-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Beranda</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <a href="{{ route('admin.paket.index') }}">Paket Internet</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Tambah Paket</span>
        </nav>
        <x-page-heading title="Tambah paket internet" description="Tentukan nama, kecepatan, harga, dan status paket baru.">
            <a class="btn btn-outline-secondary" href="{{ route('admin.paket.index') }}"><i class="bi bi-arrow-left"></i> Kembali ke paket</a>
        </x-page-heading>

        <form method="POST" action="{{ route('admin.paket.store') }}" class="page-panel paket-form-panel">
            @csrf
            <div class="paket-form-intro">
                <h3>Informasi paket</h3>
                <p>Kode paket akan dibuat otomatis setelah data disimpan.</p>
            </div>
            <div class="paket-form-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="nama_paket">Nama paket <span class="text-danger">*</span></label>
                        <input id="nama_paket" class="form-control" name="nama_paket" value="{{ old('nama_paket') }}" maxlength="100" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="harga">Harga per bulan (Rp) <span class="text-danger">*</span></label>
                        <input id="harga" class="form-control" type="number" min="0" step="0.01" name="harga" value="{{ old('harga') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="download_mbps">Kecepatan unduh (Mbps)</label>
                        <input id="download_mbps" class="form-control" type="number" min="1" name="download_mbps" value="{{ old('download_mbps') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="upload_mbps">Kecepatan unggah (Mbps)</label>
                        <input id="upload_mbps" class="form-control" type="number" min="1" name="upload_mbps" value="{{ old('upload_mbps') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="deskripsi">Deskripsi</label>
                        <textarea id="deskripsi" class="form-control" name="deskripsi" rows="3">{{ old('deskripsi') }}</textarea>
                    </div>
                    <div class="col-12">
                        <input type="hidden" name="is_active" value="0">
                        <div class="form-check">
                            <input class="form-check-input" id="is_active" type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')>
                            <label class="form-check-label" for="is_active">Paket aktif dan tersedia untuk pelanggan baru</label>
                        </div>
                    </div>
                </div>
                <div class="paket-form-footer">
                    <a class="btn btn-outline-secondary" href="{{ route('admin.paket.index') }}">Batal</a>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Simpan paket</button>
                </div>
            </div>
        </form>
    </div>
@endsection
