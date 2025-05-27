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

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Bundle\ApiBundle\Validator\Constraints\ChosenShippingMethodEligibilityValidator;
use InvalidArgumentException;
use Sylius\Bundle\ApiBundle\Command\Checkout\ChooseShippingMethod;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Bundle\ApiBundle\Validator\Constraints\ChosenShippingMethodEligibility;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Core\Repository\ShipmentRepositoryInterface;
use Sylius\Component\Core\Repository\ShippingMethodRepositoryInterface;
use Sylius\Component\Shipping\Resolver\ShippingMethodsResolverInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ChosenShippingMethodEligibilityValidatorTest extends TestCase
{
    /** @var ShipmentRepositoryInterface|MockObject */
    private MockObject $shipmentRepositoryMock;
    /** @var ShippingMethodRepositoryInterface|MockObject */
    private MockObject $shippingMethodRepositoryMock;
    /** @var ShippingMethodsResolverInterface|MockObject */
    private MockObject $shippingMethodsResolverMock;
    /** @var ExecutionContextInterface|MockObject */
    private MockObject $executionContextMock;
    private ChosenShippingMethodEligibilityValidator $chosenShippingMethodEligibilityValidator;
    protected function setUp(): void
    {
        $this->shipmentRepositoryMock = $this->createMock(ShipmentRepositoryInterface::class);
        $this->shippingMethodRepositoryMock = $this->createMock(ShippingMethodRepositoryInterface::class);
        $this->shippingMethodsResolverMock = $this->createMock(ShippingMethodsResolverInterface::class);
        $this->executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->chosenShippingMethodEligibilityValidator = new ChosenShippingMethodEligibilityValidator($this->shipmentRepositoryMock, $this->shippingMethodRepositoryMock, $this->shippingMethodsResolverMock);
        $this->initialize($this->executionContextMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->chosenShippingMethodEligibilityValidator);
    }

    public function testThrowsAnExceptionIfConstraintIsNotAnInstanceOfChosenShippingMethodEligibility(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->chosenShippingMethodEligibilityValidator->validate(new ChooseShippingMethod('SHIPPING_METHOD_CODE', 123, 'ORDER_TOKEN'), final class() extends TestCase {
        });
    }

    public function testThrowsAnExceptionIfValueIsNotAnInstanceOfChooseShippingMethodCommand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->chosenShippingMethodEligibilityValidator->validate(new CompleteOrder('TOKEN'), new ChosenShippingMethodEligibility());
    }

    public function testAddsViolationIfChosenShippingMethodDoesNotMatchSupportedMethods(): void
    {
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ShippingMethodInterface|MockObject $differentShippingMethodMock */
        $differentShippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ShipmentInterface|MockObject $shipmentMock */
        $shipmentMock = $this->createMock(ShipmentInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var AddressInterface|MockObject $shippingAddressMock */
        $shippingAddressMock = $this->createMock(AddressInterface::class);
        $command = new ChooseShippingMethod(
            orderTokenValue: 'ORDER_TOKEN',
            shippingMethodCode: 'SHIPPING_METHOD_CODE',
            shipmentId: 123,
        );
        $this->shippingMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'SHIPPING_METHOD_CODE'])->willReturn($shippingMethodMock);
        $shippingMethodMock->expects($this->once())->method('getName')->willReturn('DHL');
        $this->shipmentRepositoryMock->expects($this->once())->method('find')->with('123')->willReturn($shipmentMock);
        $shipmentMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getShippingAddress')->willReturn($shippingAddressMock);
        $this->shippingMethodsResolverMock->expects($this->once())->method('getSupportedMethods')->with($shipmentMock)->willReturn([$differentShippingMethodMock]);
        $this->executionContextMock->expects($this->once())->method('addViolation')->with('sylius.shipping_method.not_available', ['%name%' => 'DHL'])
        ;
        $this->chosenShippingMethodEligibilityValidator->validate($command, new ChosenShippingMethodEligibility());
    }

    public function testDoesNotAddViolationIfChosenShippingMethodMatchesSupportedMethods(): void
    {
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ShipmentInterface|MockObject $shipmentMock */
        $shipmentMock = $this->createMock(ShipmentInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var AddressInterface|MockObject $shippingAddressMock */
        $shippingAddressMock = $this->createMock(AddressInterface::class);
        $command = new ChooseShippingMethod(
            orderTokenValue: 'ORDER_TOKEN',
            shippingMethodCode: 'SHIPPING_METHOD_CODE',
            shipmentId: 123,
        );
        $this->shippingMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'SHIPPING_METHOD_CODE'])->willReturn($shippingMethodMock);
        $this->shipmentRepositoryMock->expects($this->once())->method('find')->with('123')->willReturn($shipmentMock);
        $shipmentMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getShippingAddress')->willReturn($shippingAddressMock);
        $this->shippingMethodsResolverMock->expects($this->once())->method('getSupportedMethods')->with($shipmentMock)->willReturn([$shippingMethodMock]);
        $this->executionContextMock->expects($this->never())->method('addViolation')
        ;
        $this->chosenShippingMethodEligibilityValidator->validate($command, new ChosenShippingMethodEligibility());
    }

    public function testAddsAViolationIfGivenShippingMethodIsNull(): void
    {
        $command = new ChooseShippingMethod(
            orderTokenValue: 'ORDER_TOKEN',
            shippingMethodCode: 'SHIPPING_METHOD_CODE',
            shipmentId: 123,
        );
        $this->shippingMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'SHIPPING_METHOD_CODE'])->willReturn(null);
        $this->shipmentRepositoryMock->expects($this->never())->method('find')->with('123');
        $this->executionContextMock->expects($this->never())->method('addViolation')
        ;
        $this->executionContextMock->expects($this->once())->method('addViolation')->with('sylius.shipping_method.not_found', ['%code%' => 'SHIPPING_METHOD_CODE'])
        ;
        $this->chosenShippingMethodEligibilityValidator->validate($command, new ChosenShippingMethodEligibility());
    }

    public function testAddsViolationIfOrderIsNotAddressed(): void
    {
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ShipmentInterface|MockObject $shipmentMock */
        $shipmentMock = $this->createMock(ShipmentInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var AddressInterface|MockObject $shippingAddressMock */
        $shippingAddressMock = $this->createMock(AddressInterface::class);
        $command = new ChooseShippingMethod(
            orderTokenValue: 'ORDER_TOKEN',
            shippingMethodCode: 'SHIPPING_METHOD_CODE',
            shipmentId: 123,
        );
        $this->shippingMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'SHIPPING_METHOD_CODE'])->willReturn($shippingMethodMock);
        $this->shipmentRepositoryMock->expects($this->once())->method('find')->with('123')->willReturn($shipmentMock);
        $shipmentMock->expects($this->once())->method('getOrder')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getShippingAddress')->willReturn(null);
        $this->shippingMethodsResolverMock->expects($this->once())->method('getSupportedMethods')->with($shipmentMock)->willReturn([$shippingMethodMock]);
        $this->executionContextMock->expects($this->once())->method('addViolation')->with('sylius.shipping_method.shipping_address_not_found')
        ;
        $this->chosenShippingMethodEligibilityValidator->validate($command, new ChosenShippingMethodEligibility());
    }

    public function testAddsViolationIfOrderShipmentIsNotFound(): void
    {
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        $command = new ChooseShippingMethod(
            orderTokenValue: 'ORDER_TOKEN',
            shippingMethodCode: 'SHIPPING_METHOD_CODE',
            shipmentId: 123,
        );
        $this->shippingMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'SHIPPING_METHOD_CODE'])->willReturn($shippingMethodMock);
        $this->shipmentRepositoryMock->expects($this->once())->method('find')->with('123')->willReturn(null);
        $this->executionContextMock->expects($this->never())->method('addViolation')
        ;
        $this->executionContextMock->expects($this->never())->method('addViolation')
        ;
        $this->executionContextMock->expects($this->once())->method('addViolation')->with('sylius.shipment.not_found')
        ;
        $this->chosenShippingMethodEligibilityValidator->validate($command, new ChosenShippingMethodEligibility());
    }
}
