<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\AdjustStock;
use App\Actions\Inventory\MutateStock;
use App\Actions\Inventory\RecordOpeningStock;
use App\Enums\InventoryMode;
use App\Enums\MembershipStatus;
use App\Enums\StockMovementType;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Item;
use App\Models\Role;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Warehouse;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockLedgerAndBalancesTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $business;

    protected User $owner;

    protected Warehouse $warehouse;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->business = Business::factory()->create(['allow_negative_stock' => false]);
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
            'name' => 'Main Depot',
            'code' => 'MAIN-01',
            'is_default' => true,
            'status' => 'active',
        ]);

        $this->unit = Unit::factory()->create(['business_id' => $this->business->id]);
    }

    public function test_opening_stock_creates_movement_and_updates_balance_atomically(): void
    {
        $item = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Normal,
        ]);

        $action = app(RecordOpeningStock::class);
        $action->execute($this->owner, $this->business, [
            'item_id' => $item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
            'unit_cost' => 120.50,
            'notes' => 'Initial stock take',
        ]);

        // Verify balance projection
        $balance = StockBalance::where('business_id', $this->business->id)
            ->where('item_id', $item->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertNotNull($balance);
        $this->assertEquals(50.0, (float) $balance->quantity);

        // Verify ledger movement
        $movement = StockMovement::where('business_id', $this->business->id)
            ->where('item_id', $item->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->where('movement_type', StockMovementType::OpeningBalance)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(50.0, (float) $movement->quantity);
        $this->assertEquals(120.50, (float) $movement->unit_cost);
        $this->assertEquals($this->owner->id, $movement->actor_id);
    }

    public function test_stock_adjustment_creates_accurate_delta_movement(): void
    {
        $item = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Normal,
        ]);

        // Establish initial 100 qty
        StockBalance::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'quantity' => 100,
        ]);

        $adjustAction = app(AdjustStock::class);
        $adjustment = $adjustAction->execute($this->owner, $this->business, [
            'warehouse_id' => $this->warehouse->id,
            'reason' => 'Damaged inventory count',
            'lines' => [
                [
                    'item_id' => $item->id,
                    'counted_quantity' => 93, // Variance is -7
                    'notes' => '7 boxes water damaged',
                ],
            ],
        ]);

        $this->assertEquals('posted', $adjustment->status->value);

        // Balance updated to 93
        $balance = StockBalance::where('business_id', $this->business->id)
            ->where('item_id', $item->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();

        $this->assertEquals(93.0, (float) $balance->quantity);

        // Ledger movement recorded signed -7
        $movement = StockMovement::where('business_id', $this->business->id)
            ->where('item_id', $item->id)
            ->where('movement_type', StockMovementType::StockAdjustment)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(-7.0, (float) $movement->quantity);
    }

    public function test_negative_stock_rejected_when_disabled(): void
    {
        $this->business->update(['allow_negative_stock' => false]);

        $item = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Normal,
        ]);

        StockBalance::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'quantity' => 10,
        ]);

        $mutateAction = app(MutateStock::class);

        $this->expectException(ValidationException::class);

        // Attempt to deduct 15 units when only 10 exist
        $mutateAction->execute(
            actor: $this->owner,
            business: $this->business,
            item: $item,
            warehouse: $this->warehouse,
            movementType: StockMovementType::StockAdjustment,
            quantity: -15,
            sourceType: 'Test',
            sourceId: 'TEST-01'
        );
    }

    public function test_negative_stock_permitted_when_explicitly_enabled_for_normal_item(): void
    {
        $this->business->update(['allow_negative_stock' => true]);

        $item = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Normal,
        ]);

        StockBalance::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $item->id,
            'quantity' => 10,
        ]);

        $mutateAction = app(MutateStock::class);

        $result = $mutateAction->execute(
            actor: $this->owner,
            business: $this->business,
            item: $item,
            warehouse: $this->warehouse,
            movementType: StockMovementType::StockAdjustment,
            quantity: -15,
            sourceType: 'Test',
            sourceId: 'TEST-01'
        );

        $this->assertEquals(-5.0, (float) $result['balance']->quantity);
        $this->assertEquals(-15.0, (float) $result['movement']->quantity);
    }
}
