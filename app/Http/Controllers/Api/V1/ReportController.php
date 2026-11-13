<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tagihan;
use App\Support\ApiResponse;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function billing(Request $request): JsonResponse
    {
        Gate::authorize('report.billing');

        $query = Tagihan::query();
        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null) {
            $query->whereHas('pelanggan', fn ($where) => $where->where('mitra_id', $scope));
        } elseif ($request->filled('partner_id')) {
            $partnerId = $request->integer('partner_id');
            $query->whereHas('pelanggan', fn ($where) => $where->where('mitra_id', $partnerId));
        }

        if ($request->filled('period')) {
            $query->where('periode', $request->input('period'));
        }

        $active = (clone $query)->where('status', '!=', 'dibatalkan');

        $totalBilled = (int) round((float) (clone $active)->sum('total_tagihan'));
        $totalPaid = (int) round((float) (clone $active)->sum('total_dibayar'));

        $byStatus = (clone $query)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($count) => (int) $count)
            ->all();

        return ApiResponse::ok([
            'period' => $request->input('period'),
            'invoices_count' => (clone $query)->count(),
            'total_billed_idr' => $totalBilled,
            'total_paid_idr' => $totalPaid,
            'outstanding_idr' => max($totalBilled - $totalPaid, 0),
            'by_status' => $byStatus,
        ], 200, $request->header('X-Request-ID', ''));
    }
}
