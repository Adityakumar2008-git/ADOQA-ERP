<?php

namespace App\Enums;

enum InventoryMode: string
{
    case Normal = 'normal';
    case Batch = 'batch';
    case Serial = 'serial';
    case Imei = 'imei';
    case Accessory = 'accessory';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal (Quantity)',
            self::Batch => 'Batch / Lot',
            self::Serial => 'Serial Tracked',
            self::Imei => 'IMEI Device',
            self::Accessory => 'Accessory',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Normal => 'Quantity-based inventory stock tracking.',
            self::Batch => 'Stock tracked with batch numbers, manufacturing, and expiry dates.',
            self::Serial => 'Individually serialized hardware units with unique serial identifiers.',
            self::Imei => 'Mobile phones and telecom devices tracked by unique IMEI numbers.',
            self::Accessory => 'Small accessories or consumable parts with standard quantity counts.',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            self::Normal->value => self::Normal->label(),
            self::Batch->value => self::Batch->label(),
            self::Serial->value => self::Serial->label(),
            self::Imei->value => self::Imei->label(),
            self::Accessory->value => self::Accessory->label(),
        ];
    }
}
