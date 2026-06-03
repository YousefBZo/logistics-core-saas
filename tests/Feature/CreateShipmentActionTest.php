<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Shipments\CreateShipmentAction;
use App\DataTransferObjects\ShipmentCreationData;
use App\Enums\ShipmentStatus;
use App\Models\ShipmentLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateShipmentActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_shipment_and_initial_log_atomically(): void
    {
        $merchant = User::factory()->merchant()->create();

        $shipment = app(CreateShipmentAction::class)->execute(new ShipmentCreationData(
            tenantId: $merchant->tenant_id,
            merchantId: $merchant->id,
            warehouseId: null,
            customerName: 'Customer One',
            customerPhone: '+970599000001',
            city: 'Hebron',
            areaOrZone: 'H1',
            detailedAddress: 'Main street, building 12',
            customerLatitude: '31.5326',
            customerLongitude: '35.0998',
            codAmount: '25.5000',
            deliveryFees: '3.2500',
            weightKg: '2.50',
            notes: 'Fragile package',
        ));

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'tenant_id' => $merchant->tenant_id,
            'merchant_id' => $merchant->id,
            'status' => ShipmentStatus::PENDING->value,
            'customer_name' => 'Customer One',
        ]);

        $this->assertDatabaseHas('shipment_logs', [
            'tenant_id' => $merchant->tenant_id,
            'shipment_id' => $shipment->id,
            'user_id' => $merchant->id,
            'status_from' => ShipmentStatus::CREATED->value,
            'status_to' => ShipmentStatus::PENDING->value,
        ]);

        $this->assertSame(1, ShipmentLog::query()->count());
    }
}
