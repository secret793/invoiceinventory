<?php

namespace App\Filament\Resources\DistributionPointResource\Pages;

use App\Filament\Resources\DistributionPointResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\TextEntry;
use App\Models\DistributionPoint;
use App\Models\Device;
use Livewire\Component;
use Filament\Notifications\Notification;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

class ViewDistributionPoint extends ViewRecord
{
    protected static string $resource = DistributionPointResource::class;

    public $distributionPoint;
    public $devices;
    public $selectedDevices = [];
    public $errorMessage;
    public $selectAll = false;
    public $selectedStatuses = [];
    public $statuses = ['ONLINE', 'OFFLINE', 'DAMAGED', 'FIXED', 'LOST', 'RECEIVED'];
    public $showCollectButtons = false;
    public $showAssignButtons = false;
    public $selectedAllocationPoint = null;
    public $allocationPoints;
    public $search = '';
    protected $queryString = ['search'];
    public $isSearching = false;
    public $isChangeStatusModalOpen = false;
    public $selectedStatus = null;
    public $availableStatuses = [
        'ONLINE' => 'Online',
        'OFFLINE' => 'Offline',
        'DAMAGED' => 'Damaged',
        'FIXED' => 'Fixed',
        'LOST' => 'Lost',
    ];

    protected static string $view = 'filament.resources.distribution-point.pages.view-distribution-point';

    public function mount(string|int $record): void
    {
        parent::mount($record);
        $this->devices = $this->getDevicesForDistributionPoint();
        $this->distributionPoint = $this->record;
    }

    public function filterDevicesByStatus($status)
    {
        // If status is already selected, remove it, otherwise add it
        if (in_array($status, $this->selectedStatuses)) {
            $this->selectedStatuses = array_diff($this->selectedStatuses, [$status]);
        } else {
            $this->selectedStatuses[] = $status;
        }

        // Refresh devices list with new filter
        $this->devices = $this->getDevicesForDistributionPoint();
    }

    protected function getDeviceStatusCounts()
    {
        $statusCounts = [
            'ONLINE' => ['count' => 0, 'color' => 'success'],
            'OFFLINE' => ['count' => 0, 'color' => 'danger'],
            'DAMAGED' => ['count' => 0, 'color' => 'danger'],
            'FIXED' => ['count' => 0, 'color' => 'success'],
            'LOST' => ['count' => 0, 'color' => 'danger'],
            'RECEIVED' => ['count' => 0, 'color' => 'warning'],
        ];

        // Get counts for each status
        $counts = Device::where('distribution_point_id', $this->record->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Update counts in our status array
        foreach ($counts as $status => $count) {
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]['count'] = $count;
            }
        }

