<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\DataEntryAssignment;
use Illuminate\Support\Str;

class CheckDataEntryAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->route('data_entry_assignment')) {
            $assignment = DataEntryAssignment::with('allocationPoint')
                ->find($request->route('data_entry_assignment'));
            $user = auth()->user();
            
            if ($assignment && $assignment->allocationPoint) {
                if ($user?->hasRole(['Super Admin', 'Warehouse Manager', 'Data Entry Officer'])) {
                    return $next($request);
                }

                abort(403, 'Unauthorized access to data entry assignment.');
            }
        }

        return $next($request);
    }
}