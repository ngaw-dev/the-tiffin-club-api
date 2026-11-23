<?php

namespace Database\Seeders;

use App\Models\GlobalSetting;
use Illuminate\Database\Seeder;

class GlobalSettingSeeder extends Seeder
{
    /**
     * Seed the default store settings rows. Runs idempotently via
     * updateOrInsert so re-seeding never duplicates or clobbers edits.
     */
    public function run(): void
    {
        // Only seed fields the store always has; the optional pickup
        // window and map URL stay unset (null) until staff fills them in.
        $defaults = [
            'delivery_suburbs' => config('tiffin.delivery_suburbs'),
            'order_cutoff_time' => config('tiffin.default_cutoff_time'),
            'opens_at' => config('tiffin.store_opens_time'),
            'store_phone' => '021 123 4567',
            'store_phone_alternate' => '04 555 1234',
            'store_email' => 'hello@thetiffinclub.co.nz',
            'pickup_address' => '123 Cuba Street, Te Aro, Wellington',
            'pickup_start_time' => '17:00',
            'pickup_end_time' => '19:00',
            'pickup_map_url' => 'https://maps.example.com/...',
        ];

        foreach ($defaults as $key => $value) {
            GlobalSetting::set($key, $value);
        }
    }
}
