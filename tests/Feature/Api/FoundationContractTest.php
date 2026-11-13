<?php

namespace Tests\Feature\Api;

use App\Models\Mitra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 1 foundation contract tests (Agent A).
 * Covers: API bootstrap, request ID middleware, response envelope,
 * /me identity endpoint, tenant context resolution.
 */
class FoundationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_returns_request_id_header(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
    }

    public function test_client_supplied_request_id_is_preserved(): void
    {
        $response = $this->getJson('/api/v1/me', [
            'X-Request-ID' => 'test-req-id-12345',
        ]);

        $this->assertSame('test-req-id-12345', $response->headers->get('X-Request-ID'));
    }

    public function test_me_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')
            ->assertStatus(401)
            ->assertJsonStructure(['error', 'meta']);
    }

    public function test_me_endpoint_returns_envelope_for_super_admin(): void
    {
        $user = User::factory()->create([
            'email' => 'root@kinet.local',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email', 'role', 'partner_id', 'is_central', 'permissions'],
                'meta' => ['request_id', 'timestamp'],
            ])
            ->assertJsonPath('data.is_central', true)
            ->assertJsonPath('data.permissions.0', '*');
    }

    public function test_me_endpoint_returns_mitra_scope_for_partner_user(): void
    {
        $mitra = Mitra::factory()->create();
        $user = User::factory()->withRole('mitra')->forMitra($mitra)->create([
            'email' => 'partner@kinet.local',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.is_central', false)
            ->assertJsonPath('data.partner_id', $mitra->id)
            ->assertJsonPath('data.role', 'mitra');
    }

    public function test_tenant_context_resolves_partner_from_principal_not_input(): void
    {
        $mitra = Mitra::factory()->create();
        $user = User::factory()->withRole('mitra')->forMitra($mitra)->create();

        // Even if a request tries to pass mitra_id, scope stays from the principal.
        $response = $this->actingAs($user)->getJson('/api/v1/me?mitra_id=999');

        $response->assertStatus(200)
            ->assertJsonPath('data.partner_id', $mitra->id);
    }
}
