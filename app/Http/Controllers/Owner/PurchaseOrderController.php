<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Procurement\ApprovePurchaseOrder;
use App\Actions\Procurement\CreatePurchaseOrder;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuthorizationService $authService,
        protected CreatePurchaseOrder $createOrder,
        protected ApprovePurchaseOrder $approveOrder,
    ) {}

    public function index(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.manage', $tenant);

        $query = PurchaseOrder::where('business_id', $tenant->id)
            ->with(['supplier', 'creator', 'approver'])
            ->withCount('lines')
            ->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        $orders = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::where('business_id', $tenant->id)->get();

        return view('owner.purchasing.orders.index', [
            'tenant' => $tenant,
            'orders' => $orders,
            'suppliers' => $suppliers,
        ]);
    }

    public function create(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.create', $tenant);

        $suppliers = Supplier::where('business_id', $tenant->id)->where('status', 'active')->get();
        $items = Item::where('business_id', $tenant->id)->where('status', 'active')->with('unit')->get();

        return view('owner.purchasing.orders.create', [
            'tenant' => $tenant,
            'suppliers' => $suppliers,
            'items' => $items,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.create', $tenant);

        $validated = $request->validate([
            'supplier_id' => ['required', 'string'],
            'order_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'string'],
            'lines.*.ordered_quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = $this->createOrder->execute($request->user(), $tenant, $validated);

        return redirect()->route('owner.purchasing.orders.show', $order)
            ->with('success', "Purchase order {$order->order_number} created successfully.");
    }

    public function show(Request $request, PurchaseOrder $order): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.manage', $tenant);

        if ($order->business_id !== $tenant->id) {
            abort(404);
        }

        return view('owner.purchasing.orders.show', [
            'tenant' => $tenant,
            'order' => $order->load(['lines.item.unit', 'supplier', 'creator', 'approver', 'receipts', 'bills']),
        ]);
    }

    public function approve(Request $request, PurchaseOrder $order): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.approve', $tenant);

        $this->approveOrder->execute($request->user(), $tenant, $order);

        return redirect()->route('owner.purchasing.orders.show', $order)
            ->with('success', "Purchase order {$order->order_number} approved successfully.");
    }
}
