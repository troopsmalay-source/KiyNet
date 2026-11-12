@extends('layouts.kinet')

@section('page-title', 'Paket & Layanan')

@push('styles')
    <link href="{{ asset('assets/css/packages.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <div class="dashboard-page pkg-module">
        <nav class="dashboard-breadcrumb" aria-label="breadcrumb">
            <i class="bi bi-house"></i>
            <span>Dashboard</span>
            <i class="bi bi-chevron-right"></i>
            <span aria-current="page">Paket &amp; Layanan</span>
        </nav>

        <header class="pkg-head">
            <div>
                <p class="pkg-eyebrow">Katalog Layanan ISP</p>
                <h2>Paket &amp; Layanan</h2>
                <p>Definisikan paket retail, bisnis, dan dedicated. Setiap paket terikat ke PPP Profile MikroTik dengan rate-limit, address pool, dan kebijakan FUP masing-masing.</p>
            </div>
            <div class="pkg-head-actions">
                <button class="pkg-btn pkg-btn--primary" data-pkg-open="create" type="button">
                    <i class="bi bi-plus-lg"></i> Tambah Paket Baru
                </button>
            </div>
        </header>

        <section class="pkg-stats" aria-label="Ringkasan paket">
            <div class="pkg-stat">
                <span class="pkg-stat-icon pkg-stat-icon--total"><i class="bi bi-box-seam"></i></span>
                <div class="pkg-stat-body">
                    <strong>{{ $stats['total'] }}</strong>
                    <span>Total Paket</span>
                </div>
            </div>
            <div class="pkg-stat">
                <span class="pkg-stat-icon pkg-stat-icon--active"><i class="bi bi-check-circle"></i></span>
                <div class="pkg-stat-body">
                    <strong>{{ $stats['active'] }}</strong>
                    <span>Paket Aktif</span>
                </div>
            </div>
            <div class="pkg-stat">
                <span class="pkg-stat-icon pkg-stat-icon--custom"><i class="bi bi-star"></i></span>
                <div class="pkg-stat-body">
                    <strong>{{ $stats['custom'] }}</strong>
                    <span>Paket Khusus</span>
                </div>
            </div>
            <div class="pkg-stat">
                <span class="pkg-stat-icon pkg-stat-icon--inactive"><i class="bi bi-pause-circle"></i></span>
                <div class="pkg-stat-body">
                    <strong>{{ $stats['inactive'] }}</strong>
                    <span>Paket Nonaktif</span>
                </div>
            </div>
        </section>

        <form class="pkg-toolbar" method="GET" action="{{ route('admin.packages.index') }}" role="search">
            <div class="pkg-filter-form">
                <label class="pkg-search">
                    <i class="bi bi-search"></i>
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama paket, profil MikroTik, atau ID...">
                </label>
                <label class="pkg-select">
                    <span>Status</span>
                    <select name="status">
                        <option value="">Semua</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </label>
                <label class="pkg-select">
                    <span>Kategori</span>
                    <select name="kategori">
                        <option value="">Semua</option>
                        @foreach (\App\Support\PackageServiceRepository::KATEGORI as $key => $label)
                            <option value="{{ $key }}" {{ request('kategori') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="pkg-select">
                    <span>Profil MikroTik</span>
                    <select name="profile">
                        <option value="">Semua</option>
                        @foreach ($profiles as $profile)
                            <option value="{{ $profile }}" {{ request('profile') === $profile ? 'selected' : '' }}>{{ $profile }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="pkg-select">
                    <span>Kecepatan</span>
                    <select name="kecepatan">
                        <option value="">Semua</option>
                        <option value="<10" {{ request('kecepatan') === '<10' ? 'selected' : '' }}>&lt; 10 Mbps</option>
                        <option value="10-30" {{ request('kecepatan') === '10-30' ? 'selected' : '' }}>10 - 30 Mbps</option>
                        <option value="31-100" {{ request('kecepatan') === '31-100' ? 'selected' : '' }}>31 - 100 Mbps</option>
                        <option value=">100" {{ request('kecepatan') === '>100' ? 'selected' : '' }}>&gt; 100 Mbps</option>
                    </select>
                </label>
                <div class="pkg-filter-actions">
                    <button type="submit" class="pkg-btn pkg-btn--primary">
                        <i class="bi bi-funnel"></i> Terapkan
                    </button>
                    <a href="{{ route('admin.packages.index') }}" class="pkg-btn pkg-btn--ghost">Reset</a>
                </div>
            </div>
            <div class="pkg-meta">
                <span>{{ $packages->count() }} dari {{ $stats['total'] }} paket ditampilkan</span>
            </div>
        </form>

        @if ($packages->isEmpty())
            <div class="pkg-empty">
                <i class="bi bi-inbox"></i>
                <strong>Tidak ada paket yang cocok</strong>
                <p>Coba ubah kata kunci atau filter, atau tambahkan paket baru.</p>
            </div>
        @else
            <div class="pkg-grid">
                @foreach ($packages as $package)
                    <x-packages.package-card :package="$package" />
                @endforeach
            </div>
        @endif

        <x-packages.form-modal :profiles="$profiles" />
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            'use strict';

            var module = document.querySelector('.pkg-module');
            if (!module) { return; }

            var modal = document.getElementById('pkgFormModal');
            if (!modal) { return; }

            var form = modal.querySelector('form');
            var titleEl = modal.querySelector('.modal-title');
            var subEl = modal.querySelector('.pkg-modal-sub');
            var bsModal = null;
            if (window.bootstrap && bootstrap.Modal) {
                bsModal = bootstrap.Modal.getOrCreateInstance(modal);
            }

            var openCreate = function () {
                form.reset();
                form.action = '{{ route('admin.packages.store') }}';
                form.method = 'POST';
                form.querySelector('input[name="_method"]')?.remove();
                titleEl.textContent = 'Tambah Paket Baru';
                subEl.textContent = 'Lengkapi informasi paket layanan baru.';
            };

            var parsePackage = function (el) {
                try {
                    return JSON.parse(el.dataset.pkg || '{}');
                } catch (e) { return {}; }
            };

            var openEdit = function (el) {
                var pkg = parsePackage(el);
                form.reset();
                form.querySelector('input[name="_method"]')?.remove();

                var method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'PUT';
                form.appendChild(method);

                form.action = '{{ url('admin/packages') }}/' + (pkg.id || '');
                titleEl.textContent = 'Edit Paket: ' + (pkg.nama || '');
                subEl.textContent = 'ID: ' + (pkg.id || '-');

                Object.keys(pkg).forEach(function (key) {
                    if (key === 'id') { return; }

                    var field = form.querySelector('[name="' + key + '"]');
                    if (!field) { return; }

                    if (field.type === 'checkbox') {
                        field.checked = !!pkg[key];
                    } else if (field.tagName === 'TEXTAREA') {
                        field.value = pkg[key] || '';
                    } else {
                        field.value = pkg[key] === null ? '' : pkg[key];
                    }
                });
            };

            var fupCheck = form.querySelector('[name="fup_enabled"]');
            var diskonSelect = form.querySelector('[name="diskon_jenis"]');

            var syncFup = function () {
                if (fupCheck) {
                    form.querySelectorAll('.pkg-fup-field').forEach(function (el) {
                        el.style.display = fupCheck.checked ? '' : 'none';
                    });
                }
            };

            var syncDiskon = function () {
                if (diskonSelect) {
                    form.querySelectorAll('.pkg-diskon-field').forEach(function (el) {
                        el.style.display = diskonSelect.value ? '' : 'none';
                    });
                }
            };

            module.addEventListener('click', function (e) {
                var target = e.target.closest('[data-pkg-open]');
                if (target) {
                    e.preventDefault();
                    if (target.dataset.pkgOpen === 'edit') { openEdit(target); } else { openCreate(); }
                    if (bsModal) { bsModal.show(); }
                    return;
                }

                var del = e.target.closest('[data-pkg-delete]');
                if (del) {
                    if (!confirm('Hapus paket ini? (soft delete, data masih tersimpan)')) { return; }
                    var delForm = document.createElement('form');
                    delForm.method = 'POST';
                    delForm.action = '{{ url('admin/packages') }}/' + del.dataset.pkgDelete;
                    delForm.innerHTML = '@{{ csrf_field() }}@{{ @method('DELETE') }}';
                    delForm.innerHTML = delForm.innerHTML.replace(/@/g, '');
                    document.body.appendChild(delForm);
                    delForm.submit();
                }
            });

            var customCheck = form.querySelector('[name="is_custom"]');
            var syncCustom = function () {
                if (customCheck) {
                    form.querySelectorAll('.pkg-custom-field').forEach(function (el) {
                        el.style.display = customCheck.checked ? '' : 'none';
                    });
                }
            };

            if (fupCheck) {
                fupCheck.addEventListener('change', syncFup);
            }
            if (diskonSelect) {
                diskonSelect.addEventListener('change', syncDiskon);
            }
            if (customCheck) {
                customCheck.addEventListener('change', syncCustom);
            }
            syncCustom();
            syncFup();
            syncDiskon();
        })();
    </script>
@endpush
