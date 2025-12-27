<?php

namespace App\Filament\Resources\AllocationPointResource\Pages;

use App\Filament\Resources\AllocationPointResource;
use Filament\Resources\Pages\ViewRecord;
use App\Models\AllocationPoint;
use App\Models\Device;
use App\Models\Transfer;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ViewAllocationPoint extends ViewRecord
{
    protected static string $resource = AllocationPointResource::class;

    public $allocationPoint;
    public $devices;
    public $allocationPoints;
    public $selectedDevices = [];
    public $selectedTargetAP;
    public $errorMessage;
    public $selectAll = false;
    public $selectedStatuses = [];
    public $statuses = ['ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST', 'RECEIVED'];
    public $search = '';
    protected $queryString = ['search'];
    public $isSearching = false;
    public $totalDeviceCount = 0;
    public $filteredDeviceCount = 0;
    public $selectedAllocationPoint = null; // Added for modal functionality
    public $showAllocationModal = false;
    public $isChangeStatusModalOpen = false;
    public $selectedStatus = null;
    public $availableStatuses = [
        'ONLINE' => 'Online',
        'OFFLINE' => 'Offline',
        'DAMAGED' => 'Damaged',
        'FIXED' => 'Fixed',
        'LOST' => 'Lost',
    ];

    protected static string $view = 'filament.resources.allocation-point.pages.view-allocation-point';

    public function mount(string|int $record): void
    {
        parent::mount($record);
        $this->allocationPoint = $this->record;
        $this->allocationPoints = AllocationPoint::where('id', '!=', $this->record->id)->get();
        $this->devices = $this->getDevicesForAllocationPoint();
    }

    public function openAllocationTransferModal()
    {
        // Simply show the modal without checking for selected devices
        $this->showAllocationModal = true;
    }

    public function closeAllocationModal()
    {
        $this->showAllocationModal = false;
        $this->selectedAllocationPoint = null;
    }

    public function processAllocationTransfer()
    {
        if (empty($this->selectedDevices)) {
            Notification::make()
                ->warning()
                ->title('No devices selected')
                ->body('Please select at least one device to transfer')
                ->send();
            return;
        }

        if (!$this->selectedAllocationPoint) {
            Notification::make()
                ->warning()
                ->title('No target allocation point selected')
                ->body('Please select a target allocation point')
                ->send();
            return;
        }

        // Check for devices that can't be transferred
        $invalidDevices = Device::whereIn('id', $this->selectedDevices)
            ->where('status', 'RECEIVED')
            ->get();

        if ($invalidDevices->isNotEmpty()) {
            $deviceIds = $invalidDevices->pluck('device_id')->join(', ');

            Notification::make()
                ->danger()
                ->title('Transfer Failed')
                ->body("The following devices cannot be transferred: {$deviceIds}. They are in RECEIVED status.")
                ->send();

            return;
        }

        DB::transaction(function () {
            foreach ($this->selectedDevices as $deviceId) {
                $device = Device::find($deviceId);

                // Create transfer record
                Transfer::create([
                    'device_id' => $deviceId,
                    'device_serial' => $device->device_id,
                    'from_location' => $this->record->id,
                    'to_location' => $this->selectedAllocationPoint,
                    'status' => 'COMPLETED',
                    'transfer_type' => 'ALLOCATION',
                    'distribution_point_status' => $device->status,
                    'quantity' => 1,
                    'received' => true
                ]);

                // Update device location and status
                $device->update([
                    'allocation_point_id' => $this->selectedAllocationPoint,
                    'status' => 'RECEIVED'
                ]);
            }
        });

        $this->selectedDevices = [];
        $this->selectedAllocationPoint = null;
        $this->dispatch('close-allocation-modal');
        $this->devices = $this->getDevicesForAllocationPoint();

        Notification::make()
            ->success()
            ->title('Devices sent to allocation point successfully')
            ->send();
    }

    /**
     * Accept devices with RECEIVED status
     */
    public function collectDevices()
    {
        if (empty($this->selectedDevices)) {
            Notification::make()
                ->warning()
                ->title('No devices selected')
                ->send();
            return;
        }

        DB::transaction(function () {
            foreach ($this->selectedDevices as $deviceId) {
                $device = Device::find($deviceId);
                if (!$device || $device->status !== 'RECEIVED') continue;

                // Get the latest transfer record, whether distribution or allocation
                $transfer = Transfer::where('device_id', $deviceId)
                    ->where(function ($query) {
                        $query->where('transfer_type', 'DISTRIBUTION')
                            ->orWhere('transfer_type', 'ALLOCATION');
                    })
                    ->latest()
                    ->first();

                if ($transfer) {
                    // Handle based on transfer type
                    if ($transfer->transfer_type === 'DISTRIBUTION') {
                        $device->update([
                            'status' => $transfer->distribution_point_status,
                            'allocation_point_id' => $this->record->id
                        ]);
                    } else if ($transfer->transfer_type === 'ALLOCATION') {
                        $device->update([
                            'status' => $transfer->original_status ?? 'ONLINE',
                            'allocation_point_id' => $this->record->id
                        ]);
                    }
                } else {
                    // If no transfer record found, default to ONLINE
                    $device->update([
                        'status' => 'ONLINE',
                        'allocation_point_id' => $this->record->id
                    ]);
                }
            }

            $this->devices = $this->getDevicesForAllocationPoint();
        });

        $this->selectedDevices = [];
        $this->selectAll = false;

        Notification::make()
            ->title('Devices collected and status updated successfully.')
            ->success()
            ->send();
    }

    /**
     * Send devices to another allocation point
     */
    public function sendToAllocationPoint()
    {
        $this->dispatch('open-allocation-modal');
    }

    /**
     * Return devices to inventory
     */
    public function returnDeviceToInventory()
    {
        if (empty($this->selectedDevices)) {
            Notification::make()
                ->warning()
                ->title('No devices selected')
                ->send();
            return;
        }

        // Check for devices with RECEIVED status
        $receivedDevices = Device::whereIn('id', $this->selectedDevices)
            ->where('status', 'RECEIVED')
            ->get();

        if ($receivedDevices->isNotEmpty()) {
            Notification::make()
                ->warning()
                ->title('Cannot return devices with RECEIVED status')
                ->body('Please accept the devices first before returning them to inventory.')
                ->send();
            return;
        }

        DB::transaction(function () {
            foreach ($this->selectedDevices as $deviceId) {
                $device = Device::find($deviceId);

                // Create transfer record
                Transfer::create([
                    'device_id' => $deviceId,
                    'device_serial' => $device->device_id,
                    'from_location' => $this->record->id,
                    'to_location' => null,
                    'status' => 'COMPLETED',
                    'transfer_type' => 'ALLOCATION',
                    'distribution_point_status' => $device->status,
                    'quantity' => 1,
                    'received' => true
                ]);

                // Update device location
                $device->update([
                    'distribution_point_id' => null,
                    'allocation_point_id' => null
                ]);
            }

            $this->devices = $this->getDevicesForAllocationPoint();
        });

        $this->selectedDevices = [];
        $this->selectAll = false;

        Notification::make()
            ->title('Devices returned to inventory successfully.')
            ->success()
            ->send();
    }

    public function openChangeStatusModal()
    {
        if (empty($this->selectedDevices)) {
            $this->errorMessage = "Please select at least one device.";
            return;
        }

        $this->dispatch('open-status-modal');
        $this->errorMessage = null;
    }

    public function closeChangeStatusModal()
    {
        $this->dispatch('close-status-modal');
        $this->selectedStatus = null;
        $this->errorMessage = null;
    }

    public function changeDeviceStatus()
    {
        // Validation
        if (empty($this->selectedDevices) || !$this->selectedStatus) {
            $this->errorMessage = "Please select devices and a status.";
            return;
        }

        // Check for devices with RECEIVED status
        $receivedDevices = Device::whereIn('id', $this->selectedDevices)
            ->where('status', 'RECEIVED')
            ->get();

        if ($receivedDevices->isNotEmpty()) {
            $this->errorMessage = "Cannot change status of devices with RECEIVED status.";
            return;
        }

        // Update device statuses
        DB::transaction(function () {
            Device::whereIn('id', $this->selectedDevices)
                ->update(['status' => $this->selectedStatus]);
        });

        // Close modal and reset
        $this->dispatch('close-status-modal');
        $this->selectedStatus = null;
        $this->selectedDevices = [];
        $this->selectAll = false;
        $this->devices = $this->getDevicesForAllocationPoint();

        Notification::make()
            ->title('Device status updated successfully.')
            ->success()
            ->send();
    }

    protected function getStatusCounts()
    {
        $statusCounts = [
            '35CM LOCKING CABLES' => ['OK' => 0, 'DAMAGED' => 0, 'LOST' => 0, 'TOTAL' => 0],
            '3 METERS LOCKING CABLES' => ['OK' => 0, 'DAMAGED' => 0, 'LOST' => 0, 'TOTAL' => 0],
        ];

        $devices = Device::where('allocation_point_id', $this->record->id)->get();

        foreach ($devices as $device) {
            if ($device->device_type === '35CM LOCKING CABLES') {
                $statusCounts['35CM LOCKING CABLES'][$device->status] = ($statusCounts['35CM LOCKING CABLES'][$device->status] ?? 0) + 1;
                $statusCounts['35CM LOCKING CABLES']['TOTAL']++;
            } elseif ($device->device_type === '3 METERS LOCKING CABLES') {
                $statusCounts['3 METERS LOCKING CABLES'][$device->status] = ($statusCounts['3 METERS LOCKING CABLES'][$device->status] ?? 0) + 1;
                $statusCounts['3 METERS LOCKING CABLES']['TOTAL']++;
            }
        }

        return $statusCounts;
    }

    public function filterByStatus($status)
    {
        // If the clicked status is the same as the currently active one, clear the filter
        if ($this->selectedStatus === $status) {
            $this->selectedStatus = null;
        } else {
            // Otherwise, set the filter to only show the clicked status
            $this->selectedStatus = $status;
        }

        // Refresh devices list with new filter
        $this->devices = $this->getDevicesForAllocationPoint();
    }

    public function clearStatusFilter()
    {
        $this->selectedStatus = null;
        $this->devices = $this->getDevicesForAllocationPoint();
    }

    protected function getDeviceStatusCounts()
    {
        $counts = [];
        $allDevices = Device::where('allocation_point_id', $this->record->id)->get();

        foreach ($this->statuses as $status) {
            $count = $allDevices->where('status', $status)->count();

            $color = match($status) {
                'ONLINE' => 'success',
                'OFFLINE' => 'danger',
                'DAMAGED' => 'warning',
                'FIXED' => 'info',
                'LOST' => 'gray',
                'RECEIVED' => 'purple',
                default => 'gray'
            };

            $counts[$status] = [
                'count' => $count,
                'color' => $color
            ];
        }

        return $counts;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Add any widgets you need here
        ];
    }

    protected function getViewData(): array
    {
        $data = parent::getViewData();
        
        // Add badge data
        $data['receivedBadge'] = [
            'count' => \App\Models\AllocationPoint::getCachedReceivedCount($this->record->id),
            'color' => \App\Models\AllocationPoint::getReceivedBadgeColor($this->record->id)
        ];
        
        $data['damagedBadge'] = [
            'count' => \App\Models\AllocationPoint::getCachedDamagedCount($this->record->id),
            'color' => \App\Models\AllocationPoint::getDamagedBadgeColor($this->record->id)
        ];
        
        // Add combined badge data
        $data['combinedBadge'] = [
            'text' => \App\Models\AllocationPoint::getBadgeText($this->record->id),
            'color' => \App\Models\AllocationPoint::getBadgeColor($this->record->id),
            'tooltip' => \App\Models\AllocationPoint::getBadgeWithTooltip($this->record->id)['tooltip'],
            'html' => \App\Models\AllocationPoint::getDualColorBadgeHtml($this->record->id)
        ];
        
        $data['device_status_counts'] = $this->getDeviceStatusCounts();
        $data['selectedStatus'] = $this->selectedStatus;
        $data['statuses'] = $this->statuses;
        
        return $data;
    }

    protected function getDevicesForAllocationPoint()
    {
        $query = Device::where('allocation_point_id', $this->record->id);

        if ($this->selectedStatus) {
            $query->where('status', $this->selectedStatus);
        }

        if ($this->search) {
            $query->where(function ($query) {
                $query->where('device_id', 'like', '%' . $this->search . '%')
                    ->orWhere('device_type', 'like', '%' . $this->search . '%')
                    ->orWhere('batch_number', 'like', '%' . $this->search . '%')
                    ->orWhere('status', 'like', '%' . $this->search . '%')
                    ->orWhere('sim_number', 'like', '%' . $this->search . '%')
                    ->orWhere('sim_operator', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%');
                    });
            });
        }

        return $query->get();
    }

    public function updatedSearch()
    {
        $this->isSearching = strlen($this->search) > 0;
        $this->devices = $this->getDevicesForAllocationPoint();
    }

    public function performSearch()
    {
        $this->isSearching = strlen($this->search) > 0;
        $this->devices = $this->getDevicesForAllocationPoint();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->isSearching = false;
        $this->devices = $this->getDevicesForAllocationPoint();
    }

    public function resetFilters()
    {
        $this->selectedStatus = null;
        $this->search = '';
        $this->isSearching = false;
        $this->devices = $this->getDevicesForAllocationPoint();
    }
}
















