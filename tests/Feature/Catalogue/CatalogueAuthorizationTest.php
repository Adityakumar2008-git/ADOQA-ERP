<?php

namespace Tests\Feature\Catalogue;

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

class CatalogueAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    protected function createMemberWithRole(string $roleSlug, ?Business $business = null): array
    {
        $business ??= Business::factory()->create(['status' => BusinessStatus::Active]);
        $user = User::factory()->create(['status' => UserStatus::Active, 'is_platform_admin' => false]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'is_owner' => $roleSlug === 'owner',
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $role = Role::where('slug', $roleSlug)->whereNull('business_id')->firstOrFail();

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'business_id' => $business->id,
        ]);

        return [$business, $user];
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/owner/catalogue/items');
        $response->assertRedirect('/login');
    }

    public function test_platform_admin_without_membership_is_forbidden(): void
    {
        $admin = User::factory()->withPlatformRole()->create();

        $response = $this->actingAs($admin)->get('/owner/catalogue/items');
        $response->assertStatus(403);
    }

    public function test_cashier_is_forbidden_from_catalogue(): void
    {
        [, $cashier] = $this->createMemberWithRole('cashier');

        $response = $this->actingAs($cashier)->get('/owner/catalogue/items');
        $response->assertStatus(403);
    }

    public function test_staff_is_forbidden_from_catalogue(): void
    {
        [, $staff] = $this->createMemberWithRole('staff');

        $response = $this->actingAs($staff)->get('/owner/catalogue/items');
        $response->assertStatus(403);
    }

    public function test_store_manager_without_catalogue_manage_is_forbidden_from_catalogue(): void
    {
        [$business, $manager] = $this->createMemberWithRole('manager');

        $response = $this->actingAs($manager)->get('/owner/catalogue/items');
        $response->assertStatus(403);
    }

    public function test_manager_with_granted_catalogue_manage_permission_can_access(): void
    {
        [$business, $manager] = $this->createMemberWithRole('manager');
        $managerRole = Role::where('slug', 'manager')->whereNull('business_id')->firstOrFail();
        $cataloguePerm = Permission::where('slug', 'catalogue.manage')->firstOrFail();
        $managerRole->permissions()->attach($cataloguePerm->id);
        app(AuthorizationService::class)->clearMemoized();

        $response = $this->actingAs($manager)->get('/owner/catalogue/items');
        $response->assertOk();
    }

    public function test_suspended_business_owner_is_forbidden(): void
    {
        $business = Business::factory()->create(['status' => BusinessStatus::Suspended]);
        [, $owner] = $this->createMemberWithRole('owner', $business);

        $response = $this->actingAs($owner)->get('/owner/catalogue/items');
        $response->assertStatus(403);
    }

    public function test_inactive_business_owner_is_forbidden(): void
    {
        $business = Business::factory()->create(['status' => BusinessStatus::Inactive]);
        [, $owner] = $this->createMemberWithRole('owner', $business);

        $response = $this->actingAs($owner)->get('/owner/catalogue/items');
        $response->assertStatus(403);
    }
}
