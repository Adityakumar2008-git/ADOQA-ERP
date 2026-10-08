<?php

namespace Tests\Feature\Business;

use App\Actions\Business\CreateBusiness;
use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RbacSeeder;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BusinessCreationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_unauthenticated_user_cannot_create_business(): void
    {
        $response = $this->postJson('/admin/businesses', [
            'name' => 'Attempted Store',
            'slug' => 'attempted-store',
            'owner_name' => 'Owner Name',
            'owner_email' => 'owner@attempted.local',
            'owner_password' => 'SecurePass123!',
        ]);

        $response->assertStatus(401);
    }

    public function test_ordinary_authenticated_user_cannot_create_business(): void
    {
        $ordinaryUser = User::factory()->create();

        $this->actingAs($ordinaryUser);

        $response = $this->postJson('/admin/businesses', [
            'name' => 'Attempted Store',
            'slug' => 'attempted-store',
            'owner_name' => 'Owner Name',
            'owner_email' => 'owner@attempted.local',
            'owner_password' => 'SecurePass123!',
        ]);

        $response->assertStatus(403);
    }

    public function test_non_platform_admin_calling_create_business_action_throws_authorization_exception(): void
    {
        $ordinaryUser = User::factory()->create();
        $action = app(CreateBusiness::class);

        $this->expectException(AuthorizationException::class);

        $action->execute($ordinaryUser, [
            'name' => 'Attempted Store',
            'slug' => 'attempted-store',
            'owner_name' => 'Owner Name',
            'owner_email' => 'owner@attempted.local',
            'owner_password' => 'SecurePass123!',
        ]);
    }

    public function test_user_with_business_permissions_cannot_provision_business(): void
    {
        $existingBusiness = Business::factory()->create();
        $businessOwner = User::factory()->create();
        BusinessMember::create([
            'business_id' => $existingBusiness->id,
            'user_id' => $businessOwner->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
        ]);
        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();
        UserRole::create([
            'user_id' => $businessOwner->id,
            'role_id' => $ownerRole->id,
            'business_id' => $existingBusiness->id,
        ]);

        $this->actingAs($businessOwner);

        $response = $this->postJson('/admin/businesses', [
            'name' => 'Unauthorized New Store',
            'slug' => 'unauth-new-store',
            'owner_name' => 'New Owner',
            'owner_email' => 'newowner@store.local',
            'owner_password' => 'SecurePass123!',
        ]);

        $response->assertStatus(403);
    }

    public function test_platform_admin_can_create_business_with_owner_and_canonical_role(): void
    {
        $platformAdmin = User::factory()->withPlatformRole()->create();

        $this->actingAs($platformAdmin);

        $payload = [
            'name' => 'Apex Electronics',
            'legal_name' => 'Apex Electronics Private Limited',
            'slug' => 'apex-electronics',
            'business_type' => 'retail',
            'email' => 'contact@apexelectronics.local',
            'phone' => '+919876543210',
            'address_line_1' => '101 Tech Boulevard',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'postal_code' => '560001',
            'country' => 'IN',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'tax_number' => '29ABCDE1234F1Z5',
            'owner_name' => 'Ramesh Kumar',
            'owner_email' => 'ramesh@apexelectronics.local',
            'owner_phone' => '+919876543210',
            'owner_password' => 'SecureOwnerPass123!',
        ];

        $response = $this->postJson('/admin/businesses', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'message' => 'Business created successfully.',
            'business' => [
                'name' => 'Apex Electronics',
                'slug' => 'apex-electronics',
                'status' => 'active',
                'owner' => [
                    'name' => 'Ramesh Kumar',
                    'email' => 'ramesh@apexelectronics.local',
                ],
            ],
        ]);

        // Verify database records
        $business = Business::where('slug', 'apex-electronics')->first();
        $this->assertNotNull($business);
        $this->assertSame(BusinessStatus::Active, $business->status);
        $this->assertEquals('Apex Electronics Private Limited', $business->legal_name);
        $this->assertEquals('INR', $business->currency);
        $this->assertEquals('Asia/Kolkata', $business->timezone);

        $owner = User::where('email', 'ramesh@apexelectronics.local')->first();
        $this->assertNotNull($owner);
        $this->assertFalse($owner->isPlatformAdmin());
        $this->assertTrue(Hash::check('SecureOwnerPass123!', $owner->password));

        $membership = BusinessMember::where('business_id', $business->id)
            ->where('user_id', $owner->id)
            ->first();
        $this->assertNotNull($membership);
        $this->assertTrue($membership->is_owner);
        $this->assertSame(MembershipStatus::Active, $membership->status);

        $this->assertEquals($business->id, $owner->business->id);

        // Verify atomic canonical owner role assignment
        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();
        $userRole = UserRole::where('user_id', $owner->id)
            ->where('business_id', $business->id)
            ->first();

        $this->assertNotNull($userRole);
        $this->assertSame($ownerRole->id, $userRole->role_id);

        // Invariant: Platform admin is NOT a member of the newly created business
        $this->assertDatabaseMissing('business_members', [
            'business_id' => $business->id,
            'user_id' => $platformAdmin->id,
        ]);

        // Audit log verified
        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'user_id' => $platformAdmin->id,
            'action' => 'business.provisioned',
            'auditable_type' => Business::class,
            'auditable_id' => $business->id,
        ]);
    }

    public function test_business_creation_rolls_back_atomically_if_role_provisioning_fails(): void
    {
        $platformAdmin = User::factory()->withPlatformRole()->create();

        // Temporarily delete canonical owner role to trigger provisioning failure inside the transaction
        Role::where('slug', 'owner')->whereNull('business_id')->delete();

        $action = app(CreateBusiness::class);

        $this->expectException(Exception::class);

        try {
            $action->execute($platformAdmin, [
                'name' => 'Rollback Test Business',
                'slug' => 'rollback-test-business',
                'owner_name' => 'Rollback Owner',
                'owner_email' => 'rollback@owner.local',
                'owner_password' => 'SecurePass123!',
            ]);
        } finally {
            // Verify atomic rollback: neither business, membership, role, nor user persisted
            $this->assertDatabaseMissing('businesses', ['slug' => 'rollback-test-business']);
            $this->assertDatabaseMissing('users', ['email' => 'rollback@owner.local']);
            $this->assertSame(0, BusinessMember::whereHas('user', fn ($q) => $q->where('email', 'rollback@owner.local'))->count());
        }
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        $platformAdmin = User::factory()->withPlatformRole()->create();
        Business::factory()->create(['slug' => 'existing-slug']);

        $this->actingAs($platformAdmin);

        $response = $this->postJson('/admin/businesses', [
            'name' => 'Another Store',
            'slug' => 'existing-slug',
            'owner_name' => 'Owner Name',
            'owner_email' => 'newowner@store.local',
            'owner_password' => 'SecurePass123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('slug');
    }

    public function test_cannot_assign_owner_who_already_belongs_to_another_business(): void
    {
        $platformAdmin = User::factory()->withPlatformRole()->create();
        $existingBusiness = Business::factory()->create();
        $existingOwner = User::factory()->create(['email' => 'existing@adoqa.local']);

        BusinessMember::create([
            'business_id' => $existingBusiness->id,
            'user_id' => $existingOwner->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
        ]);

        $this->actingAs($platformAdmin);

        // Try to create second business assigning existingOwner
        $response = $this->postJson('/admin/businesses', [
            'name' => 'Second Business',
            'slug' => 'second-business',
            'owner_name' => 'Existing Person',
            'owner_email' => 'existing@adoqa.local',
            'owner_password' => 'NewPassword123!',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('owner_email');
    }

    public function test_cannot_use_owner_phone_already_registered_to_another_business_member(): void
    {
        $platformAdmin = User::factory()->withPlatformRole()->create();
        $existingBusiness = Business::factory()->create();
        $existingOwner = User::factory()->create([
            'email' => 'phoneowner@adoqa.local',
            'phone' => '+919999988888',
        ]);

        BusinessMember::create([
            'business_id' => $existingBusiness->id,
            'user_id' => $existingOwner->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
        ]);

        $action = app(CreateBusiness::class);

        $this->expectException(ValidationException::class);

        $action->execute($platformAdmin, [
            'name' => 'Phone Collision Business',
            'slug' => 'phone-collision-biz',
            'owner_name' => 'Duplicate Phone User',
            'owner_email' => 'newemail@different.local',
            'owner_phone' => '+919999988888',
            'owner_password' => 'SecurePass123!',
        ]);
    }

    public function test_actor_cannot_override_relationships_through_request_payload(): void
    {
        $platformAdmin = User::factory()->withPlatformRole()->create();
        $otherBusiness = Business::factory()->create();
        $otherUser = User::factory()->create();

        $action = app(CreateBusiness::class);

        $business = $action->execute($platformAdmin, [
            'name' => 'Tamper Test Store',
            'slug' => 'tamper-test-store',
            'owner_name' => 'Real Owner',
            'owner_email' => 'realowner@tamper.local',
            'owner_password' => 'SecurePass123!',
            'business_id' => $otherBusiness->id, // Malicious override attempt
            'user_id' => $otherUser->id,         // Malicious override attempt
            'is_platform_admin' => true,         // Malicious privilege escalation attempt
        ]);

        // Verified: The newly created business has its own ULID, not the injected ID
        $this->assertNotEquals($otherBusiness->id, $business->id);

        // Verified: Owner is the newly created owner user, not otherUser
        $this->assertNotEquals($otherUser->id, $business->owner->id);
        $this->assertEquals('realowner@tamper.local', $business->owner->email);
        $this->assertFalse($business->owner->isPlatformAdmin());
    }
}
