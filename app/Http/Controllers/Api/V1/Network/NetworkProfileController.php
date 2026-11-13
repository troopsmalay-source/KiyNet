<?php

namespace App\Http\Controllers\Api\V1\Network;

use App\Domain\Network\Models\NetworkAccessProfile;
use App\Domain\Network\Services\NetworkAuthorizer;
use App\Domain\Network\Support\ApiEnvelope;
use App\Domain\Network\Support\ResolvesNetworkUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NetworkProfileController extends Controller
{
    use ResolvesNetworkUser;

    public function __construct(private readonly NetworkAuthorizer $authorizer)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizer->scopeFor($this->networkUser($request));

        $profiles = NetworkAccessProfile::query()->orderBy('code')->get();

        return ApiEnvelope::ok($profiles->map(fn (NetworkAccessProfile $profile) => $this->present($profile))->all());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizer->assertCentral($this->networkUser($request));

        $profile = NetworkAccessProfile::query()->create($request->validate($this->rules()));

        return ApiEnvelope::ok($this->present($profile), status: 201);
    }

    public function update(Request $request, int $profile): JsonResponse
    {
        $this->authorizer->assertCentral($this->networkUser($request));

        $model = NetworkAccessProfile::query()->find($profile);

        if ($model === null) {
            abort(404, 'Profil jaringan tidak ditemukan.');
        }

        $validated = $request->validate($this->rules(partial: true));

        if (isset($validated['download_mbps']) || isset($validated['upload_mbps'])) {
            $model->version = (int) $model->version + 1;
        }

        $model->fill($validated)->save();

        return ApiEnvelope::ok($this->present($model));
    }

    private function rules(bool $partial = false): array
    {
        $presence = $partial ? 'sometimes' : 'required';

        return [
            'code' => [$presence, 'string', 'max:60'],
            'name' => [$presence, 'string', 'max:120'],
            'download_mbps' => [$presence, 'integer', 'min:1', 'max:100000'],
            'upload_mbps' => [$presence, 'integer', 'min:1', 'max:100000'],
            'mikrotik_rate_limit' => ['nullable', 'string', 'max:100'],
            'is_isolation' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'in:active,retired'],
        ];
    }

    private function present(NetworkAccessProfile $profile): array
    {
        return [
            'id' => (string) $profile->id,
            'code' => $profile->code,
            'name' => $profile->name,
            'download_mbps' => (int) $profile->download_mbps,
            'upload_mbps' => (int) $profile->upload_mbps,
            'mikrotik_rate_limit' => $profile->rateLimit(),
            'is_isolation' => (bool) $profile->is_isolation,
            'status' => $profile->status,
            'version' => (int) $profile->version,
        ];
    }
}
