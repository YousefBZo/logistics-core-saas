<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Http\Requests\StoreStaffRequest;

final readonly class StaffCreationData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $phone,
        public string $roleType,
        public int $tenantId
    ) {}

    /**
     * Factory method to map the validated request and current tenant ID into a clean DTO.
     */
    public static function fromRequest(StoreStaffRequest $request, int $tenantId): self
    {
        return new self(
            name: (string) $request->string('name')->trim(),
            email: (string) $request->string('email')->trim()->lower(),
            password: (string) $request->input('password'),
            phone: (string) $request->string('phone')->trim(),
            roleType: (string) $request->string('role_type'),
            tenantId: $tenantId
        );
    }
}
