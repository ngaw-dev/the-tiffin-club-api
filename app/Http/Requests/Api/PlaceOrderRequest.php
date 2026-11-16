<?php

namespace App\Http\Requests\Api;

use App\Models\GlobalSetting;
use App\Models\Menu;
use App\Models\Order;
use App\Services\ClerkTokenService;
use App\Services\MenuService;
use App\Services\OrderingWindowService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    private ?Menu $cachedMenu = null;

    private bool $menuResolved = false;

    public function authorize(): bool
    {
        // A presented-but-invalid Clerk token is a 401 before any
        // payload validation; no token at all means the guest flow.
        app(ClerkTokenService::class)->assertTokenValidIfPresent($this);

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
            'email' => ['required_without:phone', 'nullable', 'email', 'max:255'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
            'suburb' => ['required_if:fulfilment,delivery', 'nullable', 'string', Rule::in(GlobalSetting::get('delivery_suburbs'))],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'required',
                'integer',
                Rule::exists('menu_items', 'id')->where('menu_id', $this->todaysMenu()?->id ?? 0),
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
            if (! $this->menuIsOrderable() && ! $validator->errors()->has('ordering')) {
                $validator->errors()->add('ordering', 'The ordering window is closed.');
            }
        });
    }

    /**
     * Today's published menu, resolved once per request.
     */
    public function todaysMenu(): ?Menu
    {
        if (! $this->menuResolved) {
            $this->cachedMenu = app(MenuService::class)->todaysPublishedMenu();
            $this->menuResolved = true;
        }

        return $this->cachedMenu;
    }

    /**
     * True when today's menu exists and the ordering window is open.
     * Re-checked at write time by the service, not only here.
     */
    public function menuIsOrderable(): bool
    {
        if ($this->todaysMenu() === null) {
            return false;
        }

        return app(OrderingWindowService::class)
            ->forMenu($this->todaysMenu())
            ->isOpen();
    }
}
