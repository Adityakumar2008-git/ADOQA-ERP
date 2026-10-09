<?php

use App\Http\Controllers\Admin\BusinessController as AdminBusinessController;
use App\Http\Controllers\Admin\BusinessModuleController;
use App\Http\Controllers\Admin\BusinessRoleOverrideController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Admin\SubscriptionPlanController as AdminSubscriptionPlanController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Owner\BrandController;
use App\Http\Controllers\Owner\CategoryController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Owner\GoodsReceiptController;
use App\Http\Controllers\Owner\InventoryStockController;
use App\Http\Controllers\Owner\ItemController;
use App\Http\Controllers\Owner\PurchaseBillController;
use App\Http\Controllers\Owner\PurchaseOrderController;
use App\Http\Controllers\Owner\SupplierController;
use App\Http\Controllers\Owner\SupplierPaymentController;
use App\Http\Controllers\Owner\UnitController;
use App\Http\Controllers\Owner\WarehouseController;
use App\Http\Controllers\Tenant\MemberRoleController;
use App\Http\Controllers\Tenant\ModuleController;
use App\Http\Controllers\Tenant\RoleController;
use App\Http\Controllers\Tenant\RoleOverrideController;
use App\Http\Controllers\Tenant\SubscriptionController as TenantSubscriptionController;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureTenantContext;
use App\Http\Middleware\PreventBackHistoryCaching;
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
Route::prefix('admin')->middleware(['auth', EnsurePlatformAdmin::class, PreventBackHistoryCaching::class])->group(function () {
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

    // Business Tenant Module Settings & Overrides
    Route::post('/businesses/{business}/modules', [BusinessModuleController::class, 'update'])
        ->middleware('permission.platform:businesses.update')
        ->name('admin.businesses.modules.update');

    Route::post('/businesses/{business}/overrides', [BusinessRoleOverrideController::class, 'store'])
        ->middleware('permission.platform:platform.roles.manage')
        ->name('admin.businesses.overrides.store');

    // Platform Roles Inspection (HTML Interface & JSON API)
    Route::get('/roles', [AdminRoleController::class, 'index'])
        ->middleware('permission.platform:platform.roles.manage')
        ->name('admin.roles.index');

    Route::get('/roles/data', [AdminRoleController::class, 'api'])
        ->middleware('permission.platform:platform.roles.manage')
        ->name('admin.roles.api');

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
Route::prefix('owner')->middleware(['auth', EnsureTenantContext::class, 'permission.business:business.view', PreventBackHistoryCaching::class])->group(function () {
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

    // Warehouses & Locations
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [WarehouseController::class, 'index'])->name('owner.inventory.warehouses.index');
        Route::get('/create', [WarehouseController::class, 'create'])->name('owner.inventory.warehouses.create');
        Route::post('/', [WarehouseController::class, 'store'])->name('owner.inventory.warehouses.store');
        Route::get('/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('owner.inventory.warehouses.edit');
        Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('owner.inventory.warehouses.update');
        Route::post('/{warehouse}/locations', [WarehouseController::class, 'storeLocation'])->name('owner.inventory.warehouses.locations.store');
    });

    // Inventory Stock & Movements
    Route::prefix('inventory')->group(function () {
        Route::get('/', fn () => redirect()->route('owner.inventory.stock.index'))->name('owner.inventory.index');
        Route::get('/stock', [InventoryStockController::class, 'index'])->name('owner.inventory.stock.index');
        Route::get('/ledger', [InventoryStockController::class, 'ledger'])->name('owner.inventory.stock.ledger');
        Route::get('/opening-stock', [InventoryStockController::class, 'openingStockForm'])->name('owner.inventory.stock.opening_stock');
        Route::post('/opening-stock', [InventoryStockController::class, 'storeOpeningStock'])->name('owner.inventory.stock.opening_stock.store');
        Route::get('/adjustments', [InventoryStockController::class, 'adjustmentsIndex'])->name('owner.inventory.adjustments.index');
        Route::get('/adjustments/create', [InventoryStockController::class, 'adjustmentForm'])->name('owner.inventory.adjustments.create');
        Route::post('/adjustments', [InventoryStockController::class, 'storeAdjustment'])->name('owner.inventory.adjustments.store');
        Route::get('/transfers', [InventoryStockController::class, 'transfersIndex'])->name('owner.inventory.transfers.index');
        Route::get('/transfers/create', [InventoryStockController::class, 'transferForm'])->name('owner.inventory.transfers.create');
        Route::post('/transfers', [InventoryStockController::class, 'storeTransfer'])->name('owner.inventory.transfers.store');
        Route::post('/transfers/{transfer}/dispatch', [InventoryStockController::class, 'dispatchTransfer'])->name('owner.inventory.transfers.dispatch');
        Route::get('/transfers/{transfer}/receive', [InventoryStockController::class, 'receiveTransferForm'])->name('owner.inventory.transfers.receive');
        Route::post('/transfers/{transfer}/receive', [InventoryStockController::class, 'storeReceiveTransfer'])->name('owner.inventory.transfers.receive.store');
        Route::post('/transfers/{transfer}/cancel', [InventoryStockController::class, 'cancelTransfer'])->name('owner.inventory.transfers.cancel');
        Route::get('/reconcile', [InventoryStockController::class, 'reconcile'])->name('owner.inventory.stock.reconcile');
    });

    // Procurement & Suppliers
    Route::prefix('purchasing')->group(function () {
        Route::get('/', fn () => redirect()->route('owner.purchasing.orders.index'))->name('owner.purchasing.index');

        // Suppliers
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('owner.purchasing.suppliers.index');
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('owner.purchasing.suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('owner.purchasing.suppliers.store');
        Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->name('owner.purchasing.suppliers.show');
        Route::get('/suppliers/{supplier}/ledger', [SupplierController::class, 'ledger'])->name('owner.purchasing.suppliers.ledger');

        // Purchase Orders
        Route::get('/orders', [PurchaseOrderController::class, 'index'])->name('owner.purchasing.orders.index');
        Route::get('/orders/create', [PurchaseOrderController::class, 'create'])->name('owner.purchasing.orders.create');
        Route::post('/orders', [PurchaseOrderController::class, 'store'])->name('owner.purchasing.orders.store');
        Route::get('/orders/{order}', [PurchaseOrderController::class, 'show'])->name('owner.purchasing.orders.show');
        Route::post('/orders/{order}/approve', [PurchaseOrderController::class, 'approve'])->name('owner.purchasing.orders.approve');

        // Purchase Bills
        Route::get('/bills', [PurchaseBillController::class, 'index'])->name('owner.purchasing.bills.index');
        Route::get('/bills/create', [PurchaseBillController::class, 'create'])->name('owner.purchasing.bills.create');
        Route::post('/bills', [PurchaseBillController::class, 'store'])->name('owner.purchasing.bills.store');
        Route::get('/bills/{bill}', [PurchaseBillController::class, 'show'])->name('owner.purchasing.bills.show');
        Route::post('/bills/{bill}/post', [PurchaseBillController::class, 'post'])->name('owner.purchasing.bills.post');

        // Goods Receipts
        Route::get('/receipts', [GoodsReceiptController::class, 'index'])->name('owner.purchasing.receipts.index');
        Route::get('/receipts/create', [GoodsReceiptController::class, 'create'])->name('owner.purchasing.receipts.create');
        Route::post('/receipts', [GoodsReceiptController::class, 'store'])->name('owner.purchasing.receipts.store');
        Route::get('/receipts/{receipt}', [GoodsReceiptController::class, 'show'])->name('owner.purchasing.receipts.show');
        Route::post('/receipts/{receipt}/post', [GoodsReceiptController::class, 'post'])->name('owner.purchasing.receipts.post');

        // Supplier Payments
        Route::get('/payments', [SupplierPaymentController::class, 'index'])->name('owner.purchasing.payments.index');
        Route::get('/payments/create', [SupplierPaymentController::class, 'create'])->name('owner.purchasing.payments.create');
        Route::post('/payments', [SupplierPaymentController::class, 'store'])->name('owner.purchasing.payments.store');
        Route::get('/payments/{payment}', [SupplierPaymentController::class, 'show'])->name('owner.purchasing.payments.show');
        Route::post('/payments/{payment}/reverse', [SupplierPaymentController::class, 'reverse'])->name('owner.purchasing.payments.reverse');
        Route::post('/payments/{payment}/allocate-advance', [SupplierPaymentController::class, 'allocateAdvance'])->name('owner.purchasing.payments.allocate_advance');
    });
});

// Staff / Cashier POS Surface (Reserved)
Route::prefix('app')->middleware(['auth', EnsureTenantContext::class, PreventBackHistoryCaching::class])->group(function () {
    Route::get('/', function () {
        return redirect()->route('owner.dashboard');
    })->name('app.index');
});

// Business Tenant Surface (Restricted strictly via server-authoritative tenant context)
Route::middleware(['auth', EnsureTenantContext::class, PreventBackHistoryCaching::class])->group(function () {
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

        // Tenant Module Controls
        Route::get('/modules', [ModuleController::class, 'index'])
            ->middleware('permission.business:business.view')
            ->name('tenant.modules.index');

        Route::put('/modules', [ModuleController::class, 'update'])
            ->middleware('permission.business:business.update')
            ->name('tenant.modules.update');

        // Tenant Role Overrides
        Route::get('/roles/{role}/overrides', [RoleOverrideController::class, 'index'])
            ->middleware('permission.business:staff.manage')
            ->name('tenant.roles.overrides.index');

        Route::post('/roles/{role}/overrides', [RoleOverrideController::class, 'store'])
            ->middleware('permission.business:staff.manage')
            ->name('tenant.roles.overrides.store');
    });
});
