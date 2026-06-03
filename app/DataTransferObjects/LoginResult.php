<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Models\User;

final readonly class LoginResult
{
    public function __construct(
        public User $user,
        public string $token,
        public int $permissionsMask,
    ) {}
}
