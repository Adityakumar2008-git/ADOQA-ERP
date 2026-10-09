<?php

namespace App\Services\Tenancy;

use App\Enums\BusinessModule;
use App\Models\Business;
use App\Models\BusinessModuleSetting;

class ModuleRegistry
{
    /**
     * Explicit, authoritative mapping from BusinessModule to permission slugs.
     * Never inferred dynamically.
     *
     * @var array<string, array<string>>
     */
    protected array $modulePermissions = [
        BusinessModule::Catalogue->value => [
            'catalogue.manage',
        ],
        BusinessModule::Inventory->value => [
            'warehouses.manage',
            'warehouses.view',
            'inventory.manage',
            'inventory.view',
            'inventory.adjust',
            'inventory.transfer',
            'inventory.opening_stock',
            'inventory.reconcile',
        ],
        BusinessModule::Procurement->value => [
            'suppliers.manage',
            'suppliers.view',
            'purchases.manage',
            'purchases.create',
            'purchases.approve',
            'purchases.bills.manage',
            'purchases.bills.post',
            'purchases.receipts.prepare',
            'purchases.receipts.post',
            'suppliers.payments.create',
            'suppliers.payments.post',
            'suppliers.ledger.view',
        ],
        BusinessModule::Customers->value => [
            'customers.manage',
        ],
        BusinessModule::Sales->value => [
            'sales.create',
            'payments.receive',
            'invoices.manage',
            'invoices.view',
            'invoices.print',
            'returns.manage',
            'returns.create',
            'quotations.manage',
            'bookings.manage',
        ],
        BusinessModule::Finance->value => [
            'finance.manage',
            'expenses.manage',
            'emi.manage',
        ],
        BusinessModule::Reports->value => [
            'reports.view',
        ],
        BusinessModule::People->value => [
            'staff.manage',
            'payroll.manage',
            'attendance.manage',
            'attendance.view_own',
            'leave.manage',
            'advances.manage',
            'performance.view',
        ],
        BusinessModule::Settings->value => [
            'business.view',
            'business.update',
            'settings.manage',
            'audit.view',
        ],
    ];

    /**
     * Inverted lookup cache: permissionSlug -> BusinessModule.
     *
     * @var array<string, BusinessModule>|null
     */
    protected ?array $permissionToModuleMap = null;

    /**
     * In-memory cache of disabled modules per business ID.
     *
     * @var array<string, array<string, bool>>
     */
    protected array $businessDisabledModulesCache = [];

    /**
     * Return all supported modules.
     *
     * @return array<BusinessModule>
     */
    public function allModules(): array
    {
        return BusinessModule::cases();
    }

    /**
     * Get permission slugs explicitly mapped to a module.
     *
     * @return array<string>
     */
    public function getPermissionsForModule(BusinessModule|string $module): array
    {
        $key = $module instanceof BusinessModule ? $module->value : $module;

        return $this->modulePermissions[$key] ?? [];
    }

    /**
     * Resolve the authoritative module owning a given permission slug.
     */
    public function getModuleForPermission(string $permissionSlug): ?BusinessModule
    {
        if ($this->permissionToModuleMap === null) {
            $this->buildPermissionToModuleMap();
        }

        return $this->permissionToModuleMap[$permissionSlug] ?? null;
    }

    /**
     * Check if a specific module is enabled for a business.
     * Default: ENABLED unless an explicit database setting has is_enabled = false.
     */
    public function isModuleEnabledForBusiness(Business $business, BusinessModule|string $module): bool
    {
        $moduleKey = $module instanceof BusinessModule ? $module->value : $module;

        if (! isset($this->businessDisabledModulesCache[$business->id])) {
            $this->loadBusinessModuleSettings($business);
        }

        return ! isset($this->businessDisabledModulesCache[$business->id][$moduleKey]);
    }

    /**
     * Clear cached module states (call upon setting changes).
     */
    public function clearCache(): void
    {
        $this->businessDisabledModulesCache = [];
    }

    /**
     * Load disabled module slugs for a business from database into cache.
     */
    protected function loadBusinessModuleSettings(Business $business): void
    {
        $settings = BusinessModuleSetting::query()
            ->where('business_id', $business->id)
            ->where('is_enabled', false)
            ->get();

        $disabledKeys = [];
        foreach ($settings as $setting) {
            $key = $setting->module_slug instanceof BusinessModule
                ? $setting->module_slug->value
                : (string) $setting->module_slug;
            $disabledKeys[$key] = true;
        }

        $this->businessDisabledModulesCache[$business->id] = $disabledKeys;
    }

    /**
     * Build inverted lookup map from module permissions table.
     */
    protected function buildPermissionToModuleMap(): void
    {
        $this->permissionToModuleMap = [];

        foreach ($this->modulePermissions as $moduleSlug => $permissions) {
            $enum = BusinessModule::tryFrom($moduleSlug);
            if (! $enum) {
                continue;
            }

            foreach ($permissions as $slug) {
                $this->permissionToModuleMap[$slug] = $enum;
            }
        }
    }
}
