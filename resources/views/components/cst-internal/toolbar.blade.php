@props([
    'filters' => [],
    'statuses' => [],
    'wilayahs' => [],
    'pakets' => [],
    'routers' => [],
    'total' => 0,
    'filtered' => 0,
])

@php
    $hasActiveFilter = collect($filters)->contains(fn ($value) => filled($value));
@endphp

<section class="cst-card cst-toolbar" aria-labelledby="cst-heading">
    <div class="cst-toolbar-head">
        <div>
            <p class="cst-eyebrow">CUSTOMER SERVICE INTERNAL</p>
            <h2 id="cst-heading">CST Internal</h2>
            <p>Kelola data pelanggan, layanan jaringan, dan riwayat status dalam satu panel operasional.</p>
        </div>
        <div class="cst-toolbar-actions">
            <button type="button" class="cst-btn cst-btn--ghost" data-bs-toggle="modal" data-bs-target="#cstImportModal">
                <i class="bi bi-upload" aria-hidden="true"></i> Impor Data
            </button>
            <a class="cst-btn cst-btn--ghost" href="{{ route('admin.cst-internal.export', array_filter($filters)) }}">
                <i class="bi bi-download" aria-hidden="true"></i> Ekspor CSV
            </a>
            <button type="button" class="cst-btn cst-btn--primary" data-bs-toggle="modal" data-bs-target="#cstCreateModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> Registrasi CST Baru
            </button>
        </div>
    </div>

    <form class="cst-filter-form" method="GET" action="{{ route('admin.cst-internal.index') }}" role="search">
        <label class="cst-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari nomor CST, nama, username PPPoE, alamat, atau no. telepon…" aria-label="Pencarian pelanggan CST">
        </label>

        <div class="cst-filter-group">
            <label class="cst-select">
                <span>Status</span>
                <select name="status" aria-label="Filter status" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </label>
            <label class="cst-select">
                <span>Wilayah</span>
                <select name="wilayah" aria-label="Filter wilayah layanan" onchange="this.form.submit()">
                    <option value="">Semua wilayah</option>
                    @foreach ($wilayahs as $wilayah)
                        <option value="{{ $wilayah }}" @selected(($filters['wilayah'] ?? '') === $wilayah)>{{ $wilayah }}</option>
                    @endforeach
                </select>
            </label>
            <label class="cst-select">
                <span>Paket</span>
                <select name="paket" aria-label="Filter paket" onchange="this.form.submit()">
                    <option value="">Semua paket</option>
                    @foreach ($pakets as $paket)
                        <option value="{{ $paket }}" @selected(($filters['paket'] ?? '') === $paket)>{{ $paket }}</option>
                    @endforeach
                </select>
            </label>
            <label class="cst-select">
                <span>Router</span>
                <select name="router" aria-label="Filter router gateway" onchange="this.form.submit()">
                    <option value="">Semua router</option>
                    @foreach ($routers as $router)
                        <option value="{{ $router }}" @selected(($filters['router'] ?? '') === $router)>{{ $router }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="cst-filter-actions">
            <button type="submit" class="cst-btn cst-btn--ghost"><i class="bi bi-funnel" aria-hidden="true"></i> Terapkan</button>
            @if ($hasActiveFilter)
                <a class="cst-btn cst-btn--ghost" href="{{ route('admin.cst-internal.index') }}" title="Reset semua filter"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
            @endif
        </div>
    </form>

    <div class="cst-toolbar-meta">
        <span><i class="bi bi-people" aria-hidden="true"></i>{{ $filtered }} dari {{ $total }} pelanggan ditampilkan</span>
        @if ($hasActiveFilter)
            <a class="cst-reset-link" href="{{ route('admin.cst-internal.index') }}">Reset filter</a>
        @endif
    </div>
</section>
