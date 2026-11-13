<?php

namespace Tests\Feature\Api;

use App\Support\ApiExceptionRenderer;
use App\Support\PermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Phase 1: frozen error envelope and permission catalog contract.
 */
class FoundationErrorEnvelopeTest extends TestCase
{
    public function test_unknown_api_route_returns_frozen_not_found_envelope(): void
    {
        $response = $this->getJson('/api/v1/does-not-exist');

        $response->assertStatus(404)
            ->assertJsonPath('error.code', 'NOT_FOUND')
            ->assertJsonStructure(['error' => ['code', 'message', 'details'], 'meta' => ['request_id', 'timestamp']]);

        $this->assertNotEmpty($response->headers->get('X-Request-ID'));
    }

    public function test_validation_exception_is_rendered_as_envelope_with_details(): void
    {
        $request = Request::create('/api/v1/customers', 'POST');
        $request->headers->set('X-Request-ID', 'req-validation-1');

        $exception = ValidationException::withMessages([
            'email' => ['The email is invalid.'],
        ]);

        $response = app(ApiExceptionRenderer::class)->render($exception, $request);

        $this->assertNotNull($response);
        $this->assertSame(422, $response->getStatusCode());

        $payload = $response->getData(true);
        $this->assertSame('VALIDATION_ERROR', $payload['error']['code']);
        $this->assertSame(['email' => ['The email is invalid.']], $payload['error']['details']);
        $this->assertSame('req-validation-1', $payload['meta']['request_id']);
    }

    public function test_non_api_requests_keep_default_handling(): void
    {
        $request = Request::create('/admin/dashboard', 'GET');

        $this->assertNull(
            app(ApiExceptionRenderer::class)->render(new \RuntimeException('boom'), $request),
        );
    }

    public function test_permission_catalog_maps_legacy_roles(): void
    {
        $this->assertSame(['*'], PermissionCatalog::forRole('super_admin'));
        $this->assertTrue(PermissionCatalog::allows('admin', 'invoice.void'));
        $this->assertFalse(PermissionCatalog::allows('mitra', 'invoice.void'));
        $this->assertTrue(PermissionCatalog::allows('mitra', 'customer.view'));
        $this->assertSame([], PermissionCatalog::forRole('unknown_role'));
        $this->assertSame([], PermissionCatalog::forRole(null));
    }
}
