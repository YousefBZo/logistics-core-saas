<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'permission:CREATE_SHIPMENT'])
            ->get('/api/testing/permissions/create-shipment', fn () => response()->json(['allowed' => true]));

        Route::middleware(['auth:sanctum', 'permission:UNKNOWN_PERMISSION'])
            ->get('/api/testing/permissions/unknown', fn () => response()->json(['allowed' => true]));
    }

    public function test_permission_middleware_allows_users_with_required_bit(): void
    {
        $user = User::factory()->merchant()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/testing/permissions/create-shipment')
            ->assertOk()
            ->assertJsonPath('allowed', true);
    }

    public function test_permission_middleware_blocks_users_without_required_bit(): void
    {
        $user = User::factory()->create([
            'permissions_mask' => Permission::VIEW_SHIPMENT->value,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/testing/permissions/create-shipment')
            ->assertForbidden()
            ->assertJsonPath('message', 'Access Denied: You do not have the algebraic clearance for this logistics action.');
    }

    public function test_permission_middleware_reports_unknown_permission_names(): void
    {
        $user = User::factory()->admin()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/testing/permissions/unknown')
            ->assertStatus(500)
            ->assertJsonPath('message', "Developer Error: Permission 'UNKNOWN_PERMISSION' does not exist.");
    }
}
