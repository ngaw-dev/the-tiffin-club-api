<?php

namespace App\Listeners;

use App\Events\OrderChanged;
use App\Mail\OrderSummaryMail;
use App\Models\Order;
use App\Models\OrderUpdate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Send the customer's "order updated/cancelled" email. Phone-only
 * customers get no email; every outcome is audited, mirroring
 * SendOrderConfirmation.
 */
class SendOrderChangeNotification implements ShouldQueue
{
    public function handle(OrderChanged $event): void
    {
        $order = $event->order->loadMissing('items.menuItem', 'customer');

        if ($order->customer?->email === null) {
            $order->audits()->create([
                'actor' => OrderUpdate::ACTOR_SYSTEM,
                'action' => OrderUpdate::ACTION_CONFIRMATION_SKIPPED,
                'changes' => ['channel' => 'email', 'reason' => 'no_email', 'change' => $event->verb],
            ]);

            return;
        }

        $cancelled = $event->verb === 'cancelled';

        Mail::to($order->customer->email)->queue(new OrderSummaryMail(
            order: $order,
            heading: $cancelled ? 'Your tiffin order is cancelled' : 'Your tiffin order has been updated',
            intro: $cancelled
                ? "Hi {$order->customer->name}, your order has been cancelled as requested."
                : "Hi {$order->customer->name}, here's your updated order:",
        ));

        $order->audits()->create([
            'actor' => OrderUpdate::ACTOR_SYSTEM,
            'action' => OrderUpdate::ACTION_CONFIRMATION_SENT,
            'changes' => ['channel' => 'email', 'change' => $event->verb],
        ]);
    }
}
