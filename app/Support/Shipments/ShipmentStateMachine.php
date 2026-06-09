<?php

declare(strict_types=1);

namespace App\Support\Shipments;

use App\Enums\ShipmentStatus;
use App\Exceptions\InvalidStatusTransitionException;

final class ShipmentStateMachine
{
    /**
     * @var array<string, list<ShipmentStatus>>
     */
    private const TRANSITIONS = [
        ShipmentStatus::CREATED->value => [
            ShipmentStatus::PENDING,
            ShipmentStatus::CANCELLED,
        ],
        ShipmentStatus::PENDING->value => [
            ShipmentStatus::PICKED_UP,
            ShipmentStatus::CANCELLED,
        ],
        ShipmentStatus::PICKED_UP->value => [
            ShipmentStatus::RECEIVED_IN_WAREHOUSE,
        ],
        ShipmentStatus::RECEIVED_IN_WAREHOUSE->value => [
            ShipmentStatus::OUT_FOR_DELIVERY,
        ],
        ShipmentStatus::OUT_FOR_DELIVERY->value => [
            ShipmentStatus::DELIVERED,
            ShipmentStatus::RETURNED,
        ],
        ShipmentStatus::CANCELLED->value => [],
        ShipmentStatus::DELIVERED->value => [],
        ShipmentStatus::RETURNED->value => [
            ShipmentStatus::RECEIVED_IN_WAREHOUSE,
        ],
    ];

    public function canTransition(?ShipmentStatus $from, ShipmentStatus $to): bool
    {
        if ($from === null) {
            return $to === ShipmentStatus::CREATED;
        }

        $allowedTransitions = self::TRANSITIONS[$from->value] ?? [];

        foreach ($allowedTransitions as $allowedTransition) {
            if ($allowedTransition === $to) {
                return true;
            }
        }

        return false;
    }

    public function assertCanTransition(?ShipmentStatus $from, ShipmentStatus $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw InvalidStatusTransitionException::fromTransition($from, $to);
        }
    }
}
