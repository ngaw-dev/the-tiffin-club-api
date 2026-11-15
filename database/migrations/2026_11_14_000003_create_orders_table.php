<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->string('manage_token', 64)->unique();
            $table->string('status')->default('confirmed');
            $table->string('fulfilment'); // pickup|delivery
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->text('delivery_notes')->nullable();
            $table->unsignedInteger('total')->default(0); // cents
            $table->timestamp('placed_at');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price'); // cents, snapshot at order time
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
