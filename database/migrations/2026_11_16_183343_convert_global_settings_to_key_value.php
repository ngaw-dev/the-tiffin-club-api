<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Setting keys that live in the key-value rows, with their
     * storage type. Single source of truth shared by the model.
     */
    public const KEY_TYPES = [
        'delivery_suburbs' => 'array',
        'order_cutoff_time' => 'time',
        'pickup_start_time' => 'time',
        'pickup_end_time' => 'time',
        'store_phone' => 'string',
        'store_phone_alternate' => 'string',
        'store_email' => 'string',
        'pickup_address' => 'string',
        'pickup_map_url' => 'string',
    ];

    public function up(): void
    {
        $old = DB::table('global_settings')->first();

        Schema::drop('global_settings');
        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Temp-save: carry the old column values across as key rows.
        $values = $old
            ? collect($old)->only(array_keys(self::KEY_TYPES))->all()
            : [];

        foreach (self::KEY_TYPES as $key => $type) {
            if (! isset($values[$key])) {
                continue;
            }

            DB::table('global_settings')->insert([
                'key' => $key,
                'value' => is_array($values[$key]) ? json_encode($values[$key]) : $values[$key],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $rows = DB::table('global_settings')->get();

        Schema::drop('global_settings');
        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->json('delivery_suburbs');
            $table->time('order_cutoff_time');
            $table->time('pickup_start_time')->nullable();
            $table->time('pickup_end_time')->nullable();
            $table->string('store_phone', 32)->default('021 123 4567');
            $table->string('store_phone_alternate', 32)->default('04 555 1234');
            $table->string('store_email')->default('hello@thetiffinclub.co.nz');
            $table->string('pickup_address')->default('123 Cuba Street, Te Aro, Wellington');
            $table->string('pickup_map_url')->nullable();
            $table->timestamps();
        });

        $v = collect($rows)->pluck('value', 'key');
        DB::table('global_settings')->insert([
            'delivery_suburbs' => $v['delivery_suburbs'] ?? json_encode(config('tiffin.delivery_suburbs')),
            'order_cutoff_time' => $v['order_cutoff_time'] ?? config('tiffin.default_cutoff_time'),
            'pickup_start_time' => $v['pickup_start_time'],
            'pickup_end_time' => $v['pickup_end_time'],
            'store_phone' => $v['store_phone'] ?? '021 123 4567',
            'store_phone_alternate' => $v['store_phone_alternate'] ?? '04 555 1234',
            'store_email' => $v['store_email'] ?? 'hello@thetiffinclub.co.nz',
            'pickup_address' => $v['pickup_address'] ?? '123 Cuba Street, Te Aro, Wellington',
            'pickup_map_url' => $v['pickup_map_url'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
