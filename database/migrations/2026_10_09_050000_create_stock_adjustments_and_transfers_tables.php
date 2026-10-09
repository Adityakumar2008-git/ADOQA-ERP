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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->string('adjustment_number', 64);
            $table->date('adjustment_date');
            $table->string('reason', 255);
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'adjustment_number']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('stock_adjustment_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignUlid('inventory_unit_id')->nullable()->constrained('inventory_units')->nullOnDelete();
            $table->decimal('system_quantity', 14, 4);
            $table->decimal('counted_quantity', 14, 4);
            $table->decimal('adjustment_quantity', 14, 4);
            $table->decimal('unit_cost', 12, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('transfer_number', 64);
            $table->foreignUlid('source_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignUlid('destination_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('transfer_date');
            $table->string('status', 32)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignUlid('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dispatched_at')->nullable();
            $table->foreignUlid('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'transfer_number']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('stock_transfer_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('stock_transfer_id')->constrained('stock_transfers')->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignUlid('batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->foreignUlid('inventory_unit_id')->nullable()->constrained('inventory_units')->nullOnDelete();
            $table->decimal('quantity_dispatched', 14, 4);
            $table->decimal('quantity_received', 14, 4)->default(0.0000);
            $table->decimal('quantity_damaged', 14, 4)->default(0.0000);
            $table->decimal('quantity_lost', 14, 4)->default(0.0000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_lines');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_adjustment_lines');
        Schema::dropIfExists('stock_adjustments');
    }
};
