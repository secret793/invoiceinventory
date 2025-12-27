<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class LiveTimerWidget extends Widget
{
    protected static string $view = 'filament.widgets.live-timer-widget';
    
    // Widget configuration
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = -1; // Display at top

    /**
     * Get server time to pass to view
     * 
     * Returns an array with:
     * - timestamp: Unix timestamp in milliseconds (for JavaScript)
     * - formatted: Human-readable format (YYYY-MM-DD HH:mm:ss)
     * - timezone: Server timezone from config
     */
    public function getServerTime(): array
    {
        $now = now();
        
        return [
            'timestamp' => $now->timestamp * 1000, // JavaScript uses milliseconds
            'formatted' => $now->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone', 'UTC'),
        ];
    }
}
