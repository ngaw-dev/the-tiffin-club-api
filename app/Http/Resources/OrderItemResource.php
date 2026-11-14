<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderItem */
class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->menuItem?->name,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price,
            'line_total' => $this->quantity * $this->unit_price,
        ];
    }
}
