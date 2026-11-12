document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.customer-module');
    if (!root || !window.bootstrap) return;

    const storageKey = 'kinet.customer-demo.v1';
    const packageCatalog = {
        '20 Mbps': { price: 150000, speed: '20 Mbps' },
        '30 Mbps': { price: 200000, speed: '30 Mbps' },
        '50 Mbps': { price: 275000, speed: '50 Mbps' },
        '100 Mbps': { price: 450000, speed: '100 Mbps' }
    };
    const seedCustomers = [
        { id: 'demo-1', kode_pelanggan: 'KNT-00001', nama: 'Andi Ramadhan', whatsapp: '081234567801', email: 'andi.ramadhan@example.test', alamat: 'Medan Johor', provinsi: 'Sumatera Utara', kota: 'Medan', kecamatan: 'Medan Johor', kelurahan: 'Pangkalan Masyhur', paket: '20 Mbps', harga: 150000, kecepatan: '20 Mbps', mitra: 'Mitra A', tanggal_aktivasi: '2026-01-01', tanggal_mulai: '2026-01-01', tanggal_pendaftaran: '2025-12-29', sumber_pendaftaran: 'Referensi', petugas: 'Admin KiNet', status_pelanggan: 'Aktif', status_layanan: 'Aktif', router: 'Router Medan 01', username_pppoe: 'andi.knt00001', password_pppoe: '••••••••', profil_layanan: 'Paket 20M', ip_address: '10.10.1.11' },
        { id: 'demo-2', kode_pelanggan: 'KNT-00002', nama: 'Budi Santoso', whatsapp: '081234567802', email: 'budi.santoso@example.test', alamat: 'Medan Selayang', provinsi: 'Sumatera Utara', kota: 'Medan', kecamatan: 'Medan Selayang', kelurahan: 'Sempakata', paket: '30 Mbps', harga: 200000, kecepatan: '30 Mbps', mitra: 'Mitra B', tanggal_aktivasi: '2026-01-05', tanggal_mulai: '2026-01-05', tanggal_pendaftaran: '2026-01-02', sumber_pendaftaran: 'Walk-in', petugas: 'Admin KiNet', status_pelanggan: 'Terisolasi', status_layanan: 'Terisolasi', router: 'Router Medan 01', username_pppoe: 'budi.knt00002', password_pppoe: '••••••••', profil_layanan: 'Paket 30M', ip_address: '10.10.1.12' },
        { id: 'demo-3', kode_pelanggan: 'KNT-00003', nama: 'Siti Rahma', whatsapp: '081234567803', email: 'siti.rahma@example.test', alamat: 'Medan Amplas', provinsi: 'Sumatera Utara', kota: 'Medan', kecamatan: 'Medan Amplas', kelurahan: 'Harjosari II', paket: '50 Mbps', harga: 275000, kecepatan: '50 Mbps', mitra: 'Mitra A', tanggal_aktivasi: '2026-01-10', tanggal_mulai: '2026-01-10', tanggal_pendaftaran: '2026-01-07', sumber_pendaftaran: 'WhatsApp', petugas: 'Admin KiNet', status_pelanggan: 'Aktif', status_layanan: 'Aktif', router: 'Router Medan 02', username_pppoe: 'siti.knt00003', password_pppoe: '••••••••', profil_layanan: 'Paket 50M', ip_address: '10.10.2.13' },
        { id: 'demo-4', kode_pelanggan: 'KNT-00004', nama: 'Dedi Saputra', whatsapp: '081234567804', email: 'dedi.saputra@example.test', alamat: 'Medan Denai', provinsi: 'Sumatera Utara', kota: 'Medan', kecamatan: 'Medan Denai', kelurahan: 'Tegal Sari Mandala III', paket: '20 Mbps', harga: 150000, kecepatan: '20 Mbps', mitra: 'Langsung', tanggal_aktivasi: '2026-01-12', tanggal_mulai: '2026-01-12', tanggal_pendaftaran: '2026-01-10', sumber_pendaftaran: 'Website', petugas: 'Admin KiNet', status_pelanggan: 'Nonaktif', status_layanan: 'Nonaktif', router: 'Router Medan 01', username_pppoe: 'dedi.knt00004', password_pppoe: '••••••••', profil_layanan: 'Paket 20M', ip_address: '10.10.1.14' },
        { id: 'demo-5', kode_pelanggan: 'KNT-00005', nama: 'Rina Amelia', whatsapp: '081234567805', email: 'rina.amelia@example.test', alamat: 'Medan Tembung', provinsi: 'Sumatera Utara', kota: 'Medan', kecamatan: 'Medan Tembung', kelurahan: 'Bantan', paket: '100 Mbps', harga: 450000, kecepatan: '100 Mbps', mitra: 'Mitra C', tanggal_aktivasi: '2026-01-15', tanggal_mulai: '2026-01-15', tanggal_pendaftaran: '2026-01-12', sumber_pendaftaran: 'Referensi', petugas: 'Admin KiNet', status_pelanggan: 'Aktif', status_layanan: 'Aktif', router: 'Router Medan 02', username_pppoe: 'rina.knt00005', password_pppoe: '••••••••', profil_layanan: 'Paket 100M', ip_address: '10.10.2.15' },
        { id: 'demo-6', kode_pelanggan: 'KNT-00006', nama: 'Fajar Nugraha', whatsapp: '081234567806', email: 'fajar.nugraha@example.test', alamat: 'Medan Baru', paket: '30 Mbps', mitra: 'Mitra A', tanggal_aktivasi: '2026-01-18', status_pelanggan: 'Aktif' },
        { id: 'demo-7', kode_pelanggan: 'KNT-00007', nama: 'Dewi Anggraini', whatsapp: '081234567807', email: 'dewi.anggraini@example.test', alamat: 'Medan Kota', paket: '50 Mbps', mitra: 'Langsung', tanggal_aktivasi: '2026-01-20', status_pelanggan: 'Aktif' },
        { id: 'demo-8', kode_pelanggan: 'KNT-00008', nama: 'Rizky Maulana', whatsapp: '081234567808', email: 'rizky.maulana@example.test', alamat: 'Medan Barat', paket: '20 Mbps', mitra: 'Mitra B', tanggal_aktivasi: '2026-01-22', status_pelanggan: 'Terisolasi' },
        { id: 'demo-9', kode_pelanggan: 'KNT-00009', nama: 'Maya Putri', whatsapp: '081234567809', email: 'maya.putri@example.test', alamat: 'Medan Timur', paket: '100 Mbps', mitra: 'Mitra C', tanggal_aktivasi: '2026-01-25', status_pelanggan: 'Aktif' },
        { id: 'demo-10', kode_pelanggan: 'KNT-00010', nama: 'Agus Setiawan', whatsapp: '081234567810', email: 'agus.setiawan@example.test', alamat: 'Medan Perjuangan', paket: '30 Mbps', mitra: 'Mitra A', tanggal_aktivasi: '2026-02-01', status_pelanggan: 'Nonaktif' },
        { id: 'demo-11', kode_pelanggan: 'KNT-00011', nama: 'Nadia Safitri', whatsapp: '081234567811', email: 'nadia.safitri@example.test', alamat: 'Medan Deli', paket: '50 Mbps', mitra: 'Mitra B', tanggal_aktivasi: '2026-02-03', status_pelanggan: 'Aktif' },
        { id: 'demo-12', kode_pelanggan: 'KNT-00012', nama: 'Irwan Hidayat', whatsapp: '081234567812', email: 'irwan.hidayat@example.test', alamat: 'Medan Labuhan', paket: '20 Mbps', mitra: 'Langsung', tanggal_aktivasi: '2026-02-07', status_pelanggan: 'Terisolasi' },
        { id: 'demo-13', kode_pelanggan: 'KNT-00013', nama: 'Lina Marlina', whatsapp: '081234567813', email: 'lina.marlina@example.test', alamat: 'Medan Maimun', paket: '100 Mbps', mitra: 'Mitra C', tanggal_aktivasi: '2026-02-10', status_pelanggan: 'Aktif' },
        { id: 'demo-14', kode_pelanggan: 'KNT-00014', nama: 'Hendra Wijaya', whatsapp: '081234567814', email: 'hendra.wijaya@example.test', alamat: 'Medan Sunggal', paket: '30 Mbps', mitra: 'Mitra A', tanggal_aktivasi: '2026-02-14', status_pelanggan: 'Aktif' },
        { id: 'demo-15', kode_pelanggan: 'KNT-00015', nama: 'Putri Maharani', whatsapp: '081234567815', email: 'putri.maharani@example.test', alamat: 'Medan Helvetia', paket: '50 Mbps', mitra: 'Mitra B', tanggal_aktivasi: '2026-02-18', status_pelanggan: 'Nonaktif' }
    ];

    const money = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    const dateFormatter = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[character]));
    const normalizeStatus = (value) => ({
        aktif: 'Aktif',
        nonaktif: 'Nonaktif',
        terisolasi: 'Terisolasi',
        isolir: 'Terisolasi'
    }[String(value || '').trim().toLocaleLowerCase('id')] || 'Aktif');
    const statusClass = (value) => ({ Aktif: 'status-aktif', Nonaktif: 'status-nonaktif', Terisolasi: 'status-terisolasi' }[value] || 'status-nonaktif');
    const today = new Date().toISOString().slice(0, 10);
    const normalizeCustomer = (customer, index) => {
        const packageInfo = packageCatalog[customer.paket] || packageCatalog['20 Mbps'];
        const status = normalizeStatus(customer.status_pelanggan);
        return {
            id: String(customer.id ?? `import-${Date.now()}-${index}`),
            kode_pelanggan: customer.kode_pelanggan || makeCustomerCode(),
            nama: customer.nama || '',
            whatsapp: customer.whatsapp || '',
            email: customer.email || '',
            alamat: customer.alamat || '',
            provinsi: customer.provinsi || 'Sumatera Utara',
            kota: customer.kota || 'Medan',
            kecamatan: customer.kecamatan || '',
            kelurahan: customer.kelurahan || '',
            paket: customer.paket || '20 Mbps',
            harga: Number(customer.harga ?? packageInfo.price),
            kecepatan: customer.kecepatan || packageInfo.speed,
            mitra: customer.mitra || 'Langsung',
            tanggal_aktivasi: customer.tanggal_aktivasi || today,
            tanggal_mulai: customer.tanggal_mulai || customer.tanggal_aktivasi || today,
            tanggal_pendaftaran: customer.tanggal_pendaftaran || today,
            sumber_pendaftaran: customer.sumber_pendaftaran || 'Walk-in',
            petugas: customer.petugas || 'Admin KiNet',
            status_pelanggan: status,
            status_layanan: customer.status_layanan || status,
            router: customer.router || 'Belum ditentukan',
            username_pppoe: customer.username_pppoe || '',
            password_pppoe: customer.password_pppoe || '••••••••',
            profil_layanan: customer.profil_layanan || `Paket ${packageInfo.speed.replace(' ', '')}`,
            ip_address: customer.ip_address || 'Otomatis'
        };
    };
    const makeCustomerCode = () => {
        const source = Array.isArray(customers) ? customers : seedCustomers;
        const maxCode = source.reduce((maximum, customer) => Math.max(maximum, Number(customer.kode_pelanggan?.match(/\d+$/)?.[0] || 0)), 0);
        return `KNT-${String(maxCode + 1).padStart(5, '0')}`;
    };

    let customers;
    try {
        const saved = localStorage.getItem(storageKey);
        customers = saved ? JSON.parse(saved).map(normalizeCustomer) : seedCustomers.map(normalizeCustomer);
        if (!Array.isArray(customers)) throw new Error('Data simulasi tersimpan tidak berbentuk daftar.');
    } catch (error) {
        customers = seedCustomers.map(normalizeCustomer);
        notify('Data lokal tidak dapat dibaca. Daftar pratinjau awal ditampilkan.', true);
        console.error('Gagal membaca data simulasi pelanggan:', error);
    }

    const state = { page: 1, pageSize: 10, sortKey: '', sortDirection: 1, selected: new Set(), applied: { status: '' }, pendingConfirm: null };
    const formModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('customerFormModal'));
    const detailModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('customerDetailModal'));
    const confirmModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('customerConfirmModal'));
    let editingId = null;
    let currentFormStep = 1;
    let importRows = [];
    document.querySelectorAll('.customer-modal').forEach((modalContent) => {
        modalContent.closest('.modal')?.addEventListener('hide.bs.modal', () => document.activeElement?.blur());
    });

    function notify(message, isError = false) {
        const toastElement = document.getElementById('customerToast');
        const toastMessage = document.getElementById('customerToastMessage');
        if (!toastElement || !toastMessage) return;
        toastMessage.textContent = message;
        toastElement.classList.toggle('is-error', isError);
        bootstrap.Toast.getOrCreateInstance(toastElement, { delay: 3200 }).show();
    }

    function persistCustomers() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(customers));
        } catch (error) {
            notify('Perubahan hanya tersimpan selama halaman ini terbuka karena penyimpanan browser tidak tersedia.', true);
            console.error('Gagal menyimpan data simulasi pelanggan:', error);
        }
    }

    function formatDate(value) {
        if (!value) return '—';
        const date = new Date(`${value}T00:00:00`);
        return Number.isNaN(date.getTime()) ? value : dateFormatter.format(date);
    }

    function monthDate(monthOffset, day = 10) {
        const date = new Date();
        date.setDate(1);
        date.setMonth(date.getMonth() + monthOffset);
        const lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
        date.setDate(Math.min(day, lastDay));
        return [
            date.getFullYear(),
            String(date.getMonth() + 1).padStart(2, '0'),
            String(date.getDate()).padStart(2, '0')
        ].join('-');
    }

    function addDays(value, days) {
        const date = new Date(`${value}T00:00:00`);
        date.setDate(date.getDate() + days);
        return [
            date.getFullYear(),
            String(date.getMonth() + 1).padStart(2, '0'),
            String(date.getDate()).padStart(2, '0')
        ].join('-');
    }

    function formatPeriod(value) {
        const date = new Date(`${value}-01T00:00:00`);
        return Number.isNaN(date.getTime())
            ? value
            : new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(date);
    }

    function filteredCustomers() {
        return customers.filter((customer) => {
            return !state.applied.status || customer.status_pelanggan === state.applied.status;
        }).sort((left, right) => {
            if (!state.sortKey) return 0;
            return String(left[state.sortKey] || '').localeCompare(String(right[state.sortKey] || ''), 'id', { numeric: true }) * state.sortDirection;
        });
    }

    function actionMenu(customer) {
        const isActive = customer.status_pelanggan === 'Aktif';
        const nextStatus = isActive ? 'Nonaktif' : 'Aktif';
        const statusLabel = isActive ? 'Nonaktifkan pelanggan' : 'Aktifkan kembali';
        const statusIcon = isActive ? 'bi-person-dash' : 'bi-person-check';
        return `<details class="customer-actions-menu"><summary aria-label="Aksi lainnya"><i class="bi bi-three-dots"></i></summary><div class="customer-actions-popover"><button type="button" data-row-action="status" data-customer-id="${escapeHtml(customer.id)}" data-next-status="${nextStatus}"><i class="bi ${statusIcon}"></i>${statusLabel}</button></div></details>`;
    }

    function renderPagination(total) {
        const pageCount = Math.max(1, Math.ceil(total / state.pageSize));
        state.page = Math.min(state.page, pageCount);
        const controls = document.querySelectorAll('.customer-pagination [data-page]');
        controls.forEach((button) => {
            const action = button.dataset.page;
            button.disabled = total === 0 || (['first', 'previous'].includes(action) && state.page === 1) || (['next', 'last'].includes(action) && state.page === pageCount);
        });
        const start = total === 0 ? 0 : (state.page - 1) * state.pageSize + 1;
        const end = Math.min(state.page * state.pageSize, total);
        document.getElementById('customerPageInfo').textContent = `Menampilkan ${start}–${end} dari ${total} pelanggan`;
        const pageNumbers = document.getElementById('customerPageNumbers');
        const firstPage = Math.max(1, Math.min(state.page - 2, pageCount - 4));
        const lastPage = Math.min(pageCount, firstPage + 4);
        pageNumbers.innerHTML = Array.from({ length: Math.max(0, lastPage - firstPage + 1) }, (_, index) => {
            const page = firstPage + index;
            return `<button type="button" data-page-number="${page}" class="${page === state.page ? 'active' : ''}" aria-label="Halaman ${page}" aria-current="${page === state.page ? 'page' : 'false'}">${page}</button>`;
        }).join('');
    }

    function renderTable() {
        const filtered = filteredCustomers();
        const pageCount = Math.max(1, Math.ceil(filtered.length / state.pageSize));
        state.page = Math.min(state.page, pageCount);
        const visible = filtered.slice((state.page - 1) * state.pageSize, state.page * state.pageSize);
        const body = document.getElementById('customerTableBody');
        body.innerHTML = visible.length ? visible.map((customer, index) => `<tr>
            <td><input class="form-check-input customer-row-check" type="checkbox" value="${escapeHtml(customer.id)}" aria-label="Pilih ${escapeHtml(customer.nama)}" ${state.selected.has(customer.id) ? 'checked' : ''}></td>
            <td>${(state.page - 1) * state.pageSize + index + 1}</td>
            <td><button class="customer-code customer-code-link" type="button" data-row-action="detail" data-customer-id="${escapeHtml(customer.id)}" aria-label="Lihat detail ${escapeHtml(customer.kode_pelanggan)}">${escapeHtml(customer.kode_pelanggan)}</button></td>
            <td class="customer-name-cell">${escapeHtml(customer.nama)}</td>
            <td>${escapeHtml(customer.whatsapp)}</td>
            <td class="customer-address-cell" title="${escapeHtml(customer.alamat)}">${escapeHtml(customer.alamat)}</td>
            <td>${escapeHtml(customer.paket)}</td><td>${escapeHtml(customer.mitra)}</td>
            <td>${escapeHtml(formatDate(customer.tanggal_aktivasi))}</td>
            <td><span class="customer-status-badge ${statusClass(customer.status_pelanggan)}">${escapeHtml(customer.status_pelanggan)}</span></td>
            <td><div class="customer-row-actions">
                <button class="btn" type="button" title="Detail" aria-label="Detail ${escapeHtml(customer.nama)}" data-row-action="detail" data-customer-id="${escapeHtml(customer.id)}"><i class="bi bi-eye"></i></button>
                <button class="btn" type="button" title="Edit" aria-label="Edit ${escapeHtml(customer.nama)}" data-row-action="edit" data-customer-id="${escapeHtml(customer.id)}"><i class="bi bi-pencil"></i></button>
                <button class="btn delete-customer" type="button" title="Hapus" aria-label="Hapus ${escapeHtml(customer.nama)}" data-row-action="delete" data-customer-id="${escapeHtml(customer.id)}"><i class="bi bi-trash3"></i></button>
                ${actionMenu(customer)}
            </div></td>
        </tr>`).join('') : `<tr><td class="customer-empty-state" colspan="11"><i class="bi bi-people"></i>${filtered.length ? 'Tidak ada pelanggan pada halaman ini.' : 'Tidak ada pelanggan yang cocok dengan pencarian atau filter.'}</td></tr>`;
        document.getElementById('customerResultCount').textContent = `${filtered.length} dari ${customers.length} data contoh`;
        document.getElementById('selectAllCustomers').checked = visible.length > 0 && visible.every((customer) => state.selected.has(customer.id));
        document.getElementById('selectAllCustomers').indeterminate = visible.some((customer) => state.selected.has(customer.id)) && !document.getElementById('selectAllCustomers').checked;
        renderPagination(filtered.length);
        document.querySelectorAll('[data-status-card]').forEach((card) => card.classList.toggle('active', card.dataset.statusCard === state.applied.status || (!state.applied.status && card.dataset.statusCard === 'Semua')));
    }

    function setFormStep(step) {
        currentFormStep = Math.max(1, Math.min(4, step));
        document.querySelectorAll('[data-form-step]').forEach((element) => element.classList.toggle('active', Number(element.dataset.formStep) === currentFormStep));
        document.querySelectorAll('[data-step-label]').forEach((element) => {
            const elementStep = Number(element.dataset.stepLabel);
            element.classList.toggle('active', elementStep === currentFormStep);
            element.classList.toggle('done', elementStep < currentFormStep);
        });
        document.getElementById('customerPreviousStep').disabled = currentFormStep === 1;
        document.getElementById('customerNextStep').classList.toggle('d-none', currentFormStep === 4);
        document.getElementById('customerSaveButton').classList.toggle('d-none', currentFormStep !== 4);
        document.querySelector('#customerSaveButton [data-save-label]').textContent = editingId ? 'Simpan Perubahan' : 'Simpan';
    }

    function updatePackageFields() {
        const form = document.getElementById('customerForm');
        const packageName = form.elements.paket.value;
        const details = packageCatalog[packageName] || packageCatalog['20 Mbps'];
        form.elements.harga.value = money.format(details.price);
        form.elements.kecepatan.value = details.speed;
    }

    function openCustomerForm(customer = null) {
        editingId = customer?.id ?? null;
        const form = document.getElementById('customerForm');
        form.reset();
        form.classList.remove('was-validated');
        form.elements.kode_pelanggan.value = customer?.kode_pelanggan || makeCustomerCode();
        form.elements.tanggal_pendaftaran.value = customer?.tanggal_pendaftaran || today;
        form.elements.tanggal_aktivasi.value = customer?.tanggal_aktivasi || today;
        form.elements.tanggal_mulai.value = customer?.tanggal_mulai || today;
        form.elements.nama.value = customer?.nama || '';
        form.elements.whatsapp.value = customer?.whatsapp || '';
        form.elements.email.value = customer?.email || '';
        form.elements.alamat.value = customer?.alamat || '';
        form.elements.provinsi.value = customer?.provinsi || 'Sumatera Utara';
        form.elements.kota.value = customer?.kota || 'Medan';
        form.elements.kecamatan.value = customer?.kecamatan || '';
        form.elements.kelurahan.value = customer?.kelurahan || '';
        form.elements.sumber_pendaftaran.value = customer?.sumber_pendaftaran || 'Walk-in';
        form.elements.mitra.value = customer?.mitra || 'Langsung';
        form.elements.petugas.value = customer?.petugas || 'Admin KiNet';
        form.elements.paket.value = customer?.paket || '20 Mbps';
        form.elements.status_pelanggan.value = customer?.status_pelanggan || 'Aktif';
        form.elements.router.value = customer?.router || 'Router Medan 01';
        form.elements.username_pppoe.value = customer?.username_pppoe || '';
        form.elements.password_pppoe.value = customer?.password_pppoe || '';
        form.elements.profil_layanan.value = customer?.profil_layanan || '';
        form.elements.ip_address.value = customer?.ip_address || '';
        document.getElementById('customerFormTitle').textContent = customer ? 'Edit Pelanggan' : 'Tambah Pelanggan';
        updatePackageFields();
        setFormStep(1);
        formModal.show();
    }

    function detailItem(label, value) {
        return `<div class="customer-detail-item"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value || '—')}</strong></div>`;
    }

    function historyTable(headers, rows) {
        return `<div class="table-responsive customer-history-table-wrap"><table class="table customer-history-table"><thead><tr>${headers.map((header) => `<th>${escapeHtml(header)}</th>`).join('')}</tr></thead><tbody>${rows.map((row) => `<tr>${row.map((cell) => `<td>${escapeHtml(cell)}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
    }

    function openCustomerDetail(customer) {
        const initials = customer.nama.split(/\s+/).slice(0, 2).map((part) => part[0] || '').join('').toUpperCase();
        const invoiceOnePeriod = monthDate(-2, 1).slice(0, 7);
        const invoiceTwoPeriod = monthDate(-1, 1).slice(0, 7);
        const invoiceThreePeriod = monthDate(0, 1).slice(0, 7);
        const invoiceOneDue = monthDate(-1);
        const invoiceTwoDue = monthDate(0);
        const invoiceThreeDue = monthDate(1);
        const invoiceStatusTwo = customer.status_pelanggan === 'Terisolasi' ? 'Belum dibayar' : 'Lunas';
        const paymentStatus = customer.status_pelanggan === 'Terisolasi' ? 'Ditolak' : 'Berhasil';
        const whatsappNumber = customer.whatsapp.replace(/\D/g, '').replace(/^0/, '62');
        document.getElementById('customerDetailTitle').textContent = customer.nama;
        document.getElementById('customerProfileSummary').innerHTML = `<span class="customer-profile-avatar" aria-hidden="true">${escapeHtml(initials)}</span><div class="customer-profile-identity"><h3>${escapeHtml(customer.nama)}</h3><p><span>${escapeHtml(customer.kode_pelanggan)}</span><span aria-hidden="true">·</span><a href="https://wa.me/${escapeHtml(whatsappNumber)}" target="_blank" rel="noopener noreferrer"><i class="bi bi-whatsapp"></i> ${escapeHtml(customer.whatsapp)}</a></p></div><span class="customer-status-badge ${statusClass(customer.status_layanan)} ms-auto">${escapeHtml(customer.status_layanan)} · layanan</span>`;
        document.getElementById('customerProfileTab').innerHTML = `<div class="customer-detail-grid">${[
            detailItem('Nama pelanggan', customer.nama), detailItem('Kode pelanggan', customer.kode_pelanggan), detailItem('WhatsApp', customer.whatsapp),
            detailItem('Email', customer.email), detailItem('Alamat pemasangan', customer.alamat), detailItem('Mitra pengelola', customer.mitra),
            detailItem('Status administrasi', customer.status_pelanggan), detailItem('Status layanan', customer.status_layanan), detailItem('Tanggal aktivasi', formatDate(customer.tanggal_aktivasi))
        ].join('')}</div>`;
        document.getElementById('customerSubscriptionTab').innerHTML = `<div class="customer-detail-grid">${[
            detailItem('Paket internet', customer.paket), detailItem('Harga per bulan', money.format(customer.harga)), detailItem('Kecepatan', customer.kecepatan),
            detailItem('Mitra', customer.mitra), detailItem('Tanggal aktivasi', formatDate(customer.tanggal_aktivasi)), detailItem('Mulai berlangganan', formatDate(customer.tanggal_mulai)),
            detailItem('Jatuh tempo bulanan', 'Tanggal 10'), detailItem('Status layanan', customer.status_layanan), detailItem('Periode layanan', 'Bulanan')
        ].join('')}</div>`;
        document.getElementById('customerConnectionTab').innerHTML = `<div class="customer-detail-grid">${[
            detailItem('Status koneksi', customer.status_layanan === 'Aktif' ? 'Terhubung' : customer.status_layanan), detailItem('Router', customer.router), detailItem('Username PPPoE', customer.username_pppoe),
            detailItem('Profil layanan', customer.profil_layanan), detailItem('IP Address', customer.ip_address), detailItem('Password PPPoE', customer.password_pppoe)
        ].join('')}</div>`;
        document.getElementById('customerInvoicesTab').innerHTML = historyTable(['No. tagihan', 'Periode', 'Tanggal jatuh tempo', 'Total tagihan', 'Dibayar', 'Status'], [
            [`INV-${customer.kode_pelanggan}-${invoiceOnePeriod.replace('-', '')}`, formatPeriod(invoiceOnePeriod), formatDate(invoiceOneDue), money.format(customer.harga), money.format(customer.harga), 'Lunas'],
            [`INV-${customer.kode_pelanggan}-${invoiceTwoPeriod.replace('-', '')}`, formatPeriod(invoiceTwoPeriod), formatDate(invoiceTwoDue), money.format(customer.harga), invoiceStatusTwo === 'Lunas' ? money.format(customer.harga) : money.format(0), invoiceStatusTwo],
            [`INV-${customer.kode_pelanggan}-${invoiceThreePeriod.replace('-', '')}`, formatPeriod(invoiceThreePeriod), formatDate(invoiceThreeDue), money.format(customer.harga), money.format(0), 'Belum jatuh tempo']
        ]);
        document.getElementById('customerPaymentsTab').innerHTML = historyTable(['No. transaksi', 'Tanggal pembayaran', 'Periode tagihan', 'Metode', 'Nominal', 'Status'], [
            [`PAY-${customer.kode_pelanggan}-001`, formatDate(addDays(invoiceOneDue, -2)), formatPeriod(invoiceOnePeriod), 'Transfer bank', money.format(customer.harga), 'Berhasil'],
            [`PAY-${customer.kode_pelanggan}-002`, formatDate(addDays(invoiceTwoDue, -1)), formatPeriod(invoiceTwoPeriod), 'QRIS', money.format(customer.status_pelanggan === 'Terisolasi' ? customer.harga / 2 : customer.harga), paymentStatus],
            [`PAY-${customer.kode_pelanggan}-003`, '—', formatPeriod(invoiceThreePeriod), '—', money.format(0), 'Belum ada pembayaran']
        ]);
        document.getElementById('customerActivityTab').innerHTML = historyTable(['Waktu', 'Aktivitas', 'Catatan', 'Petugas'], [
            [formatDate(customer.tanggal_aktivasi), 'Layanan pelanggan diaktifkan', `Paket ${customer.paket} mulai digunakan`, customer.petugas],
            [formatDate(customer.tanggal_pendaftaran), 'Data pelanggan didaftarkan', `Pendaftaran melalui ${customer.sumber_pendaftaran}`, customer.petugas],
            [formatDate(addDays(customer.tanggal_aktivasi, 1)), 'Koneksi internet disiapkan', `Akun PPPoE ${customer.username_pppoe}`, 'Teknisi KiNet']
        ]);
        document.getElementById('customerProfileTabButton').click();
        detailModal.show();
    }

    function askConfirmation(title, message, actionLabel, callback, danger = true) {
        document.getElementById('customerConfirmTitle').textContent = title;
        document.getElementById('customerConfirmMessage').textContent = message;
        const confirmButton = document.getElementById('customerConfirmAction');
        confirmButton.textContent = actionLabel;
        confirmButton.classList.toggle('btn-danger', danger);
        confirmButton.classList.toggle('btn-primary', !danger);
        state.pendingConfirm = callback;
        confirmModal.show();
    }

    function changeStatus(ids, status) {
        let changed = 0;
        customers = customers.map((customer) => {
            if (!ids.includes(customer.id)) return customer;
            changed += 1;
            return { ...customer, status_pelanggan: status, status_layanan: status };
        });
        persistCustomers();
        renderTable();
        notify(`${changed} pelanggan berhasil diubah menjadi ${status}.`);
    }

    function validateFormStep(step) {
        const section = document.querySelector(`[data-form-step="${step}"]`);
        const invalidField = [...section.querySelectorAll('input, select, textarea')]
            .find((field) => !field.checkValidity());
        if (!invalidField) return true;
        invalidField.reportValidity();
        return false;
    }

    function validateCustomerForm() {
        for (let step = 1; step <= 4; step += 1) {
            const section = document.querySelector(`[data-form-step="${step}"]`);
            const invalidField = [...section.querySelectorAll('input, select, textarea')]
                .find((field) => !field.checkValidity());
            if (invalidField) {
                setFormStep(step);
                invalidField.reportValidity();
                return false;
            }
        }
        return true;
    }

    function csvCell(value) {
        const text = String(value ?? '');
        return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
    }

    function exportCsv() {
        const columns = [
            ['kode_pelanggan', 'Kode Pelanggan'], ['nama', 'Nama'], ['whatsapp', 'WhatsApp'], ['email', 'Email'],
            ['alamat', 'Alamat'], ['paket', 'Paket'], ['harga', 'Harga'], ['kecepatan', 'Kecepatan'], ['mitra', 'Mitra'],
            ['tanggal_aktivasi', 'Tanggal Aktivasi'], ['status_pelanggan', 'Status Pelanggan'], ['status_layanan', 'Status Layanan'],
            ['router', 'Router'], ['username_pppoe', 'Username PPPoE']
        ];
        const rows = [columns.map((column) => csvCell(column[1])).join(','), ...filteredCustomers().map((customer) => columns.map(([key]) => csvCell(customer[key])).join(','))];
        const blob = new Blob(['\uFEFF' + rows.join('\r\n')], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'data-pelanggan-kinet.csv';
        link.click();
        URL.revokeObjectURL(url);
        notify(`${filteredCustomers().length} data pelanggan diekspor ke CSV.`);
    }

    function parseCsv(text) {
        const rows = [];
        let row = [];
        let cell = '';
        let quoted = false;
        for (let index = 0; index < text.length; index += 1) {
            const character = text[index];
            if (character === '"' && quoted && text[index + 1] === '"') { cell += '"'; index += 1; }
            else if (character === '"') quoted = !quoted;
            else if (character === ',' && !quoted) { row.push(cell); cell = ''; }
            else if ((character === '\n' || character === '\r') && !quoted) {
                if (character === '\r' && text[index + 1] === '\n') index += 1;
                row.push(cell); cell = '';
                if (row.some((value) => value.trim() !== '')) rows.push(row);
                row = [];
            } else cell += character;
        }
        if (cell || row.length) { row.push(cell); rows.push(row); }
        return rows;
    }

    function normalizedHeader(value) {
        return String(value).replace(/^\uFEFF/, '').trim().toLowerCase().replace(/[^a-z0-9]/g, '');
    }

    function csvDate(value) {
        const input = String(value || '').trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(input)) return input;
        const match = input.match(/^(\d{1,2})[-/](\d{1,2})[-/](\d{4})$/);
        return match ? `${match[3]}-${match[2].padStart(2, '0')}-${match[1].padStart(2, '0')}` : today;
    }

    function convertImportRow(headers, values, index) {
        const source = Object.fromEntries(headers.map((header, column) => [normalizedHeader(header), values[column] || '']));
        const pick = (...keys) => {
            const key = keys.find((candidate) => source[normalizedHeader(candidate)] !== undefined);
            return key ? String(source[normalizedHeader(key)]).trim() : '';
        };
        const nama = pick('nama', 'nama pelanggan', 'nama lengkap');
        const whatsapp = pick('whatsapp', 'nomor whatsapp', 'no hp', 'nomor hp');
        const packageName = pick('paket', 'paket internet') || '20 Mbps';
        const packageInfo = packageCatalog[packageName] || packageCatalog['20 Mbps'];
        const status = normalizeStatus(pick('status pelanggan', 'status'));
        if (!nama || !whatsapp) return null;
        return normalizeCustomer({
            id: `import-${Date.now()}-${index}`,
            kode_pelanggan: pick('kode pelanggan', 'kode_pelanggan') || makeCustomerCode(),
            nama,
            whatsapp,
            email: pick('email'),
            alamat: pick('alamat'),
            paket: packageName,
            harga: Number(pick('harga')) || packageInfo.price,
            kecepatan: pick('kecepatan') || packageInfo.speed,
            mitra: pick('mitra') || 'Langsung',
            tanggal_aktivasi: csvDate(pick('tanggal aktivasi', 'tanggal_aktivasi')),
            status_pelanggan: status,
            status_layanan: status
        }, index);
    }

    function renderImportPreview(rows) {
        const preview = document.getElementById('customerImportPreview');
        if (!rows.length) {
            preview.textContent = 'File tidak berisi data pelanggan yang valid. Pastikan tersedia kolom Nama dan WhatsApp.';
            document.getElementById('customerImportCommit').disabled = true;
            return;
        }
        preview.innerHTML = `<p>${rows.length} baris valid ditemukan. Pratinjau:</p>${historyTable(['Kode pelanggan', 'Nama', 'WhatsApp', 'Paket', 'Status'], rows.slice(0, 5).map((customer) => [customer.kode_pelanggan, customer.nama, customer.whatsapp, customer.paket, customer.status_pelanggan]))}`;
        document.getElementById('customerImportCommit').disabled = false;
    }

    renderTable();

    root.addEventListener('click', (event) => {
        const statCard = event.target.closest('[data-status-card]');
        if (statCard) {
            state.applied.status = statCard.dataset.statusCard === 'Semua' ? '' : statCard.dataset.statusCard;
            state.page = 1;
            renderTable();
            return;
        }
        const addButton = event.target.closest('[data-action="add-customer"]');
        if (addButton) { openCustomerForm(); return; }
        const rowAction = event.target.closest('[data-row-action]');
        if (rowAction) {
            const customer = customers.find((item) => item.id === rowAction.dataset.customerId);
            if (!customer) { notify('Data pelanggan tidak ditemukan.', true); return; }
            if (rowAction.dataset.rowAction === 'detail') openCustomerDetail(customer);
            if (rowAction.dataset.rowAction === 'edit') openCustomerForm(customer);
            if (rowAction.dataset.rowAction === 'delete') askConfirmation('Hapus pelanggan?', `Hapus data simulasi ${customer.nama} (${customer.kode_pelanggan})?`, 'Hapus pelanggan', () => {
                customers = customers.filter((item) => item.id !== customer.id);
                state.selected.delete(customer.id);
                persistCustomers(); renderTable(); notify('Data simulasi pelanggan berhasil dihapus.');
            });
            if (rowAction.dataset.rowAction === 'status') askConfirmation(`${rowAction.dataset.nextStatus === 'Aktif' ? 'Aktifkan' : 'Nonaktifkan'} pelanggan?`, `Ubah status administrasi dan layanan ${customer.nama} menjadi ${rowAction.dataset.nextStatus}?`, 'Ya, ubah status', () => changeStatus([customer.id], rowAction.dataset.nextStatus), false);
        }
    });

    document.getElementById('customerPageSize').addEventListener('change', (event) => {
        state.pageSize = Number(event.target.value);
        state.page = 1;
        renderTable();
    });
    document.querySelectorAll('.customer-sort').forEach((button) => button.addEventListener('click', () => {
        const key = button.dataset.sort;
        state.sortDirection = state.sortKey === key ? state.sortDirection * -1 : 1;
        state.sortKey = key;
        renderTable();
        document.querySelectorAll('.customer-sort .bi').forEach((icon) => { icon.className = 'bi bi-arrow-down-up'; });
        const icon = button.querySelector('.bi');
        icon.className = state.sortDirection === 1 ? 'bi bi-sort-down-alt' : 'bi bi-sort-up';
    }));
    document.getElementById('customerPageNumbers').addEventListener('click', (event) => {
        const button = event.target.closest('[data-page-number]');
        if (!button) return;
        state.page = Number(button.dataset.pageNumber);
        renderTable();
    });
    document.querySelector('.customer-pagination').addEventListener('click', (event) => {
        const button = event.target.closest('[data-page]');
        if (!button || button.disabled) return;
        const pageCount = Math.max(1, Math.ceil(filteredCustomers().length / state.pageSize));
        if (button.dataset.page === 'first') state.page = 1;
        if (button.dataset.page === 'previous') state.page -= 1;
        if (button.dataset.page === 'next') state.page += 1;
        if (button.dataset.page === 'last') state.page = pageCount;
        renderTable();
    });
    document.getElementById('customerTableBody').addEventListener('change', (event) => {
        const checkbox = event.target.closest('.customer-row-check');
        if (!checkbox) return;
        if (checkbox.checked) state.selected.add(checkbox.value);
        else state.selected.delete(checkbox.value);
        renderTable();
    });
    document.getElementById('selectAllCustomers').addEventListener('change', (event) => {
        const visible = filteredCustomers().slice((state.page - 1) * state.pageSize, state.page * state.pageSize);
        visible.forEach((customer) => event.target.checked ? state.selected.add(customer.id) : state.selected.delete(customer.id));
        renderTable();
    });
    document.getElementById('applyBulkAction').addEventListener('click', () => {
        const action = document.getElementById('bulkAction').value;
        const selectedIds = [...state.selected];
        if (!selectedIds.length) { notify('Pilih setidaknya satu pelanggan terlebih dahulu.', true); return; }
        if (!action) { notify('Pilih aksi massal yang akan dijalankan.', true); return; }
        if (action === 'delete') askConfirmation('Hapus pelanggan terpilih?', `Hapus ${selectedIds.length} data simulasi pelanggan yang dipilih?`, 'Hapus pelanggan', () => {
            customers = customers.filter((customer) => !state.selected.has(customer.id));
            state.selected.clear(); persistCustomers(); renderTable(); notify('Data pelanggan terpilih berhasil dihapus.');
        });
        else askConfirmation('Ubah status pelanggan?', `Ubah status ${selectedIds.length} pelanggan terpilih menjadi ${action}?`, 'Ya, ubah status', () => {
            changeStatus(selectedIds, action); state.selected.clear(); renderTable();
        }, false);
    });
    document.getElementById('customerExportButton').addEventListener('click', exportCsv);
    document.getElementById('customerNextStep').addEventListener('click', () => {
        if (!validateFormStep(currentFormStep)) return;
        if (currentFormStep < 4) setFormStep(currentFormStep + 1);
    });
    document.getElementById('customerPreviousStep').addEventListener('click', () => setFormStep(currentFormStep - 1));
    document.getElementById('customerForm').elements.paket.addEventListener('change', updatePackageFields);
    document.getElementById('customerForm').addEventListener('submit', (event) => {
        event.preventDefault();
        const form = event.currentTarget;
        if (!validateCustomerForm()) { form.classList.add('was-validated'); return; }
        const formData = new FormData(form);
        const packageName = String(formData.get('paket'));
        const packageInfo = packageCatalog[packageName];
        const customer = normalizeCustomer({
            ...(editingId ? customers.find((item) => item.id === editingId) : {}),
            id: editingId || `customer-${Date.now()}`,
            kode_pelanggan: formData.get('kode_pelanggan'),
            nama: formData.get('nama'),
            whatsapp: formData.get('whatsapp'),
            email: formData.get('email'),
            alamat: formData.get('alamat'),
            provinsi: formData.get('provinsi'),
            kota: formData.get('kota'),
            kecamatan: formData.get('kecamatan'),
            kelurahan: formData.get('kelurahan'),
            tanggal_pendaftaran: formData.get('tanggal_pendaftaran'),
            sumber_pendaftaran: formData.get('sumber_pendaftaran'),
            mitra: formData.get('mitra'),
            petugas: formData.get('petugas'),
            paket: packageName,
            harga: packageInfo.price,
            kecepatan: packageInfo.speed,
            tanggal_aktivasi: formData.get('tanggal_aktivasi'),
            tanggal_mulai: formData.get('tanggal_mulai'),
            status_pelanggan: formData.get('status_pelanggan'),
            status_layanan: formData.get('status_pelanggan'),
            router: formData.get('router'),
            username_pppoe: formData.get('username_pppoe'),
            password_pppoe: formData.get('password_pppoe') || '••••••••',
            profil_layanan: formData.get('profil_layanan'),
            ip_address: formData.get('ip_address') || 'Otomatis'
        }, customers.length);
        const wasEditing = Boolean(editingId);
        if (wasEditing) customers = customers.map((item) => item.id === editingId ? customer : item);
        else customers.unshift(customer);
        persistCustomers(); state.page = 1; renderTable(); formModal.hide();
        notify(wasEditing ? 'Perubahan data pelanggan berhasil disimpan.' : 'Pelanggan baru berhasil ditambahkan ke data simulasi.');
        editingId = null;
    });
    document.getElementById('customerFormModal').addEventListener('hidden.bs.modal', () => {
        editingId = null;
        document.getElementById('customerForm').classList.remove('was-validated');
        setFormStep(1);
    });
    document.getElementById('customerConfirmAction').addEventListener('click', () => {
        const action = state.pendingConfirm;
        state.pendingConfirm = null;
        confirmModal.hide();
        if (action) action();
    });
    document.getElementById('customerConfirmModal').addEventListener('hidden.bs.modal', () => {
        state.pendingConfirm = null;
    });
    document.getElementById('customerImportFile').addEventListener('change', async (event) => {
        importRows = [];
        const file = event.target.files?.[0];
        if (!file) { renderImportPreview([]); return; }
        try {
            const rows = parseCsv(await file.text());
            if (rows.length < 2) throw new Error('CSV harus memuat header dan setidaknya satu baris data.');
            const headers = rows[0];
            importRows = rows.slice(1).map((row, index) => convertImportRow(headers, row, index)).filter(Boolean);
            renderImportPreview(importRows);
        } catch (error) {
            document.getElementById('customerImportPreview').textContent = error.message || 'File CSV tidak dapat dibaca.';
            document.getElementById('customerImportCommit').disabled = true;
            notify('Import gagal diproses. Periksa file CSV.', true);
            console.error('Gagal membaca CSV pelanggan:', error);
        }
    });
    document.getElementById('customerImportCommit').addEventListener('click', () => {
        if (!importRows.length) { notify('Tidak ada baris CSV valid untuk diimpor.', true); return; }
        const usedCodes = new Set(customers.map((customer) => customer.kode_pelanggan));
        let nextCode = customers.reduce((maximum, customer) => Math.max(maximum, Number(customer.kode_pelanggan?.match(/\d+$/)?.[0] || 0)), 0) + 1;
        const freshRows = importRows.map((customer, index) => {
            let code = customer.kode_pelanggan;
            if (usedCodes.has(code)) {
                do {
                    code = `KNT-${String(nextCode).padStart(5, '0')}`;
                    nextCode += 1;
                } while (usedCodes.has(code));
            }
            usedCodes.add(code);
            return { ...customer, id: `import-${Date.now()}-${index}`, kode_pelanggan: code };
        });
        customers = [...freshRows, ...customers];
        persistCustomers(); state.page = 1; renderTable();
        bootstrap.Modal.getInstance(document.getElementById('customerImportModal'))?.hide();
        document.getElementById('customerImportFile').value = '';
        importRows = [];
        notify(`${freshRows.length} pelanggan berhasil ditambahkan ke data simulasi.`);
    });
    document.getElementById('customerImportModal').addEventListener('hidden.bs.modal', () => {
        document.getElementById('customerImportFile').value = '';
        document.getElementById('customerImportPreview').textContent = 'Pilih file untuk melihat pratinjau.';
        document.getElementById('customerImportCommit').disabled = true;
        importRows = [];
    });
});
