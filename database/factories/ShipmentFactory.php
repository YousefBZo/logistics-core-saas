<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Shipment> */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'merchant_id' => User::factory()->merchant(),
            'warehouse_id' => null,
            'tracking_number' => sprintf(
                'SHP-%s-%s-%s',
                fake()->numberBetween(1, 999),
                now()->format('Ymd'),
                strtoupper(fake()->bothify('########'))
            ),
            'status' => ShipmentStatus::PENDING->value,
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->numerify('+97059#######'),
            'city' => fake()->city(),
            'area_or_zone' => fake()->streetSuffix(),
            'detailed_address' => fake()->streetAddress(),
            'customer_latitude' => fake()->latitude(),
            'customer_longitude' => fake()->longitude(),
            'cod_amount' => fake()->randomFloat(4, 0, 500),
            'delivery_fees' => fake()->randomFloat(4, 0, 50),
            'weight_kg' => fake()->randomFloat(2, 0.5, 25),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forWarehouse(Warehouse $warehouse): static
    {
        return $this->state(fn (): array => [
            'tenant_id' => $warehouse->tenant_id,
            'warehouse_id' => $warehouse->id,
        ]);
    }
}
