<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayOrdersOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Order::query()
            ->whereHas('menu', fn ($q) => $q->whereDate('menu_date', today()))
            ->whereNot('status', Order::STATUS_CANCELLED);

        return [
            Stat::make('Orders today', (clone $today)->count()),
            Stat::make('Pickup', (clone $today)->where('fulfilment', Order::FULFILMENT_PICKUP)->count()),
            Stat::make('Delivery', (clone $today)->where('fulfilment', Order::FULFILMENT_DELIVERY)->count()),
            Stat::make('Revenue today', '$'.number_format((clone $today)->sum('total') / 100, 2)),
        ];
    }
}
