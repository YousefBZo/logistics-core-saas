<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Http\Requests\Auth\RegisterMerchantRequest;

final readonly class MerchantRegistrationData
{
    public function __construct(
        public string $tenantSubdomain,
        public string $name,
        public string $email,
        public string $phone,
        public string $password,
        public string $storeName,
        public string $pickupAddress,
    ) {}

    public static function fromRequest(RegisterMerchantRequest $request): self
    {
        return new self(
            tenantSubdomain: (string) $request->string('tenant_subdomain')->trim()->lower(),
            name: (string) $request->string('name')->trim(),
            email: (string) $request->string('email')->trim()->lower(),
            phone: (string) $request->string('phone')->trim(),
            password: (string) $request->input('password'),
            storeName: (string) $request->string('store_name')->trim(),
            pickupAddress: (string) $request->string('pickup_address')->trim(),
        );
    }
}
