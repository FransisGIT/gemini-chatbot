<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale') ?? $request->cookie('locale') ?? config('app.locale');

        app()->setLocale($locale);
        $response = $next($request);
        $response->headers->setCookie(cookie()->forever('locale', $locale));

        return $response;
    }
}
