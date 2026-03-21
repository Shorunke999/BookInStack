<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Superadmin access only.');
        }

        // Extra hard check — your specific email
        if ($user->email !== config('app.superadmin_email')) {
            abort(403);
        }

        return $next($request);
    }
}