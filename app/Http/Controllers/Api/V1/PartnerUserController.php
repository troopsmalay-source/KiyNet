<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PartnerUserController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request, int $partner): JsonResponse
    {
        Gate::authorize('partner.users.manage');

        $mitra = $this->scopedPartner($request, $partner);

        $users = User::query()
            ->where('mitra_id', $mitra->id)
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => $this->payload($user));

        return ApiResponse::ok($users->all(), 200, $request->header('X-Request-ID', ''));
    }

    public function store(Request $request, int $partner): JsonResponse
    {
        Gate::authorize('partner.users.manage');

        $mitra = $this->scopedPartner($request, $partner);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['boolean'],
        ]);

        $user = DB::transaction(function () use ($request, $mitra, $data): User {
            $role = Role::firstOrCreate(['name' => 'mitra'], ['label' => 'Mitra']);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role_id' => $role->id,
                'mitra_id' => $mitra->id,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->audit->log($request->user(), 'partner.user.create', 'partner', 'Partner staff created', [
                'partner_id' => $mitra->id,
                'user_id' => $user->id,
            ], $request);

            return $user;
        });

        return ApiResponse::ok($this->payload($user), 201, $request->header('X-Request-ID', ''));
    }

    public function update(Request $request, int $partner, int $user): JsonResponse
    {
        Gate::authorize('partner.users.manage');

        $mitra = $this->scopedPartner($request, $partner);
        $model = User::query()->where('mitra_id', $mitra->id)->findOrFail($user);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($model->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['boolean'],
        ]);

        if (($data['password'] ?? null) === null) {
            unset($data['password']);
        }

        $model->update($data);

        $this->audit->log($request->user(), 'partner.user.update', 'partner', 'Partner staff updated', [
            'partner_id' => $mitra->id,
            'user_id' => $model->id,
        ], $request);

        return ApiResponse::ok($this->payload($model), 200, $request->header('X-Request-ID', ''));
    }

    private function scopedPartner(Request $request, int $partnerId): Mitra
    {
        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null && $scope !== $partnerId) {
            abort(404);
        }

        return Mitra::query()->findOrFail($partnerId);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => (bool) $user->is_active,
            'role' => $user->role?->name,
            'created_at' => $user->created_at?->toIso8601ZuluString(),
        ];
    }
}
