import $ from 'jquery';
import DataTable from 'datatables.net-bs5';
import 'datatables.net-bs5/css/dataTables.bootstrap5.css';
import '../css/datatables.css';

window.$ = window.jQuery = $;
DataTable.use($);

const escapeHtml = (value) => $('<div>').text(value ?? '').html();
const statusClass = (status) => ({
    aktif: 'is-active',
    nonaktif: 'is-inactive',
    calon: 'is-warning',
    isolir: 'is-danger'
}[status] ?? 'is-inactive');

const serverColumns = {
    pelanggan: [
        { data: 'kode_pelanggan', render: DataTable.render.text() },
        { data: 'nama_pelanggan', render: DataTable.render.text() },
        { data: 'no_hp', render: DataTable.render.text() },
        { data: 'mitra', render: (value) => escapeHtml(value || 'Langsung') },
        { data: 'status', render: (value, type) => type === 'display' ? `<span class="status-badge ${statusClass(value)}">${escapeHtml(value)}</span>` : value },
        { data: null, orderable: false, searchable: false, render: (_value, type, row) => type !== 'display' ? '' : `<div class="table-actions"><a class="btn btn-outline-secondary" href="${row.show_url}" aria-label="Lihat pelanggan"><i class="bi bi-eye"></i></a><a class="btn btn-outline-secondary" href="${row.edit_url}"><i class="bi bi-pencil"></i> Edit</a></div>` }
    ],
    mitra: [
        { data: 'kode_mitra', render: DataTable.render.text() },
        { data: 'nama_mitra', render: DataTable.render.text() },
        { data: 'nama_pic', render: (value) => escapeHtml(value || '—') },
        { data: 'no_hp', render: (value) => escapeHtml(value || '—') },
        { data: 'email', render: (value) => escapeHtml(value || '—') },
        { data: 'pelanggan_count', render: DataTable.render.number('.', ',', 0) },
        { data: 'status', render: (value, type) => type === 'display' ? `<span class="status-badge ${statusClass(value)}">${escapeHtml(value)}</span>` : value },
        { data: null, orderable: false, searchable: false, render: (_value, type, row) => type !== 'display' ? '' : `<div class="table-actions"><a class="btn btn-outline-secondary" href="${row.edit_url}"><i class="bi bi-pencil"></i> Edit</a></div>` }
    ],
    paket: [
        { data: 'kode_paket', render: DataTable.render.text() },
        { data: 'nama_paket', render: DataTable.render.text() },
        { data: 'download_mbps', render: (value, type) => type === 'display' ? (value ? `${escapeHtml(value)} Mbps` : '—') : (value ?? 0) },
        { data: 'upload_mbps', render: (value, type) => type === 'display' ? (value ? `${escapeHtml(value)} Mbps` : '—') : (value ?? 0) },
        { data: 'harga', render: (value, type) => type === 'display' ? `Rp ${Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 })}` : Number(value) },
        { data: 'is_active', render: (value, type) => type === 'display' ? `<span class="status-badge ${value ? 'is-active' : 'is-inactive'}">${value ? 'Aktif' : 'Nonaktif'}</span>` : Number(Boolean(value)) },
        { data: null, orderable: false, searchable: false, render: (_value, type, row) => type !== 'display' ? '' : `<div class="table-actions"><a class="btn btn-outline-secondary" href="${row.edit_url}"><i class="bi bi-pencil"></i> Edit</a></div>` }
    ],
    langganan: [
        { data: 'kode_pelanggan', render: DataTable.render.text() },
        { data: 'nama_pelanggan', render: DataTable.render.text() },
        { data: 'nama_paket', render: DataTable.render.text() },
        { data: 'tanggal_aktivasi', render: (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`)) : '—' },
        { data: 'tanggal_jatuh_tempo', render: (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`)) : '—' },
        { data: 'harga_snapshot', render: (value, type) => type === 'display' ? `Rp ${Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 })}` : Number(value) },
        { data: 'status', render: (value, type) => type === 'display' ? `<span class="status-badge ${statusClass(value)}">${escapeHtml(({ menunggu: 'Menunggu', aktif: 'Aktif', ditangguhkan: 'Ditangguhkan', berhenti: 'Berhenti' })[value] ?? value)}</span>` : value },
        { data: null, orderable: false, searchable: false, render: (_value, type, row) => type !== 'display' ? '' : `<div class="table-actions"><a class="btn btn-outline-secondary" href="${row.edit_url}"><i class="bi bi-pencil"></i> Edit</a></div>` }
    ],
    tagihan: [
        { data: 'no_invoice', render: DataTable.render.text() },
        { data: 'kode_pelanggan', render: DataTable.render.text() },
        { data: 'nama_pelanggan', render: DataTable.render.text() },
        { data: 'nama_paket', render: DataTable.render.text() },
        { data: 'periode', render: (value) => value ? new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date(`${value}-01T00:00:00`)) : '—' },
        { data: 'jatuh_tempo', render: (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`)) : '—' },
        { data: 'total_tagihan', render: (value, type) => type === 'display' ? `Rp ${Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 })}` : Number(value) },
        { data: 'status', render: (value, type) => type === 'display' ? `<span class="status-badge ${({ lunas: 'is-active', sebagian: 'is-warning', belum_bayar: 'is-warning', dibatalkan: 'is-inactive' })[value] ?? 'is-inactive'}">${escapeHtml(({ belum_bayar: 'Belum dibayar', sebagian: 'Sebagian', lunas: 'Lunas', dibatalkan: 'Dibatalkan', draft: 'Draft' })[value] ?? value)}</span>` : value },
        { data: null, orderable: false, searchable: false, render: (_value, type, row) => type !== 'display' ? '' : `<div class="table-actions"><a class="btn btn-outline-secondary" href="${row.show_url}" aria-label="Lihat tagihan"><i class="bi bi-eye"></i> Detail</a></div>` }
    ],
    pengingat: [
        { data: 'no_invoice', render: DataTable.render.text() },
        { data: 'kode_pelanggan', render: DataTable.render.text() },
        { data: 'nama_pelanggan', render: DataTable.render.text() },
        { data: 'no_hp', render: DataTable.render.text() },
        { data: 'nama_paket', render: DataTable.render.text() },
        { data: 'jatuh_tempo', render: (value) => value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`)) : '—' },
        { data: 'sisa_tagihan', render: (value, type) => type === 'display' ? `Rp ${Number(value).toLocaleString('id-ID', { maximumFractionDigits: 0 })}` : Number(value) },
        { data: 'status', render: (value, type) => type === 'display' ? `<span class="status-badge ${value === 'sebagian' ? 'is-warning' : (value === 'belum_bayar' ? 'is-danger' : 'is-active')}">${escapeHtml(({ belum_bayar: 'Belum dibayar', sebagian: 'Dibayar sebagian', lunas: 'Lunas', dibatalkan: 'Dibatalkan' })[value] ?? value)}</span>` : value },
        { data: null, orderable: false, searchable: false, render: (_value, type, row) => type !== 'display' ? '' : `<div class="table-actions"><a class="btn btn-outline-secondary" href="${row.show_url}" aria-label="Lihat detail tagihan"><i class="bi bi-eye"></i> Detail</a></div>` }
    ]
};

