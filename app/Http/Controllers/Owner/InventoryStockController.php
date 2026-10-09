<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Inventory\AdjustStock;
use App\Actions\Inventory\CancelStockTransfer;
use App\Actions\Inventory\CreateStockTransfer;
use App\Actions\Inventory\DispatchStockTransfer;
use App\Actions\Inventory\ReceiveStockTransfer;
use App\Actions\Inventory\RecordOpeningStock;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Item;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Warehouse;
use App\Services\Auth\AuthorizationService;
use App\Services\Inventory\ReconciliationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryStockController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuthorizationService $authService,
        protected RecordOpeningStock $recordOpeningStock,
        protected AdjustStock $adjustStock,
        protected CreateStockTransfer $createTransfer,
        protected DispatchStockTransfer $dispatchTransferAction,
        protected ReceiveStockTransfer $receiveTransferAction,
        protected CancelStockTransfer $cancelTransferAction,
        protected ReconciliationService $reconciliationService,
    ) {}

    public function index(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.view', $tenant);

        $warehouses = Warehouse::where('business_id', $tenant->id)->where('status', 'active')->get();

        $query = StockBalance::where('business_id', $tenant->id)
            ->with(['item.unit', 'item.category', 'warehouse', 'batch', 'location']);

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($search = trim($request->input('search', ''))) {
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('sku', 'ilike', "%{$search}%")
                    ->orWhere('barcode', 'ilike', "%{$search}%");
            });
        }

        $balances = $query->paginate(25)->withQueryString();

        return view('owner.inventory.stock.index', [
            'tenant' => $tenant,
            'warehouses' => $warehouses,
            'balances' => $balances,
        ]);
    }

    public function ledger(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.view', $tenant);

        $warehouses = Warehouse::where('business_id', $tenant->id)->get();

        $query = StockMovement::where('business_id', $tenant->id)
            ->with(['item.unit', 'warehouse', 'batch', 'unit', 'actor'])
            ->latest('occurred_at');

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        if ($movementType = $request->input('movement_type')) {
            $query->where('movement_type', $movementType);
        }

        if ($search = trim($request->input('search', ''))) {
            $query->whereHas('item', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('sku', 'ilike', "%{$search}%");
            });
        }

        $movements = $query->paginate(30)->withQueryString();

        return view('owner.inventory.stock.ledger', [
            'tenant' => $tenant,
            'warehouses' => $warehouses,
            'movements' => $movements,
        ]);
    }

    public function openingStockForm(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.opening_stock', $tenant);

        $items = Item::where('business_id', $tenant->id)->where('status', 'active')->with('unit')->get();
        $warehouses = Warehouse::where('business_id', $tenant->id)->where('status', 'active')->with('locations')->get();

        return view('owner.inventory.stock.opening_stock', [
            'tenant' => $tenant,
            'items' => $items,
            'warehouses' => $warehouses,
        ]);
    }

    public function storeOpeningStock(Request $request): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.opening_stock', $tenant);

        $validated = $request->validate([
            'item_id' => ['required', 'string'],
            'warehouse_id' => ['required', 'string'],
            'location_id' => ['nullable', 'string'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'batch_number' => ['nullable', 'string', 'max:128'],
            'manufacturing_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'serial_numbers_text' => ['nullable', 'string'],
            'imeis_text' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! empty($validated['serial_numbers_text'])) {
            $lines = array_filter(array_map('trim', explode("\n", $validated['serial_numbers_text'])));
            $validated['serial_numbers'] = array_values($lines);
        }

        if (! empty($validated['imeis_text'])) {
            $lines = array_filter(array_map('trim', explode("\n", $validated['imeis_text'])));
            $imeis = [];
            foreach ($lines as $line) {
                $parts = array_map('trim', explode(',', $line));
                $imeis[] = [
                    'imei1' => $parts[0] ?? '',
                    'imei2' => $parts[1] ?? null,
                ];
            }
            $validated['imeis'] = $imeis;
        }

        $this->recordOpeningStock->execute($request->user(), $tenant, $validated);

        return redirect()->route('owner.inventory.stock.index')
            ->with('success', 'Opening stock recorded successfully.');
    }

    public function adjustmentsIndex(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.view', $tenant);

        $adjustments = StockAdjustment::where('business_id', $tenant->id)
            ->with(['warehouse', 'creator', 'poster'])
            ->withCount('lines')
            ->latest()
            ->paginate(20);

        return view('owner.inventory.adjustments.index', [
            'tenant' => $tenant,
            'adjustments' => $adjustments,
        ]);
    }

    public function adjustmentForm(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.adjust', $tenant);

        $warehouses = Warehouse::where('business_id', $tenant->id)->where('status', 'active')->get();
        $items = Item::where('business_id', $tenant->id)->where('status', 'active')->with(['unit', 'batches'])->get();

        return view('owner.inventory.adjustments.create', [
            'tenant' => $tenant,
            'warehouses' => $warehouses,
            'items' => $items,
        ]);
    }

    public function storeAdjustment(Request $request): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.adjust', $tenant);

        $validated = $request->validate([
            'warehouse_id' => ['required', 'string'],
            'adjustment_date' => ['nullable', 'date'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'string'],
            'lines.*.batch_id' => ['nullable', 'string'],
            'lines.*.counted_quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        $this->adjustStock->execute($request->user(), $tenant, $validated);

        return redirect()->route('owner.inventory.adjustments.index')
            ->with('success', 'Stock adjustment recorded and posted successfully.');
    }

    public function transfersIndex(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.view', $tenant);

        $transfers = StockTransfer::where('business_id', $tenant->id)
            ->with(['sourceWarehouse', 'destinationWarehouse', 'dispatcher', 'receiver'])
            ->withCount('lines')
            ->latest()
            ->paginate(20);

        return view('owner.inventory.transfers.index', [
            'tenant' => $tenant,
            'transfers' => $transfers,
        ]);
    }

    public function transferForm(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.transfer', $tenant);

        $warehouses = Warehouse::where('business_id', $tenant->id)->where('status', 'active')->get();
        $items = Item::where('business_id', $tenant->id)->where('status', 'active')->with(['unit', 'batches'])->get();

        return view('owner.inventory.transfers.create', [
            'tenant' => $tenant,
            'warehouses' => $warehouses,
            'items' => $items,
        ]);
    }

    public function storeTransfer(Request $request): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.transfer', $tenant);

        $validated = $request->validate([
            'source_warehouse_id' => ['required', 'string'],
            'destination_warehouse_id' => ['required', 'string', 'different:source_warehouse_id'],
            'transfer_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'string'],
            'lines.*.batch_id' => ['nullable', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
        ]);

        $transfer = $this->createTransfer->execute($request->user(), $tenant, $validated);

        return redirect()->route('owner.inventory.transfers.index')
            ->with('success', "Stock transfer {$transfer->transfer_number} created as draft.");
    }

    public function dispatchTransfer(Request $request, StockTransfer $transfer): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.transfer', $tenant);

        $this->dispatchTransferAction->execute($request->user(), $tenant, $transfer);

        return redirect()->route('owner.inventory.transfers.index')
            ->with('success', "Transfer {$transfer->transfer_number} dispatched successfully.");
    }

    public function receiveTransferForm(Request $request, StockTransfer $transfer): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.transfer', $tenant);

        if ($transfer->business_id !== $tenant->id) {
            abort(404);
        }

        return view('owner.inventory.transfers.receive', [
            'tenant' => $tenant,
            'transfer' => $transfer->load(['lines.item.unit', 'lines.batch', 'sourceWarehouse', 'destinationWarehouse']),
        ]);
    }

    public function storeReceiveTransfer(Request $request, StockTransfer $transfer): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.transfer', $tenant);

        $validated = $request->validate([
            'receipts' => ['required', 'array'],
            'receipts.*.received_quantity' => ['required', 'numeric', 'min:0'],
            'receipts.*.damaged_quantity' => ['nullable', 'numeric', 'min:0'],
            'receipts.*.lost_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->receiveTransferAction->execute($request->user(), $tenant, $transfer, $validated['receipts']);

        return redirect()->route('owner.inventory.transfers.index')
            ->with('success', "Transfer {$transfer->transfer_number} receipt processed.");
    }

    public function cancelTransfer(Request $request, StockTransfer $transfer): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.transfer', $tenant);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->cancelTransferAction->execute($request->user(), $tenant, $transfer, $validated['reason'] ?? 'Cancelled');

        return redirect()->route('owner.inventory.transfers.index')
            ->with('success', "Transfer {$transfer->transfer_number} cancelled.");
    }

    public function reconcile(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'inventory.reconcile', $tenant);

        $report = $this->reconciliationService->reconcile($tenant);

        return view('owner.inventory.stock.reconcile', [
            'tenant' => $tenant,
            'report' => $report,
        ]);
    }
}