        return $statusCounts;
    }

    protected function getViewData(): array
    {
        $this->distributionPoint = DistributionPoint::find($this->record->id);
        $this->devices = $this->getDevicesForDistributionPoint();
        $this->allocationPoints = \App\Models\AllocationPoint::all();

        return [
            'status_counts' => $this->getStatusCounts(),
            'device_status_counts' => $this->getDeviceStatusCounts(),
            'selectedStatuses' => $this->selectedStatuses,
            'statuses' => $this->statuses,
            'showCollectButtons' => $this->showCollectButtons,
            'showAssignButtons' => $this->showAssignButtons,
            'allocationPoints' => $this->allocationPoints,
        ];
    }

    protected function getDevicesForDistributionPoint()
    {
        // Clear any cached queries
        DB::flushQueryLog();

        // Get fresh data from the database
        return Device::where('distribution_point_id', $this->record->id)
            ->when(!empty($this->selectedStatuses), function ($query) {
                return $query->whereIn('status', $this->selectedStatuses);
            })
            ->when($this->search, function ($query) {
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
            })
            ->select(
                'id',
                'device_id',
                'device_type',
                'batch_number',
                'date_received',
                'status',
                'user_id',
                'distribution_point_id',
                'sim_number',
                'sim_operator'
            )
            ->latest('updated_at') // Order by most recently updated
            ->get();
    }

    protected function getStatusCounts()
    {
        $statusCounts = [
            '35CM LOCKING CABLES' => ['OK' => 0, 'DAMAGED' => 0, 'LOST' => 0, 'TOTAL' => 0],
            '3 METERS LOCKING CABLES' => ['OK' => 0, 'DAMAGED' => 0, 'LOST' => 0, 'TOTAL' => 0],
        ];

        foreach ($this->devices as $device) {
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

    public function updatedSelectAll($value)
    {
        $this->selectedDevices = $value ? $this->devices->pluck('id')->toArray() : [];
    }

    public function collectDevices()
    {
        if (empty($this->selectedDevices)) {
            $this->errorMessage = "No devices selected.";
            return;
        }

        $successCount = 0;
        $errorCount = 0;

        DB::transaction(function () use (&$successCount, &$errorCount) {
            foreach ($this->selectedDevices as $deviceId) {
                $device = Device::find($deviceId);

                // Debug logging
                \Log::info('Processing device for collection', [
                    'device_id' => $deviceId,
                    'status' => $device ? $device->status : 'Device not found',
                    'distribution_point_id' => $this->record->id
                ]);

                if (!$device) {
                    \Log::error('Device not found', ['device_id' => $deviceId]);
                    $errorCount++;
                    continue;
                }

                if ($device->status !== 'RECEIVED') {
                    \Log::warning('Device not in RECEIVED status', [
                        'device_id' => $deviceId,
                        'current_status' => $device->status
                    ]);
                    $errorCount++;
                    continue;
                }

                // Get the latest transfer record, whether distribution or allocation
                $transfer = Transfer::where('device_id', $deviceId)
                    ->where(function ($query) {
                        $query->where('transfer_type', 'DISTRIBUTION')
                            ->orWhere('transfer_type', 'ALLOCATION');
                    })
                    ->latest()
                    ->first();

                \Log::info('Transfer record found', [
                    'transfer' => $transfer ? $transfer->toArray() : 'No transfer found',
                    'device_id' => $deviceId
                ]);

                // Even if no transfer is found, we should still update the device
                $newStatus = 'ONLINE'; // Default status if no transfer record

                if ($transfer) {
                    if ($transfer->transfer_type === 'DISTRIBUTION') {
                        $newStatus = $transfer->distribution_point_status ?? 'ONLINE';
                    } else if ($transfer->transfer_type === 'ALLOCATION') {
                        $newStatus = $transfer->original_status ?? 'ONLINE';
                    }
                }

                // Force update the device status directly with DB query to bypass any model events
                $updated = DB::table('devices')
                    ->where('id', $deviceId)
                    ->update([
                        'status' => $newStatus,
                        'distribution_point_id' => $this->record->id,
                        'allocation_point_id' => null,
                        'updated_at' => now()
                    ]);

                \Log::info('Device update result', [
                    'device_id' => $deviceId,
                    'new_status' => $newStatus,
                    'updated' => $updated ? 'Success' : 'Failed'
                ]);

                if ($updated) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }
        });

        // Refresh the devices list
        $this->devices = $this->getDevicesForDistributionPoint();
        $this->selectedDevices = [];
        $this->selectAll = false;

        if ($successCount > 0) {
            Notification::make()
                ->title("$successCount devices collected and status updated successfully.")
                ->success()
                ->send();
        }

        if ($errorCount > 0) {
            Notification::make()
                ->title("$errorCount devices could not be updated. Check logs for details.")
                ->warning()
                ->send();
        }
    }

    public function sendToAnotherDistributionPoint()
    {
        if (empty($this->selectedDevices) || !$this->selectedTargetDP) {
            $this->errorMessage = "Please select devices and a target distribution point.";
            return;
        }

        // Check for devices that can't be transferred
        $invalidDevices = Device::whereIn('id', $this->selectedDevices)
            ->where(function ($query) {
                $query->where('status', 'RECEIVED')
                    ->orWhereHas('transfers', function ($query) {
                        $query->where('status', 'PENDING');
                    });
            })
            ->get();

        if ($invalidDevices->isNotEmpty()) {
            $deviceIds = $invalidDevices->pluck('device_id')->join(', ');

            Notification::make()
                ->title('Transfer Failed')
                ->body("The following devices cannot be transferred: {$deviceIds}. They are either in RECEIVED status or have pending transfers.")
                ->danger()
                ->send();

                return;
        }

        DB::transaction(function () {
            foreach ($this->selectedDevices as $deviceId) {
                $device = Device::find($deviceId);

                // Double check that device isn't already being transferred
                if ($device->transfers()->where('status', 'PENDING')->exists()) {
                    continue;
                }

                Transfer::create([
                    'device_id' => $deviceId,
                    'device_serial' => $device->device_id,
                    'from_location' => $this->record->id,
                    'to_location' => $this->selectedTargetDP,
                    'status' => 'COMPLETED',
                    'transfer_type' => 'DISTRIBUTION',
                    'distribution_point_status' => $device->status,
                    'quantity' => 1,
                    'received' => true
                ]);

                $device->update([
                    'distribution_point_id' => $this->selectedTargetDP,
                    'status' => 'RECEIVED'
                ]);
            }
        });

        $this->selectedDevices = [];
        $this->selectAll = false;
        $this->selectedTargetDP = null;

        Notification::make()
            ->title('Devices transferred successfully')
            ->success()
            ->send();
    }

    public function returnDeviceToInventory()
    {
        if (empty($this->selectedDevices)) {
            $this->errorMessage = "No devices selected.";
            return;
        }

        foreach ($this->selectedDevices as $deviceId) {
            $device = Device::find($deviceId);
            if ($device->status === 'RECEIVED') {
                $this->errorMessage = "Device with ID $deviceId has status RECEIVED and cannot be returned to inventory.";
                return;
            }
        }

        DB::transaction(function () {
            foreach ($this->selectedDevices as $deviceId) {
                $device = Device::find($deviceId);
                Transfer::create([
                    'device_id' => $deviceId,
                    'device_serial' => $device->device_id,
                    'from_location' => $this->record->id,
                    'to_location' => null,
                    'status' => 'COMPLETED',
                    'transfer_type' => 'DISTRIBUTION',
                    'distribution_point_status' => $device->status,
                    'quantity' => 1,
                    'received' => true
                ]);

                $device->update([
                    'distribution_point_id' => null,
                    'allocation_point_id' => null
                ]);
            }

            $this->devices = $this->getDevicesForDistributionPoint();
        });

        $this->selectedDevices = [];
        $this->selectAll = false;

        Notification::make()
            ->title('Devices returned to inventory successfully.')
            ->success()
            ->send();
    }

    public function sendToAllocationPoint()
    {
        // Use dispatch instead of dispatchBrowserEvent
        $this->dispatch('open-allocation-modal');
    }

    public function confirmSendToAllocationPoint()
    {
        if (empty($this->selectedDevices) || !$this->selectedAllocationPoint) {
            $this->errorMessage = "Please select devices and a target allocation point.";
            return;
        }

        foreach ($this->selectedDevices as $deviceId) {
            $device = Device::find($deviceId);
            if ($device->status === 'RECEIVED') {
                $this->errorMessage = "Device with ID $deviceId has status RECEIVED and cannot be sent to allocation point.";
                $this->dispatch('close-allocation-modal');
                return;
            }
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
                    'distribution_point_id' => null,
                    'allocation_point_id' => $this->selectedAllocationPoint,
                    'status' => 'RECEIVED'
                ]);
            }
        });

        $this->selectedDevices = [];
        $this->selectedAllocationPoint = null;
        $this->devices = $this->getDevicesForDistributionPoint();
        $this->dispatch('close-allocation-modal');

        Notification::make()
            ->title('Devices sent to allocation point successfully.')
            ->success()
            ->send();
    }

    public function determineButtonVisibility(): void
    {
        $hasReceivedDevices = $this->devices->contains('status', 'RECEIVED');
        $this->showCollectButtons = $hasReceivedDevices;
        $this->showAssignButtons = !$hasReceivedDevices;
    }

    public function acceptReturnedDevice()
    {
        if (empty($this->selectedDevices)) {
            $this->errorMessage = "No devices selected.";
            return;
        }

        DB::transaction(function () {
            // Get all pending devices in one query
            $pendingDevices = DB::table('devices')
                ->whereIn('id', $this->selectedDevices)
                ->where('status', 'PENDING')
                ->where('distribution_point_id', $this->record->id)
                ->pluck('id');

            if ($pendingDevices->isEmpty()) {
                $this->errorMessage = "No pending devices found to accept.";
                return;
            }

            // Get latest transfers in one query
            $latestTransfers = DB::table('transfers')
                ->whereIn('device_id', $pendingDevices)
                ->orderBy('created_at', 'desc')
                ->get()
                ->keyBy('device_id');

            // Process each device
            foreach ($pendingDevices as $deviceId) {
                $transfer = $latestTransfers->get($deviceId);
                $status = $transfer ? ($transfer->original_status ?? 'ONLINE') : 'ONLINE';

                // Update device status
                DB::table('devices')
                    ->where('id', $deviceId)
                    ->update([
                        'status' => $status,
                        'distribution_point_id' => $this->record->id,
                        'allocation_point_id' => null,
                        'updated_at' => now()
                    ]);

                // Delete the device retrieval record
                DB::table('device_retrievals')
                    ->where('device_id', $deviceId)
                    ->delete();
            }

            // Refresh device list efficiently
            $this->devices = $this->getDevicesForDistributionPoint();
        });

        $this->selectedDevices = [];
        $this->selectAll = false;

        Notification::make()
            ->title('Returned devices accepted and retrieval records cleared.')
            ->success()
            ->send();
    }

    public function cancelReturnToOutstation()
    {
        if (empty($this->selectedDevices)) {
            Notification::make()
                ->warning()
                ->title('No devices selected')
                ->send();
            return;
        }

        DB::transaction(function () {
            // Get all pending devices in one query
            $pendingDevices = DB::table('devices')
                ->whereIn('id', $this->selectedDevices)
                ->where('status', 'PENDING')
                ->where('distribution_point_id', $this->record->id)
                ->pluck('id');

            if ($pendingDevices->isEmpty()) {
                Notification::make()
                    ->warning()
                    ->title('No pending devices found to cancel')
                    ->send();
                return;
            }

            foreach ($pendingDevices as $deviceId) {
                // Get the device retrieval record
                $deviceRetrieval = DB::table('device_retrievals')
                    ->where('device_id', $deviceId)
                    ->where('retrieval_status', 'RETURNED')
                    ->first();

                if ($deviceRetrieval) {
                    // Revert device status and location
                    DB::table('devices')
                        ->where('id', $deviceId)
                        ->update([
                            'status' => 'ONLINE',
                            'distribution_point_id' => $deviceRetrieval->distribution_point_id,
                            'updated_at' => now()
                        ]);

                    // Update device retrieval status
                    DB::table('device_retrievals')
                        ->where('device_id', $deviceId)
                        ->update([
                            'retrieval_status' => 'RETRIEVED',
                            'transfer_status' => 'pending',
                            'updated_at' => now()
                        ]);
                }
            }

            // Refresh the devices list
            $this->devices = $this->getDevicesForDistributionPoint();
        });

        $this->selectedDevices = [];
        $this->selectAll = false;

        Notification::make()
            ->success()
            ->title('Return to outstation cancelled')
            ->send();
    }

    public function rejectReturnedDevice()
    {
        if (empty($this->selectedDevices)) {
            Notification::make()
                ->warning()
                ->title('No devices selected')
                ->send();
            return;
        }

        DB::transaction(function () {
            // Get all pending devices in one query
            $pendingDevices = DB::table('devices')
                ->whereIn('id', $this->selectedDevices)
                ->where('status', 'PENDING')
                ->where('distribution_point_id', $this->record->id)
                ->pluck('id');

            if ($pendingDevices->isEmpty()) {
                Notification::make()
                    ->warning()
                    ->title('No pending devices found to reject')
                    ->send();
                return;
            }

            foreach ($pendingDevices as $deviceId) {
                // Get the device retrieval record
                $deviceRetrieval = DB::table('device_retrievals')
                    ->where('device_id', $deviceId)
                    ->where('retrieval_status', 'RETURNED')
                    ->first();

                if ($deviceRetrieval) {
                    // Revert device status and location to original distribution point
                    DB::table('devices')
                        ->where('id', $deviceId)
                        ->update([
                            'status' => 'RETRIEVED',
                            'distribution_point_id' => null,
                            'updated_at' => now()
                        ]);

                    // Update device retrieval status back to RETRIEVED
                    DB::table('device_retrievals')
                        ->where('device_id', $deviceId)
                        ->update([
                            'retrieval_status' => 'RETRIEVED',
                            'transfer_status' => 'pending',
                            'updated_at' => now()
                        ]);
                }
            }

            // Refresh the devices list
            $this->devices = $this->getDevicesForDistributionPoint();
        });

        $this->selectedDevices = [];
        $this->selectAll = false;

        Notification::make()
            ->success()
            ->title('Devices rejected and returned')
            ->send();
    }

    public function updatedSearch()
    {
        $this->isSearching = strlen($this->search) > 0;
        $this->devices = $this->getDevicesForDistributionPoint();
    }

    public function performSearch()
    {
        $this->isSearching = strlen($this->search) > 0;
        $this->devices = $this->getDevicesForDistributionPoint();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->isSearching = false;
        $this->devices = $this->getDevicesForDistributionPoint();
    }

    public function openChangeStatusModal()
    {
        $this->dispatch('open-status-modal');
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

        // Reset selections
        $this->selectedDevices = [];
        $this->selectAll = false;
        $this->selectedStatus = null;

        // Close modal
        $this->dispatch('close-status-modal');

        // Refresh devices list
        $this->devices = $this->getDevicesForDistributionPoint();

        // Show success notification
        Notification::make()
            ->title('Device status updated successfully')
            ->success()
            ->send();
    }
}





