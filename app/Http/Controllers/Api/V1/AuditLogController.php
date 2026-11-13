<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('audit.view');

        $query = ActivityLog::query()->with('user:id,name,email');

        if ($module = trim((string) $request->input('module'))) {
            $query->where('modul', $module);
        }

        if ($action = trim((string) $request->input('action'))) {
            $query->where('aksi', 'ilike', '%'.$action.'%');
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        $page = $query->orderByDesc('id')->paginate(min(max($request->integer('per_page', 25), 1), 100));

        return ApiResponse::list(
            collect($page->items())->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->aksi,
                'module' => $log->modul,
                'description' => $log->deskripsi,
                'actor' => $log->user ? ['id' => $log->user->id, 'name' => $log->user->name] : null,
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at?->toIso8601ZuluString(),
            ])->all(),
            [
                'page' => $page->currentPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'last_page' => $page->lastPage(),
            ],
            $request->header('X-Request-ID', ''),
        );
    }
}
