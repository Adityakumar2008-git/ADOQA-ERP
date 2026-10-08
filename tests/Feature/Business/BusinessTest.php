<?php

namespace Tests\Feature\Business;

use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    use DatabaseTransactions;

    public function test_business_uses_ulid_primary_key_on_postgresql(): void
    {
        $business = Business::factory()->create([
            'slug' => 'test-store-ulid',
        ]);

        $this->assertNotEmpty($business->id);
        $this->assertTrue(Str::isUlid($business->id), "Expected business ID '{$business->id}' to be a valid ULID.");
        $this->assertEquals(26, strlen($business->id));

        $retrieved = Business::find($business->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals('test-store-ulid', $retrieved->slug);
    }

    public function test_business_slug_uniqueness_is_enforced_by_database_constraint(): void
    {
        Business::factory()->create([
            'slug' => 'unique-brand-slug',
        ]);

        $this->expectException(QueryException::class);

        Business::factory()->create([
            'slug' => 'unique-brand-slug',
        ]);
    }

    public function test_business_status_cast_and_helpers(): void
    {
        $activeBusiness = Business::factory()->create();
        $this->assertSame(BusinessStatus::Active, $activeBusiness->status);
        $this->assertTrue($activeBusiness->isActive());
        $this->assertFalse($activeBusiness->isSuspended());

        $suspendedBusiness = Business::factory()->suspended()->create();
        $this->assertSame(BusinessStatus::Suspended, $suspendedBusiness->status);
        $this->assertTrue($suspendedBusiness->isSuspended());
        $this->assertFalse($suspendedBusiness->isActive());
    }

    public function test_business_member_uses_ulid_and_has_valid_relationships(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();

        $member = BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $this->assertTrue(Str::isUlid($member->id));
        $this->assertTrue($member->isOwner());
        $this->assertTrue($member->isActive());

        $this->assertEquals($business->id, $member->business->id);
        $this->assertEquals($user->id, $member->user->id);

        $this->assertEquals($business->id, $user->business->id);
        $this->assertTrue($user->belongsToBusiness($business));
    }

    public function test_one_business_per_ordinary_account_enforced_by_database_constraint(): void
    {
        $businessA = Business::factory()->create(['slug' => 'store-alpha']);
        $businessB = Business::factory()->create(['slug' => 'store-beta']);
        $user = User::factory()->create();

        BusinessMember::create([
            'business_id' => $businessA->id,
            'user_id' => $user->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
        ]);

        // Attempting to attach the same user to a second business must fail at PostgreSQL constraint level
        $this->expectException(QueryException::class);

        BusinessMember::create([
            'business_id' => $businessB->id,
            'user_id' => $user->id,
            'is_owner' => false,
            'status' => MembershipStatus::Active,
        ]);
    }

    public function test_deleting_business_cascades_and_removes_memberships(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();

        $member = BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
        ]);

        $memberId = $member->id;

        $business->delete();

        $this->assertNull(BusinessMember::find($memberId));
        // The user remains intact
        $this->assertNotNull(User::find($user->id));
    }
}
