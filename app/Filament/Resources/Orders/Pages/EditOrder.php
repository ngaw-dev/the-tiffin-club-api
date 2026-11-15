<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\OrderUpdate;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function beforeSave(): void
    {
        $dirty = collect($this->record->getDirty())
            ->except(['updated_at'])
            ->all();

        if ($dirty !== []) {
            $this->record->updates()->create([
                'actor' => OrderUpdate::ACTOR_STAFF,
                'action' => 'edited',
                'changes' => $dirty,
            ]);
        }
    }
}
