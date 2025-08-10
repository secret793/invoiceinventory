<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\DeviceRetrieval;
use Illuminate\Support\Str;

class CheckDeviceRetrievalAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->route('device_retrieval')) {
            $retrieval = DeviceRetrieval::find($request->route('device_retrieval'));
            $user = auth()->user();
            
            if ($retrieval) {
                if ($user?->hasRole(['Super Admin', 'Warehouse Manager'])) {
                    return $next($request);
                }

                if ($user?->hasRole('Retrieval Officer') &&
                    $user->hasPermissionTo('view_destination_' . Str::slug($retrieval->destination))) {
                    return $next($request);
                }

                if ($user?->hasRole('Affixing Officer') &&
                    $user->hasPermissionTo('view_destination_' . Str::slug($retrieval->destination))) {
                    return $next($request);
                }

                abort(403, 'Unauthorized access to device retrieval.');
            }
        }

        return $next($request);
    }
} 