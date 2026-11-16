<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderItemResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class AdminOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'fulfilment' => $this->fulfilment,
            'total' => $this->total,
            'paid' => $this->whenLoaded('payments', fn () => $this->payments->sum('amount')),
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payments' => AdminPaymentResource::collection($this->whenLoaded('payments')),
            'delivery_notes' => $this->delivery_notes,
            'placed_at' => $this->placed_at?->toIso8601String(),
        ];
    }
}
