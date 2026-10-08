<?php

namespace Tests\Feature\Auth;

use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthenticatedSessionSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected Business $business;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);

        $this->business = Business::factory()->create();
        $this->owner = User::factory()->create([
            'status' => UserStatus::Active,
            'is_platform_admin' => false,
        ]);

        BusinessMember::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $ownerRole = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();
        UserRole::create([
            'user_id' => $this->owner->id,
            'role_id' => $ownerRole->id,
            'business_id' => $this->business->id,
        ]);
    }

    public function test_authenticated_owner_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->owner)->get('/owner/dashboard');

        $response->assertOk();
        $response->assertSee($this->business->name);
    }

    public function test_logout_invalidates_session_and_regenerates_token(): void
    {
        $this->actingAs($this->owner);
        $this->assertAuthenticatedAs($this->owner);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_protected_owner_route_inaccessible_after_logout(): void
    {
        // 1. Authenticate and access dashboard
        $response1 = $this->actingAs($this->owner)->get('/owner/dashboard');
        $response1->assertOk();

        // 2. Perform logout
        $responseLogout = $this->post('/logout');
        $responseLogout->assertRedirect('/login');
        $this->assertGuest();

        // 3. Immediately attempt accessing protected route with the now-unauthenticated session
        $response2 = $this->get('/owner/dashboard');
        $response2->assertRedirect('/login');
    }

    public function test_protected_admin_route_redirects_unauthenticated_user_to_login(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_owner_protected_responses_contain_strict_no_cache_no_store_headers(): void
    {
        $response = $this->actingAs($this->owner)->get('/owner/dashboard');

        $response->assertOk();
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
    }

    public function test_admin_protected_responses_contain_strict_no_cache_no_store_headers(): void
    {
        $admin = User::factory()->withPlatformRole()->create();

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
    }

    public function test_staff_app_protected_responses_contain_strict_no_cache_no_store_headers(): void
    {
        $response = $this->actingAs($this->owner)->get('/app');

        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('must-revalidate', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
    }

    public function test_logout_endpoint_rejects_get_method(): void
    {
        $this->actingAs($this->owner);

        // Destructive logout via GET is strictly prohibited
        $response = $this->get('/logout');
        $response->assertStatus(405);
    }

    public function test_direct_navigation_to_owner_dashboard_after_logout_redirects_to_login(): void
    {
        // Simulate login
        $this->post('/login', [
            'email' => $this->owner->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($this->owner);

        // Sign out
        $this->post('/logout');
        $this->assertGuest();

        // Simulate typing direct URL in browser
        $response = $this->get('/owner/dashboard');
        $response->assertRedirect('/login');

        // Also test sub-resource item
        $responseItems = $this->get('/owner/catalogue/items');
        $responseItems->assertRedirect('/login');
    }

    public function test_direct_navigation_to_admin_after_logout_redirects_to_login(): void
    {
        $admin = User::factory()->withPlatformRole()->create([
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);

        // Sign out
        $this->post('/logout');
        $this->assertGuest();

        // Simulate typing direct URL in browser
        $response = $this->get('/admin');
        $response->assertRedirect('/login');
    }

    public function test_owner_and_admin_layouts_render_bfcache_security_safeguard(): void
    {
        $responseOwner = $this->actingAs($this->owner)->get('/owner/dashboard');
        $responseOwner->assertOk();
        $responseOwner->assertSee('window.addEventListener(\'pageshow\'', false);
        $responseOwner->assertSee('event.persisted', false);
        $responseOwner->assertSee('window.location.reload()', false);

        $admin = User::factory()->withPlatformRole()->create();
        $responseAdmin = $this->actingAs($admin)->get('/admin');
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('window.addEventListener(\'pageshow\'', false);
        $responseAdmin->assertSee('event.persisted', false);
        $responseAdmin->assertSee('window.location.reload()', false);
    }
}
