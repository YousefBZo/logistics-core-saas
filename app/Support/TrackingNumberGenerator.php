<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final readonly class TrackingNumberGenerator
{
    public function candidate(int $tenantId): string
    {
        return sprintf(
            'SHP-%d-%s-%s',
            $tenantId,
            now()->format('Ymd'),
            Str::upper(Str::random(8))
        );
    }
}
