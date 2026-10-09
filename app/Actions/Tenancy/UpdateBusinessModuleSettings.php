<?php

namespace App\Actions\Tenancy;

use App\Enums\BusinessModule;
use App\Models\AuditLog;
use App\Models\Business;
use App\Models\BusinessModuleSetting;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UpdateBusinessModuleSettings
{
    public function __construct(
        protected AuthorizationService $authorizationService
    ) {}

    /**
     * Update module enabled/disabled settings for a business tenant.
     * Enforces strict authorization, module registry validation, transactional mutation, audit logging,
     * and auth cache invalidation.
     *
     * @param  array<string, bool>  $modules  [ 'catalogue' => true, 'inventory' => false, ... ]
     * @return array<string, bool>
     *
     * @throws AuthorizationException
     * @throws DomainException
     */
    public function execute(User $actor, Business $business, array $modules): array
    {
        // 1. Authorization check
        if ($actor->isPlatformAdmin()) {
            if ($this->authorizationService->cannot($actor, 'businesses.update')) {
                throw new AuthorizationException('Unauthorized to update business module settings as platform administrator.');
            }
        } else {
            if ($this->authorizationService->cannot($actor, 'business.update', $business)) {
                throw new AuthorizationException('Unauthorized to update module settings for this business.');
            }

            if ($actor->membership?->business_id !== $business->id) {
                throw new DomainException("Cannot modify module settings for business [{$business->id}] from another tenant context.");
            }
        }

        // 2. Validate all provided module slugs against the authoritative BusinessModule enum
        $validatedSettings = [];
        foreach ($modules as $slug => $isEnabled) {
            $enum = BusinessModule::tryFrom($slug);
            if (! $enum) {
                throw new DomainException("Unknown or invalid business module [{$slug}].");
            }
            $validatedSettings[$enum->value] = (bool) $isEnabled;
        }

        if (empty($validatedSettings)) {
            return [];
        }

        return DB::transaction(function () use ($actor, $business, $validatedSettings) {
            $changes = [];

            foreach ($validatedSettings as $moduleSlug => $isEnabled) {
                $setting = BusinessModuleSetting::query()
                    ->where('business_id', $business->id)
                    ->where('module_slug', $moduleSlug)
                    ->first();

                $oldState = $setting ? (bool) $setting->is_enabled : true;

                if (! $setting || $oldState !== $isEnabled) {
                    BusinessModuleSetting::updateOrCreate(
                        [
                            'business_id' => $business->id,
                            'module_slug' => $moduleSlug,
                        ],
                        [
                            'is_enabled' => $isEnabled,
                            'updated_by' => $actor->id,
                        ]
                    );

                    $changes[$moduleSlug] = [
                        'old' => $oldState,
                        'new' => $isEnabled,
                    ];
                }
            }

            if (! empty($changes)) {
                AuditLog::create([
                    'business_id' => $business->id,
                    'user_id' => $actor->id,
                    'action' => 'business.modules_updated',
                    'auditable_type' => Business::class,
                    'auditable_id' => $business->id,
                    'payload' => [
                        'changes' => $changes,
                    ],
                ]);
            }

            // Invalidate authorization cache immediately
            $this->authorizationService->clearMemoized();

            // Return current complete module states for this business
            $allModules = [];
            $persisted = BusinessModuleSetting::query()
                ->where('business_id', $business->id)
                ->pluck('is_enabled', 'module_slug')
                ->all();

            foreach (BusinessModule::cases() as $case) {
                $allModules[$case->value] = isset($persisted[$case->value])
                    ? (bool) $persisted[$case->value]
                    : true;
            }

            return $allModules;
        });
    }
}
