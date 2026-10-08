<?php

use App\Http\Controllers\Admin\BusinessController as AdminBusinessController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\SubscriptionPlanController as AdminSubscriptionPlanController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Owner\BrandController;
use App\Http\Controllers\Owner\CategoryController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Owner\ItemController;
use App\Http\Controllers\Owner\UnitController;
use App\Http\Controllers\Tenant\MemberRoleController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\SubscriptionController as TenantSubscriptionController;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureTenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// Platform Admin Routes (Restricted strictly to platform administrators with required platform permissions)
Route::prefix('admin')->middleware(['auth', EnsurePlatformAdmin::class])->group(function () {
    // System Overview Dashboard
    Route::get('/', [AdminDashboardController::class, 'index'])
        ->middleware('permission.platform:businesses.view')
        ->name('admin.dashboard');

    Route::get('/overview', [AdminDashboardController::class, 'index'])
        ->middleware('permission.platform:businesses.view')
        ->name('admin.overview');

    // Tenant / Business Management
    Route::get('/businesses', [AdminBusinessController::class, 'index'])
        ->middleware('permission.platform:businesses.view')
        ->name('admin.businesses.index');

    Route::get('/businesses/create', [AdminBusinessController::class, 'create'])
        ->middleware('permission.platform:businesses.create')
        ->name('admin.businesses.create');

    Route::post('/businesses', [AdminBusinessController::class, 'store'])
        ->middleware('permission.platform:businesses.create')
        ->name('admin.businesses.store');

    Route::get('/businesses/{business}', [AdminBusinessController::class, 'show'])
        ->middleware('permission.platform:businesses.view')
        ->name('admin.businesses.show');

    // Platform Roles Inspection
    Route::get('/roles', [AdminRoleController::class, 'index'])
        ->middleware('permission.platform:platform.roles.manage')
        ->name('admin.roles.index');

    // Subscription Plans Administration
    Route::get('/subscription-plans', [AdminSubscriptionPlanController::class, 'index'])
        ->middleware('permission.platform:subscriptions.manage')
        ->name('admin.subscription-plans.index');

    Route::get('/subscription-plans/create', [AdminSubscriptionPlanController::class, 'create'])
        ->middleware('permission.platform:subscriptions.manage')
        ->name('admin.subscription-plans.create');

    Route::post('/subscription-plans', [AdminSubscriptionPlanController::class, 'store'])
        ->middleware('permission.platform:subscriptions.manage')
        ->name('admin.subscription-plans.store');

    Route::get('/subscription-plans/{plan}/edit', [AdminSubscriptionPlanController::class, 'edit'])
        ->middleware('permission.platform:subscriptions.manage')
        ->name('admin.subscription-plans.edit');

    Route::put('/subscription-plans/{plan}', [AdminSubscriptionPlanController::class, 'update'])
        ->middleware('permission.platform:subscriptions.manage')
        ->name('admin.subscription-plans.update');

    // Subscription Provisioning & Transitions
    Route::post('/subscriptions', [AdminSubscriptionController::class, 'store'])
        ->middleware('permission.platform:subscriptions.manage')
        ->name('admin.subscriptions.store');

    Route::post('/subscriptions/{subscription}/transition', [AdminSubscriptionController::class, 'transition'])
        ->middleware('permission.platform:subscriptions.manage')
        ->name('admin.subscriptions.transition');
});

