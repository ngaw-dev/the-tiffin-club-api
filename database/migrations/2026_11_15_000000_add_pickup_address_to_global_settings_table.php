<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_settings', function (Blueprint $table) {
            // shortcut: placeholder defaults, replace with the real address + map share link in the DB
            $table->string('pickup_address')->default('123 Cuba Street, Te Aro, Wellington');
            $table->string('pickup_map_url')->nullable();
        });

        DB::table('global_settings')->update(['updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('global_settings', function (Blueprint $table) {
            $table->dropColumn(['pickup_address', 'pickup_map_url']);
        });
    }
};
