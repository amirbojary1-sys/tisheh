<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('shopping_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();
            $table->unique(['shopping_cart_id', 'material_product_id']);
        });

        Schema::create('cart_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('idempotency_key');
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
        });

        Schema::create('cart_checkout_orders', function (Blueprint $table) {
            $table->foreignId('cart_checkout_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->primary(['cart_checkout_id', 'material_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_checkout_orders');
        Schema::dropIfExists('cart_checkouts');
        Schema::dropIfExists('shopping_cart_items');
        Schema::dropIfExists('shopping_carts');
    }
};
