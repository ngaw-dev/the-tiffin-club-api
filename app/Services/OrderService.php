<?php

namespace App\Services;

use App\Http\Requests\Api\PlaceOrderRequest;
use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly OrderingWindowService $orderingWindows,
    ) {}

    /**
     * Place an order for today's menu from a validated request. The
     * ordering window is re-checked at write time so validation can't be
     * raced past the cutoff. Prices are snapshot from the menu; the
     * manage token is the sole handle and expires at 23:59:59 today.
     */
    public function place(PlaceOrderRequest $request): Order
    {
        $menu = $request->todaysMenu();

        if ($menu === null || ! $this->orderingWindows->forMenu($menu)->isOpen()) {
            throw ValidationException::withMessages([
                'ordering' => 'The ordering window is closed.',
            ]);
        }

        return DB::transaction(function () use ($request, $menu): Order {
            $menuItems = $menu->items->keyBy('id');

            $order = new Order;
            $order->fill([
                'menu_id' => $menu->id,
                'status' => Order::STATUS_PLACED,
                'fulfilment' => $request->validated('fulfilment'),
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'address' => $request->validated('address'),
                'suburb' => $request->validated('suburb'),
                'placed_at' => now(),
                'expires_at' => Carbon::today(OrderingWindowService::TIMEZONE)->setTime(23, 59, 59),
            ]);
            $order->manage_token = bin2hex(random_bytes(32));
            $order->save();

            $total = 0;
            foreach ($request->validated('items') as $line) {
                $menuItem = $menuItems[$line['id']];
                $total += $menuItem->price * $line['quantity'];

                $order->items()->create([
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $menuItem->price,
                ]);
            }

            $order->total = $total;
            $order->save();

            return $order->refresh();
        });
    }
}
