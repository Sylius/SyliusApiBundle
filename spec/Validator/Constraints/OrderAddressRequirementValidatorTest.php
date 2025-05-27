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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Command\Checkout\UpdateCart;
use Sylius\Bundle\ApiBundle\Exception\ChannelNotFoundException;
use Sylius\Bundle\ApiBundle\Validator\Constraints\OrderAddressRequirement;
use Sylius\Bundle\ApiBundle\Validator\Constraints\OrderAddressRequirementValidator;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class OrderAddressRequirementValidatorTest extends TestCase
{
    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;

    /** @var ExecutionContextInterface|MockObject */
    private MockObject $contextMock;

    private OrderAddressRequirementValidator $orderAddressRequirementValidator;

    public const MESSAGE = 'sylius.order.address_requirement';

    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->contextMock = $this->createMock(ExecutionContextInterface::class);
        $this->orderAddressRequirementValidator = new OrderAddressRequirementValidator($this->orderRepositoryMock);
        $this->initialize($this->contextMock);
    }

    public function testThrowsAnExceptionIfConstraintIsNotAnInstanceOfOrderAddressRequirement(): void
    {
        /** @var Constraint|MockObject $constraintMock */
        $constraintMock = $this->createMock(Constraint::class);
        $this->expectException(UnexpectedTypeException::class);
        $this->orderAddressRequirementValidator->validate('product_code', $constraintMock);
    }

    public function testThrowsAnExceptionIfValueIsNotAnInstanceOfUpdateCart(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->expectException(UnexpectedValueException::class);
        $this->orderAddressRequirementValidator->validate($orderMock, new OrderAddressRequirement());
    }

    public function testDoesNothingIfBillingAndShippingAddressesAreNotProvided(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $updateCart = new UpdateCart('token');
        $this->orderAddressRequirementValidator->validate($updateCart, new OrderAddressRequirement());
        $this->contextMock->expects($this->once())->method('addViolation')->with(self::MESSAGE, ['%addressName%' => 'billingAddress'])->shouldNotHaveBeenCalled();
        $this->contextMock->expects($this->once())->method('addViolation')->with(self::MESSAGE, ['%addressName%' => 'shippingAddress'])->shouldNotHaveBeenCalled();
    }

    public function testThrowsAnExceptionIfOrderIsNotFound(): void
    {
        /** @var AddressInterface|MockObject $billingAddressMock */
        $billingAddressMock = $this->createMock(AddressInterface::class);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn(null);
        $updateCart = new UpdateCart(billingAddress: $billingAddressMock, orderTokenValue: 'TOKEN');
        $this->expectException(ChannelNotFoundException::class);
        $this->orderAddressRequirementValidator->validate($updateCart, new OrderAddressRequirement());
    }

    public function testThrowsAnExceptionIfOrderDoesNotHaveChannel(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var AddressInterface|MockObject $billingAddressMock */
        $billingAddressMock = $this->createMock(AddressInterface::class);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getChannel')->willReturn(null);
        $updateCart = new UpdateCart(billingAddress: $billingAddressMock, orderTokenValue: 'TOKEN');
        $this->expectException(ChannelNotFoundException::class);
        $this->orderAddressRequirementValidator->validate($updateCart, new OrderAddressRequirement());
    }

    public function testDoesNothingIfShippingAddressIsRequiredAndProvided(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var AddressInterface|MockObject $shippingAddressMock */
        $shippingAddressMock = $this->createMock(AddressInterface::class);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isShippingAddressInCheckoutRequired')->willReturn(true);
        $updateCart = new UpdateCart(shippingAddress: $shippingAddressMock, orderTokenValue: 'TOKEN');
        $this->orderAddressRequirementValidator->validate($updateCart, new OrderAddressRequirement());
        $this->contextMock->expects($this->once())->method('addViolation')->with(self::MESSAGE, ['%addressName%' => 'shippingAddress'])->shouldNotHaveBeenCalled();
    }

    public function testDoesNothingIfBillingAddressIsRequiredAndProvided(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var AddressInterface|MockObject $billingAddressMock */
        $billingAddressMock = $this->createMock(AddressInterface::class);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isShippingAddressInCheckoutRequired')->willReturn(false);
        $updateCart = new UpdateCart(billingAddress: $billingAddressMock, orderTokenValue: 'TOKEN');
        $this->orderAddressRequirementValidator->validate($updateCart, new OrderAddressRequirement());
        $this->contextMock->expects($this->once())->method('addViolation')->with(self::MESSAGE, ['%addressName%' => 'billingAddress'])->shouldNotHaveBeenCalled();
    }

    public function testAddsViolationIfShippingAddressIsRequiredButNotProvided(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var AddressInterface|MockObject $billingAddressMock */
        $billingAddressMock = $this->createMock(AddressInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isShippingAddressInCheckoutRequired')->willReturn(true);
        $updateCart = new UpdateCart(billingAddress: $billingAddressMock, orderTokenValue: 'TOKEN');
        $this->orderAddressRequirementValidator->validate($updateCart, new OrderAddressRequirement());
        $this->contextMock->expects($this->once())->method('addViolation')->with(self::MESSAGE, ['%addressName%' => 'shipping address'])->shouldHaveBeenCalled();
    }

    public function testAddsViolationIfBillingAddressIsRequiredButNotProvided(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var AddressInterface|MockObject $shippingAddressMock */
        $shippingAddressMock = $this->createMock(AddressInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isShippingAddressInCheckoutRequired')->willReturn(false);
        $updateCart = new UpdateCart(shippingAddress: $shippingAddressMock, orderTokenValue: 'TOKEN');
        $this->orderAddressRequirementValidator->validate($updateCart, new OrderAddressRequirement());
        $this->contextMock->expects($this->once())->method('addViolation')->with(self::MESSAGE, ['%addressName%' => 'billing address'])->shouldHaveBeenCalled();
    }
}
