<?php

namespace Tests\Feature\Admin;

use App\Models\Business;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessLogoUploadTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->admin = User::factory()->withPlatformRole()->create();
        Storage::fake('public');
    }

    public function test_admin_can_provision_business_with_valid_logo(): void
    {
        $logo = UploadedFile::fake()->image('company_logo.png', 300, 300);

        $payload = [
            'name' => 'Logo Test Retail',
            'legal_name' => 'Logo Test Retail Pvt Ltd',
            'slug' => 'logo-test-retail',
            'business_type' => 'retail',
            'email' => 'contact@logotest.local',
            'phone' => '+919876543210',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'country' => 'IN',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'tax_number' => '27ABCDE1234F1Z5',
            'owner_name' => 'John Retailer',
            'owner_email' => 'john@logotest.local',
            'owner_phone' => '+919876543211',
            'owner_password' => 'SecurePass123!',
            'logo' => $logo,
        ];

        $response = $this->actingAs($this->admin)->post('/admin/businesses', $payload);

        $business = Business::where('slug', 'logo-test-retail')->firstOrFail();
        $response->assertRedirect(route('admin.businesses.show', $business));

        // 1. Assert file exists in public storage
        $this->assertNotNull($business->logo_path);
        Storage::disk('public')->assertExists($business->logo_path);

        // 2. Assert logo_url accessor
        $this->assertStringContainsString('/storage/logos/', $business->logo_url);

        // 3. Assert logo renders on business show page
        $showResponse = $this->actingAs($this->admin)->get(route('admin.businesses.show', $business));
        $showResponse->assertOk();
        $showResponse->assertSee($business->logo_url);

        // 4. Assert logo renders on businesses index page
        $indexResponse = $this->actingAs($this->admin)->get(route('admin.businesses.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee($business->logo_url);

        // 5. Assert logo renders for the owner on owner dashboard
        $owner = $business->owner;
        $this->assertNotNull($owner);
        $ownerDashboardResponse = $this->actingAs($owner)->get(route('owner.dashboard'));
        $ownerDashboardResponse->assertOk();
        $ownerDashboardResponse->assertSee($business->logo_url);
    }

    public function test_admin_can_provision_business_without_logo(): void
    {
        $payload = [
            'name' => 'No Logo Store',
            'slug' => 'no-logo-store',
            'owner_name' => 'Alice Plain',
            'owner_email' => 'alice@nologo.local',
            'owner_password' => 'SecurePass123!',
        ];

        $response = $this->actingAs($this->admin)->post('/admin/businesses', $payload);

        $business = Business::where('slug', 'no-logo-store')->firstOrFail();
        $response->assertRedirect(route('admin.businesses.show', $business));

        $this->assertNull($business->logo_path);
        $this->assertNull($business->logo_url);

        // UI renders fallback avatar initial 'N'
        $showResponse = $this->actingAs($this->admin)->get(route('admin.businesses.show', $business));
        $showResponse->assertOk();
        $showResponse->assertSee('N');
    }

    public function test_invalid_file_type_is_rejected(): void
    {
        $fakeScript = UploadedFile::fake()->create('malicious.php', 100, 'text/x-php');

        $payload = [
            'name' => 'Invalid File Store',
            'slug' => 'invalid-file-store',
            'owner_name' => 'Bad Actor',
            'owner_email' => 'bad@invalid.local',
            'owner_password' => 'SecurePass123!',
            'logo' => $fakeScript,
        ];

        $response = $this->actingAs($this->admin)->post('/admin/businesses', $payload);
        $response->assertSessionHasErrors('logo');

        $this->assertDatabaseMissing('businesses', ['slug' => 'invalid-file-store']);
    }

    public function test_oversized_logo_file_is_rejected(): void
    {
        // 3 MB image exceeds the 2048 KB limit
        $oversizedLogo = UploadedFile::fake()->image('heavy_logo.jpg')->size(3000);

        $payload = [
            'name' => 'Heavy Logo Store',
            'slug' => 'heavy-logo-store',
            'owner_name' => 'Heavy Owner',
            'owner_email' => 'heavy@heavy.local',
            'owner_password' => 'SecurePass123!',
            'logo' => $oversizedLogo,
        ];

        $response = $this->actingAs($this->admin)->post('/admin/businesses', $payload);
        $response->assertSessionHasErrors('logo');

        $this->assertDatabaseMissing('businesses', ['slug' => 'heavy-logo-store']);
    }

    public function test_logo_replacement_deletes_old_file_and_stores_new_one(): void
    {
        // 1. Create business with original logo
        $initialLogo = UploadedFile::fake()->image('initial.png');
        $initialPath = $initialLogo->store('logos', 'public');

        $business = Business::factory()->create([
            'logo_path' => $initialPath,
        ]);
        Storage::disk('public')->assertExists($initialPath);

        // Unrelated file should NOT be touched
        $unrelatedPath = UploadedFile::fake()->image('unrelated.png')->store('logos', 'public');
        Storage::disk('public')->assertExists($unrelatedPath);

        // 2. Update with new logo
        $newLogo = UploadedFile::fake()->image('new_logo.webp');

        $response = $this->actingAs($this->admin)->put(route('admin.businesses.update', $business), [
            'name' => $business->name,
            'logo' => $newLogo,
        ]);
        $response->assertRedirect(route('admin.businesses.show', $business));

        $business->refresh();

        // Old initial logo must be deleted
        Storage::disk('public')->assertMissing($initialPath);

        // New logo must exist
        $this->assertNotNull($business->logo_path);
        $this->assertNotEquals($initialPath, $business->logo_path);
        Storage::disk('public')->assertExists($business->logo_path);

        // Unrelated file remains untouched
        Storage::disk('public')->assertExists($unrelatedPath);
    }

    public function test_logo_removal_cleans_up_file_and_clears_database_field(): void
    {
        $initialLogo = UploadedFile::fake()->image('to_remove.jpg');
        $initialPath = $initialLogo->store('logos', 'public');

        $business = Business::factory()->create([
            'logo_path' => $initialPath,
        ]);
        Storage::disk('public')->assertExists($initialPath);

        // Submit remove_logo checkbox
        $response = $this->actingAs($this->admin)->put(route('admin.businesses.update', $business), [
            'name' => $business->name,
            'remove_logo' => true,
        ]);
        $response->assertRedirect(route('admin.businesses.show', $business));

        $business->refresh();

        // Stored file deleted and path is null
        Storage::disk('public')->assertMissing($initialPath);
        $this->assertNull($business->logo_path);
        $this->assertNull($business->logo_url);
    }

    public function test_unauthorized_user_cannot_update_business_logo(): void
    {
        $business = Business::factory()->create();
        $ordinaryUser = User::factory()->create();

        $response = $this->actingAs($ordinaryUser)->put(route('admin.businesses.update', $business), [
            'name' => 'Hacked Name',
        ]);
        $response->assertStatus(403);
    }
}
