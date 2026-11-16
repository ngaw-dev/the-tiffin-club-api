<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\CustomerResource;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Payment */
class AdminPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'customer' => $this->whenLoaded('customer', fn () => new CustomerResource($this->customer)),
            'amount' => $this->amount,
            'method' => $this->method,
            'paid_at' => $this->paid_at?->toIso8601String(),
        ];
    }
}
