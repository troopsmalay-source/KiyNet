@props(['customer' => null, 'statuses' => []])

@php
    $record = $customer ?? [];
    $action = filled($record['id'] ?? null) ? route('admin.cst-internal.status', $record['id']) : '';
@endphp

<div class="modal fade cst-modal" id="cstStatusModal" tabindex="-1" aria-labelledby="cstStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="{{ $action }}">
            @csrf
            <input type="hidden" name="_form" value="status">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="cstStatusModalLabel">Ubah Status Pelanggan</h5>
                    <p class="cst-modal-sub" data-cst-status-subject>
                        {{ filled($record['id'] ?? null) ? $record['id'].' · '.$record['nama'] : 'Pilih pelanggan dari tabel.' }}
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                @if ($errors->any() && old('_form') === 'status')
                    <div class="cst-form-error" role="alert">
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                        <span>Periksa kembali isian berikut: {{ $errors->first() }}</span>
                    </div>
                @endif
                <div class="cst-modal-status">
                    <span>Status saat ini</span>
                    <x-cst-internal.status-badge :status="$record['status'] ?? 'Pending'" size="lg" data-cst-current-status />
                </div>

                <div class="cst-field" style="margin-bottom: 12px;">
                    <label for="cst-status-select">Status baru <em>*</em></label>
                    <select class="form-select" id="cst-status-select" name="status" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" @selected(old('status', $record['status'] ?? 'Pending') === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="cst-field">
                    <label for="cst-status-reason">Alasan / catatan perubahan <em>*</em></label>
                    <textarea class="form-control" id="cst-status-reason" name="reason" rows="3" required minlength="5" maxlength="500"
                        placeholder="Contoh: Pembayaran diterima via QRIS, layanan dipulihkan dan secret PPPoE diaktifkan kembali.">{{ old('reason') }}</textarea>
                </div>

                <p class="cst-hint" style="margin-top: 10px;">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    Perubahan dicatat pada timeline: waktu, status lama → baru, petugas, dan catatan. Sinkronisasi MikroTik mengikuti status baru.
                </p>
            </div>

            <div class="modal-footer">
                <button type="button" class="cst-btn cst-btn--ghost" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="cst-btn cst-btn--primary">
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i> Simpan Perubahan Status
                </button>
            </div>
        </form>
    </div>
</div>
