@extends('layouts.kinet')

@section('page-title', 'Tambah Pelanggan')

@section('content')
    <x-page-heading title="Tambah pelanggan" description="Masukkan informasi awal pelanggan baru.">
        <a class="btn btn-outline-secondary" href="{{ route('admin.pelanggan.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
    </x-page-heading>

    <form method="POST" action="{{ route('admin.pelanggan.store') }}" class="page-panel page-form-panel">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="nama_pelanggan">Nama pelanggan</label>
                <input id="nama_pelanggan" class="form-control" name="nama_pelanggan" value="{{ old('nama_pelanggan') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="no_hp">Nomor HP</label>
                <input id="no_hp" class="form-control" name="no_hp" value="{{ old('no_hp') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email <span class="text-secondary fw-normal">(opsional)</span></label>
                <input id="email" class="form-control" name="email" type="email" value="{{ old('email') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="mitra_id">Mitra</label>
                <select id="mitra_id" class="form-select" name="mitra_id">
                    <option value="">Langsung / tanpa mitra</option>
                    @foreach ($mitra as $m)
                        <option value="{{ $m->id }}" @selected(old('mitra_id') == $m->id)>{{ $m->nama_mitra }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="alamat_pasang">Alamat pemasangan</label>
                <textarea id="alamat_pasang" class="form-control" name="alamat_pasang" required>{{ old('alamat_pasang') }}</textarea>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Simpan pelanggan</button>
            </div>
        </div>
    </form>
@endsection
