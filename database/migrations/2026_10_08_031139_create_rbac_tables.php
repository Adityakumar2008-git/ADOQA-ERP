<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('scope', 32)->default('business')->index();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false)->index();
            $table->timestamps();

            $table->index('business_id');
        });

        // Partial unique constraints for roles (PostgreSQL native):
        // Global/system roles (business_id IS NULL) must have unique slugs globally.
        // Business-scoped custom roles must have unique slugs within that business.
        DB::statement('CREATE UNIQUE INDEX roles_global_slug_unique ON roles (slug) WHERE business_id IS NULL');
        DB::statement('CREATE UNIQUE INDEX roles_business_slug_unique ON roles (business_id, slug) WHERE business_id IS NOT NULL');

        Schema::create('permissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('group', 64)->index();
            $table->string('scope', 32)->default('business')->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUlid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
            $table->index('permission_id');
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUlid('business_id')->nullable()->constrained('businesses')->cascadeOnDelete();
            $table->timestamps();

            $table->index('user_id');
            $table->index('role_id');
            $table->index('business_id');
            $table->index(['business_id', 'user_id']);
        });

        // Unique constraints for user_roles:
        // Platform roles have business_id IS NULL (unique per user and role)
        // Business roles have business_id IS NOT NULL (unique per user, role, and business)
        DB::statement('CREATE UNIQUE INDEX user_roles_platform_unique ON user_roles (user_id, role_id) WHERE business_id IS NULL');
        DB::statement('CREATE UNIQUE INDEX user_roles_business_unique ON user_roles (user_id, role_id, business_id) WHERE business_id IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
