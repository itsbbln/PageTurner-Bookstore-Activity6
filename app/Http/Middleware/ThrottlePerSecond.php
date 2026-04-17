<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottlePerSecond
{
    public function handle(Request $request, Closure $next, int $maxAttempts = 2, string $scope = 'global'): Response
    {
        $user = $request->user();
        $key = ($user?->id ? "user:{$user->id}" : "ip:{$request->ip()}");
        $key = "ps:{$scope}:{$key}";

        // 1-second decay for burst protection
        $decaySeconds = 1;

        if (RateLimiter::tooManyAttempts($key, max(1, $maxAttempts))) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too Many Requests',
                'limit' => (int) $maxAttempts,
                'retry_after_seconds' => (int) $retryAfter,
            ], 429)->withHeaders([
                'Retry-After' => (string) $retryAfter,
                'X-RateLimit-Limit' => (string) $maxAttempts,
                'X-RateLimit-Remaining' => '0',
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);

        /** @var Response $response */
        $response = $next($request);

        $remaining = max(0, max(1, $maxAttempts) - RateLimiter::attempts($key));
        $response->headers->set('X-RateLimit-Limit', (string) $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);

        return $response;
    }
}

