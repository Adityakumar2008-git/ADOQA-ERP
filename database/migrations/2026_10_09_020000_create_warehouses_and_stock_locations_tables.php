<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name', 128);
            $table->string('code', 64);
            $table->text('address')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();

            $table->unique(['business_id', 'code']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('stock_locations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('name', 128);
            $table->string('code', 64)->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();

            $table->unique(['warehouse_id', 'name']);
            $table->index(['business_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_locations');
        Schema::dropIfExists('warehouses');
    }
};
