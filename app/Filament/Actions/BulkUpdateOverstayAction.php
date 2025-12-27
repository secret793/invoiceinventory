<?php

namespace App\Filament\Actions;

use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BulkUpdateOverstayAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'bulk_update_overstay';
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->label('Update Overstay to Current Time')
            ->icon('heroicon-o-clock')
            ->color('warning')
            ->action(function (Collection $records): void {
                try {
                    DB::beginTransaction();

                    $updated = 0;
                    $errors = [];

                    foreach ($records as $record) {
                        try {
                            // Skip records that are already RETRIEVED
                            if ($record->retrieval_status === 'RETRIEVED') {
                                Log::info('Skipping bulk overstay update - device already retrieved', [
                                    'device_retrieval_id' => $record->id,
                                    'device_id' => $record->device_id,
                                    'retrieval_status' => $record->retrieval_status,
                                    'timestamp' => now()->toDateTimeString()
                                ]);
                                $deviceId = $record->device?->device_id ?? 'N/A';
                                $errors[] = "Device $deviceId: Device already retrieved - cannot update overstay";
                                continue;
                            }
                            
                            // Get current time
                            $currentTime = now();
                            
                            // Get grace period based on route type
                            $gracePeriodSeconds = $record->long_route_id ? (48 * 3600) : (24 * 3600);
                            
                            // Get affixing time
                            $affixingTime = $record->affixing_date;
                            
                            if (!$affixingTime) {
                                $deviceId = $record->device?->device_id ?? 'N/A';
                                $errors[] = "Device $deviceId: No affixing date found";
                                continue;
                            }
                            
                            // Calculate overstay days
                            $totalSeconds = $currentTime->diffInSeconds($affixingTime);
                            $overstaySeconds = $totalSeconds - $gracePeriodSeconds;
                            $overstayDays = (int) ceil($overstaySeconds / 86400);
                            
                            // Ensure minimum is 0
                            $overstayDays = max(0, $overstayDays);
                            
                            // Calculate overstay amount
                            $overstayAmount = $overstayDays * 1000;
                            
                            // Update the record through the model to trigger observers
                            $record->update([
                                'overstay_days' => $overstayDays,
                                'overstay_amount' => $overstayAmount,
                                'updated_at' => now(),
                            ]);
                            
                            $updated++;
                            
                            Log::info('Bulk overstay update successful', [
                                'device_retrieval_id' => $record->id,
                                'device_id' => $record->device_id,
                                'affixing_date' => $affixingTime->toDateTimeString(),
                                'current_time' => $currentTime->toDateTimeString(),
                                'grace_period_seconds' => $gracePeriodSeconds,
                                'overstay_days' => $overstayDays,
                                'overstay_amount' => $overstayAmount,
                                'timestamp' => now()->toDateTimeString(),
                            ]);
                        } catch (\Exception $e) {
                            $deviceId = $record->device?->device_id ?? 'N/A';
                            $errors[] = "Device $deviceId: {$e->getMessage()}";
                            Log::error('Bulk overstay update failed for single record', [
                                'device_retrieval_id' => $record->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                        }
                    }

                    DB::commit();

                    // Show success notification
                    if ($updated > 0) {
                        $message = "Successfully updated overstay for {$updated} record" . ($updated > 1 ? 's' : '') . '.';
                        
                        if (!empty($errors)) {
                            $message .= ' Failed: ' . count($errors) . ' record' . (count($errors) > 1 ? 's' : '') . '.';
                        }
                        
                        Notification::make()
                            ->success()
                            ->title('Bulk Update Complete')
                            ->body($message)
                            ->send();
                        
                        Log::info('Bulk overstay update completed', [
                            'total_records' => $records->count(),
                            'updated_count' => $updated,
                            'error_count' => count($errors),
                            'timestamp' => now()->toDateTimeString(),
                        ]);
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Update Failed')
                            ->body('No records were updated. Errors: ' . implode('; ', $errors))
                            ->send();
                    }
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Bulk overstay update transaction failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    Notification::make()
                        ->danger()
                        ->title('Error')
                        ->body('Failed to update overstay records: ' . $e->getMessage())
                        ->send();
                }
            })
            ->requiresConfirmation()
            ->modalHeading('Update Overstay to Current Time')
            ->modalDescription('This will recalculate overstay days and amount for all selected records based on the current server time.')
            ->modalSubmitActionLabel('Update')
            ->visible(fn () => auth()->user()?->hasAnyRole([
                'Super Admin',
                'Warehouse Manager',
                'Finance Officer',
                'Affixing Officer',
            ]));
    }
}

