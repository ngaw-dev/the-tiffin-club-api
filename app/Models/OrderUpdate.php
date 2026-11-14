<?php

namespace App\Models;

use Database\Factories\OrderUpdateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderUpdate extends Model
{
    /** @use HasFactory<OrderUpdateFactory> */
    use HasFactory;

    public const ACTOR_CUSTOMER = 'customer';

    public const ACTOR_STAFF = 'staff';

    public const ACTION_PLACED = 'placed';

    public const ACTION_EDITED = 'edited';

    public const ACTION_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'actor',
        'action',
        'changes',
    ];

    protected $casts = [
        'changes' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
