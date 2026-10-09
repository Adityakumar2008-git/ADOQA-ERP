<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Tenancy\UpdateBusinessModuleSettings;
use App\Enums\BusinessModule;
use App\Http\Controllers\Controller;
use App\Models\Business;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BusinessModuleController extends Controller
{
    /**
     * Update module configurations for a specific business tenant.
     * Accessible exclusively to platform administrators with businesses.update permission.
     */
    public function update(
        Request $request,
        Business $business,
        UpdateBusinessModuleSettings $action
    ): JsonResponse|RedirectResponse {
        $rawModules = $request->input('modules', []);

        // Process module inputs: array of [module_slug => 0/1/true/false]
        $modules = [];
        foreach (BusinessModule::cases() as $module) {
            if (is_array($rawModules) && array_key_exists($module->value, $rawModules)) {
                $modules[$module->value] = filter_var($rawModules[$module->value], FILTER_VALIDATE_BOOLEAN);
            }
        }

        try {
            $updatedModules = $action->execute(
                actor: $request->user(),
                business: $business,
                modules: $modules
            );
        } catch (DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Business modules updated successfully.',
                'modules' => $updatedModules,
            ]);
        }

        return redirect()->route('admin.businesses.show', $business)
            ->with('success', 'Business module configurations updated successfully.');
    }
}
