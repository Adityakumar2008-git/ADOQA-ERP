<?php

namespace App\Actions\Tenancy;

use App\Enums\PermissionOverrideState;
use App\Models\Business;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SyncBusinessRolePermissionOverrides
{
    public function __construct(
        protected SetBusinessRolePermissionOverride $setOverrideAction,
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Batch update permission overrides for a business-scoped role.
     *
     * @param  array<string, string|PermissionOverrideState>  $overrides  [ 'permission_id_or_slug' => 'allow'|'deny'|'inherit' ]
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function execute(
        User $actor,
        Business $business,
        Role $role,
        array $overrides
    ): Collection {
        return DB::transaction(function () use ($actor, $business, $role, $overrides) {
            $results = collect();

            foreach ($overrides as $permIdentifier => $state) {
                $permission = Permission::where('id', $permIdentifier)
                    ->orWhere('slug', $permIdentifier)
                    ->first();

                if (! $permission) {
                    throw new DomainException("Unknown permission identifier [{$permIdentifier}].");
                }

                $overrideState = is_string($state) ? PermissionOverrideState::from($state) : $state;

                $result = $this->setOverrideAction->execute(
                    actor: $actor,
                    business: $business,
                    role: $role,
                    permission: $permission,
                    state: $overrideState
                );

                if ($result) {
                    $results->push($result);
                }
            }

            return $results;
        });
    }
}
