<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Http\Requests\Api\PlaceOrderRequest;
use App\Models\Customer;
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

            $customer = $this->resolveCustomer($request);

            $order = new Order;
            $order->fill([
                'menu_id' => $menu->id,
                'customer_id' => $customer->id,
                'status' => Order::STATUS_CONFIRMED,
                'fulfilment' => $request->validated('fulfilment'),
                'delivery_notes' => $request->validated('delivery_notes'),
                'placed_at' => now(),
                'expires_at' => Carbon::today(OrderingWindowService::TIMEZONE)->setTime(23, 59, 59),
            ]);
            $order->manage_token = bin2hex(random_bytes(32));
            $order->save();

            $order = $this->syncItems($order, $request->validated('items'), $menuItems);

            $this->recordAudit($order, OrderUpdate::ACTION_PLACED, ['items' => $request->validated('items')]);

            // Confirmation email fires only once placement is committed.
            DB::afterCommit(fn () => OrderPlaced::dispatch($order));

            return $order->refresh();
        });
    }

    /**
     * Find or create the customer for this order. Matched by email
     * first, then phone — when both match different customers the
     * email match wins. Latest submitted details become the defaults.
     */
    private function resolveCustomer(PlaceOrderRequest $request): Customer
    {
        $email = strtolower((string) $request->validated('email'));
        $phone = preg_replace('/\D+/', '', (string) $request->validated('phone'));

        $query = Customer::query();

        if ($email !== '') {
            $query->where('email', $email); // mutator lowercases; lookup matches
        } elseif ($phone !== '') {
            $query->where('phone', $phone); // mutator strips digits; lookup matches
        }

        $customer = $query->first();

        if ($customer === null) {
            $customer = new Customer;
            $customer->name = $request->validated('name');
            $customer->email = $request->validated('email');
            $customer->phone = $request->validated('phone');

            // Two first orders with the same email can race past the
            // lookup above; the unique index decides, the loser re-fetches.
            try {
                $customer->save();
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                $customer = Customer::query()
                    ->when($email !== '', fn ($q) => $q->where('email', $email))
                    ->when($email === '' && $phone !== '', fn ($q) => $q->where('phone', $phone))
                    ->first();
            }
        }

        // Remember the latest submitted details as the defaults, but
        // never claim an email/phone another customer already owns.
        $taken = fn (string $field, ?string $value) => $value !== null && $value !== ''
            && Customer::query()->where($field, $value)->whereKeyNot($customer->id)->exists();

        foreach (['name', 'email', 'phone', 'address', 'suburb'] as $field) {
            $value = $request->validated($field);
            if ($value !== null && $value !== '' && ! $taken($field, $value)) {
                $customer->{$field} = $value;
            }
        }

        if ($customer->isDirty()) {
            $customer->save();
        }

        return $customer;
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
            ->with(['items.menuItem', 'customer'])
            ->where('manage_token', $token)
            ->first();

        if ($order === null || now()->isAfter($order->expires_at)) {
            throw new OrderNotFound;
        }

        $customer = $order->customer;

        $matchesEmail = $email !== null && $customer->email !== null
            && $customer->email === strtolower($email);
        $matchesPhone = $phone !== null && $customer->phone !== null
            && $customer->phone === preg_replace('/\D+/', '', $phone);

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

            $detailFields = ['fulfilment', 'delivery_notes'];
            foreach ($detailFields as $field) {
                if (array_key_exists($field, $attributes) && $order->{$field} !== $attributes[$field]) {
                    $changes[$field] = ['from' => $order->{$field}, 'to' => $attributes[$field]];
                    $order->{$field} = $attributes[$field];
                }
            }

            foreach (['address', 'suburb'] as $field) {
                if (array_key_exists($field, $attributes) && $order->customer->{$field} !== $attributes[$field]) {
                    $changes[$field] = ['from' => $order->customer->{$field}, 'to' => $attributes[$field]];
                    $order->customer->{$field} = $attributes[$field];
                }
            }
            if ($order->customer->isDirty()) {
                $order->customer->save();
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
