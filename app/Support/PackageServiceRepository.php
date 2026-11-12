<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

/**
 * Sumber data Paket & Layanan tahap UI.
 *
 * Data awal dibaca dari resources/data/packages-mock.json. Perubahan
 * (tambah, edit, toggle, hapus) disimpan pada sesi agar halaman dapat
 * diuji end-to-end sebelum tabel paket tersedia.
 *
 * TODO(db): ganti seed JSON + sesi dengan tabel packages saat skema siap.
 */
class PackageServiceRepository
{
    public const KATEGORI = ['retail' => 'Retail Rumah', 'bisnis' => 'Bisnis / SOHO', 'mitra' => 'Mitra Reseller', 'dedicated' => 'Khusus / Dedicated'];

    public const SIKLUS = ['bulanan' => 'Bulanan / 30 Hari', 'prabayar' => 'Pra-bayar', 'pascabayar' => 'Pasca-bayar'];

    public const FUP_AKSI = ['throttle' => 'Turunkan Kecepatan', 'isolir' => 'Isolir Sementara', 'kuota' => 'Beli Kuota Tambahan'];

    public const PROFILES = ['home-10m', 'home-20m', 'home-30m', 'biz-50m', 'biz-100m', 'ded-100m-sym', 'mitra-30m', 'ded-50m-sym', 'biz-200m'];

    private const SESSION_CUSTOM = 'packages.custom';

    private const SESSION_DELETED = 'packages.deleted';

    public function all(): Collection
    {
        $custom = collect(Session::get(self::SESSION_CUSTOM, []));
        $deleted = Session::get(self::SESSION_DELETED, []);

        return collect($this->seed())
            ->concat($custom)
            ->reject(fn (array $row) => in_array($row['id'], $deleted, true))
            ->values();
    }

    public function active(): Collection
    {
        return $this->all()->filter(fn (array $row) => $row['is_active']);
    }

    public function customPackages(): Collection
    {
        return $this->all()->filter(fn (array $row) => $row['is_custom']);
    }

    public function inactive(): Collection
    {
        return $this->all()->filter(fn (array $row) => ! $row['is_active']);
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

                foreach (['nama', 'mikrotik_profile', 'id'] as $field) {
                    if (str_contains(mb_strtolower((string) ($row[$field] ?? '')), $needle)) {
                        return true;
                    }
                }

                return false;
            }))
            ->when(filled($filters['status'] ?? null), function (Collection $rows) use ($filters): Collection {
                return $rows->filter(function (array $row) use ($filters): bool {
                    if ($filters['status'] === 'active') {
                        return $row['is_active'];
                    }

                    return ! $row['is_active'];
                });
            })
            ->when(filled($filters['kategori'] ?? null), fn (Collection $rows) => $rows->where('kategori', $filters['kategori']))
            ->when(filled($filters['profile'] ?? null), fn (Collection $rows) => $rows->where('mikrotik_profile', $filters['profile']))
            ->when(filled($filters['kecepatan'] ?? null), function (Collection $rows) use ($filters): Collection {
                return $rows->filter(function (array $row) use ($filters): bool {
                    return match ($filters['kecepatan']) {
                        '<10' => $row['download_mbps'] < 10,
                        '10-30' => $row['download_mbps'] >= 10 && $row['download_mbps'] <= 30,
                        '31-100' => $row['download_mbps'] > 30 && $row['download_mbps'] <= 100,
                        '>100' => $row['download_mbps'] > 100,
                        default => true,
                    };
                });
            })
            ->values();
    }

    public function find(string $id): ?array
    {
        return $this->all()->firstWhere('id', $id);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $sequence = (int) $this->all()
            ->map(fn (array $row) => (int) ltrim((string) $row['id'], 'PKG-') ?: 0)
            ->max() + 1;

        $record = $this->normalize('PKG-'.str_pad((string) max(1, $sequence), 3, '0', STR_PAD_LEFT), $data);

        $custom = Session::get(self::SESSION_CUSTOM, []);
        array_unshift($custom, $record);
        Session::put(self::SESSION_CUSTOM, $custom);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(string $id, array $data): void
    {
        $custom = collect(Session::get(self::SESSION_CUSTOM, []))->keyBy('id');
        $seeded = collect($this->seed())->keyBy('id');

        $base = $custom->get($id) ?? $seeded->get($id);

        if ($base === null) {
            return;
        }

        $merged = $this->normalize($id, array_replace($base, $data));

        if ($custom->has($id)) {
            $custom[$id] = $merged;
        } else {
            // seed tidak bisa diubah langsung; simpan sebagai custom
            $all = Session::get(self::SESSION_CUSTOM, []);
            array_unshift($all, $merged);
            Session::put(self::SESSION_CUSTOM, $all);
            $custom = null;
        }

        if ($custom !== null) {
            Session::put(self::SESSION_CUSTOM, $custom->values()->all());
        }
    }

    public function toggleStatus(string $id): void
    {
        $record = $this->find($id);
        if ($record === null) {
            return;
        }

        $this->update($id, ['is_active' => ! $record['is_active']]);
    }

    public function destroy(string $id): void
    {
        $deleted = Session::get(self::SESSION_DELETED, []);

        if (! in_array($id, $deleted, true)) {
            $deleted[] = $id;
            Session::put(self::SESSION_DELETED, $deleted);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(string $id, array $data): array
    {
        return [
            'id' => $id,
            'nama' => (string) $data['nama'],
            'kategori' => (string) $data['kategori'],
            'siklus_tagihan' => (string) ($data['siklus_tagihan'] ?? 'bulanan'),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'is_custom' => (bool) ($data['is_custom'] ?? false),
            'custom_pelanggan' => array_values((array) ($data['custom_pelanggan'] ?? [])),
            'download_mbps' => (float) ($data['download_mbps'] ?? 0),
            'upload_mbps' => (float) ($data['upload_mbps'] ?? 0),
            'mikrotik_profile' => (string) ($data['mikrotik_profile'] ?? ''),
            'rate_limit' => (string) ($data['rate_limit'] ?? ''),
            'ip_pool' => (string) ($data['ip_pool'] ?? ''),
            'queue_priority' => (int) ($data['queue_priority'] ?? 5),
            'harga' => (float) ($data['harga'] ?? 0),
            'biaya_instalasi' => (float) ($data['biaya_instalasi'] ?? 0),
            'biaya_addon' => (float) ($data['biaya_addon'] ?? 0),
            'diskon_jenis' => $data['diskon_jenis'] ?? null,
            'diskon_value' => (float) ($data['diskon_value'] ?? 0),
            'diskon_berlaku_sampai' => $data['diskon_berlaku_sampai'] ?? null,
            'fup_enabled' => (bool) ($data['fup_enabled'] ?? false),
            'fup_limit_gb' => isset($data['fup_limit_gb']) && $data['fup_limit_gb'] !== '' ? (float) $data['fup_limit_gb'] : null,
            'fup_throttle_rx' => isset($data['fup_throttle_rx']) && $data['fup_throttle_rx'] !== '' ? (float) $data['fup_throttle_rx'] : null,
            'fup_throttle_tx' => isset($data['fup_throttle_tx']) && $data['fup_throttle_tx'] !== '' ? (float) $data['fup_throttle_tx'] : null,
            'fup_aksi' => $data['fup_aksi'] ?? null,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function seed(): array
    {
        $path = resource_path('data/packages-mock.json');

        if (! File::exists($path)) {
            return [];
        }

        return File::json($path)['packages'] ?? [];
    }
}
