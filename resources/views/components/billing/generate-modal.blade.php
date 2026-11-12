<div class="modal fade" id="generateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-lightning-charge me-2"></i>Generate Tagihan Massal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <form method="POST" action="{{ route('admin.billing.generate') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Periode Tagihan</label>
                            <input type="month" class="form-control" name="periode" id="genPeriode" value="{{ now()->format('Y-m') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Terbit</label>
                            <input type="date" class="form-control" name="tanggal_terbit" id="genTerbit" value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jatuh Tempo</label>
                            <input type="date" class="form-control" name="tanggal_jatuh_tempo" id="genJatuhTempo" value="{{ now()->addDays(10)->toDateString() }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Pelanggan Sasaran</label>
                            <select class="form-select" name="target" id="genTarget">
                                <option value="all">Semua Pelanggan Aktif</option>
                                @foreach ($routers as $router)
                                    <option value="{{ $router }}">POP / Router: {{ $router }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="prorata" value="1" id="genProrata" checked>
                                <label class="form-check-label" for="genProrata">
                                    Aktifkan prorata pelanggan pasang baru
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Rumus prorata: <code>(Sisa Hari Aktif / Total Hari Bulan Berjalan) × Harga Pokok Paket</code>
                    </div>
                    <div id="genPreview" class="mt-3 d-none">
                        <div class="bill-pre">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong id="genPreviewCount">0 lembar</strong>
                                <span class="fw-bold" id="genPreviewTotal"></span>
                            </div>
                            <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>Pelanggan</th><th>Paket</th><th class="text-end">Total</th><th>Catatan</th></tr></thead>
                                    <tbody id="genPreviewRows"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-lightning-charge me-1"></i>Proses Generate</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modalEl = document.getElementById('generateModal');
    if (!modalEl) return;

    async function loadPreview() {
        const params = new URLSearchParams();
        params.append('periode', document.getElementById('genPeriode').value);
        params.append('target', document.getElementById('genTarget').value);
        params.append('prorata', document.getElementById('genProrata').checked ? '1' : '0');

        try {
            const res = await fetch('{{ route('admin.billing.preview') }}?' + params.toString(), { headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            const box = document.getElementById('genPreview');
            const rows = document.getElementById('genPreviewRows');
            const count = document.getElementById('genPreviewCount');
            const total = document.getElementById('genPreviewTotal');

            if (data.count === 0) {
                count.textContent = '0 lembar';
                total.textContent = 'Rp 0';
                rows.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Tidak ada pelanggan baru untuk ditagih pada periode ini.</td></tr>';
            } else {
                count.textContent = data.count + ' lembar';
                total.textContent = data.total_formatted;
                rows.innerHTML = data.targets.map((row) => `<tr>
                        <td>${row.nama}<br><small class="text-muted">${row.no_cst}</small></td>
                        <td>${row.paket}</td>
                        <td class="text-end">${row.total_formatted}</td>
                        <td><small class="text-muted">${row.prorata_note || '-'}</small></td>
                    </tr>`).join('');
            }

            box.classList.remove('d-none');
        } catch (error) {
            console.error('Preview generate gagal dimuat:', error);
        }
    }

    ['genPeriode', 'genTarget', 'genProrata'].forEach((id) => {
        document.getElementById(id).addEventListener('change', loadPreview);
    });

    modalEl.addEventListener('shown.bs.modal', loadPreview);
})();
</script>
@endpush
