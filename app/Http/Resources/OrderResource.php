<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'manage_token' => $this->manage_token,
            'status' => $this->status,
            'fulfilment' => $this->fulfilment,
            'customer' => $this->whenLoaded('customer', fn () => [
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'phone' => $this->customer->phone,
                'address' => $this->customer->address,
                'suburb' => $this->customer->suburb,
            ]),
            'delivery_notes' => $this->delivery_notes,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'total' => $this->total,
            'payment' => 'pay_in_person',
            'placed_at' => $this->placed_at->toIso8601String(),
            'expires_at' => $this->expires_at->toIso8601String(),
        ];
    }
}
