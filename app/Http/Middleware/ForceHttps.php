<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    /**
     * Redirect insecure requests to HTTPS in production.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production') && ! $request->secure()) {
            // Ensure proxies are trusted when running behind a load balancer.
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
