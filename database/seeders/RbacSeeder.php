<?php

namespace Database\Seeders;

use App\Enums\RoleScope;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    /**
     * Canonical platform permissions from 03_ROLES_PERMISSIONS.md
     */
    protected array $platformPermissions = [
        ['slug' => 'businesses.create', 'name' => 'Create Businesses', 'group' => 'businesses'],
        ['slug' => 'businesses.view', 'name' => 'View Businesses', 'group' => 'businesses'],
        ['slug' => 'businesses.update', 'name' => 'Update Businesses', 'group' => 'businesses'],
        ['slug' => 'businesses.suspend', 'name' => 'Suspend Businesses', 'group' => 'businesses'],
        ['slug' => 'subscriptions.manage', 'name' => 'Manage Subscriptions', 'group' => 'subscriptions'],
        ['slug' => 'platform.users.view', 'name' => 'View Platform Users', 'group' => 'platform'],
        ['slug' => 'platform.roles.manage', 'name' => 'Manage Platform Roles', 'group' => 'platform'],
        ['slug' => 'partners.manage', 'name' => 'Manage Partners', 'group' => 'partners'],
        ['slug' => 'leads.manage', 'name' => 'Manage Leads', 'group' => 'marketing'],
        ['slug' => 'campaigns.manage', 'name' => 'Manage Marketing Campaigns', 'group' => 'marketing'],
        ['slug' => 'commissions.manage', 'name' => 'Manage Commissions', 'group' => 'partners'],
        ['slug' => 'payouts.manage', 'name' => 'Manage Payouts', 'group' => 'partners'],
        ['slug' => 'platform.settings.manage', 'name' => 'Manage Platform Settings', 'group' => 'platform'],
        ['slug' => 'system_logs.view', 'name' => 'View System Logs', 'group' => 'system'],
        ['slug' => 'platform.analytics.view', 'name' => 'View Platform Analytics', 'group' => 'platform'],
    ];

    /**
     * Canonical business permissions from 03_ROLES_PERMISSIONS.md
     */
    protected array $businessPermissions = [
        // Business profile
        ['slug' => 'business.view', 'name' => 'View Business Profile', 'group' => 'business'],
        ['slug' => 'business.update', 'name' => 'Update Business Profile', 'group' => 'business'],

        // Staff
        ['slug' => 'staff.manage', 'name' => 'Manage Staff Members', 'group' => 'staff'],

        // Catalogue & Inventory
        ['slug' => 'catalogue.manage', 'name' => 'Manage Product Catalogue', 'group' => 'catalogue'],
        ['slug' => 'warehouses.manage', 'name' => 'Manage Warehouses & Locations', 'group' => 'inventory'],
        ['slug' => 'warehouses.view', 'name' => 'View Warehouses & Locations', 'group' => 'inventory'],
        ['slug' => 'inventory.manage', 'name' => 'Manage Inventory Stock', 'group' => 'inventory'],
        ['slug' => 'inventory.view', 'name' => 'View Assigned Inventory Stock', 'group' => 'inventory'],
        ['slug' => 'inventory.adjust', 'name' => 'Perform Stock Adjustments', 'group' => 'inventory'],
        ['slug' => 'inventory.transfer', 'name' => 'Transfer Stock Between Warehouses', 'group' => 'inventory'],
        ['slug' => 'inventory.opening_stock', 'name' => 'Record Opening Stock', 'group' => 'inventory'],
        ['slug' => 'inventory.reconcile', 'name' => 'Reconcile Inventory Balances', 'group' => 'inventory'],

        // Purchasing & Suppliers
        ['slug' => 'suppliers.manage', 'name' => 'Manage Suppliers', 'group' => 'purchasing'],
        ['slug' => 'suppliers.view', 'name' => 'View Supplier Directory', 'group' => 'purchasing'],
        ['slug' => 'purchases.manage', 'name' => 'Manage Purchase Orders', 'group' => 'purchasing'],
        ['slug' => 'purchases.create', 'name' => 'Create Purchase Orders', 'group' => 'purchasing'],
        ['slug' => 'purchases.approve', 'name' => 'Approve Purchase Orders', 'group' => 'purchasing'],
        ['slug' => 'purchases.bills.manage', 'name' => 'Manage Purchase Bills', 'group' => 'purchasing'],
        ['slug' => 'purchases.bills.post', 'name' => 'Post Purchase Bills to Payable Ledger', 'group' => 'purchasing'],
        ['slug' => 'purchases.receipts.prepare', 'name' => 'Prepare Goods Receipts', 'group' => 'purchasing'],
        ['slug' => 'purchases.receipts.post', 'name' => 'Post and Approve Goods Receipts', 'group' => 'purchasing'],
        ['slug' => 'suppliers.payments.create', 'name' => 'Create Supplier Payments', 'group' => 'purchasing'],
        ['slug' => 'suppliers.payments.post', 'name' => 'Post and Reverse Supplier Payments', 'group' => 'purchasing'],
        ['slug' => 'suppliers.ledger.view', 'name' => 'View Supplier Payable Subledger', 'group' => 'purchasing'],

        // Customers & Sales
        ['slug' => 'customers.manage', 'name' => 'Manage Customers', 'group' => 'sales'],
        ['slug' => 'sales.create', 'name' => 'Create Sales Orders & POS Checkout', 'group' => 'sales'],
        ['slug' => 'payments.receive', 'name' => 'Receive Payments', 'group' => 'sales'],
        ['slug' => 'invoices.manage', 'name' => 'Manage Invoices', 'group' => 'sales'],
        ['slug' => 'invoices.view', 'name' => 'View Invoices', 'group' => 'sales'],
        ['slug' => 'invoices.print', 'name' => 'Print and Share Invoices', 'group' => 'sales'],
        ['slug' => 'returns.manage', 'name' => 'Manage Sales Returns', 'group' => 'sales'],
        ['slug' => 'returns.create', 'name' => 'Create Limited Returns', 'group' => 'sales'],
        ['slug' => 'quotations.manage', 'name' => 'Manage Quotations', 'group' => 'sales'],
        ['slug' => 'bookings.manage', 'name' => 'Manage Pre-Orders & Bookings', 'group' => 'sales'],

        // Finance & EMI
        ['slug' => 'finance.manage', 'name' => 'Manage Accounts & Ledgers', 'group' => 'finance'],
        ['slug' => 'expenses.manage', 'name' => 'Manage Business Expenses', 'group' => 'finance'],
        ['slug' => 'emi.manage', 'name' => 'Manage EMI Accounts & Plans', 'group' => 'emi'],

        // Reports
        ['slug' => 'reports.view', 'name' => 'View Business Reports', 'group' => 'reports'],

        // HR & People
        ['slug' => 'payroll.manage', 'name' => 'Manage Payroll Runs', 'group' => 'people'],
        ['slug' => 'attendance.manage', 'name' => 'Manage Staff Attendance', 'group' => 'people'],
        ['slug' => 'attendance.view_own', 'name' => 'View Own Attendance', 'group' => 'people'],
        ['slug' => 'leave.manage', 'name' => 'Manage Leave Requests', 'group' => 'people'],
        ['slug' => 'advances.manage', 'name' => 'Manage Salary Advances', 'group' => 'people'],
        ['slug' => 'performance.view', 'name' => 'View Staff Performance', 'group' => 'people'],

        // Settings & Audit
        ['slug' => 'settings.manage', 'name' => 'Manage Business Settings', 'group' => 'system'],
        ['slug' => 'audit.view', 'name' => 'View Business Audit Logs', 'group' => 'system'],
    ];

    /**
     * Run the database seeds idempotently.
     */
    public function run(): void
    {
        // 1. Seed Platform Permissions
        $platformPermModels = [];
        foreach ($this->platformPermissions as $perm) {
            $platformPermModels[$perm['slug']] = Permission::updateOrCreate(
                ['slug' => $perm['slug']],
                [
                    'name' => $perm['name'],
                    'group' => $perm['group'],
                    'scope' => RoleScope::Platform,
                ]
            );
        }

        // 2. Seed Business Permissions
        $businessPermModels = [];
        foreach ($this->businessPermissions as $perm) {
            $businessPermModels[$perm['slug']] = Permission::updateOrCreate(
                ['slug' => $perm['slug']],
                [
                    'name' => $perm['name'],
                    'group' => $perm['group'],
                    'scope' => RoleScope::Business,
                ]
            );
        }

        // 3. Seed Platform Administrator Role
        $platformAdminRole = Role::updateOrCreate(
            ['slug' => 'platform_admin', 'business_id' => null],
            [
                'name' => 'Platform Administrator',
                'scope' => RoleScope::Platform,
                'description' => 'Master platform administrator with complete platform authority.',
                'is_system' => true,
            ]
        );
        $platformAdminRole->permissions()->sync(array_values(array_map(fn ($p) => $p->id, $platformPermModels)));

        // 4. Seed Canonical Business Owner Role
        $ownerRole = Role::updateOrCreate(
            ['slug' => 'owner', 'business_id' => null],
            [
                'name' => 'Business Owner',
                'scope' => RoleScope::Business,
                'description' => 'Primary account owner with full authority over business operations.',
                'is_system' => true,
            ]
        );
        $ownerRole->permissions()->sync(array_values(array_map(fn ($p) => $p->id, $businessPermModels)));

        // 5. Seed Canonical Business Manager Role
        $managerRole = Role::updateOrCreate(
            ['slug' => 'manager', 'business_id' => null],
            [
                'name' => 'Store Manager',
                'scope' => RoleScope::Business,
                'description' => 'Configurable operational manager for sales, purchasing, inventory, and staff.',
                'is_system' => true,
            ]
        );
        $managerPermSlugs = [
            'sales.create',
            'invoices.manage',
            'invoices.view',
            'invoices.print',
            'customers.manage',
            'inventory.manage',
            'inventory.view',
            'inventory.adjust',
            'inventory.transfer',
            'inventory.opening_stock',
            'inventory.reconcile',
            'warehouses.manage',
            'warehouses.view',
            'purchases.manage',
            'purchases.create',
            'purchases.approve',
            'purchases.bills.manage',
            'purchases.bills.post',
            'purchases.receipts.prepare',
            'purchases.receipts.post',
            'suppliers.manage',
            'suppliers.view',
            'suppliers.payments.create',
            'suppliers.payments.post',
            'suppliers.ledger.view',
            'reports.view',
            'staff.manage',
            'business.view',
        ];
        $managerRole->permissions()->sync($this->collectPermissionIds($businessPermModels, $managerPermSlugs));

        // 6. Seed Canonical Staff Role
        $staffRole = Role::updateOrCreate(
            ['slug' => 'staff', 'business_id' => null],
            [
                'name' => 'Staff Member',
                'scope' => RoleScope::Business,
                'description' => 'Standard staff operational role with sales, customer management, and own attendance.',
                'is_system' => true,
            ]
        );
        $staffPermSlugs = [
            'sales.create',
            'customers.manage',
            'invoices.view',
            'inventory.view',
            'warehouses.view',
            'purchases.receipts.prepare',
            'attendance.view_own',
            'reports.view',
        ];
        $staffRole->permissions()->sync($this->collectPermissionIds($businessPermModels, $staffPermSlugs));

        // 7. Seed Canonical Cashier Role
        $cashierRole = Role::updateOrCreate(
            ['slug' => 'cashier', 'business_id' => null],
            [
                'name' => 'Cashier',
                'scope' => RoleScope::Business,
                'description' => 'POS cashier role for sales checkout, receiving payments, and sharing invoices.',
                'is_system' => true,
            ]
        );
        $cashierPermSlugs = [
            'sales.create',
            'payments.receive',
            'invoices.view',
            'invoices.print',
            'customers.manage',
            'returns.create',
        ];
        $cashierRole->permissions()->sync($this->collectPermissionIds($businessPermModels, $cashierPermSlugs));
    }

    /**
     * Helper to map slug list to permission IDs.
     */
    protected function collectPermissionIds(array $permModels, array $slugs): array
    {
        $ids = [];
        foreach ($slugs as $slug) {
            if (isset($permModels[$slug])) {
                $ids[] = $permModels[$slug]->id;
            }
        }

        return $ids;
    }
}