const tableLanguage = {
    processing: 'Memuat data...',
    search: 'Cari:',
    lengthMenu: 'Tampilkan _MENU_',
    info: 'Menampilkan _START_–_END_ dari _TOTAL_ data',
    infoEmpty: 'Tidak ada data untuk ditampilkan',
    infoFiltered: '(disaring dari _MAX_ total data)',
    loadingRecords: 'Memuat...',
    zeroRecords: 'Data yang dicari tidak ditemukan',
    emptyTable: 'Belum ada data untuk ditampilkan',
    paginate: {
        first: '<i class="bi bi-chevron-double-left" aria-hidden="true"></i><span class="visually-hidden">Pertama</span>',
        last: '<i class="bi bi-chevron-double-right" aria-hidden="true"></i><span class="visually-hidden">Terakhir</span>',
        next: '<i class="bi bi-chevron-right" aria-hidden="true"></i><span class="visually-hidden">Berikutnya</span>',
        previous: '<i class="bi bi-chevron-left" aria-hidden="true"></i><span class="visually-hidden">Sebelumnya</span>'
    },
    aria: {
        sortAscending: ': aktifkan untuk mengurutkan menaik',
        sortDescending: ': aktifkan untuk mengurutkan menurun',
        paginate: { first: 'Halaman pertama', last: 'Halaman terakhir', next: 'Halaman berikutnya', previous: 'Halaman sebelumnya', number: 'Halaman %d' }
    }
};

