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

    public function test_roles_page_contains_prominent_tenant_controls_entry_point(): void
    {
        $response = $this->actingAs($this->platformAdmin)
            ->get(route('admin.roles.index'));

        $response->assertOk();
        $response->assertSee('Tenant Feature & Role Controls');
        $response->assertSee('Customize permissions for a specific business tenant?');
        $response->assertSee(route('admin.tenant-controls.index'));

        // Confirm misleading button is relabeled accurately
        $response->assertDontSee('JSON Schema API');
        $response->assertSee('Raw RBAC Data (JSON)');
    }
}
