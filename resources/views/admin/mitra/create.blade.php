@extends('layouts.kinet')

@section('page-title', 'Tambah Mitra')

@push('styles')
    <link href="{{ asset('assets/css/mitra-management.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <x-page-heading title="Tambah mitra" description="Daftarkan mitra baru untuk mengelola pelanggan KiNet.">
        <a class="btn btn-outline-secondary" href="{{ route('admin.mitra.index') }}"><i class="bi bi-arrow-left"></i> Kembali ke data mitra</a>
    </x-page-heading>

    <form method="POST" action="{{ route('admin.mitra.store') }}" class="page-panel mitra-form-panel">
        @csrf
        <div class="mitra-form-intro">
            <h3>Informasi mitra</h3>
            <p>Lengkapi identitas dan kontak utama. Kode mitra akan dibuat otomatis setelah data disimpan.</p>
        </div>
        <div class="mitra-form-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="nama_mitra">Nama mitra <span class="text-danger">*</span></label>
                    <input id="nama_mitra" class="form-control" name="nama_mitra" value="{{ old('nama_mitra') }}" maxlength="150" autocomplete="organization" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="nama_pic">Nama penanggung jawab</label>
                    <input id="nama_pic" class="form-control" name="nama_pic" value="{{ old('nama_pic') }}" maxlength="150" autocomplete="name">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="no_hp">Nomor HP</label>
                    <input id="no_hp" class="form-control" name="no_hp" value="{{ old('no_hp') }}" maxlength="25" inputmode="tel" autocomplete="tel">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="email">Email</label>
                    <input id="email" class="form-control" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="status">Status mitra</label>
                    <select id="status" class="form-select" name="status">
                        <option value="aktif" @selected(old('status', 'aktif') === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected(old('status') === 'nonaktif')>Nonaktif</option>
                    </select>
                    <p class="mitra-field-note">Mitra nonaktif tidak akan muncul sebagai pilihan saat pelanggan baru ditambahkan.</p>
                </div>
                <div class="col-12">
                    <label class="form-label" for="alamat">Alamat</label>
                    <textarea id="alamat" class="form-control" name="alamat" rows="3" autocomplete="street-address">{{ old('alamat') }}</textarea>
                </div>
            </div>
            <div class="mitra-form-footer">
                <a class="btn btn-outline-secondary" href="{{ route('admin.mitra.index') }}">Batal</a>
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Simpan mitra</button>
            </div>
        </div>
    </form>
@endsection
