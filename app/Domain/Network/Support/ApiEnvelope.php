<?php

namespace App\Domain\Network\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * TEMPORARY: mirrors docs/04-API-CONTRACT.md `{data, meta}` envelope until
 * Agent A's shared response middleware is registered. Replace with the shared
 * helper at contract-freeze merge; the payload shape will stay identical.
 */
final class ApiEnvelope
{
    public static function ok(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => self::meta($meta),
        ], $status);
    }

    public static function paginated(array $data, int $page, int $perPage, int $total, int $lastPage): JsonResponse
    {
        return self::ok($data, [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
        ]);
    }

    public static function meta(array $extra = []): array
    {
        /** @var Request|null $request */
        $request = request();

        return array_merge([
            'request_id' => $request?->header('X-Request-Id') ?: (string) Str::uuid(),
            'timestamp' => CarbonImmutable::now('UTC')->toIso8601ZuluString(),
        ], $extra);
    }
}
