<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Inventory\CreateStockLocation;
use App\Actions\Inventory\CreateWarehouse;
use App\Actions\Inventory\UpdateWarehouse;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Warehouse;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuthorizationService $authService,
        protected CreateWarehouse $createWarehouse,
        protected UpdateWarehouse $updateWarehouse,
        protected CreateStockLocation $createLocation,
    ) {}

    public function index(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'warehouses.view', $tenant);

        $warehouses = Warehouse::where('business_id', $tenant->id)
            ->with(['locations'])
            ->withCount('balances')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('owner.inventory.warehouses.index', [
            'tenant' => $tenant,
            'warehouses' => $warehouses,
        ]);
    }

    public function create(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'warehouses.manage', $tenant);

        return view('owner.inventory.warehouses.create', [
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'warehouses.manage', $tenant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'code' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $this->createWarehouse->execute($request->user(), $tenant, $validated);

        return redirect()->route('owner.inventory.warehouses.index')
            ->with('success', 'Warehouse created successfully.');
    }

    public function edit(Request $request, Warehouse $warehouse): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'warehouses.manage', $tenant);

        if ($warehouse->business_id !== $tenant->id) {
            abort(404);
        }

        return view('owner.inventory.warehouses.edit', [
            'tenant' => $tenant,
            'warehouse' => $warehouse->load('locations'),
        ]);
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'warehouses.manage', $tenant);

        if ($warehouse->business_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'code' => ['required', 'string', 'max:64'],
            'address' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $this->updateWarehouse->execute($request->user(), $tenant, $warehouse, $validated);

        return redirect()->route('owner.inventory.warehouses.index')
            ->with('success', 'Warehouse updated successfully.');
    }

    public function storeLocation(Request $request, Warehouse $warehouse): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'warehouses.manage', $tenant);

        if ($warehouse->business_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'code' => ['nullable', 'string', 'max:64'],
        ]);

        $this->createLocation->execute($request->user(), $tenant, $warehouse, $validated);

        return redirect()->route('owner.inventory.warehouses.edit', $warehouse)
            ->with('success', 'Location created successfully.');
    }
}
