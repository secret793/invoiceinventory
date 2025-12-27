<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Device;
use App\Models\Route;
use Carbon\Carbon;

class DispatchDeviceModal extends Component
{
    public $isOpen = true;
    public $selectedDevices = [];
    public $sadNumber;
    public $vehicleNumber;
    public $regime;
    public $route_id;
    public $manifestDate;
    public $routes;
    
    protected $listeners = [
        'openDispatchModal' => 'openModal',
        'deviceSelected' => 'updateSelectedDevices'
    ];

    public function mount()
    {
        $this->routes = Route::all();
        $this->manifestDate = Carbon::today()->format('Y-m-d');
    }

    public function openModal($devices)
    {
        $this->selectedDevices = $devices;
        $this->isOpen = true;
    }

    public function closeModal()
    {
        $this->isOpen = false;
        $this->selectedDevices = [];
        $this->reset(['sadNumber', 'vehicleNumber', 'regime', 'route_id', 'manifestDate']);
    }

    public function dispatchToAgent()
    {
        if (empty($this->selectedDevices)) {
            $this->addError('devices', 'Please select at least one device');
            return;
        }

        $this->validate([
            'sadNumber' => 'required',
            'vehicleNumber' => 'required',
            'regime' => 'required',
            'route_id' => 'required|exists:routes,id',
            'manifestDate' => 'required|date',
        ]);
        
        return redirect()->route('filament.resources.assign-to-agents.create', [
            'device_id' => $this->selectedDevices[0],
            'sad_number' => $this->sadNumber,
            'vehicle_number' => $this->vehicleNumber,
            'regime' => $this->regime,
            'route_id' => $this->route_id,
            'manifest_date' => $this->manifestDate,
        ]);
    }

    public function render()
    {
        return view('livewire.dispatch-device-modal');
    }
}
