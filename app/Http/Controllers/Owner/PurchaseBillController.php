<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Procurement\CreatePurchaseBill;
use App\Actions\Procurement\PostPurchaseBill;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Item;
use App\Models\PurchaseBill;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseBillController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuthorizationService $authService,
        protected CreatePurchaseBill $createBill,
        protected PostPurchaseBill $postBillAction,
    ) {}

    public function index(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.bills.manage', $tenant);

        $query = PurchaseBill::where('business_id', $tenant->id)
            ->with(['supplier', 'creator', 'poster', 'purchaseOrder'])
            ->withCount('lines')
            ->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        $bills = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::where('business_id', $tenant->id)->get();

        return view('owner.purchasing.bills.index', [
            'tenant' => $tenant,
            'bills' => $bills,
            'suppliers' => $suppliers,
        ]);
    }

    public function create(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.bills.manage', $tenant);

        $suppliers = Supplier::where('business_id', $tenant->id)->where('status', 'active')->get();
        $items = Item::where('business_id', $tenant->id)->where('status', 'active')->with('unit')->get();
        $orders = PurchaseOrder::where('business_id', $tenant->id)->whereIn('status', ['approved', 'partially_received'])->get();

        return view('owner.purchasing.bills.create', [
            'tenant' => $tenant,
            'suppliers' => $suppliers,
            'items' => $items,
            'orders' => $orders,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.bills.manage', $tenant);

        $validated = $request->validate([
            'supplier_id' => ['required', 'string'],
            'supplier_invoice_number' => ['required', 'string', 'max:128'],
            'purchase_order_id' => ['nullable', 'string'],
            'bill_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'string'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.0001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $bill = $this->createBill->execute($request->user(), $tenant, $validated);

        return redirect()->route('owner.purchasing.bills.show', $bill)
            ->with('success', "Purchase bill {$bill->bill_number} created as draft.");
    }

    public function show(Request $request, PurchaseBill $bill): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.bills.manage', $tenant);

        if ($bill->business_id !== $tenant->id) {
            abort(404);
        }

        return view('owner.purchasing.bills.show', [
            'tenant' => $tenant,
            'bill' => $bill->load(['lines.item.unit', 'supplier', 'creator', 'poster', 'allocations.payment']),
        ]);
    }

    public function post(Request $request, PurchaseBill $bill): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.bills.post', $tenant);

        $this->postBillAction->execute($request->user(), $tenant, $bill);

        return redirect()->route('owner.purchasing.bills.show', $bill)
            ->with('success', "Purchase bill {$bill->bill_number} posted to supplier subledger.");
    }
}
