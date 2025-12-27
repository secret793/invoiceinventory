<?php

namespace App\Http\Controllers;

use App\Imports\DevicesImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,csv,xls',
        ]);

        Excel::import(new DevicesImport, $request->file('file'));

        return redirect()->back()->with('success', 'Devices imported successfully.');
    }

    public function showOnline()
    {
        // Logic to retrieve and display online devices
    }

    public function showOffline()
    {
        // Logic to retrieve and display offline devices
    }

   
}

