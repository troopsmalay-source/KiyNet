@extends('layouts.kinet')

@section('page-title', 'Billing & Invoice')

@push('styles')
    <link href="{{ asset('assets/css/cst-internal.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/billing.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="dashboard-page billing-module cst-module">
    <nav class="dashboard-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <i class="bi bi-chevron-right" aria-hidden="true"></i>
        <span aria-current="page">Billing &amp; Invoice</span>
    </nav>

    @include('components.billing.metric-tiles', ['stats' => $stats])

    {{-- Toolbar card --}}
    <div class="cst-card bill-toolbar">
        <div class="cst-toolbar-head">
            <div>
                <p class="cst-eyebrow">MANAJEMEN INVOICE</p>
                <h2>Tagihan &amp; Piutang</h2>
                <p>Kelola seluruh siklus penagihan pelanggan aktif.</p>
            </div>
            <div class="bill-toolbar-actions">
                <button class="cst-btn cst-btn--primary" data-bs-toggle="modal" data-bs-target="#manualInvoiceModal">
                    <i class="bi bi-plus-lg"></i> Buat Invoice Manual
                </button>
                <button class="cst-btn" data-bs-toggle="modal" data-bs-target="#generateModal">
                    <i class="bi bi-lightning-charge"></i> Generate Massal
                </button>
                <button class="cst-btn" data-bs-toggle="modal" data-bs-target="#waModal">
                    <i class="bi bi-whatsapp"></i> Pengingat WA
                </button>
                <a href="{{ route('admin.billing.export', request()->query()) }}" class="cst-btn cst-btn--ghost">
                    <i class="bi bi-download"></i> Unduh CSV
                </a>
            </div>
        </div>
        <form method="GET" action="{{ route('admin.billing.index') }}" class="cst-filter-form" role="search">
            <div class="cst-search">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari invoice, CST, nama, atau WA..." aria-label="Cari invoice">
            </div>
            <div class="cst-filter-group">
                <div class="cst-select">
                    <span>Status</span>
                    <select name="status">
                        <option value="">Semua Status</option>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cst-select">
                    <span>Tipe</span>
                    <select name="tipe">
                        <option value="">Semua Tipe</option>
                        @foreach ($tipeLabels as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['tipe'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="cst-select">
                    <span>Terbit dari</span>
                    <input type="date" class="form-control" name="dari" value="{{ $filters['dari'] ?? '' }}">
                </div>
                <div class="cst-select">
                    <span>Sampai</span>
                    <input type="date" class="form-control" name="sampai" value="{{ $filters['sampai'] ?? '' }}">
                </div>
            </div>
            <div class="cst-filter-actions">
                <button type="submit" class="cst-btn cst-btn--primary"><i class="bi bi-funnel"></i> Filter</button>
                <a href="{{ route('admin.billing.index') }}" class="cst-reset-link">Reset</a>
            </div>
        </form>
    </div>

    <div class="cst-card cst-table-card bill-table-card">
        <div class="cst-table-head">
            <div>
                <h3>Daftar Invoice</h3>
                <p>{{ $paginator->total() }} tagihan</p>
            </div>
            <div class="cst-row-actions">
                <span class="cst-source-chip"><i class="bi bi-credit-card-2-front"></i> SIKLUS {{ now()->format('M Y') }}</span>
            </div>
        </div>
        <div class="cst-table-scroll">
            <table class="cst-table bill-table">
                <thead>
                    <tr>
                        <th class="cst-sticky-col cst-col-inv">No Invoice</th>
                        <th class="cst-col-pelanggan">Pelanggan</th>
                        <th class="cst-col-tipe">Tipe &amp; Paket</th>
                        <th class="cst-col-periode">Periode</th>
                        <th class="cst-col-nominal">Nominal</th>
                        <th class="cst-col-status2">Status</th>
                        <th class="cst-col-aksi">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $inv)
                        @php
                            $effStatus = $inv['status'];
                            if ($inv['status'] === 'unpaid' && $inv['tanggal_jatuh_tempo'] < now()->toDateString()) {
                                $effStatus = 'overdue';
                            }
                            $daysLeft = (int) floor(abs(now()->floatDiffInDays(now()->parse($inv['tanggal_jatuh_tempo']))));
                        @endphp
                        <tr>
                            <td class="cst-sticky-col">
                                <a class="cst-code" href="{{ route('admin.billing.show', $inv['no_invoice']) }}">#{{ $inv['no_invoice'] }}</a>
                            </td>
                            <td>
                                <div class="cst-person">
                                    <span class="cst-avatar cst-avatar--{{ strtolower(substr($inv['nama'], 0, 1)) }}">{{ substr($inv['nama'], 0, 1) }}</span>
                                    <span class="cst-person-info">
                                        <strong>{{ $inv['nama'] }}</strong>
                                        <span>{{ $inv['no_cst'] }}</span>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="cst-type-badge">{{ $tipeLabels[$inv['tipe']] ?? $inv['tipe'] }}</span>
                                <span class="cst-cell-strong cst-mt">{{ $inv['paket'] }}</span>
                            </td>
                            <td>
                                <span class="cst-cell-strong">{{ \Carbon\Carbon::parse($inv['tanggal_terbit'])->translatedFormat('d M Y') }}</span>
                                <span class="cst-sub">Jatuh tempo: {{ \Carbon\Carbon::parse($inv['tanggal_jatuh_tempo'])->translatedFormat('d M Y') }}</span>
                                @if ($effStatus === 'overdue')
                                    <span class="cst-sub"><i class="bi bi-exclamation-triangle"></i> telat {{ abs($daysLeft) }} hari</span>
                                @elseif ($inv['status'] === 'unpaid' && $daysLeft >= 0 && $daysLeft <= 3)
                                    <span class="cst-sub"><i class="bi bi-clock"></i> {{ $daysLeft }} hari lagi</span>
                                @endif
                            </td>
                            <td>
                                @if ($inv['diskon'] > 0)
                                    <span class="cst-sub">Diskon: Rp {{ number_format($inv['diskon'], 0, ',', '.') }}</span>
                                @endif
                                @if ($inv['biaya_admin'] > 0)
                                    <span class="cst-sub">Biaya admin: Rp {{ number_format($inv['biaya_admin'], 0, ',', '.') }}</span>
                                @endif
                                <span class="cst-cell-strong bill-nominal">Rp {{ number_format($inv['total'], 0, ',', '.') }}</span>
                            </td>
                            <td><span class="cst-badge cst-badge--status{{ $effStatus }}">{{ $statuses[$effStatus] ?? $effStatus }}</span></td>
                            <td>
                                <div class="bill-qactions">
                                    @if (!in_array($effStatus, ['paid', 'void'], true))
                                        <button class="bill-icon-btn" title="Bayar / Input Pelunasan" data-bs-toggle="modal" data-bs-target="#payModal" data-inv="{{ $inv['no_invoice'] }}" data-nama="{{ $inv['nama'] }}" data-total="{{ number_format($inv['total'], 0, ',', '.') }}"><i class="bi bi-cash-coin"></i></button>
                                        <form method="POST" action="{{ route('admin.billing.wa') }}" class="d-none" id="wa-inline-{{ $inv['no_invoice'] }}">
                                            @csrf
                                            <input type="hidden" name="no_invoice" value="{{ $inv['no_invoice'] }}">
                                            <input type="hidden" name="template" value="{{ $effStatus === 'overdue' ? 'overdue' : 'h3' }}">
                                        </form>
                                        <button class="bill-icon-btn" title="Kirim Tagihan WhatsApp" data-bs-toggle="modal" data-bs-target="#waModal" onclick="document.getElementById('waSelectInvoice').value = '{{ $inv['no_invoice'] }}';"><i class="bi bi-whatsapp text-success"></i></button>
                                    @endif
                                    <a class="bill-icon-btn" title="Cetak / PDF" href="{{ route('admin.billing.show', $inv['no_invoice']) }}" target="_blank"><i class="bi bi-printer"></i></a>
                                    <div class="dropdown">
                                        <button class="bill-icon-btn" data-bs-toggle="dropdown" aria-label="Opsi lainnya"><i class="bi bi-three-dots"></i></button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="{{ route('admin.billing.show', $inv['no_invoice']) }}"><i class="bi bi-eye me-2"></i>Lihat Detail</i></a></li>
                                            @if ($inv['status'] === 'unpaid')
                                                <li>
                                                    <form method="POST" action="{{ route('admin.billing.pending', $inv['no_invoice']) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item"><i class="bi bi-hourglass-split me-2"></i>Tandai Pending</button>
                                                    </form>
                                                </li>
                                            @endif
                                            @if (!in_array($effStatus, ['paid', 'void'], true))
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" action="{{ route('admin.billing.void', $inv['no_invoice']) }}" onsubmit="return confirm('Batalkan invoice {{ $inv['no_invoice'] }}?')">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item text-danger"><i class="bi bi-x-circle me-2"></i>Batalkan / Void</button>
                                                    </form>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="cst-empty-row"><td colspan="7"><i class="bi bi-inbox"></i><strong>Tidak ada invoice</strong>Tidak ada tagihan yang cocok dengan filter aktif. <a href="{{ route('admin.billing.index') }}">Reset filter</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="cst-pagination">
            <div class="cst-pagination-info">
                <span>Menampilkan
                    <strong>{{ $paginator->total() === 0 ? 0 : $paginator->firstItem() }} – {{ $paginator->lastItem() }}</strong>
                    dari <strong>{{ $paginator->total() }}</strong> pelanggan
                </span>
                <label class="cst-per-page" for="billPerPage">
                    Per halaman
                    <select id="billPerPage" name="per_page" data-cst-per-page>
                        @foreach ([5, 10, 25, 50] as $option)
                            <option value="{{ $option }}" @selected($option === $perPage)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            @if ($paginator->hasPages())
                <nav aria-label="Navigasi halaman invoice" class="cst-pagination-nav">
                    {{ $paginator->withQueryString()->links('pagination::bootstrap-5') }}
                </nav>
            @endif
        </div>
    </div>

    <div class="row g-3 mt-4">
        <div class="col-lg-6">
            <div class="bill-recap-card">
                <div class="bill-recap-title"><i class="bi bi-graph-up-arrow"></i> Aging Piutang (Unpaid + Overdue)</div>
                <div class="bill-aging-item"><span>&lt; 30 hari</span><strong>Rp {{ number_format($stats['aging']['lt30'], 0, ',', '.') }}</strong></div>
                <div class="bill-aging-item"><span>31 - 60 hari</span><strong>Rp {{ number_format($stats['aging']['b3060'], 0, ',', '.') }}</strong></div>
                <div class="bill-aging-item"><span>&gt; 60 hari</span><strong>Rp {{ number_format($stats['aging']['gt60'], 0, ',', '.') }}</strong></div>
                <div class="bill-aging-item"><span class="fw-bold">Total Piutang</span><strong class="text-danger">Rp {{ number_format($stats['piutang_total'], 0, ',', '.') }}</strong></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="bill-recap-card">
                <div class="bill-recap-title"><i class="bi bi-geo-alt"></i> Piutang per Wilayah / POP</div>
                @forelse ($stats['per_wilayah'] as $wilayah => $data)
                    <div class="bill-aging-item"><span>{{ $wilayah }} <small class="text-muted">({{ $data['count'] }} inv)</small></span><strong>Rp {{ number_format($data['total'], 0, ',', '.') }}</strong></div>
                @empty
                    <p class="text-muted mb-0">Tidak ada piutang aktif.</p>
                @endforelse
            </div>
        </div>
        <div class="col-12">
            <div class="bill-recap-card">
                <div class="bill-recap-title"><i class="bi bi-arrow-repeat"></i> Log Mutasi Pembayaran Terbaru</div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Tanggal</th><th>Invoice</th><th>Pelanggan</th><th>Metode</th><th class="text-end">Nominal</th></tr></thead>
                        <tbody>
                            @forelse ($recentPayments as $row)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($row['tanggal_bayar'])->translatedFormat('d M Y') }}</td>
                                    <td class="font-monospace small">{{ $row['no_invoice'] }}</td>
                                    <td>{{ $row['nama'] }}</td>
                                    <td><span class="bill-badge bill-badge--paid">{{ ucfirst($row['metode_pembayaran']) }}</span></td>
                                    <td class="text-end">Rp {{ number_format($row['total'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada mutasi pembayaran.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Manual Invoice Modal --}}
    <div class="modal fade" id="manualInvoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Buat Invoice Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form method="POST" action="{{ route('admin.billing.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pelanggan</label>
                                <select class="form-select" name="no_cst" id="cstSelect" required>
                                    <option value="">- Pilih pelanggan -</option>
                                    @foreach ($customers as $cst)
                                        <option value="{{ $cst['id'] }}" data-paket="{{ $cst['paket'] }}">{{ $cst['id'] }} - {{ $cst['nama'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipe Tagihan</label>
                                <select class="form-select" name="tipe" required>
                                    <option value="bulanan">Bulanan</option>
                                    <option value="psb">Instalasi / Pasang Baru</option>
                                    <option value="perangkat">Perangkat</option>
                                    <option value="tambahan">Biaya Tambahan</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Terbit</label>
                                <input type="date" class="form-control" name="tanggal_terbit" value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Jatuh Tempo</label>
                                <input type="date" class="form-control" name="tanggal_jatuh_tempo" value="{{ now()->addDays(14)->toDateString() }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Periode</label>
                                <input type="month" class="form-control" name="periode" value="{{ now()->format('Y-m') }}">
                            </div>
                        </div>
                        <hr>
                        <label class="form-label">Item Tagihan</label>
                        <div class="bill-modal-rows" id="itemRows">
                            <div class="bill-item-row">
                                <input class="form-control" name="items[0][nama]" placeholder="Deskripsi item (mis. Langganan paket Home 20)" required>
                                <input class="form-control" name="items[0][qty]" type="number" min="1" value="1" required>
                                <input class="form-control" name="items[0][harga]" type="number" min="0" step="500" placeholder="Harga" required>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.bill-item-row').remove()" title="Hapus item"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addItemBtn"><i class="bi bi-plus"></i> Tambah Item</button>
                        <div class="row g-3 mt-1">
                            <div class="col-md-4">
                                <label class="form-label">Diskon (Rp)</label>
                                <input type="number" name="diskon" class="form-control" min="0" step="500" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Biaya Admin (Rp)</label>
                                <input type="number" name="biaya_admin" class="form-control" min="0" step="500" value="0">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="ppn_enabled" value="1" id="ppnEnabled">
                                    <label class="form-check-label" for="ppnEnabled">Tambahkan PPN 11%</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan Invoice</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Pay Modal --}}
    <div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-cash-coin me-2"></i>Catat Pelunasan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <form method="POST" id="payForm" action="">
                    @csrf
                    <div class="modal-body">
                        <p class="mb-2">Invoice <strong id="payInv"></strong> - <span id="payNama"></span></p>
                        <p class="fs-5 fw-bold mb-3" id="payTotal"></p>
                        <label class="form-label">Metode Pembayaran</label>
                        <select class="form-select" name="metode" required>
                            <option value="cash">Cash</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="midtrans">Midtrans / Payment Gateway</option>
                        </select>
                        <label class="form-label mt-3">Catatan (opsional)</label>
                        <input type="text" class="form-control" name="catatan" placeholder="mis. transfer BCA 11 Nov, verifikasi admin">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Konfirmasi Lunas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('components.billing.generate-modal', ['routers' => $routers])
    @include('components.billing.wa-reminder-modal', ['invoices' => $invoices])
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Pay modal wiring
    const payModalEl = document.getElementById('payModal');
    if (payModalEl) {
        payModalEl.addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            document.getElementById('payInv').textContent = btn.dataset.inv;
            document.getElementById('payNama').textContent = btn.dataset.nama;
            document.getElementById('payTotal').textContent = btn.dataset.total;
            document.getElementById('payForm').action = '/admin/billing/' + encodeURIComponent(btn.dataset.inv) + '/payment';
        });
    }

    // Manual invoice: dynamic item rows
    const addItemBtn = document.getElementById('addItemBtn');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function () {
            const rows = document.getElementById('itemRows');
            const idx = rows.children.length;
            const tpl = rows.children[0].cloneNode(true);
            tpl.querySelectorAll('input').forEach((input) => { input.value = input.type === 'number' && input.min === '1' ? 1 : ''; });
            tpl.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(/\[\d+\]/, '[' + idx + ']');
            });
            rows.appendChild(tpl);
        });
    }

    // Auto-fill first item with customer package name on select
    const cstSelect = document.getElementById('cstSelect');
    if (cstSelect) {
        cstSelect.addEventListener('change', function () {
            const opt = cstSelect.selectedOptions[0];
            const firstRow = document.querySelector('#itemRows .bill-item-row input[name$="[nama]"]');
            if (opt.dataset.paket && firstRow && !firstRow.value) {
                firstRow.value = 'Langganan Internet ' + opt.dataset.paket;
            }
        });
    }
})();
</script>
@endpush
