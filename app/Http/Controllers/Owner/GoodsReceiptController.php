<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Procurement\CreateGoodsReceipt;
use App\Actions\Procurement\PostGoodsReceipt;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\PurchaseBill;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoodsReceiptController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuthorizationService $authService,
        protected CreateGoodsReceipt $createReceipt,
        protected PostGoodsReceipt $postReceiptAction,
    ) {}

    public function index(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.receipts.prepare', $tenant);

        $query = GoodsReceipt::where('business_id', $tenant->id)
            ->with(['supplier', 'warehouse', 'preparer', 'poster'])
            ->withCount('lines')
            ->latest();

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $receipts = $query->paginate(20)->withQueryString();

        return view('owner.purchasing.receipts.index', [
            'tenant' => $tenant,
            'receipts' => $receipts,
        ]);
    }

    public function create(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.receipts.prepare', $tenant);

        $suppliers = Supplier::where('business_id', $tenant->id)->where('status', 'active')->get();
        $warehouses = Warehouse::where('business_id', $tenant->id)->where('status', 'active')->get();
        $items = Item::where('business_id', $tenant->id)->where('status', 'active')->with('unit')->get();
        $orders = PurchaseOrder::where('business_id', $tenant->id)->whereIn('status', ['approved', 'partially_received'])->get();
        $bills = PurchaseBill::where('business_id', $tenant->id)->where('status', 'posted')->get();

        return view('owner.purchasing.receipts.create', [
            'tenant' => $tenant,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'items' => $items,
            'orders' => $orders,
            'bills' => $bills,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.receipts.prepare', $tenant);

        $validated = $request->validate([
            'supplier_id' => ['required', 'string'],
            'warehouse_id' => ['required', 'string'],
            'purchase_order_id' => ['nullable', 'string'],
            'purchase_bill_id' => ['nullable', 'string'],
            'received_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'string'],
            'lines.*.accepted_quantity' => ['required', 'numeric', 'min:0'],
            'lines.*.rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.actual_unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.batch_number' => ['nullable', 'string'],
            'lines.*.manufacturing_date' => ['nullable', 'date'],
            'lines.*.expiry_date' => ['nullable', 'date'],
            'lines.*.serials_text' => ['nullable', 'string'],
            'lines.*.imeis_text' => ['nullable', 'string'],
        ]);

        foreach ($validated['lines'] as $index => &$line) {
            if (! empty($line['serials_text'])) {
                $line['serial_numbers'] = array_values(array_filter(array_map('trim', explode("\n", $line['serials_text']))));
            }
            if (! empty($line['imeis_text'])) {
                $imeiLines = array_filter(array_map('trim', explode("\n", $line['imeis_text'])));
                $imeis = [];
                foreach ($imeiLines as $iLine) {
                    $parts = array_map('trim', explode(',', $iLine));
                    $imeis[] = [
                        'imei1' => $parts[0] ?? '',
                        'imei2' => $parts[1] ?? null,
                    ];
                }
                $line['imeis'] = $imeis;
            }
        }

        $receipt = $this->createReceipt->execute($request->user(), $tenant, $validated);

        return redirect()->route('owner.purchasing.receipts.show', $receipt)
            ->with('success', "Goods receipt {$receipt->receipt_number} prepared as draft.");
    }

    public function show(Request $request, GoodsReceipt $receipt): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.receipts.prepare', $tenant);

        if ($receipt->business_id !== $tenant->id) {
            abort(404);
        }

        return view('owner.purchasing.receipts.show', [
            'tenant' => $tenant,
            'receipt' => $receipt->load(['lines.item.unit', 'lines.units.unit', 'supplier', 'warehouse', 'preparer', 'poster']),
        ]);
    }

    public function post(Request $request, GoodsReceipt $receipt): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'purchases.receipts.post', $tenant);

        $this->postReceiptAction->execute($request->user(), $tenant, $receipt);

        return redirect()->route('owner.purchasing.receipts.show', $receipt)
            ->with('success', "Goods receipt {$receipt->receipt_number} posted and stock updated.");
    }
}
