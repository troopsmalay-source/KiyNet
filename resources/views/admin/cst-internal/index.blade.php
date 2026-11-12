@extends('layouts.kinet')

@section('page-title', 'CST Internal')

@push('styles')
    <link href="{{ asset('assets/css/cst-internal.css') }}?v=5" rel="stylesheet">
@endpush

@section('content')
    <div class="dashboard-page cst-module">
        <nav class="dashboard-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">CST Internal</span>
        </nav>

        <x-cst-internal.toolbar
            :filters="$filters"
            :statuses="$statuses"
            :wilayahs="$wilayahs"
            :pakets="$pakets"
            :routers="$routers"
            :total="$total"
            :filtered="$paginator->total()"
        />

        <x-cst-internal.customer-table :customers="$customers" :paginator="$paginator" :per-page="$perPage" :sort="$sort" :direction="$direction" />
    </div>

    <x-cst-internal.customer-modal :wilayahs="$wilayahs" :pakets="$pakets" :routers="$routers" />
    <x-cst-internal.customer-modal mode="edit" :wilayahs="$wilayahs" :pakets="$pakets" :routers="$routers" />
    <x-cst-internal.status-modal :statuses="$statuses" />
    <x-cst-internal.import-modal />
@endsection

@push('scripts')
    <script>
        window.cstOpenCreateModal = @json($errors->any() && old('_form') === 'create');
    </script>
    <script src="{{ asset('assets/js/cst-internal.js') }}?v=5"></script>
@endpush
