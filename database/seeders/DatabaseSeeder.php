<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = Tenant::factory()->create([
            'company_name' => 'Demo Logistics',
            'subdomain' => 'demo-logistics',
        ]);

        User::factory()->admin()->for($tenant)->create([
            'name' => 'Demo Admin',
            'email' => 'test@example.com',
            'phone' => '+15550101010',
        ]);
    }
}
