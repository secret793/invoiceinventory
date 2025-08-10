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
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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

                return [
                    Forms\Components\Section::make('Payment Information')
                        ->schema([
                            Forms\Components\TextInput::make('reference_number')
                                ->label('Reference Number')
                                ->default('INV-' . now()->format('YmdHis'))
                                ->required()
                                ->maxLength(255)
                                ->unique(Invoice::class, 'reference_number'),
                            
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
                                ->default(auth()->user()->name)
                                ->required()
                                ->maxLength(255),
                            
                            Forms\Components\TextInput::make('received_by')
                                ->label('Received By')
                                ->default('Finance Department')
                                ->required()
                                ->maxLength(255),
                        ]),

                    Forms\Components\Section::make('Device Details')
                        ->schema([
                            Forms\Components\TextInput::make('device_number')
                                ->label('Device Number')
                                ->default($device?->device_id)
                                ->required()
                                ->disabled()
                                ->dehydrated(),
                            
                            Forms\Components\Select::make('selected_route')
                                ->label('Route (Optional)')
                                ->options($allRoutes)
                                ->default($currentRoute)
                                ->searchable()
                                ->helperText('Select a route if needed')
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
                                ->default(1000)
                                ->numeric()
                                ->prefix('D')
                                ->required()
                                ->rules(['numeric', 'min:0']),
                        ]),

                    Forms\Components\Section::make('Additional Information')
                        ->schema([
                            Forms\Components\TextInput::make('sad_boe')
                                ->label('SAD/BOE Number')
                                ->default($deviceRetrieval->boe)
                                ->required()
                                ->maxLength(255),
                            
                            Forms\Components\TextInput::make('regime')
                                ->label('Regime')
                                ->default($deviceRetrieval->regime)
                                ->required()
                                ->maxLength(255),
                            
                            Forms\Components\TextInput::make('agent')
                                ->label('Agent')
                                ->default($deviceRetrieval->agent_contact)
                                ->required()
                                ->maxLength(255),
                            
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->default(fn () => "Overstay payment for device " . 
                                    ($device?->device_id ?? '') . 
                                    " for {$deviceRetrieval->overstay_days} days")
                                ->maxLength(500),
                            
                            Forms\Components\FileUpload::make('logo')
                                ->label('Invoice Logo')
                                ->image()
                                ->imageResizeMode('contain')
                                ->imageCropAspectRatio('16:9')
                                ->imageResizeTargetWidth('1920')
                                ->imageResizeTargetHeight('1080')
                                ->directory('invoice-logos')
                                ->preserveFilenames()
                                ->maxSize(5120)
                                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
                                ->helperText('Supported formats: JPEG, PNG, GIF. Max size: 5MB'),
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

                    // Handle logo upload
                    $logoPath = null;
                    if (isset($data['logo']) && $data['logo']) {
                        if ($data['logo'] instanceof TemporaryUploadedFile) {
                            $logoPath = $data['logo']->store('invoice-logos', 'public');
                        } else {
                            $logoPath = $data['logo'];
                        }
                    }

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
                        'regime' => $data['regime'],
                        'agent' => $data['agent'],
                        'route' => $data['route'],
                        'overstay_days' => $data['overstay_days'],
                        'penalty_amount' => $data['penalty_amount'],
                        'device_number' => $data['device_number'],
                        'total_amount' => $data['total_amount'],
                        'description' => $data['description'] ?? null,
                        'paid_by' => $data['paid_by'],
                        'received_by' => $data['received_by'],
                        'logo_path' => $logoPath,
                        'status' => 'PP', // Changed from 'pending_approval' to 'PP'
                    ]);

                    DB::commit();

                    Notification::make()
                        ->title('Invoice Created Successfully')
                        ->body('The invoice has been created and is pending finance approval.')
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    DB::rollBack();
                    
                    // Clean up uploaded file if exists
                    if (isset($logoPath) && Storage::disk('public')->exists($logoPath)) {
                        Storage::disk('public')->delete($logoPath);
                    }

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
                return $record->overstay_days >= 2 &&
                    $record->payment_status !== 'PD' &&
                    auth()->user()?->hasAnyRole([
                        'Super Admin',
                        'Warehouse Manager',
                        'Retrieval Officer'
                    ]);
            });
    }
}