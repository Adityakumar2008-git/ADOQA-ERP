<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Catalogue\ActivateUnit;
use App\Actions\Catalogue\CreateUnit;
use App\Actions\Catalogue\DeactivateUnit;
use App\Actions\Catalogue\DeleteUnit;
use App\Actions\Catalogue\UpdateUnit;
use App\Enums\CatalogStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UnitRequest;
use App\Models\Business;
use App\Models\Unit;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnitController extends Controller
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

        $query = Unit::query()
            ->where('business_id', $tenant->id)
            ->withCount('items');

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $units = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('owner.catalogue.units.index', compact('units', 'tenant'));
    }

    public function create(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        return view('owner.catalogue.units.create', compact('tenant'));
    }

    public function store(UnitRequest $request, CreateUnit $action): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        $unit = $action->execute(
            actor: $request->user(),
            business: $tenant,
            data: $request->validated()
        );

        return redirect()->route('owner.catalogue.units.index')
            ->with('success', "Unit '{$unit->name}' ({$unit->code}) created successfully.");
    }

    public function edit(Request $request, Unit $unit): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($unit->business_id !== $tenant->id) {
            abort(404);
        }

        return view('owner.catalogue.units.edit', compact('unit', 'tenant'));
    }

    public function update(UnitRequest $request, Unit $unit, UpdateUnit $action): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($unit->business_id !== $tenant->id) {
            abort(404);
        }

        $action->execute(
            actor: $request->user(),
            business: $tenant,
            unit: $unit,
            data: $request->validated()
        );

        return redirect()->route('owner.catalogue.units.index')
            ->with('success', "Unit '{$unit->name}' updated successfully.");
    }

    public function toggleStatus(Request $request, Unit $unit, ActivateUnit $activate, DeactivateUnit $deactivate): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($unit->business_id !== $tenant->id) {
            abort(404);
        }

        if ($unit->status === CatalogStatus::Active) {
            $deactivate->execute($request->user(), $tenant, $unit);
            $msg = "Unit '{$unit->name}' deactivated.";
        } else {
            $activate->execute($request->user(), $tenant, $unit);
            $msg = "Unit '{$unit->name}' activated.";
        }

        return back()->with('success', $msg);
    }

    public function destroy(Request $request, Unit $unit, DeleteUnit $action): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($unit->business_id !== $tenant->id) {
            abort(404);
        }

        $name = $unit->name;
        $action->execute($request->user(), $tenant, $unit);

        return redirect()->route('owner.catalogue.units.index')
            ->with('success', "Unit '{$name}' deleted successfully.");
    }
}
