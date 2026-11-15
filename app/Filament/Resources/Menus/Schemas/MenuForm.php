<?php

namespace App\Filament\Resources\Menus\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DatePicker::make('menu_date')
                    ->required(),
                DateTimePicker::make('published_at'),
                TimePicker::make('cutoff_time'),
            ]);
    }
}
