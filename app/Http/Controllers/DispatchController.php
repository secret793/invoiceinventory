<?php

namespace App\Http\Controllers;

use App\Models\AssignToAgent;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use App\Models\Regime;
use App\Models\Destination;

class DispatchController extends Controller
{

    public function cancel(Request $request)
    {
        // Add any logic you need to handle the cancel action.
        return redirect()->route('data-entry-assignment.show', $request->input('assignment_id'));
    }

public function create()
{
    $regimes = Regime::where('is_active', true)->get();
    $destinations = Destination::where('status', 'active')->get();

    return view('filament.resources.assign-to-agent.create', compact('regimes', 'destinations'));
}

}
