<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginAction
{
    public function execute(string $email, string $password): array
    {

        $user = User::withoutGlobalScopes()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],

            ]);
        }

        // Checking for administrative bans in SaaS

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account has been suspended. Please contact management.'],

            ]);
        }

        // Generating Sanctum's digital token

        $token = $user->createToken('auth_token')->plainTextToken;

        // Returning the data array; where the frontend reads the mask to immediately open the corresponding validation screens

        return [
            'user' => $user,

            'token' => $token,

            'permissions_mask' => $user->permissions_mask,

        ];
    }
}
