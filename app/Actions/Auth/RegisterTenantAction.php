<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObjects\TenantRegistrationData;
use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class RegisterTenantAction
{
    public function execute(TenantRegistrationData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $tenant = Tenant::create([
                'company_name' => $data->companyName,
                'subdomain' => $data->subdomain,
            ]);

            return User::provision(
                attributes: [
                    'name' => $data->name,
                    'email' => $data->email,
                    'phone' => $data->phone,
                    'password' => $data->password,
                    'status' => 'active',
                ],
                tenantId: $tenant->id,
                permissionsMask: Permission::MANAGE_TENANT->value,
            );
        });
    }
}
