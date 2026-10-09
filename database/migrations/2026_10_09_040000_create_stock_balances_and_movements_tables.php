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
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignUlid('location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->foreignUlid('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->decimal('quantity', 14, 4)->default(0.0000);
            $table->timestamps();

            $table->index(['business_id', 'item_id']);
            $table->index(['business_id', 'warehouse_id']);
        });

        // Enforce strict uniqueness for balance projection handling nullable location and batch
        DB::statement("
            CREATE UNIQUE INDEX stock_balances_unique_dim
            ON stock_balances (
                business_id,
                item_id,
                warehouse_id,
                COALESCE(location_id, '00000000000000000000000000'),
                COALESCE(batch_id, '00000000000000000000000000')
            )
        ");

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('location_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->foreignUlid('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignUlid('inventory_unit_id')->nullable()->constrained('inventory_units')->nullOnDelete();
            $table->string('movement_type', 32)->index();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 12, 2)->default(0.00);
            $table->string('source_type', 128);
            $table->string('source_id', 64)->nullable();
            $table->string('source_line_id', 64)->nullable();
            $table->foreignUlid('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at')->index();
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('reference', 128)->nullable();
            $table->timestamps();

            $table->index(['business_id', 'item_id', 'occurred_at']);
            $table->index(['business_id', 'warehouse_id']);
            $table->index(['business_id', 'source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_balances');
    }
};
