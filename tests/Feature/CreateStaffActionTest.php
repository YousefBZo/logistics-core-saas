<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Staff\CreateStaffAction;
use App\DataTransferObjects\StaffCreationData;
use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

class CreateStaffActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_driver_staff_with_default_permissions(): void
    {
        $tenant = Tenant::factory()->create();

        $user = app(CreateStaffAction::class)->execute(new StaffCreationData(
            name: 'Route Driver',
            email: 'driver@example.com',
            password: 'password-secret',
            phone: '+15550100010',
            roleType: 'driver',
            tenantId: $tenant->id,
        ));

        $user = User::withoutGlobalScopes()->findOrFail($user->id);

        $this->assertSame($tenant->id, $user->tenant_id);
        $this->assertSame('Route Driver', $user->name);
        $this->assertSame('driver@example.com', $user->email);
        $this->assertSame('+15550100010', $user->phone);
        $this->assertSame(Permission::driverDefault(), $user->permissions_mask);
        $this->assertTrue(Hash::check('password-secret', $user->password));
    }

    public function test_it_creates_warehouse_manager_staff_with_default_permissions(): void
    {
        $tenant = Tenant::factory()->create();

        $user = app(CreateStaffAction::class)->execute(new StaffCreationData(
            name: 'Warehouse Manager',
            email: 'warehouse@example.com',
            password: 'password-secret',
            phone: '+15550100011',
            roleType: 'warehouse_manager',
            tenantId: $tenant->id,
        ));

        $user = User::withoutGlobalScopes()->findOrFail($user->id);

        $this->assertSame($tenant->id, $user->tenant_id);
        $this->assertSame(Permission::warehouseDefault(), $user->permissions_mask);
        $this->assertTrue(Hash::check('password-secret', $user->password));
    }

    public function test_it_rejects_unsupported_staff_role_type(): void
    {
        $tenant = Tenant::factory()->create();

        try {
            app(CreateStaffAction::class)->execute(new StaffCreationData(
                name: 'Dispatcher',
                email: 'dispatcher@example.com',
                password: 'password-secret',
                phone: '+15550100012',
                roleType: 'dispatcher',
                tenantId: $tenant->id,
            ));

            $this->fail('Unsupported staff role types should not create users.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Unsupported staff role type.', $exception->getMessage());
        }

        $this->assertDatabaseMissing('users', [
            'email' => 'dispatcher@example.com',
        ]);
    }
}
