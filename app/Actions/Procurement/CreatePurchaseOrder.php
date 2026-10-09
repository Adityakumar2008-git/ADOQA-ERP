<?php

namespace App\Actions\Procurement;

use App\Enums\PurchaseOrderStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePurchaseOrder
{
    /**
     * @param array{
     *     supplier_id: string,
     *     order_date?: ?string,
     *     expected_delivery_date?: ?string,
     *     currency?: string,
     *     notes?: ?string,
     *     lines: array<array{
     *         item_id: string,
     *         ordered_quantity: numeric,
     *         unit_price: numeric,
     *         tax_rate?: ?numeric,
     *         discount_amount?: ?numeric
     *     }>
     * } $data
     */
    public function execute(User $actor, Business $business, array $data): PurchaseOrder
    {
        $supplier = Supplier::where('id', $data['supplier_id'])->where('business_id', $business->id)->firstOrFail();
        if (! $supplier->isActive()) {
            throw ValidationException::withMessages(['supplier_id' => ['Cannot create purchase order for an inactive supplier.']]);
        }

        $linesData = (array) ($data['lines'] ?? []);
        if (empty($linesData)) {
            throw ValidationException::withMessages(['lines' => ['A purchase order must contain at least one line item.']]);
        }

        $orderNumber = 'PO-'.strtoupper(bin2hex(random_bytes(4)));

        return DB::transaction(function () use ($actor, $business, $supplier, $orderNumber, $linesData, $data) {
            $subtotal = 0.00;
            $totalTax = 0.00;
            $totalDiscount = 0.00;
            $processedLines = [];

            foreach ($linesData as $line) {
                $item = Item::where('id', $line['item_id'])->where('business_id', $business->id)->firstOrFail();
                if (! $item->isActive()) {
                    throw ValidationException::withMessages(['lines' => ["Item '{$item->name}' is inactive and cannot be ordered."]]);
                }

                $qty = (float) $line['ordered_quantity'];
                if ($qty <= 0) {
                    throw ValidationException::withMessages(['lines' => ['Ordered quantity must be greater than zero.']]);
                }

                $price = (float) $line['unit_price'];
                if ($price < 0) {
                    throw ValidationException::withMessages(['lines' => ['Unit price cannot be negative.']]);
                }

                $taxRate = (float) ($line['tax_rate'] ?? $item->tax_rate ?? 0.00);
                $discount = (float) ($line['discount_amount'] ?? 0.00);

                $lineSubtotal = round($qty * $price, 2);
                $lineTax = round($lineSubtotal * ($taxRate / 100.0), 2);
                $lineTotal = round($lineSubtotal + $lineTax - $discount, 2);

                $subtotal += $lineSubtotal;
                $totalTax += $lineTax;
                $totalDiscount += $discount;

                $processedLines[] = [
                    'item' => $item,
                    'ordered_quantity' => $qty,
                    'unit_price' => $price,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $lineTax,
                    'discount_amount' => $discount,
                    'subtotal' => $lineSubtotal,
                    'total_amount' => $lineTotal,
                ];
            }

            $orderTotal = round($subtotal + $totalTax - $totalDiscount, 2);

            $order = PurchaseOrder::create([
                'business_id' => $business->id,
                'supplier_id' => $supplier->id,
                'order_number' => $orderNumber,
                'order_date' => ! empty($data['order_date']) ? Carbon::parse($data['order_date']) : now()->toDateString(),
                'expected_delivery_date' => ! empty($data['expected_delivery_date']) ? Carbon::parse($data['expected_delivery_date']) : null,
                'currency' => $data['currency'] ?? $business->currency,
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'discount_amount' => $totalDiscount,
                'total_amount' => $orderTotal,
                'status' => PurchaseOrderStatus::Draft,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($processedLines as $pl) {
                PurchaseOrderLine::create([
                    'purchase_order_id' => $order->id,
                    'item_id' => $pl['item']->id,
                    'ordered_quantity' => $pl['ordered_quantity'],
                    'received_quantity' => 0.0000,
                    'billed_quantity' => 0.0000,
                    'unit_price' => $pl['unit_price'],
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
                'action' => 'purchase_order.created',
                'auditable_type' => PurchaseOrder::class,
                'auditable_id' => $order->id,
                'payload' => [
                    'order_number' => $order->order_number,
                    'supplier_name' => $supplier->name,
                    'total_amount' => $orderTotal,
                ],
            ]);

            return $order->load(['lines.item', 'supplier']);
        });
    }
}
