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
        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->string('batch_number', 128);
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('cost_price', 12, 2)->default(0.00);
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();

            $table->unique(['business_id', 'item_id', 'batch_number']);
            $table->index(['business_id', 'item_id', 'expiry_date']);
        });

        Schema::create('inventory_units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->string('serial_number', 128)->nullable();
            $table->string('imei1', 64)->nullable()->unique();
            $table->string('imei2', 64)->nullable()->unique();
            $table->decimal('cost_price', 12, 2)->default(0.00);
            $table->string('status', 32)->default('available')->index();
            $table->timestamps();

            $table->unique(['business_id', 'item_id', 'serial_number']);
            $table->index(['business_id', 'warehouse_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_units');
        Schema::dropIfExists('inventory_batches');
    }
};
