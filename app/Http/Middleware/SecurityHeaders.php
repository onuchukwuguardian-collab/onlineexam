<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Content Security Policy - Fixed for local assets (IPv4 with flexible port)
        $devSources = config('app.debug') ? " http://localhost:5173 http://127.0.0.1:5173 http://localhost:5174 http://127.0.0.1:5174" : "";
        $devWsSources = config('app.debug') ? " ws://localhost:5173 ws://127.0.0.1:5173 ws://localhost:5174 ws://127.0.0.1:5174" : "";
        
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com{$devSources}; " .
               "style-src 'self' 'unsafe-inline'{$devSources}; " .
               "font-src 'self' data: https://fonts.gstatic.com https://fonts.googleapis.com{$devSources}; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self'{$devSources}{$devWsSources}; " .
               "frame-ancestors 'none';";

        $response->headers->set('Content-Security-Policy', $csp);
        
        // Security headers
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // HSTS for production
        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}