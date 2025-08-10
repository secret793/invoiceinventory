<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AllocationPoint;
use Illuminate\Support\Str;

class CheckAllocationPointAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->route('allocation_point')) {
            $allocationPoint = AllocationPoint::find($request->route('allocation_point'));
            $user = auth()->user();
            
            if ($allocationPoint) {
                if ($user?->hasRole(['Super Admin', 'Warehouse Manager'])) {
                    return $next($request);
                }

                if ($user?->hasRole('Allocation Officer') && 
                    $user->hasPermissionTo('view_allocationpoint_' . Str::slug($allocationPoint->name))) {
                    return $next($request);
                }

                abort(403, 'Unauthorized access to allocation point.');
            }
        }

        return $next($request);
    }
}
