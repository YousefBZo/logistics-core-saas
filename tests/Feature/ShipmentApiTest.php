<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Permission;
use App\Enums\ShipmentStatus;
use App\Models\ShipmentLog;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShipmentApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('idempotency.store', 'array');
    }

    public function test_merchant_can_create_shipment_with_initial_tracking_log(): void
    {
        $tenant = Tenant::factory()->create();
        $merchant = User::factory()->merchant()->for($tenant)->create();
        $warehouse = Warehouse::create([
            'tenant_id' => $tenant->id,
            'name' => 'Central Sorting Center',
            'city' => 'Hebron',
            'address_details' => 'Industrial zone',
        ]);

        Sanctum::actingAs($merchant);

        $response = $this->postJson('/api/shipments', $this->validPayload([
            'warehouse_id' => $warehouse->id,
        ]), [
            'X-Idempotency-Key' => 'shipment-create-001',
        ]);

        $response
            ->assertCreated()
            ->assertHeader('X-Idempotency-Cache', 'MISS')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'tracking_number',
                    'status',
                    'warehouse_id',
                    'customer_name',
                    'customer_phone',
                    'city',
                    'area_or_zone',
                    'cod_amount',
                    'delivery_fees',
                    'weight_kg',
                    'created_at',
                ],
            ])
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Shipment created successfully.')
            ->assertJsonPath('data.status', ShipmentStatus::CREATED->value)
            ->assertJsonPath('data.warehouse_id', $warehouse->id)
            ->assertJsonPath('data.customer_name', 'Customer One');

        $this->assertSame(['status', 'message', 'data'], array_keys($response->json()));
        $this->assertSame([
            'id',
            'tracking_number',
            'status',
            'warehouse_id',
            'customer_name',
            'customer_phone',
            'city',
            'area_or_zone',
            'cod_amount',
            'delivery_fees',
            'weight_kg',
            'created_at',
        ], array_keys($response->json('data')));

        $this->assertDatabaseHas('shipments', [
            'id' => $response->json('data.id'),
            'tenant_id' => $tenant->id,
            'merchant_id' => $merchant->id,
            'warehouse_id' => $warehouse->id,
            'status' => ShipmentStatus::CREATED->value,
            'customer_name' => 'Customer One',
        ]);

        $this->assertDatabaseHas('shipment_logs', [
            'tenant_id' => $tenant->id,
            'shipment_id' => $response->json('data.id'),
            'user_id' => $merchant->id,
            'triggered_by' => $merchant->id,
            'action_type' => ShipmentStatus::CREATED->value,
            'status_from' => ShipmentStatus::CREATED->value,
            'status_to' => ShipmentStatus::CREATED->value,
        ]);

        $visibleLogIds = ShipmentLog::query()->pluck('id')->all();

        $this->assertCount(1, $visibleLogIds);
    }

    public function test_idempotency_key_replays_cached_response_without_duplicate_shipments(): void
    {
        $merchant = User::factory()->merchant()->create();

        Sanctum::actingAs($merchant);

        $headers = ['X-Idempotency-Key' => 'shipment-create-duplicate-key'];
        $payload = $this->validPayload();

        $firstResponse = $this->postJson('/api/shipments', $payload, $headers)
            ->assertCreated()
            ->assertHeader('X-Idempotency-Cache', 'MISS');

        $secondResponse = $this->postJson('/api/shipments', $payload, $headers)
            ->assertCreated()
            ->assertHeader('X-Idempotency-Cache', 'HIT');

        $this->assertSame($firstResponse->json(), $secondResponse->json());
        $this->assertDatabaseCount('shipments', 1);
        $this->assertDatabaseCount('shipment_logs', 1);
    }

    public function test_reusing_idempotency_key_with_different_payload_is_rejected(): void
    {
        $merchant = User::factory()->merchant()->create();

        Sanctum::actingAs($merchant);

        $headers = ['X-Idempotency-Key' => 'shipment-create-payload-mismatch'];

        $this->postJson('/api/shipments', $this->validPayload(), $headers)
            ->assertCreated();

        $this->postJson('/api/shipments', $this->validPayload([
            'customer_name' => 'Different Customer',
        ]), $headers)
            ->assertConflict()
            ->assertJsonPath('message', 'This idempotency key has already been used with a different request payload.');

        $this->assertDatabaseCount('shipments', 1);
        $this->assertDatabaseCount('shipment_logs', 1);
    }

    public function test_shipment_creation_requires_idempotency_key(): void
    {
        $merchant = User::factory()->merchant()->create();

        Sanctum::actingAs($merchant);

        $this->postJson('/api/shipments', $this->validPayload())
            ->assertBadRequest()
            ->assertJsonPath('message', 'Missing required X-Idempotency-Key header.');

        $this->assertDatabaseCount('shipments', 0);
        $this->assertDatabaseCount('shipment_logs', 0);
    }

    public function test_shipment_creation_requires_create_shipment_permission(): void
    {
        $user = User::factory()->create([
            'permissions_mask' => Permission::VIEW_SHIPMENT->value,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/shipments', $this->validPayload(), [
            'X-Idempotency-Key' => 'shipment-create-forbidden',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Access Denied: You do not have the algebraic clearance for this logistics action.');
    }

    public function test_shipment_creation_rejects_warehouse_from_another_tenant(): void
    {
        $merchant = User::factory()->merchant()->create();
        $otherTenant = Tenant::factory()->create();
        $warehouse = Warehouse::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Sorting Center',
            'city' => 'Ramallah',
            'address_details' => 'North district',
        ]);

        Sanctum::actingAs($merchant);

        $this->postJson('/api/shipments', $this->validPayload([
            'warehouse_id' => $warehouse->id,
        ]), [
            'X-Idempotency-Key' => 'shipment-create-wrong-warehouse',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('warehouse_id');
    }

    public function test_shipment_creation_rejects_merchant_from_another_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->admin()->for($tenant)->create([
            'permissions_mask' => Permission::MANAGE_TENANT->value | Permission::CREATE_SHIPMENT->value,
        ]);
        $otherMerchant = User::factory()->merchant()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/shipments', $this->validPayload([
            'merchant_id' => $otherMerchant->id,
        ]), [
            'X-Idempotency-Key' => 'shipment-create-wrong-merchant',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('merchant_id');
    }

    public function test_admin_must_provide_merchant_id_when_creating_shipment(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->for($tenant)->create([
            'permissions_mask' => Permission::MANAGE_TENANT->value | Permission::CREATE_SHIPMENT->value,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/shipments', $this->validPayload(), [
            'X-Idempotency-Key' => 'shipment-create-admin-missing-merchant-id',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('merchant_id');
    }

    public function test_admin_can_create_on_behalf_of_merchant_within_same_tenant(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->for($tenant)->create([
            'permissions_mask' => Permission::MANAGE_TENANT->value | Permission::CREATE_SHIPMENT->value,
        ]);
        $merchant = User::factory()->merchant()->for($tenant)->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/shipments', $this->validPayload([
            'merchant_id' => $merchant->id,
        ]), [
            'X-Idempotency-Key' => 'shipment-create-admin-on-behalf',
        ])->assertCreated();

        $shipmentId = $response->json('data.id');

        $this->assertDatabaseHas('shipments', [
            'id' => $shipmentId,
            'tenant_id' => $tenant->id,
            'merchant_id' => $merchant->id,
        ]);

        $this->assertDatabaseHas('shipment_logs', [
            'shipment_id' => $shipmentId,
            'user_id' => $merchant->id,
            'triggered_by' => $admin->id,
        ]);
    }

    public function test_merchant_payload_merchant_id_is_ignored_to_prevent_spoofing(): void
    {
        $tenant = Tenant::factory()->create();
        $merchant = User::factory()->merchant()->for($tenant)->create();
        $otherMerchant = User::factory()->merchant()->for($tenant)->create();

        Sanctum::actingAs($merchant);

        $response = $this->postJson('/api/shipments', $this->validPayload([
            'merchant_id' => $otherMerchant->id,
        ]), [
            'X-Idempotency-Key' => 'shipment-create-merchant-spoof-attempt',
        ])->assertCreated();

        $this->assertDatabaseHas('shipments', [
            'id' => $response->json('data.id'),
            'tenant_id' => $tenant->id,
            'merchant_id' => $merchant->id,
        ]);
    }

    public function test_shipment_creation_rejects_cod_amount_outside_precision_12_4(): void
    {
        $merchant = User::factory()->merchant()->create();

        Sanctum::actingAs($merchant);

        $this->postJson('/api/shipments', $this->validPayload([
            'cod_amount' => '123456789.12345',
        ]), [
            'X-Idempotency-Key' => 'shipment-create-cod-precision',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cod_amount');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Customer One',
            'customer_phone' => '+970599000001',
            'city' => 'Hebron',
            'area_or_zone' => 'H1',
            'detailed_address' => 'Main street, building 12',
            'customer_latitude' => '31.5326',
            'customer_longitude' => '35.0998',
            'cod_amount' => '25.5000',
            'delivery_fees' => '3.2500',
            'weight_kg' => '2.50',
            'notes' => 'Fragile package',
        ], $overrides);
    }
}
