<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Shipments\CreateShipmentAction;
use App\DataTransferObjects\ShipmentCreationData;
use App\Enums\ShipmentStatus;
use App\Models\ShipmentLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
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
            'status' => ShipmentStatus::CREATED->value,
            'customer_name' => 'Customer One',
        ]);

        $this->assertDatabaseHas('shipment_logs', [
            'tenant_id' => $merchant->tenant_id,
            'shipment_id' => $shipment->id,
            'user_id' => $merchant->id,
            'triggered_by' => $merchant->id,
            'action_type' => ShipmentStatus::CREATED->value,
            'status_from' => ShipmentStatus::CREATED->value,
            'status_to' => ShipmentStatus::CREATED->value,
        ]);

        $this->assertSame(1, ShipmentLog::query()->count());
    }

    public function test_it_rejects_cross_tenant_merchant_before_transaction_starts(): void
    {
        $merchant = User::factory()->merchant()->create();

        try {
            app(CreateShipmentAction::class)->execute(new ShipmentCreationData(
                tenantId: $merchant->tenant_id + 999,
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

            $this->fail('Expected AuthorizationException was not thrown.');
        } catch (AuthorizationException $exception) {
            $this->assertSame('The selected merchant does not belong to your tenant.', $exception->getMessage());
        }

        $this->assertDatabaseCount('shipments', 0);
        $this->assertDatabaseCount('shipment_logs', 0);
    }
}
