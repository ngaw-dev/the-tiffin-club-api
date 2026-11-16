<?php

namespace App\Http\Resources\Admin;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Menu */
class AdminMenuResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'menu_date' => $this->menu_date->toDateString(),
            'cutoff_time' => $this->cutoff_time?->format('H:i'),
            'published' => $this->published_at !== null,
            'items' => AdminMenuItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
