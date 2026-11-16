<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after an order's update or cancel transaction commits. The
 * verb names the change; listeners (customer + owner emails) run on
 * the queue.
 */
class OrderChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly string $verb, // updated|cancelled
    ) {}
}
