<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyApiCsrf
{
    public function handle(Request $request, Closure $next): Response
    {
        // For API routes, verify CSRF token is present and valid
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')) {
            $token = $request->header('X-CSRF-TOKEN') ?: $request->input('_token');
            
            if (!$token || !hash_equals(session()->token(), $token)) {
                return response()->json(['error' => 'CSRF token mismatch'], 419);
            }
        }

        return $next($request);
    }
}