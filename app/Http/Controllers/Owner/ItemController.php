<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Catalogue\ActivateItem;
use App\Actions\Catalogue\CreateItem;
use App\Actions\Catalogue\DeactivateItem;
use App\Actions\Catalogue\DeleteItem;
use App\Actions\Catalogue\UpdateItem;
use App\Enums\CatalogStatus;
use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreItemRequest;
use App\Http\Requests\Owner\UpdateItemRequest;
use App\Models\Brand;
use App\Models\Business;
use App\Models\Category;
use App\Models\Item;
use App\Models\Unit;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuthorizationService $authService,
    ) {}

    /**
     * Display a listing of catalogue items for the active tenant.
     */
    public function index(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        $query = Item::query()
            ->where('business_id', $tenant->id)
            ->with(['category', 'brand', 'unit']);

        // Search by name, SKU, or barcode
        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('sku', 'ilike', "%{$search}%")
                    ->orWhere('barcode', 'ilike', "%{$search}%");
            });
        }

        // Filters
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId = $request->input('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($mode = $request->input('inventory_mode')) {
            $query->where('inventory_mode', $mode);
        }

        $items = $query->latest('created_at')->paginate(15)->withQueryString();

        $categories = Category::where('business_id', $tenant->id)->orderBy('name')->get();
        $brands = Brand::where('business_id', $tenant->id)->orderBy('name')->get();

        return view('owner.catalogue.items.index', compact('items', 'categories', 'brands', 'tenant'));
    }

    /**
     * Show the form for creating a new item.
     */
    public function create(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        $categories = Category::where('business_id', $tenant->id)
            ->where('status', CatalogStatus::Active)
            ->orderBy('name')
            ->get();

        $brands = Brand::where('business_id', $tenant->id)
            ->where('status', CatalogStatus::Active)
            ->orderBy('name')
            ->get();

        $units = Unit::where('business_id', $tenant->id)
            ->where('status', CatalogStatus::Active)
            ->orderBy('name')
            ->get();

        return view('owner.catalogue.items.create', compact('categories', 'brands', 'units', 'tenant'));
    }

    /**
     * Store a newly created item in the catalogue.
     */
    public function store(StoreItemRequest $request, CreateItem $action): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        $item = $action->execute(
            actor: $request->user(),
            business: $tenant,
            data: $request->validated()
        );

        return redirect()->route('owner.catalogue.items.show', $item)
            ->with('success', "Item '{$item->name}' created successfully in catalogue.");
    }

    /**
     * Display the specified catalogue item.
     */
    public function show(Request $request, Item $item): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($item->business_id !== $tenant->id) {
            abort(404);
        }

        $item->load(['category', 'brand', 'unit']);

        return view('owner.catalogue.items.show', compact('item', 'tenant'));
    }

    /**
     * Show the form for editing the specified item.
     */
    public function edit(Request $request, Item $item): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($item->business_id !== $tenant->id) {
            abort(404);
        }

        $categories = Category::where('business_id', $tenant->id)
            ->where(function ($q) use ($item) {
                $q->where('status', CatalogStatus::Active)
                    ->orWhere('id', $item->category_id);
            })
            ->orderBy('name')
            ->get();

        $brands = Brand::where('business_id', $tenant->id)
            ->where(function ($q) use ($item) {
                $q->where('status', CatalogStatus::Active)
                    ->orWhere('id', $item->brand_id);
            })
            ->orderBy('name')
            ->get();

        $units = Unit::where('business_id', $tenant->id)
            ->where(function ($q) use ($item) {
                $q->where('status', CatalogStatus::Active)
                    ->orWhere('id', $item->unit_id);
            })
            ->orderBy('name')
            ->get();

        return view('owner.catalogue.items.edit', compact('item', 'categories', 'brands', 'units', 'tenant'));
    }

    /**
     * Update the specified catalogue item.
     */
    public function update(UpdateItemRequest $request, Item $item, UpdateItem $action): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($item->business_id !== $tenant->id) {
            abort(404);
        }

        $action->execute(
            actor: $request->user(),
            business: $tenant,
            item: $item,
            data: $request->validated()
        );

        return redirect()->route('owner.catalogue.items.show', $item)
            ->with('success', "Item '{$item->name}' updated successfully.");
    }

    /**
     * Toggle the active status of an item.
     */
    public function toggleStatus(Request $request, Item $item, ActivateItem $activate, DeactivateItem $deactivate): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($item->business_id !== $tenant->id) {
            abort(404);
        }

        if ($item->status === ItemStatus::Active) {
            $deactivate->execute($request->user(), $tenant, $item);
            $msg = "Item '{$item->name}' deactivated.";
        } else {
            $activate->execute($request->user(), $tenant, $item);
            $msg = "Item '{$item->name}' activated.";
        }

        return back()->with('success', $msg);
    }

    /**
     * Delete an item if not referenced.
     */
    public function destroy(Request $request, Item $item, DeleteItem $action): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($item->business_id !== $tenant->id) {
            abort(404);
        }

        $name = $item->name;
        $action->execute($request->user(), $tenant, $item);

        return redirect()->route('owner.catalogue.items.index')
            ->with('success', "Item '{$name}' deleted from catalogue.");
    }
}
