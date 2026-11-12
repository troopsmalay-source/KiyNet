<section class="process-section" aria-label="Ilustrasi alur proses KiNet eBilling">
    <div class="process-top">
        <article class="flow-card customer-flow">
            <header class="flow-header">
                <span class="step-number">1</span>
                <h2>Input Data Pelanggan (Admin / Mitra)</h2>
            </header>
            <div class="customer-preview">
                <div class="customer-illustration">
                    <img src="{{ asset('assets/images/customer-admin.svg') }}" alt="" loading="lazy">
                    <div class="illustration-caption"><strong>Admin / Mitra</strong><span>menginput data pelanggan</span></div>
                </div>
                <div class="mock-form" aria-label="Contoh tampilan form pelanggan">
                    <strong class="mock-form-title">Tambah Pelanggan</strong>
                    <label>Nama Pelanggan <span>*</span><input readonly placeholder="Nama pelanggan"></label>
                    <label>No. Pelanggan<input readonly placeholder="Nomor pelanggan"></label>
                    <label>Email <span>*</span><input readonly placeholder="Email pelanggan"></label>
                    <label>Alamat<input readonly placeholder="Alamat pemasangan"></label>
                    <div class="mock-actions">
                        <button type="button" class="mock-button mock-button-muted" data-toast="Pratinjau form saja — data tidak disimpan.">Batal</button>
                        <button type="button" class="mock-button mock-button-primary" data-toast="Contoh tampilan saja — data tidak disimpan."><i class="bi bi-floppy"></i> Simpan</button>
                    </div>
                </div>
            </div>
        </article>

        <article class="flow-card reminder-flow">
            <header class="flow-header">
                <span class="step-number">2</span>
                <h2>Kirim Pengingat Tagihan</h2>
            </header>
            <div class="reminder-preview">
                <div class="reminder-composer">
                    <div class="preview-tabs" data-preview-tabs>
                        <button class="preview-tab active" type="button" data-preview-tab="whatsapp"><i class="bi bi-whatsapp"></i> WhatsApp</button>
                        <button class="preview-tab" type="button" data-preview-tab="email"><i class="bi bi-envelope-fill"></i> Email</button>
                        <button class="preview-tab" type="button" data-preview-tab="sms"><i class="bi bi-chat-dots-fill"></i> SMS</button>
                    </div>
                    <label class="preview-label">Pilih Pelanggan
                        <select disabled><option>Pilih pelanggan</option></select>
                    </label>
                    <label class="preview-label">Pesan Pengingat
                        <textarea readonly rows="4" data-reminder-message>Yth. Pelanggan KiNet,
