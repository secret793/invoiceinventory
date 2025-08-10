<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('guard_name')
                            ->default('web')
                            ->required(),
                        Forms\Components\Section::make('Permissions')
                            ->schema([
                                Forms\Components\Tabs::make('Permissions')
                                    ->tabs([
                                        Forms\Components\Tabs\Tab::make('Allocation Points')
                                            ->schema([
                                                Forms\Components\CheckboxList::make('permissions')
                                                    ->label('Allocation Points Permissions')
                                                    ->options(fn () => \Spatie\Permission\Models\Permission::query()
                                                        ->where('name', 'like', 'view_allocationpoint_%')
                                                        ->pluck('name', 'name'))
                                                    ->columns(2)
                                                    ->searchable(),
                                            ]),
                                        Forms\Components\Tabs\Tab::make('Other Permissions')
                                            ->schema([
                                                Forms\Components\CheckboxList::make('permissions')
                                                    ->label('Other Permissions')
                                                    ->options(fn () => \Spatie\Permission\Models\Permission::query()
                                                        ->where('name', 'not like', 'view_allocationpoint_%')
                                                        ->pluck('name', 'name'))
                                                    ->columns(2)
                                                    ->searchable(),
                                            ]),
                                    ]),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('permissions.name')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListRoles::route('/roles'),
            'create' => Pages\CreateRole::route('/roles/create'),
            'edit' => Pages\EditRole::route('/roles/{record}/edit'),
        ];
    }
}
