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
        // 1. Tenant Module Configurations
        Schema::create('business_module_settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('module_slug', 64);
            $table->boolean('is_enabled')->default(true);
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'module_slug']);
            $table->index(['business_id', 'is_enabled']);
        });

        // 2. Tenant Role-Permission Overrides (INHERIT, ALLOW, DENY)
        Schema::create('business_role_permission_overrides', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUlid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->string('state', 32)->default('inherit');
            $table->foreignUlid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'role_id', 'permission_id'], 'biz_role_perm_override_unique');
            $table->index(['business_id', 'role_id'], 'biz_role_perm_override_biz_role_idx');
        });

        // PostgreSQL CHECK constraint enforcing allowable override states
        DB::statement("ALTER TABLE business_role_permission_overrides ADD CONSTRAINT chk_override_state CHECK (state IN ('inherit', 'allow', 'deny'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_role_permission_overrides');
        Schema::dropIfExists('business_module_settings');
    }
};
