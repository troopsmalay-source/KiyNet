@extends('layouts.kinet')

@section('page-title', 'CST Internal')

@push('styles')
    <link href="{{ asset('assets/css/cst-internal.css') }}?v=9" rel="stylesheet">
@endpush

@section('content')
    @php
        $words = preg_split('/\s+/', trim((string) $customer['nama'])) ?: [];
        $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1).mb_substr($words[1] ?? '', 0, 1));
    @endphp

    <div class="dashboard-page cst-module">
        <nav class="dashboard-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <a href="{{ route('admin.cst-internal.index') }}">CST Internal</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">{{ $customer['id'] }}</span>
        </nav>

        <section class="cst-card cst-hero" aria-labelledby="cst-customer-heading">
            <div class="cst-hero-main">
                <span class="cst-avatar cst-avatar--lg" aria-hidden="true">{{ $initials }}</span>
                <div>
                    <div class="cst-hero-tags">
                        <x-cst-internal.status-badge :status="$customer['status']" size="lg" />
                        <code class="cst-code-chip cst-code-chip--plain">{{ $customer['id'] }}</code>
                        <span class="cst-code-chip cst-code-chip--plain">{{ $customer['paket'] }}</span>
                    </div>
                    <h2 id="cst-customer-heading">{{ $customer['nama'] }}</h2>
                    <p>{{ $customer['wilayah'] }} · {{ $customer['cluster'] }} · {{ $customer['pppoe_username'] }}</p>
                </div>
            </div>
            <div class="cst-hero-actions">
                <a class="cst-btn cst-btn--ghost" href="{{ route('admin.cst-internal.index') }}">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali
                </a>
                <button type="button" class="cst-btn cst-btn--ghost" data-bs-toggle="modal" data-bs-target="#cstEditModal">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i> Edit Data
                </button>
                <button type="button" class="cst-btn cst-btn--primary" data-bs-toggle="modal" data-bs-target="#cstStatusModal">
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Ubah Status
                </button>
            </div>
        </section>

        <div class="cst-detail-grid">
            <x-cst-internal.profile-card :customer="$customer" />
            <x-cst-internal.service-card :customer="$customer" />
        </div>

        <div class="cst-detail-grid cst-detail-grid--split">
            <x-cst-internal.status-card :customer="$customer" />
            <x-cst-internal.history-tabs :customer="$customer" />
        </div>

        <x-cst-internal.status-modal :customer="$customer" :statuses="$statuses" />
        <x-cst-internal.customer-modal mode="edit" :customer="$customer" :wilayahs="$wilayahs" :pakets="$pakets" :routers="$routers" />
    </div>
@endsection

@push('scripts')
    <script>
        window.cstOpenEditModal = @json($errors->any() && old('_form') === 'edit');
        window.cstOpenStatusModal = @json($errors->any() && old('_form') === 'status');
    </script>
    <script src="{{ asset('assets/js/cst-internal.js') }}?v=6"></script>
    <script>
        if (window.cstOpenStatusModal) {
            new bootstrap.Modal(document.getElementById('cstStatusModal')).show();
        }
    </script>
@endpush
