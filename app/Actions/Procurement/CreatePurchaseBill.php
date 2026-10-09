<?php

namespace App\Actions\Procurement;

use App\Enums\PurchaseBillStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Item;
use App\Models\PurchaseBill;
use App\Models\PurchaseBillLine;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePurchaseBill
{
    /**
     * @param array{
     *     supplier_id: string,
     *     supplier_invoice_number: string,
     *     purchase_order_id?: ?string,
     *     bill_date?: ?string,
     *     due_date?: ?string,
     *     notes?: ?string,
     *     lines: array<array{
     *         item_id: string,
     *         quantity: numeric,
     *         unit_cost: numeric,
     *         tax_rate?: ?numeric,
     *         discount_amount?: ?numeric
     *     }>
     * } $data
     */
    public function execute(User $actor, Business $business, array $data): PurchaseBill
    {
        $supplier = Supplier::where('id', $data['supplier_id'])->where('business_id', $business->id)->firstOrFail();
        $invoiceNumber = trim($data['supplier_invoice_number']);

        if (empty($invoiceNumber)) {
            throw ValidationException::withMessages(['supplier_invoice_number' => ['Supplier invoice number is required.']]);
        }

        if (PurchaseBill::where('business_id', $business->id)->where('supplier_id', $supplier->id)->where('supplier_invoice_number', $invoiceNumber)->exists()) {
            throw ValidationException::withMessages(['supplier_invoice_number' => ["A bill with invoice number '{$invoiceNumber}' already exists for this supplier."]]);
        }

        $po = null;
        if (! empty($data['purchase_order_id'])) {
            $po = PurchaseOrder::where('id', $data['purchase_order_id'])->where('business_id', $business->id)->firstOrFail();
        }

        $linesData = (array) ($data['lines'] ?? []);
        if (empty($linesData)) {
            throw ValidationException::withMessages(['lines' => ['A purchase bill must include at least one item line.']]);
        }

        $billNumber = 'BILL-'.strtoupper(bin2hex(random_bytes(4)));

        return DB::transaction(function () use ($actor, $business, $supplier, $po, $billNumber, $invoiceNumber, $linesData, $data) {
            $subtotal = 0.00;
            $totalTax = 0.00;
            $totalDiscount = 0.00;
            $processedLines = [];

            foreach ($linesData as $line) {
                $item = Item::where('id', $line['item_id'])->where('business_id', $business->id)->firstOrFail();
                $qty = (float) $line['quantity'];
                if ($qty <= 0) {
                    throw ValidationException::withMessages(['lines' => ['Billed quantity must be greater than zero.']]);
                }

                $cost = (float) $line['unit_cost'];
                if ($cost < 0) {
                    throw ValidationException::withMessages(['lines' => ['Unit cost cannot be negative.']]);
                }

                $taxRate = (float) ($line['tax_rate'] ?? $item->tax_rate ?? 0.00);
                $discount = (float) ($line['discount_amount'] ?? 0.00);

                $lineSubtotal = round($qty * $cost, 2);
                $lineTax = round($lineSubtotal * ($taxRate / 100.0), 2);
                $lineTotal = round($lineSubtotal + $lineTax - $discount, 2);

                $subtotal += $lineSubtotal;
                $totalTax += $lineTax;
                $totalDiscount += $discount;

                $processedLines[] = [
                    'item' => $item,
                    'quantity' => $qty,
                    'unit_cost' => $cost,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'discount_amount' => $discount,
                    'subtotal' => $lineSubtotal,
                    'total_amount' => $lineTotal,
                ];
            }

            $totalAmount = round($subtotal + $totalTax - $totalDiscount, 2);

            $bill = PurchaseBill::create([
                'business_id' => $business->id,
                'supplier_id' => $supplier->id,
                'purchase_order_id' => $po?->id,
                'bill_number' => $billNumber,
                'supplier_invoice_number' => $invoiceNumber,
                'bill_date' => ! empty($data['bill_date']) ? Carbon::parse($data['bill_date']) : now()->toDateString(),
                'due_date' => ! empty($data['due_date']) ? Carbon::parse($data['due_date']) : null,
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'discount_amount' => $totalDiscount,
                'total_amount' => $totalAmount,
                'paid_amount' => 0.00,
                'status' => PurchaseBillStatus::Draft,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($processedLines as $pl) {
                PurchaseBillLine::create([
                    'purchase_bill_id' => $bill->id,
                    'item_id' => $pl['item']->id,
                    'quantity' => $pl['quantity'],
                    'unit_cost' => $pl['unit_cost'],
                    'tax_rate' => $pl['tax_rate'],
                    'tax_amount' => $pl['tax_amount'],
                    'discount_amount' => $pl['discount_amount'],
                    'subtotal' => $pl['subtotal'],
                    'total_amount' => $pl['total_amount'],
                ]);
            }

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'purchase_bill.created',
                'auditable_type' => PurchaseBill::class,
                'auditable_id' => $bill->id,
                'payload' => [
                    'bill_number' => $bill->bill_number,
                    'supplier_invoice_number' => $bill->supplier_invoice_number,
                    'total_amount' => $bill->total_amount,
                ],
            ]);

            return $bill->load(['lines.item', 'supplier']);
        });
    }
}
