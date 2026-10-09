<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Procurement\AllocateSupplierAdvance;
use App\Actions\Procurement\RecordSupplierPayment;
use App\Actions\Procurement\ReverseSupplierPayment;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\PurchaseBill;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierPaymentController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuthorizationService $authService,
        protected RecordSupplierPayment $recordPayment,
        protected ReverseSupplierPayment $reversePayment,
        protected AllocateSupplierAdvance $allocateAdvance,
    ) {}

    public function index(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'suppliers.payments.create', $tenant);

        $query = SupplierPayment::where('business_id', $tenant->id)
            ->with(['supplier', 'poster'])
            ->latest();

        if ($supplierId = $request->input('supplier_id')) {
            $query->where('supplier_id', $supplierId);
        }

        $payments = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::where('business_id', $tenant->id)->get();

        return view('owner.purchasing.payments.index', [
            'tenant' => $tenant,
            'payments' => $payments,
            'suppliers' => $suppliers,
        ]);
    }

    public function create(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'suppliers.payments.create', $tenant);

        $suppliers = Supplier::where('business_id', $tenant->id)->where('status', 'active')->get();
        $selectedSupplier = null;
        $outstandingBills = collect();

        if ($supplierId = $request->input('supplier_id')) {
            $selectedSupplier = Supplier::where('id', $supplierId)->where('business_id', $tenant->id)->first();
            if ($selectedSupplier) {
                $outstandingBills = PurchaseBill::where('business_id', $tenant->id)
                    ->where('supplier_id', $selectedSupplier->id)
                    ->whereIn('status', ['posted', 'partially_paid'])
                    ->get();
            }
        }

        return view('owner.purchasing.payments.create', [
            'tenant' => $tenant,
            'suppliers' => $suppliers,
            'selectedSupplier' => $selectedSupplier,
            'outstandingBills' => $outstandingBills,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'suppliers.payments.post', $tenant);

        $validated = $request->validate([
            'supplier_id' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['nullable', 'date'],
            'payment_mode' => ['required', 'string', 'in:cash,bank_transfer,upi,card,cheque'],
            'reference_number' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string'],
            'allocations' => ['nullable', 'array'],
            'allocations.*.purchase_bill_id' => ['required', 'string'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $payment = $this->recordPayment->execute($request->user(), $tenant, $validated);

        return redirect()->route('owner.purchasing.payments.show', $payment)
            ->with('success', "Supplier payment {$payment->payment_number} posted successfully.");
    }

    public function show(Request $request, SupplierPayment $payment): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'suppliers.payments.create', $tenant);

        if ($payment->business_id !== $tenant->id) {
            abort(404);
        }

        $outstandingBills = PurchaseBill::where('business_id', $tenant->id)
            ->where('supplier_id', $payment->supplier_id)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->get();

        return view('owner.purchasing.payments.show', [
            'tenant' => $tenant,
            'payment' => $payment->load(['supplier', 'poster', 'reverser', 'allocations.bill']),
            'outstandingBills' => $outstandingBills,
        ]);
    }

    public function reverse(Request $request, SupplierPayment $payment): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'suppliers.payments.post', $tenant);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->reversePayment->execute($request->user(), $tenant, $payment, $validated['reason'] ?? 'Payment reversed');

        return redirect()->route('owner.purchasing.payments.show', $payment)
            ->with('success', "Payment {$payment->payment_number} reversed successfully.");
    }

    public function allocateAdvance(Request $request, SupplierPayment $payment): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'suppliers.payments.post', $tenant);

        $validated = $request->validate([
            'purchase_bill_id' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $bill = PurchaseBill::where('id', $validated['purchase_bill_id'])->where('business_id', $tenant->id)->firstOrFail();

        $this->allocateAdvance->execute($request->user(), $tenant, $payment, $bill, (float) $validated['amount']);

        return redirect()->route('owner.purchasing.payments.show', $payment)
            ->with('success', 'Advance allocated to bill successfully.');
    }
}
