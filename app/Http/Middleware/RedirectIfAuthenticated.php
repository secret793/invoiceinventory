<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Check if trying to access login page
                if ($request->is('admin/login')) {
                    return redirect('/admin');
                }
                return redirect(RouteServiceProvider::HOME);
            }
        }

        // If not authenticated and not trying to access login page, redirect to login
        if (!$request->is('admin/login') && !Auth::check()) {
            return redirect('/admin/login');
        }

        return $next($request);
    }
}
