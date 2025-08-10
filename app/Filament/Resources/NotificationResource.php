<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    
    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('message')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'info' => 'Info',
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ])
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name'),
                Forms\Components\Textarea::make('data')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('data')
                    ->searchable(),
                TextColumn::make('read_at')
                    ->dateTime()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state): string => $state === null ? 'warning' : 'success')
                    ->formatStateUsing(fn ($state) => $state === null ? 'Unread' : Carbon::parse($state)->diffForHumans()),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('unread')
                    ->query(fn ($query) => $query->whereNull('read_at')),
                Tables\Filters\Filter::make('read')
                    ->query(fn ($query) => $query->whereNotNull('read_at')),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('mark_as_read')
                    ->label('Mark as Read')
                    ->icon('heroicon-o-check')
                    ->action(function (Collection $records) {
                        $records->each(function ($record) {
                            $record->forceFill(['read_at' => now()])->save();
                        });
                    }),
                Tables\Actions\BulkAction::make('mark_as_unread')
                    ->label('Mark as Unread')
                    ->icon('heroicon-o-x')
                    ->action(function (Collection $records) {
                        $records->each(function ($record) {
                            $record->forceFill(['read_at' => null])->save();
                        });
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereNull('read_at')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::whereNull('read_at')->count() > 0 ? 'warning' : null;
    }
}
