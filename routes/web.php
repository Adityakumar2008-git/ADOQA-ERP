<?php

use App\Http\Controllers\Admin\BusinessController as AdminBusinessController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
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
    Route::post('/businesses', [AdminBusinessController::class, 'store'])
        ->middleware('permission.platform:businesses.create')
        ->name('admin.businesses.store');
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
});
