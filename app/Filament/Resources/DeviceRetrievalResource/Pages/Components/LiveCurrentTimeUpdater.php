<?php

namespace App\Filament\Resources\DeviceRetrievalResource\Pages\Components;

use Livewire\Component;
use App\Models\DeviceRetrieval;

class LiveCurrentTimeUpdater extends Component
{
    public $deviceRetrievalId;
    public $currentTime;

    protected $listeners = ['refreshCurrentTime' => '$refresh'];

    public function mount($deviceRetrievalId)
    {
        $this->deviceRetrievalId = $deviceRetrievalId;
        $this->updateCurrentTime();
    }

    public function updateCurrentTime()
    {
        $deviceRetrieval = DeviceRetrieval::find($this->deviceRetrievalId);
        if ($deviceRetrieval) {
            $this->currentTime = $deviceRetrieval->current_time?->diffForHumans();
        }
    }

    public function render()
    {
        return view('filament.resources.device-retrieval-resource.pages.components.live-current-time-updater');
    }
}
