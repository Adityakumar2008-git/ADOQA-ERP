<?php

namespace App\Actions\Business;

use App\Enums\BusinessStatus;
use App\Enums\MembershipStatus;
use App\Enums\UserStatus;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateBusiness
{
    /**
     * Create a new business and initialize its owner membership.
     * Can only be executed by a platform administrator.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $actor, array $data): Business
    {
        if (! $actor->isPlatformAdmin()) {
            throw new AuthorizationException('Only platform administrators can create a business.');
        }

        $slug = Str::slug($data['slug'] ?? $data['name']);

        if (Business::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'slug' => 'A business with this code or slug already exists.',
            ]);
        }

        $ownerEmail = strtolower(trim($data['owner_email']));

        $existingUser = User::query()->where('email', $ownerEmail)->first();

        if ($existingUser && $existingUser->membership()->exists()) {
            throw ValidationException::withMessages([
                'owner_email' => 'This user account already belongs to another business.',
            ]);
        }

        return DB::transaction(function () use ($data, $slug, $ownerEmail, $existingUser) {
            $owner = $existingUser ?? User::create([
                'name' => trim($data['owner_name']),
                'email' => $ownerEmail,
                'phone' => $data['owner_phone'] ?? null,
                'password' => Hash::make($data['owner_password']),
                'status' => UserStatus::Active,
                'is_platform_admin' => false,
            ]);

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

            BusinessMember::create([
                'business_id' => $business->id,
                'user_id' => $owner->id,
                'is_owner' => true,
                'status' => MembershipStatus::Active,
                'joined_at' => now(),
            ]);

            return $business->load(['members.user']);
        });
    }
}
