<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Catalogue\ActivateBrand;
use App\Actions\Catalogue\CreateBrand;
use App\Actions\Catalogue\DeactivateBrand;
use App\Actions\Catalogue\DeleteBrand;
use App\Actions\Catalogue\UpdateBrand;
use App\Enums\CatalogStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\BrandRequest;
use App\Models\Brand;
use App\Models\Business;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function __construct(
        protected TenantContext $tenantContext,
        protected AuthorizationService $authService,
    ) {}

    public function index(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        $query = Brand::query()
            ->where('business_id', $tenant->id)
            ->withCount('items');

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $brands = $query->latest('created_at')->paginate(15)->withQueryString();

        return view('owner.catalogue.brands.index', compact('brands', 'tenant'));
    }

    public function create(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        return view('owner.catalogue.brands.create', compact('tenant'));
    }

    public function store(BrandRequest $request, CreateBrand $action): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        $brand = $action->execute(
            actor: $request->user(),
            business: $tenant,
            data: $request->validated()
        );

        return redirect()->route('owner.catalogue.brands.index')
            ->with('success', "Brand '{$brand->name}' created successfully.");
    }

    public function edit(Request $request, Brand $brand): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($brand->business_id !== $tenant->id) {
            abort(404);
        }

        return view('owner.catalogue.brands.edit', compact('brand', 'tenant'));
    }

    public function update(BrandRequest $request, Brand $brand, UpdateBrand $action): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($brand->business_id !== $tenant->id) {
            abort(404);
        }

        $action->execute(
            actor: $request->user(),
            business: $tenant,
            brand: $brand,
            data: $request->validated()
        );

        return redirect()->route('owner.catalogue.brands.index')
            ->with('success', "Brand '{$brand->name}' updated successfully.");
    }

    public function toggleStatus(Request $request, Brand $brand, ActivateBrand $activate, DeactivateBrand $deactivate): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($brand->business_id !== $tenant->id) {
            abort(404);
        }

        if ($brand->status === CatalogStatus::Active) {
            $deactivate->execute($request->user(), $tenant, $brand);
            $msg = "Brand '{$brand->name}' deactivated.";
        } else {
            $activate->execute($request->user(), $tenant, $brand);
            $msg = "Brand '{$brand->name}' activated.";
        }

        return back()->with('success', $msg);
    }

    public function destroy(Request $request, Brand $brand, DeleteBrand $action): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($brand->business_id !== $tenant->id) {
            abort(404);
        }

        $name = $brand->name;
        $action->execute($request->user(), $tenant, $brand);

        return redirect()->route('owner.catalogue.brands.index')
            ->with('success', "Brand '{$name}' deleted successfully.");
    }
}
