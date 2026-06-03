<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObjects\LoginCredentialsData;
use App\DataTransferObjects\LoginResult;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class LoginAction
{
    public function execute(LoginCredentialsData $credentials): LoginResult
    {
        $user = User::withoutGlobalScopes()->where('email', $credentials->email)->first();

        if (! $user || ! Hash::check($credentials->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Your account has been suspended. Please contact management.'],
            ]);
        }

        return new LoginResult(
            user: $user,
            token: $user->createToken('auth_token')->plainTextToken,
            permissionsMask: $user->permissions_mask,
        );
    }
}
