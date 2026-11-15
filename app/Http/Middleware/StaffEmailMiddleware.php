<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StaffEmailMiddleware
{
    /**
     * Staff access is by email allowlist, not a DB flag.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $email = Auth::user()?->email;

        if ($email === null || ! in_array(strtolower($email), config('staff.emails', []), true)) {
            abort(403);
        }

        return $next($request);
    }
}
