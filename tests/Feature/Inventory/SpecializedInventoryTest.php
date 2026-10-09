<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\MutateStock;
use App\Actions\Inventory\RecordOpeningStock;
use App\Enums\InventoryMode;
use App\Enums\InventoryUnitStatus;
use App\Enums\MembershipStatus;
use App\Enums\StockMovementType;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\InventoryBatch;
use App\Models\InventoryUnit;
use App\Models\Item;
use App\Models\Role;
use App\Models\StockBalance;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserRole;
use App\Models\Warehouse;
use Database\Seeders\RbacSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SpecializedInventoryTest extends TestCase
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

        $this->business = Business::factory()->create(['allow_negative_stock' => true]);
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
            'name' => 'Main Warehouse',
            'code' => 'MAIN-01',
            'is_default' => true,
            'status' => 'active',
        ]);

        $this->unit = Unit::factory()->create(['business_id' => $this->business->id]);
    }

    public function test_batch_inventory_requires_batch_identity_and_tracks_batch_balance(): void
    {
        $batchItem = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Batch,
        ]);

        $action = app(RecordOpeningStock::class);

        // Missing batch should be rejected
        try {
            $action->execute($this->owner, $this->business, [
                'item_id' => $batchItem->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 10,
            ]);
            $this->fail('Expected ValidationException when batch number is missing for BATCH item.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('batch_number', $e->errors());
        }

        // Valid batch creation
        $action->execute($this->owner, $this->business, [
            'item_id' => $batchItem->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 20,
            'batch_number' => 'LOT-2026-X',
            'manufacturing_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
        ]);

        $batch = InventoryBatch::where('business_id', $this->business->id)
            ->where('item_id', $batchItem->id)
            ->where('batch_number', 'LOT-2026-X')
            ->first();

        $this->assertNotNull($batch);

        // Balance row must have batch_id populated
        $balance = StockBalance::where('business_id', $this->business->id)
            ->where('item_id', $batchItem->id)
            ->where('batch_id', $batch->id)
            ->first();

        $this->assertNotNull($balance);
        $this->assertEquals(20.0, (float) $balance->quantity);
    }

    public function test_batch_inventory_cannot_become_negative_even_if_business_permits_negative_stock(): void
    {
        $this->business->update(['allow_negative_stock' => true]);

        $batchItem = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Batch,
        ]);

        $batch = InventoryBatch::create([
            'business_id' => $this->business->id,
            'item_id' => $batchItem->id,
            'batch_number' => 'MED-100',
        ]);

        StockBalance::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $batchItem->id,
            'batch_id' => $batch->id,
            'quantity' => 5,
        ]);

        $mutateAction = app(MutateStock::class);

        $this->expectException(ValidationException::class);

        // Attempting to deduct 10 from batch of 5 must fail
        $mutateAction->execute(
            actor: $this->owner,
            business: $this->business,
            item: $batchItem,
            warehouse: $this->warehouse,
            movementType: StockMovementType::StockAdjustment,
            quantity: -10,
            batch: $batch,
            sourceType: 'Test',
            sourceId: 'TEST-01'
        );
    }

    public function test_serial_inventory_registers_discrete_physical_units(): void
    {
        $serialItem = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Serial,
        ]);

        $action = app(RecordOpeningStock::class);
        $action->execute($this->owner, $this->business, [
            'item_id' => $serialItem->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 3,
            'serial_numbers' => ['SN-AAA-01', 'SN-AAA-02', 'SN-AAA-03'],
        ]);

        $this->assertCount(3, InventoryUnit::where('item_id', $serialItem->id)->get());

        $unit = InventoryUnit::where('serial_number', 'SN-AAA-01')->first();
        $this->assertNotNull($unit);
        $this->assertEquals(InventoryUnitStatus::Available, $unit->status);
        $this->assertEquals($this->warehouse->id, $unit->warehouse_id);

        $balance = StockBalance::where('item_id', $serialItem->id)->where('warehouse_id', $this->warehouse->id)->first();
        $this->assertEquals(3.0, (float) $balance->quantity);
    }

    public function test_imei_dual_sim_device_counts_as_one_unit_and_enforces_global_uniqueness(): void
    {
        $phoneItem = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Imei,
        ]);

        $action = app(RecordOpeningStock::class);
        $action->execute($this->owner, $this->business, [
            'item_id' => $phoneItem->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 1,
            'imeis' => [
                [
                    'imei1' => '860123456789012',
                    'imei2' => '860123456789013', // Dual-SIM phone
                ],
            ],
        ]);

        // Dual-SIM phone must count as ONE physical unit
        $units = InventoryUnit::where('item_id', $phoneItem->id)->get();
        $this->assertCount(1, $units);
        $this->assertEquals('860123456789012', $units->first()->imei1);
        $this->assertEquals('860123456789013', $units->first()->imei2);

        $balance = StockBalance::where('item_id', $phoneItem->id)->first();
        $this->assertEquals(1.0, (float) $balance->quantity);

        // Global uniqueness check: another business cannot register the same IMEI
        $otherBusiness = Business::factory()->create();
        $otherUnitItem = Item::factory()->create([
            'business_id' => $otherBusiness->id,
            'unit_id' => Unit::factory()->create(['business_id' => $otherBusiness->id])->id,
            'inventory_mode' => InventoryMode::Imei,
        ]);

        $this->expectException(QueryException::class);

        InventoryUnit::create([
            'business_id' => $otherBusiness->id,
            'item_id' => $otherUnitItem->id,
            'warehouse_id' => $this->warehouse->id,
            'imei1' => '860123456789012', // Duplicate across platform
            'status' => InventoryUnitStatus::Available,
        ]);
    }

    public function test_physical_unit_cannot_be_consumed_twice(): void
    {
        $serialItem = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $this->unit->id,
            'inventory_mode' => InventoryMode::Serial,
        ]);

        $unit = InventoryUnit::create([
            'business_id' => $this->business->id,
            'item_id' => $serialItem->id,
            'warehouse_id' => $this->warehouse->id,
            'serial_number' => 'SERIAL-TEST-99',
            'status' => InventoryUnitStatus::Damaged, // Not Available!
        ]);

        StockBalance::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $serialItem->id,
            'quantity' => 1,
        ]);

        $mutateAction = app(MutateStock::class);

        $this->expectException(ValidationException::class);

        // Attempting to deduct already damaged unit must be rejected
        $mutateAction->execute(
            actor: $this->owner,
            business: $this->business,
            item: $serialItem,
            warehouse: $this->warehouse,
            movementType: StockMovementType::Damage,
            quantity: -1,
            unit: $unit,
            sourceType: 'Test',
            sourceId: 'TEST-01'
        );
    }
}
