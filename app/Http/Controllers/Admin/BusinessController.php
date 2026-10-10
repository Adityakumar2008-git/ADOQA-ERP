<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Business\ProvisionBusinessWithSubscription;
use App\Enums\BusinessStatus;
use App\Enums\RoleScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateBusinessRequest;
use App\Http\Requests\Admin\UpdateBusinessRequest;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\BusinessRolePermissionOverride;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Services\Tenancy\ModuleRegistry;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BusinessController extends Controller
{
    /**
     * Display a listing of businesses / tenants.
     * Accessible exclusively to platform administrators.
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Business::query()
            ->with(['ownerMembership.user', 'activeSubscription.plan'])
            ->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->trim()->toString()) {
            if ($enumStatus = BusinessStatus::tryFrom($status)) {
                $query->where('status', $enumStatus);
            }
        }

        $businesses = $query->paginate(15)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'businesses' => $businesses,
            ]);
        }

        return view('admin.businesses.index', [
            'businesses' => $businesses,
            'currentSearch' => $search,
            'currentStatus' => $status,
        ]);
    }

    /**
     * Show the form for provisioning a new business.
     */
    public function create(): View
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        return view('admin.businesses.create', [
            'plans' => $plans,
        ]);
    }

    /**
     * Store a newly created business in storage.
     * Accessible exclusively to platform administrators.
     * Atomically coordinates business and optional initial subscription provisioning.
     */
    public function store(
        CreateBusinessRequest $request,
        ProvisionBusinessWithSubscription $orchestrator
    ): JsonResponse|RedirectResponse {
        $plan = $request->filled('plan_id')
            ? SubscriptionPlan::findOrFail($request->input('plan_id'))
            : null;

        $businessData = $request->safe()->except(['plan_id', 'skip_trial']);
        if ($request->hasFile('logo')) {
            $businessData['logo'] = $request->file('logo');
        }

        try {
            $business = $orchestrator->execute(
                actor: $request->user(),
                businessData: $businessData,
                plan: $plan,
                subscriptionOptions: [
                    'skip_trial' => $request->boolean('skip_trial'),
                ]
            );
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Business created successfully.',
                'business' => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'slug' => $business->slug,
                    'status' => $business->status->value,
                    'logo_url' => $business->logo_url,
                    'owner' => [
                        'id' => $business->owner?->id,
                        'name' => $business->owner?->name,
                        'email' => $business->owner?->email,
                    ],
                ],
            ], 201);
        }

        return redirect()->route('admin.businesses.show', $business)
            ->with('success', "Business '{$business->name}' has been provisioned successfully.");
    }

    /**
     * Show the form for editing an existing business profile.
     */
    public function edit(Business $business): View
    {
        return view('admin.businesses.edit', [
            'business' => $business,
        ]);
    }

    /**
     * Update the business profile and logo.
     */
    public function update(
        UpdateBusinessRequest $request,
        Business $business
    ): RedirectResponse|JsonResponse {
        $validated = $request->validated();

        // Handle logo replacement or removal
        if ($request->boolean('remove_logo')) {
            if ($business->logo_path) {
                Storage::disk('public')->delete($business->logo_path);
                $business->logo_path = null;
            }
        } elseif ($request->hasFile('logo')) {
            if ($business->logo_path) {
                Storage::disk('public')->delete($business->logo_path);
            }
            $business->logo_path = $request->file('logo')->store('logos', 'public');
        }

        $business->fill([
            'name' => $validated['name'],
            'legal_name' => $validated['legal_name'] ?? null,
            'business_type' => $validated['business_type'] ?? $business->business_type,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address_line_1' => $validated['address_line_1'] ?? null,
            'address_line_2' => $validated['address_line_2'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'country' => $validated['country'] ?? $business->country,
            'currency' => strtoupper($validated['currency'] ?? $business->currency),
            'timezone' => $validated['timezone'] ?? $business->timezone,
            'tax_number' => $validated['tax_number'] ?? null,
        ]);
        $business->save();

        AuditLog::create([
            'business_id' => $business->id,
            'user_id' => $request->user()->id,
            'action' => 'business.updated',
            'auditable_type' => Business::class,
            'auditable_id' => $business->id,
            'payload' => [
                'name' => $business->name,
                'has_logo' => (bool) $business->logo_path,
            ],
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Business profile updated successfully.',
                'business' => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'logo_url' => $business->logo_url,
                ],
            ]);
        }

        return redirect()->route('admin.businesses.show', $business)
            ->with('success', "Business '{$business->name}' profile updated successfully.");
    }

    /**
     * Display the specified business / tenant details.
     */
    public function show(
        Request $request,
        Business $business,
        ModuleRegistry $moduleRegistry
    ): View|JsonResponse {
        $business->load([
            'ownerMembership.user',
            'subscriptions.plan',
            'members.user',
            'userRoles.role',
        ]);

        $auditLogs = AuditLog::where('business_id', $business->id)
            ->with('user')
            ->latest()
            ->take(25)
            ->get();

        $availablePlans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get();

        $moduleStates = [];
        foreach ($moduleRegistry->allModules() as $module) {
            $moduleStates[] = [
                'enum' => $module,
                'slug' => $module->value,
                'name' => $module->label(),
                'description' => $module->description(),
                'is_enabled' => $moduleRegistry->isModuleEnabledForBusiness($business, $module),
            ];
        }

        $businessRoles = Role::where('scope', RoleScope::Business)
            ->where(function ($q) use ($business) {
                $q->whereNull('business_id')->orWhere('business_id', $business->id);
            })
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();

        $businessPermissions = Permission::where('scope', RoleScope::Business)
            ->orderBy('group')
            ->orderBy('name')
            ->get();

        $roleOverrides = BusinessRolePermissionOverride::where('business_id', $business->id)
            ->with(['role', 'permission'])
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'business' => $business,
                'audit_logs' => $auditLogs,
                'modules' => $moduleStates,
                'roles' => $businessRoles,
                'overrides' => $roleOverrides,
            ]);
        }

        return view('admin.businesses.show', [
            'business' => $business,
            'auditLogs' => $auditLogs,
            'availablePlans' => $availablePlans,
            'modules' => $moduleStates,
            'businessRoles' => $businessRoles,
            'businessPermissions' => $businessPermissions,
            'roleOverrides' => $roleOverrides,
        ]);
    }
}
