<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TaxSubmission;
use App\Models\TaxSubmissionItem;
use App\Support\ApiResponse;
use App\Support\AuditLogger;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TaxSubmissionController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('tax.view');

        $query = $this->scopedQuery($request)->with(['category', 'mitra']);

        if ($request->filled('period')) {
            $query->where('period', $request->input('period'));
        }

        if (in_array($request->input('status'), ['draft', 'submitted', 'approved', 'rejected', 'changes_requested'], true)) {
            $query->where('status', $request->input('status'));
        }

        $page = $query->orderByDesc('id')->paginate($this->perPage($request));

        return ApiResponse::list(
            collect($page->items())->map(fn (TaxSubmission $submission) => $this->payload($submission))->all(),
            $this->paginationMeta($page),
            $request->header('X-Request-ID', ''),
        );
    }

    public function show(Request $request, int $submission): JsonResponse
    {
        $model = $this->scopedQuery($request)->with(['category', 'mitra', 'items'])->findOrFail($submission);

        return ApiResponse::ok($this->payload($model), 200, $request->header('X-Request-ID', ''));
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('tax.create');

        $data = $request->validate([
            'tax_category_id' => ['required', 'integer', 'exists:tax_categories,id'],
            'period' => ['required', 'date_format:Y-m'],
            'gross_amount_idr' => ['required', 'integer', 'min:0'],
            'tax_amount_idr' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:200'],
            'items.*.amount_idr' => ['required_with:items', 'integer', 'min:0'],
        ]);

        $mitraId = $this->resolveMitraId($request);

        $exists = TaxSubmission::query()
            ->where('mitra_id', $mitraId)
            ->where('tax_category_id', $data['tax_category_id'])
            ->where('period', $data['period'])
            ->exists();

        if ($exists) {
            throw new ConflictHttpException('A tax submission already exists for this partner, category and period.');
        }

        $model = DB::transaction(function () use ($request, $data, $mitraId): TaxSubmission {
            $model = TaxSubmission::create([
                'mitra_id' => $mitraId,
                'tax_category_id' => $data['tax_category_id'],
                'period' => $data['period'],
                'gross_amount_idr' => $data['gross_amount_idr'],
                'tax_amount_idr' => $data['tax_amount_idr'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] ?? [] as $item) {
                $model->items()->create($item);
            }

            $this->audit->log($request->user(), 'tax.submission.create', 'tax', 'Tax submission drafted', [
                'submission_id' => $model->id,
                'mitra_id' => $mitraId,
                'period' => $model->period,
            ], $request);

            return $model;
        });

        return ApiResponse::ok($this->payload($model->load(['category', 'mitra', 'items'])), 201, $request->header('X-Request-ID', ''));
    }

    public function update(Request $request, int $submission): JsonResponse
    {
        Gate::authorize('tax.create');

        $model = $this->scopedQuery($request)->findOrFail($submission);

        if (! in_array($model->status, ['draft', 'changes_requested'], true)) {
            throw new ConflictHttpException('Only draft or changes-requested submissions can be edited.');
        }

        $data = $request->validate([
            'gross_amount_idr' => ['sometimes', 'integer', 'min:0'],
            'tax_amount_idr' => ['sometimes', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.description' => ['required_with:items', 'string', 'max:200'],
            'items.*.amount_idr' => ['required_with:items', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($model, $data): void {
            $items = $data['items'] ?? null;
            unset($data['items']);

            $model->update($data);

            if ($items !== null) {
                $model->items()->delete();
                foreach ($items as $item) {
                    $model->items()->create($item);
                }
            }
        });

        return ApiResponse::ok($this->payload($model->fresh(['category', 'mitra', 'items'])), 200, $request->header('X-Request-ID', ''));
    }

    public function submit(Request $request, int $submission): JsonResponse
    {
        Gate::authorize('tax.submit');

        $model = $this->scopedQuery($request)->findOrFail($submission);

        if (! in_array($model->status, ['draft', 'changes_requested'], true)) {
            throw new ConflictHttpException('Submission is not in an editable state.');
        }

        DB::transaction(function () use ($request, $model): void {
            $model->update(['status' => 'submitted', 'submitted_at' => now()]);

            $this->audit->log($request->user(), 'tax.submission.submit', 'tax', 'Tax submission submitted', [
                'submission_id' => $model->id,
            ], $request);
        });

        return ApiResponse::ok($this->payload($model->fresh(['category', 'mitra', 'items'])), 200, $request->header('X-Request-ID', ''));
    }

    public function review(Request $request, int $submission): JsonResponse
    {
        Gate::authorize('tax.review');

        $model = TaxSubmission::query()->findOrFail($submission);

        if ($model->status !== 'submitted') {
            throw new ConflictHttpException('Only submitted tax reports can be reviewed.');
        }

        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected,changes_requested'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $model, $data): void {
            $model->update([
                'status' => $data['decision'],
                'notes' => $data['notes'] ?? $model->notes,
                'reviewed_at' => now(),
                'reviewed_by' => $request->user()->id,
            ]);

            $this->audit->log($request->user(), 'tax.submission.review', 'tax', 'Tax submission reviewed: '.$data['decision'], [
                'submission_id' => $model->id,
                'decision' => $data['decision'],
            ], $request);
        });

        return ApiResponse::ok($this->payload($model->fresh(['category', 'mitra', 'items'])), 200, $request->header('X-Request-ID', ''));
    }

    public function partnerReport(Request $request, int $partner): JsonResponse
    {
        Gate::authorize('tax.report.partner');

        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null && $scope !== $partner) {
            abort(404);
        }

        $query = TaxSubmission::query()
            ->with('category')
            ->where('mitra_id', $partner)
            ->whereIn('status', ['submitted', 'approved']);

        if ($request->filled('period')) {
            $query->where('period', $request->input('period'));
        }

        $rows = $query->get()
            ->groupBy(fn (TaxSubmission $submission) => $submission->category?->code ?? 'UNKNOWN')
            ->map(fn ($group, $code) => [
                'category' => $code,
                'submissions' => $group->count(),
                'gross_amount_idr' => (int) $group->sum('gross_amount_idr'),
                'tax_amount_idr' => (int) $group->sum('tax_amount_idr'),
            ])
            ->values()
            ->all();

        return ApiResponse::ok([
            'partner_id' => $partner,
            'period' => $request->input('period'),
            'categories' => $rows,
        ], 200, $request->header('X-Request-ID', ''));
    }

    private function scopedQuery(Request $request)
    {
        $query = TaxSubmission::query();
        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null) {
            $query->where('mitra_id', $scope);
        }

        return $query;
    }

    private function resolveMitraId(Request $request): int
    {
        $scope = TenantContext::partnerScope($request->user());

        if ($scope !== null) {
            return $scope;
        }

        return (int) $request->validate([
            'mitra_id' => ['required', 'integer', 'exists:mitra,id'],
        ])['mitra_id'];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(TaxSubmission $submission): array
    {
        return [
            'id' => $submission->id,
            'partner_id' => $submission->mitra_id,
            'partner_name' => $submission->mitra?->nama_mitra,
            'category' => [
                'id' => $submission->tax_category_id,
                'code' => $submission->category?->code,
                'name' => $submission->category?->name,
            ],
            'period' => $submission->period,
            'gross_amount_idr' => (int) $submission->gross_amount_idr,
            'tax_amount_idr' => (int) $submission->tax_amount_idr,
            'status' => $submission->status,
            'notes' => $submission->notes,
            'submitted_at' => $submission->submitted_at?->toIso8601ZuluString(),
            'reviewed_at' => $submission->reviewed_at?->toIso8601ZuluString(),
            'items' => $submission->relationLoaded('items')
                ? $submission->items->map(fn (TaxSubmissionItem $item) => [
                    'description' => $item->description,
                    'amount_idr' => (int) $item->amount_idr,
                ])->all()
                : [],
        ];
    }

    private function perPage(Request $request): int
    {
        return min(max($request->integer('per_page', 25), 1), 100);
    }

    /**
     * @return array<string, int>
     */
    private function paginationMeta($page): array
    {
        return [
            'page' => $page->currentPage(),
            'per_page' => $page->perPage(),
            'total' => $page->total(),
            'last_page' => $page->lastPage(),
        ];
    }
}
