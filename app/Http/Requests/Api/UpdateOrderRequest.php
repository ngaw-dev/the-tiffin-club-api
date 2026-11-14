<?php

namespace App\Http\Requests\Api;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Item ids are validated against the order's own menu at write time
     * by OrderService::update(), after the expiry/editability gates.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fulfilment' => ['sometimes', Rule::in([Order::FULFILMENT_PICKUP, Order::FULFILMENT_DELIVERY])],
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'suburb' => ['sometimes', 'nullable', 'string', Rule::in(config('tiffin.delivery_suburbs'))],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['required_with:items', 'integer'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1', 'max:20'],
        ];
    }
}
