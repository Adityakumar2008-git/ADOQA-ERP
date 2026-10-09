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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('code', 64)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('gst_number', 64)->nullable();
            $table->decimal('credit_limit', 14, 2)->nullable();
            $table->decimal('opening_balance', 14, 2)->default(0.00);
            $table->string('status', 32)->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'code']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('order_number', 64);
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('currency', 8)->default('INR');
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->string('status', 32)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'order_number']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('purchase_order_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('ordered_quantity', 14, 4);
            $table->decimal('received_quantity', 14, 4)->default(0.0000);
            $table->decimal('billed_quantity', 14, 4)->default(0.0000);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('subtotal', 14, 2);
            $table->decimal('total_amount', 14, 2);
            $table->timestamps();
        });

        Schema::create('purchase_bills', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignUlid('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('bill_number', 64);
            $table->string('supplier_invoice_number', 128);
            $table->date('bill_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('tax_amount', 14, 2)->default(0.00);
            $table->decimal('discount_amount', 14, 2)->default(0.00);
            $table->decimal('total_amount', 14, 2)->default(0.00);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->string('status', 32)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignUlid('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'bill_number']);
            $table->unique(['business_id', 'supplier_id', 'supplier_invoice_number']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('purchase_bill_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_bill_id')->constrained('purchase_bills')->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('subtotal', 14, 2);
            $table->decimal('total_amount', 14, 2);
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignUlid('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignUlid('purchase_bill_id')->nullable()->constrained('purchase_bills')->nullOnDelete();
            $table->string('receipt_number', 64);
            $table->foreignUlid('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->date('received_date');
            $table->string('status', 32)->default('draft')->index();
            $table->text('notes')->nullable();
            $table->foreignUlid('prepared_by')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'receipt_number']);
            $table->index(['business_id', 'status']);
        });

        Schema::create('goods_receipt_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete();
            $table->foreignUlid('item_id')->constrained('items')->restrictOnDelete();
            $table->decimal('accepted_quantity', 14, 4);
            $table->decimal('rejected_quantity', 14, 4)->default(0.0000);
            $table->decimal('actual_unit_cost', 12, 2)->default(0.00);
            $table->string('batch_number', 128)->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        Schema::create('goods_receipt_units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('goods_receipt_line_id')->constrained('goods_receipt_lines')->cascadeOnDelete();
            $table->foreignUlid('inventory_unit_id')->constrained('inventory_units')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_units');
        Schema::dropIfExists('goods_receipt_lines');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('purchase_bill_lines');
        Schema::dropIfExists('purchase_bills');
        Schema::dropIfExists('purchase_order_lines');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};
