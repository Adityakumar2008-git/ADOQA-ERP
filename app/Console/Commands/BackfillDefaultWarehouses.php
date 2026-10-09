<?php

namespace App\Console\Commands;

use App\Actions\Inventory\InitializeDefaultWarehouse;
use App\Models\Business;
use Illuminate\Console\Command;

class BackfillDefaultWarehouses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:backfill-default-warehouses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Idempotently ensure all existing businesses have a default warehouse and location.';

    /**
     * Execute the console command.
     */
    public function handle(InitializeDefaultWarehouse $action): int
    {
        $businesses = Business::all();
        $count = 0;

        foreach ($businesses as $business) {
            $warehouse = $action->execute($business);
            if ($warehouse->wasRecentlyCreated) {
                $count++;
                $this->info("Provisioned default warehouse for business: {$business->name} ({$business->id})");
            }
        }

        $this->info("Completed default warehouse backfill. Newly provisioned: {$count}");

        return self::SUCCESS;
    }
}
