@props(['customers', 'paginator' => null, 'perPage' => 10, 'sort' => 'id', 'direction' => 'asc'])
@php
    // Urutan header: field sortable -> label. 'kontak' statis (tidak bisa disortir).
    $sortable = [
        'id' => 'Nomor CST',
        'nama' => 'Nama Pelanggan',
        'kontak' => 'No HP',
        'wilayah' => 'Wilayah',
        'paket' => 'Paket & Router',
        'pppoe_username' => 'PPPoE',
        'status' => 'Status',
    ];
    $sortableFields = ['id', 'nama', 'wilayah', 'paket', 'pppoe_username', 'status'];
@endphp

<section class="cst-card cst-table-card" aria-labelledby="cst-table-heading">
    <header class="cst-table-head">
        <div>
            <h3 id="cst-table-heading">Daftar Pelanggan</h3>
            <p>Nomor CST, layanan aktif, dan status penanganan pelanggan.</p>
        </div>
        <span class="cst-source-chip"><i class="bi bi-hdd-stack" aria-hidden="true"></i> Data demo JSON</span>
    </header>

    <div class="table-responsive cst-table-scroll">
        <table class="table cst-table cst-table-fixed">
            <colgroup>
                <col class="cst-col-id" />
                <col class="cst-col-nama" />
                <col class="cst-col-kontak" />
                <col class="cst-col-wilayah" />
                <col class="cst-col-paket" />
                <col class="cst-col-pppoe" />
                <col class="cst-col-status" />
            </colgroup>
            <thead>
                <tr>
                    @foreach ($sortable as $field => $label)
                        @if (! in_array($field, $sortableFields, true))
                            <th scope="col">{{ $label }}</th>
                            @continue
                        @endif
                        @php
                            $isActive = $sort === $field;
                            $nextDir = $isActive && $direction === 'asc' ? 'desc' : 'asc';
                            $sortQuery = array_merge(request()->query(), ['sort' => $field, 'direction' => $nextDir, 'page' => 1]);
                        @endphp
                        <th scope="col" class="@if ($field === 'id') cst-sticky-col @endif">
                            <a class="cst-sort-link{{ $isActive ? ' cst-sort-link--active' : '' }}"
                               href="{{ route('admin.cst-internal.index', $sortQuery) }}"
                               title="Urutkan berdasarkan {{ $label }}">{{ $label }}
                                @if ($isActive)
                                    <i class="bi {{ $direction === 'asc' ? 'bi-caret-up-fill' : 'bi-caret-down-fill' }}" aria-hidden="true"></i>
                                @endif
                            </a>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    @php
                        $words = preg_split('/\s+/', trim((string) $customer['nama'])) ?: [];
                        $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1).mb_substr($words[1] ?? '', 0, 1));
                        $avatarTone = strtolower((string) ($words[0] ?? 'a')[0]);
                        $avatarTone = in_array($avatarTone, ['a', 'b', 'c', 'd', 'e'], true) ? $avatarTone : 'a';
                        $wa = preg_replace('/^0/', '62', preg_replace('/\D/', '', (string) $customer['no_hp']));
                    @endphp
                    <tr>
                        <td class="cst-sticky-col">
                            <a class="cst-code" href="{{ route('admin.cst-internal.show', $customer['id']) }}">{{ $customer['id'] }}</a>
                            <span class="cst-sub">{{ $customer['tanggal_aktivasi'] ? 'Aktif sejak '.$customer['tanggal_aktivasi'] : 'Belum aktivasi' }}</span>
                        </td>
                        <td>
                            <div class="cst-person">
                                <span class="cst-avatar cst-avatar--{{ $avatarTone }}" aria-hidden="true">{{ $initials }}</span>
                                <div class="cst-person-info">
                                    <strong title="{{ $customer['nama'] }}">{{ $customer['nama'] }}</strong>
                                    <span class="cst-sub cst-sub--person">CST-202611-{{ str_pad(substr((string) $customer['id'], -3), 3, '0', STR_PAD_LEFT) }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="cst-contact">
                                <a class="cst-wa-link" href="https://wa.me/{{ $wa }}" target="_blank" rel="noopener" title="Chat WhatsApp {{ $customer['nama'] }}">
                                    <svg class="cst-wa-icon" viewBox="0 0 32 32" aria-hidden="true" width="14" height="14"><path fill="#25D366" d="M16 .8C7.6.8.8 7.6.8 16c0 2.7.7 5.3 2 7.6L.8 31.2l7.8-2c2.2 1.2 4.7 1.9 7.4 1.9 8.4 0 15.2-6.8 15.2-15.2S24.4.8 16 .8zm0 27.7c-2.4 0-4.7-.7-6.7-1.9l-.5-.3-4.6 1.2 1.2-4.5-.3-.5c-1.3-2-2-4.4-2-6.8C3.1 8.9 8.9 3.1 16 3.1S28.9 8.9 28.9 16 23.1 28.5 16 28.5zm6.9-9.4c-.4-.2-2.2-1.1-2.6-1.2-.3-.1-.6-.2-.8.2-.3.4-1 1.2-1.2 1.5-.2.2-.4.3-.8.1-.4-.2-1.6-.6-3.1-1.9-1.1-1-1.9-2.2-2.1-2.6-.2-.4 0-.6.2-.8l.6-.7c.2-.2.2-.4.4-.6.1-.3 0-.5 0-.7-.1-.2-.8-2-.9-2.3-.1-.3-.4-.3-.6-.3h-.6c-.2 0-.6.1-.9.4-.3.4-1.2 1.2-1.2 2.9s1.2 3.3 1.4 3.6c.2.3 2.4 3.7 5.8 5.2.8.4 1.4.6 1.9.8.8.3 1.5.2 2.1.1.6-.1 1.9-.8 2.2-1.5.3-.7.3-1.3.2-1.5-.1-.1-.3-.2-.7-.4z"/></svg>
                                    {{ $customer['no_hp'] }}
                                </a>
                            </span>
                            <span class="cst-sub">{{ $customer['email'] ?: 'Tanpa email' }}</span>
                        </td>
                        <td>
                            <span class="cst-cell-strong">{{ $customer['wilayah'] }}</span>
                            <span class="cst-sub">{{ $customer['cluster'] }} · {{ $customer['odp'] }}</span>
                        </td>
                        <td>
                            <span class="cst-cell-strong">{{ $customer['paket'] }}</span>
                            <span class="cst-sub">{{ $customer['bandwidth'] }} · {{ $customer['router'] }}</span>
                        </td>
                        <td>
                            <code class="cst-code-chip">{{ $customer['pppoe_username'] }}</code>
                        </td>
                        <td>
                            <x-cst-internal.status-badge :status="$customer['status']" />
                            <span class="cst-sub">Sejak {{ $customer['status_since'] }}</span>
                        </td>
                    </tr>
                @empty
                    <tr class="cst-empty-row">
                        <td colspan="7">
                            <i class="bi bi-search" aria-hidden="true"></i>
                            <strong>Tidak ada pelanggan yang cocok</strong>
                            Coba ubah kata kunci atau reset filter.
                            <a href="{{ route('admin.cst-internal.index') }}">Reset filter</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($paginator !== null)
        <div class="cst-pagination">
            <div class="cst-pagination-info">
                <span>Menampilkan
                    <strong>{{ $paginator->total() === 0 ? 0 : $paginator->firstItem() }} – {{ $paginator->lastItem() }}</strong>
                    dari <strong>{{ $paginator->total() }}</strong> pelanggan
                </span>
                <label class="cst-per-page" for="cstPerPage">
                    Per halaman
                    <select id="cstPerPage" name="per_page" data-cst-per-page>
                        @foreach ([5, 10, 25, 50] as $option)
                            <option value="{{ $option }}" @selected($option === $perPage)>{{ $option }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <nav aria-label="Navigasi halaman pelanggan" class="cst-pagination-nav">
                {{ $paginator->withQueryString()->links('pagination::bootstrap-5') }}
            </nav>
        </div>
    @endif
</section>
