<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOneC
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = (string) config('services.onec.token');

        if ($expectedToken === '') {
            return new JsonResponse([
                'message' => '1C integration is not configured.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $providedToken = (string) ($request->header('X-1C-Token')
            ?? $request->bearerToken()
            ?? $request->query('token', ''));

        if (!hash_equals($expectedToken, $providedToken)) {
            return new JsonResponse([
                'message' => 'Unauthorized',
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
