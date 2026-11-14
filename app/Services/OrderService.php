<?php

namespace App\Services;

use App\Http\Requests\Api\PlaceOrderRequest;
use App\Models\Order;
use App\Models\OrderUpdate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderNotFound extends \RuntimeException {}

class OrderNoLongerAvailable extends \RuntimeException {}

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
                'email' => strtolower((string) $request->validated('email')),                'phone' => $request->validated('phone'),
                'address' => $request->validated('address'),
                'suburb' => $request->validated('suburb'),
                'placed_at' => now(),
                'expires_at' => Carbon::today(OrderingWindowService::TIMEZONE)->setTime(23, 59, 59),
            ]);
            $order->manage_token = bin2hex(random_bytes(32));
            $order->save();

            $order = $this->syncItems($order, $request->validated('items'), $menuItems);

            $this->recordAudit($order, OrderUpdate::ACTION_PLACED, ['items' => $request->validated('items')]);

            return $order->refresh();
        });
    }

    /**
     * Find an order by manage token; unknown tokens are not found, and
     * expired tokens (past 23:59:59 on the order day) are explicitly
     * "no longer available".
     */
    public function findByToken(string $token): Order
    {
        $order = Order::query()
            ->with(['items.menuItem'])
            ->where('manage_token', $token)
            ->first();

        if ($order === null) {
            throw new OrderNotFound;
        }

        if (now()->isAfter($order->expires_at)) {
            throw new OrderNoLongerAvailable;
        }

        return $order;
    }

    /**
     * Knowledge-based order lookup: the manage token plus the email or
     * phone used at checkout. Any mismatch (or missing/expired token)
     * is a uniform "not found" so nothing leaks about which identifier
     * an order carries.
     */
    public function lookup(string $token, ?string $email, ?string $phone): Order
    {
        $order = Order::query()
            ->with(['items.menuItem'])
            ->where('manage_token', $token)
            ->first();

        if ($order === null || now()->isAfter($order->expires_at)) {
            throw new OrderNotFound;
        }

        $matchesEmail = $email !== null && $order->email !== null
            && $order->email === strtolower($email);
        $matchesPhone = $phone !== null && $order->phone !== null
            && $order->phone === $phone;

        if (! $matchesEmail && ! $matchesPhone) {
            throw new OrderNotFound;
        }

        return $order;
    }

    /**
     * True when the customer may still change or cancel this order —
     * the same server-side gate that guards placement.
     */
    public function isEditableByCustomer(Order $order): bool
    {
        return ! $order->isCancelled()
            && $this->orderingWindows->forMenu($order->menu)->isOpen();
    }

    /**
     * Edit an order in place: replace items, details, or both; recompute
     * the total at menu prices; append to the audit trail.
     */
    public function update(Order $order, array $attributes): Order
    {
        $this->assertEditable($order);
        $this->assertItemsOnMenu($order, $attributes['items'] ?? []);

        return DB::transaction(function () use ($order, $attributes): Order {
            $changes = [];

            $detailFields = ['fulfilment', 'name', 'email', 'phone', 'address', 'suburb'];
            foreach ($detailFields as $field) {
                if (array_key_exists($field, $attributes) && $order->{$field} !== $attributes[$field]) {
                    $changes[$field] = ['from' => $order->{$field}, 'to' => $attributes[$field]];
                    $order->{$field} = $attributes[$field];
                }
            }

            if (array_key_exists('items', $attributes)) {
                $changes['items'] = $attributes['items'];
                $order = $this->syncItems($order, $attributes['items'], $order->menu->items->keyBy('id'));
            }

            $order->save();

            $this->recordAudit($order, OrderUpdate::ACTION_EDITED, $changes);

            return $order->refresh();
        });
    }

    /**
     * Cancel an order until cutoff; append to the audit trail.
     */
    public function cancel(Order $order): Order
    {
        $this->assertEditable($order);

        return DB::transaction(function () use ($order): Order {
            $order->status = Order::STATUS_CANCELLED;
            $order->save();

            $this->recordAudit($order, OrderUpdate::ACTION_CANCELLED, null);

            return $order->refresh();
        });
    }

    /**
     * Replace the order's items with the given lines, recomputing the
     * total at menu prices.
     */
    private function syncItems(Order $order, array $lines, $menuItems): Order
    {
        $order->items()->delete();

        $total = 0;
        foreach ($lines as $line) {
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

        return $order;
    }

    private function assertEditable(Order $order): void
    {
        if (! $this->isEditableByCustomer($order)) {
            throw ValidationException::withMessages([
                'ordering' => 'The ordering window is closed; this order can no longer be changed.',
            ]);
        }
    }

    /**
     * Every line must reference an item on the order's own menu.
     */
    private function assertItemsOnMenu(Order $order, array $lines): void
    {
        $menuItemIds = $order->menu->items->pluck('id')->flip();

        foreach ($lines as $index => $line) {
            if (! $menuItemIds->has($line['id'])) {
                throw ValidationException::withMessages([
                    "items.{$index}.id" => 'The selected item is not on this order\'s menu.',
                ]);
            }
        }
    }

    private function recordAudit(Order $order, string $action, ?array $changes): void
    {
        $order->audits()->create([
            'actor' => OrderUpdate::ACTOR_CUSTOMER,
            'action' => $action,
            'changes' => $changes,
        ]);
    }
}
