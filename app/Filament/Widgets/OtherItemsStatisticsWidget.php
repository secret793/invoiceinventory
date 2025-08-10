<?php
namespace App\Filament\Widgets;

use App\Models\OtherItem;
use Filament\Widgets\Widget;

class OtherItemsStatisticsWidget extends Widget
{
    protected static string $view = 'filament.widgets.other-items-statistics';

    public function getStats(): array
    {
        return [
            '35cm' => [
                'OK' => OtherItem::where('description', '35cm locking table')->where('status', 'OK')->count(),
                'Damaged' => OtherItem::where('description', '35cm locking table')->where('status', 'DAMAGED')->count(),
                'Lost' => OtherItem::where('description', '35cm locking table')->where('status', 'LOST')->count(),
                'Total' => OtherItem::where('description', '35cm locking table')->count(),
                'Distributed' => OtherItem::where('description', '35cm locking table')->where('is_distributed', true)->count(),
                'Allocated' => OtherItem::where('description', '35cm locking table')->where('is_allocated', true)->count(),
                'Assigned' => OtherItem::where('description', '35cm locking table')->where('is_assigned', true)->count(),
                'Remaining' => OtherItem::where('description', '35cm locking table')->where('status', 'OK')->count(),
            ],
            '3m' => [
                'OK' => OtherItem::where('description', '3 meters locking table')->where('status', 'OK')->count(),
                'Damaged' => OtherItem::where('description', '3 meters locking table')->where('status', 'DAMAGED')->count(),
                'Lost' => OtherItem::where('description', '3 meters locking table')->where('status', 'LOST')->count(),
                'Total' => OtherItem::where('description', '3 meters locking table')->count(),
                'Distributed' => OtherItem::where('description', '3 meters locking table')->where('is_distributed', true)->count(),
                'Allocated' => OtherItem::where('description', '3 meters locking table')->where('is_allocated', true)->count(),
                'Assigned' => OtherItem::where('description', '3 meters locking table')->where('is_assigned', true)->count(),
                'Remaining' => OtherItem::where('description', '3 meters locking table')->where('status', 'OK')->count(),
            ]
        ];
    }
}
