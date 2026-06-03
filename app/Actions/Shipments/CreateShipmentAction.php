<?php

declare(strict_types=1);

namespace App\Actions\Shipments;

use App\DataTransferObjects\ShipmentCreationData;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\ShipmentLog;
use App\Support\TrackingNumberGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class CreateShipmentAction
{
    private const MAX_TRACKING_NUMBER_ATTEMPTS = 5;

    public function __construct(
        private TrackingNumberGenerator $trackingNumberGenerator
    ) {}

    public function execute(ShipmentCreationData $data): Shipment
    {
        return DB::transaction(function () use ($data): Shipment {
            for ($attempt = 0; $attempt < self::MAX_TRACKING_NUMBER_ATTEMPTS; $attempt++) {
                try {
                    $shipment = Shipment::create([
                        'tenant_id' => $data->tenantId,
                        'merchant_id' => $data->merchantId,
                        'warehouse_id' => $data->warehouseId,
                        'tracking_number' => $this->trackingNumberGenerator->candidate($data->tenantId),
                        'status' => ShipmentStatus::PENDING->value,
                        'customer_name' => $data->customerName,
                        'customer_phone' => $data->customerPhone,
                        'city' => $data->city,
                        'area_or_zone' => $data->areaOrZone,
                        'detailed_address' => $data->detailedAddress,
                        'customer_latitude' => $data->customerLatitude,
                        'customer_longitude' => $data->customerLongitude,
                        'cod_amount' => $data->codAmount,
                        'delivery_fees' => $data->deliveryFees,
                        'weight_kg' => $data->weightKg,
                        'notes' => $data->notes,
                    ]);

                    ShipmentLog::create([
                        'tenant_id' => $data->tenantId,
                        'shipment_id' => $shipment->id,
                        'user_id' => $data->merchantId,
                        'status_from' => ShipmentStatus::CREATED->value,
                        'status_to' => ShipmentStatus::PENDING->value,
                        'comment' => 'Shipment created and queued for logistics intake.',
                    ]);

                    return $shipment;
                } catch (QueryException $exception) {
                    if (! $this->isDuplicateTrackingNumberViolation($exception)) {
                        throw $exception;
                    }
                }
            }

            throw new RuntimeException('Unable to generate a unique tracking number.');
        });
    }

    private function isDuplicateTrackingNumberViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;

        return $sqlState === '23505'
            || str_contains(strtolower($exception->getMessage()), 'tracking_number');
    }
}
