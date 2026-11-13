<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Domain\Network\Models\NasDevice;
use App\Domain\Network\Models\RadiusSyncOutbox;
use App\Domain\Accounting\Models\RadiusSession;
use App\Domain\Network\Dto\PartnerUsageQuery;
use App\Domain\Network\Dto\TrustedScope;
use App\Domain\Network\Services\NetworkAnalyticsReader;
use App\Domain\Network\Support\ApiEnvelope;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only network views for the admin dashboard and partner usage summary.
 *
 * Partner scope is derived server-side from the authenticated user; never from
 * HTTP input. A partner can only see its own usage summary.
 */
class NetworkWidgetController extends Controller
{
    public function widget(Request $request): View
    {
        $active = NasDevice::query()->where('status', 'active')->count();

        return view('admin.network.widget', [
            'nasActive' => $active,
            'outboxPending' => RadiusSyncOutbox::query()
                ->whereIn('status', [RadiusSyncOutbox::PENDING, RadiusSyncOutbox::FAILED])
                ->count(),
            'outboxDeadLetter' => RadiusSyncOutbox::query()
                ->where('status', RadiusSyncOutbox::DEAD_LETTER)
                ->count(),
            'activeSessions' => RadiusSession::query()
                ->whereIn('status', ['active', 'stale'])
                ->count(),
        ]);
    }

    public function partnerUsageSummary(Request $request, NetworkAnalyticsReader $reader): JsonResponse
    {
        $user = $request->user();
        $partnerId = $user->mitra_id !== null ? (int) $user->mitra_id : null;

        $scope = $user->hasRole('mitra') && $partnerId !== null
            ? TrustedScope::partner($partnerId)
            : TrustedScope::central();

        $targetPartnerId = $scope->isCentral()
            ? (int) $request->route('partner')
            : $partnerId;

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $to = isset($validated['to']) ? CarbonImmutable::parse($validated['to']) : CarbonImmutable::today();
        $from = isset($validated['from']) ? CarbonImmutable::parse($validated['from']) : $to->subDays(6);

        try {
            $summary = $reader->partnerUsage(new PartnerUsageQuery(
                scope: $scope,
                partnerId: $targetPartnerId,
                from: $from,
                to: $to,
            ));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ApiEnvelope::ok(['message' => 'Tidak ada data pemakaian untuk mitra ini.'], [], 404);
        }

        return ApiEnvelope::ok($summary->toArray());
    }
}
