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
        $defaults = [
            'delivery_suburbs' => config('tiffin.delivery_suburbs'),
            'order_cutoff_time' => config('tiffin.default_cutoff_time'),
            'pickup_start_time' => '16:00:00',
            'pickup_end_time' => '23:00:00',
            'store_phone' => '021 123 4567',
            'store_phone_alternate' => '04 555 1234',
            'store_email' => 'hello@thetiffinclub.co.nz',
            'pickup_address' => '123 Cuba Street, Te Aro, Wellington',
            'pickup_map_url' => 'https://google.com',
        ];

        foreach ($defaults as $key => $value) {
            GlobalSetting::set($key, $value);
        }
    }
}
