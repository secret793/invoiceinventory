<?php

namespace App\Http\Controllers;

use App\Models\AllocationPoint;
use Illuminate\Http\Request;

class DataEntryController extends Controller
{
    public function show($id)
    {
        $allocationPoint = AllocationPoint::findOrFail($id);

        // Return the custom Filament view
        return view('filament.pages.data-entry', [
            'allocationPoint' => $allocationPoint,
        ]);
    }
}
