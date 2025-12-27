<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Device;
use Illuminate\Support\Facades\Route;

class DeviceDispatchTable extends Component
{
    public $search = '';
    public $selectAll = false;
    public $selectedDevices = [];
    public $statuses = ['ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST', 'ASSIGNED TO AGENT'];
    public $activeStatus = 'ONLINE';
    public $status_counts = [];
    public $sortField = 'device_id';
    public $sortDirection = 'asc';

    public function mount()
    {
        $this->calculateStatusCounts();
    }

    public function calculateStatusCounts()
    {
        $this->status_counts = Device::whereIn('status', $this->statuses)
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as count')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function setStatus($status)
    {
        $this->activeStatus = $status;
        $this->resetPage();
    }

    public function sort($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSelectAll($value)
    {
        $this->selectedDevices = $value
            ? $this->getDevicesQuery()->pluck('id')->toArray()
            : [];
    }

    public function dispatchSingleDevice($deviceId)
    {
        return $this->redirectToDispatch([$deviceId]);
    }

    public function dispatchSelectedDevices()
    {
        if (empty($this->selectedDevices)) {
            $this->dispatch('notify', [
                'title' => 'Error',
                'message' => 'Please select devices to dispatch.',
                'type' => 'error'
            ]);
            return;
        }

        return $this->redirectToDispatch($this->selectedDevices);
    }

    protected function redirectToDispatch($deviceIds)
    {
        return redirect()->route('dispatch.create', [
            'devices' => implode(',', $deviceIds),
            'assignment_id' => request()->route('record') // Assuming the assignment ID is in the route
        ]);
    }

    public function getDevicesQuery()
    {
        return Device::when($this->search, function ($query) {
                $query->where('device_id', 'like', "%{$this->search}%")
                      ->orWhere('description', 'like', "%{$this->search}%");
            })
            ->when($this->activeStatus, function ($query) {
                $query->where('status', $this->activeStatus);
            })
            ->orderBy($this->sortField, $this->sortDirection);
    }

    public function render()
    {
        $devices = $this->getDevicesQuery()->get();

        return view('livewire.device-dispatch-table', [
            'devices' => $devices
        ]);
    }
}
