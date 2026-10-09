<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Services\Inventory\ReconciliationService;
use Illuminate\Console\Command;

class ReconcileInventoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:reconcile {business_id? : Optional specific business ULID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify and reconcile inventory movements against balances without rewriting history.';

    /**
     * Execute the console command.
     */
    public function handle(ReconciliationService $service): int
    {
        $businessId = $this->argument('business_id');

        $query = Business::query();
        if ($businessId) {
            $query->where('id', $businessId);
        }

        $businesses = $query->get();
        if ($businesses->isEmpty()) {
            $this->warn('No businesses found to reconcile.');

            return self::SUCCESS;
        }

        $hasAnyDiscrepancies = false;

        foreach ($businesses as $business) {
            $this->info("Reconciling business: {$business->name} ({$business->id})");
            $report = $service->reconcile($business);

            if ($report['status'] === 'clean') {
                $this->line("  [OK] Clean: All {$report['summary']['total_balances_checked']} balances match movement ledger.");
            } else {
                $hasAnyDiscrepancies = true;
                $this->error('  [DISCREPANCIES DETECTED]:');
                foreach ($report['stock_balance_discrepancies'] as $disc) {
                    $this->error("    Balance discrepancy: Item {$disc['item_id']}, Warehouse {$disc['warehouse_id']}, Stored: {$disc['stored_quantity']}, Movement Sum: {$disc['movement_sum']}");
                }
                foreach ($report['batch_discrepancies'] as $disc) {
                    $this->error("    Batch discrepancy: Batch {$disc['batch_number']}, Balance: {$disc['balance_sum']}, Movement Sum: {$disc['movement_sum']}");
                }
                foreach ($report['serialized_discrepancies'] as $disc) {
                    $this->error("    Serialized discrepancy: Item {$disc['item_name']}, Units: {$disc['available_units_count']}, Balance: {$disc['balance_quantity']}");
                }
            }
        }

        return $hasAnyDiscrepancies ? self::FAILURE : self::SUCCESS;
    }
}
