<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Destination;
use Illuminate\Support\Str;

class CheckDestinationAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->route('destination')) {
            $destination = Destination::find($request->route('destination'));
            $user = auth()->user();
            
            if ($destination) {
                if ($user?->hasRole(['Super Admin', 'Warehouse Manager'])) {
                    return $next($request);
                }

                if ($user->hasPermissionTo('view_destination_' . Str::slug($destination->name))) {
                    return $next($request);
                }

                abort(403, 'Unauthorized access to destination.');
            }
        }

        return $next($request);
    }
} 