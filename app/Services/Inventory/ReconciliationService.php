<?php

namespace App\Services\Inventory;

use App\Enums\InventoryUnitStatus;
use App\Enums\PurchaseBillStatus;
use App\Enums\SupplierPaymentStatus;
use App\Models\Business;
use App\Models\InventoryBatch;
use App\Models\InventoryUnit;
use App\Models\Item;
use App\Models\PurchaseBill;
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
     * Compare raw supplier subledger entries against authoritative source documents and open item states.
     *
     * Accounting Sign Convention & Domain Model:
     * - Subledger (SupplierLedgerEntry) representation:
     *   - Payable liability increases are recorded with POSITIVE amounts (+):
     *     - BillPosted (+bill total_amount)
     *     - PaymentReversed (+payment amount restored upon cheque bounce or cancellation)
     *     - Adjustment (+opening balance configured upon supplier onboarding)
     *   - Payable settlements (cash outflow to supplier) are recorded with NEGATIVE amounts (-):
     *     - PaymentPosted (-payment amount, whether allocated immediately or held as advance)
     *   - Tracking entries have ZERO financial delta (0.00):
     *     - AdvanceAllocated (0.00, because the cash outflow already reduced the payable at payment creation;
     *       allocating an advance to an existing bill shifts liability from unapplied advance to bill settlement)
     *
     * Authoritative Independent Expected Balance Derivation:
     * 1. Document-Level Expected Balance:
     *    Expected Balance = (Supplier Opening Balance)
     *                     + (Sum of total_amount for all Posted, PartiallyPaid, and Paid Purchase Bills)
     *                     - (Sum of amount for all active Posted Supplier Payments [excluding Reversed])
     *
     * 2. Open Items & Advances Independent Derivation:
     *    Open Items Balance = (Supplier Opening Balance)
     *                       + (Sum of unpaid bill balances: total_amount - paid_amount)
     *                       - (Sum of active unapplied advance balances: advance_amount)
     *
     * In an accounting-sound ledger, both derivations are mathematically equal to each other
     * and equal to the append-only sum of SupplierLedgerEntry records.
     */
    protected function checkSupplierPayableLedger(Business $business): array
    {
        $discrepancies = [];

        $suppliers = Supplier::where('business_id', $business->id)->get();

        foreach ($suppliers as $supplier) {
            $openingBalance = round((float) ($supplier->opening_balance ?? 0.00), 2);

            // Authoritative posted bills total
            $postedBillsTotal = round((float) $supplier->purchaseBills()
                ->whereIn('status', [
                    PurchaseBillStatus::Posted->value,
                    PurchaseBillStatus::PartiallyPaid->value,
                    PurchaseBillStatus::Paid->value,
                ])
                ->sum('total_amount'), 2);

            // Authoritative active posted payments total (excluding reversed)
            $activePaymentsTotal = round((float) $supplier->payments()
                ->where('status', SupplierPaymentStatus::Posted->value)
                ->sum('amount'), 2);

            // 1. Authoritative expected balance derived from posted source documents
            $expectedDocumentBalance = round($openingBalance + $postedBillsTotal - $activePaymentsTotal, 2);

            // 2. Authoritative expected balance derived from open unpaid bill items and unapplied advances
            $unpaidBillsTotal = round((float) $supplier->purchaseBills()
                ->whereIn('status', [
                    PurchaseBillStatus::Posted->value,
                    PurchaseBillStatus::PartiallyPaid->value,
                    PurchaseBillStatus::Paid->value,
                ])
                ->get()
                ->sum(fn (PurchaseBill $bill) => (float) $bill->outstandingAmount()), 2);

            $unappliedAdvancesTotal = round((float) $supplier->payments()
                ->where('status', SupplierPaymentStatus::Posted->value)
                ->sum('advance_amount'), 2);

            $expectedOpenItemsBalance = round($openingBalance + $unpaidBillsTotal - $unappliedAdvancesTotal, 2);

            // 3. Raw append-only subledger sum
            $ledgerSum = round((float) $supplier->ledgerEntries()->sum('amount'), 2);

            $hasLedgerMismatch = abs($ledgerSum - $expectedDocumentBalance) > 0.005;
            $hasAllocationMismatch = abs($expectedDocumentBalance - $expectedOpenItemsBalance) > 0.005;

            if ($hasLedgerMismatch || $hasAllocationMismatch) {
                $difference = round($ledgerSum - $expectedDocumentBalance, 2);
                $discrepancies[] = [
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'ledger_balance' => $ledgerSum,
                    'ledger_sum' => $ledgerSum,
                    'expected_balance' => $expectedDocumentBalance,
                    'document_balance' => $expectedDocumentBalance,
                    'calculated_outstanding' => $expectedDocumentBalance,
                    'open_items_balance' => $expectedOpenItemsBalance,
                    'difference' => $difference,
                    'variance' => $difference,
                    'opening_balance' => $openingBalance,
                    'posted_bills_total' => $postedBillsTotal,
                    'active_payments_total' => $activePaymentsTotal,
                    'unpaid_bills_total' => $unpaidBillsTotal,
                    'unapplied_advances_total' => $unappliedAdvancesTotal,
                    'ledger_mismatch' => $hasLedgerMismatch,
                    'allocation_mismatch' => $hasAllocationMismatch,
                ];
            }
        }

        return $discrepancies;
    }
}
