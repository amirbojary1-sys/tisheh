<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('material_orders', function (Blueprint $t) {
            $t->timestamp('reservation_expires_at')->nullable()->index();
            $t->boolean('quote_acceptance_required')->default(false);
            $t->timestamp('quote_accepted_at')->nullable();
        });
        Schema::table('order_events', fn (Blueprint $t) => $t->unsignedBigInteger('user_id')->nullable()->change());
    }

    public function down(): void
    {
        Schema::table('material_orders', fn (Blueprint $t) => $t->dropColumn(['reservation_expires_at', 'quote_acceptance_required', 'quote_accepted_at'])); /* System events retain nullable actor, avoiding destructive loss on rollback. */
    }
};
