<?php

namespace App\Actions\Business;

use App\Actions\Catalogue\SeedDefaultBusinessUnits;
use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\Auth\AuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CreateBusiness
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Authoritatively provision a new ADOQA business, owner identity, membership, and RBAC role.
     * Can only be executed by a platform administrator possessing the 'businesses.create' permission.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $actor, array $data): Business
    {
        // 1. Authorize actor strictly via platform RBAC permission
        if ($this->authorizationService->cannot($actor, 'businesses.create')) {
            throw new AuthorizationException('Only platform administrators with [businesses.create] permission can provision a business.');
        }

        // 2. Business slug validation
        $slug = Str::slug($data['slug'] ?? $data['name']);

        if (Business::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'A business with this code or slug already exists.',
            ]);
        }

        // 3. Owner email & single-business policy validation
        $ownerEmail = strtolower(trim($data['owner_email']));
        $existingUser = User::query()->where('email', $ownerEmail)->first();

        if ($existingUser && $existingUser->membership()->exists()) {
            throw ValidationException::withMessages([
                'owner_email' => 'This user account already belongs to another business.',
            ]);
        }

        // 4. Owner phone duplicate validation across memberships
        if (! empty($data['owner_phone'])) {
            $ownerPhone = trim($data['owner_phone']);
            $existingPhoneUser = User::query()->where('phone', $ownerPhone)->first();
            if ($existingPhoneUser && $existingPhoneUser->membership()->exists()) {
                throw ValidationException::withMessages([
                    'owner_phone' => 'This phone number is already registered to a member of another business.',
                ]);
            }
        }

        return DB::transaction(function () use ($actor, $data, $slug, $ownerEmail, $existingUser) {
            // 5. Resolve canonical owner role
            $ownerRole = Role::query()
                ->where('slug', 'owner')
                ->whereNull('business_id')
                ->first();

            if (! $ownerRole) {
                throw new RuntimeException('Canonical business owner role has not been seeded.');
            }

            // 6. Provision owner user (never platform admin)
            $owner = $existingUser ?? User::create([
                'name' => trim($data['owner_name']),
                'email' => $ownerEmail,
                'phone' => isset($data['owner_phone']) ? trim($data['owner_phone']) : null,
                'password' => Hash::make($data['owner_password']),
                'status' => UserStatus::Active,
                'is_platform_admin' => false,
            ]);

            // 7. Provision Business in active status
            $business = Business::create([
                'name' => trim($data['name']),
                'legal_name' => isset($data['legal_name']) ? trim($data['legal_name']) : null,
                'slug' => $slug,
                'business_type' => $data['business_type'] ?? 'retail',
                'email' => isset($data['email']) ? strtolower(trim($data['email'])) : $ownerEmail,
                'phone' => $data['phone'] ?? ($data['owner_phone'] ?? null),
                'address_line_1' => $data['address_line_1'] ?? null,
                'address_line_2' => $data['address_line_2'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? 'IN',
                'currency' => strtoupper($data['currency'] ?? 'INR'),
                'timezone' => $data['timezone'] ?? 'Asia/Kolkata',
                'tax_number' => $data['tax_number'] ?? null,
                'status' => BusinessStatus::Active,
            ]);

            // 8. Provision owner BusinessMember (Master Admin is NEVER added as a member)
            BusinessMember::create([
                'business_id' => $business->id,
                'user_id' => $owner->id,
                'is_owner' => true,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
            ]);

            // 9. Assign canonical owner role to owner
            UserRole::create([
                'user_id' => $owner->id,
                'role_id' => $ownerRole->id,
                'business_id' => $business->id,
            ]);

            // 10. Provision canonical catalogue units
            (new SeedDefaultBusinessUnits)->execute($business);

            // 11. Record immutable audit log
            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'business.provisioned',
                'auditable_type' => Business::class,
                'auditable_id' => $business->id,
                'payload' => [
                    'name' => $business->name,
                    'slug' => $business->slug,
                    'owner_id' => $owner->id,
                    'owner_email' => $owner->email,
                    'status' => $business->status->value,
                ],
            ]);

            // 11. Invalidate memoized permissions
            $this->authorizationService->clearMemoized();

            return $business->load(['members.user', 'userRoles.role']);
        });
    }
}
