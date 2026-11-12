<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

/**
 * Sumber data Billing & Invoice tahap UI.
 *
 * Data awal dibaca dari resources/data/billing-mock.json. Perubahan
 * (invoice manual, generate massal, pembayaran, void) disimpan pada
 * sesi agar alur penagihan dapat diuji end-to-end di browser.
 *
 * TODO(db): ganti seed JSON + sesi dengan tabel invoices saat skema siap.
 */
class BillingInvoiceRepository
{
    public const STATUSES = ['draft', 'unpaid', 'pending', 'paid', 'overdue', 'void'];

    public const LABEL_STATUS = [
        'draft' => 'Draft',
        'unpaid' => 'Unpaid',
        'pending' => 'Pending',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'void' => 'Void / Refund',
    ];

    public const TIPE_LABEL = [
        'bulanan' => 'Bulanan',
        'psb' => 'Pasang Baru (PSB)',
        'perangkat' => 'Perangkat',
        'tambahan' => 'Biaya Tambahan',
    ];

    private const SESSION_OVERRIDES = 'billing.overrides';

    private const SESSION_DELETED = 'billing.deleted';

    private const SESSION_SEQ = 'billing.seq';

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function all(): Collection
    {
        $overrides = Session::get(self::SESSION_OVERRIDES, []);
        $deleted = Session::get(self::SESSION_DELETED, []);

        $seeded = collect($this->seed())
            ->reject(fn (array $row) => in_array($row['no_invoice'], $deleted, true))
            ->map(function (array $row) use ($overrides): array {
                $override = $overrides[$row['no_invoice']] ?? [];

                return $override === [] ? $row : array_replace($row, $override);
            });

        $created = collect($overrides)
            ->reject(fn (array $row, string $key) => $seeded->contains(fn ($r) => $r['no_invoice'] === $key))
            ->values();

        return $seeded->concat($created)
            ->sortByDesc('no_invoice')
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function filter(array $filters): Collection
    {
        $search = mb_strtolower(trim((string) ($filters['q'] ?? '')));

        return $this->all()
            ->filter(fn (array $row) => $this->effectiveStatus($row) !== null || true)
            ->when($search !== '', fn (Collection $rows) => $rows->filter(function (array $row) use ($search): bool {
                foreach (['no_invoice', 'no_cst', 'nama', 'no_hp'] as $field) {
                    if (str_contains(mb_strtolower((string) ($row[$field] ?? '')), $search)) {
                        return true;
                    }
                }

                return false;
            }))
            ->when(filled($filters['status'] ?? null), fn (Collection $rows) => $rows->filter(
                fn (array $row) => $this->effectiveStatus($row) === $filters['status']
            ))
            ->when(filled($filters['tipe'] ?? null), fn (Collection $rows) => $rows->where('tipe', $filters['tipe']))
            ->when(filled($filters['periode'] ?? null), fn (Collection $rows) => $rows->where('periode', $filters['periode']))
            ->when(filled($filters['dari'] ?? null), fn (Collection $rows) => $rows->filter(
                fn (array $row) => $row['tanggal_terbit'] >= $filters['dari']
            ))
            ->when(filled($filters['sampai'] ?? null), fn (Collection $rows) => $rows->filter(
                fn (array $row) => $row['tanggal_terbit'] <= $filters['sampai']
            ))
            ->sortByDesc('no_invoice')
            ->values();
    }

    /**
     * Status efektif: unpaid dengan due date terlewat otomatis overdue.
     *
     * @param  array<string, mixed>  $row
     */
    public function effectiveStatus(array $row): string
    {
        if (in_array($row['status'], ['paid', 'void', 'pending', 'draft'], true)) {
            return $row['status'];
        }

        if ($row['status'] === 'unpaid' && $row['tanggal_jatuh_tempo'] < now()->toDateString()) {
            return 'overdue';
        }

        return $row['status'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $noInvoice): ?array
    {
        return $this->all()->firstWhere('no_invoice', $noInvoice);
    }

    /**
     * Statistik ringkas untuk metric tiles + rekap piutang.
     *
     * @return array<string, mixed>
     */
    public function stats(): array
    {
        $all = $this->all();
        $active = $all->filter(fn (array $row) => in_array($this->effectiveStatus($row), ['unpaid', 'overdue'], true));
        $thisMonth = $all->filter(fn (array $row) => str_starts_with((string) $row['tanggal_terbit'], now()->format('Y-m')));
        $paid = $all->filter(fn (array $row) => $this->effectiveStatus($row) === 'paid');
        $issuedCount = max(1, $all->where('status', '!=', 'draft')->count());

        $receivable = $active->sum(fn (array $row) => $row['total']);

        $aging = ['lt30' => 0, 'b3060' => 0, 'gt60' => 0];
        foreach ($active->all() as $row) {
            $days = now()->diffInDays(now()->parse($row['tanggal_jatuh_tempo']), false) * -1;
            if ($days <= 30) {
                $aging['lt30'] += $row['total'];
            } elseif ($days <= 60) {
                $aging['b3060'] += $row['total'];
            } else {
                $aging['gt60'] += $row['total'];
            }
        }

        $perWilayah = $active
            ->groupBy(fn (array $row) => $row['wilayah'] ?? '-')
            ->map(fn (Collection $rows) => [
                'total' => $rows->sum('total'),
                'count' => $rows->count(),
            ])
            ->sortKeys();

        return [
            'piutang_total' => $receivable,
            'piutang_count' => $active->count(),
            'month_total' => $thisMonth->sum('total'),
            'month_count' => $thisMonth->count(),
            'paid_total' => $paid->sum('total'),
            'paid_count' => $paid->count(),
            'paid_ratio' => $all->count() > 0 ? round($all->count() > 0 ? ($paid->count() / $issuedCount) * 100 : 0) : 0,
            'overdue_total' => $active->filter(fn ($r) => $this->effectiveStatus($r) === 'overdue')->sum('total'),
            'overdue_count' => $active->filter(fn ($r) => $this->effectiveStatus($r) === 'overdue')->count(),
            'pending_total' => $all->filter(fn ($r) => $r['status'] === 'pending')->sum('total'),
            'pending_count' => $all->filter(fn ($r) => $r['status'] === 'pending')->count(),
            'aging' => $aging,
            'per_wilayah' => $perWilayah,
        ];
    }

    /**
     * Log mutasi pembayaran terbaru (status paid), terbaru dulu.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function recentPayments(int $limit = 10): Collection
    {
        return $this->all()
            ->filter(fn (array $row) => $row['status'] === 'paid' && filled($row['tanggal_bayar']))
            ->sortByDesc('tanggal_bayar')
            ->take($limit)
            ->values();
    }

    /**
     * Preview target generate massal: jumlah lembar & proyeksi nominal.
     *
     * @return array{count: int, total: float, targets: Collection<int, array<string, mixed>>}
     */
    public function previewBatch(string $periode, string $targetRouter, bool $prorata): array
    {
        $targets = $this->batchTargets($periode, $targetRouter, $prorata);
        $total = $targets->sum(fn (array $row) => $row['total']);

        return ['count' => $targets->count(), 'total' => $total, 'targets' => $targets];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function batchTargets(string $periode, string $targetRouter, bool $prorata): Collection
    {
        $customers = $this->activeCustomers()
            ->when($targetRouter !== 'all', fn (Collection $rows) => $rows->where('router', $targetRouter));

        $existing = $this->all()
            ->filter(fn (array $row) => $row['tipe'] === 'bulanan')
            ->map(fn (array $row) => $row['no_cst'].'@'.$row['periode'])
            ->flip();

        [$year, $month] = array_map('intval', explode('-', $periode));
        $daysInMonth = now()->setDate($year, $month, 1)->daysInMonth;

        return $customers
            ->reject(fn (array $cst) => $existing->has($cst['id'].'@'.$periode))
            ->map(function (array $cst) use ($periode, $prorata, $year, $month, $daysInMonth): array {
                $harga = $this->paketHarga($cst['paket']);
                $prorataNote = null;

                if ($prorata && filled($cst['tanggal_aktivasi'] ?? null)) {
                    $aktivasi = now()->setDate($year, $month, 1)->parse($cst['tanggal_aktivasi']);
                    $firstOfPeriod = now()->setDate($year, $month, 1);

                    if ($aktivasi->gt($firstOfPeriod)) {
                        $daysLeft = $daysInMonth - $aktivasi->day + 1;
                        $harga = (int) floor($daysLeft / $daysInMonth * $harga);
                        $prorataNote = "Prorata: {$daysLeft}/{$daysInMonth} hari";
                    }
                }

                return [
                    'no_cst' => $cst['id'],
                    'nama' => $cst['nama'],
                    'no_hp' => $cst['no_hp'],
                    'paket' => $cst['paket'],
                    'total' => $harga,
                    'prorata_note' => $prorataNote,
                    'cst' => $cst,
                ];
            })->values();
    }

    /**
     * Generate tagihan massal untuk satu periode.
     *
     * @return array{created: int, total: float}
     */
    public function generateBatch(string $periode, string $tanggalTerbit, string $tanggalJatuhTempo, string $targetRouter, bool $prorata): array
    {
        $targets = $this->batchTargets($periode, $targetRouter, $prorata);
        $created = 0;
        $total = 0.0;

        foreach ($targets as $target) {
            $cst = $target['cst'];
            $noInvoice = $this->nextInvoiceNumber();
            $this->saveOverride($noInvoice, [
                'no_invoice' => $noInvoice,
                'no_cst' => $cst['id'],
                'nama' => $cst['nama'],
                'no_hp' => $cst['no_hp'],
                'tipe' => 'bulanan',
                'paket' => $cst['paket'],
                'periode' => $periode,
                'tanggal_terbit' => $tanggalTerbit,
                'tanggal_jatuh_tempo' => $tanggalJatuhTempo,
                'items' => [[
                    'nama' => 'Langganan Internet '.$cst['paket'].' - '.now()->parse($periode.'-01')->translatedFormat('F Y'),
                    'qty' => 1,
                    'harga' => $target['total'],
                ]],
                'subtotal' => $target['total'],
                'diskon' => 0,
                'biaya_admin' => 0,
                'ppn' => 0,
                'total' => $target['total'],
                'status' => 'unpaid',
                'metode_pembayaran' => null,
                'tanggal_bayar' => null,
                'pppoe_username' => $cst['pppoe_username'],
                'router' => $cst['router'],
                'wilayah' => $cst['wilayah'],
                'odp' => $cst['odp'],
                'audit_log' => [[
                    'waktu' => now()->format('Y-m-d H:i'),
                    'aksi' => 'created',
                    'catatan' => 'Generate massal periode '.$periode.($target['prorata_note'] ? ' ('.$target['prorata_note'].')' : ''),
                ]],
            ], isCreate: true);
            $created++;
            $total += $target['total'];
        }

        return ['created' => $created, 'total' => $total];
    }

    /**
     * Buat invoice manual multi-item.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createManual(array $data): array
    {
        $items = array_values(array_filter($data['items'], fn (array $item) => $item['nama'] !== '' && (float) $item['harga'] > 0));
        $subtotal = 0.0;
        foreach ($items as $item) {
            $subtotal += (float) $item['harga'] * (int) $item['qty'];
        }

        $diskon = (float) ($data['diskon'] ?? 0);
        $biayaAdmin = (float) ($data['biaya_admin'] ?? 0);
        $ppn = $data['ppn_enabled'] ? floor($subtotal * 0.11) : 0;
        $total = max(0, $subtotal - $diskon + $biayaAdmin + $ppn);

        $cst = $this->customerById($data['no_cst']);

        $record = [
            'no_invoice' => $this->nextInvoiceNumber(),
            'no_cst' => $data['no_cst'],
            'nama' => $cst['nama'] ?? $data['no_cst'],
            'no_hp' => $cst['no_hp'] ?? '',
            'tipe' => $data['tipe'],
            'paket' => $cst['paket'] ?? '',
            'periode' => $data['periode'] ?? now()->format('Y-m'),
            'tanggal_terbit' => $data['tanggal_terbit'],
            'tanggal_jatuh_tempo' => $data['tanggal_jatuh_tempo'],
            'items' => $items,
            'subtotal' => $subtotal,
            'diskon' => $diskon,
            'biaya_admin' => $biayaAdmin,
            'ppn' => $ppn,
            'total' => $total,
            'status' => 'unpaid',
            'metode_pembayaran' => null,
            'tanggal_bayar' => null,
            'pppoe_username' => $cst['pppoe_username'] ?? null,
            'router' => $cst['router'] ?? null,
            'wilayah' => $cst['wilayah'] ?? null,
            'odp' => $cst['odp'] ?? null,
            'audit_log' => [[
                'waktu' => now()->format('Y-m-d H:i'),
                'aksi' => 'created',
                'catatan' => 'Invoice manual dibuat oleh admin',
            ]],
        ];

        $this->saveOverride($record['no_invoice'], $record, isCreate: true);

        return $record;
    }

    /**
     * Catat pelunasan pembayaran.
     *
     * @return array<string, mixed>|null
     */
    public function recordPayment(string $noInvoice, string $metode, ?string $catatan = null): ?array
    {
        $record = $this->find($noInvoice);
        if ($record === null || $record['status'] === 'paid') {
            return null;
        }

        $record['status'] = 'paid';
        $record['metode_pembayaran'] = $metode;
        $record['tanggal_bayar'] = now()->toDateString();
        $record['audit_log'] = array_merge($record['audit_log'] ?? [], [[
            'waktu' => now()->format('Y-m-d H:i'),
            'aksi' => 'paid',
            'catatan' => 'Pembayaran dicatat ('.$metode.')'.($catatan ? ' - '.$catatan : ''),
        ]]);

        $this->saveOverride($noInvoice, $record);

        return $record;
    }

    /**
     * Tandai invoice sebagai pending verifikasi.
     *
     * @return array<string, mixed>|null
     */
    public function markPending(string $noInvoice): ?array
    {
        $record = $this->find($noInvoice);
        if ($record === null) {
            return null;
        }

        $record['status'] = 'pending';
        $record['audit_log'] = array_merge($record['audit_log'] ?? [], [[
            'waktu' => now()->format('Y-m-d H:i'),
            'aksi' => 'pending',
            'catatan' => 'Menunggu konfirmasi pembayaran',
        ]]);

        $this->saveOverride($noInvoice, $record);

        return $record;
    }

    /**
     * Batalkan / void invoice.
     *
     * @return array<string, mixed>|null
     */
    public function void(string $noInvoice, ?string $alasan = null): ?array
    {
        $record = $this->find($noInvoice);
        if ($record === null || $record['status'] === 'paid') {
            return null;
        }

        $record['status'] = 'void';
        $record['audit_log'] = array_merge($record['audit_log'] ?? [], [[
            'waktu' => now()->format('Y-m-d H:i'),
            'aksi' => 'void',
            'catatan' => 'Invoice dibatalkan'.($alasan ? ': '.$alasan : ''),
        ]]);

        $this->saveOverride($noInvoice, $record);

        return $record;
    }

    /**
     * Tandai invoice terkirim via WhatsApp.
     *
     * @return array<string, mixed>|null
     */
    public function markWaSent(string $noInvoice, string $template): ?array
    {
        $record = $this->find($noInvoice);
        if ($record === null) {
            return null;
        }

        $record['audit_log'] = array_merge($record['audit_log'] ?? [], [[
            'waktu' => now()->format('Y-m-d H:i'),
            'aksi' => 'sent_wa',
            'catatan' => 'Pengingat dikirim via WhatsApp ('.$template.')',
        ]]);

        $this->saveOverride($noInvoice, $record);

        return $record;
    }

    public function nextInvoiceNumber(): string
    {
        $seq = (int) Session::get(self::SESSION_SEQ, 92);

        do {
            $seq++;
            $candidate = 'INV-'.now()->format('Ym').'-'.str_pad((string) $seq, '4', '0', STR_PAD_LEFT);
        } while ($this->find($candidate) !== null);

        Session::put(self::SESSION_SEQ, $seq);

        return $candidate;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function customers(): Collection
    {
        return $this->activeCustomers();
    }

    public function customerById(string $noCst): ?array
    {
        return $this->allCustomers()->firstWhere('id', $noCst);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function routers(): Collection
    {
        return $this->allCustomers()
            ->pluck('router')
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function activeCustomers(): Collection
    {
        return $this->allCustomers()->filter(fn (array $row) => ($row['status'] ?? '') === 'Aktif');
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function allCustomers(): Collection
    {
        $path = resource_path('data/cst-internal-mock.json');

        if (! File::exists($path)) {
            return collect();
        }

        return collect(File::json($path)['customers'] ?? []);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function seed(): array
    {
        $path = resource_path('data/billing-mock.json');

        if (! File::exists($path)) {
            return [];
        }

        return File::json($path);
    }

    private function paketHarga(string $paket): float
    {
        $prices = [
            'KiNet Home 10' => 125000,
            'KiNet Home 20' => 150000,
            'KiNet Home 30' => 200000,
            'KiNet Bisnis 50' => 450000,
            'KiNet Bisnis 100' => 750000,
        ];

        return $prices[$paket] ?? 150000;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function saveOverride(string $noInvoice, array $values, bool $isCreate = false): void
    {
        if ($isCreate) {
            $overrides = Session::get(self::SESSION_OVERRIDES, []);
            $overrides[$noInvoice] = $values;
            Session::put(self::SESSION_OVERRIDES, $overrides);

            return;
        }

        $overrides = Session::get(self::SESSION_OVERRIDES, []);
        $overrides[$noInvoice] = array_replace($overrides[$noInvoice] ?? [], $values);
        Session::put(self::SESSION_OVERRIDES, $overrides);
    }
}
