@extends('layouts.kinet')

@section('page-title', $feature)

@section('content')
    <section class="panel-card development-panel">
        <span class="development-icon"><i class="bi bi-cone-striped"></i></span>
        <span class="panel-kicker">DALAM PENGEMBANGAN</span>
        <h2>Halaman {{ $feature }} sedang disiapkan</h2>
        <p>Menu ini belum tersedia pada Tahap 1. Data dan transaksi yang sudah ada tetap aman.</p>
        <a class="btn btn-primary development-button" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
        </a>
    </section>
@endsection
