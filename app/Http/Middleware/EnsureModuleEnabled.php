<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks routes for modules disabled on the user's organization.
 * Usage: ->middleware('module:hiring')
 *
 * Super admins bypass this check (they need to access everything for support).
 */
class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        // Super admins always have access — they need to see all modules for support.
        if ($user->role === 'super_admin') {
            return $next($request);
        }

        $org = $user->currentOrganization();
        if (!$org || !$org->canUseModule($module)) {
            $label = config("modules.modules.{$module}.label", $module);
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => "{$label} is not enabled for your workspace.",
                    'module' => $module,
                ], 403);
            }
            abort(404, "{$label} is not enabled for your workspace.");
        }

        return $next($request);
    }
}
