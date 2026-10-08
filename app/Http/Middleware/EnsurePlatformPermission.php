<?php

namespace App\Http\Middleware;

use App\Services\Auth\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformPermission
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->guest('/login');
        }

        if (! $user->isPlatformAdmin()) {
            abort(403, 'Platform administrator privileges required.');
        }

        if ($this->authorizationService->cannot($user, $permission)) {
            abort(403, "You do not have the required platform permission [{$permission}].");
        }

        return $next($request);
    }
}
