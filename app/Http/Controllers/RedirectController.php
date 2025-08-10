<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // If user is not logged in, redirect to login
        if (!Auth::check()) {
            return redirect('/admin/login');
        }

        // If user is logged in, redirect to admin panel
        return redirect('/admin');
    }
}
