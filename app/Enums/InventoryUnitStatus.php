<?php

namespace App\Enums;

enum InventoryUnitStatus: string
{
    case Available = 'available';
    case InTransit = 'in_transit';
    case Damaged = 'damaged';
    case Lost = 'lost';
    case Consumed = 'consumed';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::InTransit => 'In Transit',
            self::Damaged => 'Damaged',
            self::Lost => 'Lost',
            self::Consumed => 'Consumed',
            self::Deactivated => 'Deactivated',
        };
    }

    public function isAvailable(): bool
    {
        return $this === self::Available;
    }
}
