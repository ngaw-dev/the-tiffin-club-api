<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmationMail;
use App\Models\OrderUpdate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

/**
 * Send the customer's order confirmation email. Phone-only customers
 * get no out-of-band confirmation; every outcome (sent or skipped) is
 * appended to the order's audit trail as a system entry.
 */
class SendOrderConfirmation implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->loadMissing('items.menuItem', 'customer');

        if ($order->customer?->email === null) {
            $order->audits()->create([
                'actor' => OrderUpdate::ACTOR_SYSTEM,
                'action' => OrderUpdate::ACTION_CONFIRMATION_SKIPPED,
                'changes' => ['channel' => 'email', 'reason' => 'no_email'],
            ]);

            return;
        }

        Mail::to($order->customer->email)->queue(
            new OrderConfirmationMail($order),
        );

        $order->audits()->create([
            'actor' => OrderUpdate::ACTOR_SYSTEM,
            'action' => OrderUpdate::ACTION_CONFIRMATION_SENT,
            'changes' => ['channel' => 'email'],
        ]);
    }
}
