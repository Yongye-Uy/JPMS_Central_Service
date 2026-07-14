<?php

namespace App\Http\Middleware;

use App\Support\AuditContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only API is allowed to call Central-Service's /internal/v1/* routes.
 * Checks the X-Internal-Token shared secret and captures the acting user
 * (forwarded by API from Backend's authenticated session) for audit logging.
 */
class EnsureInternalToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Internal-Token');

        if (! $token || ! hash_equals((string) config('services.internal.shared_secret'), (string) $token)) {
            return response()->json(['message' => 'Invalid internal token.'], 401);
        }

        $actorId = $request->header('X-Actor-User-Id');
        AuditContext::set($actorId !== null ? (int) $actorId : null);

        return $next($request);
    }
}
