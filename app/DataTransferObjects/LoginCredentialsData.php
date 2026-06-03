<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Http\Requests\Auth\LoginRequest;

final readonly class LoginCredentialsData
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            email: (string) $request->string('email')->trim()->lower(),
            password: (string) $request->input('password'),
        );
    }
}
