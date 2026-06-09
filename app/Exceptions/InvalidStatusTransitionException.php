<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ShipmentStatus;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

final class InvalidStatusTransitionException extends UnprocessableEntityHttpException
{
    public static function fromTransition(?ShipmentStatus $from, ShipmentStatus $to): self
    {
        $fromValue = $from->value ?? 'start';

        return new self(sprintf(
            'Illegal shipment status transition from "%s" to "%s".',
            $fromValue,
            $to->value,
        ));
    }
}
