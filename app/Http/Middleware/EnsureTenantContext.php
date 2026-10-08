<?php

namespace App\Http\Middleware;

use App\Exceptions\TenantAccessDeniedException;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantContext
{
    public function __construct(
        protected TenantContext $tenantContext
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->guest('/login');
        }

        try {
            $business = $this->tenantContext->resolveForUser($user);

            // Bind resolved tenant to request attributes
            $request->attributes->set('tenant', $business);
            $request->attributes->set('tenant_id', $business->id);
        } catch (TenantAccessDeniedException $e) {
            abort(403, $e->getMessage());
        }

        return $next($request);
    }
}