document.querySelectorAll('table.js-data-table').forEach((table) => {
    const type = table.dataset.tableType;
    const isServerSide = table.dataset.serverSide === 'true';
    const options = {
        language: tableLanguage,
        pageLength: isServerSide ? 10 : 5,
        lengthMenu: [5, 10, 25, 50],
        autoWidth: false,
        order: [],
        responsive: false,
        searching: true,
        paging: true,
        info: true,
        columnDefs: [{ targets: '_all', defaultContent: '—' }]
    };

    if (isServerSide) {
        options.processing = true;
        options.serverSide = true;
        options.searchDelay = 350;
        options.ajax = { url: table.dataset.ajaxUrl, type: 'GET' };
        if (type === 'mitra') {
            options.ajax.data = (data) => {
                data.status = document.getElementById('mitraStatusFilter')?.value ?? '';
            };
        }
        if (type === 'paket') {
            options.ajax.data = (data) => {
                data.status = document.getElementById('paketStatusFilter')?.value ?? '';
            };
        }
        if (type === 'langganan') {
            options.ajax.data = (data) => {
                data.status = document.getElementById('langgananStatusFilter')?.value ?? '';
            };
        }
        if (type === 'tagihan') {
            options.ajax.data = (data) => {
                data.status = document.getElementById('tagihanStatusFilter')?.value ?? '';
            };
        }
        if (type === 'pengingat') {
            options.ajax.data = (data) => {
                data.days = document.querySelector('.pengingat-tab.is-selected')?.dataset.pengingatDays ?? '';
            };
        }
        options.columns = serverColumns[type];
        options.drawCallback = () => {
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach((row) => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 1 && cells[0].hasAttribute('colspan')) row.remove();
            });
        };
    }

    const dataTable = $(table).DataTable(options);

    if (type === 'mitra') {
        const statusFilter = document.getElementById('mitraStatusFilter');
        const statusCards = document.querySelectorAll('[data-mitra-status-card]');
        const applyStatus = (status) => {
            statusFilter.value = status;
            statusCards.forEach((card) => {
                const isSelected = card.dataset.mitraStatusCard === status;
                card.classList.toggle('is-selected', isSelected);
                card.setAttribute('aria-pressed', String(isSelected));
            });
            dataTable.ajax.reload();
        };

        statusFilter.addEventListener('change', () => applyStatus(statusFilter.value));
        statusCards.forEach((card) => card.addEventListener('click', () => applyStatus(card.dataset.mitraStatusCard)));
    }

    if (type === 'paket') {
        const statusFilter = document.getElementById('paketStatusFilter');
        const statusCards = document.querySelectorAll('[data-paket-status-card]');
        const applyStatus = (status) => {
            statusFilter.value = status;
            statusCards.forEach((card) => {
                const isSelected = card.dataset.paketStatusCard === status;
                card.classList.toggle('is-selected', isSelected);
                card.setAttribute('aria-pressed', String(isSelected));
            });
            dataTable.ajax.reload();
        };

        statusFilter.addEventListener('change', () => applyStatus(statusFilter.value));
        statusCards.forEach((card) => card.addEventListener('click', () => applyStatus(card.dataset.paketStatusCard)));
    }

    if (type === 'langganan') {
        const statusFilter = document.getElementById('langgananStatusFilter');
        const statusCards = document.querySelectorAll('[data-langganan-status-card]');
        const applyStatus = (status) => {
            statusFilter.value = status;
            statusCards.forEach((card) => {
                const isSelected = card.dataset.langgananStatusCard === status;
                card.classList.toggle('is-selected', isSelected);
                card.setAttribute('aria-pressed', String(isSelected));
            });
            dataTable.ajax.reload();
        };

        statusFilter.addEventListener('change', () => applyStatus(statusFilter.value));
        statusCards.forEach((card) => card.addEventListener('click', () => applyStatus(card.dataset.langgananStatusCard)));
    }

    if (type === 'tagihan') {
        const statusFilter = document.getElementById('tagihanStatusFilter');
        const statusCards = document.querySelectorAll('[data-tagihan-status-card]');
        const applyStatus = (status) => {
            statusFilter.value = status;
            statusCards.forEach((card) => {
                const isSelected = card.dataset.tagihanStatusCard === status;
                card.classList.toggle('is-selected', isSelected);
                card.setAttribute('aria-pressed', String(isSelected));
            });
            dataTable.ajax.reload();
        };

        statusFilter.addEventListener('change', () => applyStatus(statusFilter.value));
        statusCards.forEach((card) => card.addEventListener('click', () => applyStatus(card.dataset.tagihanStatusCard)));
    }

    if (type === 'pengingat') {
        const tabs = document.querySelectorAll('[data-pengingat-days]');
        const heading = document.querySelector('[data-pengingat-heading]');
        const headings = {
            '': 'Semua pelanggan',
            '7': 'Jatuh tempo dalam 7 hari',
            '3': 'Jatuh tempo dalam 3 hari',
            '1': 'Jatuh tempo besok'
        };

        tabs.forEach((tab) => tab.addEventListener('click', () => {
            tabs.forEach((item) => {
                const isSelected = item === tab;
                item.classList.toggle('is-selected', isSelected);
                item.setAttribute('aria-selected', String(isSelected));
            });
            heading.textContent = headings[tab.dataset.pengingatDays] ?? headings[''];
            dataTable.ajax.reload();
        }));
    }
});
