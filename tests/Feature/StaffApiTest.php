<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('idempotency.store', 'array');
    }

    public function test_tenant_admin_can_onboard_driver_staff_with_strict_contract(): void
    {
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->admin()->create([
            'tenant_id' => $tenant->id,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/staff', $this->validPayload(), [
            'X-Idempotency-Key' => 'staff-create-001',
        ]);

        $response
            ->assertCreated()
            ->assertHeader('X-Idempotency-Cache', 'MISS')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'permissions_mask',
                    'created_at',
                ],
            ])
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Staff member onboarded successfully inside your tenant.')
            ->assertJsonPath('data.name', 'Route Driver')
            ->assertJsonPath('data.email', 'driver@example.com')
            ->assertJsonPath('data.phone', '+15550100020')
            ->assertJsonPath('data.permissions_mask', Permission::driverDefault());

        $this->assertSame(['status', 'message', 'data'], array_keys($response->json()));
        $this->assertSame([
            'id',
            'name',
            'email',
            'phone',
            'permissions_mask',
            'created_at',
        ], array_keys($response->json('data')));
        $this->assertArrayNotHasKey('password', $response->json('data'));

        $staff = User::withoutGlobalScopes()->where('email', 'driver@example.com')->firstOrFail();

        $this->assertSame($tenant->id, $staff->tenant_id);
        $this->assertSame(Permission::driverDefault(), $staff->permissions_mask);
        $this->assertTrue(Hash::check('password-secret', $staff->password));
    }

    public function test_idempotency_key_replays_cached_response_without_duplicate_staff(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $headers = ['X-Idempotency-Key' => 'staff-create-duplicate-key'];
        $payload = $this->validPayload();

        $firstResponse = $this->postJson('/api/staff', $payload, $headers)
            ->assertCreated()
            ->assertHeader('X-Idempotency-Cache', 'MISS');

        $secondResponse = $this->postJson('/api/staff', $payload, $headers)
            ->assertCreated()
            ->assertHeader('X-Idempotency-Cache', 'HIT');

        $this->assertSame($firstResponse->json(), $secondResponse->json());
        $this->assertDatabaseCount('users', 2);
    }

    public function test_staff_onboarding_requires_idempotency_key(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/staff', $this->validPayload())
            ->assertBadRequest()
            ->assertJsonPath('message', 'Missing required X-Idempotency-Key header.');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_tenant_admin_can_onboard_warehouse_manager_staff(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/staff', $this->validPayload([
            'name' => 'Warehouse Lead',
            'email' => 'warehouse@example.com',
            'role_type' => 'warehouse_manager',
        ]), [
            'X-Idempotency-Key' => 'staff-create-warehouse-manager',
        ])
            ->assertCreated()
            ->assertJsonPath('data.permissions_mask', Permission::warehouseDefault());
    }

    public function test_staff_onboarding_requires_manage_tenant_permission(): void
    {
        $user = User::factory()->create([
            'permissions_mask' => Permission::VIEW_SHIPMENT->value,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/staff', $this->validPayload(), [
            'X-Idempotency-Key' => 'staff-create-forbidden',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Access Denied: You do not have the algebraic clearance for this logistics action.');
    }

    public function test_staff_onboarding_validates_role_contract(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/staff', $this->validPayload([
            'email' => 'dispatcher@example.com',
            'role_type' => 'dispatcher',
        ]), [
            'X-Idempotency-Key' => 'staff-create-invalid-role',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role_type');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Route Driver',
            'email' => 'driver@example.com',
            'password' => 'password-secret',
            'phone' => '+15550100020',
            'role_type' => 'driver',
        ], $overrides);
    }
}
