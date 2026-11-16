<?php

namespace App\Http\Resources\Admin;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Customer */
class AdminCustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'suburb' => $this->suburb,
            'order_count' => $this->whenCounted('orders'),
            'orders' => AdminOrderResource::collection($this->whenLoaded('orders')),
            'payments' => AdminPaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
