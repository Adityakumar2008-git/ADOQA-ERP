<?php

namespace App\Services\Inventory;

use App\Enums\InventoryUnitStatus;
use App\Models\Business;
use App\Models\InventoryBatch;
use App\Models\InventoryUnit;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;

class ReconciliationService
{
    /**
     * Run complete system reconciliation for a tenant and return detailed discrepancy report.
     * Never mutates or rewrites historical data.
     *
     * @return array{
     *     status: 'clean'|'discrepancies_found',
     *     stock_balance_discrepancies: array,
     *     batch_discrepancies: array,
     *     serialized_discrepancies: array,
     *     supplier_payable_discrepancies: array,
     *     summary: array
     * }
     */
    public function reconcile(Business $business): array
    {
        $stockDiscrepancies = $this->checkStockBalanceMovements($business);
        $batchDiscrepancies = $this->checkBatchMovements($business);
        $serialDiscrepancies = $this->checkSerializedUnitCounts($business);
        $supplierDiscrepancies = $this->checkSupplierPayableLedger($business);

        $hasDiscrepancies = ! empty($stockDiscrepancies)
            || ! empty($batchDiscrepancies)
            || ! empty($serialDiscrepancies)
            || ! empty($supplierDiscrepancies);

        return [
            'reconciled' => ! $hasDiscrepancies,
            'status' => $hasDiscrepancies ? 'discrepancies_found' : 'clean',
            'timestamp' => now()->format('Y-m-d H:i:s'),
            'stock_balance_discrepancies' => $stockDiscrepancies,
            'stock_discrepancies' => $stockDiscrepancies,
            'batch_discrepancies' => $batchDiscrepancies,
            'serialized_discrepancies' => $serialDiscrepancies,
            'unit_discrepancies' => $serialDiscrepancies,
            'supplier_payable_discrepancies' => $supplierDiscrepancies,
            'supplier_discrepancies' => $supplierDiscrepancies,
            'summary' => [
                'total_balances_checked' => StockBalance::where('business_id', $business->id)->count(),
                'total_movements_checked' => StockMovement::where('business_id', $business->id)->count(),
                'total_suppliers_checked' => Supplier::where('business_id', $business->id)->count(),
                'is_clean' => ! $hasDiscrepancies,
            ],
        ];
    }

    /**
     * Compare stored stock balances with sum of append-only movements.
     */
    protected function checkStockBalanceMovements(Business $business): array
    {
        $discrepancies = [];

        $balances = StockBalance::where('business_id', $business->id)->get();

        foreach ($balances as $balance) {
            $movementSum = (float) StockMovement::where('business_id', $business->id)
                ->where('item_id', $balance->item_id)
                ->where('warehouse_id', $balance->warehouse_id)
                ->where('location_id', $balance->location_id)
                ->where('batch_id', $balance->batch_id)
                ->sum('quantity');

            $storedQty = (float) $balance->quantity;

            if (abs($storedQty - $movementSum) > 0.0001) {
                $discrepancies[] = [
                    'balance_id' => $balance->id,
                    'item_id' => $balance->item_id,
                    'item_name' => $balance->item?->name ?? 'Unknown',
                    'warehouse_id' => $balance->warehouse_id,
                    'warehouse_name' => $balance->warehouse?->name ?? 'Unknown',
                    'stored_quantity' => $storedQty,
                    'balance_quantity' => $storedQty,
                    'movement_sum' => $movementSum,
                    'movement_quantity' => $movementSum,
                    'variance' => round($storedQty - $movementSum, 4),
                    'difference' => round($storedQty - $movementSum, 4),
                ];
            }
        }

        return $discrepancies;
    }

    /**
     * Compare batch balances with batch movements.
     */
    protected function checkBatchMovements(Business $business): array
    {
        $discrepancies = [];

        $batches = InventoryBatch::where('business_id', $business->id)->get();

        foreach ($batches as $batch) {
            $movementSum = (float) StockMovement::where('business_id', $business->id)
                ->where('batch_id', $batch->id)
                ->sum('quantity');

            $balanceSum = (float) StockBalance::where('business_id', $business->id)
                ->where('batch_id', $batch->id)
                ->sum('quantity');

            if (abs($balanceSum - $movementSum) > 0.0001) {
                $discrepancies[] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'balance_sum' => $balanceSum,
                    'movement_sum' => $movementSum,
                    'variance' => round($balanceSum - $movementSum, 4),
                ];
            }
        }

        return $discrepancies;
    }

    /**
     * Compare serialized available physical units with positive warehouse balances.
     */
    protected function checkSerializedUnitCounts(Business $business): array
    {
        $discrepancies = [];

        $serializedItems = Item::where('business_id', $business->id)
            ->whereIn('inventory_mode', ['serial', 'imei'])
            ->get();

        foreach ($serializedItems as $item) {
            $availableUnitsCount = InventoryUnit::where('business_id', $business->id)
                ->where('item_id', $item->id)
                ->where('status', InventoryUnitStatus::Available)
                ->count();

            $balanceQty = (float) StockBalance::where('business_id', $business->id)
                ->where('item_id', $item->id)
                ->sum('quantity');

            if ((int) $balanceQty !== $availableUnitsCount) {
                $discrepancies[] = [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'available_units_count' => $availableUnitsCount,
                    'balance_quantity' => (int) $balanceQty,
                    'variance' => (int) $balanceQty - $availableUnitsCount,
                ];
            }
        }

        return $discrepancies;
    }

    /**
     * Compare supplier outstanding balance with sum of supplier subledger entries.
     */
    protected function checkSupplierPayableLedger(Business $business): array
    {
        $discrepancies = [];

        $suppliers = Supplier::where('business_id', $business->id)->get();

        foreach ($suppliers as $supplier) {
            $ledgerSum = (float) $supplier->ledgerEntries()->sum('amount');
            $calculatedOutstanding = $supplier->currentOutstanding();

            if (abs($ledgerSum - $calculatedOutstanding) > 0.01) {
                $discrepancies[] = [
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'ledger_sum' => $ledgerSum,
                    'calculated_outstanding' => $calculatedOutstanding,
                ];
            }
        }

        return $discrepancies;
    }
}
