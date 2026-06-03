<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObjects\MerchantRegistrationData;
use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RegisterMerchantAction
{
    public function execute(MerchantRegistrationData $data): User
    {
        return DB::transaction(function () use ($data): User {
            $tenant = Tenant::query()
                ->where('subdomain', $data->tenantSubdomain)
                ->firstOrFail();

            $exists = User::withoutGlobalScopes()
                ->where(function ($query) use ($data): void {
                    $query->where('email', $data->email)
                        ->orWhere('phone', $data->phone);
                })
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'email' => 'This merchant email or phone is already registered.',
                ]);
            }

            $user = User::provision(
                attributes: [
                    'name' => $data->name,
                    'email' => $data->email,
                    'phone' => $data->phone,
                    'password' => $data->password,
                    'status' => 'active',
                ],
                tenantId: $tenant->id,
                permissionsMask: Permission::merchantDefault(),
            );

            $profile = $user->merchantProfile()->make([
                'store_name' => $data->storeName,
                'pickup_address' => $data->pickupAddress,
            ]);
            $profile->forceFill(['tenant_id' => $tenant->id]);
            $user->merchantProfile()->save($profile);

            return $user;
        });
    }
}
