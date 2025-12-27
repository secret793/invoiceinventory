<?php

namespace App\Http\Controllers;

use App\Models\DistributionPoint;
use Illuminate\Http\Request;

class DistributionPointController extends Controller
{
    public function index()
    {
        // Fetch distribution points and pass to the view
        $distributionPoints = DistributionPoint::all(); // Adjust according to your model
        return view('filament.pages.distribution-point', compact('distributionPoints'));
    }

    public function show($id)
    {
        // Fetch a specific distribution point by ID
        $distributionPoint = DistributionPoint::findOrFail($id); // Adjust according to your model

        // Example data for status counts
        $status_counts = [
            'OK' => 30,
            'DAMAGED' => 0,
            'LOST' => 0,
            'TOTAL' => 30,
        ];

        // Example data for devices
        $devices = [
            ['description' => '35CM LOCKING CABLES', 'ok' => 30, 'damaged' => 0, 'lost' => 0, 'total' => 30],
            ['description' => '3 METERS LOCKING CABLES', 'ok' => 30, 'damaged' => 0, 'lost' => 0, 'total' => 30],
        ];

        return view('filament.resources.distribution-point.pages.view-distribution-point', compact('distributionPoint', 'status_counts', 'devices'));
    }
}
