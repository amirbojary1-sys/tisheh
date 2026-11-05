<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_user', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('role_id')->constrained()->cascadeOnDelete();
            $t->primary(['user_id', 'role_id']);
        });
        DB::table('users')->whereNotNull('role_id')->orderBy('id')->chunkById(200, function ($users) {
            foreach ($users as $u) {
                DB::table('role_user')->insertOrIgnore(['user_id' => $u->id, 'role_id' => $u->role_id]);
            }
        });
        Schema::table('user_profiles', function (Blueprint $t) {
            foreach (['national_id', 'contact_phone', 'private_address', 'postal_code'] as $field) {
                $t->text($field)->nullable();
            }
            $t->string('national_id_fingerprint', 64)->nullable()->unique();
            $t->string('identity_status', 24)->default('not_submitted')->index();
            $t->unsignedInteger('identity_revision')->default(0);
            $t->timestamp('identity_submitted_at')->nullable();
            $t->timestamp('identity_reviewed_at')->nullable();
            $t->foreignId('identity_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('identity_review_note')->nullable();
            $t->string('headline', 120)->nullable();
            $t->string('activity_type', 32)->default('worker');
            $t->unsignedTinyInteger('experience_years')->default(0);
            $t->string('availability', 20)->default('available');
            $t->boolean('directory_enabled')->default(false);
            $t->string('directory_status', 24)->default('draft');
            $t->unsignedInteger('directory_revision')->default(0);
            $t->text('directory_review_note')->nullable();
            $t->boolean('public_contact')->default(false);
            $t->index(['directory_status', 'directory_enabled', 'city'], 'directory_public_city');
        });
        Schema::create('job_category_user', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('job_category_id')->constrained()->cascadeOnDelete();
            $t->primary(['user_id', 'job_category_id']);
        });
        Schema::create('portfolio_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('title', 120);
            $t->text('description');
            $t->unsignedSmallInteger('completed_year')->nullable();
            $t->string('image_path')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_entries');
        Schema::dropIfExists('job_category_user');
        Schema::table('user_profiles', function (Blueprint $t) {
            $t->dropForeign(['identity_reviewer_id']);
            $t->dropIndex('directory_public_city');
            $t->dropUnique(['national_id_fingerprint']);
            $t->dropIndex(['identity_status']);
            $t->dropColumn(['national_id', 'contact_phone', 'private_address', 'postal_code', 'national_id_fingerprint', 'identity_status', 'identity_revision', 'identity_submitted_at', 'identity_reviewed_at', 'identity_reviewer_id', 'identity_review_note', 'headline', 'activity_type', 'experience_years', 'availability', 'directory_enabled', 'directory_status', 'directory_revision', 'directory_review_note', 'public_contact']);
        });
        Schema::dropIfExists('role_user');
    }
};
