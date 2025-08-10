<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use App\Services\AllocationPointPermissionService;
use App\Filament\Resources\UserResource\Forms\UserForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Spatie\Permission\Models\Permission;
use Filament\Forms\Components\Grid;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        $hasRole = $user?->hasRole('Super Admin') ?? false;
        Log::info('UserResource::shouldRegisterNavigation check', [
            'user_id' => $user?->id,
            'email' => $user?->email,
            'has_super_admin_role' => $hasRole,
            'roles' => $user?->getRoleNames(),
        ]);
        return $hasRole;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255)
                    ->confirmed()
                    ->autocomplete('new-password'),

                Forms\Components\TextInput::make('password_confirmation')
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    ->maxLength(255)
                    ->autocomplete('new-password')
                    ->dehydrated(false),

                Section::make('Role Assignment')
                    ->schema([
                        Select::make('roles')
                            ->label('Select Roles')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                            ->preload()
                            ->searchable()
                            ->required(),
                    ]),

                Section::make('User Permissions')
                    ->schema([
                        Grid::make()
                            ->schema([
                                CheckboxList::make('permission_checkboxes')
                                    ->label('All Available Permissions')
                                    ->options(fn () => Permission::query()
                                        ->orderBy('name')
                                        ->pluck('name', 'name')
                                        ->toArray()
                                    )
                                    ->columns(3)
                                    ->searchable()
                                    ->bulkToggleable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $selectedPermissions = collect($state)->filter()->keys()->toArray();
                                        $set('permissions', $selectedPermissions);
                                    })
                                    ->dehydrated(false),

                                Select::make('permissions')
                                    ->label('Selected Permissions')
                                    ->multiple()
                                    ->relationship('permissions', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, $set) {
                                        $set('permission_checkboxes', collect($state)->mapWithKeys(fn ($item) => [$item => true])->toArray());
                                    }),
                            ]),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('email')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('roles.name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('permissions.name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('permission_stored')->sortable()->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(), // Ensure delete action is enabled
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return true;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['permissions'])) {
            $data['permission_checkboxes'] = collect($data['permissions'])
                ->mapWithKeys(fn ($item) => [$item => true])
                ->toArray();
        }
        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = User::find($data['id'] ?? null);
        if ($user) {
            $permissions = $user->permissions->pluck('name')->toArray();
            $data['permission_checkboxes'] = collect($permissions)
                ->mapWithKeys(fn ($item) => [$item => true])
                ->toArray();
        }
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        // Keep only the permissions field for saving
        if (isset($data['direct_permissions'])) {
            // Ensure permissions field matches direct_permissions
            $data['permissions'] = $data['direct_permissions'];
            unset($data['direct_permissions']);
        }
        return $data;
    }

    public static function afterSave(Model $record, array $data): void
    {
        if (isset($data['permissions'])) {
            $record->syncPermissions($data['permissions']);
        }
    }
}