Tagihan internet Anda akan segera jatuh tempo.
Silakan periksa detail tagihan pada aplikasi KiNet.
Terima kasih.</textarea>
                    </label>
                    <div class="mock-actions reminder-actions">
                        <button type="button" class="mock-button mock-button-muted" data-toast="Ini hanya pratinjau pesan, tidak dikirim."><i class="bi bi-eye"></i> Preview</button>
                        <button type="button" class="mock-button mock-button-primary" data-toast="Pengiriman pengingat belum tersedia. Tidak ada pesan yang dikirim."><i class="bi bi-send-fill"></i> Kirim Sekarang</button>
                    </div>
                </div>
                <img class="phone-illustration" src="{{ asset('assets/images/reminder-phone.svg') }}" alt="Ilustrasi pratinjau pesan pada smartphone" loading="lazy">
            </div>
        </article>

        <article class="flow-card payment-flow">
            <header class="flow-header">
                <span class="step-number">3</span>
                <h2>Pembayaran oleh Pelanggan</h2>
            </header>
            <div class="payment-preview">
                <div class="preview-tabs payment-tabs" data-payment-tabs>
                    <button class="preview-tab active" type="button" data-payment-tab="qris"><i class="bi bi-qr-code"></i> QRIS</button>
                    <button class="preview-tab" type="button" data-payment-tab="virtual-account"><i class="bi bi-bank"></i> Virtual Account</button>
                </div>
                <div class="payment-method-panel active" data-payment-panel="qris">
                    <div class="qr-preview">
                        <img src="{{ asset('assets/images/qr-preview.svg') }}" alt="" loading="lazy">
                        <span>Ilustrasi QRIS<br><small>Bukan kode pembayaran</small></span>
                    </div>
                    <div class="payment-instructions"><i class="bi bi-qr-code-scan"></i><strong>Scan QR Code</strong><span>untuk melihat contoh pembayaran</span></div>
                </div>
                <div class="payment-method-panel va-panel" data-payment-panel="virtual-account" hidden>
                    <span class="va-label">Nomor Virtual Account</span>
                    <strong class="va-number">•••• •••• ••••</strong>
                    <span class="va-label">Bank tujuan</span>
                    <div class="bank-list"><span>BCA</span><span>mandiri</span><span>BRI</span></div>
                    <small class="preview-disclaimer">Tampilan ilustrasi — bukan nomor pembayaran.</small>
                </div>
            </div>
        </article>
    </div>

    <div class="process-bottom">
        <article class="flow-card status-flow">
            <header class="flow-header">
                <span class="step-number">4</span>
                <h2>Status Pembayaran</h2>
            </header>
            <div class="status-preview-body">
                <div class="payment-success-preview">
                    <i class="bi bi-check-circle-fill"></i>
                    <div><strong>Contoh Status Berhasil</strong><span>Pratinjau saja — bukan transaksi.</span></div>
                </div>
                <dl class="payment-details">
                    <div><dt>No. Invoice</dt><dd>—</dd></div>
                    <div><dt>Pelanggan</dt><dd>—</dd></div>
                    <div><dt>Jumlah Bayar</dt><dd>Rp 0</dd></div>
                    <div><dt>Tanggal Bayar</dt><dd>—</dd></div>
                </dl>
            </div>
        </article>

        <article class="flow-card invoice-flow">
            <header class="flow-header">
                <span class="step-number">5</span>
                <h2>Cetak Invoice</h2>
            </header>
            <div class="invoice-preview-body">
                <div class="invoice-actions">
                    <button type="button" class="mock-button mock-button-primary" data-toast="Fitur cetak invoice tersedia pada tahap modul tagihan."><i class="bi bi-printer-fill"></i> Cetak Invoice</button>
                    <button type="button" class="mock-button mock-button-outline" data-toast="Unduh PDF belum tersedia. Tidak ada file transaksi dibuat."><i class="bi bi-download"></i> Download PDF</button>
                    <button type="button" class="mock-button mock-button-outline" data-toast="Pengiriman email invoice belum tersedia."><i class="bi bi-envelope-fill"></i> Kirim Email</button>
                </div>
                <div class="invoice-paper">
                    <div class="invoice-brand"><img src="{{ asset('assets/images/kinet-logo.svg') }}" alt="kiynet"><span>INVOICE<small>PRATINJAU</small></span></div>
                    <div class="invoice-rule"></div>
                    <div class="invoice-paper-row"><span>Pelanggan</span><strong>—</strong></div>
                    <div class="invoice-paper-row"><span>Periode</span><strong>—</strong></div>
                    <div class="invoice-paper-row invoice-total"><span>Jumlah</span><strong>Rp 0</strong></div>
                    <img class="invoice-qr" src="{{ asset('assets/images/qr-preview.svg') }}" alt="">
                    <small class="invoice-preview-note">Ilustrasi saja</small>
                </div>
            </div>
        </article>

        <aside class="quick-flow">
            <h2>Alur Proses Singkat</h2>
            <ol>
                <li><span>1</span><div>Input data pelanggan<small>(Admin / Mitra)</small></div></li>
                <li><span>2</span><div>Kirim pengingat tagihan</div></li>
                <li><span>3</span><div>Pelanggan bayar<small>(QRIS / Virtual Account)</small></div></li>
                <li><span>4</span><div>Status pembayaran terupdate</div></li>
                <li><span>5</span><div>Cetak invoice</div></li>
            </ol>
        </aside>
    </div>
</section>
