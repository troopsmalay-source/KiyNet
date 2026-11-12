@extends('layouts.kinet')

@section('page-title', 'Buat Tagihan')

@push('styles')
    <link href="{{ asset('assets/css/tagihan-management.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <div class="tagihan-module">
        <nav class="dashboard-breadcrumb tagihan-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Beranda</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <a href="{{ route('admin.tagihan.index') }}">Tagihan</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Buat tagihan</span>
        </nav>
        <x-page-heading title="Buat tagihan" description="Terbitkan invoice untuk satu langganan aktif dan satu periode.">
            <a class="btn btn-outline-secondary" href="{{ route('admin.tagihan.index') }}"><i class="bi bi-arrow-left"></i> Kembali</a>
        </x-page-heading>

        <form method="POST" action="{{ route('admin.tagihan.store') }}" class="page-panel tagihan-form-panel">
            @csrf
            <div class="tagihan-form-intro">
                <h3>Informasi invoice</h3>
                <p>Nominal tagihan menggunakan harga snapshot langganan. Setiap langganan hanya dapat memiliki satu invoice per periode.</p>
            </div>
            <div class="tagihan-form-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="langganan_id">Langganan aktif <span class="text-danger">*</span></label>
                        <select class="form-select" id="langganan_id" name="langganan_id" required>
                            <option value="">Pilih pelanggan dan paket</option>
                            @foreach ($subscriptions as $subscription)
                                <option value="{{ $subscription->id }}" @selected((string) old('langganan_id') === (string) $subscription->id)>
                                    {{ $subscription->pelanggan->kode_pelanggan }} — {{ $subscription->pelanggan->nama_pelanggan }} / {{ $subscription->paketInternet->nama_paket }} · Rp {{ number_format((float) $subscription->harga_snapshot, 0, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        @if ($subscriptions->isEmpty())
                            <p class="tagihan-field-note">Belum ada langganan aktif yang dapat ditagihkan.</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="periode">Periode tagihan <span class="text-danger">*</span></label>
                        <input class="form-control" id="periode" type="month" name="periode" value="{{ old('periode', now()->format('Y-m')) }}" required>
                        @error('periode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="jatuh_tempo">Tanggal jatuh tempo <span class="text-danger">*</span></label>
                        <input class="form-control" id="jatuh_tempo" type="date" name="jatuh_tempo" min="{{ now()->toDateString() }}" value="{{ old('jatuh_tempo', now()->addDays(7)->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="diskon">Diskon (Rp)</label>
                        <input class="form-control" id="diskon" type="number" name="diskon" min="0" step="1000" value="{{ old('diskon', 0) }}">
                        <small class="tagihan-field-note">Diskon harus lebih kecil dari harga langganan.</small>
                    </div>
                </div>
                <div class="tagihan-form-footer">
                    <a class="btn btn-outline-secondary" href="{{ route('admin.tagihan.index') }}">Batal</a>
                    <button class="btn btn-primary" type="submit" @disabled($subscriptions->isEmpty())><i class="bi bi-receipt"></i> Terbitkan invoice</button>
                </div>
            </div>
        </form>
    </div>
@endsection
