<?php

declare(strict_types=1);

namespace Tests\Feature\Brand;

use App\Enums\BusinessStatus;
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

class BrandingFoundationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_brand_configuration_has_exact_palette_tokens(): void
    {
        $this->assertEquals('ADOQA ERP', config('brand.name'));
        $this->assertEquals('#FFDE2D', config('brand.colors.yellow'));
        $this->assertEquals('#7F7F7F', config('brand.colors.grey'));
        $this->assertEquals('#22B14C', config('brand.colors.green'));
        $this->assertEquals('#B5E61D', config('brand.colors.lime'));
        $this->assertEquals('#99D9EA', config('brand.colors.sky'));
        $this->assertEquals('#FFFFFF', config('brand.colors.white'));
    }

    public function test_official_logo_asset_exists_in_public(): void
    {
        $this->assertFileExists(public_path('images/adoqa-logo.png'));
        $this->assertGreaterThan(0, filesize(public_path('images/adoqa-logo.png')));
    }

    public function test_login_page_renders_official_logo_and_brand_elements(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('images/adoqa-logo.png');
        $response->assertSee('Sign in to ADOQA ERP');
    }

    public function test_admin_layout_renders_official_logo(): void
    {
        $admin = User::factory()->withPlatformRole()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('images/adoqa-logo.png');
        $response->assertSee('ADOQA');
        $response->assertSee('Master Admin');
    }

    public function test_owner_layout_renders_official_logo(): void
    {
        $business = Business::factory()->create(['status' => BusinessStatus::Active]);
        $owner = User::factory()->create(['status' => UserStatus::Active, 'is_platform_admin' => false]);

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'is_owner' => true,
            'status' => MembershipStatus::Active,
            'joined_at' => now(),
        ]);

        $role = Role::where('slug', 'owner')->whereNull('business_id')->firstOrFail();

        UserRole::create([
            'user_id' => $owner->id,
            'role_id' => $role->id,
            'business_id' => $business->id,
        ]);

        $response = $this->actingAs($owner)->get(route('owner.dashboard'));

        $response->assertOk();
        $response->assertSee('images/adoqa-logo.png');
        $response->assertSee('ADOQA');
        $response->assertSee('Owner ERP');
    }

    public function test_css_contains_shared_brand_tokens_and_glass_utilities(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('--color-adoqa-yellow: #FFDE2D;', $css);
        $this->assertStringContainsString('--color-adoqa-grey: #7F7F7F;', $css);
        $this->assertStringContainsString('--color-adoqa-green: #22B14C;', $css);
        $this->assertStringContainsString('--color-adoqa-lime: #B5E61D;', $css);
        $this->assertStringContainsString('--color-adoqa-sky: #99D9EA;', $css);
        $this->assertStringContainsString('.adoqa-glass', $css);
        $this->assertStringContainsString('.adoqa-glass-card', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
    }
}
