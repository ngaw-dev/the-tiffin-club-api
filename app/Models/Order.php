<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';

    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * One-way lifecycle: forward means exactly one step along the
     * chain; cancelled is reachable from any non-terminal status;
     * completed and cancelled are terminal.
     *
     * @var array<string, list<string>>
     */
    public const STATUS_TRANSITIONS = [
        self::STATUS_CONFIRMED => [self::STATUS_READY_FOR_PICKUP, self::STATUS_CANCELLED],
        self::STATUS_READY_FOR_PICKUP => [self::STATUS_OUT_FOR_DELIVERY, self::STATUS_CANCELLED],
        self::STATUS_OUT_FOR_DELIVERY => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
        self::STATUS_COMPLETED => [],
        self::STATUS_CANCELLED => [],
    ];

    public const FULFILMENT_PICKUP = 'pickup';

    public const FULFILMENT_DELIVERY = 'delivery';

    protected $fillable = [
        'menu_id',
        'customer_id',
        'status',
        'fulfilment',
        'delivery_notes',
        'placed_at',
        'expires_at',
    ];

    protected $casts = [
        'total' => 'integer',
        'placed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function updates(): HasMany
    {
        return $this->hasMany(OrderUpdate::class);
    }

    public function audits(): HasMany
    {
        return $this->updates();
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::STATUS_TRANSITIONS[$this->status] ?? [], true);
    }
}
