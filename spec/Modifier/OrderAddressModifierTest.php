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

namespace Tests\Sylius\Bundle\ApiBundle\Modifier;

use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\ApiBundle\Mapper\AddressMapperInterface;
use Sylius\Bundle\ApiBundle\Modifier\OrderAddressModifier;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;

final class OrderAddressModifierTest extends TestCase
{
    /** @var StateMachineInterface|MockObject */
    private MockObject $stateMachineMock;

    /** @var AddressMapperInterface|MockObject */
    private MockObject $addressMapperMock;

    private OrderAddressModifier $orderAddressModifier;

    protected function setUp(): void
    {
        $this->stateMachineMock = $this->createMock(StateMachineInterface::class);
        $this->addressMapperMock = $this->createMock(AddressMapperInterface::class);
        $this->orderAddressModifier = new OrderAddressModifier($this->stateMachineMock, $this->addressMapperMock);
    }

    public function testModifiesAddressesOfAnOrderWithoutProvidedShippingAddress(): void
    {
        /** @var AddressInterface|MockObject $billingAddressMock */
        $billingAddressMock = $this->createMock(AddressInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $orderMock->expects($this->once())->method('getTokenValue')->willReturn('ORDERTOKEN');
        $orderMock->expects($this->once())->method('getShippingAddress')->willReturn(null);
        $orderMock->expects($this->once())->method('getBillingAddress')->willReturn(null);
        $orderMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isShippingAddressInCheckoutRequired')->willReturn(false);
        $orderMock->expects($this->once())->method('setBillingAddress')->with($billingAddressMock);
        $orderMock->expects($this->once())->method('setShippingAddress')->with($this->isInstanceOf(AddressInterface::class));
        $this->stateMachineMock->expects($this->once())->method('can')->with($orderMock, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_ADDRESS)
            ->willReturn(true)
        ;
        $this->stateMachineMock->expects($this->once())->method('apply')->with($orderMock, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_ADDRESS)
        ;
        $this->orderAddressModifier->modify($orderMock, $billingAddressMock, null);
    }

    public function testModifiesAddressesOfAnOrderWithoutProvidedBillingAddress(): void
    {
        /** @var AddressInterface|MockObject $shippingAddressMock */
        $shippingAddressMock = $this->createMock(AddressInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $orderMock->expects($this->once())->method('getTokenValue')->willReturn('ORDERTOKEN');
        $orderMock->expects($this->once())->method('getShippingAddress')->willReturn(null);
        $orderMock->expects($this->once())->method('getBillingAddress')->willReturn(null);
        $orderMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isShippingAddressInCheckoutRequired')->willReturn(true);
        $orderMock->expects($this->once())->method('setShippingAddress')->with($shippingAddressMock);
        $orderMock->expects($this->once())->method('setBillingAddress')->with($this->isInstanceOf(AddressInterface::class));
        $this->stateMachineMock->expects($this->once())->method('can')->with($orderMock, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_ADDRESS)
            ->willReturn(true)
        ;
        $this->stateMachineMock->expects($this->once())->method('apply')->with($orderMock, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_ADDRESS)
        ;
        $this->orderAddressModifier->modify($orderMock, null, $shippingAddressMock);
    }

    public function testModifiesAddressesOfAnOrder(): void
    {
        /** @var AddressInterface|MockObject $billingAddressMock */
        $billingAddressMock = $this->createMock(AddressInterface::class);
        /** @var AddressInterface|MockObject $shippingAddressMock */
        $shippingAddressMock = $this->createMock(AddressInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $orderMock->expects($this->once())->method('getTokenValue')->willReturn('ORDERTOKEN');
        $orderMock->expects($this->once())->method('getShippingAddress')->willReturn(null);
        $orderMock->expects($this->once())->method('getBillingAddress')->willReturn(null);
        $orderMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isShippingAddressInCheckoutRequired')->willReturn(false);
        $orderMock->expects($this->once())->method('setBillingAddress')->with($billingAddressMock);
        $orderMock->expects($this->once())->method('setShippingAddress')->with($shippingAddressMock);
        $this->stateMachineMock->expects($this->once())->method('can')->with($orderMock, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_ADDRESS)
            ->willReturn(true)
        ;
        $this->stateMachineMock->expects($this->once())->method('apply')->with($orderMock, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_ADDRESS)
        ;
        $this->orderAddressModifier->modify($orderMock, $billingAddressMock, $shippingAddressMock);
    }

    public function testUpdatesOrderAddresses(): void
    {
        /** @var AddressInterface|MockObject $newBillingAddressMock */
        $newBillingAddressMock = $this->createMock(AddressInterface::class);
        /** @var AddressInterface|MockObject $newShippingAddressMock */
        $newShippingAddressMock = $this->createMock(AddressInterface::class);
        /** @var AddressInterface|MockObject $oldBillingAddressMock */
        $oldBillingAddressMock = $this->createMock(AddressInterface::class);
        /** @var AddressInterface|MockObject $oldShippingAddressMock */
        $oldShippingAddressMock = $this->createMock(AddressInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $orderMock->expects($this->once())->method('getTokenValue')->willReturn('ORDERTOKEN');
        $orderMock->expects($this->once())->method('getBillingAddress')->willReturn($oldBillingAddressMock);
        $orderMock->expects($this->once())->method('getShippingAddress')->willReturn($oldShippingAddressMock);
        $orderMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isShippingAddressInCheckoutRequired')->willReturn(false);
        $this->addressMapperMock->expects($this->once())->method('mapExisting')->with($oldBillingAddressMock, $newBillingAddressMock)->willReturn($oldBillingAddressMock);
        $this->addressMapperMock->expects($this->once())->method('mapExisting')->with($oldShippingAddressMock, $newShippingAddressMock)->willReturn($oldShippingAddressMock);
        $orderMock->expects($this->once())->method('setBillingAddress')->with($oldBillingAddressMock);
        $orderMock->expects($this->once())->method('setShippingAddress')->with($oldShippingAddressMock);
        $this->stateMachineMock->expects($this->once())->method('can')->with($orderMock, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_ADDRESS)
            ->willReturn(true)
        ;
        $this->stateMachineMock->expects($this->once())->method('apply')->with($orderMock, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_ADDRESS)
        ;
        $this->orderAddressModifier->modify($orderMock, $newBillingAddressMock, $newShippingAddressMock);
    }

    public function testThrowsAnExceptionIfOrderCannotBeAddressed(): void
    {
        /** @var AddressInterface|MockObject $billingAddressMock */
        $billingAddressMock = $this->createMock(AddressInterface::class);
        /** @var AddressInterface|MockObject $shippingAddressMock */
        $shippingAddressMock = $this->createMock(AddressInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->stateMachineMock->expects($this->once())->method('can')->with($orderMock, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_ADDRESS)
            ->willReturn(false)
        ;
        $this->expectException(LogicException::class);
        $this->orderAddressModifier->modify($orderMock, $billingAddressMock, $shippingAddressMock);
    }
}
