@extends('layouts.kinet')

@section('page-title', 'Manajemen Pembayaran')

@push('styles')
    <link href="{{ asset('assets/css/payment-management.css') }}?v=1" rel="stylesheet">
@endpush

@section('content')
    <div class="payment-module" data-payment-module>
        <nav class="dashboard-breadcrumb payment-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Beranda</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Pembayaran</span>
        </nav>

        <section class="payment-page-card">
            <header class="payment-page-heading">
                <div>
                    <p class="payment-eyebrow">MANAJEMEN PEMBAYARAN</p>
                    <h2>Manajemen Pembayaran</h2>
                    <p>Kelola transaksi pembayaran pelanggan, verifikasi pembayaran, dan riwayat pelunasan tagihan internet.</p>
                </div>
                <div class="payment-header-actions">
                    <button class="btn btn-primary" type="button" data-payment-action="manual"><i class="bi bi-plus-lg"></i> Input Pembayaran Manual</button>
                    <button class="btn btn-outline-primary" type="button" data-payment-action="verification"><i class="bi bi-patch-check"></i> Verifikasi Pembayaran <span class="payment-action-count" data-verification-count>0</span></button>
                    <button class="btn btn-outline-secondary" type="button" data-payment-action="export"><i class="bi bi-download"></i> Export Laporan</button>
                </div>
            </header>

            <section class="payment-stats-grid" aria-label="Statistik pembayaran">
                <article class="payment-stat-card"><span class="payment-stat-icon is-blue"><i class="bi bi-arrow-left-right"></i></span><div><span>Total transaksi bulan ini</span><strong data-stat="transactions">0</strong><small>Seluruh kanal pembayaran</small></div></article>
                <article class="payment-stat-card"><span class="payment-stat-icon is-green"><i class="bi bi-check2-circle"></i></span><div><span>Pembayaran berhasil</span><strong data-stat="success">0</strong><small>Transaksi terkonfirmasi</small></div></article>
                <article class="payment-stat-card"><span class="payment-stat-icon is-orange"><i class="bi bi-hourglass-split"></i></span><div><span>Pembayaran pending</span><strong data-stat="pending">0</strong><small>Menunggu pembayaran</small></div></article>
                <article class="payment-stat-card"><span class="payment-stat-icon is-purple"><i class="bi bi-patch-check"></i></span><div><span>Menunggu verifikasi</span><strong data-stat="verification">0</strong><small>Transfer manual masuk</small></div></article>
                <article class="payment-stat-card"><span class="payment-stat-icon is-cyan"><i class="bi bi-wallet2"></i></span><div><span>Total nominal pembayaran</span><strong data-stat="amount">Rp 0</strong><small>Pembayaran berhasil</small></div></article>
                <article class="payment-stat-card"><span class="payment-stat-icon is-red"><i class="bi bi-exclamation-circle"></i></span><div><span>Tagihan belum lunas</span><strong data-stat="unpaid">0</strong><small>Perlu ditindaklanjuti</small></div></article>
            </section>

            <section class="payment-simulation-banner" aria-label="Kontrol simulasi tanggal">
                <div><span class="payment-simulation-icon"><i class="bi bi-calendar2-week"></i></span><div><strong>Simulasi tanggal billing</strong><p><span data-simulated-date></span> <span class="payment-simulation-note">· Tidak mengubah tanggal sistem atau data server.</span></p></div></div>
                <div class="payment-simulation-actions">
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-payment-action="advance-date"><i class="bi bi-calendar-plus"></i> Simulasikan +1 hari</button>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-payment-action="reset-date"><i class="bi bi-arrow-counterclockwise"></i> Kembali ke hari ini</button>
                    <button class="btn btn-sm btn-outline-danger" type="button" data-payment-action="reset-demo"><i class="bi bi-trash3"></i> Reset Data Demo</button>
                </div>
            </section>

            <nav class="payment-tabs" aria-label="Kategori transaksi">
                <button class="payment-tab is-active" type="button" data-payment-filter="all">Semua Pembayaran <span data-tab-count="all">0</span></button>
                <button class="payment-tab" type="button" data-payment-filter="pending">Menunggu Pembayaran <span data-tab-count="pending">0</span></button>
                <button class="payment-tab" type="button" data-payment-filter="verification">Menunggu Verifikasi <span data-tab-count="verification">0</span></button>
                <button class="payment-tab" type="button" data-payment-filter="success">Pembayaran Berhasil <span data-tab-count="success">0</span></button>
                <button class="payment-tab" type="button" data-payment-filter="failed">Pembayaran Gagal <span data-tab-count="failed">0</span></button>
                <button class="payment-tab" type="button" data-payment-filter="history">Riwayat Transaksi <span data-tab-count="history">0</span></button>
            </nav>

            <section class="payment-table-panel">
                <div class="payment-table-heading">
                    <div><h3 data-payment-table-title>Semua Pembayaran</h3><p>Semua transaksi di halaman ini menggunakan data simulasi frontend.</p></div>
                    <div class="payment-filters">
                        <label><span>Tanggal dari</span><input class="form-control" type="date" data-filter-from></label>
                        <label><span>Sampai tanggal</span><input class="form-control" type="date" data-filter-to></label>
                        <label><span>Metode</span><select class="form-select" data-filter-method><option value="">Semua metode</option><option>QRIS</option><option>Virtual Account</option><option>Transfer Manual</option></select></label>
                        <label><span>Status</span><select class="form-select" data-filter-status><option value="">Semua status</option><option value="success">Berhasil</option><option value="pending">Menunggu Pembayaran</option><option value="verification">Menunggu Verifikasi</option><option value="failed">Gagal / Ditolak</option></select></label>
                    </div>
                </div>
                <div class="payment-table-tools">
                    <label class="payment-search"><i class="bi bi-search"></i><input type="search" class="form-control" placeholder="Cari transaksi, invoice, pelanggan..." data-payment-search aria-label="Cari transaksi"></label>
                    <label class="payment-page-size">Tampilkan <select class="form-select" data-payment-page-size aria-label="Jumlah transaksi per halaman"><option>10</option><option>15</option><option>25</option></select></label>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle payment-table">
                        <thead><tr>
                            <th>No.</th><th><button type="button" data-sort="id">ID Transaksi <i class="bi bi-arrow-down-up"></i></button></th>
                            <th><button type="button" data-sort="invoice">Nomor Invoice <i class="bi bi-arrow-down-up"></i></button></th>
                            <th><button type="button" data-sort="customerCode">Kode Pelanggan <i class="bi bi-arrow-down-up"></i></button></th>
                            <th><button type="button" data-sort="customerName">Nama Pelanggan <i class="bi bi-arrow-down-up"></i></button></th>
                            <th><button type="button" data-sort="period">Periode Tagihan <i class="bi bi-arrow-down-up"></i></button></th>
                            <th><button type="button" data-sort="dueOffset">Jatuh Tempo <i class="bi bi-arrow-down-up"></i></button></th>
                            <th>Metode</th><th><button type="button" data-sort="amount">Nominal <i class="bi bi-arrow-down-up"></i></button></th>
                            <th>Tanggal Pembayaran</th><th>Status Pembayaran</th><th class="text-end">Aksi</th>
                        </tr></thead>
                        <tbody data-payment-rows></tbody>
                    </table>
                </div>
                <footer class="payment-table-footer"><span data-payment-pagination-info></span><nav aria-label="Navigasi transaksi"><button type="button" data-page="previous" aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button><span data-page-numbers></span><button type="button" data-page="next" aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button></nav></footer>
            </section>
            <p class="payment-demo-disclaimer"><i class="bi bi-info-circle"></i> QRIS, Virtual Account, verifikasi transfer, penerbitan dokumen, dan reaktivasi jaringan adalah simulasi. Tidak ada transaksi bank, payment gateway, database, atau MikroTik sungguhan yang dihubungkan.</p>
        </section>
    </div>

    <div class="modal fade" id="paymentDetailModal" tabindex="-1" aria-labelledby="paymentDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content payment-modal">
            <div class="modal-header"><div><p class="payment-eyebrow">RINCIAN TRANSAKSI</p><h2 class="modal-title" id="paymentDetailTitle">Detail Pembayaran</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body" data-payment-detail></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button><button type="button" class="btn btn-outline-primary" data-payment-action="view-invoice"><i class="bi bi-file-earmark-text"></i> Lihat Invoice Tagihan</button><button type="button" class="btn btn-primary" data-payment-action="pay-selected"><i class="bi bi-credit-card"></i> Lanjutkan Pembayaran</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="paymentFlowModal" tabindex="-1" aria-labelledby="paymentFlowTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content payment-modal">
            <div class="modal-header"><div><p class="payment-eyebrow">SIMULASI PEMBAYARAN</p><h2 class="modal-title" id="paymentFlowTitle">Pilih Metode Pembayaran</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body" data-payment-flow></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-payment-action="cancel-flow">Batalkan</button><button type="button" class="btn btn-primary" data-payment-action="flow-primary"><i class="bi bi-arrow-right"></i> Lanjutkan</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="paymentVerifyModal" tabindex="-1" aria-labelledby="paymentVerifyTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content payment-modal">
            <div class="modal-header"><div><p class="payment-eyebrow">TINDAKAN ADMIN</p><h2 class="modal-title" id="paymentVerifyTitle">Verifikasi Pembayaran</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <form data-verify-form><div class="modal-body"><p data-verify-summary class="payment-verify-summary"></p><label class="payment-field"><span data-verify-note-label>Catatan verifikasi</span><textarea class="form-control" rows="3" data-verify-note placeholder="Catatan untuk riwayat transaksi"></textarea></label><div class="invalid-feedback" data-verify-error></div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" data-verify-submit>Konfirmasi</button></div></form>
        </div></div>
    </div>

    <div class="modal fade" id="paymentDocumentModal" tabindex="-1" aria-labelledby="paymentDocumentTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content payment-modal">
            <div class="modal-header"><div><p class="payment-eyebrow" data-document-kicker>DOKUMEN</p><h2 class="modal-title" id="paymentDocumentTitle">Invoice</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body payment-document-modal-body"><article class="payment-print-document" data-payment-document></article></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button><button type="button" class="btn btn-primary" data-payment-action="print-document"><i class="bi bi-printer"></i> Cetak / Simpan PDF</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="paymentConfirmModal" tabindex="-1" aria-labelledby="paymentConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content payment-modal">
            <div class="modal-header"><h2 class="modal-title" id="paymentConfirmTitle">Konfirmasi</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body" data-confirm-message></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-danger" data-payment-action="confirm-reset">Ya, reset demo</button></div>
        </div></div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/payment-management.js') }}?v=1" defer></script>
@endpush
