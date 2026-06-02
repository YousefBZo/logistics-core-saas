<?php

namespace App\Actions\Auth;

use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterTenantAction
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $tenant = Tenant::create([
                'company_name' => $data['company_name'],

                'subdomain' => strtolower($data['subdomain']),

            ]);

            // 2. Create the overall admin account and activate the highest mathematical bit MANAGE_TENANT

            return User::create([
                'tenant_id' => $tenant->id,

                'name' => $data['name'],

                'email' => $data['email'],

                'phone' => $data['phone'],

                'password' => Hash::make($data['password']),

                'permissions_mask' => Permission::MANAGE_TENANT->value, // Value: 32 (binary: 100000)

                'status' => 'active',
            ]);
        });
    }
}
