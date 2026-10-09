<?php

namespace App\Actions\Procurement;

use App\Enums\GoodsReceiptStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\GoodsReceiptUnit;
use App\Models\InventoryUnit;
use App\Models\Item;
use App\Models\PurchaseBill;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Auth\AuthorizationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateGoodsReceipt
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * @param array{
     *     supplier_id: string,
     *     warehouse_id: string,
     *     purchase_order_id?: ?string,
     *     purchase_bill_id?: ?string,
     *     received_date?: ?string,
     *     notes?: ?string,
     *     lines: array<array{
     *         item_id: string,
     *         accepted_quantity: numeric,
     *         rejected_quantity?: numeric,
     *         actual_unit_cost: numeric,
     *         batch_number?: ?string,
     *         manufacturing_date?: ?string,
     *         expiry_date?: ?string,
     *         serial_numbers?: array<string>,
     *         imeis?: array<array{imei1: string, imei2?: ?string}>
     *     }>
     * } $data
     */
    public function execute(User $actor, Business $business, array $data): GoodsReceipt
    {
        // Authorize actor possesses purchases.receipts.prepare permission
        if ($this->authorizationService->cannot($actor, 'purchases.receipts.prepare', $business)) {
            throw ValidationException::withMessages(['authorization' => ['You do not have permission to prepare goods receipts.']]);
        }

        $supplier = Supplier::where('id', $data['supplier_id'])->where('business_id', $business->id)->firstOrFail();
        $warehouse = Warehouse::where('id', $data['warehouse_id'])->where('business_id', $business->id)->firstOrFail();

        $po = null;
        if (! empty($data['purchase_order_id'])) {
            $po = PurchaseOrder::where('id', $data['purchase_order_id'])->where('business_id', $business->id)->firstOrFail();
        }

        $bill = null;
        if (! empty($data['purchase_bill_id'])) {
            $bill = PurchaseBill::where('id', $data['purchase_bill_id'])->where('business_id', $business->id)->firstOrFail();
        }

        $linesData = (array) ($data['lines'] ?? []);
        if (empty($linesData)) {
            throw ValidationException::withMessages(['lines' => ['A goods receipt must include at least one item line.']]);
        }

        $receiptNumber = 'GRN-'.strtoupper(bin2hex(random_bytes(4)));

        return DB::transaction(function () use ($actor, $business, $supplier, $warehouse, $po, $bill, $receiptNumber, $linesData, $data) {
            $receipt = GoodsReceipt::create([
                'business_id' => $business->id,
                'supplier_id' => $supplier->id,
                'warehouse_id' => $warehouse->id,
                'purchase_order_id' => $po?->id,
                'purchase_bill_id' => $bill?->id,
                'receipt_number' => $receiptNumber,
                'received_date' => ! empty($data['received_date']) ? Carbon::parse($data['received_date']) : now()->toDateString(),
                'status' => GoodsReceiptStatus::Draft,
                'notes' => $data['notes'] ?? null,
                'prepared_by' => $actor->id,
            ]);

            foreach ($linesData as $line) {
                $item = Item::where('id', $line['item_id'])->where('business_id', $business->id)->firstOrFail();
                $acceptedQty = (float) $line['accepted_quantity'];
                $rejectedQty = (float) ($line['rejected_quantity'] ?? 0.0000);

                if ($acceptedQty <= 0 && $rejectedQty <= 0) {
                    throw ValidationException::withMessages(['lines' => ['Line quantity must be greater than zero.']]);
                }

                $cost = (float) $line['actual_unit_cost'];
                if ($cost < 0) {
                    throw ValidationException::withMessages(['lines' => ['Actual unit cost cannot be negative.']]);
                }

                $mfgDate = ! empty($line['manufacturing_date']) ? Carbon::parse($line['manufacturing_date']) : null;
                $expDate = ! empty($line['expiry_date']) ? Carbon::parse($line['expiry_date']) : null;

                $grLine = GoodsReceiptLine::create([
                    'goods_receipt_id' => $receipt->id,
                    'item_id' => $item->id,
                    'accepted_quantity' => $acceptedQty,
                    'rejected_quantity' => $rejectedQty,
                    'actual_unit_cost' => $cost,
                    'batch_number' => filled($line['batch_number'] ?? null) ? strtoupper(trim($line['batch_number'])) : null,
                    'manufacturing_date' => $mfgDate,
                    'expiry_date' => $expDate,
                ]);

                // Prepare units for serial-tracked items
                if ($item->inventory_mode->value === 'serial' && ! empty($line['serial_numbers'])) {
                    foreach ((array) $line['serial_numbers'] as $serial) {
                        $trimmed = trim($serial);
                        $unit = InventoryUnit::create([
                            'business_id' => $business->id,
                            'item_id' => $item->id,
                            'warehouse_id' => $warehouse->id,
                            'serial_number' => $trimmed,
                            'cost_price' => $cost,
                            'status' => 'available',
                        ]);

                        GoodsReceiptUnit::create([
                            'goods_receipt_line_id' => $grLine->id,
                            'inventory_unit_id' => $unit->id,
                        ]);
                    }
                }

                // Prepare units for IMEI-tracked items
                if ($item->inventory_mode->value === 'imei' && ! empty($line['imeis'])) {
                    foreach ((array) $line['imeis'] as $imeiDef) {
                        $imei1 = trim($imeiDef['imei1'] ?? '');
                        $imei2 = filled($imeiDef['imei2'] ?? null) ? trim($imeiDef['imei2']) : null;

                        $unit = InventoryUnit::create([
                            'business_id' => $business->id,
                            'item_id' => $item->id,
                            'warehouse_id' => $warehouse->id,
                            'imei1' => $imei1,
                            'imei2' => $imei2,
                            'cost_price' => $cost,
                            'status' => 'available',
                        ]);

                        GoodsReceiptUnit::create([
                            'goods_receipt_line_id' => $grLine->id,
                            'inventory_unit_id' => $unit->id,
                        ]);
                    }
                }
            }

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'goods_receipt.created',
                'auditable_type' => GoodsReceipt::class,
                'auditable_id' => $receipt->id,
                'payload' => [
                    'receipt_number' => $receipt->receipt_number,
                    'supplier_name' => $supplier->name,
                    'warehouse_name' => $warehouse->name,
                ],
            ]);

            return $receipt->load(['lines.item', 'supplier', 'warehouse']);
        });
    }
}
