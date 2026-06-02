<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_queries_are_limited_to_the_current_tenant(): void
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
}
