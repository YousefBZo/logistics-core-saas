<?php

declare(strict_types=1);

namespace App\Actions\Shipments;

use App\DataTransferObjects\ShipmentCreationData;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\ShipmentLog;
use App\Models\User;
use App\Support\Shipments\ShipmentStateMachine;
use App\Support\TrackingNumberGenerator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class CreateShipmentAction
{
    public function __construct(
        private TrackingNumberGenerator $trackingNumberGenerator,
        private ShipmentStateMachine $shipmentStateMachine,
    ) {}

    public function execute(ShipmentCreationData $data): Shipment
    {
        $merchant = User::query()
            ->withoutGlobalScopes()
            ->select(['id', 'tenant_id'])
            ->where('id', $data->merchantId)
            ->where('tenant_id', $data->tenantId)
            ->first();

        if ($merchant === null) {
            throw new AuthorizationException('The selected merchant does not belong to your tenant.');
        }

        $initialStatus = ShipmentStatus::CREATED;
        $this->shipmentStateMachine->assertCanTransition(null, $initialStatus);

        $triggeredBy = (int) (Auth::id() ?? $data->merchantId);
        $trackingNumber = $this->trackingNumberGenerator->generate($data->tenantId);

        DB::beginTransaction();

        try {
            $shipment = Shipment::create([
                'tenant_id' => $data->tenantId,
                'merchant_id' => $data->merchantId,
                'warehouse_id' => $data->warehouseId,
                'tracking_number' => $trackingNumber,
                'status' => $initialStatus->value,
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
                'triggered_by' => $triggeredBy,
                'action_type' => ShipmentStatus::CREATED->value,
                'status_from' => ShipmentStatus::CREATED->value,
                'status_to' => ShipmentStatus::CREATED->value,
                'comment' => 'Shipment created.',
            ]);

            DB::commit();

            return $shipment;
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        }
    }
}
