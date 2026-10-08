<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventBackHistoryCaching
{
    /**
     * Prevent browsers and intermediate caches from storing sensitive
     * authenticated application responses in cache or bfcache history.
     *
     * In compliance with CWE-525 / OWASP cache-control security standards:
     * - Cache-Control: no-store, no-cache, must-revalidate, max-age=0
     * - Pragma: no-cache
     * - Expires: 0
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
