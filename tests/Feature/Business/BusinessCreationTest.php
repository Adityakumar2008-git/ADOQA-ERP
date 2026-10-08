<?php

namespace Tests\Feature\Business;

use App\Actions\Business\CreateBusiness;
use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BusinessCreationTest extends TestCase
{
    use DatabaseTransactions;

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

    public function test_platform_admin_can_create_business_with_owner(): void
    {
        $platformAdmin = User::factory()->platformAdmin()->create();

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
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        $platformAdmin = User::factory()->platformAdmin()->create();
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
        $platformAdmin = User::factory()->platformAdmin()->create();
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
}
