<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The store's single row of operator-editable global settings. Seeded
 * from config at migration time; edited directly in the DB afterwards.
 */
class GlobalSetting extends Model
{
    protected $fillable = [
        'delivery_suburbs',
        'order_cutoff_time',
        'pickup_start_time',
        'pickup_end_time',
        'store_phone',
        'store_phone_alternate',
        'store_email',
    ];

    protected function casts(): array
    {
        return [
            'delivery_suburbs' => 'array',
            'order_cutoff_time' => 'datetime:H:i',
            'pickup_start_time' => 'datetime:H:i',
            'pickup_end_time' => 'datetime:H:i',
        ];
    }

    /**
     * The single settings row, firstOrCreate'd so a missing row falls
     * back to config defaults rather than breaking the app.
     */
    public static function current(): self
    {
        return static::query()->firstOrFail();
    }
}
