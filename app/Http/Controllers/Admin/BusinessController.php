<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Business\CreateBusiness;
use App\Actions\Subscription\ProvisionSubscription;
use App\Enums\BusinessStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateBusinessRequest;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
     */
    public function store(
        CreateBusinessRequest $request,
        CreateBusiness $createBusiness,
        ProvisionSubscription $provisionSubscription
    ): JsonResponse|RedirectResponse {
        $business = $createBusiness->execute(
            actor: $request->user(),
            data: $request->validated()
        );

        // Optional subscription plan assignment during business provisioning
        if ($request->filled('plan_id')) {
            $plan = SubscriptionPlan::findOrFail($request->input('plan_id'));
            $provisionSubscription->execute(
                actor: $request->user(),
                business: $business,
                plan: $plan,
                options: [
                    'skip_trial' => $request->boolean('skip_trial'),
                ]
            );
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Business created successfully.',
                'business' => [
                    'id' => $business->id,
                    'name' => $business->name,
                    'slug' => $business->slug,
                    'status' => $business->status->value,
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
     * Display the specified business / tenant details.
     */
    public function show(Request $request, Business $business): View|JsonResponse
    {
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

        if ($request->wantsJson()) {
            return response()->json([
                'business' => $business,
                'audit_logs' => $auditLogs,
            ]);
        }

        return view('admin.businesses.show', [
            'business' => $business,
            'auditLogs' => $auditLogs,
            'availablePlans' => $availablePlans,
        ]);
    }
}
