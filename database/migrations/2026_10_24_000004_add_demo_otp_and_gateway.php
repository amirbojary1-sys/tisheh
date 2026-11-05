<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_challenges', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('code_hash');
            $t->string('browser_token_hash', 64);
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->boolean('is_demo')->default(true);
            $t->timestamp('expires_at');
            $t->timestamp('consumed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('demo_payment_attempts', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('material_order_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('provider_reference')->unique();
            $t->string('status', 30)->default('demo_pending')->index();
            $t->unsignedBigInteger('amount');
            $t->char('currency', 3)->default('IRR');
            $t->boolean('is_demo')->default(true);
            $t->timestamp('expires_at');
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demo_payment_attempts');
        Schema::dropIfExists('otp_challenges');
    }
};
