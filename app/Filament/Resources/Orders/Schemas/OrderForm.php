<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Order;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('menu_id')
                    ->relationship('menu', 'menu_date')
                    ->required(),
                Select::make('status')
                    ->options([
                        Order::STATUS_CONFIRMED => 'Confirmed',
                        Order::STATUS_READY_FOR_PICKUP => 'Ready for pickup',
                        Order::STATUS_OUT_FOR_DELIVERY => 'Out for delivery',
                        Order::STATUS_COMPLETED => 'Completed',
                        Order::STATUS_CANCELLED => 'Cancelled',
                    ])
                    ->required(),
                Select::make('fulfilment')
                    ->options([
                        Order::FULFILMENT_PICKUP => 'Pickup',
                        Order::FULFILMENT_DELIVERY => 'Delivery',
                    ])
                    ->required(),
                Textarea::make('delivery_notes')
                    ->columnSpanFull(),
                DateTimePicker::make('placed_at'),
                DateTimePicker::make('expires_at'),
            ]);
    }
}
