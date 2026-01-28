<?php

namespace App\Filament\Actions;

use App\Models\DeviceRetrieval;
use App\Models\Invoice;
use App\Models\Route;
use App\Models\LongRoute;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OverdueBillsAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->name('createOverdueBills')
            ->label('Overdue Bills')
            ->icon('heroicon-o-document-text')
            ->color('danger')
            ->form(function ($record) {
                // Fetch related device retrieval details
                $deviceRetrieval = DeviceRetrieval::findOrFail($record->id);
                $device = $deviceRetrieval->device;

                // Get all routes and combine them with their types
                $allRoutes = [];
                
                // Add short routes
                $shortRoutes = Route::orderBy('name')->get();
                foreach ($shortRoutes as $route) {
                    $allRoutes["short_{$route->id}"] = "Short Route: {$route->name}";
                }
                
                // Add long routes
                $longRoutes = LongRoute::orderBy('name')->get();
                foreach ($longRoutes as $route) {
                    $allRoutes["long_{$route->id}"] = "Long Route: {$route->name}";
                }

                // Determine the current route
                $currentRoute = null;
                if ($deviceRetrieval->route_id) {
                    $currentRoute = "short_{$deviceRetrieval->route_id}";
                } elseif ($deviceRetrieval->long_route_id) {
                    $currentRoute = "long_{$deviceRetrieval->long_route_id}";
                }

                // Map allocation point names to customs post codes
                $customsPostCode = match($deviceRetrieval->allocationPoint?->name) {
                    'Amdallai' => 'GMAMD',
                    'Banjul' => 'GMBJL',
                    'BASSE' => 'GMBSS',
                    'FARAFENNI' => 'GMFRN',
                    'JIBORO' => 'GMJIB',
                    'MANDINARY' => 'GMMDR',
                    default => 'Unknown'
                };

                // Calculate dynamic penalty amount based on overstay days
                $penaltyAmount = $deviceRetrieval->overstay_days * 1000;

                return [
                    Forms\Components\Section::make('Payment Information')
                        ->schema([
                            Forms\Components\TextInput::make('reference_number')
                                ->label('Reference Number')
                                ->default('OVR-' . now()->format('YmdHis'))
                                ->required()
                                ->maxLength(255)
                                ->unique(Invoice::class, 'reference_number')
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('total_amount')
                                ->label('Total Amount')
                                ->default($deviceRetrieval->overstay_amount)
                                ->numeric()
                                ->prefix('D')
                                ->required()
                                ->disabled()
                                ->dehydrated(), // This ensures the field is included in form submission
                            
                            Forms\Components\TextInput::make('paid_by')
                                ->label('Paid By')
                                ->default($deviceRetrieval->driver_name)
                                ->required()
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('received_by')
                                ->label('Received By')
                                ->default(auth()->user()->name)
                                ->required()
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(),
                        ]),

                    Forms\Components\Section::make('Device Details')
                        ->schema([
                            Forms\Components\TextInput::make('device_number')
                                ->label('Device ID')
                                ->default($device?->device_id)
                                ->required()
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('asset_number')
                                ->label('Vehicle Number')
                                ->default($deviceRetrieval->vehicle_number)
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('driver_name')
                                ->label('Driver Name')
                                ->default($deviceRetrieval->driver_name)
                                ->required()
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('departure')
                                ->label('Allocation Point')
                                ->default($deviceRetrieval->allocationPoint?->name ?? 'Unknown')
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('destination')
                                ->label('Destination')
                                ->default($deviceRetrieval->destination)
                                ->required()
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\Select::make('selected_route')
                                ->label('Route (Optional)')
                                ->options($allRoutes)
                                ->default($currentRoute)
                                ->searchable()
                                ->helperText('Select a route if needed')
                                ->disabled()
                                ->dehydrated()
                                ->placeholder('Select a route (optional)'),
                            
                            Forms\Components\Hidden::make('overstay_days')
                                ->default($deviceRetrieval->overstay_days),
                            
                            Forms\Components\TextInput::make('overstay_days_display')
                                ->label('Overstay Days')
                                ->default($deviceRetrieval->overstay_days)
                                ->disabled()
                                ->dehydrated(false),
                            
                            Forms\Components\TextInput::make('penalty_amount')
                                ->label('Penalty Amount Per Day')
                                ->default($penaltyAmount)
                                ->numeric()
                                ->prefix('D')
                                ->required()
                                ->disabled()
                                ->dehydrated(),
                        ]),

                    Forms\Components\Section::make('Additional Information')
                        ->schema([
                            Forms\Components\TextInput::make('sad_boe')
                                ->label('SAD/BOE Number')
                                ->default($deviceRetrieval->boe)
                                ->required()
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('customs_post')
                                ->label('E-Tracking/Customs Post')
                                ->default($customsPostCode)
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('regime')
                                ->label('Regime')
                                ->default($deviceRetrieval->regime)
                                ->required()
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\TextInput::make('consignee')
                                ->label('Consignee')
                                ->maxLength(255),
                            
                            Forms\Components\TextInput::make('agent')
                                ->label('Agent Name')
                                ->default($deviceRetrieval->agent_contact)
                                ->required()
                                ->maxLength(255)
                                ->dehydrated(),
                            
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->default(fn () => "Overstay payment for device " . 
                                    ($device?->device_id ?? '') . 
                                    " for {$deviceRetrieval->overstay_days} days")
                                ->maxLength(500),
                        ])
                ];
            })
            ->action(function (array $data, $record) {
                try {
                    DB::beginTransaction();

                    // Validate device retrieval exists
                    $deviceRetrieval = DeviceRetrieval::findOrFail($record->id);
                    if (!$deviceRetrieval) {
                        throw new \Exception('Device retrieval record not found.');
                    }

                    // Use fixed logo path from public folder
                    $logoPath = 'images/logos/right-logo.jpeg';

                    // Get the selected route if any
                    $selectedRoute = $data['selected_route'] ?? null;
                    $routeId = null;
                    $longRouteId = null;
                    
                    if ($selectedRoute) {
                        // Extract route type and ID
                        [$type, $id] = explode('_', $selectedRoute, 2);
                        if ($type === 'short') {
                            $routeId = $id;
                        } else {
                            $longRouteId = $id;
                        }
                    }

                    // Create invoice record
                    $invoice = Invoice::create([
                        'device_retrieval_id' => $deviceRetrieval->id,
                        'reference_number' => $data['reference_number'],
                        'reference_date' => now(),
                        'sad_boe' => $data['sad_boe'],
                        'customs_post' => $data['customs_post'] ?? null,
                        'regime' => $data['regime'],
                        'consignee' => $data['consignee'] ?? null,
                        'agent' => $data['agent'],
                        'driver_name' => $data['driver_name'] ?? null,
                        'departure' => $data['departure'] ?? null,
                        'destination' => $data['destination'] ?? null,
                        'device_number' => $data['device_number'],
                        'asset_number' => $data['asset_number'] ?? null,
                        'route' => $data['route'] ?? null,
                        'overstay_days' => $data['overstay_days'],
                        'penalty_amount' => $data['penalty_amount'],
                        'total_amount' => $data['total_amount'],
                        'description' => $data['description'] ?? null,
                        'paid_by' => $data['paid_by'],
                        'received_by' => $data['received_by'],
                        'logo_path' => $logoPath,
                        'status' => 'PD', // Auto-set as Paid when generated
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                    ]);

                    DB::commit();

                    Notification::make()
                        ->title('Invoice Created Successfully')
                        ->body('The invoice has been created and marked as paid.')
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    DB::rollBack();

                    Log::error('Invoice creation failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'record_id' => $record->id,
                        'data' => $data,
                    ]);

                    Notification::make()
                        ->title('Failed to Create Invoice')
                        ->body('Error: ' . $e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();
                }
            })
            ->requiresConfirmation()
            ->modalHeading(fn (DeviceRetrieval $record): string => 
                'Create Overdue Bill for Device ' . $record->device?->device_id
            )
            ->modalDescription(function (DeviceRetrieval $record): string {
                return "This device is overstayed by {$record->overstay_days} days with a penalty amount of D{$record->overstay_amount}. Please complete the payment details.";
            })
            ->visible(function (DeviceRetrieval $record): bool {
                return $record->overstay_days >= 1 &&
                    $record->payment_status !== 'PD' &&
                    !$record->isWaived() &&
                    auth()->user()?->hasAnyRole([
                        'Super Admin',
                        'Warehouse Manager',
                        'Retrieval Officer'
                    ]);
            });
    }
}