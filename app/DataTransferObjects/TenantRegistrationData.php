<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Http\Requests\Auth\RegisterTenantRequest;

final readonly class TenantRegistrationData
{
    public function __construct(
        public string $companyName,
        public string $subdomain,
        public string $name,
        public string $email,
        public string $phone,
        public string $password,
    ) {}

    public static function fromRequest(RegisterTenantRequest $request): self
    {
        return new self(
            companyName: (string) $request->string('company_name')->trim(),
            subdomain: (string) $request->string('subdomain')->trim()->lower(),
            name: (string) $request->string('name')->trim(),
            email: (string) $request->string('email')->trim()->lower(),
            phone: (string) $request->string('phone')->trim(),
            password: (string) $request->input('password'),
        );
    }
}
