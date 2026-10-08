<?php

namespace App\Http\Middleware;

use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessPermission
{
    public function __construct(
        protected AuthorizationService $authorizationService,
        protected TenantContext $tenantContext
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

        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();

        if (! $tenant || ! $tenant->isActive()) {
            abort(403, 'A valid active business context is required for this action.');
        }

        if ($this->authorizationService->cannot($user, $permission, $tenant)) {
            abort(403, "You do not have the required business permission [{$permission}].");
        }

        return $next($request);
    }
}
