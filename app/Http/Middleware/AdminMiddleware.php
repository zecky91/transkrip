<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        // Allow both admin and guru_bk roles
        if (!in_array(Auth::user()->role, ['admin', 'guru_bk'])) {
            return redirect('/login');
        }

        return $next($request);
    }
}
