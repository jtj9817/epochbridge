<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    /**
     * Vite dev-server origins allowed in local/testing environments. The
     * production policy allows same-origin assets only.
     *
     * @var list<string>
     */
    private const DEV_ORIGINS = [
        'http://localhost:5173',
        'http://localhost:5175',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5175',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());

        // Only advertise HSTS once the request actually arrived over TLS;
        // sending it on plain HTTP (local dev) would be ignored at best.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }

    /**
     * The root Blade template inlines a small appearance-bootstrap script
     * and React renders inline style attributes, so script/style sources
     * need 'unsafe-inline'; everything else is locked to same-origin.
     */
    private function contentSecurityPolicy(): string
    {
        $scriptSrc = ["'self'", "'unsafe-inline'"];
        $connectSrc = ["'self'"];

        if (app()->environment('local', 'testing')) {
            foreach (self::DEV_ORIGINS as $origin) {
                $scriptSrc[] = $origin;
                $connectSrc[] = $origin;
                $connectSrc[] = str_replace('http://', 'ws://', $origin);
            }
        }

        $directives = [
            "default-src 'self'",
            'script-src '.implode(' ', $scriptSrc),
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data:",
            "font-src 'self' data:",
            'connect-src '.implode(' ', $connectSrc),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ];

        return implode('; ', $directives);
    }
}
