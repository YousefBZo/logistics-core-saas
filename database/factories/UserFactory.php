<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** @extends Factory<User> */
class UserFactory extends Factory
{
    protected static ?string $password;

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

    public function create($attributes = [], $parent = null): Model|EloquentCollection
    {
        return User::unguarded(fn (): Model|EloquentCollection => parent::create($attributes, $parent));
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => [
            'permissions_mask' => Permission::MANAGE_TENANT->value,
        ]);
    }

    public function merchant(): static
    {
        return $this->state(fn (array $attributes): array => [
            'permissions_mask' => Permission::merchantDefault(),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'suspended',
        ]);
    }
}
