<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class ApiResponse
{
    /**
     * Success single resource.
     */
    public static function ok(mixed $data = null, int $status = 200, string $requestId = ''): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => [
                'request_id' => $requestId,
                'timestamp' => Carbon::now()->toIso8601ZuluString(),
            ],
        ], $status);
    }

    /**
     * Success list with pagination meta.
     */
    public static function list(mixed $data, array $meta, string $requestId = ''): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'meta' => array_merge([
                'request_id' => $requestId,
                'timestamp' => Carbon::now()->toIso8601ZuluString(),
            ], $meta),
        ], 200);
    }

    /**
     * Error response.
     */
    public static function error(string $code, string $message, array $details = [], int $status = 422, string $requestId = ''): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta' => [
                'request_id' => $requestId,
                'timestamp' => Carbon::now()->toIso8601ZuluString(),
            ],
        ], $status);
    }
}
