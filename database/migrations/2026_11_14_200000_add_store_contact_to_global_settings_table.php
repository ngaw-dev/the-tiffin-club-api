<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_settings', function (Blueprint $table) {
            $table->string('store_phone', 32)->default('021 123 4567');
            $table->string('store_phone_alternate', 32)->default('04 555 1234');
            $table->string('store_email')->default('hello@thetiffinclub.co.nz');
        });

        DB::table('global_settings')->update(['updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('global_settings', function (Blueprint $table) {
            $table->dropColumn(['store_phone', 'store_phone_alternate', 'store_email']);
        });
    }
};
