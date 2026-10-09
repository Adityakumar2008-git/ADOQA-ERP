<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\RecordOpeningStock;
use App\Enums\InventoryMode;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Item;
use App\Models\Role;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Warehouse;
use App\Services\Inventory\ReconciliationService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $business;

    protected User $owner;

    protected Warehouse $warehouse;

    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->business = Business::factory()->create();
        $this->owner = User::factory()->create(['status' => UserStatus::Active]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();
        UserRole::create([
            'user_id' => $this->owner->id,
            'role_id' => $ownerRole->id,
            'business_id' => $this->business->id,
        ]);

        $this->warehouse = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Central Depot',
            'code' => 'CENTRAL',
            'is_default' => true,
            'status' => 'active',
        ]);

        $unit = Unit::factory()->create(['business_id' => $this->business->id]);
        $this->item = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $unit->id,
            'inventory_mode' => InventoryMode::Normal,
        ]);
    }

    public function test_reconciliation_passes_on_consistent_stock_ledger(): void
    {
        $openingStockAction = app(RecordOpeningStock::class);
        $openingStockAction->execute($this->owner, $this->business, [
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 25,
            'unit_cost' => 50,
        ]);

        $service = app(ReconciliationService::class);
        $report = $service->reconcile($this->business);

        $this->assertTrue($report['reconciled']);
        $this->assertEmpty($report['stock_discrepancies']);
        $this->assertEmpty($report['unit_discrepancies']);
    }

    public function test_reconciliation_detects_deliberate_balance_discrepancy(): void
    {
        $openingStockAction = app(RecordOpeningStock::class);
        $openingStockAction->execute($this->owner, $this->business, [
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 25,
            'unit_cost' => 50,
        ]);

        // Deliberately corrupt balance record (simulate unauthorized mutation or data corruption)
        StockBalance::where('business_id', $this->business->id)
            ->where('item_id', $this->item->id)
            ->update(['quantity' => 99]);

        $service = app(ReconciliationService::class);
        $report = $service->reconcile($this->business);

        $this->assertFalse($report['reconciled']);
        $this->assertCount(1, $report['stock_discrepancies']);
        $this->assertEquals(99.0, (float) $report['stock_discrepancies'][0]['balance_quantity']);
        $this->assertEquals(25.0, (float) $report['stock_discrepancies'][0]['movement_quantity']);
    }

    public function test_reconciliation_artisan_command_detects_clean_and_corrupt_states(): void
    {
        $this->artisan('inventory:reconcile', ['business_id' => $this->business->id])
            ->assertExitCode(0);

        // Inject discrepancy
        StockBalance::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 15, // No corresponding movement
        ]);

        $this->artisan('inventory:reconcile', ['business_id' => $this->business->id])
            ->assertExitCode(1);
    }
}
