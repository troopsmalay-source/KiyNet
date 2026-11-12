@php
    $oldKey = old('_form');
@endphp

<div class="modal fade pkg-modal" id="pkgFormModal" tabindex="-1" aria-labelledby="pkgFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="pkgFormModalLabel">Tambah Paket Baru</h5>
                    <p class="pkg-modal-sub">Lengkapi informasi paket layanan baru.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <form id="pkgForm" method="POST" action="{{ route('admin.packages.store') }}" novalidate>
                    @csrf

                    <section class="pkg-form-section">
                        <div class="pkg-form-section-head"><i class="bi bi-info-circle"></i> Informasi Dasar &amp; Visibilitas</div>
                        <div class="pkg-form-grid">
                            <div class="pkg-field">
                                <label for="pkg-nama">Nama Paket <em>*</em></label>
                                <input id="pkg-nama" type="text" class="form-control @error('nama') is-invalid @enderror" name="nama" value="{{ old('nama') }}" placeholder="KiNet Home Ultra 50" required>
                                @error('nama') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field">
                                <label for="pkg-kategori">Kategori Layanan <em>*</em></label>
                                <select id="pkg-kategori" class="form-select @error('kategori') is-invalid @enderror" name="kategori" required>
                                    @foreach (\App\Support\PackageServiceRepository::KATEGORI as $key => $label)
                                        <option value="{{ $key }}" {{ old('kategori') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('kategori') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field">
                                <label for="pkg-siklus">Siklus Tagihan <em>*</em></label>
                                <select id="pkg-siklus" class="form-select @error('siklus_tagihan') is-invalid @enderror" name="siklus_tagihan" required>
                                    @foreach (\App\Support\PackageServiceRepository::SIKLUS as $key => $label)
                                        <option value="{{ $key }}" {{ old('siklus_tagihan') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('siklus_tagihan') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field">
                                <label for="pkg-status">Status Paket</label>
                                <select id="pkg-status" class="form-select" name="is_active">
                                    <option value="1" {{ old('is_active', '1') === '1' ? 'selected' : '' }}>Aktif</option>
                                    <option value="0" {{ old('is_active') === '0' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                            <div class="pkg-field pkg-field--full">
                                <div class="pkg-check">
                                    <input type="checkbox" id="pkg-is-custom" name="is_custom" value="1" {{ old('is_custom') ? 'checked' : '' }}>
                                    <label for="pkg-is-custom">
                                        Jadikan Paket Khusus (Tidak tampil di pendaftaran umum)
                                        <small>Paket hanya terlihat untuk pelanggan tertentu.</small>
                                    </label>
                                </div>
                            </div>
                            <div class="pkg-field pkg-field--full pkg-custom-field" style="display: none">
                                <label for="pkg-custom-pelanggan">Pelanggan yang Berhak (pisahkan ID/Label dengan koma)</label>
                                <input id="pkg-custom-pelanggan" type="text" class="form-control @error('custom_pelanggan') is-invalid @enderror" name="custom_pelanggan" value="{{ old('custom_pelanggan') }}" placeholder="CST-202611-001, CST-202611-004">
                                @error('custom_pelanggan') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="pkg-form-section">
                        <div class="pkg-form-section-head"><i class="bi bi-hdd-network"></i> Bandwidth &amp; Profil MikroTik</div>
                        <div class="pkg-form-grid">
                            <div class="pkg-field">
                                <label for="pkg-download">Kecepatan Download (Mbps) <em>*</em></label>
                                <input id="pkg-download" type="number" step="0.5" min="0.5" class="form-control @error('download_mbps') is-invalid @enderror" name="download_mbps" value="{{ old('download_mbps') }}" placeholder="50" required>
                                @error('download_mbps') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field">
                                <label for="pkg-upload">Kecepatan Upload (Mbps) <em>*</em></label>
                                <input id="pkg-upload" type="number" step="0.5" min="0.5" class="form-control @error('upload_mbps') is-invalid @enderror" name="upload_mbps" value="{{ old('upload_mbps') }}" placeholder="20" required>
                                @error('upload_mbps') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field">
                                <label for="pkg-profile">PPP Profile MikroTik <em>*</em></label>
                                <input id="pkg-profile" type="text" class="form-control @error('mikrotik_profile') is-invalid @enderror" name="mikrotik_profile" value="{{ old('mikrotik_profile') }}" placeholder="profile-50m-retail" list="pkgProfileList" required>
                                <datalist id="pkgProfileList">
                                    @foreach ($profiles ?? [] as $profile)
                                        <option value="{{ $profile }}"></option>
                                    @endforeach
                                </datalist>
                                @error('mikrotik_profile') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field">
                                <label for="pkg-rate-limit">Rate-Limit (rx-rate/tx-rate) <em>*</em></label>
                                <input id="pkg-rate-limit" type="text" class="form-control @error('rate_limit') is-invalid @enderror" name="rate_limit" value="{{ old('rate_limit') }}" placeholder="20M/50M" required>
                                @error('rate_limit') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field">
                                <label for="pkg-ip-pool">Address Pool</label>
                                <input id="pkg-ip-pool" type="text" class="form-control @error('ip_pool') is-invalid @enderror" name="ip_pool" value="{{ old('ip_pool') }}" placeholder="100.64.0.0/24">
                                @error('ip_pool') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field">
                                <label for="pkg-queue">Queue Priority (1-8) <em>*</em></label>
                                <input id="pkg-queue" type="number" min="1" max="8" class="form-control @error('queue_priority') is-invalid @enderror" name="queue_priority" value="{{ old('queue_priority', '5') }}" required>
                                @error('queue_priority') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="pkg-form-section">
                        <div class="pkg-form-section-head"><i class="bi bi-cash-stack"></i> Biaya, Diskon &amp; Add-on</div>
                        <div class="pkg-form-grid">
                            <div class="pkg-field">
                                <label for="pkg-harga">Harga Pokok Berlangganan (Rp) <em>*</em></label>
                                <input id="pkg-harga" type="number" min="0" step="1000" class="form-control @error('harga') is-invalid @enderror" name="harga" value="{{ old('harga') }}" placeholder="250000" required>
                                @error('harga') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field">
                                <label for="pkg-instalasi">Biaya Instalasi / Pasang Baru (Rp)</label>
                                <input id="pkg-instalasi" type="number" min="0" step="1000" class="form-control @error('biaya_instalasi') is-invalid @enderror" name="biaya_instalasi" value="{{ old('biaya_instalasi', '0') }}">
                                @error('biaya_instalasi') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field">
                                <label for="pkg-addon">Biaya Add-on Bulanan (Rp)</label>
                                <input id="pkg-addon" type="number" min="0" step="1000" class="form-control @error('biaya_addon') is-invalid @enderror" name="biaya_addon" value="{{ old('biaya_addon', '0') }}" placeholder="Sewa ONT, IP Publik, dll.">
                                @error('biaya_addon') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field">
                                <label for="pkg-diskon-jenis">Skema Diskon</label>
                                <select id="pkg-diskon-jenis" class="form-select" name="diskon_jenis">
                                    <option value="">Tidak ada</option>
                                    <option value="persen" {{ old('diskon_jenis') === 'persen' ? 'selected' : '' }}>Persentase (%)</option>
                                    <option value="nominal" {{ old('diskon_jenis') === 'nominal' ? 'selected' : '' }}>Potongan Nominal (Rp)</option>
                                </select>
                            </div>
                            <div class="pkg-field pkg-diskon-field" style="display: none">
                                <label for="pkg-diskon-value">Nilai Diskon</label>
                                <input id="pkg-diskon-value" type="number" min="0" step="any" class="form-control @error('diskon_value') is-invalid @enderror" name="diskon_value" value="{{ old('diskon_value') }}" placeholder="10 / 50000">
                                @error('diskon_value') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field pkg-diskon-field" style="display: none">
                                <label for="pkg-diskon-sampai">Diskon Berlaku Sampai</label>
                                <input id="pkg-diskon-sampai" type="date" class="form-control @error('diskon_berlaku_sampai') is-invalid @enderror" name="diskon_berlaku_sampai" value="{{ old('diskon_berlaku_sampai') }}">
                                @error('diskon_berlaku_sampai') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </section>

                    <section class="pkg-form-section">
                        <div class="pkg-form-section-head"><i class="bi bi-speedometer2"></i> Kebijakan Pemakaian Adil (FUP)</div>
                        <div class="pkg-form-grid">
                            <div class="pkg-field pkg-field--full">
                                <div class="pkg-check">
                                    <input type="checkbox" id="pkg-fup-enabled" name="fup_enabled" value="1" {{ old('fup_enabled') ? 'checked' : '' }}>
                                    <label for="pkg-fup-enabled">
                                        Aktifkan Batas FUP (Fair Usage Policy)
                                        <small>Turunkan kecepatan atau isolir pelanggan setelah kuota bulanan tercapai.</small>
                                    </label>
                                </div>
                            </div>
                            <div class="pkg-field pkg-fup-field" style="display: none">
                                <label for="pkg-fup-limit">Batas Kuota FUP (GB / bulan)</label>
                                <input id="pkg-fup-limit" type="number" min="1" step="any" class="form-control @error('fup_limit_gb') is-invalid @enderror" name="fup_limit_gb" value="{{ old('fup_limit_gb') }}" placeholder="1000">
                                @error('fup_limit_gb') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field pkg-fup-field" style="display: none">
                                <label for="pkg-fup-aksi">Tindakan saat limit tercapai</label>
                                <select id="pkg-fup-aksi" class="form-select" name="fup_aksi">
                                    <option value="throttle">Turunkan Kecepatan</option>
                                    <option value="isolir">Isolir Sementara</option>
                                    <option value="kuota">Beli Kuota Tambahan</option>
                                </select>
                            </div>
                            <div class="pkg-field pkg-fup-field" style="display: none">
                                <label for="pkg-fup-rx">Kecepatan Throttle Download (Mbps)</label>
                                <input id="pkg-fup-rx" type="number" min="0" step="any" class="form-control @error('fup_throttle_rx') is-invalid @enderror" name="fup_throttle_rx" value="{{ old('fup_throttle_rx') }}" placeholder="10">
                                @error('fup_throttle_rx') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="pkg-field pkg-fup-field" style="display: none">
                                <label for="pkg-fup-tx">Kecepatan Throttle Upload (Mbps)</label>
                                <input id="pkg-fup-tx" type="number" min="0" step="any" class="form-control @error('fup_throttle_tx') is-invalid @enderror" name="fup_throttle_tx" value="{{ old('fup_throttle_tx') }}" placeholder="5">
                                @error('fup_throttle_tx') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </section>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="pkg-btn pkg-btn--ghost" data-bs-dismiss="modal">Batal</button>
                <button type="submit" form="pkgForm" class="pkg-btn pkg-btn--primary">Simpan Paket</button>
            </div>
        </div>
    </div>
</div>
