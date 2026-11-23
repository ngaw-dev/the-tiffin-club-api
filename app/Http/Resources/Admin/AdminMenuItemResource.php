<?php

namespace App\Http\Resources\Admin;

use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin MenuItem */
class AdminMenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->image_path ? asset('storage/'.$this->image_path) : null,
            'price' => $this->price,
            'sort_order' => $this->sort_order,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
