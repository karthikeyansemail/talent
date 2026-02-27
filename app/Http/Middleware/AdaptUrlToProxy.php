<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * When the app sits behind a reverse proxy (ngrok, Cloudflare, etc.) the
 * APP_URL config is set to the local origin (e.g. http://localhost/talent/public).
 * Laravel's asset() helper uses that value, which produces HTTP URLs even when
 * the browser reached us over HTTPS — causing mixed-content blocks.
 *
 * This middleware detects a proxied request via X-Forwarded-Proto / X-Forwarded-Host
 * and overrides the URL root on-the-fly so that asset() and route() produce URLs
 * that match the actual scheme + host the browser sees.
 */
class AdaptUrlToProxy
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only adjust when TrustProxies has already resolved the forwarded headers
        // into the request's scheme/host (i.e. the request appears to come from HTTPS).
        if ($request->getScheme() !== parse_url(config('app.url'), PHP_URL_SCHEME)
            || $request->getHttpHost() !== parse_url(config('app.url'), PHP_URL_HOST)) {

            // Build the root URL from what the browser actually sees
            $root = $request->getScheme() . '://' . $request->getHttpHost();

            // If the app lives at a sub-path (e.g. /talent/public), keep it
            $basePath = parse_url(config('app.url'), PHP_URL_PATH);
            if ($basePath && $basePath !== '/') {
                $root .= rtrim($basePath, '/');
            }

            URL::forceRootUrl($root);
        }

        return $next($request);
    }
}
