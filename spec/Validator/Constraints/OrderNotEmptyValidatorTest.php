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
use Sylius\Bundle\ApiBundle\Validator\Constraints\OrderNotEmptyValidator;
use InvalidArgumentException;
use Doctrine\Common\Collections\ArrayCollection;
use Sylius\Bundle\ApiBundle\Command\Cart\RemoveItemFromCart;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Bundle\ApiBundle\Command\Checkout\UpdateCart;
use Sylius\Bundle\ApiBundle\Validator\Constraints\OrderNotEmpty;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class OrderNotEmptyValidatorTest extends TestCase
{
    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;
    private OrderNotEmptyValidator $orderNotEmptyValidator;
    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->orderNotEmptyValidator = new OrderNotEmptyValidator($this->orderRepositoryMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->orderNotEmptyValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotAnInstanceOfCompleteOrderOrUpdateCart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->orderNotEmptyValidator->validate(new RemoveItemFromCart('token', 1), new OrderNotEmpty());
    }

    public function testThrowsAnExceptionIfConstraintIsNotAnInstanceOfOrderNotEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->orderNotEmptyValidator->validate(new UpdateCart('token'), final class() extends TestCase {
        });
    }

    public function testThrowsAnExceptionIfOrderIsNull(): void
    {
        $value = new CompleteOrder(orderTokenValue: 'token');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'token'])->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->orderNotEmptyValidator->validate($value, new OrderNotEmpty());
    }

    public function testAddsViolationIfTheOrderHasNoItems(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->orderNotEmptyValidator->initialize($executionContextMock);
        $value = new CompleteOrder(orderTokenValue: 'token');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'token'])->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getItems')->willReturn(new ArrayCollection());
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.order.not_empty');
        $this->orderNotEmptyValidator->validate($value, new OrderNotEmpty());
    }

    public function testDoesNotAddViolationIfTheOrderHasItems(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->orderNotEmptyValidator->initialize($executionContextMock);
        $value = new UpdateCart(orderTokenValue: 'token');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'token'])->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getItems')->willReturn(new ArrayCollection([$orderItemMock]));
        $executionContextMock->expects($this->never())->method('addViolation')->with('sylius.order.not_empty');
        $this->orderNotEmptyValidator->validate($value, new OrderNotEmpty());
    }
}
