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

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Command\Account\ChangePaymentMethod;
use Sylius\Bundle\ApiBundle\Validator\Constraints\CanPaymentMethodBeChanged;
use Sylius\Bundle\ApiBundle\Validator\Constraints\CanPaymentMethodBeChangedValidator;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class CanPaymentMethodBeChangedValidatorTest extends TestCase
{
    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;

    /** @var ExecutionContextInterface|MockObject */
    private MockObject $executionContextMock;

    private CanPaymentMethodBeChangedValidator $canPaymentMethodBeChangedValidator;

    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->canPaymentMethodBeChangedValidator = new CanPaymentMethodBeChangedValidator($this->orderRepositoryMock);
        $this->initialize($this->executionContextMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->canPaymentMethodBeChangedValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotChangePaymentMethodCommand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->canPaymentMethodBeChangedValidator->validate('', new CanPaymentMethodBeChanged());
    }

    public function testThrowsAnExceptionIfConstraintIsNotAnInstanceOfCannotChangePaymentMethodForCancelledOrder(): void
    {
        /** @var Constraint|MockObject $constraintMock */
        $constraintMock = $this->createMock(Constraint::class);
        $this->expectException(InvalidArgumentException::class);
        $this->canPaymentMethodBeChangedValidator->validate(new ChangePaymentMethod('code', 123, 'ORDER_TOKEN'), $constraintMock);
    }

    public function testThrowsAnExceptionIfOrderIsNull(): void
    {
        $command = new ChangePaymentMethod(
            orderTokenValue: 'ORDER_TOKEN',
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            paymentId: 123,
        );
        $this->orderRepositoryMock->expects($this->once())->method('findOneByTokenValue')->with('ORDER_TOKEN')->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->canPaymentMethodBeChangedValidator->validate($command, new CanPaymentMethodBeChanged());
    }

    public function testAddsViolationIfOrderIsCancelled(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $command = new ChangePaymentMethod(
            orderTokenValue: 'ORDER_TOKEN',
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            paymentId: 123,
        );
        $this->orderRepositoryMock->expects($this->once())->method('findOneByTokenValue')->with('ORDER_TOKEN')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getState')->willReturn(OrderInterface::STATE_CANCELLED);
        $this->executionContextMock->expects($this->once())->method('addViolation')->with('sylius.payment_method.cannot_change_payment_method_for_cancelled_order')
        ;
        $this->canPaymentMethodBeChangedValidator->validate($command, new CanPaymentMethodBeChanged());
    }

    public function testDoesNothingIfOrderIsNotCancelled(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $command = new ChangePaymentMethod(
            orderTokenValue: 'ORDER_TOKEN',
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            paymentId: 123,
        );
        $this->orderRepositoryMock->expects($this->once())->method('findOneByTokenValue')->with('ORDER_TOKEN')->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getState')->willReturn(OrderInterface::STATE_NEW);
        $this->executionContextMock->expects($this->never())->method('addViolation')->with('sylius.payment_method.cannot_change_payment_method_for_cancelled_order')
        ;
        $this->canPaymentMethodBeChangedValidator->validate($command, new CanPaymentMethodBeChanged());
    }
}
