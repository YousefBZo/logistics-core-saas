<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MerchantProfile;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MerchantProfile> */
class MerchantProfileFactory extends Factory
{
    protected $model = MerchantProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->merchant(),
            'store_name' => fake()->company(),
            'pickup_address' => fake()->streetAddress(),
        ];
    }

    public function configure(): static
    {
        return $this->afterMaking(function (MerchantProfile $profile): void {
            if ($profile->tenant_id !== null) {
                return;
            }

            $tenantId = User::withoutGlobalScopes()
                ->whereKey($profile->user_id)
                ->value('tenant_id');

            if ($tenantId !== null) {
                $profile->forceFill(['tenant_id' => $tenantId]);
            }
        });
    }

    public function forTenant(Tenant $tenant): static
    {
        return $this->state(fn (): array => [
            'user_id' => User::factory()->merchant()->for($tenant),
        ]);
    }
}
