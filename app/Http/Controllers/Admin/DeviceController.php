<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;

class DeviceController extends Controller
{
    public function index()
    {
        // Logic to retrieve devices and return a view
        $devices = Device::all(); // Example: retrieve all devices
        return view('admin.devices.index', compact('devices')); // Adjust the view path as necessary
    }
} 