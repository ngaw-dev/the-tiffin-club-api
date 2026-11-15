<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\OrderUpdate;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['placed_at'] ??= now();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->updates()->create([
            'actor' => OrderUpdate::ACTOR_STAFF,
            'action' => 'placed',
            'changes' => ['source' => 'admin panel'],
        ]);
    }
}
