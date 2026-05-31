<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetAdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->preferredLocale();

        if (array_key_exists((string) $locale, User::ADMIN_LOCALES)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
