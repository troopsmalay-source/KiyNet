@extends('layouts.kinet')

@section('page-title', 'Dashboard')

@push('styles')
    <link href="{{ asset('assets/css/dashboard-modular.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <div class="dashboard-page dashboard-modular">
        <nav class="dashboard-breadcrumb" aria-label="Breadcrumb">
            <span>Beranda</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Dashboard</span>
        </nav>

        <section class="dash-intro" aria-labelledby="dashboard-heading">
            <div>
                <p class="dash-eyebrow">RINGKASAN OPERASIONAL</p>
                <h2 id="dashboard-heading">Ringkasan operasional</h2>
                <p>Pantau pelanggan, mitra, tagihan, kesehatan jaringan, dan insiden dalam satu tempat.</p>
                @if ($demoMode)
                    <span class="dashboard-demo-notice">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        Data demo — hanya untuk pratinjau dan tidak disimpan.
                    </span>
                @endif
            </div>
            <div class="intro-date">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <span>{{ $today->locale('id')->translatedFormat('l, d F Y') }}</span>
            </div>
        </section>

        <x-dashboard.customer-stats :stats="$customerStats" />

        <x-dashboard.partner-stats :stats="$partnerStats" />

        <x-dashboard.billing-section :billing="$billing" :revenue="$revenue" />

        <x-dashboard.router-gateway :network="$network" :routers="$routers" />

        <x-dashboard.alert-feed :incidents="$incidents" />
    </div>
@endsection

@push('scripts')
    <script>
        window.kinetDashboardRevenue = @js($revenue);
    </script>
    <script src="{{ asset('assets/js/dashboard-modular.js') }}?v=1" defer></script>
@endpush
