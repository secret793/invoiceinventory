<?php

namespace App\Http\Controllers\Filament;

use App\Http\Controllers\Controller;
use App\Models\AssignToAgent;
use App\Models\ConfirmedAffixed;
use Illuminate\View\View;

class ConfirmedAffixedViewController extends Controller
{
    public function __invoke(ConfirmedAffixed $confirmedAffixed): View
    {
        $assignedDevices = AssignToAgent::query()
            ->where('allocation_point_id', $confirmedAffixed->allocation_point_id)
            ->with(['device', 'agent', 'allocationPoint'])
            ->latest()
            ->paginate(10);

        return view('filament.pages.confirmed-affixed-view', [
            'confirmedAffixed' => $confirmedAffixed,
            'assignedDevices' => $assignedDevices,
        ]);
    }
}
