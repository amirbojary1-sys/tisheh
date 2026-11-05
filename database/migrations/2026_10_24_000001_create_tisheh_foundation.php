<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('label');
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
        });
        Schema::create('permission_role', function (Blueprint $t) {
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $t->primary(['role_id', 'permission_id']);
        });
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('role_id')->nullable()->constrained();
            $t->string('last_name')->default('');
            $t->string('mobile', 20)->nullable()->unique();
            $t->timestamp('mobile_verified_at')->nullable();
            $t->string('status', 20)->default('active')->index();
        });
        Schema::create('countries', function (Blueprint $t) {
            $t->char('code', 2)->primary();
            $t->string('name');
            $t->string('currency', 3);
            $t->string('dial_code', 6);
            $t->boolean('enabled')->default(false);
        });
        Schema::create('user_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->char('country_code', 2)->default('IR');
            $t->foreign('country_code')->references('code')->on('countries');
            $t->string('province')->nullable();
            $t->string('city');
            $t->string('specialty')->nullable();
            $t->string('business_name')->nullable();
            $t->text('bio')->nullable();
            $t->string('locale', 8)->default('fa');
            $t->timestamps();
        });
        foreach (['job_categories', 'material_categories'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id();
                $t->string('name')->unique();
                $t->string('slug')->unique();
                $t->timestamps();
            });
        }
        Schema::create('job_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('job_category_id')->constrained();
            $t->string('title');
            $t->text('description');
            $t->text('skills')->nullable();
            $t->string('city')->index();
            $t->string('address');
            $t->unsignedSmallInteger('workers_count');
            $t->date('start_date');
            $t->unsignedSmallInteger('duration_days');
            $t->unsignedBigInteger('wage')->nullable();
            $t->char('currency', 3)->default('IRR');
            $t->string('payment_type', 20);
            $t->string('contact_method', 20)->default('mobile');
            $t->string('status', 24)->default('pending')->index();
            $t->text('moderation_note')->nullable();
            $t->timestamps();
            $t->index(['status', 'city', 'job_category_id']);
        });
        Schema::create('job_applications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('job_request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained();
            $t->text('message');
            $t->string('status', 20)->default('pending');
            $t->timestamps();
            $t->unique(['job_request_id', 'user_id']);
        });
        Schema::create('seller_locations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained();
            $t->string('name');
            $t->string('city');
            $t->string('address');
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->timestamps();
        });
        Schema::create('material_products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('material_category_id')->constrained();
            $t->string('name');
            $t->string('brand');
            $t->text('specifications');
            $t->unsignedBigInteger('price');
            $t->char('currency', 3)->default('IRR');
            $t->string('unit', 24);
            $t->unsignedInteger('stock');
            $t->unsignedInteger('minimum_order')->default(1);
            $t->unsignedBigInteger('shipping_cost')->nullable();
            $t->text('sale_terms')->nullable();
            $t->string('city')->index();
            $t->string('status', 20)->default('pending')->index();
            $t->text('moderation_note')->nullable();
            $t->timestamps();
            $t->index(['status', 'material_category_id', 'price']);
        });
        Schema::create('product_images', function (Blueprint $t) {
            $t->id();
            $t->foreignId('material_product_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->unsignedSmallInteger('position')->default(0);
            $t->timestamps();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action')->index();
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->json('metadata')->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary();
            $t->json('value');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'audit_logs', 'product_images', 'material_products', 'seller_locations', 'job_applications', 'job_requests', 'material_categories', 'job_categories', 'user_profiles'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('role_id');
            $t->dropColumn(['last_name', 'mobile', 'mobile_verified_at', 'status']);
        });
        foreach (['countries', 'permission_role', 'permissions', 'roles'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