// Business Owner ERP Surface (Dedicated tenant-scoped operational shell)
Route::prefix('owner')->middleware(['auth', EnsureTenantContext::class, 'permission.business:business.view'])->group(function () {
    Route::get('/', [OwnerDashboardController::class, 'index'])->name('owner.index');
    Route::get('/dashboard', [OwnerDashboardController::class, 'index'])->name('owner.dashboard');

    // Catalogue Management
    Route::prefix('catalogue')->group(function () {
        Route::get('/', fn () => redirect()->route('owner.catalogue.items.index'))->name('owner.catalogue.index');

        // Items
        Route::get('/items', [ItemController::class, 'index'])->name('owner.catalogue.items.index');
        Route::get('/items/create', [ItemController::class, 'create'])->name('owner.catalogue.items.create');
        Route::post('/items', [ItemController::class, 'store'])->name('owner.catalogue.items.store');
        Route::get('/items/{item}', [ItemController::class, 'show'])->name('owner.catalogue.items.show');
        Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('owner.catalogue.items.edit');
        Route::put('/items/{item}', [ItemController::class, 'update'])->name('owner.catalogue.items.update');
        Route::patch('/items/{item}/status', [ItemController::class, 'toggleStatus'])->name('owner.catalogue.items.status');
        Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('owner.catalogue.items.destroy');

        // Categories
        Route::get('/categories', [CategoryController::class, 'index'])->name('owner.catalogue.categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('owner.catalogue.categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('owner.catalogue.categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('owner.catalogue.categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('owner.catalogue.categories.update');
        Route::patch('/categories/{category}/status', [CategoryController::class, 'toggleStatus'])->name('owner.catalogue.categories.status');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('owner.catalogue.categories.destroy');

        // Brands
        Route::get('/brands', [BrandController::class, 'index'])->name('owner.catalogue.brands.index');
        Route::get('/brands/create', [BrandController::class, 'create'])->name('owner.catalogue.brands.create');
        Route::post('/brands', [BrandController::class, 'store'])->name('owner.catalogue.brands.store');
        Route::get('/brands/{brand}/edit', [BrandController::class, 'edit'])->name('owner.catalogue.brands.edit');
        Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('owner.catalogue.brands.update');
        Route::patch('/brands/{brand}/status', [BrandController::class, 'toggleStatus'])->name('owner.catalogue.brands.status');
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('owner.catalogue.brands.destroy');

        // Units
        Route::get('/units', [UnitController::class, 'index'])->name('owner.catalogue.units.index');
        Route::get('/units/create', [UnitController::class, 'create'])->name('owner.catalogue.units.create');
        Route::post('/units', [UnitController::class, 'store'])->name('owner.catalogue.units.store');
        Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])->name('owner.catalogue.units.edit');
        Route::put('/units/{unit}', [UnitController::class, 'update'])->name('owner.catalogue.units.update');
        Route::patch('/units/{unit}/status', [UnitController::class, 'toggleStatus'])->name('owner.catalogue.units.status');
        Route::delete('/units/{unit}', [UnitController::class, 'destroy'])->name('owner.catalogue.units.destroy');
    });
});

// Business Tenant Surface (Restricted strictly via server-authoritative tenant context)
Route::middleware(['auth', EnsureTenantContext::class])->group(function () {
    Route::get('/tenant/current', function (Request $request) {
        $tenant = $request->attributes->get('tenant');

        return response()->json([
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'tenant_slug' => $tenant->slug,
        ]);
    })->name('tenant.current');

    Route::get('/tenant/profile', function (Request $request) {
        $tenant = $request->attributes->get('tenant');

        return response()->json([
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'currency' => $tenant->currency,
        ]);
    })->middleware('permission.business:business.view')->name('tenant.profile');

    // Subscription Inspection & Cancellation (Tenant Surface)
    Route::get('/tenant/subscription', [TenantSubscriptionController::class, 'show'])
        ->middleware('permission.business:business.view')
        ->name('tenant.subscription.show');

    Route::post('/tenant/subscription/cancel', [TenantSubscriptionController::class, 'cancel'])
        ->middleware('permission.business:business.update')
        ->name('tenant.subscription.cancel');

    // Role & Permission Administration (Tenant Surface)
    Route::prefix('tenant')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission.business:staff.manage')
            ->name('tenant.roles.index');

        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('permission.business:staff.manage')
            ->name('tenant.roles.store');

        Route::get('/roles/{role}', [RoleController::class, 'show'])
            ->middleware('permission.business:staff.manage')
            ->name('tenant.roles.show');

        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission.business:staff.manage')
            ->name('tenant.roles.update');

        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission.business:staff.manage')
            ->name('tenant.roles.destroy');

        Route::put('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])
            ->middleware('permission.business:staff.manage')
            ->name('tenant.roles.permissions.sync');

        Route::post('/members/{user}/roles', [MemberRoleController::class, 'store'])
            ->middleware('permission.business:staff.manage')
            ->name('tenant.members.roles.store');

        Route::delete('/members/{user}/roles/{role}', [MemberRoleController::class, 'destroy'])
            ->middleware('permission.business:staff.manage')
            ->name('tenant.members.roles.destroy');
    });
});
