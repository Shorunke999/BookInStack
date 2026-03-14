<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->isAdmin()) {
            // API request
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Admin access required'], 403);
            }

            // Web request — redirect with error
            return redirect()->route('dashboard')
                ->with('error', 'You do not have permission to access that page.');
        }

        return $next($request);
    }
}
