<?php

namespace App\Http\Middleware;

use App\Support\PlatformSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWebsiteIsOnline
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! PlatformSettings::websiteIsOnline()) {
            return response()->json([
                'message' => 'Website is offline.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $next($request);
    }
}
