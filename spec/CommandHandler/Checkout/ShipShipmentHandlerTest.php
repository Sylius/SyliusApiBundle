<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Sylius\Bundle\ApiBundle\CommandHandler\Checkout;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use spec\Sylius\Bundle\ApiBundle\CommandHandler\MessageHandlerAttributeTrait;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\ApiBundle\Command\Checkout\SendShipmentConfirmationEmail;
use Sylius\Bundle\ApiBundle\Command\Checkout\ShipShipment;
use Sylius\Bundle\ApiBundle\CommandHandler\Checkout\ShipShipmentHandler;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Repository\ShipmentRepositoryInterface;
use Sylius\Component\Shipping\ShipmentTransitions;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final class ShipShipmentHandlerTest extends TestCase
{
    /** @var ShipmentRepositoryInterface|MockObject */
    private MockObject $shipmentRepositoryMock;

    /** @var StateMachineInterface|MockObject */
    private MockObject $stateMachineMock;

    /** @var MessageBusInterface|MockObject */
    private MockObject $eventBusMock;

    private ShipShipmentHandler $shipShipmentHandler;

    use MessageHandlerAttributeTrait;

    protected function setUp(): void
    {
        $this->shipmentRepositoryMock = $this->createMock(ShipmentRepositoryInterface::class);
        $this->stateMachineMock = $this->createMock(StateMachineInterface::class);
        $this->eventBusMock = $this->createMock(MessageBusInterface::class);
        $this->shipShipmentHandler = new ShipShipmentHandler($this->shipmentRepositoryMock, $this->stateMachineMock, $this->eventBusMock);
    }

    public function testHandlesShippingWithoutTrackingNumber(): void
    {
        /** @var ShipmentInterface|MockObject $shipmentMock */
        $shipmentMock = $this->createMock(ShipmentInterface::class);
        $shipShipment = new ShipShipment(shipmentId: 123);
        $this->shipmentRepositoryMock->expects($this->once())->method('find')->with(123)->willReturn($shipmentMock);
        $shipmentMock->expects($this->never())->method('setTracking')->with(null);
        $this->stateMachineMock->expects($this->once())->method('can')->with($shipmentMock, ShipmentTransitions::GRAPH, ShipmentTransitions::TRANSITION_SHIP)->willReturn(true);
        $this->stateMachineMock->expects($this->once())->method('apply')->with($shipmentMock, ShipmentTransitions::GRAPH, ShipmentTransitions::TRANSITION_SHIP);
        $sendShipmentConfirmationEmail = new SendShipmentConfirmationEmail(123);
        $this->eventBusMock->expects($this->once())->method('dispatch')->with($sendShipmentConfirmationEmail, [new DispatchAfterCurrentBusStamp()])
            ->willReturn(new Envelope($sendShipmentConfirmationEmail))
        ;
        $this->assertSame($shipmentMock, $this($shipShipment));
    }

    public function testHandlesShippingWithTrackingNumber(): void
    {
        /** @var ShipmentInterface|MockObject $shipmentMock */
        $shipmentMock = $this->createMock(ShipmentInterface::class);
        $shipShipment = new ShipShipment(shipmentId: 123, trackingCode: 'TRACK');
        $this->shipmentRepositoryMock->expects($this->once())->method('find')->with(123)->willReturn($shipmentMock);
        $shipmentMock->expects($this->once())->method('setTracking')->with('TRACK');
        $this->stateMachineMock->expects($this->once())->method('can')->with($shipmentMock, ShipmentTransitions::GRAPH, ShipmentTransitions::TRANSITION_SHIP)->willReturn(true);
        $this->stateMachineMock->expects($this->once())->method('apply')->with($shipmentMock, ShipmentTransitions::GRAPH, ShipmentTransitions::TRANSITION_SHIP);
        $sendShipmentConfirmationEmail = new SendShipmentConfirmationEmail(123);
        $this->eventBusMock->expects($this->once())->method('dispatch')->with($sendShipmentConfirmationEmail, [new DispatchAfterCurrentBusStamp()])
            ->willReturn(new Envelope($sendShipmentConfirmationEmail))
        ;
        $this->assertSame($shipmentMock, $this($shipShipment));
    }

    public function testThrowsAnExceptionIfShipmentDoesNotExist(): void
    {
        $shipShipment = new ShipShipment(shipmentId: 123, trackingCode: 'TRACK');
        $this->shipmentRepositoryMock->expects($this->once())->method('find')->with(123)->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->shipShipmentHandler->__invoke($shipShipment);
    }

    public function testThrowsAnExceptionIfShipmentCannotBeShipped(): void
    {
        /** @var ShipmentInterface|MockObject $shipmentMock */
        $shipmentMock = $this->createMock(ShipmentInterface::class);
        $shipShipment = new ShipShipment(shipmentId: 123, trackingCode: 'TRACK');
        $this->shipmentRepositoryMock->expects($this->once())->method('find')->with(123)->willReturn($shipmentMock);
        $shipmentMock->expects($this->once())->method('setTracking')->with('TRACK');
        $this->stateMachineMock->expects($this->once())->method('can')->with($shipmentMock, ShipmentTransitions::GRAPH, ShipmentTransitions::TRANSITION_SHIP)->willReturn(false);
        $this->expectException(InvalidArgumentException::class);
        $this->shipShipmentHandler->__invoke($shipShipment);
    }
}
