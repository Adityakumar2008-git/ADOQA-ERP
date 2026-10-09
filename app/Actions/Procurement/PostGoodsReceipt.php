<?php

namespace App\Actions\Procurement;

use App\Actions\Inventory\MutateStock;
use App\Enums\GoodsReceiptStatus;
use App\Enums\InventoryMode;
use App\Enums\StockMovementType;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\GoodsReceipt;
use App\Models\InventoryBatch;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PostGoodsReceipt
{
    public function __construct(
        protected AuthorizationService $authorizationService,
        protected MutateStock $mutateStock
    ) {}

    public function execute(User $actor, Business $business, GoodsReceipt $receipt): GoodsReceipt
    {
        if ($receipt->business_id !== $business->id) {
            throw ValidationException::withMessages(['receipt' => ['Unauthorized goods receipt context.']]);
        }

        // Authorize actor possesses purchases.receipts.post permission
        if ($this->authorizationService->cannot($actor, 'purchases.receipts.post', $business)) {
            throw ValidationException::withMessages(['authorization' => ['You do not have permission to post and approve goods receipts.']]);
        }

        if ($receipt->status !== GoodsReceiptStatus::Draft) {
            throw ValidationException::withMessages(['status' => ["Only draft goods receipts can be posted (current: {$receipt->status->value})."]]);
        }

        return DB::transaction(function () use ($actor, $business, $receipt) {
            $warehouse = $receipt->warehouse;

            foreach ($receipt->lines as $line) {
                $item = $line->item;
                $acceptedQty = (float) $line->accepted_quantity;
                $unitCost = (float) $line->actual_unit_cost;

                if ($acceptedQty <= 0) {
                    continue;
                }

                $batch = null;
                if ($item->inventory_mode === InventoryMode::Batch) {
                    $batchNumber = $line->batch_number;
                    if (empty($batchNumber)) {
                        throw ValidationException::withMessages(['lines' => ["Batch number is required for batch-tracked item '{$item->name}'."]]);
                    }

                    $batch = InventoryBatch::firstOrCreate(
                        [
                            'business_id' => $business->id,
                            'item_id' => $item->id,
                            'batch_number' => $batchNumber,
                        ],
                        [
                            'manufacturing_date' => $line->manufacturing_date,
                            'expiry_date' => $line->expiry_date,
                            'cost_price' => $unitCost,
                            'status' => 'active',
                        ]
                    );
                }

                // Serial & IMEI units
                if (in_array($item->inventory_mode, [InventoryMode::Serial, InventoryMode::Imei], true)) {
                    $units = $line->units;
                    if ($units->count() !== (int) $acceptedQty) {
                        throw ValidationException::withMessages([
                            'lines' => ["Mismatch between accepted quantity ({$acceptedQty}) and registered unit identifiers (".$units->count().") for '{$item->name}'."],
                        ]);
                    }

                    foreach ($units as $receiptUnit) {
                        $physicalUnit = $receiptUnit->unit;
                        $this->mutateStock->execute(
                            business: $business,
                            item: $item,
                            warehouse: $warehouse,
                            movementType: StockMovementType::PurchaseReceipt,
                            quantity: 1.0,
                            unitCost: $unitCost,
                            unit: $physicalUnit,
                            sourceType: 'goods_receipt',
                            sourceId: $receipt->id,
                            sourceLineId: $line->id,
                            actor: $actor,
                            notes: "Goods Receipt {$receipt->receipt_number}"
                        );
                    }
                } else {
                    // Normal, Batch, Accessory
                    $this->mutateStock->execute(
                        business: $business,
                        item: $item,
                        warehouse: $warehouse,
                        movementType: StockMovementType::PurchaseReceipt,
                        quantity: $acceptedQty,
                        unitCost: $unitCost,
                        batch: $batch,
                        sourceType: 'goods_receipt',
                        sourceId: $receipt->id,
                        sourceLineId: $line->id,
                        actor: $actor,
                        notes: "Goods Receipt {$receipt->receipt_number}"
                    );
                }

                // If linked to PO, increment received_quantity on PO line
                if ($receipt->purchase_order_id) {
                    $poLine = PurchaseOrderLine::where('purchase_order_id', $receipt->purchase_order_id)
                        ->where('item_id', $item->id)
                        ->first();

                    if ($poLine) {
                        $poLine->increment('received_quantity', $acceptedQty);
                    }
                }
            }

            $receipt->update([
                'status' => GoodsReceiptStatus::Posted,
                'posted_by' => $actor->id,
                'posted_at' => now(),
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'goods_receipt.posted',
                'auditable_type' => GoodsReceipt::class,
                'auditable_id' => $receipt->id,
                'payload' => [
                    'receipt_number' => $receipt->receipt_number,
                    'warehouse_name' => $warehouse->name,
                ],
            ]);

            return $receipt->fresh(['lines.item', 'supplier', 'warehouse']);
        });
    }
}
