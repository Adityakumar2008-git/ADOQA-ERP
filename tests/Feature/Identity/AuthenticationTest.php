<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('target@adoqa.local|127.0.0.1');
    }

    public function test_login_screen_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sign in to ADOQA ERP');
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
    }

    public function test_active_user_can_authenticate_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@adoqa.local',
            'password' => Hash::make('SecretPass123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'staff@adoqa.local',
            'password' => 'SecretPass123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect('/');

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }

    public function test_platform_admin_is_redirected_to_admin_surface_on_login(): void
    {
        $admin = User::factory()->platformAdmin()->create([
            'email' => 'master@adoqa.local',
            'password' => Hash::make('AdminSecret123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'master@adoqa.local',
            'password' => 'AdminSecret123!',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect('/admin');
    }

    public function test_suspended_user_cannot_authenticate(): void
    {
        User::factory()->suspended()->create([
            'email' => 'suspended@adoqa.local',
            'password' => Hash::make('SecretPass123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'suspended@adoqa.local',
            'password' => 'SecretPass123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@adoqa.local',
            'password' => Hash::make('SecretPass123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@adoqa.local',
            'password' => 'SecretPass123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_invalid_password_returns_generic_error_and_does_not_reveal_account_state(): void
    {
        User::factory()->create([
            'email' => 'valid@adoqa.local',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = $this->post('/login', [
            'email' => 'valid@adoqa.local',
            'password' => 'WrongPassword!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');

        $errorMessageWithValidEmail = session('errors')->first('email');

        // Check non-existent user error message
        $responseNonExistent = $this->post('/login', [
            'email' => 'nonexistent@adoqa.local',
            'password' => 'AnyPassword!',
        ]);

        $this->assertGuest();
        $responseNonExistent->assertSessionHasErrors('email');
        $errorMessageWithNonExistentEmail = session('errors')->first('email');

        // Both must return identical generic error
        $this->assertSame($errorMessageWithValidEmail, $errorMessageWithNonExistentEmail);
    }

    public function test_brute_force_rate_limiting_blocks_repeated_failed_attempts(): void
    {
        $user = User::factory()->create([
            'email' => 'target@adoqa.local',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        // 5 attempts allowed
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => 'target@adoqa.local',
                'password' => 'WrongPassword',
            ]);
        }

        // 6th attempt must be throttled
        $response = $this->post('/login', [
            'email' => 'target@adoqa.local',
            'password' => 'CorrectPassword123!',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
        $error = session('errors')->first('email');
        $this->assertStringContainsString('Too many login attempts', $error);
    }

    public function test_authenticated_user_can_logout_and_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_public_registration_is_strictly_disabled(): void
    {
        $getResponse = $this->get('/register');
        $getResponse->assertStatus(404);

        $postResponse = $this->post('/register', [
            'name' => 'Attacker',
            'email' => 'attacker@adoqa.local',
            'password' => 'Password123!',
        ]);
        $postResponse->assertStatus(404);
    }
}
