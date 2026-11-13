<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $request->header('X-Request-ID')
            ?? Str::orderedUuid()->toString();

        $request->headers->set('X-Request-ID', $requestId);

        $response = $next($request);

        // headers->set() also works for StreamedResponse (CSV exports).
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
