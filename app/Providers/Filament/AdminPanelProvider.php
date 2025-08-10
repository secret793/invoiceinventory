<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Resources\AllocationPointResource;
use App\Filament\Resources\AssignToAgentResource;
use App\Filament\Resources\ConfirmedAffixedResource;
use App\Filament\Resources\DataEntryAssignmentResource;
use App\Filament\Resources\DeviceRetrievalResource;
use App\Filament\Resources\DeviceResource;
use App\Filament\Resources\DestinationResource;
use App\Filament\Resources\DistributionPointResource;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\LongRouteResource;
use App\Filament\Resources\MonitoringResource;
use App\Filament\Resources\NotificationResource;
use App\Filament\Resources\OtherItemResource;
use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\RegimeResource;
use App\Filament\Resources\ReportResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\RouteResource;
use App\Filament\Resources\StoreResource;
use App\Filament\Resources\TransferResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\DispatchLogResource;
use App\Models\AllocationPoint;
use App\Models\DataEntryAssignment;
use App\Models\DistributionPoint;
use Filament\Facades\Filament;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->darkModebrandLogo('<img src="' . asset('images/etracking logo.jpg') . '" style="max-width:300px;max-height:120px;display:block;margin:auto;" alt="Logo">')
            ->login()
            ->brandName('E-Tracking Inventory System')
           // ->brandLogo(asset('images/etracking logo2.jpg'))
            //->brandLogoHeight('4rem')
            //->viteTheme('resources/css/filament/admin/theme.css')
            ->passwordReset()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
               // Widgets\AccountWidget::class,
               // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                $user = auth()->user();

                if (!$user) {
                    return $builder;
                }

                // Add debugging for multi-role users
                \Log::info('AdminPanelProvider: Building navigation', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_roles' => $user->roles->pluck('name')->toArray()
                ]);

                try {
                    $isSuperAdmin = $user->hasRole('Super Admin');
                    $isWarehouseManager = $user->hasRole('Warehouse Manager');
                    $isAllocationOfficer = $user->hasRole('Allocation Officer');
                    $isDataEntryOfficer = $user->hasRole('Data Entry Officer');
                    $isDistributionOfficer = $user->hasRole('Distribution Officer');
                    $isMonitoringOfficer = $user->hasRole('Monitoring Officer');
                    $isAffixingOfficer = $user->hasRole('Affixing Officer');
                    $isRetrievalOfficer = $user->hasRole('Retrieval Officer');
                    $isFinanceOfficer = $user->hasRole('Finance Officer');
                } catch (\Exception $e) {
                    \Log::error('AdminPanelProvider: Error checking roles', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage()
                    ]);
                    return $builder;
                }

                // Base navigation for all users
                $builder->item(
                    NavigationItem::make('Dashboard')
                        ->icon('heroicon-o-home')
                        ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.dashboard'))
                        ->url(fn (): string => Pages\Dashboard::getUrl())
                );

                // Finance Officer navigation
                if ($isFinanceOfficer) {
                    $builder->group('Finance Management', [
                        NavigationItem::make('Invoices')
                            ->icon('heroicon-o-document-text')
                            ->url(\App\Filament\Resources\InvoiceResource::getUrl())
                            ->badge(
                                fn () => \App\Models\Invoice::where('status', 'pending')->count() ?: null,
                                color: fn () => 'warning'
                            ),
                    ]);
                }

                // Data Entry Officer navigation - Only show Data Entry/Assignment menu
                if ($isDataEntryOfficer && !$isSuperAdmin && !$isWarehouseManager) {
                    $builder->group('Data Entry/Assignment',
                        DataEntryAssignment::with('allocationPoint')
                            ->whereHas('allocationPoint', function ($query) {
                                $userPermissions = auth()->user()->permissions;

                                $allowedPoints = collect($userPermissions)
                                    ->filter(fn ($permission) => str_starts_with($permission->name, 'view_data_entry_'))
                                    ->map(function ($permission) {
                                        $slug = Str::after($permission->name, 'view_data_entry_');
                                        return Str::title(str_replace('-', ' ', $slug));
                                    })
                                    ->toArray();

                                $query->whereIn('name', $allowedPoints);
                            })
                            ->get()
                            ->map(fn (DataEntryAssignment $assignment) =>
                                NavigationItem::make($assignment->allocationPoint->name)
                                    ->icon('heroicon-o-document')
                                    ->url(DataEntryAssignmentResource::getUrl('view', ['record' => $assignment]))
                            )
                            ->toArray()
                    );

                    // Return here to prevent showing other menus
                    return $builder;
                }

                // Monitoring Officer Navigation
                if ($isMonitoringOfficer && !$isSuperAdmin && !$isWarehouseManager) {
                    $builder->group('Monitoring', [
                        NavigationItem::make('Device Monitoring')
                            ->icon('heroicon-o-building-office')
                            ->url(MonitoringResource::getUrl()),
                    ]);

                    return $builder;
                }

                // Multi-role navigation for Affixing Officer and Retrieval Officer
                if (($isAffixingOfficer || $isRetrievalOfficer) && !$isSuperAdmin && !$isWarehouseManager) {
                    \Log::info('AdminPanelProvider: Building multi-role navigation', [
                        'user_id' => $user->id,
                        'isAffixingOfficer' => $isAffixingOfficer,
                        'isRetrievalOfficer' => $isRetrievalOfficer
                    ]);

                    $navigationItems = [];

                    // Add Confirmed Affixed navigation if user has Affixing Officer role
                    if ($isAffixingOfficer) {
                        try {
                            // Check if we can safely get the resource URL
                            if (class_exists(ConfirmedAffixedResource::class)) {
                                $navigationItems[] = NavigationItem::make('Confirmed Affixed')
                                    ->icon('heroicon-o-check-circle')
                                    ->url(ConfirmedAffixedResource::getUrl());
                                \Log::info('AdminPanelProvider: Added Confirmed Affixed navigation');
                            }
                        } catch (\Exception $e) {
                            \Log::error('AdminPanelProvider: Error adding Confirmed Affixed navigation', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }

                    // Add Device Retrieval navigation if user has Retrieval Officer role
                    if ($isRetrievalOfficer) {
                        try {
                            // Check if we can safely get the resource URL
                            if (class_exists(DeviceRetrievalResource::class)) {
                                $navigationItems[] = NavigationItem::make('Device Retrievals')
                                    ->icon('heroicon-o-arrow-uturn-left')
                                    ->url(DeviceRetrievalResource::getUrl());
                                \Log::info('AdminPanelProvider: Added Device Retrieval navigation');
                            }
                        } catch (\Exception $e) {
                            \Log::error('AdminPanelProvider: Error adding Device Retrieval navigation', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }

                    // Determine group name based on roles
                    $groupName = 'Management';
                    if ($isAffixingOfficer && $isRetrievalOfficer) {
                        $groupName = 'Affixing & Retrieval Management';
                    } elseif ($isAffixingOfficer) {
                        $groupName = 'Confirmed Dispatch';
                    } elseif ($isRetrievalOfficer) {
                        $groupName = 'Device Retrieval';
                    }

                    if (!empty($navigationItems)) {
                        $builder->group($groupName, $navigationItems);
                    }

                    return $builder;
                }



                // Distribution Officer Navigation
                if ($isDistributionOfficer && !$isSuperAdmin && !$isWarehouseManager) {
                    $distributionPoints = DistributionPoint::all()
                        ->map(fn (DistributionPoint $point) =>
                            NavigationItem::make($point->name)
                                ->icon('heroicon-o-building-office')
                                ->url(DistributionPointResource::getUrl('view', ['record' => $point]))
                                ->badge(
                                    DistributionPoint::getBadgeText($point->id),
                                    color: function() use ($point) {
                                        $config = DistributionPoint::getBadgeConfig($point->id);
                                        // Use the color of the majority as the badge color
                                        return $config['receivedRatio'] > $config['otherRatio'] ? 'danger' : 'warning';
                                    }
                                )
                        )
                        ->toArray();

                    $builder->group('Distribution Points', $distributionPoints);

                    return $builder;
                }

                // Allocation Officer navigation
                if ($isAllocationOfficer && !$isSuperAdmin && !$isWarehouseManager) {
                    \Log::info('AdminPanelProvider: Building Allocation Officer navigation', [
                        'user_id' => $user->id,
                        'user_permissions' => $user->permissions->pluck('name')->toArray()
                    ]);

                    $allocationPoints = AllocationPoint::all()
                        ->filter(function (AllocationPoint $point) {
                            $permissionName = 'view_allocationpoint_' . Str::slug($point->name);
                            $hasPermission = auth()->user()->hasPermissionTo($permissionName);
                            \Log::info('AdminPanelProvider: Checking allocation point permission', [
                                'point_name' => $point->name,
                                'permission_name' => $permissionName,
                                'has_permission' => $hasPermission
                            ]);
                            return $hasPermission;
                        })
                        ->map(fn (AllocationPoint $point) =>
                            NavigationItem::make($point->name)
                                ->icon('heroicon-o-home')
                                ->url(AllocationPointResource::getUrl('view', ['record' => $point->id]))
                                ->badge(
                                    AllocationPoint::getBadgeText($point->id),
                                    color: AllocationPoint::getBadgeColor($point->id)
                                )
                        )
                        ->toArray();

                    \Log::info('AdminPanelProvider: Allocation Officer navigation items', [
                        'user_id' => $user->id,
                        'allocation_points_count' => count($allocationPoints),
                        'allocation_points' => array_map(fn($item) => $item->getLabel(), $allocationPoints)
                    ]);

                    if (!empty($allocationPoints)) {
                        $builder->group('Allocation', $allocationPoints);
                    } else {
                        \Log::warning('AdminPanelProvider: No allocation points found for Allocation Officer', [
                            'user_id' => $user->id
                        ]);
                    }

                    return $builder;
                }

                // Admin and Warehouse Manager navigation (full access)
                if ($isSuperAdmin || $isWarehouseManager) {
                    // Inventory Management
                    $builder->group('Inventory Management', [
                        NavigationItem::make('Devices/Trackers')
                            ->icon('heroicon-o-device-phone-mobile')
                            ->url(DeviceResource::getUrl()),
                        NavigationItem::make('Stores/Device Stock')
                            ->icon('heroicon-o-archive-box')
                            ->url(StoreResource::getUrl()),
                        NavigationItem::make('Transfers')
                            ->icon('heroicon-o-map')
                            ->url(TransferResource::getUrl()),
                        NavigationItem::make('Other Items')
                            ->icon('heroicon-o-arrow-path')
                            ->url(OtherItemResource::getUrl()),
                    ]);

                    // Distribution Points
                    $distributionPoints = DistributionPoint::all()
                        ->map(fn (DistributionPoint $point) =>
                            NavigationItem::make($point->name)
                                ->icon('heroicon-o-building-office')
                                ->url(DistributionPointResource::getUrl('view', ['record' => $point]))
                                ->badge(
                                    DistributionPoint::getBadgeText($point->id),
                                    color: DistributionPoint::getDetailedBadgeColor($point->id)
                                )
                        )
                        ->toArray();

                    $builder->group('Distribution Points', $distributionPoints);

                    // Allocation Points
                    $allocationPoints = AllocationPoint::all()
                        ->map(fn (AllocationPoint $point) =>
                            NavigationItem::make($point->name)
                                ->icon('heroicon-o-home')
                                ->url(AllocationPointResource::getUrl('view', ['record' => $point->id]))
                                ->badge(
                                    AllocationPoint::getBadgeText($point->id),
                                    color: AllocationPoint::getBadgeColor($point->id)
                                )
                        )
                        ->toArray();

                    $builder->group('Allocation Points', $allocationPoints);

                    // Data Entry/Assignment
                    $dataEntryAssignments = DataEntryAssignment::with('allocationPoint')
                        ->whereHas('allocationPoint')
                        ->get()
                        ->map(fn (DataEntryAssignment $assignment) =>
                            NavigationItem::make($assignment->allocationPoint->name)
                                ->icon('heroicon-o-document')
                                ->url(DataEntryAssignmentResource::getUrl('view', ['record' => $assignment]))
                        )
                        ->toArray();

                    $builder->group('Data Entry/Assignment', $dataEntryAssignments);

                    // Confirmed Dispatch
                    $builder->group('Confirmed Dispatch', [
                        NavigationItem::make('Confirmed Affixed')
                            ->icon('heroicon-o-check-circle')
                            ->url(ConfirmedAffixedResource::getUrl()),
                    ]);

                    // Device Retrievals
                    $builder->group('Device Retrievals', [
                        NavigationItem::make('Device Retrievals')
                            ->icon('heroicon-o-arrow-uturn-left')
                            ->url(DeviceRetrievalResource::getUrl()),
                    ]);

                    // Reports
                    $builder->group('Reports', [
                        NavigationItem::make('All Reports')
                            ->icon('heroicon-o-document-chart-bar')
                            ->url(ReportResource::getUrl()),
                    ]);

                    // Monitoring
                    $builder->group('Monitoring', [
                        NavigationItem::make('Monitoring')
                            ->icon('heroicon-o-chart-bar')
                            ->url(MonitoringResource::getUrl('index')),
                    ]);

                    // Notifications (Super Admin only)
                    if ($isSuperAdmin) {
                        $builder->group('Notifications', [
                            NavigationItem::make('All Notifications')
                                ->icon('heroicon-o-bell')
                                ->url(NotificationResource::getUrl())
                                ->badge(NotificationResource::getNavigationBadge(), color: 'warning'),
                        ]);
                    }

                    // Configuration
                    $configItems = [
                        NavigationItem::make('Distribution Points')
                            ->icon('heroicon-o-building-office')
                            ->url(DistributionPointResource::getUrl()),
                        NavigationItem::make('Routes')
                            ->icon('heroicon-o-map')
                            ->url(RouteResource::getUrl()),
                        NavigationItem::make('Long Routes')
                            ->icon('heroicon-o-map')
                            ->url(LongRouteResource::getUrl()),
                        NavigationItem::make('Allocation Points')
                            ->icon('heroicon-o-building-storefront')
                            ->url(AllocationPointResource::getUrl()),
                        NavigationItem::make('Regimes')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->url(RegimeResource::getUrl()),
                        NavigationItem::make('Destinations')
                            ->icon('heroicon-o-map-pin')
                            ->url(DestinationResource::getUrl()),
                    ];

                    // Add user management items for Super Admin only
                    if ($isSuperAdmin) {
                        array_unshift($configItems,
                            NavigationItem::make('Users')
                                ->icon('heroicon-o-users')
                                ->url(UserResource::getUrl()),
                            NavigationItem::make('Roles')
                                ->icon('heroicon-o-shield-check')
                                ->url(RoleResource::getUrl()),
                            NavigationItem::make('Permissions')
                                ->icon('heroicon-o-key')
                                ->url(PermissionResource::getUrl())
                        );
                    }

                    $builder->group('Configuration', $configItems);
                }

                return $builder;
            });

    }

    public function boot()
    {
        // We're not using this method for navigation anymore
        // All navigation is handled in the navigation() method above
    }
}
