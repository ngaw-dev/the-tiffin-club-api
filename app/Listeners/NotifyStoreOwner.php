<?php

namespace App\Listeners;

use App\Events\OrderChanged;
use App\Events\OrderPlaced;
use App\Mail\OrderSummaryMail;
use App\Models\GlobalSetting;
use App\Models\OrderUpdate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Email the store owner (GlobalSetting store_email) whenever an
 * order is placed, updated, or cancelled. Skipped and audited when
 * no store email is configured.
 */
class NotifyStoreOwner implements ShouldQueue
{
    public function handle(OrderPlaced|OrderChanged $event): void
    {
        $order = $event->order->loadMissing('items.menuItem', 'customer');
        $verb = $event instanceof OrderPlaced ? 'placed' : $event->verb;

        $storeEmail = GlobalSetting::get('store_email');

        if ($storeEmail === null || $storeEmail === '') {
            $order->audits()->create([
                'actor' => OrderUpdate::ACTOR_SYSTEM,
                'action' => OrderUpdate::ACTION_OWNER_NOTIFIED_SKIPPED,
                'changes' => ['channel' => 'email', 'reason' => 'no_store_email', 'change' => $verb],
            ]);

            return;
        }

        $heading = match ($verb) {
            'placed' => 'New order placed',
            'cancelled' => 'Order cancelled',
            default => 'Order updated',
        };

        $contact = $order->customer?->email ?? $order->customer?->phone ?? '';

        Mail::to($storeEmail)->queue(new OrderSummaryMail(
            order: $order,
            heading: $heading.' — '.$order->customer?->name,
            intro: "Customer {$order->customer?->name} ({$contact}) {$verb} an order totalling $".number_format($order->total / 100, 2).'.',
            forCustomer: false,
        ));

        $order->audits()->create([
            'actor' => OrderUpdate::ACTOR_SYSTEM,
            'action' => OrderUpdate::ACTION_OWNER_NOTIFIED,
            'changes' => ['channel' => 'email', 'change' => $verb],
        ]);
    }
}
