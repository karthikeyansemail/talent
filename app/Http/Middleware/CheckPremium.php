<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPremium
{
    public function handle(Request $request, Closure $next): Response
    {
        $org = $request->user()?->organization;

        if (!$org || !$org->is_premium) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Premium subscription required.'], 403);
            }
            abort(403, 'This feature requires a premium subscription.');
        }

        // Check expiry
        if ($org->premium_expires_at && $org->premium_expires_at->isPast()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Premium subscription has expired.'], 403);
            }
            abort(403, 'Your premium subscription has expired.');
        }

        return $next($request);
    }
}
