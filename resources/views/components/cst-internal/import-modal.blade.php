<div class="modal fade cst-modal" id="cstImportModal" tabindex="-1" aria-labelledby="cstImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="cstImportModalLabel">Impor Data CST</h5>
                    <p class="cst-modal-sub">Unggah berkas Excel atau CSV berisi data pelanggan.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="cst-import-note">
                    <i class="bi bi-tools" aria-hidden="true"></i>
                    Endpoint impor massal belum aktif pada tahap UI ini. Gunakan <strong>Ekspor CSV</strong> untuk melihat format kolom yang akan dipakai.
                </p>
                <label class="cst-dropzone" for="cst-import-file">
                    <i class="bi bi-file-earmark-arrow-up" aria-hidden="true"></i>
                    <strong>Pilih berkas .csv atau .xlsx</strong>
                    <span>Maksimal 5 MB, maksimal 500 baris per unggahan.</span>
                    <input type="file" id="cst-import-file" accept=".csv,.xlsx" disabled>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="cst-btn cst-btn--ghost" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="cst-btn cst-btn--primary" disabled title="Menunggu endpoint impor backend">Impor Sekarang</button>
            </div>
        </div>
    </div>
</div>
