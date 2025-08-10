<?php

namespace App\Imports;

use App\Models\Device;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class DevicesImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row)
    {
        // Try to find existing device
        $device = Device::where('device_id', $row['device_id'])->first();

        // Parse date with flexible format handling
        try {
            if (empty($row['date_received'])) {
                $dateReceived = now();
            } else if (is_numeric($row['date_received'])) {
                // Handle Excel date number
                $dateReceived = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $row['date_received']));
            } else {
                // Handle string date
                $dateReceived = Carbon::parse($row['date_received']);
            }

            // Validate that the date is not in the future
            if ($dateReceived->isFuture()) {
                throw ValidationException::withMessages([
                    'date_received' => 'Date received cannot be in the future.',
                ]);
            }
        } catch (\Exception $e) {
            // If all parsing fails, use current date
            $dateReceived = now();
        }

        $data = [
            'device_type' => $row['device_type'],
            'device_id' => (string) $row['device_id'],
            'batch_number' => $row['batch_number'],
            'status' => strtoupper($row['status'] ?? 'UNCONFIGURED'),
            'date_received' => $dateReceived,
            'user_id' => $this->getUserId($row['added_by'] ?? auth()->id()),
            'sim_number' => $row['sim_number'] ? (string) $row['sim_number'] : null,
            'sim_operator' => $row['sim_operator'] ?? null,
        ];

        if ($device) {
            $device->update($data);
            return $device;
        }

        return new Device($data);
    }
    
    private function getUserId($username)
    {
        if (is_numeric($username)) {
            return $username;
        }
        $user = User::where('name', $username)->first();
        return $user ? $user->id : auth()->id();
    }

    public function rules(): array
    {
        return [
            'device_type' => ['required', Rule::in(['JT701', 'JT709A', 'JT709C'])],
            'device_id' => ['required', 'unique:devices,device_id'],
            'batch_number' => ['required'],
            'status' => ['nullable', Rule::in(['UNCONFIGURED', 'CONFIGURED', 'ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST'])],
            'date_received' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (empty($value)) {
                        return; // Allow empty value, will be set to now() in model
                    }

                    try {
                        $date = null;
                        if (is_numeric($value)) {
                            // Handle Excel date number
                            $date = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value));
                        } else {
                            // Handle string date
                            $date = Carbon::parse($value);
                        }

                        if ($date && $date->isFuture()) {
                            $fail('The date cannot be in the future.');
                        }
                    } catch (\Exception $e) {
                        $fail('The date format is invalid. Please use a valid date format (e.g., YYYY-MM-DD).');
                    }
                }
            ],
            'sim_number' => ['nullable', 'unique:devices,sim_number'],
            'sim_operator' => ['nullable'],
            'added_by' => ['nullable'],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'device_type.in' => 'Device type must be one of: JT701, JT709A, JT709C',
            'status.in' => 'Status must be one of: UNCONFIGURED, CONFIGURED, ONLINE, OFFLINE, DAMAGED, FIXED, LOST',
            'sim_number.unique' => 'SIM number :input already exists',
        ];
    }
}
