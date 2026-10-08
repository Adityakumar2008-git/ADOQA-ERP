<?php

namespace App\Actions\Rbac;

use App\Enums\RoleScope;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateBusinessRole
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Create a new custom role within a specific business tenant.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws DomainException
     * @throws ValidationException
     */
    public function execute(User $actor, Business $business, array $data): Role
    {
        // 1. Authorization: Actor must have staff.manage permission in current business
        if ($this->authorizationService->cannot($actor, 'staff.manage', $business)) {
            throw new AuthorizationException('Unauthorized to create roles in this business.');
        }

        // 2. Reject platform scope creation attempts by business users
        if (isset($data['scope']) && ($data['scope'] === RoleScope::Platform->value || $data['scope'] === RoleScope::Platform)) {
            throw new DomainException('Businesses cannot create platform-scoped roles.');
        }

        $name = trim($data['name']);
        $slug = Str::slug($data['slug'] ?? $name);

        // 3. Unique slug check within this specific business
        if (Role::where('business_id', $business->id)->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A custom role with this name or code already exists in this business.',
            ]);
        }

        // 4. Resolve permissions and validate scope & privilege escalation
        $permissions = collect();
        if (! empty($data['permissions'])) {
            $permissions = Permission::whereIn('id', $data['permissions'])
                ->orWhereIn('slug', $data['permissions'])
                ->get();

            foreach ($permissions as $perm) {
                if ($perm->scope !== RoleScope::Business) {
                    throw new DomainException("Cannot attach platform permission [{$perm->slug}] to a business role.");
                }

                // Privilege escalation protection: Actor cannot grant permissions they do not possess
                if ($this->authorizationService->cannot($actor, $perm->slug, $business)) {
                    throw new DomainException("Privilege escalation blocked: Cannot grant permission [{$perm->slug}] which actor does not possess.");
                }
            }
        }

        return DB::transaction(function () use ($actor, $business, $name, $slug, $data, $permissions) {
            $role = Role::create([
                'business_id' => $business->id,
                'name' => $name,
                'slug' => $slug,
                'scope' => RoleScope::Business,
                'description' => isset($data['description']) ? trim($data['description']) : null,
                'is_system' => false,
            ]);

            if ($permissions->isNotEmpty()) {
                $role->permissions()->attach($permissions->pluck('id'));
            }

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'role.created',
                'auditable_type' => Role::class,
                'auditable_id' => $role->id,
                'payload' => [
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'permissions' => $permissions->pluck('slug')->toArray(),
                ],
            ]);

            $this->authorizationService->clearMemoized();

            return $role->load('permissions');
        });
    }
}
