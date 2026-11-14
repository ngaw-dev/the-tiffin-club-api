<?php

namespace App\Services;

use App\Http\Requests\Api\PlaceOrderRequest;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly MenuService $menus,
        private readonly OrderingWindowService $orderingWindows,
    ) {}

    /**
     * Place an order for today's menu from a validated request. Prices
     * are snapshot from the menu at order time; the manage token is the
     * sole handle on the order and expires at end of day.
     */
    public function place(PlaceOrderRequest $request): Order
    {
        $menu = $this->menus->todaysPublishedMenu();

        return DB::transaction(function () use ($request, $menu): Order {
            $menuItems = $menu->items->keyBy('id');

            $order = Order::create([
                'menu_id' => $menu->id,
                'manage_token' => bin2hex(random_bytes(32)),
                'status' => Order::STATUS_PLACED,
                'fulfilment' => $request->validated('fulfilment'),
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'phone' => $request->validated('phone'),
                'address' => $request->validated('address'),
                'suburb' => $request->validated('suburb'),
                'placed_at' => now(),
                'expires_at' => Carbon::today(OrderingWindowService::TIMEZONE)->modify('tomorrow midnight'),
            ]);

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

            $order->update(['total' => $total]);

            return $order->refresh();
        });
    }
}
