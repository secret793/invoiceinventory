<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeviceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            // ... other validation rules ...
            'date_received' => 'required|date|before_or_equal:today', // Ensure it cannot be in the future
            // ... other validation rules ...
        ];
    }
}

