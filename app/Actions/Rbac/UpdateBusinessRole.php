<?php

namespace App\Actions\Rbac;

use App\Models\AuditLog;
use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UpdateBusinessRole
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Update an existing custom business role.
     * System roles cannot be casually modified or converted.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function execute(User $actor, Business $business, Role $role, array $data): Role
    {
        // 1. Authorization: Actor must have staff.manage in current business
        if ($this->authorizationService->cannot($actor, 'staff.manage', $business)) {
            throw new AuthorizationException('Unauthorized to update roles in this business.');
        }

        // 2. Canonical system role protection
        if ($role->is_system) {
            throw new DomainException("Canonical system role [{$role->slug}] cannot be renamed, converted, or modified.");
        }

        // 3. Tenant boundary protection: Cannot modify role owned by another tenant
        if ($role->business_id !== $business->id) {
            throw new DomainException("Cannot modify role belonging to business [{$role->business_id}] from business [{$business->id}].");
        }

        // 4. Scope immutability
        if (isset($data['scope']) && $data['scope'] !== $role->scope->value) {
            throw new DomainException('Role scope cannot be changed.');
        }

        return DB::transaction(function () use ($actor, $business, $role, $data) {
            $oldValues = [
                'name' => $role->name,
                'description' => $role->description,
            ];

            $role->update([
                'name' => isset($data['name']) ? trim($data['name']) : $role->name,
                'description' => array_key_exists('description', $data) ? $data['description'] : $role->description,
            ]);

            AuditLog::create([
                'business_id' => $business->id,
                'user_id' => $actor->id,
                'action' => 'role.updated',
                'auditable_type' => Role::class,
                'auditable_id' => $role->id,
                'payload' => [
                    'old' => $oldValues,
                    'new' => [
                        'name' => $role->name,
                        'description' => $role->description,
                    ],
                ],
            ]);

            return $role;
        });
    }
}
