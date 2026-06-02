<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the tenant's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_name' => fake()->unique()->company(),
            'subdomain' => fake()->unique()->lexify('tenant-??????'),
            'currency' => 'USD',
            'timezone' => 'UTC',
            'status' => 'active',
        ];
    }
}
