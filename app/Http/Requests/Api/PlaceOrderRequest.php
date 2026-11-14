<?php

namespace App\Http\Requests\Api;

use App\Models\Menu;
use App\Models\Order;
use App\Services\MenuService;
use App\Services\OrderingWindowService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fulfilment' => ['required', Rule::in([Order::FULFILMENT_PICKUP, Order::FULFILMENT_DELIVERY])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required_if:fulfilment,delivery', 'nullable', 'string', 'max:32'],
            'address' => ['required_if:fulfilment,delivery', 'nullable', 'string', 'max:255'],
            'suburb' => ['required_if:fulfilment,delivery', 'nullable', 'string', Rule::in(config('tiffin.delivery_suburbs'))],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'required',
                'integer',
                Rule::exists('menu_items', 'id')->where('menu_id', $this->menuIdForValidation()),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    /**
     * The ordering window gates placement: reject when today's menu is
     * missing, unpublished, or outside the window.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->menuForValidation() === null && ! $validator->errors()->has('ordering')) {
                $validator->errors()->add('ordering', 'No menu is published for today yet.');
            } elseif ($this->menuForValidation() !== null
                && ! app(OrderingWindowService::class)->forMenu($this->menuForValidation())->isOpen()
                && ! $validator->errors()->has('ordering')) {
                $validator->errors()->add('ordering', 'The ordering window is closed.');
            }
        });
    }

    private function menuForValidation(): ?Menu
    {
        return app(MenuService::class)->todaysPublishedMenu();
    }

    private function menuIdForValidation(): ?int
    {
        return $this->menuForValidation()?->id;
    }
}
