<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The store's global settings, stored as one row per key. The KEYS
 * map is the single source of truth for which settings exist and
 * their storage type; adding a setting means adding a key here.
 */
class GlobalSetting extends Model
{
    /** @var array<string, string> setting key => storage type */
    public const KEYS = [
        'delivery_suburbs' => 'array',
        'order_cutoff_time' => 'time',
        'opens_at' => 'time',
        'pickup_start_time' => 'time',
        'pickup_end_time' => 'time',
        'store_phone' => 'string',
        'store_phone_alternate' => 'string',
        'store_email' => 'string',
        'pickup_address' => 'string',
        'pickup_map_url' => 'string',
    ];

    protected $fillable = ['key', 'value'];

    /**
     * A typed setting value: arrays come back decoded, times as
     * Carbon, everything else as the stored string (or null).
     */
    public static function get(string $key): mixed
    {
        $value = DB::table('global_settings')->where('key', $key)->value('value');

        return match (self::KEYS[$key] ?? 'string') {
            'array' => $value === null ? [] : json_decode($value, true),
            'time' => $value === null ? null : Carbon::parse($value),
            default => $value,
        };
    }

    /**
     * Upsert a setting value; arrays are stored as JSON.
     */
    public static function set(string $key, mixed $value): void
    {
        $stored = is_array($value) ? json_encode($value) : $value;

        DB::table('global_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $stored, 'updated_at' => now()],
        );
    }

    /**
     * All settings as a flat key => value array, straight from the rows.
     *
     * @return array<string, mixed>
     */
    public static function allSettings(): array
    {
        return DB::table('global_settings')->pluck('value', 'key')->all();
    }
}
