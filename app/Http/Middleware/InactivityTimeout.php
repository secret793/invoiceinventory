<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class InactivityTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            // Get inactivity timeout in minutes (default: 30 minutes)
            $timeout = config('session.inactivity_timeout', 30);
            
            // Get last activity timestamp
            $lastActivity = Session::get('last_activity_time');
            
            if ($lastActivity) {
                // Calculate time since last activity
                $inactiveMinutes = now()->diffInMinutes($lastActivity);
                
                // If inactive for longer than timeout, log out
                if ($inactiveMinutes >= $timeout) {
                    Auth::logout();
                    Session::flush();
                    Session::regenerate();
                    
                    return redirect()->route('filament.admin.auth.login')
                        ->with('error', 'Your session expired due to inactivity. Please login again.');
                }
            }
            
            // Update last activity time
            Session::put('last_activity_time', now());
        }
        
        return $next($request);
    }
}
