<?php

namespace App\Actions\Auth;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterMerchantAction
{
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $exists = User::withoutGlobalScopes()

                ->where(function ($query) use ($data) {
                    $query->where('email', $data['email'])

                        ->orWhere('phone', $data['phone']);
                })->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'email' => 'This merchant email or phone is already registered.',

                ]);
            }

            // 1. Create the user account with the merchant's default bitmask injection (CREATE_SHIPMENT | VIEW_SHIPMENT)

            $user = User::create([
                'tenant_id' => $data['tenant_id'],

                'name' => $data['name'],

                'email' => $data['email'],

                'phone' => $data['phone'],

                'password' => Hash::make($data['password']),

                'permissions_mask' => Permission::merchantDefault(), // Value: 3 (binary: 000011)

                'status' => 'active',
            ]);

            // 2. Create the merchant's store profile to specify their warehouse address
            $user->merchantProfile()->create([
                'store_name' => $data['store_name'],

                'pickup_address' => $data['pickup_address'],

            ]);

            return $user;
        });
    }
}
