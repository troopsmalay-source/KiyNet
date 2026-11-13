@extends('layouts.kinet')

@section('page-title', 'Widget Jaringan')

@push('styles')
    <link href="{{ asset('assets/css/dashboard-modular.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <div class="dashboard-page dashboard-modular">
        <nav class="dashboard-breadcrumb" aria-label="Breadcrumb">
            <span>Dashboard</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Widget Jaringan</span>
        </nav>

        <section class="dash-intro" aria-labelledby="network-widget-heading">
            <div>
                <p class="dash-eyebrow">STATUS AAA</p>
                <h2 id="network-widget-heading">Widget jaringan central</h2>
                <p>Ringkasan sinkronisasi AAA: NAS aktif, sesi RADIUS, dan kondisi outbox.</p>
            </div>
        </section>

        <section class="dash-section" aria-labelledby="network-widget-stats">
            <header class="dash-section-head">
                <h2 id="network-widget-stats" class="visually-hidden">Statistik jaringan</h2>
            </header>
            <div class="dash-stat-grid dash-cols-4">
                <x-dashboard.stat-tile
                    label="NAS Aktif"
                    :value="number_format($nasActive, 0, ',', '.')"
                    meta="Router dengan status aktif"
                    tone="success"
                    icon="bi-router"
                />
                <x-dashboard.stat-tile
                    label="Sesi Aktif"
                    :value="number_format($activeSessions, 0, ',', '.')"
                    meta="Sesi PPPoE yang berjalan"
                    tone="info"
                    icon="bi-broadcast"
                />
                <x-dashboard.stat-tile
                    label="Outbox Pending"
                    :value="number_format($outboxPending, 0, ',', '.')"
                    meta="Perintah jaringan menunggu diproses"
                    tone="warning"
                    icon="bi-hourglass-split"
                />
                <x-dashboard.stat-tile
                    label="Outbox Dead Letter"
                    :value="number_format($outboxDeadLetter, 0, ',', '.')"
                    meta="Perintah gagal setelah semua percobaan"
                    tone="danger"
                    icon="bi-exclamation-octagon"
                />
            </div>
        </section>
    </div>
@endsection
