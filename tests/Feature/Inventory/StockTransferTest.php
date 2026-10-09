<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\CancelStockTransfer;
use App\Actions\Inventory\CreateStockTransfer;
use App\Actions\Inventory\DispatchStockTransfer;
use App\Actions\Inventory\ReceiveStockTransfer;
use App\Enums\InventoryMode;
use App\Enums\MembershipStatus;
use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
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

class StockTransferTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $business;

    protected User $owner;

    protected Warehouse $sourceWh;

    protected Warehouse $destWh;

    protected Item $item;

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

        $this->sourceWh = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Main Warehouse',
            'code' => 'MAIN-01',
            'is_default' => true,
            'status' => 'active',
        ]);

        $this->destWh = Warehouse::create([
            'business_id' => $this->business->id,
            'name' => 'Retail Outlet Depot',
            'code' => 'RETAIL-01',
            'is_default' => false,
            'status' => 'active',
        ]);

        $unit = Unit::factory()->create(['business_id' => $this->business->id]);
        $this->item = Item::factory()->create([
            'business_id' => $this->business->id,
            'unit_id' => $unit->id,
            'inventory_mode' => InventoryMode::Normal,
        ]);
    }

    public function test_complete_stock_transfer_lifecycle(): void
    {
        // 1. Initial stock in source warehouse: 100 units
        StockBalance::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->sourceWh->id,
            'item_id' => $this->item->id,
            'quantity' => 100,
        ]);

        $createAction = app(CreateStockTransfer::class);
        $transfer = $createAction->execute($this->owner, $this->business, [
            'source_warehouse_id' => $this->sourceWh->id,
            'destination_warehouse_id' => $this->destWh->id,
            'lines' => [
                [
                    'item_id' => $this->item->id,
                    'quantity' => 30,
                ],
            ],
            'notes' => 'Transfer 30 units to store',
        ]);

        $this->assertEquals(StockTransferStatus::Draft, $transfer->status);

        // 2. Dispatch
        $dispatchAction = app(DispatchStockTransfer::class);
        $transfer = $dispatchAction->execute($this->owner, $this->business, $transfer);

        $this->assertEquals(StockTransferStatus::Dispatched, $transfer->status);

        // Source balance should now be 70
        $sourceBalance = StockBalance::where('warehouse_id', $this->sourceWh->id)->where('item_id', $this->item->id)->first();
        $this->assertEquals(70.0, (float) $sourceBalance->quantity);

        // Outbound movement recorded
        $outMovement = StockMovement::where('warehouse_id', $this->sourceWh->id)
            ->where('movement_type', StockMovementType::TransferOut)
            ->where('source_id', $transfer->id)
            ->first();
        $this->assertNotNull($outMovement);
        $this->assertEquals(-30.0, (float) $outMovement->quantity);

        // 3. Receive at destination depot
        $receiveAction = app(ReceiveStockTransfer::class);
        $transferLine = $transfer->lines->first();

        $transfer = $receiveAction->execute($this->owner, $this->business, $transfer, [
            $transferLine->id => [
                'received_quantity' => 30,
                'damaged_quantity' => 0,
                'lost_quantity' => 0,
            ],
        ]);

        $this->assertEquals(StockTransferStatus::Received, $transfer->status);

        // Destination balance should now be 30
        $destBalance = StockBalance::where('warehouse_id', $this->destWh->id)->where('item_id', $this->item->id)->first();
        $this->assertNotNull($destBalance);
        $this->assertEquals(30.0, (float) $destBalance->quantity);

        // Inbound movement recorded
        $inMovement = StockMovement::where('warehouse_id', $this->destWh->id)
            ->where('movement_type', StockMovementType::TransferIn)
            ->where('source_id', $transfer->id)
            ->first();
        $this->assertNotNull($inMovement);
        $this->assertEquals(30.0, (float) $inMovement->quantity);
    }

    public function test_transfer_with_identical_source_and_destination_is_rejected(): void
    {
        $createAction = app(CreateStockTransfer::class);

        $this->expectException(ValidationException::class);

        $createAction->execute($this->owner, $this->business, [
            'source_warehouse_id' => $this->sourceWh->id,
            'destination_warehouse_id' => $this->sourceWh->id, // Identical!
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 10],
            ],
        ]);
    }

    public function test_cannot_dispatch_transfer_when_source_stock_insufficient(): void
    {
        // Source only has 5 units
        StockBalance::create([
            'business_id' => $this->business->id,
            'warehouse_id' => $this->sourceWh->id,
            'item_id' => $this->item->id,
            'quantity' => 5,
        ]);

        $createAction = app(CreateStockTransfer::class);
        $transfer = $createAction->execute($this->owner, $this->business, [
            'source_warehouse_id' => $this->sourceWh->id,
            'destination_warehouse_id' => $this->destWh->id,
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 20], // Requests 20
            ],
        ]);

        $dispatchAction = app(DispatchStockTransfer::class);

        $this->expectException(ValidationException::class);
        $dispatchAction->execute($this->owner, $this->business, $transfer);
    }

    public function test_draft_transfer_can_be_cancelled(): void
    {
        $createAction = app(CreateStockTransfer::class);
        $transfer = $createAction->execute($this->owner, $this->business, [
            'source_warehouse_id' => $this->sourceWh->id,
            'destination_warehouse_id' => $this->destWh->id,
            'lines' => [
                ['item_id' => $this->item->id, 'quantity' => 5],
            ],
        ]);

        $cancelAction = app(CancelStockTransfer::class);
        $transfer = $cancelAction->execute($this->owner, $this->business, $transfer, 'Transfer cancelled by planner');

        $this->assertEquals(StockTransferStatus::Cancelled, $transfer->status);
    }
}
