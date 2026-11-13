<?php

namespace App\Http\Controllers\Api\V1\Network;

use App\Domain\Network\Models\NasDevice;
use App\Domain\Network\Services\NetworkAuthorizer;
use App\Domain\Network\Support\ApiEnvelope;
use App\Domain\Network\Support\ResolvesNetworkUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkNasController extends Controller
{
    use ResolvesNetworkUser;

    public function __construct(private readonly NetworkAuthorizer $authorizer)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizer->assertCentral($this->networkUser($request));

        $devices = NasDevice::query()->orderBy('code')->get();

        return ApiEnvelope::ok($devices->map(fn (NasDevice $device) => $this->present($device))->all());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizer->assertCentral($this->networkUser($request));

        $validated = $request->validate($this->rules());
        $device = NasDevice::query()->create($validated);

        return ApiEnvelope::ok($this->present($device), status: 201);
    }

    public function update(Request $request, int $nas): JsonResponse
    {
        $this->authorizer->assertCentral($this->networkUser($request));

        $device = NasDevice::query()->find($nas);

        if ($device === null) {
            abort(404, 'NAS tidak ditemukan.');
        }

        $device->fill($request->validate($this->rules(partial: true)))->save();

        return ApiEnvelope::ok($this->present($device));
    }

    private function rules(bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';

        return [
            'code' => [$presence, 'string', 'max:40'],
            'name' => [$presence, 'string', 'max:120'],
            'management_ip' => [$presence, 'ip'],
            'nas_identifier' => ['nullable', 'string', 'max:120'],
            'site_label' => ['nullable', 'string', 'max:120'],
            'routeros_version' => ['nullable', 'string', 'max:40'],
            'model' => ['nullable', 'string', 'max:80'],
            'status' => ['sometimes', 'in:active,maintenance,disabled'],
            'accounting_enabled' => ['sometimes', 'boolean'],
            'interim_interval_seconds' => ['sometimes', 'integer', 'min:60', 'max:3600'],
            // Secret references only. Plaintext secrets/shared secrets are rejected.
            'radius_secret_reference' => ['nullable', 'string', 'max:191'],
            'api_secret_reference' => ['nullable', 'string', 'max:191'],
        ];
    }

    private function present(NasDevice $device): array
    {
        return [
            'id' => (string) $device->id,
            'code' => $device->code,
            'name' => $device->name,
            'management_ip' => $device->management_ip,
            'nas_identifier' => $device->nas_identifier,
            'site_label' => $device->site_label,
            'routeros_version' => $device->routeros_version,
            'model' => $device->model,
            'status' => $device->status,
            'accounting_enabled' => (bool) $device->accounting_enabled,
            'interim_interval_seconds' => (int) $device->interim_interval_seconds,
            'last_seen_at' => $device->last_seen_at?->toIso8601ZuluString(),
            'has_radius_secret' => filled($device->radius_secret_reference),
            'has_api_secret' => filled($device->api_secret_reference),
        ];
    }
}
