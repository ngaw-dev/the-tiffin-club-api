<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\OrderUpdate;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * The record is only dirty after the parent applies the form
     * data, so the audit diff happens here rather than beforeSave.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record = parent::handleRecordUpdate($record, $data);

        $dirty = collect($record->getChanges())
            ->except(['updated_at'])
            ->all();

        if ($dirty !== []) {
            $record->updates()->create([
                'actor' => OrderUpdate::ACTOR_STAFF,
                'action' => 'edited',
                'changes' => $dirty,
            ]);
        }

        return $record;
    }
}
