<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\TrackingNumberGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TrackingNumberGeneratorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function generate_uses_barcode_friendly_tracking_format(): void
    {
        $generator = new TrackingNumberGenerator;

        $trackingNumber = $generator->generate(42);

        $this->assertMatchesRegularExpression('/^TRK-\d{8}-\d{6}$/', $trackingNumber);
    }

    #[Test]
    public function generate_creates_unique_values(): void
    {
        $generator = new TrackingNumberGenerator;

        $first = $generator->generate(7);
        $second = $generator->generate(7);

        $this->assertNotSame($first, $second);
    }
}
