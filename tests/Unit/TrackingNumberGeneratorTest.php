<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\TrackingNumberGenerator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TrackingNumberGeneratorTest extends TestCase
{
    #[Test]
    public function candidate_includes_tenant_id_date_and_random_suffix(): void
    {
        $generator = new TrackingNumberGenerator;

        $trackingNumber = $generator->candidate(42);

        $this->assertMatchesRegularExpression('/^SHP-42-\d{8}-[A-Z0-9]{8}$/', $trackingNumber);
    }

    #[Test]
    public function candidate_generates_unique_values(): void
    {
        $generator = new TrackingNumberGenerator;

        $first = $generator->candidate(7);
        $second = $generator->candidate(7);

        $this->assertNotSame($first, $second);
    }
}
