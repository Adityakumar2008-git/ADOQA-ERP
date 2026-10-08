<?php

namespace Tests\Feature\Rbac;

use App\Enums\RoleScope;
use App\Models\Business;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RbacSeeder;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class RolePermissionModelTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed canonical RBAC once for baseline tests
        $this->seed(RbacSeeder::class);
    }

    public function test_ulid_primary_and_foreign_keys_integrity(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();
        $user->membership()->create([
            'business_id' => $business->id,
            'is_owner' => true,
            'status' => 'active',
        ]);

        $role = Role::create([
            'business_id' => $business->id,
            'name' => 'Custom Supervisor',
            'slug' => 'custom_supervisor_'.Str::random(6),
            'scope' => RoleScope::Business,
            'description' => 'Test description',
            'is_system' => false,
        ]);

        $permission = Permission::create([
            'name' => 'Custom Audit',
            'slug' => 'audit.custom_'.Str::random(6),
            'group' => 'audit',
            'scope' => RoleScope::Business,
            'description' => 'Test permission',
        ]);

        $role->permissions()->attach($permission->id);

        $userRole = UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'business_id' => $business->id,
        ]);

        // Assert 26-char ULID primary keys
        $this->assertSame(26, strlen($role->id));
        $this->assertSame(26, strlen($permission->id));
        $this->assertSame(26, strlen($userRole->id));

        // Assert relations
        $this->assertTrue($role->permissions->contains($permission));
        $this->assertSame($user->id, $userRole->user->id);
        $this->assertSame($role->id, $userRole->role->id);
        $this->assertSame($business->id, $userRole->business->id);
    }

    public function test_duplicate_role_prevention_for_global_roles(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        Role::create([
            'business_id' => null,
            'name' => 'Duplicate Owner',
            'slug' => 'owner', // already seeded
            'scope' => RoleScope::Business,
            'is_system' => false,
        ]);
    }

    public function test_duplicate_role_prevention_for_business_roles(): void
    {
        $business = Business::factory()->create();

        Role::create([
            'business_id' => $business->id,
            'name' => 'Lead Cashier',
            'slug' => 'lead_cashier',
            'scope' => RoleScope::Business,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        Role::create([
            'business_id' => $business->id,
            'name' => 'Lead Cashier Copy',
            'slug' => 'lead_cashier',
            'scope' => RoleScope::Business,
        ]);
    }

    public function test_same_slug_permitted_across_different_businesses(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();

        $roleA = Role::create([
            'business_id' => $businessA->id,
            'name' => 'Lead Cashier',
            'slug' => 'lead_cashier',
            'scope' => RoleScope::Business,
        ]);

        $roleB = Role::create([
            'business_id' => $businessB->id,
            'name' => 'Lead Cashier',
            'slug' => 'lead_cashier',
            'scope' => RoleScope::Business,
        ]);

        $this->assertNotSame($roleA->id, $roleB->id);
        $this->assertSame('lead_cashier', $roleA->slug);
        $this->assertSame('lead_cashier', $roleB->slug);
    }

    public function test_duplicate_permission_prevention(): void
    {
        $this->expectException(UniqueConstraintViolationException::class);

        Permission::create([
            'name' => 'Duplicate Create Businesses',
            'slug' => 'businesses.create', // already seeded
            'group' => 'businesses',
            'scope' => RoleScope::Platform,
        ]);
    }

    public function test_duplicate_role_permission_assignment_prevention(): void
    {
        $role = Role::where('slug', 'owner')->firstOrFail();
        $permission = Permission::where('slug', 'sales.create')->firstOrFail();

        $this->expectException(UniqueConstraintViolationException::class);

        // Attempting direct raw duplicate insert into role_permissions
        RolePermission::create([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);
    }

    public function test_duplicate_user_role_assignment_prevention(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();
        $user->membership()->create([
            'business_id' => $business->id,
            'is_owner' => false,
            'status' => 'active',
        ]);
        $role = Role::where('slug', 'cashier')->firstOrFail();

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'business_id' => $business->id,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'business_id' => $business->id,
        ]);
    }

    public function test_platform_user_duplicate_role_prevention(): void
    {
        $platformAdmin = User::factory()->platformAdmin()->create();
        $platformRole = Role::where('slug', 'platform_admin')->firstOrFail();

        UserRole::create([
            'user_id' => $platformAdmin->id,
            'role_id' => $platformRole->id,
            'business_id' => null,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        UserRole::create([
            'user_id' => $platformAdmin->id,
            'role_id' => $platformRole->id,
            'business_id' => null,
        ]);
    }

    public function test_cross_business_role_assignment_prevention_for_custom_roles(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();

        $roleA = Role::create([
            'business_id' => $businessA->id,
            'name' => 'Store A Specialist',
            'slug' => 'specialist_a_'.Str::random(5),
            'scope' => RoleScope::Business,
        ]);

        $userB = User::factory()->create();
        $userB->membership()->create([
            'business_id' => $businessB->id,
            'is_owner' => false,
            'status' => 'active',
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Cannot assign role owned by business/');

        UserRole::create([
            'user_id' => $userB->id,
            'role_id' => $roleA->id,
            'business_id' => $businessB->id,
        ]);
    }

    public function test_user_business_membership_mismatch_rejection(): void
    {
        $businessA = Business::factory()->create();
        $businessB = Business::factory()->create();

        $userA = User::factory()->create();
        $userA->membership()->create([
            'business_id' => $businessA->id,
            'is_owner' => false,
            'status' => 'active',
        ]);

        $canonicalCashierRole = Role::where('slug', 'cashier')->firstOrFail();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessageMatches('/Cannot assign business .* role to user member of business/');

        // Trying to assign role in Business B to a user who belongs to Business A
        UserRole::create([
            'user_id' => $userA->id,
            'role_id' => $canonicalCashierRole->id,
            'business_id' => $businessB->id,
        ]);
    }

    public function test_platform_and_business_scope_separation_invariants(): void
    {
        $platformRole = Role::where('slug', 'platform_admin')->firstOrFail();
        $business = Business::factory()->create();
        $user = User::factory()->platformAdmin()->create();

        // Platform role cannot have business_id
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Platform role cannot be assigned with a business context.');

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $platformRole->id,
            'business_id' => $business->id,
        ]);
    }

    public function test_business_role_requires_non_null_business_id(): void
    {
        $cashierRole = Role::where('slug', 'cashier')->firstOrFail();
        $user = User::factory()->create();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Business role must be assigned with a non-null business context.');

        UserRole::create([
            'user_id' => $user->id,
            'role_id' => $cashierRole->id,
            'business_id' => null,
        ]);
    }

    public function test_invalid_foreign_key_rejection(): void
    {
        $this->expectException(QueryException::class);

        // Invalid fake ULID for role_id
        UserRole::create([
            'user_id' => User::factory()->create()->id,
            'role_id' => (string) Str::ulid(),
            'business_id' => Business::factory()->create()->id,
        ]);
    }

    public function test_canonical_seed_idempotency_and_spec_counts(): void
    {
        // Run seed again
        $seeder = new RbacSeeder;
        $seeder->run();

        $this->assertSame(5, Role::whereNull('business_id')->count());
        $this->assertSame(45, Permission::count());

        $platformAdminRole = Role::where('slug', 'platform_admin')->firstOrFail();
        $this->assertSame(RoleScope::Platform, $platformAdminRole->scope);
        $this->assertSame(15, $platformAdminRole->permissions()->count());

        $ownerRole = Role::where('slug', 'owner')->firstOrFail();
        $this->assertSame(RoleScope::Business, $ownerRole->scope);
        $this->assertSame(30, $ownerRole->permissions()->count());

        $managerRole = Role::where('slug', 'manager')->firstOrFail();
        $this->assertSame(12, $managerRole->permissions()->count());

        $staffRole = Role::where('slug', 'staff')->firstOrFail();
        $this->assertSame(6, $staffRole->permissions()->count());

        $cashierRole = Role::where('slug', 'cashier')->firstOrFail();
        $this->assertSame(6, $cashierRole->permissions()->count());
    }

    public function test_cascade_delete_integrity(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();
        $user->membership()->create([
            'business_id' => $business->id,
            'is_owner' => false,
            'status' => 'active',
        ]);

        $customRole = Role::create([
            'business_id' => $business->id,
            'name' => 'Deletable Role',
            'slug' => 'deletable_'.Str::random(5),
            'scope' => RoleScope::Business,
        ]);

        $permission = Permission::where('slug', 'sales.create')->firstOrFail();
        $customRole->permissions()->attach($permission->id);

        $userRole = UserRole::create([
            'user_id' => $user->id,
            'role_id' => $customRole->id,
            'business_id' => $business->id,
        ]);

        $customRoleId = $customRole->id;
        $userRoleId = $userRole->id;

        // Delete custom role
        $customRole->delete();

        $this->assertDatabaseMissing('roles', ['id' => $customRoleId]);
        $this->assertDatabaseMissing('role_permissions', ['role_id' => $customRoleId]);
        $this->assertDatabaseMissing('user_roles', ['id' => $userRoleId]);
    }
}
