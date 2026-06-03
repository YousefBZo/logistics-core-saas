<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: int
{
    // Utilizing Bit Shifting (<<) to precisely allocate binary bit positions
    case CREATE_SHIPMENT = 1 << 0; // 1  (Binary: 000001) -> Merchant specific
    case VIEW_SHIPMENT = 1 << 1; // 2  (Binary: 000010) -> Shared access
    case SORT_PACKAGES = 1 << 2; // 4  (Binary: 000100) -> Warehouse manager
    case ASSIGN_DRIVERS = 1 << 3; // 8  (Binary: 001000) -> Management / Admin
    case DELIVER_SHIPMENT = 1 << 4; // 16 (Binary: 010000) -> Driver
    case MANAGE_TENANT = 1 << 5; // 32 (Binary: 100000) -> Logistics Tenant Admin

    // Cumulative Default Masks combined via bitwise OR (|)
    public static function merchantDefault(): int
    {
        return self::CREATE_SHIPMENT->value | self::VIEW_SHIPMENT->value; // 1 | 2 = 3
    }

    public static function driverDefault(): int
    {
        return self::VIEW_SHIPMENT->value | self::DELIVER_SHIPMENT->value; // 2 | 16 = 18
    }

    public static function warehouseDefault(): int
    {
        return self::VIEW_SHIPMENT->value | self::SORT_PACKAGES->value; // 2 | 4 = 6
    }

    public static function resolveRouteName(string $name): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return null;
    }
}
