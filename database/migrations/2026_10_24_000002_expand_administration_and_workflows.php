<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('conversations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('initiator_id')->constrained('users');
            $t->foreignId('recipient_id')->constrained('users');
            $t->string('context_type', 20);
            $t->unsignedBigInteger('context_id');
            $t->string('title');
            $t->timestamp('initiator_read_at')->nullable();
            $t->timestamp('recipient_read_at')->nullable();
            $t->timestamps();
            $t->unique(['initiator_id', 'context_type', 'context_id']);
        });
        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained();
            $t->text('body');
            $t->timestamps();
        });
        Schema::create('reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->string('subject_type', 20);
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->string('reason');
            $t->text('description');
            $t->string('status', 20)->default('open')->index();
            $t->text('admin_response')->nullable();
            $t->foreignId('assigned_to')->nullable()->constrained('users');
            $t->timestamps();
        });
        Schema::create('content_pages', function (Blueprint $t) {
            $t->id();
            $t->string('slug')->unique();
            $t->string('title');
            $t->text('body');
            $t->boolean('published')->default(false);
            $t->timestamps();
        });
        Schema::create('material_orders', function (Blueprint $t) {
            $t->id();
            $t->uuid('reference')->unique();
            $t->uuid('idempotency_key')->unique();
            $t->foreignId('buyer_id')->constrained('users');
            $t->foreignId('seller_id')->constrained('users');
            $t->string('status', 24)->default('awaiting_seller')->index();
            $t->char('currency', 3)->default('IRR');
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('shipping_cost')->nullable();
            $t->unsignedBigInteger('total')->nullable();
            $t->string('payment_method', 16)->default('direct');
            $t->string('payment_status', 24)->default('unverified_external');
            $t->text('delivery_address');
            $t->text('note')->nullable();
            $t->timestamps();
        });
        Schema::create('material_order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('material_product_id')->constrained();
            $t->string('name');
            $t->string('brand');
            $t->string('unit');
            $t->unsignedBigInteger('unit_price');
            $t->unsignedInteger('quantity');
            $t->unsignedBigInteger('line_total');
            $t->timestamps();
        });
        Schema::create('order_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_order_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained();
            $t->string('status');
            $t->text('note')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['order_events', 'material_order_items', 'material_orders', 'content_pages', 'reports', 'messages', 'conversations', 'notifications'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
