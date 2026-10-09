<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Procurement\CreateSupplier;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Supplier;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuthorizationService $authService,
        protected CreateSupplier $createSupplier,
    ) {}

    public function index(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'suppliers.view', $tenant);

        $query = Supplier::where('business_id', $tenant->id)
            ->withCount(['purchaseOrders', 'purchaseBills']);

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        $suppliers = $query->paginate(20)->withQueryString();

        return view('owner.purchasing.suppliers.index', [
            'tenant' => $tenant,
            'suppliers' => $suppliers,
        ]);
    }

    public function create(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'suppliers.manage', $tenant);

        return view('owner.purchasing.suppliers.create', [
            'tenant' => $tenant,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'suppliers.manage', $tenant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:64'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'gst_number' => ['nullable', 'string', 'max:64'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier = $this->createSupplier->execute($request->user(), $tenant, $validated);

        return redirect()->route('owner.purchasing.suppliers.show', $supplier)
            ->with('success', 'Supplier created successfully.');
    }

    public function show(Request $request, Supplier $supplier): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'suppliers.view', $tenant);

        if ($supplier->business_id !== $tenant->id) {
            abort(404);
        }

        $orders = $supplier->purchaseOrders()->latest()->take(10)->get();
        $bills = $supplier->purchaseBills()->latest()->take(10)->get();
        $payments = $supplier->payments()->latest()->take(10)->get();

        return view('owner.purchasing.suppliers.show', [
            'tenant' => $tenant,
            'supplier' => $supplier,
            'orders' => $orders,
            'bills' => $bills,
            'payments' => $payments,
        ]);
    }

    public function ledger(Request $request, Supplier $supplier): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'suppliers.ledger.view', $tenant);

        if ($supplier->business_id !== $tenant->id) {
            abort(404);
        }

        $entries = $supplier->ledgerEntries()
            ->with('actor')
            ->orderBy('posted_at')
            ->paginate(30);

        return view('owner.purchasing.suppliers.ledger', [
            'tenant' => $tenant,
            'supplier' => $supplier,
            'entries' => $entries,
        ]);
    }
}
