<div class="modal fade" id="waModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-whatsapp me-2 text-success"></i>Kirim Pengingat WhatsApp Massal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Pilih preset templat, centang pelanggan tujuan, lalu kirim pengingat satu-per-satu dengan progres per pelanggan.</p>

                <div class="mb-3">\n                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="waCheckAll" checked>
                        <label class="form-check-label fw-semibold" for="waCheckAll">Pilih semua pelanggan</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Preset Template</label>
                    <select class="form-select" id="waTemplateSelect">
                        <option value="h3">Pengingat H-3 (ramah: rekening + QRIS)</option>
                        <option value="hari_h">Hari-H Jatuh Tempo (batas akhir pembayaran)</option>
                        <option value="overdue">Overdue (risiko auto-isolir MikroTik)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label d-flex justify-content-between">
                        <span>Pratinjau Pesan (variabel: {nama} {no_invoice} {total_tagihan} {jatuh_tempo} {link_pembayaran})</span>
                        <span class="text-muted">Pilih pelanggan untuk preview</span>
                    </label>
                    <select class="form-select mb-2" id="waSelectInvoice">
                        <option value="">- Pilih invoice untuk preview -</option>
                        @foreach ($invoices->filter(fn ($r) => in_array($r['status'], ['unpaid', 'overdue', 'pending'], true)) as $inv)
                            <option value="{{ $inv['no_invoice'] }}" data-nama="{{ $inv['nama'] }}" data-jatuh="{{ $inv['tanggal_jatuh_tempo'] }}" data-total="{{ $inv['total'] }}">{{ $inv['no_invoice'] }} - {{ $inv['nama'] }}</option>
                        @endforeach
                    </select>
                    <textarea class="bill-wa-preview" id="waPreview" readonly placeholder="Preview pesan akan tampil di sini..."></textarea>
                </div>

                <div class="progress mb-2 d-none" id="waProgressWrap" role="progressbar" aria-label="Progres pengiriman">
                    <div class="progress-bar bg-success progress-bar-striped progress-bar-animated" id="waProgressBar" style="width: 0%"></div>
                </div>
                <p class="small text-muted d-none mb-2" id="waProgressText"></p>

                <div class="bill-pre">
                    <strong class="d-block mb-2">Pelanggan yang akan dikirimi (unpaid + overdue)</strong>
                    <div class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                        <table class="table table-sm mb-0">
                            <thead><tr><th></th><th>Invoice</th><th>Pelanggan</th><th class="text-end">Total</th><th>Status</th><th class="text-center">Kirim</th></tr></thead>
                            <tbody id="waRecipientBody">
                                @foreach ($invoices->filter(fn ($r) => in_array($r['status'], ['unpaid', 'overdue'], true)) as $inv)
                                    <tr data-invoice="{{ $inv['no_invoice'] }}">
                                        <td>
                                            <input class="form-check-input wa-recipient" type="checkbox"
                                                value="{{ $inv['no_invoice'] }}"
                                                data-nama="{{ $inv['nama'] }}"
                                                data-jatuh="{{ $inv['tanggal_jatuh_tempo'] }}"
                                                data-total="{{ $inv['total'] }}" checked>
                                        </td>
                                        <td class="font-monospace small">{{ $inv['no_invoice'] }}</td>
                                        <td>{{ $inv['nama'] }}</td>
                                        <td class="text-end">Rp {{ number_format($inv['total'], 0, ',', '.') }}</td>
                                        <td><span class="bill-badge bill-badge--{{ $inv['status'] }}">{{ $inv['status'] }}</span></td>
                                        <td class="text-center wa-status"><span class="text-muted">–</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <form method="POST" action="{{ route('admin.billing.wa') }}" class="d-inline" id="waForm">
                    @csrf
                    <input type="hidden" name="no_invoice" id="waInvoiceInput" value="">
                    <input type="hidden" name="template" id="waTemplateInput" value="h3">
                    <button type="submit" class="btn btn-success"><i class="bi bi-whatsapp me-1"></i>Kirim Pengingat</button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const tplSelect = document.getElementById('waTemplateSelect');
    const tplInput = document.getElementById('waTemplateInput');
    const invSelect = document.getElementById('waSelectInvoice');
    const invInput = document.getElementById('waInvoiceInput');
    const preview = document.getElementById('waPreview');
    const form = document.getElementById('waForm');
    const checkAll = document.getElementById('waCheckAll');
    const body = document.getElementById('waRecipientBody');
    const progressWrap = document.getElementById('waProgressWrap');
    const progressBar = document.getElementById('waProgressBar');
    const progressText = document.getElementById('waProgressText');
    const submitBtn = form.querySelector('button[type="submit"]');
    let waSending = false;
    const rows = () => Array.from(body.querySelectorAll('tr[data-invoice]'));

    // Checklist pendukung: pilih semua + preview berdasar checkbox pertama terpilih.
    if (checkAll && body) {
        checkAll.addEventListener('change', function () {
            rows().forEach((tr) => {
                tr.querySelector('.wa-recipient').checked = checkAll.checked;
            });
        });

        body.addEventListener('change', function () {
            const cbs = rows().map((tr) => tr.querySelector('.wa-recipient'));
            checkAll.checked = cbs.every((cb) => cb.checked);
            checkAll.indeterminate = cbs.some((cb) => cb.checked) && !checkAll.checked;
        });
    }

    const rekening = 'Bank BCA 8820-1001-22 a.n. PT KINET\nQRIS: https://qr.kiynet.app/pay/';
    const link = 'https://kiynet.app/pay/';

    function templates(t) {
        const nama = '{nama}', noInv = '{no_invoice}', total = '{total_tagihan}', jt = '{jatuh_tempo}', lp = '{link_pembayaran}';
        const rk = rekening;
        if (t === 'h3') {
            return 'Halo *' + nama + '*, terima kasih telah berlangganan KiNet Internet.\n\n'
                + 'Tagihan Anda sebesar *' + total + '* (No. Invoice *' + noInv + '*) akan jatuh tempo pada *' + jt + '*.\n\n'
                + 'Pembayaran dapat dilakukan melalui:\n' + rk + '\n\nDetail tagihan: ' + lp + '\n\nTerima kasih 🙏';
        }
        if (t === 'hari_h') {
            return 'Yth. *' + nama + '*, hari ini adalah batas akhir pembayaran tagihan internet Anda sebesar *'
                + total + '* (Invoice *' + noInv + '*).\n\n'
                + 'Mohon segera lakukan pembayaran sebelum *' + jt + '* pukul 23.59 untuk menghindari penundaan layanan.\n\n'
                + 'Pembayaran: ' + rk + '\nBayar online: ' + lp + '\n\nTerima kasih.';
        }
        return 'Yth. *' + nama + '*, tagihan internet Anda sebesar *' + total + '* (Invoice *' + noInv + '*) '
            + 'telah melewati jatuh tempo pada *' + jt + '*.\n\n'
            + 'Mohon segera lunasi tagihan. Apabila tidak ada pembayaran hingga 3 hari ke depan, layanan akan '
            + '*otomatis diisolir* pada MikroTik.\n\nPembayaran: ' + rk + '\nBayar online: ' + lp + '\n\nTerima kasih.';
    }

    function refreshPreview() {
        const tpl = tplSelect.value;
        tplInput.value = tpl;
        const opt = invSelect.selectedOptions[0];
        const inv = invSelect.value;
        invInput.value = inv;

        if (!inv || !opt) {
            preview.value = 'Pilih invoice di atas untuk menampilkan preview.';
            return;
        }

        const invData = {
            nama: opt.dataset.nama,
            no_invoice: inv,
            total_tagihan: 'Rp ' + Number(opt.dataset.total).toLocaleString('id-ID'),
            jatuh_tempo: opt.dataset.jatuh,
            link_pembayaran: link + inv
        };

        let msg = templates(tpl);
        Object.keys(invData).forEach((key) => { msg = msg.replace('{' + key + '}', invData[key]); });
        preview.value = msg;
    }

    if (tplSelect) {
        tplSelect.addEventListener('change', refreshPreview);
        invSelect.addEventListener('change', refreshPreview);
        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const targets = rows()
                .map((tr) => ({ tr, cb: tr.querySelector('.wa-recipient') }))
                .filter((r) => r.cb.checked);

            if (targets.length === 0) {
                alert('Centang minimal satu pelanggan terlebih dahulu.');
                return;
            }
            if (waSending) return;
            waSending = true;
            submitBtn.disabled = true;
            progressWrap.classList.remove('d-none');

            let success = 0, fail = 0;
            for (let i = 0; i < targets.length; i++) {
                const { tr, cb } = targets[i];
                const status = tr.querySelector('.wa-status');
                const noInv = cb.value;
                const template = tplSelect.value;
                progressText.classList.remove('d-none');
                progressText.textContent = 'Mengirim ' + (i + 1) + ' dari ' + targets.length + ' pelanggan...';

                try {
                    const fd = new FormData();
                    fd.append('no_invoice', noInv);
                    fd.append('template', template);
                    fd.append('_token', form.querySelector('input[name="_token"]').value);

                    const res = await fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });

                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    const data = await res.json();
                    success++;
                    status.innerHTML = '<span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>Terkirim</span>';
                    window.open(data.wa_link, '_blank');
                } catch (err) {
                    fail++;
                    status.innerHTML = '<span class="badge bg-danger-subtle text-danger"><i class="bi bi-x-circle me-1"></i>Gagal</span>';
                }

 progressBar.style.width = Math.round((i + 1) / targets.length * 100) + '%';
            }

            progressText.textContent = 'Selesai: ' + success + ' berhasil' + (fail > 0 ? ', ' + fail + ' gagal' : '') + '.';
            progressText.classList.toggle('text-danger', fail > 0);
            submitBtn.disabled = false;
            waSending = false;
        });
    }
})();
</script>
@endpush
