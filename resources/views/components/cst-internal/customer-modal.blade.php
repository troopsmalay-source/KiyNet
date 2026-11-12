@props([
    'mode' => 'create',
    'customer' => null,
    'wilayahs' => [],
    'pakets' => [],
    'routers' => [],
])

@php
    $editing = $mode === 'edit';
    $modalId = $editing ? 'cstEditModal' : 'cstCreateModal';
    $record = $customer ?? [];
    $wilayahOptions = collect($wilayahs)->push($record['wilayah'] ?? null)->filter()->unique()->sort()->values();
    $paketOptions = collect($pakets)->push($record['paket'] ?? null)->filter()->unique()->sort()->values();
    $routerOptions = collect($routers)->push($record['router'] ?? null)->filter()->unique()->sort()->values();
    $action = $editing
        ? (filled($record['id'] ?? null) ? route('admin.cst-internal.update', $record['id']) : '')
        : route('admin.cst-internal.store');
    $formId = $editing ? 'cstEditForm' : 'cstCreateForm';
@endphp

<div class="modal fade cst-modal" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <form class="modal-content" id="{{ $formId }}" method="POST" action="{{ $action }}">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <input type="hidden" name="_form" value="{{ $editing ? 'edit' : 'create' }}">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="{{ $modalId }}Label">{{ $editing ? 'Edit Data Pelanggan' : 'Registrasi CST Baru' }}</h5>
                    <p class="cst-modal-sub" data-cst-edit-subject>
                        @if ($editing && filled($record['id'] ?? null))
                            {{ $record['id'] }} · {{ $record['nama'] }}
                        @elseif ($editing)
                            Pilih pelanggan dari tabel untuk mengisi formulir.
                        @else
                            Nomor CST akan digenerate otomatis setelah data disimpan.
                        @endif
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body">
                @if ($errors->any() && $errors->first() && old('_form') === ($editing ? 'edit' : 'create'))
                    <div class="cst-form-error" role="alert">
                        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                        <span>Periksa kembali isian berikut: {{ $errors->first() }}</span>
                    </div>
                @endif
                @unless ($editing)
                    <p class="cst-modal-note">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                        Formulir demo: data disimpan pada sesi browser dan belum masuk ke database.
                    </p>
                @endunless

                <div class="cst-form-grid">
                    @if ($editing)
                        <div class="cst-field cst-field--full">
                            <label for="{{ $formId }}-id">Nomor Pelanggan (otomatis)</label>
                            <input type="text" class="form-control" id="{{ $formId }}-id" value="{{ $record['id'] ?? 'Digenerate otomatis' }}" readonly>
                        </div>
                    @endif

                    <div class="cst-field">
                        <label for="{{ $formId }}-nama">Nama Lengkap <em>*</em></label>
                        <input type="text" class="form-control" id="{{ $formId }}-nama" name="nama" value="{{ old('nama', $record['nama'] ?? '') }}" required maxlength="150" autocomplete="off">
                    </div>
                    <div class="cst-field">
                        <label for="{{ $formId }}-hp">No. HP / WhatsApp <em>*</em></label>
                        <input type="text" class="form-control" id="{{ $formId }}-hp" name="no_hp" value="{{ old('no_hp', $record['no_hp'] ?? '') }}" required maxlength="25" inputmode="tel" autocomplete="off">
                    </div>
                    <div class="cst-field cst-field--full">
                        <label for="{{ $formId }}-email">Email</label>
                        <input type="email" class="form-control" id="{{ $formId }}-email" name="email" value="{{ old('email', $record['email'] ?? '') }}" maxlength="255" autocomplete="off">
                    </div>
                    <div class="cst-field cst-field--full">
                        <label for="{{ $formId }}-alamat">Alamat Pemasangan <em>*</em></label>
                        <textarea class="form-control" id="{{ $formId }}-alamat" name="alamat" rows="2" required maxlength="500">{{ old('alamat', $record['alamat'] ?? '') }}</textarea>
                    </div>
                    <div class="cst-field">
                        <label for="{{ $formId }}-wilayah">Wilayah Layanan <em>*</em></label>
                        <select class="form-select" id="{{ $formId }}-wilayah" name="wilayah" required>
                            <option value="">Pilih wilayah</option>
                            @foreach ($wilayahOptions as $wilayah)
                                <option value="{{ $wilayah }}" @selected(old('wilayah', $record['wilayah'] ?? '') === $wilayah)>{{ $wilayah }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cst-field">
                        <label for="{{ $formId }}-paket">Paket Internet <em>*</em></label>
                        <select class="form-select" id="{{ $formId }}-paket" name="paket" required>
                            <option value="">Pilih paket</option>
                            @foreach ($paketOptions as $paket)
                                <option value="{{ $paket }}" @selected(old('paket', $record['paket'] ?? '') === $paket)>{{ $paket }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cst-field">
                        <label for="{{ $formId }}-router">Router Gateway <em>*</em></label>
                        <select class="form-select" id="{{ $formId }}-router" name="router" required>
                            <option value="">Pilih router</option>
                            @foreach ($routerOptions as $router)
                                <option value="{{ $router }}" @selected(old('router', $record['router'] ?? '') === $router)>{{ $router }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="cst-field">
                        <label for="{{ $formId }}-pppoe">Username PPPoE <em>*</em></label>
                        <input type="text" class="form-control" id="{{ $formId }}-pppoe" name="pppoe_username" value="{{ old('pppoe_username', $record['pppoe_username'] ?? '') }}" required maxlength="100" pattern="[A-Za-z0-9._-]+" autocomplete="off">
                    </div>
                    <div class="cst-field cst-field--full">
                        <label for="{{ $formId }}-catatan">Catatan Khusus</label>
                        <textarea class="form-control" id="{{ $formId }}-catatan" name="catatan" rows="2" maxlength="500" placeholder="Contoh: koordinasi jadwal instalasi, kebutuhan khusus, atau informasi kontak.">{{ old('catatan', $record['catatan'] ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="cst-btn cst-btn--ghost" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="cst-btn cst-btn--primary">
                    <i class="bi bi-check-lg" aria-hidden="true"></i> {{ $editing ? 'Simpan Perubahan' : 'Simpan & Generate CST' }}
                </button>
            </div>
        </form>
    </div>
</div>
