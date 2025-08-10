<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\User;
use App\Models\Store;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StoresImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
       return new Store([
            'device_type' => $row['device_type'],
            'serial_number' => $row['serial_number'], // Added serial_number
            'batch_number' => $row['batch_number'],
            'status' => $row['status'],
            'date_received' => \Carbon\Carbon::parse($row['date_received']),
            'user_id' => $this->getUserId($row['added_by']), // Assuming 'added_by' is the column in the Excel file
        ]);
    }

    private function getUserId($username)
    {
        $user = User::where('name', $username)->first();
        return $user ? $user->id : null;
    }
}
