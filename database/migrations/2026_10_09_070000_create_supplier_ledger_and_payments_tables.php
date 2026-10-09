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
        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('payment_number', 64);
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('payment_mode', 32);
            $table->string('reference_number', 128)->nullable();
            $table->decimal('allocated_amount', 14, 2)->default(0.00);
            $table->decimal('advance_amount', 14, 2)->default(0.00);
            $table->string('status', 32)->default('posted')->index();
            $table->text('notes')->nullable();
            $table->foreignUlid('posted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('posted_at');
            $table->foreignUlid('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->string('idempotency_key', 128)->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'payment_number']);
            $table->unique(['business_id', 'idempotency_key']);
            $table->index(['business_id', 'supplier_id']);
        });

        Schema::create('supplier_payment_allocations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('supplier_payment_id')->constrained('supplier_payments')->cascadeOnDelete();
            $table->foreignUlid('purchase_bill_id')->constrained('purchase_bills')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->unique(['supplier_payment_id', 'purchase_bill_id']);
        });

        Schema::create('supplier_ledger_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignUlid('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('entry_type', 32)->index();
            $table->decimal('amount', 14, 2);
            $table->decimal('running_balance', 14, 2)->default(0.00);
            $table->string('source_type', 128);
            $table->string('source_id', 64);
            $table->string('source_reference', 128);
            $table->timestamp('posted_at')->index();
            $table->foreignUlid('actor_id')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->ulid('reversal_entry_id')->nullable()->index();
            $table->timestamps();

            $table->index(['business_id', 'supplier_id', 'posted_at']);
            $table->index(['business_id', 'source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_ledger_entries');
        Schema::dropIfExists('supplier_payment_allocations');
        Schema::dropIfExists('supplier_payments');
    }
};
