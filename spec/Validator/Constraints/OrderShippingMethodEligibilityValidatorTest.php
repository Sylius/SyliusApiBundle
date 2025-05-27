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

namespace Tests\Sylius\Bundle\ApiBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Bundle\ApiBundle\Validator\Constraints\OrderShippingMethodEligibility;
use Sylius\Bundle\ApiBundle\Validator\Constraints\OrderShippingMethodEligibilityValidator;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Shipping\Checker\Eligibility\ShippingMethodEligibilityCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class OrderShippingMethodEligibilityValidatorTest extends TestCase
{
    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;

    /** @var ShippingMethodEligibilityCheckerInterface|MockObject */
    private MockObject $eligibilityCheckerMock;

    private OrderShippingMethodEligibilityValidator $orderShippingMethodEligibilityValidator;

    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->eligibilityCheckerMock = $this->createMock(ShippingMethodEligibilityCheckerInterface::class);
        $this->orderShippingMethodEligibilityValidator = new OrderShippingMethodEligibilityValidator($this->orderRepositoryMock, $this->eligibilityCheckerMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->orderShippingMethodEligibilityValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotInstanceOfCompleteOrder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->orderShippingMethodEligibilityValidator->validate('', new OrderShippingMethodEligibility());
    }

    public function testThrowsAnExceptionIfConstraintIsNotInstanceOfOrderShippingMethodEligibility(): void
    {
        /** @var Constraint|MockObject $constraintMock */
        $constraintMock = $this->createMock(Constraint::class);
        $this->expectException(InvalidArgumentException::class);
        $this->orderShippingMethodEligibilityValidator->validate(new CompleteOrder('token'), $constraintMock);
    }

    public function testThrowsAnExceptionIfOrderIsNull(): void
    {
        $constraint = new OrderShippingMethodEligibility();
        $value = new CompleteOrder(orderTokenValue: 'token');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'token'])->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->orderShippingMethodEligibilityValidator->validate($value, $constraint);
    }

    public function testAddsAViolationForEveryNotAvailableShippingMethodAttachedToTheOrder(): void
    {
        /** @var ExecutionContextInterface|MockObject $contextMock */
        $contextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ShipmentInterface|MockObject $shipmentOneMock */
        $shipmentOneMock = $this->createMock(ShipmentInterface::class);
        /** @var ShipmentInterface|MockObject $shipmentTwoMock */
        $shipmentTwoMock = $this->createMock(ShipmentInterface::class);
        /** @var ShippingMethodInterface|MockObject $shippingMethodOneMock */
        $shippingMethodOneMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ShippingMethodInterface|MockObject $shippingMethodTwoMock */
        $shippingMethodTwoMock = $this->createMock(ShippingMethodInterface::class);
        /** @var Collection|MockObject $channelsCollectionOneMock */
        $channelsCollectionOneMock = $this->createMock(Collection::class);
        /** @var Collection|MockObject $channelsCollectionTwoMock */
        $channelsCollectionTwoMock = $this->createMock(Collection::class);
        $this->orderShippingMethodEligibilityValidator->initialize($contextMock);
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'ORDERTOKENVALUE'])->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getShipments')->willReturn(new ArrayCollection([$shipmentOneMock, $shipmentTwoMock]));
        $orderMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $shipmentOneMock->expects($this->once())->method('getMethod')->willReturn($shippingMethodOneMock);
        $shipmentTwoMock->expects($this->once())->method('getMethod')->willReturn($shippingMethodTwoMock);
        $shippingMethodOneMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $shippingMethodTwoMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $shippingMethodOneMock->expects($this->once())->method('getChannels')->willReturn($channelsCollectionOneMock);
        $shippingMethodTwoMock->expects($this->once())->method('getChannels')->willReturn($channelsCollectionTwoMock);
        $shippingMethodOneMock->expects($this->once())->method('getName')->willReturn('Shipping method one');
        $shippingMethodTwoMock->expects($this->once())->method('getName')->willReturn('Shipping method two');
        $channelsCollectionOneMock->expects($this->once())->method('contains')->with($channelMock)->willReturn(true);
        $channelsCollectionTwoMock->expects($this->once())->method('contains')->with($channelMock)->willReturn(false);
        $contextMock->expects($this->exactly(2))->method('addViolation')->willReturnMap([['sylius.order.shipping_method_not_available', ['%shippingMethodName%' => 'Shipping method one']], ['sylius.order.shipping_method_not_available', ['%shippingMethodName%' => 'Shipping method two']]]);
        $this->orderShippingMethodEligibilityValidator->validate(new CompleteOrder('ORDERTOKENVALUE'), new OrderShippingMethodEligibility());
    }

    public function testDoesNotAddViolationIfAllShippingMethodsAreAvailable(): void
    {
        /** @var ExecutionContextInterface|MockObject $contextMock */
        $contextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ShipmentInterface|MockObject $shipmentOneMock */
        $shipmentOneMock = $this->createMock(ShipmentInterface::class);
        /** @var ShipmentInterface|MockObject $shipmentTwoMock */
        $shipmentTwoMock = $this->createMock(ShipmentInterface::class);
        /** @var ShippingMethodInterface|MockObject $shippingMethodOneMock */
        $shippingMethodOneMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ShippingMethodInterface|MockObject $shippingMethodTwoMock */
        $shippingMethodTwoMock = $this->createMock(ShippingMethodInterface::class);
        /** @var Collection|MockObject $channelsCollectionOneMock */
        $channelsCollectionOneMock = $this->createMock(Collection::class);
        /** @var Collection|MockObject $channelsCollectionTwoMock */
        $channelsCollectionTwoMock = $this->createMock(Collection::class);
        $this->orderShippingMethodEligibilityValidator->initialize($contextMock);
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'ORDERTOKENVALUE'])->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getShipments')->willReturn(new ArrayCollection([$shipmentOneMock, $shipmentTwoMock]));
        $orderMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $shipmentOneMock->expects($this->once())->method('getMethod')->willReturn($shippingMethodOneMock);
        $shipmentTwoMock->expects($this->once())->method('getMethod')->willReturn($shippingMethodTwoMock);
        $shippingMethodOneMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $shippingMethodTwoMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $shippingMethodOneMock->expects($this->once())->method('getChannels')->willReturn($channelsCollectionOneMock);
        $shippingMethodTwoMock->expects($this->once())->method('getChannels')->willReturn($channelsCollectionTwoMock);
        $shippingMethodOneMock->expects($this->once())->method('getName')->willReturn('Shipping method one');
        $shippingMethodTwoMock->expects($this->once())->method('getName')->willReturn('Shipping method two');
        $channelsCollectionOneMock->expects($this->once())->method('contains')->with($channelMock)->willReturn(true);
        $channelsCollectionTwoMock->expects($this->once())->method('contains')->with($channelMock)->willReturn(true);
        $contextMock->expects($this->exactly(2))->method('addViolation')->willReturnMap([['sylius.order.shipping_method_not_available', ['%shippingMethodName%' => 'Shipping method one']], ['sylius.order.shipping_method_not_available', ['%shippingMethodName%' => 'Shipping method two']]]);
        $this->eligibilityCheckerMock->expects($this->once())->method('isEligible')->with($shipmentOneMock, $shippingMethodOneMock)->willReturn(true);
        $this->eligibilityCheckerMock->expects($this->once())->method('isEligible')->with($shipmentTwoMock, $shippingMethodTwoMock)->willReturn(true);
        $this->orderShippingMethodEligibilityValidator->validate(new CompleteOrder('ORDERTOKENVALUE'), new OrderShippingMethodEligibility());
    }

    public function testAddsViolationIfShipmentDoesNotMatchWithShippingMethod(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ShipmentInterface|MockObject $shipmentMock */
        $shipmentMock = $this->createMock(ShipmentInterface::class);
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var Collection|MockObject $channelsCollectionMock */
        $channelsCollectionMock = $this->createMock(Collection::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->orderShippingMethodEligibilityValidator->initialize($executionContextMock);
        $constraint = new OrderShippingMethodEligibility();
        $value = new CompleteOrder(orderTokenValue: 'token');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'token'])->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getShipments')->willReturn(new ArrayCollection([$shipmentMock]));
        $orderMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $shipmentMock->expects($this->once())->method('getMethod')->willReturn($shippingMethodMock);
        $this->eligibilityCheckerMock->expects($this->once())->method('isEligible')->with($shipmentMock, $shippingMethodMock)->willReturn(false);
        $shippingMethodMock->expects($this->once())->method('getName')->willReturn('InPost');
        $shippingMethodMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $shippingMethodMock->expects($this->once())->method('getChannels')->willReturn($channelsCollectionMock);
        $channelsCollectionMock->expects($this->once())->method('contains')->with($channelMock)->willReturn(true);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.order.shipping_method_eligibility', ['%shippingMethodName%' => 'InPost'])
        ;
        $this->orderShippingMethodEligibilityValidator->validate($value, $constraint);
    }

    public function testDoesNotAddAViolationIfShipmentMatchesWithShippingMethod(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ShipmentInterface|MockObject $shipmentMock */
        $shipmentMock = $this->createMock(ShipmentInterface::class);
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var Collection|MockObject $channelsCollectionMock */
        $channelsCollectionMock = $this->createMock(Collection::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->orderShippingMethodEligibilityValidator->initialize($executionContextMock);
        $constraint = new OrderShippingMethodEligibility();
        $value = new CompleteOrder(orderTokenValue: 'token');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'token'])->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getShipments')->willReturn(new ArrayCollection([$shipmentMock]));
        $orderMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $shipmentMock->expects($this->once())->method('getMethod')->willReturn($shippingMethodMock);
        $this->eligibilityCheckerMock->expects($this->once())->method('isEligible')->with($shipmentMock, $shippingMethodMock)->willReturn(true);
        $shippingMethodMock->expects($this->once())->method('getName')->willReturn('InPost');
        $shippingMethodMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $shippingMethodMock->expects($this->once())->method('getChannels')->willReturn($channelsCollectionMock);
        $channelsCollectionMock->expects($this->once())->method('contains')->with($channelMock)->willReturn(true);
        $executionContextMock->expects($this->never())->method('addViolation')->with('sylius.order.shipping_method_eligibility', ['%shippingMethodName%' => 'InPost'])
        ;
        $this->orderShippingMethodEligibilityValidator->validate($value, $constraint);
    }
}
