<?php

namespace App\Http\Controllers\Owner;

use App\Actions\Catalogue\ActivateCategory;
use App\Actions\Catalogue\CreateCategory;
use App\Actions\Catalogue\DeactivateCategory;
use App\Actions\Catalogue\DeleteCategory;
use App\Actions\Catalogue\UpdateCategory;
use App\Enums\CatalogStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CategoryRequest;
use App\Models\Business;
use App\Models\Category;
use App\Services\Auth\AuthorizationService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
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

        $query = Category::query()
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

        $categories = $query->latest('created_at')->paginate(15)->withQueryString();

        return view('owner.catalogue.categories.index', compact('categories', 'tenant'));
    }

    public function create(Request $request): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        return view('owner.catalogue.categories.create', compact('tenant'));
    }

    public function store(CategoryRequest $request, CreateCategory $action): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        $category = $action->execute(
            actor: $request->user(),
            business: $tenant,
            data: $request->validated()
        );

        return redirect()->route('owner.catalogue.categories.index')
            ->with('success', "Category '{$category->name}' created successfully.");
    }

    public function edit(Request $request, Category $category): View
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($category->business_id !== $tenant->id) {
            abort(404);
        }

        return view('owner.catalogue.categories.edit', compact('category', 'tenant'));
    }

    public function update(CategoryRequest $request, Category $category, UpdateCategory $action): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($category->business_id !== $tenant->id) {
            abort(404);
        }

        $action->execute(
            actor: $request->user(),
            business: $tenant,
            category: $category,
            data: $request->validated()
        );

        return redirect()->route('owner.catalogue.categories.index')
            ->with('success', "Category '{$category->name}' updated successfully.");
    }

    public function toggleStatus(Request $request, Category $category, ActivateCategory $activate, DeactivateCategory $deactivate): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($category->business_id !== $tenant->id) {
            abort(404);
        }

        if ($category->status === CatalogStatus::Active) {
            $deactivate->execute($request->user(), $tenant, $category);
            $msg = "Category '{$category->name}' deactivated.";
        } else {
            $activate->execute($request->user(), $tenant, $category);
            $msg = "Category '{$category->name}' activated.";
        }

        return back()->with('success', $msg);
    }

    public function destroy(Request $request, Category $category, DeleteCategory $action): RedirectResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant') ?? $this->tenantContext->current();
        $this->authService->authorize($request->user(), 'catalogue.manage', $tenant);

        if ($category->business_id !== $tenant->id) {
            abort(404);
        }

        $name = $category->name;
        $action->execute($request->user(), $tenant, $category);

        return redirect()->route('owner.catalogue.categories.index')
            ->with('success', "Category '{$name}' deleted successfully.");
    }
}
