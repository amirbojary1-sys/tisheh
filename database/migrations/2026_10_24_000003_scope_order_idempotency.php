<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_orders', function (Blueprint $t) {
            $t->dropUnique('material_orders_idempotency_key_unique');
            $t->unique(['buyer_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::table('material_orders', function (Blueprint $t) {
            $t->dropUnique(['buyer_id', 'idempotency_key']);
            $t->unique('idempotency_key');
        });
    }
};
