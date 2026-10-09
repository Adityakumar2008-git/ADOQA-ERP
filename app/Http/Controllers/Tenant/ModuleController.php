<?php

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenancy\UpdateBusinessModuleSettings;
use App\Enums\BusinessModule;
use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Services\Tenancy\ModuleRegistry;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModuleController extends Controller
{
    /**
     * List all modules and their enabled state for current tenant.
     */
    public function index(Request $request, ModuleRegistry $registry): JsonResponse
    {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        $modules = [];
        foreach ($registry->allModules() as $module) {
            $modules[] = [
                'slug' => $module->value,
                'name' => $module->label(),
                'description' => $module->description(),
                'is_enabled' => $registry->isModuleEnabledForBusiness($tenant, $module),
                'permissions' => $registry->getPermissionsForModule($module),
            ];
        }

        return response()->json([
            'modules' => $modules,
        ]);
    }

    /**
     * Update module configurations for current tenant.
     */
    public function update(
        Request $request,
        UpdateBusinessModuleSettings $action
    ): JsonResponse {
        /** @var Business $tenant */
        $tenant = $request->attributes->get('tenant');

        $rawModules = $request->input('modules', []);
        $modules = [];

        foreach (BusinessModule::cases() as $module) {
            if (is_array($rawModules) && array_key_exists($module->value, $rawModules)) {
                $modules[$module->value] = filter_var($rawModules[$module->value], FILTER_VALIDATE_BOOLEAN);
            }
        }

        try {
            $updated = $action->execute(
                actor: $request->user(),
                business: $tenant,
                modules: $modules
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Module configurations updated successfully.',
            'modules' => $updated,
        ]);
    }
}
