<?php

namespace App\Filament\Resources\UserResource\Forms;

use Filament\Forms;
use Spatie\Permission\Models\Permission;

class UserForm
{
    public static function schema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('password')
                ->password()
                ->dehydrateStateUsing(fn ($state) => !empty($state) ? bcrypt($state) : null)
                ->required(fn (string $context): bool => $context === 'create')
                ->maxLength(255),

            Forms\Components\Select::make('roles')
                ->multiple()
                ->relationship('roles', 'name')
                ->preload()
                ->reactive(),

            Forms\Components\Select::make('permissions')
                ->multiple()
                ->relationship('permissions', 'name')
                ->preload()
                ->reactive()
                ->label('Permissions')
                ->options(Permission::all()->pluck('name', 'id')->toArray())
                ->default(function ($record) {
                    return $record ? $record->permissions->pluck('id')->toArray() : [];
                }),
        ];
    }
}
