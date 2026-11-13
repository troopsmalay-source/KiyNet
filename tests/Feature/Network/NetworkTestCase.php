<?php

namespace Tests\Feature\Network;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Local test harness for Agent B.
 *
 * The repository's phpunit.xml targets PostgreSQL and config/database.php opens
 * SQLite read-only, which this machine cannot run. These tests therefore use a
 * dedicated writable SQLite connection per test and run the real migrations.
 */
abstract class NetworkTestCase extends TestCase
{
    protected string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databasePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'kikinet-net-'.bin2hex(random_bytes(8)).'.sqlite';
        touch($this->databasePath);

        config([
            'database.default' => 'network_testing',
            'database.connections.network_testing' => [
                'driver' => 'sqlite',
                'database' => $this->databasePath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'network.driver' => 'fake',
            'network.radius.connection' => null,
        ]);

        DB::purge('network_testing');
        $this->artisan('migrate', ['--force' => true])->run();

        Route::middleware('web')
            ->prefix('api/v1')
            ->group(base_path('routes/api/v1/network.php'));
    }

    protected function tearDown(): void
    {
        DB::purge('network_testing');

        if (isset($this->databasePath) && is_file($this->databasePath)) {
            @unlink($this->databasePath);
        }

        parent::tearDown();
    }

    protected function makeMitra(string $code = 'MTR-01'): int
    {
        return DB::table('mitra')->insertGetId([
            'kode_mitra' => $code,
            'nama_mitra' => 'Mitra '.$code,
            'status' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function makeUser(string $roleName = 'admin', ?int $mitraId = null): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['label' => ucfirst($roleName)],
        );

        return User::query()->create([
            'name' => 'User '.$roleName,
            'email' => $roleName.'-'.bin2hex(random_bytes(4)).'@kikinet.test',
            'password' => 'secret-password',
            'role_id' => $role->id,
            'mitra_id' => $mitraId,
            'is_active' => true,
        ]);
    }

    protected function makeCustomer(?int $mitraId = null, string $code = 'C-001'): int
    {
        return DB::table('pelanggan')->insertGetId([
            'mitra_id' => $mitraId,
            'kode_pelanggan' => $code,
            'nama_pelanggan' => 'Pelanggan '.$code,
            'no_hp' => '081200000000',
            'alamat_pasang' => 'Jalan Uji 1',
            'status' => 'aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
