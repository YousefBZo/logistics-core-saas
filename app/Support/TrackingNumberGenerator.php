<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Shipment;
use RuntimeException;

final readonly class TrackingNumberGenerator
{
    private const MAX_ATTEMPTS = 3;

    public function generate(int $tenantId): string
    {
        $attempt = 0;

        do {
            $attempt++;
            $trackingNumber = $this->buildCandidate();
            $isCollision = Shipment::query()
                ->withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('tracking_number', $trackingNumber)
                ->exists();

            if (! $isCollision) {
                return $trackingNumber;
            }
        } while ($attempt < self::MAX_ATTEMPTS);

        throw new RuntimeException('Unable to generate a unique tracking number after 3 attempts.');
    }

    public function candidate(int $tenantId): string
    {
        return $this->generate($tenantId);
    }

    private function buildCandidate(): string
    {
        $randomDigits = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return sprintf(
            'TRK-%s-%s',
            now()->format('Ymd'),
            $randomDigits,
        );
    }
}
