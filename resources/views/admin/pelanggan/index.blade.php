@extends('layouts.kinet')

@section('page-title', 'Data Pelanggan')

@push('styles')
    <link href="{{ asset('assets/css/customer-management.css') }}?v=6" rel="stylesheet">
@endpush

@section('content')
    <div class="dashboard-page customer-module">
        <nav class="dashboard-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
            <span aria-current="page">Data Pelanggan</span>
        </nav>

        <section class="customer-page-card">
            <header class="customer-page-heading">
                <div>
                    <p class="customer-eyebrow">MANAJEMEN PELANGGAN</p>
                    <h2>Data Pelanggan</h2>
                    <p>Kelola informasi pelanggan, langganan internet, dan status layanan KiNet eBilling.</p>
                </div>
                <div class="customer-header-actions">
                    <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#customerImportModal">
                        <i class="bi bi-upload"></i> Import
                    </button>
                    <button class="btn btn-outline-secondary" type="button" id="customerExportButton">
                        <i class="bi bi-download"></i> Export CSV
                    </button>
                    <button class="btn btn-primary" type="button" data-action="add-customer">
                        <i class="bi bi-person-plus"></i> Tambah Pelanggan
                    </button>
                </div>
            </header>

            <div class="customer-stat-grid" aria-label="Ringkasan pelanggan">
                <button class="customer-stat stat-blue" type="button" data-status-card="Semua">
                    <span class="customer-stat-icon"><i class="bi bi-people"></i></span>
                    <span class="customer-stat-copy"><span>Total Pelanggan</span><strong>1.250</strong><small>Seluruh pelanggan terdaftar</small></span>
                    <i class="bi bi-arrow-up-right customer-stat-arrow" aria-hidden="true"></i>
                </button>
                <button class="customer-stat stat-green" type="button" data-status-card="Aktif">
                    <span class="customer-stat-icon"><i class="bi bi-person-check"></i></span>
                    <span class="customer-stat-copy"><span>Pelanggan Aktif</span><strong>1.180</strong><small>Layanan berjalan normal</small></span>
                    <i class="bi bi-arrow-up-right customer-stat-arrow" aria-hidden="true"></i>
                </button>
                <button class="customer-stat stat-gray" type="button" data-status-card="Nonaktif">
                    <span class="customer-stat-icon"><i class="bi bi-person-dash"></i></span>
                    <span class="customer-stat-copy"><span>Pelanggan Nonaktif</span><strong>45</strong><small>Akun tidak aktif</small></span>
                    <i class="bi bi-arrow-up-right customer-stat-arrow" aria-hidden="true"></i>
                </button>
                <button class="customer-stat stat-red" type="button" data-status-card="Terisolasi">
                    <span class="customer-stat-icon"><i class="bi bi-person-lock"></i></span>
                    <span class="customer-stat-copy"><span>Pelanggan Terisolasi</span><strong>25</strong><small>Layanan perlu tindak lanjut</small></span>
                    <i class="bi bi-arrow-up-right customer-stat-arrow" aria-hidden="true"></i>
                </button>
            </div>

            <section class="customer-table-section" aria-label="Daftar pelanggan">
                <div class="customer-table-heading">
                    <div><h3>Daftar pelanggan</h3><p id="customerResultCount" aria-live="polite"></p></div>
                    <div class="customer-bulk-controls">
                        <label class="visually-hidden" for="bulkAction">Aksi massal</label>
                        <select id="bulkAction" class="form-select" aria-label="Aksi massal">
                            <option value="">Aksi massal</option><option value="Aktif">Aktifkan</option><option value="Nonaktif">Nonaktifkan</option><option value="delete">Hapus</option>
                        </select>
                        <button class="btn btn-outline-secondary" type="button" id="applyBulkAction">Terapkan</button>
                    </div>
                </div>
                <div class="customer-table-card">
                    <div class="table-responsive">
                        <table class="table align-middle customer-table">
                            <thead><tr>
                                <th class="customer-check-col"><input class="form-check-input" id="selectAllCustomers" type="checkbox" aria-label="Pilih semua pelanggan"></th>
                                <th>No.</th><th><button type="button" class="customer-sort" data-sort="kode_pelanggan">Kode Pelanggan <i class="bi bi-arrow-down-up"></i></button></th>
                                <th><button type="button" class="customer-sort" data-sort="nama">Nama Pelanggan <i class="bi bi-arrow-down-up"></i></button></th>
                                <th><button type="button" class="customer-sort" data-sort="whatsapp">Nomor WhatsApp <i class="bi bi-arrow-down-up"></i></button></th>
                                <th><button type="button" class="customer-sort" data-sort="alamat">Alamat <i class="bi bi-arrow-down-up"></i></button></th>
                                <th><button type="button" class="customer-sort" data-sort="paket">Paket Internet <i class="bi bi-arrow-down-up"></i></button></th>
                                <th><button type="button" class="customer-sort" data-sort="mitra">Mitra <i class="bi bi-arrow-down-up"></i></button></th>
                                <th><button type="button" class="customer-sort" data-sort="tanggal_aktivasi">Tanggal Aktivasi <i class="bi bi-arrow-down-up"></i></button></th>
                                <th><button type="button" class="customer-sort" data-sort="status_pelanggan">Status pelanggan <i class="bi bi-arrow-down-up"></i></button></th><th class="text-end">Aksi</th>
                            </tr></thead>
                            <tbody id="customerTableBody"></tbody>
                        </table>
                    </div>
                    <div class="customer-table-footer">
                        <label class="customer-page-size">Tampilkan
                            <select id="customerPageSize" class="form-select" aria-label="Jumlah data per halaman"><option>5</option><option selected>10</option><option>25</option><option>50</option></select>
                        </label>
                        <span class="customer-page-info" id="customerPageInfo"></span>
                        <nav class="customer-pagination" aria-label="Navigasi daftar pelanggan">
                            <button type="button" data-page="first" aria-label="Halaman pertama"><i class="bi bi-chevron-double-left"></i></button>
                            <button type="button" data-page="previous" aria-label="Halaman sebelumnya"><i class="bi bi-chevron-left"></i></button>
                            <span id="customerPageNumbers"></span>
                            <button type="button" data-page="next" aria-label="Halaman berikutnya"><i class="bi bi-chevron-right"></i></button>
                            <button type="button" data-page="last" aria-label="Halaman terakhir"><i class="bi bi-chevron-double-right"></i></button>
                        </nav>
                    </div>
                </div>
            </section>
        </section>
    </div>

    <div class="modal fade" id="customerFormModal" tabindex="-1" aria-labelledby="customerFormTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable"><div class="modal-content customer-modal">
            <div class="modal-header"><div><p class="customer-eyebrow">FORM DATA PELANGGAN</p><h2 class="modal-title" id="customerFormTitle">Tambah Pelanggan</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="customer-wizard-progress" aria-label="Tahapan formulir"><span data-step-label="1" class="active">1 <b>Informasi pelanggan</b></span><i></i><span data-step-label="2">2 <b>Pendaftaran</b></span><i></i><span data-step-label="3">3 <b>Langganan</b></span><i></i><span data-step-label="4">4 <b>Koneksi</b></span></div>
            <form id="customerForm" novalidate>
                <div class="modal-body">
                    <section class="customer-form-step active" data-form-step="1"><h3>Informasi pelanggan</h3><p>Masukkan identitas dan alamat pelanggan.</p><div class="customer-form-grid">
                        <label class="customer-field"><span>Kode pelanggan</span><input class="form-control" name="kode_pelanggan" readonly></label>
                        <label class="customer-field"><span>Nama lengkap <em>*</em></span><input class="form-control" name="nama" required minlength="2" maxlength="150" autocomplete="name"></label>
                        <label class="customer-field"><span>Nomor WhatsApp <em>*</em></span><input class="form-control" name="whatsapp" inputmode="tel" required pattern="[0-9+]{8,20}" maxlength="20" autocomplete="tel" title="Masukkan nomor WhatsApp 8–20 digit, boleh diawali tanda +"></label>
                        <label class="customer-field"><span>Email</span><input class="form-control" type="email" name="email"></label>
                        <label class="customer-field customer-field-wide"><span>Alamat <em>*</em></span><textarea class="form-control" name="alamat" rows="2" required></textarea></label>
                        <label class="customer-field"><span>Provinsi</span><input class="form-control" name="provinsi" value="Sumatera Utara"></label>
                        <label class="customer-field"><span>Kabupaten/Kota</span><input class="form-control" name="kota" value="Medan"></label>
                        <label class="customer-field"><span>Kecamatan</span><input class="form-control" name="kecamatan"></label>
                        <label class="customer-field"><span>Kelurahan</span><input class="form-control" name="kelurahan"></label>
                    </div></section>
                    <section class="customer-form-step" data-form-step="2"><h3>Informasi pendaftaran</h3><p>Lengkapi sumber pendaftaran dan penanggung jawab.</p><div class="customer-form-grid">
                        <label class="customer-field"><span>Tanggal pendaftaran</span><input class="form-control" type="date" name="tanggal_pendaftaran"></label>
                        <label class="customer-field"><span>Sumber pendaftaran</span><select class="form-select" name="sumber_pendaftaran"><option>Walk-in</option><option>WhatsApp</option><option>Website</option><option>Referensi</option></select></label>
                        <label class="customer-field"><span>Mitra</span><select class="form-select" name="mitra"><option>Langsung</option><option>Mitra A</option><option>Mitra B</option><option>Mitra C</option></select></label>
                        <label class="customer-field"><span>Petugas input</span><input class="form-control" name="petugas" value="Admin KiNet"></label>
                    </div></section>
                    <section class="customer-form-step" data-form-step="3"><h3>Informasi langganan</h3><p>Pilih paket dan atur tanggal layanan pelanggan.</p><div class="customer-form-grid">
                        <label class="customer-field"><span>Paket internet</span><select class="form-select" name="paket"><option value="20 Mbps">20 Mbps</option><option value="30 Mbps">30 Mbps</option><option value="50 Mbps">50 Mbps</option><option value="100 Mbps">100 Mbps</option></select></label>
                        <label class="customer-field"><span>Harga paket</span><input class="form-control" name="harga" readonly></label>
                        <label class="customer-field"><span>Kecepatan</span><input class="form-control" name="kecepatan" readonly></label>
                        <label class="customer-field"><span>Tanggal aktivasi</span><input class="form-control" type="date" name="tanggal_aktivasi"></label>
                        <label class="customer-field"><span>Mulai berlangganan</span><input class="form-control" type="date" name="tanggal_mulai"></label>
                        <label class="customer-field"><span>Status pelanggan</span><select class="form-select" name="status_pelanggan"><option>Aktif</option><option>Nonaktif</option><option>Terisolasi</option></select></label>
                    </div></section>
                    <section class="customer-form-step" data-form-step="4"><h3>Informasi koneksi</h3><p>Informasi koneksi simulasi untuk pratinjau administrasi.</p><div class="customer-form-grid">
                        <label class="customer-field"><span>Router</span><select class="form-select" name="router"><option>Router Medan 01</option><option>Router Medan 02</option><option>Belum ditentukan</option></select></label>
                        <label class="customer-field"><span>Username PPPoE</span><input class="form-control" name="username_pppoe"></label>
                        <label class="customer-field"><span>Password PPPoE</span><input class="form-control" type="password" name="password_pppoe"></label>
                        <label class="customer-field"><span>Profil layanan</span><input class="form-control" name="profil_layanan"></label>
                        <label class="customer-field"><span>IP Address</span><input class="form-control" name="ip_address" placeholder="Otomatis"></label>
                    </div></section>
                </div>
                <div class="modal-footer customer-form-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-outline-secondary" id="customerPreviousStep"><i class="bi bi-arrow-left"></i> Kembali</button><button type="button" class="btn btn-primary" id="customerNextStep">Selanjutnya <i class="bi bi-arrow-right"></i></button><button type="submit" class="btn btn-primary d-none" id="customerSaveButton"><i class="bi bi-check2"></i> <span data-save-label>Simpan</span></button></div>
            </form>
        </div></div>
    </div>

    <div class="modal fade" id="customerDetailModal" tabindex="-1" aria-labelledby="customerDetailTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable customer-detail-dialog"><div class="modal-content customer-modal">
            <div class="modal-header"><div><p class="customer-eyebrow">PROFIL PELANGGAN</p><h2 class="modal-title" id="customerDetailTitle">Detail Pelanggan</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body">
                <div id="customerProfileSummary" class="customer-profile-summary"></div>
                <ul class="nav nav-tabs customer-detail-tabs" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" id="customerProfileTabButton" data-bs-toggle="tab" data-bs-target="#customerProfileTab" type="button" role="tab" aria-controls="customerProfileTab" aria-selected="true">Profil Pelanggan</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="customerSubscriptionTabButton" data-bs-toggle="tab" data-bs-target="#customerSubscriptionTab" type="button" role="tab" aria-controls="customerSubscriptionTab" aria-selected="false">Langganan</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="customerConnectionTabButton" data-bs-toggle="tab" data-bs-target="#customerConnectionTab" type="button" role="tab" aria-controls="customerConnectionTab" aria-selected="false">Koneksi Internet</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="customerInvoicesTabButton" data-bs-toggle="tab" data-bs-target="#customerInvoicesTab" type="button" role="tab" aria-controls="customerInvoicesTab" aria-selected="false">Riwayat Tagihan</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="customerPaymentsTabButton" data-bs-toggle="tab" data-bs-target="#customerPaymentsTab" type="button" role="tab" aria-controls="customerPaymentsTab" aria-selected="false">Riwayat Pembayaran</button></li>
                    <li class="nav-item" role="presentation"><button class="nav-link" id="customerActivityTabButton" data-bs-toggle="tab" data-bs-target="#customerActivityTab" type="button" role="tab" aria-controls="customerActivityTab" aria-selected="false">Riwayat Aktivitas</button></li>
                </ul>
                <div class="tab-content customer-detail-content">
                    <div class="tab-pane fade show active" id="customerProfileTab" role="tabpanel" aria-labelledby="customerProfileTabButton"></div><div class="tab-pane fade" id="customerSubscriptionTab" role="tabpanel" aria-labelledby="customerSubscriptionTabButton"></div>
                    <div class="tab-pane fade" id="customerConnectionTab" role="tabpanel" aria-labelledby="customerConnectionTabButton"></div><div class="tab-pane fade" id="customerInvoicesTab" role="tabpanel" aria-labelledby="customerInvoicesTabButton"></div>
                    <div class="tab-pane fade" id="customerPaymentsTab" role="tabpanel" aria-labelledby="customerPaymentsTabButton"></div><div class="tab-pane fade" id="customerActivityTab" role="tabpanel" aria-labelledby="customerActivityTabButton"></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="customerConfirmModal" tabindex="-1" aria-labelledby="customerConfirmTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content customer-modal">
            <div class="modal-header"><h2 class="modal-title" id="customerConfirmTitle">Konfirmasi tindakan</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body" id="customerConfirmMessage"></div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-danger" id="customerConfirmAction">Ya, lanjutkan</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="customerImportModal" tabindex="-1" aria-labelledby="customerImportTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content customer-modal">
            <div class="modal-header"><div><p class="customer-eyebrow">DATA SIMULASI FRONTEND</p><h2 class="modal-title" id="customerImportTitle">Import pelanggan</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body"><p class="customer-import-note"><i class="bi bi-info-circle"></i> CSV hanya diproses di browser dan tidak dikirim ke server.</p>
                <label class="customer-field"><span>Pilih file CSV</span><input id="customerImportFile" class="form-control" type="file" accept=".csv,text/csv"></label>
                <div id="customerImportPreview" class="customer-import-preview" aria-live="polite">Pilih file untuk melihat pratinjau.</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button><button type="button" class="btn btn-primary" id="customerImportCommit" disabled><i class="bi bi-upload"></i> Import data</button></div>
        </div></div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3 customer-toast-container">
        <div id="customerToast" class="toast border-0" role="status" aria-live="polite" aria-atomic="true">
            <div class="d-flex"><div class="toast-body" id="customerToastMessage"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/customer-management.js') }}?v=5" defer></script>
@endpush
