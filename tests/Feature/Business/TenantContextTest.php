<?php

namespace Tests\Feature\Business;

use App\Enums\MembershipStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TenantContextTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authenticated_user_resolves_their_authoritative_tenant(): void
    {
        $business = Business::factory()->create(['name' => 'Original Authorized Store']);
        $user = User::factory()->create();

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
        ]);

        $this->actingAs($user);

        $response = $this->get('/tenant/current');

        $response->assertStatus(200);
        $response->assertJson([
            'tenant_id' => $business->id,
            'tenant_name' => 'Original Authorized Store',
            'tenant_slug' => $business->slug,
        ]);
    }

    public function test_user_cannot_access_another_business_by_url_query_tampering(): void
    {
        $businessA = Business::factory()->create(['name' => 'Store Alpha']);
        $businessB = Business::factory()->create(['name' => 'Store Beta - Target']);

        $user = User::factory()->create();

        BusinessMember::create([
            'business_id' => $businessA->id,
            'user_id' => $user->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
        ]);

        $this->actingAs($user);

        // Attacker attempts to provide business_id of Store Beta via URL query
        $response = $this->get('/tenant/current?business_id='.$businessB->id);

        $response->assertStatus(200);
        // Server MUST return Store Alpha, ignoring client-provided business_id
        $response->assertJson([
            'tenant_id' => $businessA->id,
            'tenant_name' => 'Store Alpha',
        ]);
        $response->assertJsonMissing([
            'tenant_id' => $businessB->id,
            'tenant_name' => 'Store Beta - Target',
        ]);
    }

    public function test_user_cannot_access_another_business_by_header_tampering(): void
    {
        $businessA = Business::factory()->create(['name' => 'Store Alpha']);
        $businessB = Business::factory()->create(['name' => 'Store Beta - Target']);

        $user = User::factory()->create();

        BusinessMember::create([
            'business_id' => $businessA->id,
            'user_id' => $user->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
        ]);

        $this->actingAs($user);

        // Attacker attempts to provide X-Business-ID or X-Tenant-ID header
        $response = $this->withHeaders([
            'X-Business-ID' => $businessB->id,
            'X-Tenant-ID' => $businessB->id,
        ])->get('/tenant/current');

        $response->assertStatus(200);
        // Server MUST return Store Alpha
        $response->assertJson([
            'tenant_id' => $businessA->id,
            'tenant_name' => 'Store Alpha',
        ]);
        $response->assertJsonMissing([
            'tenant_id' => $businessB->id,
        ]);
    }

    public function test_user_cannot_access_suspended_business(): void
    {
        $suspendedBusiness = Business::factory()->suspended()->create();
        $user = User::factory()->create();

        BusinessMember::create([
            'business_id' => $suspendedBusiness->id,
            'user_id' => $user->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
        ]);

        $this->actingAs($user);

        $response = $this->get('/tenant/current');

        $response->assertStatus(403);
    }

    public function test_user_with_inactive_membership_is_rejected(): void
    {
        $business = Business::factory()->create();
        $user = User::factory()->create();

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'is_owner' => false,
            'status' => MembershipStatus::Inactive,
        ]);

        $this->actingAs($user);

        $response = $this->get('/tenant/current');

        $response->assertStatus(403);
    }

    public function test_user_without_any_business_membership_is_rejected(): void
    {
        $unaffiliatedUser = User::factory()->create();

        $this->actingAs($unaffiliatedUser);

        $response = $this->get('/tenant/current');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_request_is_redirected_or_unauthorized(): void
    {
        $response = $this->get('/tenant/current');

        $response->assertRedirect('/login');
    }
}
