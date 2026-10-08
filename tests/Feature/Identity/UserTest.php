<?php

namespace Tests\Feature\Identity;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_uses_ulid_primary_key_on_postgresql(): void
    {
        $user = User::factory()->create([
            'email' => 'test-ulid@adoqa.local',
        ]);

        $this->assertNotEmpty($user->id);
        $this->assertTrue(Str::isUlid($user->id), "Expected user ID '{$user->id}' to be a valid ULID.");
        $this->assertEquals(26, strlen($user->id));

        $retrieved = User::find($user->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals('test-ulid@adoqa.local', $retrieved->email);
    }

    public function test_email_uniqueness_is_enforced_by_database_constraint(): void
    {
        User::factory()->create([
            'email' => 'duplicate@adoqa.local',
        ]);

        $this->expectException(QueryException::class);

        User::factory()->create([
            'email' => 'duplicate@adoqa.local',
        ]);
    }

    public function test_default_status_is_active(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isSuspended());
        $this->assertFalse($user->isPlatformAdmin());
    }

    public function test_suspended_and_inactive_factory_states(): void
    {
        $suspendedUser = User::factory()->suspended()->create();
        $this->assertSame(UserStatus::Suspended, $suspendedUser->status);
        $this->assertTrue($suspendedUser->isSuspended());
        $this->assertFalse($suspendedUser->isActive());

        $inactiveUser = User::factory()->inactive()->create();
        $this->assertSame(UserStatus::Inactive, $inactiveUser->status);
        $this->assertFalse($inactiveUser->isActive());
    }

    public function test_platform_admin_factory_state(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->assertTrue($admin->isPlatformAdmin());
    }
}
