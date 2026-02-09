<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureOrganization
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();
        if ($user && !$user->isSuperAdmin() && !$user->organization_id) {
            return redirect('/dashboard')->with('error', 'No organization assigned to your account.');
        }
        return $next($request);
    }
}
