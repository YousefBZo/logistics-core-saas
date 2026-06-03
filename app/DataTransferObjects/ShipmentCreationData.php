<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Http\Requests\StoreShipmentRequest;

final readonly class ShipmentCreationData
{
    public function __construct(
        public int $tenantId,
        public int $merchantId,
        public ?int $warehouseId,
        public string $customerName,
        public string $customerPhone,
        public string $city,
        public string $areaOrZone,
        public string $detailedAddress,
        public ?string $customerLatitude,
        public ?string $customerLongitude,
        public string $codAmount,
        public string $deliveryFees,
        public string $weightKg,
        public ?string $notes,
    ) {}

    public static function fromRequest(StoreShipmentRequest $request, int $tenantId, int $merchantId): self
    {
        return new self(
            tenantId: $tenantId,
            merchantId: $merchantId,
            warehouseId: $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null,
            customerName: (string) $request->string('customer_name')->trim(),
            customerPhone: (string) $request->string('customer_phone')->trim(),
            city: (string) $request->string('city')->trim(),
            areaOrZone: (string) $request->string('area_or_zone')->trim(),
            detailedAddress: (string) $request->string('detailed_address')->trim(),
            customerLatitude: self::nullableString($request, 'customer_latitude'),
            customerLongitude: self::nullableString($request, 'customer_longitude'),
            codAmount: self::decimalString($request, 'cod_amount', '0'),
            deliveryFees: self::decimalString($request, 'delivery_fees', '0'),
            weightKg: self::decimalString($request, 'weight_kg', '1.00'),
            notes: $request->filled('notes') ? (string) $request->string('notes')->trim() : null,
        );
    }

    private static function nullableString(StoreShipmentRequest $request, string $key): ?string
    {
        return $request->filled($key) ? (string) $request->input($key) : null;
    }

    private static function decimalString(StoreShipmentRequest $request, string $key, string $default): string
    {
        return $request->filled($key) ? (string) $request->input($key) : $default;
    }
}
