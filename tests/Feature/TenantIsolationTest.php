<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MerchantProfile;
use App\Models\Shipment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_queries_are_limited_to_the_current_tenant_for_users(): void
    {
        $currentTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();

        $actingUser = User::factory()->admin()->for($currentTenant)->create();
        $visibleUser = User::factory()->for($currentTenant)->create();
        $hiddenUser = User::factory()->for($otherTenant)->create();

        $this->actingAs($actingUser);

        $visibleIds = User::query()->pluck('id')->all();

        $this->assertContains($actingUser->id, $visibleIds);
        $this->assertContains($visibleUser->id, $visibleIds);
        $this->assertNotContains($hiddenUser->id, $visibleIds);
    }

    public function test_authenticated_queries_are_limited_to_the_current_tenant_for_shipments(): void
    {
        $currentTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $merchant = User::factory()->merchant()->for($currentTenant)->create();

        Shipment::factory()->for($currentTenant)->create([
            'merchant_id' => $merchant->id,
            'tracking_number' => 'SHP-1-20260604-VISIBLE1',
        ]);
        Shipment::factory()->for($otherTenant)->create([
            'tracking_number' => 'SHP-2-20260604-HIDDEN01',
        ]);

        $this->actingAs($merchant);

        $this->assertSame(1, Shipment::query()->count());
        $this->assertSame('SHP-1-20260604-VISIBLE1', Shipment::query()->value('tracking_number'));
    }

    public function test_authenticated_queries_are_limited_to_the_current_tenant_for_warehouses(): void
    {
        $currentTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $admin = User::factory()->admin()->for($currentTenant)->create();

        Warehouse::factory()->for($currentTenant)->create(['name' => 'Visible Warehouse']);
        Warehouse::factory()->for($otherTenant)->create(['name' => 'Hidden Warehouse']);

        $this->actingAs($admin);

        $this->assertSame(1, Warehouse::query()->count());
        $this->assertSame('Visible Warehouse', Warehouse::query()->value('name'));
    }

    public function test_authenticated_queries_are_limited_to_the_current_tenant_for_merchant_profiles(): void
    {
        $currentTenant = Tenant::factory()->create();
        $otherTenant = Tenant::factory()->create();
        $admin = User::factory()->admin()->for($currentTenant)->create();

        MerchantProfile::factory()->forTenant($currentTenant)->create(['store_name' => 'Visible Store']);
        MerchantProfile::factory()->forTenant($otherTenant)->create(['store_name' => 'Hidden Store']);

        $this->actingAs($admin);

        $this->assertSame(1, MerchantProfile::query()->count());
        $this->assertSame('Visible Store', MerchantProfile::query()->value('store_name'));
    }
}
