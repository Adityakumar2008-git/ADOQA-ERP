<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Business\CreateBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateBusinessRequest;
use Illuminate\Http\JsonResponse;

class BusinessController extends Controller
{
    /**
     * Store a newly created business in storage.
     * Accessible exclusively to platform administrators.
     */
    public function store(CreateBusinessRequest $request, CreateBusiness $createBusiness): JsonResponse
    {
        $business = $createBusiness->execute(
            actor: $request->user(),
            data: $request->validated()
        );

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
}
