<?php

namespace Tests\Feature\Admin;

use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Auth\AuthorizationService;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TenantControlsWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $platformAdmin;

    protected AuthorizationService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->platformAdmin = User::factory()->withPlatformRole()->create();
        $this->authService = app(AuthorizationService::class);
    }

    protected function createTenantWithStaff(string $roleSlug = 'staff'): array
    {
        $business = Business::factory()->create(['status' => BusinessStatus::Active]);
        $user = User::factory()->create(['status' => UserStatus::Active, 'is_platform_admin' => false]);

        $member = BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'is_owner' => false,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $role = Role::where('slug', $roleSlug)->whereNull('business_id')->firstOrFail();

        $userRole = UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'business_id' => $business->id,
        ]);

        return [$business, $user, $role];
    }

    public function test_master_admin_can_access_tenant_controls_index(): void
    {
        [$bizA] = $this->createTenantWithStaff();
        [$bizB] = $this->createTenantWithStaff();

        $response = $this->actingAs($this->platformAdmin)
            ->get(route('admin.tenant-controls.index'));

        $response->assertOk();
        $response->assertSee('Tenant Feature & Role Controls');
        $response->assertSee($bizA->name);
        $response->assertSee($bizB->name);
    }

    public function test_selecting_business_displays_modules_and_role_overrides(): void
    {
        [$business, , $role] = $this->createTenantWithStaff('manager');

        $response = $this->actingAs($this->platformAdmin)
            ->get(route('admin.tenant-controls.index', [
                'business_id' => $business->id,
                'role_id' => $role->id,
            ]));

        $response->assertOk();
        $response->assertSee($business->name);
        $response->assertSee('Tenant Module Controls');
        $response->assertSee('Catalogue & Products');
        $response->assertSee('Inventory & Warehouses');
        $response->assertSee('Procurement & Suppliers');
        $response->assertSee($role->name);
        $response->assertSee('INHERIT');
        $response->assertSee('ALLOW');
        $response->assertSee('DENY');
    }

    public function test_updating_modules_persists_and_reloads_accurately(): void
    {
        [$business] = $this->createTenantWithStaff();

        // 1. Disable inventory module via tenant controls form
        $response = $this->actingAs($this->platformAdmin)
            ->post(route('admin.tenant-controls.modules.update'), [
                'business_id' => $business->id,
                'modules' => [
                    'catalogue' => '1',
                    'inventory' => '0',
                    'procurement' => '1',
                ],
            ]);

        $response->assertRedirect(route('admin.tenant-controls.index', ['business_id' => $business->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('business_module_settings', [
            'business_id' => $business->id,
            'module_slug' => 'inventory',
            'is_enabled' => false,
        ]);

        // 2. Reload the page and verify persisted state in the HTML
        $reloadResponse = $this->actingAs($this->platformAdmin)
            ->get(route('admin.tenant-controls.index', ['business_id' => $business->id]));

        $reloadResponse->assertOk();
        $reloadResponse->assertSee('DISABLED');
        $reloadResponse->assertSee('Module Disabled (Denied)');
    }

    public function test_updating_role_overrides_persists_and_reloads_accurately(): void
    {
        [$business, $user, $role] = $this->createTenantWithStaff('staff');
        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();

        // Canonical staff does not possess catalogue.manage
        $this->assertFalse($this->authService->can($user, 'catalogue.manage', $business));

        // 1. Set explicit ALLOW override
        $postResponse = $this->actingAs($this->platformAdmin)
            ->post(route('admin.tenant-controls.overrides.update'), [
                'business_id' => $business->id,
                'role_id' => $role->id,
                'overrides' => [
                    $perm->id => 'allow',
                ],
            ]);

        $postResponse->assertRedirect(route('admin.tenant-controls.index', [
            'business_id' => $business->id,
            'role_id' => $role->id,
        ]));
        $postResponse->assertSessionHas('success');

        // Verify database persistence
        $this->assertDatabaseHas('business_role_permission_overrides', [
            'business_id' => $business->id,
            'role_id' => $role->id,
            'permission_id' => $perm->id,
            'state' => 'allow',
        ]);

        // Backend authorization immediately honors the override
        $this->assertTrue($this->authService->can($user, 'catalogue.manage', $business));

        // 2. Reload the page and verify HTML shows ALLOW selected and PERMITTED status
        $reloadResponse = $this->actingAs($this->platformAdmin)
            ->get(route('admin.tenant-controls.index', [
                'business_id' => $business->id,
                'role_id' => $role->id,
            ]));

        $reloadResponse->assertOk();
        $reloadResponse->assertSee('PERMITTED');
        $reloadResponse->assertSee('value="allow"', false);
    }

    public function test_changing_one_tenant_does_not_affect_any_other_tenant(): void
    {
        [$bizA, $userA, $roleA] = $this->createTenantWithStaff('staff');
        [$bizB, $userB] = $this->createTenantWithStaff('staff');

        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();

        // Explicitly ALLOW catalogue.manage for Tenant A
        $this->actingAs($this->platformAdmin)
            ->post(route('admin.tenant-controls.overrides.update'), [
                'business_id' => $bizA->id,
                'role_id' => $roleA->id,
                'overrides' => [
                    $perm->id => 'allow',
                ],
            ]);

        // Tenant A user has permission
        $this->assertTrue($this->authService->can($userA, 'catalogue.manage', $bizA));

        // Tenant B user remains denied (100% tenant isolation preserved)
        $this->assertFalse($this->authService->can($userB, 'catalogue.manage', $bizB));

        // Disable module for Tenant A
        $this->actingAs($this->platformAdmin)
            ->post(route('admin.tenant-controls.modules.update'), [
                'business_id' => $bizA->id,
                'modules' => [
                    'catalogue' => '0',
                ],
            ]);

        // Tenant A is now denied due to disabled module
        $this->assertFalse($this->authService->can($userA, 'catalogue.manage', $bizA));

        // Tenant B catalogue operations remain completely untouched
        [$bizB2, $ownerUserB] = $this->createTenantWithStaff('owner');
        $this->assertTrue($this->authService->can($ownerUserB, 'catalogue.manage', $bizB2));
    }

    public function test_deny_override_can_be_selected_saved_and_persisted(): void
    {
        [$business, $user, $role] = $this->createTenantWithStaff('manager');
        $perm = Permission::where('slug', 'inventory.manage')->firstOrFail();

        // Canonical manager role has inventory.manage
        $this->assertTrue($this->authService->can($user, 'inventory.manage', $business));

        // 1. Submit DENY override
        $postResponse = $this->actingAs($this->platformAdmin)
            ->post(route('admin.tenant-controls.overrides.update'), [
                'business_id' => $business->id,
                'role_id' => $role->id,
                'overrides' => [
                    $perm->id => 'deny',
                ],
            ]);

        // Never redirect to blank page: redirects to tenant-controls with business & role context
        $postResponse->assertRedirect(route('admin.tenant-controls.index', [
            'business_id' => $business->id,
            'role_id' => $role->id,
        ]));
        $postResponse->assertSessionHas('success');

        // Verify DB persistence
        $this->assertDatabaseHas('business_role_permission_overrides', [
            'business_id' => $business->id,
            'role_id' => $role->id,
            'permission_id' => $perm->id,
            'state' => 'deny',
        ]);

        // Audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'user_id' => $this->platformAdmin->id,
            'action' => 'role.permission_override_set',
        ]);

        // Backend immediately denies access
        $this->assertFalse($this->authService->can($user, 'inventory.manage', $business));

        // 2. Reload page and confirm DENIED status and radio persistence
        $reloadResponse = $this->actingAs($this->platformAdmin)
            ->get(route('admin.tenant-controls.index', [
                'business_id' => $business->id,
                'role_id' => $role->id,
            ]));

        $reloadResponse->assertOk();
        $reloadResponse->assertSee('DENIED');
        $reloadResponse->assertSee('value="deny"', false);
    }

    public function test_inherit_override_deletes_override_record_and_restores_template_default(): void
    {
        [$business, $user, $role] = $this->createTenantWithStaff('staff');
        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();

        // First set ALLOW override
        $this->actingAs($this->platformAdmin)
            ->post(route('admin.tenant-controls.overrides.update'), [
                'business_id' => $business->id,
                'role_id' => $role->id,
                'overrides' => [
                    $perm->id => 'allow',
                ],
            ]);
        $this->assertTrue($this->authService->can($user, 'catalogue.manage', $business));

        // Now set override back to INHERIT
        $postResponse = $this->actingAs($this->platformAdmin)
            ->post(route('admin.tenant-controls.overrides.update'), [
                'business_id' => $business->id,
                'role_id' => $role->id,
                'overrides' => [
                    $perm->id => 'inherit',
                ],
            ]);

        $postResponse->assertRedirect(route('admin.tenant-controls.index', [
            'business_id' => $business->id,
            'role_id' => $role->id,
        ]));
        $postResponse->assertSessionHas('success');

        // Override row deleted from DB
        $this->assertDatabaseMissing('business_role_permission_overrides', [
            'business_id' => $business->id,
            'role_id' => $role->id,
            'permission_id' => $perm->id,
        ]);

        // Canonical staff template restored (denied)
        $this->assertFalse($this->authService->can($user, 'catalogue.manage', $business));

        // Reload page and confirm INHERIT selected
        $reloadResponse = $this->actingAs($this->platformAdmin)
            ->get(route('admin.tenant-controls.index', [
                'business_id' => $business->id,
                'role_id' => $role->id,
            ]));
        $reloadResponse->assertOk();
        $reloadResponse->assertSee('value="inherit"', false);
    }

    public function test_disabled_module_strictly_preempts_allow_permission_override(): void
    {
        [$business, $user, $role] = $this->createTenantWithStaff('staff');
        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();

        // Set ALLOW override for staff
        $this->actingAs($this->platformAdmin)
            ->post(route('admin.tenant-controls.overrides.update'), [
                'business_id' => $business->id,
                'role_id' => $role->id,
                'overrides' => [
                    $perm->id => 'allow',
                ],
            ]);
        $this->assertTrue($this->authService->can($user, 'catalogue.manage', $business));

        // Now disable catalogue module for this business
        $this->actingAs($this->platformAdmin)
            ->post(route('admin.tenant-controls.modules.update'), [
                'business_id' => $business->id,
                'modules' => [
                    'catalogue' => '0',
                ],
            ]);

        // Even though role override is ALLOW, disabled module strictly forces false!
        $this->assertFalse($this->authService->can($user, 'catalogue.manage', $business));

        // In the UI, the effective state is DENIED
        $reloadResponse = $this->actingAs($this->platformAdmin)
            ->get(route('admin.tenant-controls.index', [
                'business_id' => $business->id,
                'role_id' => $role->id,
            ]));
        $reloadResponse->assertOk();
        $reloadResponse->assertSee('Module Disabled (Denied)');
    }

    public function test_roles_page_contains_prominent_tenant_controls_entry_point(): void
    {
        $response = $this->actingAs($this->platformAdmin)
            ->get(route('admin.roles.index'));

        $response->assertOk();
        $response->assertSee('Tenant Feature & Role Controls');
        $response->assertSee('Customize permissions for a specific business tenant?');
        $response->assertSee(route('admin.tenant-controls.index'));

        // Confirm misleading buttons are not shown on normal frontend
        $response->assertDontSee('JSON Schema API');
        $response->assertDontSee('Raw RBAC Data (JSON)');
    }

    public function test_layout_markup_contains_overflow_hidden_and_relative_radio_labels(): void
    {
        [$business, , $role] = $this->createTenantWithStaff('manager');

        $response = $this->actingAs($this->platformAdmin)
            ->get(route('admin.tenant-controls.index', [
                'business_id' => $business->id,
                'role_id' => $role->id,
            ]));

        $response->assertOk();
        // Layout must prevent root document scrolling that triggers white blank page
        $response->assertSee('overflow-hidden');
        // Radio labels must be positioned relatively to encapsulate sr-only inputs
        $response->assertSee('relative cursor-pointer');
        // Radio controls must support instant auto-saving on click/change
        $response->assertSee('onchange="this.form.submit()"', false);
    }

    public function test_single_override_submission_via_permission_id_and_state(): void
    {
        [$business, $user, $role] = $this->createTenantWithStaff('staff');
        $perm = Permission::where('slug', 'catalogue.manage')->firstOrFail();

        // 1. Submit single override via permission_id and state
        $postResponse = $this->actingAs($this->platformAdmin)
            ->post(route('admin.tenant-controls.overrides.update'), [
                'business_id' => $business->id,
                'role_id' => $role->id,
                'permission_id' => $perm->id,
                'state' => 'allow',
            ]);

        $postResponse->assertRedirect(route('admin.tenant-controls.index', [
            'business_id' => $business->id,
            'role_id' => $role->id,
        ]));
        $postResponse->assertSessionHas('success');

        $this->assertDatabaseHas('business_role_permission_overrides', [
            'business_id' => $business->id,
            'role_id' => $role->id,
            'permission_id' => $perm->id,
            'state' => 'allow',
        ]);

        $this->assertTrue($this->authService->can($user, 'catalogue.manage', $business));
    }
}
