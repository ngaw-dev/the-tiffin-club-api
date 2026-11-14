<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->json('delivery_suburbs');
            $table->time('order_cutoff_time');
            $table->time('pickup_start_time')->nullable();
            $table->time('pickup_end_time')->nullable();
            $table->timestamps();
        });

        // Seed the single row from the previous config/env values so the
        // cutover is invisible; further edits happen directly in the DB.
        DB::table('global_settings')->insert([
            'delivery_suburbs' => json_encode(config('tiffin.delivery_suburbs')),
            'order_cutoff_time' => config('tiffin.default_cutoff_time'),
            'pickup_start_time' => null,
            'pickup_end_time' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('global_settings');
    }
};
