<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

/**
 * Sumber data CST Internal tahap UI.
 *
 * Data awal dibaca dari resources/data/cst-internal-mock.json. Perubahan
 * (registrasi, edit profil, ubah status) disimpan pada sesi agar halaman
 * dapat diuji end-to-end sebelum tabel relasi CST tersedia.
 *
 * TODO(api): ganti seed JSON + sesi dengan tabel pelanggan, langganan,
 * status_logs, tiket, dan isolasi_logs saat skema siap.
 */
class CstInternalRepository
{
    public const STATUSES = ['Pending', 'Aktif', 'Menunggak', 'Isolir', 'Suspend', 'Berhenti'];

    private const SESSION_CREATED = 'cst_internal.created';

    private const SESSION_OVERRIDES = 'cst_internal.overrides';

    public function all(): Collection
    {
        $created = collect(Session::get(self::SESSION_CREATED, []));
        $overrides = Session::get(self::SESSION_OVERRIDES, []);

        return collect($this->seed())
            ->concat($created)
            ->map(function (array $record) use ($overrides): array {
                $override = $overrides[$record['id']] ?? [];

                return $override === [] ? $record : array_replace($record, $override);
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filter(array $filters): Collection
    {
        $search = trim((string) ($filters['q'] ?? ''));

        return $this->all()
            ->when($search !== '', fn (Collection $rows) => $rows->filter(function (array $row) use ($search): bool {
                $needle = mb_strtolower($search);

                foreach (['id', 'nama', 'pppoe_username', 'alamat', 'no_hp'] as $field) {
                    if (str_contains(mb_strtolower((string) ($row[$field] ?? '')), $needle)) {
                        return true;
                    }
                }

                return false;
            }))
            ->when(filled($filters['status'] ?? null), fn (Collection $rows) => $rows->where('status', $filters['status']))
            ->when(filled($filters['wilayah'] ?? null), fn (Collection $rows) => $rows->where('wilayah', $filters['wilayah']))
            ->when(filled($filters['paket'] ?? null), fn (Collection $rows) => $rows->where('paket', $filters['paket']))
            ->when(filled($filters['router'] ?? null), fn (Collection $rows) => $rows->where('router', $filters['router']))
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        return $this->all()->firstWhere('id', $id);
    }

    /**
     * @return Collection<int, string>
     */
    public function options(string $field): Collection
    {
        return $this->all()
            ->pluck($field)
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $prefix = 'CST-'.now()->format('Ym').'-';
        $sequence = (int) $this->all()
            ->filter(fn (array $row) => str_starts_with($row['id'], $prefix))
            ->map(fn (array $row) => (int) substr($row['id'], strlen($prefix)))
            ->max() + 1;

        $record = $this->blankRecord($prefix.str_pad((string) max(1, $sequence), 3, '0', STR_PAD_LEFT), $data);

        $created = Session::get(self::SESSION_CREATED, []);
        array_unshift($created, $record);
        Session::put(self::SESSION_CREATED, $created);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(string $id, array $data): void
    {
        $record = $this->find($id);
        if ($record === null) {
            return;
        }

        $defaults = $this->paketDefaults($data['paket'] ?? $record['paket']);

        $this->saveOverride($id, [
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'],
            'email' => $data['email'] ?? null,
            'alamat' => $data['alamat'],
            'wilayah' => $data['wilayah'],
            'paket' => $data['paket'],
            'router' => $data['router'],
            'router_ip' => $this->routerIp($data['router']),
            'pppoe_username' => $data['pppoe_username'],
            'catatan' => $data['catatan'] ?? null,
            'bandwidth' => $defaults['bandwidth'],
            'profile' => $defaults['profile'],
            'rate_limit' => $defaults['rate_limit'],
        ]);
    }

    public function updateStatus(string $id, string $status, string $reason): void
    {
        $record = $this->find($id);
        if ($record === null || $record['status'] === $status) {
            return;
        }

        $logs = $record['status_logs'] ?? [];
        array_unshift($logs, [
            'at' => now()->format('Y-m-d H:i'),
            'from' => $record['status'],
            'to' => $status,
            'by' => auth()->user()?->name ?? 'Sistem',
            'note' => $reason,
        ]);

        $this->saveOverride($id, [
            'status' => $status,
            'status_since' => now()->toDateString(),
            'status_logs' => $logs,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function blankRecord(string $id, array $data): array
    {
        $defaults = $this->paketDefaults($data['paket']);

        return [
            'id' => $id,
            'nama' => $data['nama'],
            'no_hp' => $data['no_hp'],
            'email' => $data['email'] ?? null,
            'alamat' => $data['alamat'],
            'wilayah' => $data['wilayah'],
            'cluster' => 'Belum ditentukan',
            'odp' => 'Belum ditentukan',
            'latitude' => null,
            'longitude' => null,
            'catatan' => $data['catatan'] ?? null,
            'status' => 'Pending',
            'status_since' => now()->toDateString(),
            'pppoe_username' => $data['pppoe_username'],
            'pppoe_password' => 'Knt#'.now()->format('Ymd'),
            'paket' => $data['paket'],
            'bandwidth' => $defaults['bandwidth'],
            'profile' => $defaults['profile'],
            'router' => $data['router'],
            'router_ip' => $this->routerIp($data['router']),
            'rate_limit' => $defaults['rate_limit'],
            'queue' => 'queue-'.str_replace('.', '-', $data['pppoe_username']),
            'tanggal_aktivasi' => null,
            'jatuh_tempo' => now()->addMonthNoOverflow()->day(10)->toDateString(),
            'mitra' => 'Internal',
            'status_logs' => [[
                'at' => now()->format('Y-m-d H:i'),
                'from' => 'Baru',
                'to' => 'Pending',
                'by' => auth()->user()?->name ?? 'Sistem',
                'note' => 'Registrasi CST baru.',
            ]],
            'invoices' => [],
            'payments' => [],
            'package_changes' => [],
            'isolation_logs' => [],
            'tickets' => [],
        ];
    }

    /**
     * @return array{bandwidth: string, profile: string, rate_limit: string}
     */
    private function paketDefaults(string $paket): array
    {
        return match ($paket) {
            'KiNet Home 10' => ['bandwidth' => '10 Mbps / 3 Mbps', 'profile' => 'home-10m', 'rate_limit' => '10M/3M'],
            'KiNet Home 30' => ['bandwidth' => '30 Mbps / 10 Mbps', 'profile' => 'home-30m', 'rate_limit' => '30M/10M'],
            'KiNet Bisnis 30' => ['bandwidth' => '30 Mbps / 15 Mbps', 'profile' => 'biz-30m', 'rate_limit' => '30M/15M'],
            'KiNet Bisnis 50' => ['bandwidth' => '50 Mbps / 25 Mbps', 'profile' => 'biz-50m', 'rate_limit' => '50M/25M'],
            'KiNet Bisnis 100' => ['bandwidth' => '100 Mbps / 50 Mbps', 'profile' => 'biz-100m', 'rate_limit' => '100M/50M'],
            default => ['bandwidth' => '20 Mbps / 5 Mbps', 'profile' => 'home-20m', 'rate_limit' => '20M/5M'],
        };
    }

    private function routerIp(string $router): string
    {
        return match ($router) {
            'Router Binjai' => '10.10.10.1',
            'Router Deli Serdang' => '10.10.20.1',
            'Router Tebing Tinggi' => '10.10.30.1',
            default => '10.10.0.1',
        };
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function saveOverride(string $id, array $values): void
    {
        $overrides = Session::get(self::SESSION_OVERRIDES, []);
        $overrides[$id] = array_replace($overrides[$id] ?? [], $values);
        Session::put(self::SESSION_OVERRIDES, $overrides);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function seed(): array
    {
        $path = resource_path('data/cst-internal-mock.json');

        if (! File::exists($path)) {
            return [];
        }

        return File::json($path)['customers'] ?? [];
    }
}
