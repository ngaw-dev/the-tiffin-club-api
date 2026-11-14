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

    public const STATUS_PLACED = 'placed';

    public const FULFILMENT_PICKUP = 'pickup';

    public const FULFILMENT_DELIVERY = 'delivery';

    protected $fillable = [
        'menu_id',
        'manage_token',
        'status',
        'fulfilment',
        'name',
        'email',
        'phone',
        'address',
        'suburb',
        'total',
        'placed_at',
        'expires_at',
    ];

    protected $casts = [
        'total' => 'integer',
        'placed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
