<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Renders the frozen API error envelope (docs/04-API-CONTRACT.md §2).
 *
 * Every /api/* error is translated here so clients always receive the same
 * shape and never see stack traces, SQL or internal messages.
 */
class ApiExceptionRenderer
{
    public function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        [$status, $code, $message, $details] = match (true) {
            $e instanceof ValidationException => [
                422, 'VALIDATION_ERROR', 'The given data was invalid.', $e->errors(),
            ],
            $e instanceof AuthenticationException => [
                401, 'UNAUTHENTICATED', 'Unauthenticated.', [],
            ],
            $e instanceof AuthorizationException => [
                403, 'FORBIDDEN', 'This action is unauthorized.', [],
            ],
            $e instanceof HttpExceptionInterface => [
                $e->getStatusCode(),
                $this->codeForStatus($e->getStatusCode()),
                $this->messageForStatus($e->getStatusCode()),
                [],
            ],
            default => [500, 'INTERNAL_ERROR', 'Server Error', []],
        };

        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta' => [
                'request_id' => $request->header('X-Request-ID', ''),
                'timestamp' => now()->toIso8601ZuluString(),
            ],
        ], $status);
    }

    private function codeForStatus(int $status): string
    {
        return match ($status) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHENTICATED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            405 => 'METHOD_NOT_ALLOWED',
            409 => 'CONFLICT',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMITED',
            default => $status >= 500 ? 'INTERNAL_ERROR' : 'REQUEST_FAILED',
        };
    }

    private function messageForStatus(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request.',
            401 => 'Unauthenticated.',
            403 => 'This action is unauthorized.',
            404 => 'Not Found.',
            405 => 'Method Not Allowed.',
            409 => 'Conflict.',
            422 => 'The given data was invalid.',
            429 => 'Too Many Requests.',
            default => 'Server Error',
        };
    }
}
