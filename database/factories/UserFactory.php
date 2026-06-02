<?php

namespace Database\Factories;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('+1##########'),
            'password' => static::$password ??= Hash::make('password'),
            'permissions_mask' => 0,
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Assign tenant-administrator permissions.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions_mask' => Permission::MANAGE_TENANT->value,
        ]);
    }

    /**
     * Assign the default merchant logistics permissions.
     */
    public function merchant(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions_mask' => Permission::merchantDefault(),
        ]);
    }

    /**
     * Mark the user as suspended for authentication checks.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }
}
