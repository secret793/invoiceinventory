<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckDataEntryOfficerTwo
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->user()->hasRole('Data Entry Officer two')) {
            return $next($request);
        }

        abort(403, 'Unauthorized access.');
    }
}
