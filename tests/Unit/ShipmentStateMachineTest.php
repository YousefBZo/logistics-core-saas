<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ShipmentStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Support\Shipments\ShipmentStateMachine;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ShipmentStateMachineTest extends TestCase
{
    #[Test]
    public function it_allows_declared_transitions_and_initial_creation(): void
    {
        $machine = new ShipmentStateMachine;

        $this->assertTrue($machine->canTransition(null, ShipmentStatus::CREATED));
        $this->assertTrue($machine->canTransition(ShipmentStatus::CREATED, ShipmentStatus::PENDING));
        $this->assertTrue($machine->canTransition(ShipmentStatus::CREATED, ShipmentStatus::CANCELLED));
        $this->assertTrue($machine->canTransition(ShipmentStatus::PENDING, ShipmentStatus::PICKED_UP));
        $this->assertTrue($machine->canTransition(ShipmentStatus::PENDING, ShipmentStatus::CANCELLED));
        $this->assertTrue($machine->canTransition(ShipmentStatus::PICKED_UP, ShipmentStatus::RECEIVED_IN_WAREHOUSE));
        $this->assertTrue($machine->canTransition(ShipmentStatus::RECEIVED_IN_WAREHOUSE, ShipmentStatus::OUT_FOR_DELIVERY));
        $this->assertTrue($machine->canTransition(ShipmentStatus::OUT_FOR_DELIVERY, ShipmentStatus::DELIVERED));
        $this->assertTrue($machine->canTransition(ShipmentStatus::OUT_FOR_DELIVERY, ShipmentStatus::RETURNED));
        $this->assertTrue($machine->canTransition(ShipmentStatus::RETURNED, ShipmentStatus::RECEIVED_IN_WAREHOUSE));
    }

    #[Test]
    public function it_rejects_illegal_transitions_with_unprocessable_status(): void
    {
        $machine = new ShipmentStateMachine;

        try {
            $machine->assertCanTransition(ShipmentStatus::CREATED, ShipmentStatus::DELIVERED);
            $this->fail('Expected InvalidStatusTransitionException was not thrown.');
        } catch (InvalidStatusTransitionException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertStringContainsString('Illegal shipment status transition', $exception->getMessage());
        }
    }
}
