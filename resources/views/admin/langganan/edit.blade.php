@extends('layouts.kinet')

@section('page-title', 'Edit Langganan')

@push('styles')
    <link href="{{ asset('assets/css/langganan-management.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <div class="langganan-module">
        <nav class="dashboard-breadcrumb langganan-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Beranda</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <a href="{{ route('admin.langganan.index') }}">Langganan</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Edit Langganan</span>
        </nav>
        <x-page-heading title="Edit langganan" description="Perbarui periode, paket, status, dan pengaturan koneksi.">
            <a class="btn btn-outline-secondary" href="{{ route('admin.langganan.index') }}"><i class="bi bi-arrow-left"></i> Kembali ke langganan</a>
        </x-page-heading>

        <form method="POST" action="{{ route('admin.langganan.update', $langganan) }}" class="page-panel langganan-form-panel">
            @csrf
            @method('PUT')
            <div class="langganan-form-intro">
                <h3>Informasi langganan</h3>
                <p>Harga snapshot saat ini: <strong>Rp {{ number_format((float) $langganan->harga_snapshot, 0, ',', '.') }}</strong>/bulan. Harga diperbarui hanya jika paket diganti.</p>
            </div>
            <div class="langganan-form-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="pelanggan_id">Pelanggan <span class="text-danger">*</span></label>
                        <select class="form-select" id="pelanggan_id" name="pelanggan_id" required>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('pelanggan_id', $langganan->pelanggan_id) === (string) $customer->id)>{{ $customer->kode_pelanggan }} — {{ $customer->nama_pelanggan }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="paket_internet_id">Paket internet <span class="text-danger">*</span></label>
                        <select class="form-select" id="paket_internet_id" name="paket_internet_id" required>
                            @foreach ($packages as $package)
                                <option value="{{ $package->id }}" @selected((string) old('paket_internet_id', $langganan->paket_internet_id) === (string) $package->id)>{{ $package->nama_paket }} · {{ $package->download_mbps ?? '—' }} Mbps · Rp {{ number_format((float) $package->harga, 0, ',', '.') }}/bln{{ $package->is_active ? '' : ' (nonaktif)' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tanggal_aktivasi">Tanggal aktivasi <span class="text-danger">*</span></label>
                        <input class="form-control" id="tanggal_aktivasi" type="date" name="tanggal_aktivasi" value="{{ old('tanggal_aktivasi', $langganan->tanggal_aktivasi?->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tanggal_jatuh_tempo">Jatuh tempo berikutnya</label>
                        <input class="form-control" id="tanggal_jatuh_tempo" type="date" name="tanggal_jatuh_tempo" value="{{ old('tanggal_jatuh_tempo', $langganan->tanggal_jatuh_tempo?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="tanggal_berakhir">Tanggal berakhir</label>
                        <input class="form-control" id="tanggal_berakhir" type="date" name="tanggal_berakhir" value="{{ old('tanggal_berakhir', $langganan->tanggal_berakhir?->format('Y-m-d')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="masa_tenggang_hari">Masa tenggang (hari) <span class="text-danger">*</span></label>
                        <input class="form-control" id="masa_tenggang_hari" type="number" min="0" max="30" name="masa_tenggang_hari" value="{{ old('masa_tenggang_hari', $langganan->masa_tenggang_hari) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="status">Status langganan <span class="text-danger">*</span></label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="menunggu" @selected(old('status', $langganan->status) === 'menunggu')>Menunggu aktivasi</option>
                            <option value="aktif" @selected(old('status', $langganan->status) === 'aktif')>Aktif</option>
                            <option value="ditangguhkan" @selected(old('status', $langganan->status) === 'ditangguhkan')>Ditangguhkan</option>
                            <option value="berhenti" @selected(old('status', $langganan->status) === 'berhenti')>Berhenti</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pppoe_username">Username PPPoE</label>
                        <input class="form-control" id="pppoe_username" name="pppoe_username" value="{{ old('pppoe_username', $langganan->pppoe_username) }}" maxlength="255" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="ip_static">IP statis</label>
                        <input class="form-control" id="ip_static" name="ip_static" value="{{ old('ip_static', $langganan->ip_static) }}" maxlength="45" placeholder="Opsional">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="profile_router">Profil router</label>
                        <input class="form-control" id="profile_router" name="profile_router" value="{{ old('profile_router', $langganan->profile_router) }}" maxlength="255" placeholder="Contoh: home-20m">
                    </div>
                </div>
                <div class="langganan-form-footer">
                    <a class="btn btn-outline-secondary" href="{{ route('admin.langganan.index') }}">Batal</a>
                    <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Simpan perubahan</button>
                </div>
            </div>
        </form>
    </div>
@endsection
