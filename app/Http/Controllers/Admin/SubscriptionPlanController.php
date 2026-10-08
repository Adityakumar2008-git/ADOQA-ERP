<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Subscription\CreateSubscriptionPlan;
use App\Actions\Subscription\UpdateSubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateSubscriptionPlanRequest;
use App\Http\Requests\Admin\UpdateSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;

class SubscriptionPlanController extends Controller
{
    /**
     * Display a listing of subscription plans in platform scope.
     */
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::query()
            ->orderBy('price')
            ->get();

        return response()->json([
            'plans' => $plans,
        ]);
    }

    /**
     * Create a new subscription plan.
     */
    public function store(CreateSubscriptionPlanRequest $request, CreateSubscriptionPlan $action): JsonResponse
    {
        $plan = $action->execute(
            actor: $request->user(),
            data: $request->validated()
        );

        return response()->json([
            'message' => 'Subscription plan created successfully.',
            'plan' => $plan,
        ], 201);
    }

    /**
     * Update an existing subscription plan.
     */
    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $plan, UpdateSubscriptionPlan $action): JsonResponse
    {
        $updatedPlan = $action->execute(
            actor: $request->user(),
            plan: $plan,
            data: $request->validated()
        );

        return response()->json([
            'message' => 'Subscription plan updated successfully.',
            'plan' => $updatedPlan,
        ]);
    }
}
