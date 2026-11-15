<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('placed_at', 'desc')
            ->columns([
                TextColumn::make('customer.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('menu.menu_date')
                    ->date()
                    ->label('Menu date')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Order::STATUS_READY_FOR_PICKUP => 'Ready for pickup',
                        Order::STATUS_OUT_FOR_DELIVERY => 'Out for delivery',
                        default => ucfirst($state),
                    })
                    ->colors([
                        'success' => Order::STATUS_COMPLETED,
                        'warning' => Order::STATUS_READY_FOR_PICKUP,
                        'info' => Order::STATUS_OUT_FOR_DELIVERY,
                        'danger' => Order::STATUS_CANCELLED,
                    ]),
                TextColumn::make('fulfilment')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('item_count')->counts('items')->label('Items'),
                TextColumn::make('total')
                    ->money('NZD')
                    ->sortable(),
                TextColumn::make('placed_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        Order::STATUS_CONFIRMED => 'Confirmed',
                        Order::STATUS_READY_FOR_PICKUP => 'Ready for pickup',
                        Order::STATUS_OUT_FOR_DELIVERY => 'Out for delivery',
                        Order::STATUS_COMPLETED => 'Completed',
                        Order::STATUS_CANCELLED => 'Cancelled',
                    ]),
                SelectFilter::make('fulfilment')
                    ->options([
                        Order::FULFILMENT_PICKUP => 'Pickup',
                        Order::FULFILMENT_DELIVERY => 'Delivery',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
