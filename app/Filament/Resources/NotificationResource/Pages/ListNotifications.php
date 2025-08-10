<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;

class ListNotifications extends ListRecords
{
    protected static string $resource = NotificationResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->query(Notification::query()->latest())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('message')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('notifiable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-check')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->getStateUsing(fn (Notification $record): bool => !is_null($record->read_at)),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'info' => 'Info',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'read' => 'Read',
                        'unread' => 'Unread',
                    ]),
            ])
            ->actions([
                Action::make('mark_as_read')
                    ->label('Mark as Read')
                    ->icon('heroicon-o-check')
                    ->action(fn (Notification $record) => $record->markAsRead())
                    ->hidden(fn (Notification $record): bool => !is_null($record->read_at)),
                Action::make('mark_as_unread')
                    ->label('Mark as Unread')
                    ->icon('heroicon-o-check')
                    ->action(fn (Notification $record) => $record->markAsUnread())
                    ->hidden(fn (Notification $record): bool => is_null($record->read_at)),
            ])
            ->bulkActions([
                BulkAction::make('mark_as_read')
                    ->label('Mark as Read')
                    ->icon('heroicon-o-check')
                    ->action(fn (Collection $records) => $records->each->markAsRead()),
                BulkAction::make('mark_as_unread')
                    ->label('Mark as Unread')
                    ->icon('heroicon-o-check')
                    ->action(fn (Collection $records) => $records->each->markAsUnread()),
            ]);
    }
}
