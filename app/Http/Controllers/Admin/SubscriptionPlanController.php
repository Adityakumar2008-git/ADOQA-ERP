<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Subscription\CreateSubscriptionPlan;
use App\Actions\Subscription\UpdateSubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateSubscriptionPlanRequest;
use App\Http\Requests\Admin\UpdateSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of subscription plans in platform scope.
     */
    public function index(Request $request): View|JsonResponse
    {
        $plans = SubscriptionPlan::query()
            ->withCount('subscriptions')
            ->orderBy('price')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'plans' => $plans,
            ]);
        }

        return view('admin.plans.index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Show form to create a new subscription plan.
     */
    public function create(): View
    {
        return view('admin.plans.create');
    }

    /**
     * Create a new subscription plan.
     */
    public function store(CreateSubscriptionPlanRequest $request, CreateSubscriptionPlan $action): JsonResponse|RedirectResponse
    {
        $plan = $action->execute(
            actor: $request->user(),
            data: $request->validated()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Subscription plan created successfully.',
                'plan' => $plan,
            ], 201);
        }

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', "Subscription plan '{$plan->name}' created successfully.");
    }

    /**
     * Show the form for editing an existing subscription plan.
     */
    public function edit(SubscriptionPlan $plan): View
    {
        return view('admin.plans.edit', [
            'plan' => $plan,
        ]);
    }

    /**
     * Update an existing subscription plan.
     */
    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $plan, UpdateSubscriptionPlan $action): JsonResponse|RedirectResponse
    {
        $updatedPlan = $action->execute(
            actor: $request->user(),
            plan: $plan,
            data: $request->validated()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Subscription plan updated successfully.',
                'plan' => $updatedPlan,
            ]);
        }

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', "Subscription plan '{$updatedPlan->name}' updated successfully.");
    }
}
