<?php

namespace App\Filament\Pages;

use App\Models\GlobalSetting;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page as BasePage;
use Filament\Schemas\Schema;

/**
 * shortcut: plain Page bound to the GlobalSetting key-value rows,
 * revisit if moving to the filament-settings plugin.
 */
class Settings extends BasePage implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'store_phone' => GlobalSetting::get('store_phone'),
            'store_phone_alternate' => GlobalSetting::get('store_phone_alternate'),
            'store_email' => GlobalSetting::get('store_email'),
            'pickup_address' => GlobalSetting::get('pickup_address'),
            'pickup_map_url' => GlobalSetting::get('pickup_map_url'),
            'order_cutoff_time' => GlobalSetting::get('order_cutoff_time')?->format('H:i'),
            'pickup_start_time' => GlobalSetting::get('pickup_start_time')?->format('H:i'),
            'pickup_end_time' => GlobalSetting::get('pickup_end_time')?->format('H:i'),
            'delivery_suburbs' => collect(GlobalSetting::get('delivery_suburbs'))
                ->map(fn (string $suburb) => ['suburb' => $suburb])
                ->values()->all(),
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                TextInput::make('store_phone')->tel(),
                TextInput::make('store_phone_alternate')->tel(),
                TextInput::make('store_email')->email(),
                TextInput::make('pickup_address')->columnSpanFull(),
                TextInput::make('pickup_map_url')->url()->columnSpanFull(),
                TimePicker::make('order_cutoff_time'),
                TimePicker::make('pickup_start_time'),
                TimePicker::make('pickup_end_time'),
                Repeater::make('delivery_suburbs')
                    ->schema([
                        TextInput::make('suburb')->required(),
                    ])
                    ->grid(2)
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $state['delivery_suburbs'] = collect($state['delivery_suburbs'] ?? [])
            ->pluck('suburb')->values()->all();

        foreach ($state as $key => $value) {
            if (array_key_exists($key, GlobalSetting::KEYS)) {
                GlobalSetting::set($key, $value);
            }
        }
    }
}
