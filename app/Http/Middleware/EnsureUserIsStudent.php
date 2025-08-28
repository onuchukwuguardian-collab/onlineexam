<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureUserIsStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isStudent()) {
            if (Auth::user()->class_id) { // Ensure student is assigned to a class
                return $next($request);
            }
            // Redirect to a page or show an error if not assigned to a class
            return redirect()->route('dashboard')->with('error', 'You are not assigned to a class. Please contact an administrator.');
        }
        abort(403, 'Unauthorized action. Student access required.');
    }
}
