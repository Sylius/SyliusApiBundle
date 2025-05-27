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
use Sylius\Bundle\ApiBundle\Validator\Constraints\UpdateCartEmailNotAllowedValidator;
use InvalidArgumentException;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Bundle\ApiBundle\Command\Checkout\UpdateCart;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Bundle\ApiBundle\Validator\Constraints\UpdateCartEmailNotAllowed;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class UpdateCartEmailNotAllowedValidatorTest extends TestCase
{
    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;
    /** @var UserContextInterface|MockObject */
    private MockObject $userContextMock;
    private UpdateCartEmailNotAllowedValidator $updateCartEmailNotAllowedValidator;
    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->userContextMock = $this->createMock(UserContextInterface::class);
        $this->updateCartEmailNotAllowedValidator = new UpdateCartEmailNotAllowedValidator($this->orderRepositoryMock, $this->userContextMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->updateCartEmailNotAllowedValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotAnInstanceOfUpdateCart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->updateCartEmailNotAllowedValidator->validate(new CompleteOrder('token'), new UpdateCartEmailNotAllowed());
    }

    public function testThrowsAnExceptionIfConstraintIsNotAnInstanceOfUpdateCartEmailNotAllowed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->updateCartEmailNotAllowedValidator->validate(new UpdateCart('token'), final class() extends TestCase {
        });
    }

    public function testThrowsAnExceptionIfOrderIsNull(): void
    {
        $command = new UpdateCart(orderTokenValue: 'token');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'token'])->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->updateCartEmailNotAllowedValidator->validate($command, new UpdateCartEmailNotAllowed());
    }

    public function testDoesNotAddViolationIfTheCustomerOnTheOrderIsNull(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        $this->updateCartEmailNotAllowedValidator->initialize($executionContextMock);
        $command = new UpdateCart(email: 'shopuser@example.com', orderTokenValue: 'token');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'token'])->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getCustomer')->willReturn(null);
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn($shopUserMock);
        $executionContextMock->expects($this->never())->method('addViolation')->with('sylius.checkout.email.not_changeable');
        $this->updateCartEmailNotAllowedValidator->validate($command, new UpdateCartEmailNotAllowed());
    }

    public function testDoesNotAddViolationIfTheEmailIsTheSameAsTheOneInTheOrder(): void
    {
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        $this->updateCartEmailNotAllowedValidator->initialize($executionContextMock);
        $command = new UpdateCart(email: 'shopuser@example.com', orderTokenValue: 'token');
        $customerMock->expects($this->once())->method('getEmail')->willReturn('shopuser@example.com');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'token'])->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn($shopUserMock);
        $executionContextMock->expects($this->never())->method('addViolation')->with('sylius.checkout.email.not_changeable');
        $this->updateCartEmailNotAllowedValidator->validate($command, new UpdateCartEmailNotAllowed());
    }

    public function testAddsViolationIfTheUserIsLoggedInAndTheyTryToChangeTheEmail(): void
    {
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        $this->updateCartEmailNotAllowedValidator->initialize($executionContextMock);
        $command = new UpdateCart(email: 'changed_email@example.com', orderTokenValue: 'token');
        $shopUserMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'token'])->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn($shopUserMock);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.checkout.email.not_changeable');
        $this->updateCartEmailNotAllowedValidator->validate($command, new UpdateCartEmailNotAllowed());
    }

    public function testDoesNotAddViolationIfUserIsNotLoggedIn(): void
    {
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        $this->updateCartEmailNotAllowedValidator->initialize($executionContextMock);
        $command = new UpdateCart(email: 'customer@example.com', orderTokenValue: 'token');
        $shopUserMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'token'])->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn(null);
        $executionContextMock->expects($this->never())->method('addViolation')->with('sylius.checkout.email.not_changeable');
        $this->updateCartEmailNotAllowedValidator->validate($command, new UpdateCartEmailNotAllowed());
    }
}
