<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    /**
     * Determine if this status allows active business operation.
     */
    public function isUsable(): bool
    {
        return match ($this) {
            self::Trial, self::Active, self::PastDue => true,
            self::Suspended, self::Cancelled, self::Expired => false,
        };
    }

    /**
     * Allowed transitions according to 07_WORKFLOWS_AND_STATE_MACHINES.md:
     * Trial → Active → Past Due → Suspended
     * Active → Cancelled/Expired
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Trial => [
                self::Active,
                self::Expired,
                self::Cancelled,
            ],
            self::Active => [
                self::PastDue,
                self::Suspended,
                self::Cancelled,
                self::Expired,
            ],
            self::PastDue => [
                self::Active,
                self::Suspended,
                self::Cancelled,
                self::Expired,
            ],
            self::Suspended => [
                self::Active,
                self::Cancelled,
                self::Expired,
            ],
            self::Cancelled => [], // Terminal state
            self::Expired => [],   // Terminal state
        };
    }

    /**
     * Check if transition to target status is valid.
     */
    public function canTransitionTo(SubscriptionStatus $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
