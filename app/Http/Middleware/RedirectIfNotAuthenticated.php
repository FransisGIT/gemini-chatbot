<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            session()->flash('failed_message', 'Maaf, kamu tidak memiliki akses ke halaman ini. Silakan login terlebih dahulu!');

            return redirect()->route('auth.login');
        }

        return $next($request);
    }
}
