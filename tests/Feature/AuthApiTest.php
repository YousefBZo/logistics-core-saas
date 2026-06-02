<?php

namespace Tests\Feature;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_registration_creates_tenant_admin_and_returns_contract(): void
    {
        $response = $this->postJson('/api/auth/register-company', [
            'company_name' => 'Acme Logistics',
            'subdomain' => 'Acme-Hub',
            'name' => 'Acme Admin',
            'email' => 'admin@example.com',
            'phone' => '+15550100001',
            'password' => 'password-secret',
            'password_confirmation' => 'password-secret',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'tenant_id',
                    'name',
                    'email',
                    'phone',
                    'permissions_mask',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('user.email', 'admin@example.com')
            ->assertJsonPath('user.permissions_mask', Permission::MANAGE_TENANT->value);

        $this->assertArrayNotHasKey('password', $response->json('user'));
        $this->assertDatabaseHas('tenants', [
            'company_name' => 'Acme Logistics',
            'subdomain' => 'acme-hub',
        ]);

        $user = User::withoutGlobalScopes()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password-secret', $user->password));
        $this->assertSame(Permission::MANAGE_TENANT->value, $user->permissions_mask);
    }

    public function test_merchant_registration_creates_profile_with_default_permissions(): void
    {
        $tenant = Tenant::factory()->create();

        $response = $this->postJson('/api/auth/register-merchant', [
            'tenant_id' => $tenant->id,
            'name' => 'North Store',
            'email' => 'merchant@example.com',
            'phone' => '+15550100002',
            'password' => 'password-secret',
            'store_name' => 'North Storefront',
            'pickup_address' => '12 Market Street',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'tenant_id',
                    'name',
                    'email',
                    'phone',
                    'permissions_mask',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('user.tenant_id', $tenant->id)
            ->assertJsonPath('user.permissions_mask', Permission::merchantDefault());

        $user = User::withoutGlobalScopes()->where('email', 'merchant@example.com')->firstOrFail();

        $this->assertDatabaseHas('merchant_profiles', [
            'user_id' => $user->id,
            'store_name' => 'North Storefront',
            'pickup_address' => '12 Market Street',
        ]);
    }

    public function test_merchant_registration_rejects_duplicate_identity_across_tenants(): void
    {
        User::factory()->create([
            'email' => 'taken@example.com',
            'phone' => '+15550100003',
        ]);

        $tenant = Tenant::factory()->create();

        $this->postJson('/api/auth/register-merchant', [
            'tenant_id' => $tenant->id,
            'name' => 'Duplicate Merchant',
            'email' => 'taken@example.com',
            'phone' => '+15550100003',
            'password' => 'password-secret',
            'store_name' => 'Duplicate Store',
            'pickup_address' => '99 Market Street',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone']);
    }

    public function test_login_issues_bearer_token_and_permission_contract(): void
    {
        $user = User::factory()->merchant()->create([
            'email' => 'merchant-login@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'merchant-login@example.com',
            'password' => 'correct-password',
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'access_token',
                'token_type',
                'permissions_mask',
                'user' => [
                    'id',
                    'tenant_id',
                    'name',
                    'email',
                    'phone',
                    'permissions_mask',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('permissions_mask', Permission::merchantDefault())
            ->assertJsonPath('user.id', $user->id);

        $this->assertNotEmpty($response->json('access_token'));
        $this->assertArrayNotHasKey('password', $response->json('user'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_suspended_users_cannot_login(): void
    {
        User::factory()->suspended()->create([
            'email' => 'suspended@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'suspended@example.com',
            'password' => 'correct-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_authenticated_user_can_read_me_and_logout_current_token(): void
    {
        User::factory()->merchant()->create([
            'email' => 'me@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $token = $this->postJson('/api/auth/login', [
            'email' => 'me@example.com',
            'password' => 'correct-password',
        ])->assertOk()->json('access_token');

        $this->withToken($token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('email', 'me@example.com');

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Tokens revoked successfully. Logged out.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
