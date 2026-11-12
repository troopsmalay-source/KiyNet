document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-payment-module]');
    if (!root || !window.bootstrap) return;

    const storageKey = 'kinet.payment-demo.v1';
    const customers = [
        ['KNT-00001', 'Andi Ramadhan', '081234567801', 'Jl. Karya Wisata, Medan Johor', 'KiNet Home 20 Mbps', 'Mitra Medan Kota', 150000],
        ['KNT-00002', 'Budi Santoso', '081234567802', 'Jl. Setia Budi, Medan Selayang', 'KiNet Family 30 Mbps', 'Mitra Medan Utara', 200000],
        ['KNT-00003', 'Siti Rahma', '081234567803', 'Jl. Garu III, Medan Amplas', 'KiNet Gamer 50 Mbps', 'Mitra Medan Kota', 275000],
        ['KNT-00004', 'Dedi Saputra', '081234567804', 'Jl. Denai, Medan Denai', 'KiNet Home 20 Mbps', 'Langsung', 150000],
        ['KNT-00005', 'Rina Amelia', '081234567805', 'Jl. Tembung Raya, Medan Tembung', 'KiNet Ultra 100 Mbps', 'Mitra Deli Serdang', 450000],
        ['KNT-00006', 'Fajar Nugraha', '081234567806', 'Jl. Iskandar Muda, Medan Baru', 'KiNet Family 30 Mbps', 'Mitra Medan Kota', 200000],
        ['KNT-00007', 'Dewi Anggraini', '081234567807', 'Jl. Puri, Medan Kota', 'KiNet Gamer 50 Mbps', 'Langsung', 275000],
        ['KNT-00008', 'Rizky Maulana', '081234567808', 'Jl. Kapten Muslim, Medan Helvetia', 'KiNet Home 20 Mbps', 'Mitra Medan Utara', 150000],
        ['KNT-00009', 'Maya Putri', '081234567809', 'Jl. Setia Budi, Medan Sunggal', 'KiNet Ultra 100 Mbps', 'Mitra Binjai', 450000],
        ['KNT-00010', 'Agus Setiawan', '081234567810', 'Jl. Perjuangan, Medan Perjuangan', 'KiNet Family 30 Mbps', 'Mitra Medan Kota', 200000],
        ['KNT-00011', 'Nadia Safitri', '081234567811', 'Jl. Marelan Raya, Medan Marelan', 'KiNet Gamer 50 Mbps', 'Mitra Medan Utara', 275000],
        ['KNT-00012', 'Irwan Hidayat', '081234567812', 'Jl. Yos Sudarso, Medan Labuhan', 'KiNet Home 20 Mbps', 'Langsung', 150000],
        ['KNT-00013', 'Lina Marlina', '081234567813', 'Jl. Maimun, Medan Maimun', 'KiNet Ultra 100 Mbps', 'Mitra Deli Serdang', 450000],
        ['KNT-00014', 'Hendra Wijaya', '081234567814', 'Jl. Sunggal, Medan Sunggal', 'KiNet Family 30 Mbps', 'Mitra Medan Kota', 200000],
        ['KNT-00015', 'Putri Maharani', '081234567815', 'Jl. Helvetia, Medan Helvetia', 'KiNet Gamer 50 Mbps', 'Mitra Medan Utara', 275000],
        ['KNT-00016', 'Tono Prasetyo', '081234567816', 'Jl. Karya Jaya, Medan Johor', 'KiNet Home 20 Mbps', 'Langsung', 150000],
        ['KNT-00017', 'Vina Oktavia', '081234567817', 'Jl. Marelan V, Medan Marelan', 'KiNet Family 30 Mbps', 'Mitra Deli Serdang', 200000],
        ['KNT-00018', 'Rudi Hartono', '081234567818', 'Jl. Bromo, Medan Denai', 'KiNet Gamer 50 Mbps', 'Mitra Medan Kota', 275000]
    ];

    const demoDefinitions = [
        ['QRIS', 'success', 0, 0, 'TRX-BCA-774102', 'Aktif'],
        ['Transfer Manual', 'verification', 2, 0, '', 'Aktif'],
        ['Virtual Account', 'pending', 3, 0, '', 'Aktif'],
        ['QRIS', 'success', -1, 0, 'TRX-QR-864211', 'Aktif'],
        ['Transfer Manual', 'failed', -3, 0, 'TRX-MAN-884001', 'Aktif'],
        ['Virtual Account', 'success', 1, 0, 'TRX-VA-112298', 'Aktif'],
        ['QRIS', 'pending', 5, 0, '', 'Aktif'],
        ['Transfer Manual', 'verification', 0, 0, '', 'Aktif'],
        ['Virtual Account', 'success', -2, 25000, 'TRX-VA-663412', 'Aktif'],
        ['QRIS', 'failed', -8, 0, 'TRX-QR-993810', 'Isolir'],
        ['Transfer Manual', 'pending', 4, 0, '', 'Aktif'],
        ['Virtual Account', 'success', 0, 0, 'TRX-VA-283199', 'Aktif'],
        ['QRIS', 'verification', 1, 0, '', 'Aktif'],
        ['Transfer Manual', 'success', -1, 0, 'TRX-MAN-128800', 'Aktif'],
        ['Virtual Account', 'pending', 7, 0, '', 'Aktif'],
        ['QRIS', 'success', -4, 0, 'TRX-QR-228191', 'Aktif'],
        ['Transfer Manual', 'failed', 2, 0, 'TRX-MAN-228120', 'Aktif'],
        ['Virtual Account', 'success', -9, 0, 'TRX-VA-553812', 'Aktif']
    ];

    const money = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 });
    const dateFormatter = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
    const dateTimeFormatter = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
    const monthFormatter = new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' });
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[character]));
    const pad = (value) => String(value).padStart(2, '0');
    const localDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
    const parseDate = (value) => {
        const [year, month, day] = String(value).split('-').map(Number);
        return new Date(year, month - 1, day || 1);
    };
    const addDays = (date, days) => {
        const result = new Date(date);
        result.setDate(result.getDate() + days);
        return result;
    };
    const dateFromOffset = (offset, simulatedDays = state.simulatedDays) => addDays(addDays(new Date(), simulatedDays), offset);
    const formatDate = (date) => dateFormatter.format(date);
    const formatDateTime = (date) => date ? dateTimeFormatter.format(new Date(date)) : '—';
    const currentPeriod = () => monthFormatter.format(addDays(new Date(), state.simulatedDays));
    const makeDemoRecords = () => {
        const today = new Date();
        const month = `${today.getFullYear()}${pad(today.getMonth() + 1)}`;
        return customers.map((customer, index) => {
            const definition = demoDefinitions[index];
            const [method, status, dueOffset, fee, reference, serviceStatus] = definition;
            const [customerCode, customerName, phone, address, packageName, partner, amount] = customer;
            const id = `TRX-${month}-${String(index + 1).padStart(4, '0')}`;
            const paymentDate = status === 'success' ? addDays(today, -Math.min(index % 5, 3)) : null;
            const createdAt = addDays(today, -Math.min(index % 4, 2));
            const record = {
                id,
                invoice: `INV-${customerCode}-${month}`,
                customerCode,
                customerName,
                phone,
                address,
                packageName,
                partner,
                period: currentPeriod(),
                amount,
                fee,
                dueOffset,
                status,
                billStatus: status === 'success' ? 'paid' : 'unpaid',
                method,
                paidAt: paymentDate ? paymentDate.toISOString() : null,
                createdAt: createdAt.toISOString(),
                reference,
                paymentInvoice: status === 'success' ? `PAY-INV-${month}-${String(index + 1).padStart(4, '0')}` : '',
                bank: method === 'Virtual Account' ? ['BCA', 'BRI', 'BNI', 'Mandiri', 'Permata'][index % 5] : (method === 'Transfer Manual' ? 'BCA' : ''),
                senderName: method === 'Transfer Manual' ? customerName : '',
                senderBank: method === 'Transfer Manual' ? 'BCA' : '',
                senderAccount: '',
                transferDate: '',
                evidenceName: status === 'verification' || status === 'failed' ? `bukti-transfer-${customerCode.toLowerCase()}.jpg` : '',
                verifyNote: status === 'success' ? 'Pembayaran berhasil dikonfirmasi.' : '',
                rejectedReason: status === 'failed' ? 'Nominal pada bukti belum sesuai.' : '',
                verifiedBy: status === 'success' && method === 'Transfer Manual' ? 'Admin KiNet' : '',
                verifiedAt: status === 'success' && method === 'Transfer Manual' ? paymentDate?.toISOString() : '',
                serviceStatus: serviceStatus.toLowerCase() === 'isolir' ? 'isolated' : 'active',
                history: [
                    { label: 'Tagihan dibuat', date: createdAt.toISOString(), note: `Periode ${currentPeriod()}` },
                    { label: 'Invoice tagihan diterbitkan', date: createdAt.toISOString(), note: `Nomor ${`INV-${customerCode}-${month}`}` }
                ]
            };
            if (method !== 'Transfer Manual') {
                record.history.push({ label: 'Metode pembayaran dipilih', date: createdAt.toISOString(), note: method });
            }
            if (status === 'success') {
                record.history.push(
                    { label: 'Pembayaran dilakukan', date: paymentDate.toISOString(), note: `${method} · ${money.format(amount + fee)}` },
                    { label: 'Pembayaran diverifikasi', date: paymentDate.toISOString(), note: reference || 'Transaksi otomatis' },
                    { label: 'Invoice pembayaran diterbitkan', date: paymentDate.toISOString(), note: record.paymentInvoice },
                    { label: 'Transaksi selesai', date: paymentDate.toISOString(), note: 'Tagihan lunas' }
                );
            } else if (status === 'verification') {
                record.history.push({ label: 'Bukti transfer dikirim', date: createdAt.toISOString(), note: record.evidenceName });
            }
            return record;
        });
    };

    const state = {
        records: [],
        simulatedDays: 0,
        tab: 'all',
        search: '',
        from: '',
        to: '',
        method: '',
        status: '',
        page: 1,
        pageSize: 10,
        sortKey: 'createdAt',
        sortDirection: -1,
        selectedId: null,
        selectedMethod: '',
        selectedBank: '',
        flowStage: '',
        verifyMode: '',
        documentKind: '',
        pendingConfirm: null,
        timer: null,
        flowExpiry: 0
    };

    const modals = {
        detail: bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentDetailModal')),
        flow: bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentFlowModal')),
        verify: bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentVerifyModal')),
        document: bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentDocumentModal')),
        confirm: bootstrap.Modal.getOrCreateInstance(document.getElementById('paymentConfirmModal'))
    };

    const toast = (message) => {
        const element = document.getElementById('kinetToast');
        const text = document.querySelector('[data-toast-message]');
        if (!element || !text) return;
        text.textContent = message;
        bootstrap.Toast.getOrCreateInstance(element, { delay: 3500 }).show();
    };
    const recordById = (id = state.selectedId) => state.records.find((record) => record.id === id);
    const statusLabels = { success: 'Berhasil', pending: 'Menunggu Pembayaran', verification: 'Menunggu Verifikasi', failed: 'Gagal / Ditolak' };
    const statusClass = { success: 'is-success', pending: 'is-pending', verification: 'is-verification', failed: 'is-failed' };
    const setStatusBadge = (status) => `<span class="payment-status ${statusClass[status] || 'is-pending'}">${escapeHtml(statusLabels[status] || status)}</span>`;

    function saveData() {
        try {
            localStorage.setItem(storageKey, JSON.stringify({
                records: state.records,
                simulatedDays: state.simulatedDays
            }));
        } catch (error) {
            console.error('Gagal menyimpan simulasi pembayaran ke localStorage:', error);
            toast('Perubahan simulasi tidak dapat disimpan di browser ini.');
        }
    }

    function loadData() {
        try {
            const saved = localStorage.getItem(storageKey);
            if (!saved) {
                state.records = makeDemoRecords();
                saveData();
                return;
            }
            const parsed = JSON.parse(saved);
            if (!Array.isArray(parsed.records) || parsed.records.length < 1) {
                throw new Error('Data demo pembayaran tersimpan tidak valid.');
            }
            state.records = parsed.records;
            state.simulatedDays = Number.isFinite(parsed.simulatedDays) ? parsed.simulatedDays : 0;
        } catch (error) {
            console.error('Gagal membaca data simulasi pembayaran:', error);
            state.records = makeDemoRecords();
            state.simulatedDays = 0;
            toast('Data demo tersimpan tidak dapat dibaca. Data awal simulasi ditampilkan.');
        }
    }

    function billState(record) {
        if (record.billStatus === 'paid' || record.status === 'success') {
            return { key: 'paid', label: 'Lunas', days: 0 };
        }
        const days = record.dueOffset - state.simulatedDays;
        if (days > 0) return { key: 'future', label: 'Belum jatuh tempo', days };
        if (days === 0) return { key: 'due', label: 'Jatuh tempo', days: 0 };
        if (days >= -7) return { key: 'grace', label: 'Masa tenggang', days: Math.abs(days) };
        return { key: 'late', label: 'Terlambat', days: Math.abs(days) };
    }

    function recalculateIsolation() {
        let changed = false;
        state.records.forEach((record) => {
            if (record.billStatus !== 'paid' && billState(record).key === 'late' && record.serviceStatus !== 'isolated') {
                record.serviceStatus = 'isolated';
                record.history.push({
                    label: 'Layanan diisolasi (simulasi)',
                    date: addDays(new Date(), state.simulatedDays).toISOString(),
                    note: 'Masa tenggang 7 hari berakhir dan tagihan belum lunas.'
                });
                changed = true;
            }
        });
        if (changed) {
            saveData();
            toast('Simulasi tanggal melewati masa tenggang. Layanan terkait ditandai terisolasi.');
        }
    }

    function rowDate(record) {
        return record.paidAt ? new Date(record.paidAt) : new Date(record.createdAt);
    }

    function visibleRecords() {
        let records = [...state.records];
        if (state.tab === 'pending') records = records.filter((record) => record.status === 'pending');
        if (state.tab === 'verification') records = records.filter((record) => record.status === 'verification');
        if (state.tab === 'success') records = records.filter((record) => record.status === 'success');
        if (state.tab === 'failed') records = records.filter((record) => record.status === 'failed');
        if (state.tab === 'history') records = records.filter((record) => ['success', 'failed'].includes(record.status));

        const needle = state.search.trim().toLocaleLowerCase('id');
        if (needle) {
            records = records.filter((record) => [
                record.id, record.invoice, record.customerCode, record.customerName,
                record.phone, record.method, record.bank, record.reference
            ].join(' ').toLocaleLowerCase('id').includes(needle));
        }
        if (state.method) records = records.filter((record) => record.method === state.method);
        if (state.status) records = records.filter((record) => record.status === state.status);
        if (state.from) records = records.filter((record) => localDate(rowDate(record)) >= state.from);
        if (state.to) records = records.filter((record) => localDate(rowDate(record)) <= state.to);

        records.sort((left, right) => {
            const leftValue = left[state.sortKey];
            const rightValue = right[state.sortKey];
            if (typeof leftValue === 'number' && typeof rightValue === 'number') {
                return (leftValue - rightValue) * state.sortDirection;
            }
            return String(leftValue ?? '').localeCompare(String(rightValue ?? ''), 'id', { numeric: true }) * state.sortDirection;
        });
        return records;
    }

    function updateSummary() {
        const success = state.records.filter((record) => record.status === 'success');
        const pending = state.records.filter((record) => record.status === 'pending').length;
        const verification = state.records.filter((record) => record.status === 'verification').length;
        const unpaid = state.records.filter((record) => record.billStatus !== 'paid').length;
        const count = (value) => new Intl.NumberFormat('id-ID').format(value);
        const stats = {
            transactions: state.records.length,
            success: success.length,
            pending,
            verification,
            amount: money.format(success.reduce((sum, record) => sum + record.amount + record.fee, 0)),
            unpaid
        };
        Object.entries(stats).forEach(([key, value]) => {
            const target = root.querySelector(`[data-stat="${key}"]`);
            if (target) target.textContent = typeof value === 'number' && key !== 'amount' ? count(value) : value;
        });
        const counts = {
            all: state.records.length,
            pending,
            verification,
            success: success.length,
            failed: state.records.filter((record) => record.status === 'failed').length,
            history: state.records.filter((record) => ['success', 'failed'].includes(record.status)).length
        };
        Object.entries(counts).forEach(([key, value]) => {
            const target = root.querySelector(`[data-tab-count="${key}"]`);
            if (target) target.textContent = count(value);
        });
        root.querySelector('[data-verification-count]').textContent = count(verification);
        root.querySelector('[data-simulated-date]').textContent = formatDate(addDays(new Date(), state.simulatedDays));
    }

    function dueBadge(record) {
        if (record.billStatus === 'paid') return '<span class="payment-isolation-state">Lunas</span>';
        const due = billState(record);
        const klass = { future: 'is-future', due: 'is-due', grace: 'is-grace', late: 'is-late' }[due.key];
        const description = due.key === 'future' ? `${due.days} hari lagi` : (due.key === 'late' ? `Terlambat ${due.days} hari` : due.label);
        return `<span class="payment-isolation-state ${klass}">${escapeHtml(description)}</span>`;
    }

    function renderTable() {
        const records = visibleRecords();
        const pageCount = Math.max(1, Math.ceil(records.length / state.pageSize));
        state.page = Math.min(state.page, pageCount);
        const start = (state.page - 1) * state.pageSize;
        const pageRecords = records.slice(start, start + state.pageSize);
        const tbody = root.querySelector('[data-payment-rows]');
        tbody.innerHTML = pageRecords.length ? pageRecords.map((record, index) => {
            const due = dateFromOffset(record.dueOffset);
            const paid = record.paidAt ? formatDate(new Date(record.paidAt)) : '—';
            const methodIcon = record.method === 'QRIS' ? 'bi-qr-code' : (record.method === 'Virtual Account' ? 'bi-bank' : 'bi-arrow-left-right');
            const verificationAction = record.status === 'verification'
                ? `<button type="button" class="btn btn-outline-primary" data-payment-action="verify" data-record-id="${escapeHtml(record.id)}">Verifikasi</button>`
                : '';
            const payAction = record.status === 'pending' || record.status === 'failed'
                ? `<button type="button" class="btn btn-outline-success" data-payment-action="retry" data-record-id="${escapeHtml(record.id)}">Bayar</button>`
                : '';
            return `<tr>
                <td>${start + index + 1}</td>
                <td><button class="payment-link" type="button" data-payment-action="detail" data-record-id="${escapeHtml(record.id)}">${escapeHtml(record.id)}</button></td>
                <td><button class="payment-link" type="button" data-payment-action="invoice" data-record-id="${escapeHtml(record.id)}">${escapeHtml(record.invoice)}</button></td>
                <td>${escapeHtml(record.customerCode)}</td><td><span class="payment-customer-name">${escapeHtml(record.customerName)}</span></td>
                <td>${escapeHtml(record.period)}</td><td>${escapeHtml(formatDate(due))}<br>${dueBadge(record)}</td>
                <td><span class="payment-method-pill"><i class="bi ${methodIcon}"></i>${escapeHtml(record.method)}</span></td>
                <td>${money.format(record.amount + record.fee)}</td><td>${escapeHtml(paid)}</td><td>${setStatusBadge(record.status)}</td>
                <td><div class="payment-row-actions">${verificationAction}${payAction}<button type="button" class="btn btn-outline-secondary" data-payment-action="detail" data-record-id="${escapeHtml(record.id)}">Detail</button></div></td>
            </tr>`;
        }).join('') : '<tr><td class="payment-empty" colspan="12"><i class="bi bi-receipt"></i><br>Tidak ada transaksi yang sesuai dengan filter.</td></tr>';

        const from = records.length ? start + 1 : 0;
        const to = Math.min(start + state.pageSize, records.length);
        root.querySelector('[data-payment-pagination-info]').textContent = `Menampilkan ${from}–${to} dari ${records.length} transaksi`;
        const pageNumbers = root.querySelector('[data-page-numbers]');
        pageNumbers.innerHTML = Array.from({ length: pageCount }, (_, index) => {
            const page = index + 1;
            return `<button type="button" data-page-number="${page}" class="${page === state.page ? 'is-current' : ''}" aria-label="Halaman ${page}" ${page === state.page ? 'aria-current="page"' : ''}>${page}</button>`;
        }).join('');
        root.querySelector('[data-page="previous"]').disabled = state.page <= 1;
        root.querySelector('[data-page="next"]').disabled = state.page >= pageCount;
        root.querySelector('[data-payment-table-title]').textContent = {
            all: 'Semua Pembayaran',
            pending: 'Menunggu Pembayaran',
            verification: 'Menunggu Verifikasi',
            success: 'Pembayaran Berhasil',
            failed: 'Pembayaran Gagal',
            history: 'Riwayat Transaksi'
        }[state.tab];
        updateSummary();
    }

    function detailItem(label, value) {
        return `<div class="payment-detail-item"><span>${escapeHtml(label)}</span><strong>${escapeHtml(value || '—')}</strong></div>`;
    }

    function detailSection(title, items) {
        return `<section class="payment-detail-section"><h3>${escapeHtml(title)}</h3><div class="payment-detail-grid">${items.map(([label, value]) => detailItem(label, value)).join('')}</div></section>`;
    }

    function renderTimeline(record) {
        const steps = [
            ['Tagihan dibuat', record.history.find((entry) => entry.label === 'Tagihan dibuat')],
            ['Invoice diterbitkan', record.history.find((entry) => entry.label === 'Invoice tagihan diterbitkan')],
            ['Metode pembayaran dipilih', record.history.find((entry) => entry.label === 'Metode pembayaran dipilih')],
            ['Pembayaran dilakukan / bukti dikirim', record.history.find((entry) => ['Pembayaran dilakukan', 'Bukti transfer dikirim'].includes(entry.label))],
            ['Pembayaran diverifikasi', record.history.find((entry) => entry.label === 'Pembayaran diverifikasi')],
            ['Invoice pembayaran diterbitkan', record.history.find((entry) => entry.label === 'Invoice pembayaran diterbitkan')],
            ['Transaksi selesai', record.history.find((entry) => entry.label === 'Transaksi selesai')]
        ];
        return `<div class="payment-timeline">${steps.map(([label, entry]) => `<div class="payment-timeline-item ${entry ? 'is-done' : ''}"><span class="payment-timeline-dot"><i class="bi ${entry ? 'bi-check2' : 'bi-circle'}"></i></span><div class="payment-timeline-copy"><strong>${escapeHtml(label)}</strong><small>${entry ? `${escapeHtml(formatDateTime(entry.date))} · ${escapeHtml(entry.note)}` : 'Belum dilakukan'}</small></div></div>`).join('')}</div>`;
    }

    function showDetail(record) {
        if (!record) return;
        state.selectedId = record.id;
        document.getElementById('paymentDetailTitle').textContent = `${record.customerName} · ${record.id}`;
        const dueState = billState(record);
        const remainingDays = dueState.key === 'future' ? `${dueState.days} hari tersisa` : (dueState.key === 'late' ? `Terlambat ${dueState.days} hari` : dueState.label);
        const serviceLabel = { active: 'Aktif', isolated: 'Terisolasi', waiting_activation: 'Menunggu Aktivasi' }[record.serviceStatus] || record.serviceStatus;
        const detail = document.querySelector('[data-payment-detail]');
        detail.innerHTML = `
            <div class="payment-flow-summary"><span>${escapeHtml(record.customerCode)} · ${escapeHtml(record.customerName)}</span><strong>${setStatusBadge(record.status)}</strong><span>Tagihan · ${escapeHtml(record.invoice)}</span><strong>${money.format(record.amount + record.fee)}</strong></div>
            ${detailSection('Informasi pelanggan', [
                ['Kode pelanggan', record.customerCode], ['Nama pelanggan', record.customerName], ['Nomor WhatsApp', record.phone],
                ['Alamat', record.address], ['Paket internet', record.packageName], ['Mitra', record.partner]
            ])}
            ${detailSection('Informasi tagihan', [
                ['Nomor invoice tagihan', record.invoice], ['Periode tagihan', record.period], ['Tanggal terbit', formatDate(new Date(record.createdAt))],
                ['Tanggal jatuh tempo', formatDate(dateFromOffset(record.dueOffset))], ['Nominal tagihan', money.format(record.amount)],
                ['Denda', money.format(record.fee)], ['Total harus dibayar', money.format(record.amount + record.fee)],
                ['Status jatuh tempo', billState(record).label], ['Sisa waktu pembayaran', remainingDays],
                ['Akhir masa tenggang', formatDate(dateFromOffset(record.dueOffset + 7))]
            ])}
            ${detailSection('Informasi pembayaran', [
                ['ID transaksi', record.id], ['Metode pembayaran', record.method], ['Nominal pembayaran', record.status === 'success' ? money.format(record.amount + record.fee) : 'Belum diterima'],
                ['Waktu pembayaran', formatDateTime(record.paidAt)], ['Nomor referensi', record.reference], ['Status transaksi', statusLabels[record.status]],
                ['Bank', record.bank], ['Admin verifikasi', record.verifiedBy], ['Tanggal verifikasi', formatDateTime(record.verifiedAt)]
            ])}
            <section class="payment-detail-section"><h3>Riwayat proses transaksi</h3>${renderTimeline(record)}</section>
            <div class="payment-service-alert ${record.serviceStatus === 'active' ? 'is-active' : ''}"><strong>Status layanan: ${escapeHtml(serviceLabel)}.</strong> ${record.serviceStatus === 'isolated' ? 'Setelah pembayaran dikonfirmasi, lakukan simulasi reaktivasi.' : (record.serviceStatus === 'waiting_activation' ? 'Pembayaran lunas. Reaktivasi belum dijalankan.' : 'Status ini hanya simulasi, bukan koneksi MikroTik.')}${record.serviceStatus === 'waiting_activation' ? `<button class="btn btn-sm btn-success ms-2" type="button" data-payment-action="reactivate" data-record-id="${escapeHtml(record.id)}"><i class="bi bi-router"></i> Simulasikan Reaktivasi</button>` : ''}</div>
            ${record.status === 'verification' ? `<div class="payment-success-actions mt-3"><button class="btn btn-primary" type="button" data-payment-action="verify" data-record-id="${escapeHtml(record.id)}"><i class="bi bi-check2-circle"></i> Verifikasi & Lunasi</button><button class="btn btn-outline-danger" type="button" data-payment-action="reject" data-record-id="${escapeHtml(record.id)}"><i class="bi bi-x-circle"></i> Tolak Bukti</button></div>` : ''}
            ${record.evidenceName ? `<div class="mt-3"><strong class="d-block mb-2">Bukti transfer</strong><button class="btn btn-sm btn-outline-secondary" type="button" data-payment-action="proof" data-record-id="${escapeHtml(record.id)}"><i class="bi bi-image"></i> Lihat bukti · ${escapeHtml(record.evidenceName)}</button></div>` : ''}
        `;
        const detailPayButton = document.querySelector('#paymentDetailModal [data-payment-action="pay-selected"]');
        detailPayButton.disabled = record.billStatus === 'paid';
        detailPayButton.innerHTML = record.billStatus === 'paid'
            ? '<i class="bi bi-check2-circle"></i> Tagihan Lunas'
            : '<i class="bi bi-credit-card"></i> Lanjutkan Pembayaran';
        modals.detail.show();
    }

    function invoiceStatus(record) {
        const stateNow = billState(record);
        if (stateNow.key === 'paid') return 'Lunas';
        if (stateNow.key === 'late') return 'Terlambat';
        if (record.status === 'pending' || record.status === 'verification') return 'Menunggu pembayaran';
        return 'Belum dibayar';
    }

    function renderInvoice(record) {
        const unpaid = record.billStatus !== 'paid';
        const due = dateFromOffset(record.dueOffset);
        const stateNow = billState(record);
        const stateClassName = stateNow.key === 'paid' ? '' : 'is-unpaid';
        return `<div class="payment-invoice-document">
            <header class="payment-invoice-header">
                <div><div class="payment-brand"><span class="payment-brand-mark"><i class="bi bi-router-fill"></i></span><div><strong>KiNet eBilling</strong><small>Internet Service Provider</small></div></div><p>Jl. Gatot Subroto No. 88, Medan · (061) 555-0199 · billing@kinet.test</p></div>
                <div class="payment-invoice-no"><span>Invoice Tagihan</span><strong>${escapeHtml(record.invoice)}</strong><p>Terbit ${escapeHtml(formatDate(new Date(record.createdAt)))}</p></div>
            </header>
            <div class="payment-invoice-parties"><div><h4>Ditagihkan kepada</h4><strong>${escapeHtml(record.customerName)}</strong><p>${escapeHtml(record.customerCode)}</p><p>${escapeHtml(record.address)}</p><p>${escapeHtml(record.phone)}</p></div><div><h4>Informasi periode</h4><strong>${escapeHtml(record.period)}</strong><p>Paket ${escapeHtml(record.packageName)}</p><p>Mitra: ${escapeHtml(record.partner)}</p><p>Jatuh tempo: ${escapeHtml(formatDate(due))}</p></div></div>
            <table class="payment-invoice-table"><thead><tr><th>Deskripsi</th><th>Periode</th><th class="text-end">Jumlah</th></tr></thead><tbody><tr><td>Layanan internet ${escapeHtml(record.packageName)}</td><td>${escapeHtml(record.period)}</td><td class="text-end">${money.format(record.amount)}</td></tr><tr><td>Denda keterlambatan</td><td>—</td><td class="text-end">${money.format(record.fee)}</td></tr></tbody></table>
            <div class="payment-invoice-total"><div><span>Harga paket</span><strong>${money.format(record.amount)}</strong></div><div><span>Denda</span><strong>${money.format(record.fee)}</strong></div><div class="grand-total"><span>Total tagihan</span><strong>${money.format(record.amount + record.fee)}</strong></div></div>
            <div class="payment-invoice-due-box"><div><span>Tanggal jatuh tempo</span><strong>${escapeHtml(formatDate(due))}</strong></div><div><span>Sisa hari / terlambat</span><strong>${escapeHtml(stateNow.key === 'future' ? `${stateNow.days} hari lagi` : (stateNow.key === 'late' ? `${stateNow.days} hari terlambat` : stateNow.label))}</strong></div><div><span>Akhir masa tenggang</span><strong>${escapeHtml(formatDate(dateFromOffset(record.dueOffset + 7)))}</strong></div></div>
            <span class="payment-invoice-status ${stateClassName}">${escapeHtml(invoiceStatus(record))}</span>
            <footer class="payment-invoice-footer">Dokumen tagihan ini berbeda dengan bukti pembayaran. Pembayaran hanya dapat dilakukan melalui kanal yang tersedia pada simulasi KiNet eBilling. Layanan tidak diisolasi hanya karena invoice diterbitkan.</footer>
            ${unpaid ? `<div class="payment-success-actions"><button class="btn btn-primary" type="button" data-payment-action="pay-from-invoice" data-record-id="${escapeHtml(record.id)}"><i class="bi bi-credit-card"></i> Bayar Sekarang</button></div>` : ''}
        </div>`;
    }

    function openDocument(record, kind) {
        if (!record) return;
        state.selectedId = record.id;
        state.documentKind = kind;
        const doc = document.querySelector('[data-payment-document]');
        const kicker = document.querySelector('[data-document-kicker]');
        if (kind === 'invoice') {
            kicker.textContent = 'DOKUMEN TAGIHAN';
            document.getElementById('paymentDocumentTitle').textContent = `Invoice Tagihan · ${record.invoice}`;
            doc.innerHTML = renderInvoice(record);
        } else if (kind === 'receipt') {
            kicker.textContent = 'BUKTI PEMBAYARAN';
            document.getElementById('paymentDocumentTitle').textContent = `Bukti Pembayaran · ${record.id}`;
            doc.innerHTML = renderReceipt(record);
        } else {
            kicker.textContent = 'BUKTI TRANSFER SIMULASI';
            document.getElementById('paymentDocumentTitle').textContent = `Bukti Transfer · ${record.id}`;
            doc.innerHTML = `<div class="payment-receipt"><div class="payment-proof-placeholder"><div><i class="bi bi-file-earmark-image"></i><strong>${escapeHtml(record.evidenceName || 'Bukti transfer belum diunggah')}</strong><span>Pratinjau ini hanya menampilkan nama file yang dipilih. File tidak dikirim atau disimpan ke server.</span></div></div><div class="mt-3">${detailItem('Pengirim', record.senderName)}${detailItem('Bank pengirim', record.senderBank)}${detailItem('Tanggal transfer', record.transferDate ? formatDate(new Date(`${record.transferDate}T00:00:00`)) : '—')}${detailItem('Nominal transfer', money.format(record.amount + record.fee))}</div></div>`;
        }
        modals.document.show();
    }

    function renderReceipt(record) {
        return `<div class="payment-receipt">
            <header class="payment-invoice-header"><div class="payment-brand"><span class="payment-brand-mark"><i class="bi bi-check2-circle"></i></span><div><strong>KiNet eBilling</strong><small>Bukti Penerimaan Pembayaran</small></div></div><div class="payment-invoice-no"><span>No. invoice pembayaran</span><strong>${escapeHtml(record.paymentInvoice || '—')}</strong></div></header>
            <div class="payment-receipt-status"><i class="bi bi-check-circle-fill"></i> PEMBAYARAN LUNAS & BERHASIL</div>
            <div class="payment-receipt-grid">${[
                ['ID Transaksi', record.id], ['Nomor Referensi', record.reference],
                ['Nama Pelanggan', record.customerName], ['Kode Pelanggan', record.customerCode],
                ['Nomor Invoice Tagihan', record.invoice], ['Metode Pembayaran', record.method],
                ['Waktu Pembayaran', formatDateTime(record.paidAt)], ['Admin Verifikasi', record.verifiedBy || 'Otomatis (simulasi)']
            ].map(([label, value]) => detailItem(label, value)).join('')}</div>
            <div class="payment-invoice-total"><div><span>Nominal tagihan</span><strong>${money.format(record.amount)}</strong></div><div><span>Denda</span><strong>${money.format(record.fee)}</strong></div><div class="grand-total"><span>Total dibayar</span><strong>${money.format(record.amount + record.fee)}</strong></div></div>
            <footer class="payment-invoice-footer">Simpan bukti ini sebagai konfirmasi pembayaran. Ini adalah dokumen simulasi frontend dan bukan bukti transaksi bank sungguhan.</footer>
        </div>`;
    }

    function openFlow(record = null, forceMethod = '') {
        if (record) state.selectedId = record.id;
        state.selectedMethod = forceMethod;
        state.selectedBank = '';
        state.flowStage = record ? 'method' : 'invoice';
        renderFlow();
        modals.flow.show();
    }

    function flowSummary(record) {
        return `<div class="payment-flow-summary"><span>Invoice</span><strong>${escapeHtml(record.invoice)}</strong><span>Pelanggan</span><strong>${escapeHtml(record.customerName)} · ${escapeHtml(record.customerCode)}</strong><span>Jatuh tempo</span><strong>${escapeHtml(formatDate(dateFromOffset(record.dueOffset)))}</strong><span>Total pembayaran</span><strong class="payment-amount-highlight">${money.format(record.amount + record.fee)}</strong></div>`;
    }

    function methodOptions() {
        return `<div class="payment-method-grid">
            ${[['QRIS', 'bi-qr-code', 'Bayar dengan QR simulasi'], ['Virtual Account', 'bi-bank', 'Pilih bank tujuan'], ['Transfer Manual', 'bi-arrow-left-right', 'Unggah bukti transfer']].map(([method, icon, hint]) => `<button type="button" class="payment-method-option ${state.selectedMethod === method ? 'is-selected' : ''}" data-method-choice="${escapeHtml(method)}"><i class="bi ${icon}"></i><strong>${escapeHtml(method)}</strong><small>${escapeHtml(hint)}</small></button>`).join('')}
        </div>`;
    }

    function qrPattern() {
        return `<div class="payment-qr-simulation" aria-label="Pola QR code dekoratif simulasi">${Array.from({ length: 169 }, (_, index) => {
            const row = Math.floor(index / 13);
            const column = index % 13;
            const finder = (r, c) => (r < 5 && c < 5) || (r < 5 && c > 7) || (r > 7 && c < 5);
            const isDark = finder(row, column)
                ? (row % 4 === 0 || column % 4 === 0 || row % 4 === 1 || column % 4 === 1)
                : ((row * 7 + column * 11 + row * column) % 5 < 2);
            return `<span class="${isDark ? 'is-dark' : ''}"></span>`;
        }).join('')}</div>`;
    }

    function renderFlow() {
        const body = document.querySelector('[data-payment-flow]');
        const primary = document.querySelector('#paymentFlowModal [data-payment-action="flow-primary"]');
        const title = document.getElementById('paymentFlowTitle');
        const record = recordById();
        const cancel = document.querySelector('#paymentFlowModal [data-payment-action="cancel-flow"]');
        if (state.flowStage === 'invoice') {
            title.textContent = 'Pilih Tagihan Pelanggan';
            body.innerHTML = `<p class="payment-note">Pilih tagihan simulasi yang masih memiliki sisa pembayaran.</p><label class="payment-field"><span>Tagihan pelanggan</span><select class="form-select" data-flow-record>${state.records.filter((item) => item.billStatus !== 'paid').map((item) => `<option value="${escapeHtml(item.id)}" ${item.id === state.selectedId ? 'selected' : ''}>${escapeHtml(item.id)} · ${escapeHtml(item.customerName)} · ${escapeHtml(item.invoice)} · ${money.format(item.amount + item.fee)}</option>`).join('')}</select></label>${record ? flowSummary(record) : ''}`;
            primary.innerHTML = '<i class="bi bi-arrow-right"></i> Pilih Metode';
            primary.hidden = !state.records.some((item) => item.billStatus !== 'paid');
            cancel.hidden = false;
            return;
        }
        if (!record) {
            state.flowStage = 'invoice';
            renderFlow();
            return;
        }
        if (state.flowStage === 'method') {
            title.textContent = 'Pilih Metode Pembayaran';
            body.innerHTML = `${flowSummary(record)}${methodOptions()}<p class="payment-note mt-3 mb-0">Semua kanal pembayaran pada halaman ini adalah simulasi frontend.</p>`;
            primary.innerHTML = '<i class="bi bi-arrow-right"></i> Lanjutkan';
            primary.hidden = false;
            cancel.hidden = false;
            return;
        }
        if (state.flowStage === 'qris') {
            title.textContent = 'Pembayaran QRIS';
            if (!state.flowExpiry) state.flowExpiry = Date.now() + 5 * 60 * 1000;
            const seconds = Math.max(0, Math.ceil((state.flowExpiry - Date.now()) / 1000));
            body.innerHTML = `<div class="payment-qr-layout"><div>${qrPattern()}<p class="payment-qr-caption">QR code visual untuk simulasi, bukan kode pembayaran aktif.</p></div><div>${flowSummary(record)}<div class="payment-countdown" data-countdown>QR berlaku ${pad(Math.floor(seconds / 60))}:${pad(seconds % 60)}</div><p class="payment-note">Merchant: KiNet Internet Medan<br>Invoice tagihan dan bukti pembayaran merupakan dokumen berbeda.</p></div></div>`;
            primary.innerHTML = '<i class="bi bi-check2-circle"></i> Simulasikan Pembayaran Berhasil';
            primary.hidden = false;
            cancel.hidden = false;
            clearInterval(state.timer);
            state.timer = window.setInterval(() => {
                const countdown = document.querySelector('[data-countdown]');
                if (!countdown) {
                    clearInterval(state.timer);
                    return;
                }
                const remaining = Math.max(0, Math.ceil((state.flowExpiry - Date.now()) / 1000));
                countdown.textContent = remaining ? `QR berlaku ${pad(Math.floor(remaining / 60))}:${pad(remaining % 60)}` : 'QR simulasi kedaluwarsa. Batalkan dan mulai ulang untuk membuat sesi baru.';
                if (!remaining) clearInterval(state.timer);
            }, 1000);
            return;
        }
        if (state.flowStage === 'va') {
            title.textContent = 'Virtual Account Simulasi';
            const banks = ['BCA', 'BRI', 'BNI', 'Mandiri', 'Permata'];
            const vaNumber = state.selectedBank ? `${{
                BCA: '8808', BRI: '8877', BNI: '8810', Mandiri: '8950', Permata: '8528'
            }[state.selectedBank]}${record.customerCode.replace(/\D/g, '')}${record.id.slice(-4)}` : '';
            body.innerHTML = `${flowSummary(record)}<p class="payment-note">Pilih bank untuk membuat nomor VA lokal contoh.</p><div class="payment-bank-grid">${banks.map((bank) => `<button class="payment-bank-option ${state.selectedBank === bank ? 'is-selected' : ''}" type="button" data-bank-choice="${bank}">${bank}</button>`).join('')}</div>${state.selectedBank ? `<div class="payment-va-number"><span data-va-number>${escapeHtml(vaNumber)}</span><button type="button" class="btn btn-sm btn-outline-primary" data-payment-action="copy-va"><i class="bi bi-copy"></i> Salin</button></div><p class="payment-note mt-2">Atas nama ${escapeHtml(record.customerName)} · Batas waktu simulasi 24 jam sejak sesi dibuat.</p>` : ''}`;
            primary.innerHTML = '<i class="bi bi-check2-circle"></i> Simulasikan Pembayaran';
            primary.hidden = !state.selectedBank;
            cancel.hidden = false;
            return;
        }
        if (state.flowStage === 'manual') {
            title.textContent = 'Transfer Bank Manual';
            body.innerHTML = `${flowSummary(record)}<div class="payment-flow-summary"><span>Bank tujuan</span><strong>BCA · 1234567890</strong><span>Nama pemilik rekening</span><strong>PT KiNet Nusantara</strong></div>
                <div class="row g-2"><div class="col-md-6"><label class="payment-field"><span>Nama pengirim *</span><input class="form-control" data-manual-field="senderName" value="${escapeHtml(record.senderName)}" required></label></div>
                <div class="col-md-6"><label class="payment-field"><span>Bank pengirim *</span><select class="form-select" data-manual-field="senderBank" required><option value="">Pilih bank</option>${['BCA', 'BRI', 'BNI', 'Mandiri', 'Permata', 'Lainnya'].map((bank) => `<option ${record.senderBank === bank ? 'selected' : ''}>${bank}</option>`).join('')}</select></label></div>
                <div class="col-md-6"><label class="payment-field"><span>Nomor rekening pengirim</span><input class="form-control" data-manual-field="senderAccount" value="${escapeHtml(record.senderAccount)}" inputmode="numeric"></label></div>
                <div class="col-md-6"><label class="payment-field"><span>Tanggal transfer *</span><input class="form-control" type="date" data-manual-field="transferDate" value="${escapeHtml(record.transferDate || localDate(addDays(new Date(), state.simulatedDays)))}" required></label></div>
                <div class="col-12"><label class="payment-field"><span>Nominal transfer *</span><input class="form-control" type="number" min="1" step="1" data-manual-field="transferAmount" value="${record.amount + record.fee}" required></label></div>
                <div class="col-12"><label class="payment-field"><span>Upload bukti transfer *</span><input class="form-control" type="file" accept="image/*,.pdf" data-manual-field="evidence" required><small data-evidence>${record.evidenceName ? `Bukti sebelumnya: ${escapeHtml(record.evidenceName)}` : 'File hanya dibaca sebagai nama file simulasi, tidak diunggah.'}</small></label></div></div>
                <p class="payment-note mb-0">Setelah bukti dikirim, status menjadi Menunggu Verifikasi. Tagihan tetap belum lunas sampai admin menyetujui.</p>`;
            primary.innerHTML = '<i class="bi bi-send"></i> Kirim Bukti Pembayaran';
            primary.hidden = false;
            cancel.hidden = false;
            return;
        }
        if (state.flowStage === 'success') {
            title.textContent = 'Pembayaran Berhasil';
            const serviceMessage = record.serviceStatus === 'waiting_activation'
                ? `<div class="payment-service-alert"><strong>Layanan menunggu aktivasi kembali.</strong> Status terisolasi sebelum pelunasan. Reaktivasi jaringan hanya simulasi.<div class="mt-2"><button class="btn btn-sm btn-success" type="button" data-payment-action="reactivate" data-record-id="${escapeHtml(record.id)}"><i class="bi bi-router"></i> Simulasikan Reaktivasi</button></div></div>`
                : `<div class="payment-service-alert is-active">Status layanan: ${escapeHtml(record.serviceStatus === 'isolated' ? 'Terisolasi' : 'Aktif')}. Tidak ada koneksi MikroTik sungguhan.</div>`;
            body.innerHTML = `<div class="payment-receipt"><div class="payment-receipt-status"><i class="bi bi-check-circle-fill"></i> PEMBAYARAN LUNAS & BERHASIL</div>${renderReceipt(record)}${serviceMessage}<div class="payment-success-actions"><button class="btn btn-outline-primary" type="button" data-payment-action="receipt" data-record-id="${escapeHtml(record.id)}"><i class="bi bi-receipt"></i> Cetak Bukti Pembayaran</button><button class="btn btn-outline-secondary" type="button" data-payment-action="detail" data-record-id="${escapeHtml(record.id)}"><i class="bi bi-card-list"></i> Detail Transaksi</button></div></div>`;
            primary.innerHTML = '<i class="bi bi-file-earmark-check"></i> Lihat Invoice Pembayaran';
            primary.hidden = false;
            cancel.hidden = true;
        }
    }

    function startPayment(record, method = state.selectedMethod) {
        if (!record || record.billStatus === 'paid') {
            toast('Tagihan ini sudah lunas.');
            return;
        }
        state.selectedMethod = method;
        state.selectedBank = '';
        state.flowExpiry = 0;
        state.flowStage = method === 'QRIS' ? 'qris' : (method === 'Virtual Account' ? 'va' : 'manual');
        renderFlow();
    }

    function recordHistory(record, label, note) {
        record.history.push({
            label,
            note,
            date: addDays(new Date(), state.simulatedDays).toISOString()
        });
    }

    function finishPayment(record) {
        if (!record || record.status === 'success') return;
        const wasIsolated = record.serviceStatus === 'isolated';
        const completedAt = addDays(new Date(), state.simulatedDays);
        record.status = 'success';
        record.billStatus = 'paid';
        record.paidAt = completedAt.toISOString();
        record.reference = `SIM-${record.method.replace(/\s/g, '').toUpperCase()}-${Date.now().toString().slice(-8)}`;
        record.paymentInvoice = `PAY-INV-${localDate(completedAt).replace(/-/g, '')}-${record.id.slice(-4)}`;
        record.verifiedBy = record.method === 'Transfer Manual' ? 'Admin KiNet (simulasi)' : 'Otomatis (simulasi)';
        record.verifiedAt = completedAt.toISOString();
        record.verifyNote = record.method === 'Transfer Manual' ? (record.verifyNote || 'Bukti transfer diverifikasi admin.') : 'Transaksi berhasil (simulasi).';
        recordHistory(record, 'Pembayaran dilakukan', `${record.method} · ${money.format(record.amount + record.fee)}`);
        recordHistory(record, 'Pembayaran diverifikasi', `${record.reference} · ${record.verifiedBy}`);
        recordHistory(record, 'Invoice pembayaran diterbitkan', record.paymentInvoice);
        recordHistory(record, 'Transaksi selesai', 'Tagihan berubah menjadi lunas.');
        if (wasIsolated) {
            record.serviceStatus = 'waiting_activation';
            recordHistory(record, 'Reaktivasi layanan menunggu', 'Pelunasan diterima; proses reaktivasi hanya simulasi.');
        }
        saveData();
        renderTable();
        state.flowStage = 'success';
        renderFlow();
        toast('Pembayaran berhasil disimulasikan. Bukti dan invoice pembayaran telah diterbitkan.');
    }

    function submitManualPayment(record) {
        const fields = [...document.querySelectorAll('[data-manual-field]')];
        const name = document.querySelector('[data-manual-field="senderName"]');
        const bank = document.querySelector('[data-manual-field="senderBank"]');
        const date = document.querySelector('[data-manual-field="transferDate"]');
        const amount = document.querySelector('[data-manual-field="transferAmount"]');
        const evidence = document.querySelector('[data-manual-field="evidence"]');
        const invalid = fields.find((field) => field.type !== 'file' && !field.checkValidity());
        const file = evidence?.files?.[0];
        if (invalid) {
            invalid.reportValidity();
            return;
        }
        if (!file && !record.evidenceName) {
            toast('Pilih bukti transfer simulasi terlebih dahulu.');
            evidence?.focus();
            return;
        }
        if (Number(amount.value) !== record.amount + record.fee) {
            amount.setCustomValidity('Nominal transfer harus sama dengan total tagihan.');
            amount.reportValidity();
            amount.setCustomValidity('');
            return;
        }
        record.senderName = name.value.trim();
        record.senderBank = bank.value;
        record.senderAccount = document.querySelector('[data-manual-field="senderAccount"]').value.trim();
        record.transferDate = date.value;
        record.evidenceName = file?.name || record.evidenceName;
        record.method = 'Transfer Manual';
        record.status = 'verification';
        record.billStatus = 'unpaid';
        record.paidAt = null;
        record.reference = '';
        recordHistory(record, 'Bukti transfer dikirim', `${record.senderName} · ${record.senderBank} · ${record.evidenceName}`);
        saveData();
        clearInterval(state.timer);
        state.flowExpiry = 0;
        modals.flow.hide();
        renderTable();
        toast('Bukti pembayaran dikirim. Tagihan menunggu verifikasi admin dan belum lunas.');
    }

    function openVerification(record, mode = 'approve') {
        state.selectedId = record.id;
        state.verifyMode = mode;
        const form = document.querySelector('[data-verify-form]');
        form.reset();
        const reject = mode === 'reject';
        document.getElementById('paymentVerifyTitle').textContent = reject ? 'Tolak Bukti Pembayaran' : 'Verifikasi Pembayaran';
        document.querySelector('[data-verify-note-label]').textContent = reject ? 'Alasan penolakan *' : 'Catatan verifikasi *';
        document.querySelector('[data-verify-note]').placeholder = reject ? 'Wajib diisi, contoh: nominal tidak sesuai.' : 'Masukkan catatan verifikasi pembayaran.';
        document.querySelector('[data-verify-note]').required = true;
        document.querySelector('[data-verify-submit]').textContent = reject ? 'Tolak Pembayaran' : 'Verifikasi & Lunasi';
        document.querySelector('[data-verify-submit]').classList.toggle('btn-danger', reject);
        document.querySelector('[data-verify-submit]').classList.toggle('btn-primary', !reject);
        document.querySelector('[data-verify-summary]').textContent = `${record.id} · ${record.customerName} · ${record.invoice} · ${money.format(record.amount + record.fee)} · Bukti: ${record.evidenceName || 'tidak ada nama file'}`;
        document.querySelector('[data-verify-error]').textContent = '';
        modals.verify.show();
    }

    function reverify(record, note) {
        const wasIsolated = record.serviceStatus === 'isolated';
        const when = addDays(new Date(), state.simulatedDays);
        record.status = 'success';
        record.billStatus = 'paid';
        record.paidAt = when.toISOString();
        record.reference = `SIM-MAN-${Date.now().toString().slice(-8)}`;
        record.paymentInvoice = `PAY-INV-${localDate(when).replace(/-/g, '')}-${record.id.slice(-4)}`;
        record.verifiedBy = 'Admin KiNet (simulasi)';
        record.verifiedAt = when.toISOString();
        record.verifyNote = note;
        recordHistory(record, 'Pembayaran diverifikasi', `${note} · ${record.verifiedBy}`);
        recordHistory(record, 'Invoice pembayaran diterbitkan', record.paymentInvoice);
        recordHistory(record, 'Transaksi selesai', 'Tagihan berubah menjadi lunas.');
        if (wasIsolated) {
            record.serviceStatus = 'waiting_activation';
            recordHistory(record, 'Reaktivasi layanan menunggu', 'Pelunasan diterima; proses reaktivasi hanya simulasi.');
        }
        saveData();
        renderTable();
        modals.verify.hide();
        toast('Pembayaran diverifikasi dan tagihan ditandai lunas (simulasi).');
    }

    function rejectPayment(record, reason) {
        record.status = 'failed';
        record.billStatus = 'unpaid';
        record.paidAt = null;
        record.reference = `REJ-${Date.now().toString().slice(-8)}`;
        record.rejectedReason = reason;
        record.verifyNote = '';
        recordHistory(record, 'Bukti pembayaran ditolak', reason);
        saveData();
        renderTable();
        modals.verify.hide();
        toast('Bukti ditolak. Tagihan belum lunas; pelanggan dapat mengirim ulang bukti.');
    }

    function reactivate(record) {
        if (!record || record.billStatus !== 'paid' || record.serviceStatus !== 'waiting_activation') {
            toast('Reaktivasi tersedia setelah tagihan terisolasi berhasil dilunasi.');
            return;
        }
        record.serviceStatus = 'active';
        recordHistory(record, 'Layanan aktif kembali (simulasi)', 'Reaktivasi MikroTik simulasi berhasil.');
        saveData();
        renderTable();
        if (state.flowStage === 'success') renderFlow();
        if (document.getElementById('paymentDetailModal').classList.contains('show')) showDetail(record);
        toast('Layanan ditandai aktif kembali. Tidak ada koneksi MikroTik sungguhan.');
    }

    function resetDemo() {
        state.records = makeDemoRecords();
        state.simulatedDays = 0;
        state.tab = 'all';
        state.page = 1;
        state.search = '';
        state.from = '';
        state.to = '';
        state.method = '';
        state.status = '';
        root.querySelector('[data-payment-search]').value = '';
        root.querySelector('[data-filter-from]').value = '';
        root.querySelector('[data-filter-to]').value = '';
        root.querySelector('[data-filter-method]').value = '';
        root.querySelector('[data-filter-status]').value = '';
        root.querySelectorAll('[data-payment-filter]').forEach((tab) => tab.classList.toggle('is-active', tab.dataset.paymentFilter === 'all'));
        saveData();
        renderTable();
        modals.confirm.hide();
        toast('Data pembayaran demo dikembalikan ke kondisi awal.');
    }

    function exportCsv() {
        const headers = ['ID Transaksi', 'No Invoice Tagihan', 'Kode Pelanggan', 'Nama Pelanggan', 'Periode', 'Jatuh Tempo', 'Metode', 'Nominal', 'Tanggal Pembayaran', 'Status'];
        const csvEscape = (value) => {
            const text = String(value ?? '');
            return /[",\r\n]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
        };
        const rows = visibleRecords().map((record) => [
            record.id, record.invoice, record.customerCode, record.customerName, record.period,
            localDate(dateFromOffset(record.dueOffset)), record.method, record.amount + record.fee,
            record.paidAt ? localDate(new Date(record.paidAt)) : '', statusLabels[record.status]
        ]);
        const blob = new Blob(['\uFEFF' + [headers, ...rows].map((row) => row.map(csvEscape).join(',')).join('\r\n')], { type: 'text/csv;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `laporan-pembayaran-kinet-${localDate(addDays(new Date(), state.simulatedDays))}.csv`;
        link.click();
        URL.revokeObjectURL(url);
        toast(`${rows.length} transaksi diekspor ke CSV.`);
    }

    function cancelFlow() {
        clearInterval(state.timer);
        state.flowExpiry = 0;
        state.flowStage = '';
        state.selectedMethod = '';
    }

    loadData();
    recalculateIsolation();
    renderTable();

    document.addEventListener('click', (event) => {
        const tab = event.target.closest('[data-payment-filter]');
        if (tab) {
            state.tab = tab.dataset.paymentFilter;
            state.page = 1;
            root.querySelectorAll('[data-payment-filter]').forEach((item) => item.classList.toggle('is-active', item === tab));
            renderTable();
            return;
        }
        const sort = event.target.closest('[data-sort]');
        if (sort) {
            const key = sort.dataset.sort;
            state.sortDirection = state.sortKey === key ? state.sortDirection * -1 : 1;
            state.sortKey = key;
            renderTable();
            return;
        }
        const pageNumber = event.target.closest('[data-page-number]');
        if (pageNumber) {
            state.page = Number(pageNumber.dataset.pageNumber);
            renderTable();
            return;
        }
        const pageButton = event.target.closest('[data-page]');
        if (pageButton && !pageButton.disabled) {
            state.page += pageButton.dataset.page === 'next' ? 1 : -1;
            renderTable();
            return;
        }
        const bankChoice = event.target.closest('[data-bank-choice]');
        if (bankChoice) {
            state.selectedBank = bankChoice.dataset.bankChoice;
            renderFlow();
            return;
        }
        const methodChoice = event.target.closest('[data-method-choice]');
        if (methodChoice) {
            state.selectedMethod = methodChoice.dataset.methodChoice;
            renderFlow();
            return;
        }
        const action = event.target.closest('[data-payment-action]');
        if (!action) return;
        const record = recordById(action.dataset.recordId || state.selectedId);
        switch (action.dataset.paymentAction) {
            case 'detail':
                showDetail(record);
                break;
            case 'invoice':
                openDocument(record, 'invoice');
                break;
            case 'receipt':
                if (record?.status === 'success') openDocument(record, 'receipt');
                else toast('Bukti pembayaran tersedia setelah transaksi berhasil.');
                break;
            case 'proof':
                openDocument(record, 'proof');
                break;
            case 'retry':
            case 'pay-selected':
                if (document.getElementById('paymentDetailModal').classList.contains('show')) modals.detail.hide();
                openFlow(record);
                break;
            case 'pay-from-invoice':
                modals.document.hide();
                openFlow(record);
                break;
            case 'manual':
                state.selectedId = state.records.find((item) => item.billStatus !== 'paid')?.id ?? null;
                openFlow();
                break;
            case 'verification':
                state.tab = 'verification';
                root.querySelector('[data-payment-filter="verification"]').click();
                break;
            case 'export':
                exportCsv();
                break;
            case 'advance-date':
                state.simulatedDays += 1;
                recalculateIsolation();
                renderTable();
                toast(`Tanggal simulasi maju satu hari: ${formatDate(addDays(new Date(), state.simulatedDays))}.`);
                break;
            case 'reset-date':
                state.simulatedDays = 0;
                recalculateIsolation();
                saveData();
                renderTable();
                toast('Tanggal simulasi kembali ke hari ini.');
                break;
            case 'reset-demo':
                document.getElementById('paymentConfirmTitle').textContent = 'Reset Data Demo?';
                document.querySelector('[data-confirm-message]').textContent = 'Semua perubahan transaksi, bukti transfer, dan tanggal simulasi lokal akan dikembalikan ke data awal. Data server tidak disentuh.';
                state.pendingConfirm = resetDemo;
                modals.confirm.show();
                break;
            case 'verify':
                if (record) openVerification(record, 'approve');
                break;
            case 'reject':
                if (record) openVerification(record, 'reject');
                break;
            case 'view-invoice':
                if (record) openDocument(record, 'invoice');
                break;
            case 'flow-primary':
                if (state.flowStage === 'invoice') {
                    const select = document.querySelector('[data-flow-record]');
                    if (!select?.value) {
                        toast('Pilih tagihan terlebih dahulu.');
                        return;
                    }
                    state.selectedId = select.value;
                    state.flowStage = 'method';
                    renderFlow();
                } else if (state.flowStage === 'method') {
                    if (!state.selectedMethod) {
                        toast('Pilih salah satu metode pembayaran.');
                        return;
                    }
                    startPayment(record, state.selectedMethod);
                } else if (state.flowStage === 'qris') {
                    if (Date.now() >= state.flowExpiry) {
                        toast('QR simulasi sudah kedaluwarsa. Mulai ulang pembayaran.');
                        return;
                    }
                    finishPayment(record);
                } else if (state.flowStage === 'va') {
                    if (!state.selectedBank) {
                        toast('Pilih bank virtual account terlebih dahulu.');
                        return;
                    }
                    record.bank = state.selectedBank;
                    finishPayment(record);
                } else if (state.flowStage === 'manual') {
                    submitManualPayment(record);
                } else if (state.flowStage === 'success') {
                    openDocument(record, 'receipt');
                }
                break;
            case 'copy-va': {
                const number = document.querySelector('[data-va-number]')?.textContent;
                if (number && navigator.clipboard?.writeText) {
                    navigator.clipboard.writeText(number)
                        .then(() => toast('Nomor VA simulasi disalin.'))
                        .catch((error) => {
                            console.error('Gagal menyalin nomor VA:', error);
                            toast('Nomor VA tidak dapat disalin otomatis. Silakan salin secara manual.');
                        });
                }
                break;
            }
            case 'reactivate':
                reactivate(record);
                break;
            case 'print-document':
                document.body.classList.add('payment-printing');
                window.print();
                break;
            case 'confirm-reset':
                state.pendingConfirm?.();
                state.pendingConfirm = null;
                break;
            case 'cancel-flow':
                cancelFlow();
                break;
            default:
                break;
        }
    });

    root.querySelector('[data-payment-search]').addEventListener('input', (event) => {
        state.search = event.target.value;
        state.page = 1;
        renderTable();
    });
    root.querySelector('[data-filter-from]').addEventListener('change', (event) => { state.from = event.target.value; state.page = 1; renderTable(); });
    root.querySelector('[data-filter-to]').addEventListener('change', (event) => { state.to = event.target.value; state.page = 1; renderTable(); });
    root.querySelector('[data-filter-method]').addEventListener('change', (event) => { state.method = event.target.value; state.page = 1; renderTable(); });
    root.querySelector('[data-filter-status]').addEventListener('change', (event) => { state.status = event.target.value; state.page = 1; renderTable(); });
    root.querySelector('[data-payment-page-size]').addEventListener('change', (event) => { state.pageSize = Number(event.target.value); state.page = 1; renderTable(); });

    document.addEventListener('change', (event) => {
        if (event.target.matches('[data-flow-record]')) {
            state.selectedId = event.target.value;
            renderFlow();
        }
        if (event.target.matches('[data-manual-field="evidence"]')) {
            const filename = event.target.files?.[0]?.name;
            const note = document.querySelector('[data-evidence]');
            if (filename && note) note.textContent = `Nama file bukti simulasi: ${filename}`;
        }
    });

    document.querySelector('[data-verify-form]').addEventListener('submit', (event) => {
        event.preventDefault();
        const record = recordById();
        const note = document.querySelector('[data-verify-note]').value.trim();
        if (!record || !note) {
            document.querySelector('[data-verify-error]').textContent = 'Catatan wajib diisi sebelum melanjutkan.';
            document.querySelector('[data-verify-note]').focus();
            return;
        }
        if (state.verifyMode === 'reject') rejectPayment(record, note);
        else reverify(record, note);
    });

    document.getElementById('paymentFlowModal').addEventListener('hidden.bs.modal', cancelFlow);
    document.getElementById('paymentConfirmModal').addEventListener('hidden.bs.modal', () => { state.pendingConfirm = null; });
    document.getElementById('paymentDocumentModal').addEventListener('hidden.bs.modal', () => {
        document.body.classList.remove('payment-printing');
    });
    window.addEventListener('afterprint', () => document.body.classList.remove('payment-printing'));
});
