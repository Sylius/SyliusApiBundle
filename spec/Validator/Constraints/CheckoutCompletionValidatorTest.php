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
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Abstraction\StateMachine\Transition;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Bundle\ApiBundle\Validator\Constraints\CheckoutCompletion;
use Sylius\Bundle\ApiBundle\Validator\Constraints\CheckoutCompletionValidator;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class CheckoutCompletionValidatorTest extends TestCase
{
    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;

    /** @var StateMachineInterface|MockObject */
    private MockObject $stateMachineMock;

    private CheckoutCompletionValidator $checkoutCompletionValidator;

    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->stateMachineMock = $this->createMock(StateMachineInterface::class);
        $this->checkoutCompletionValidator = new CheckoutCompletionValidator($this->orderRepositoryMock, $this->stateMachineMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->checkoutCompletionValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotAnInstanceOfOrderTokenValueAwareInterface(): void
    {
        /** @var Constraint|MockObject $constraintMock */
        $constraintMock = $this->createMock(Constraint::class);
        $this->expectException(InvalidArgumentException::class);
        $this->checkoutCompletionValidator->validate('', $constraintMock);
    }

    public function testThrowsAnExceptionIfConstraintIsNotAnInstanceOfCheckoutCompletion(): void
    {
        /** @var Constraint|MockObject $constraintMock */
        $constraintMock = $this->createMock(Constraint::class);
        $this->expectException(InvalidArgumentException::class);
        $this->checkoutCompletionValidator->validate(new CompleteOrder('token'), $constraintMock);
    }

    public function testThrowsAnExceptionIfOrderWithGivenTokenValueDoesNotExist(): void
    {
        /** @var Constraint|MockObject $constraintMock */
        $constraintMock = $this->createMock(Constraint::class);
        $completeOrder = new CompleteOrder('xxx');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'xxx'])->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->checkoutCompletionValidator->validate($completeOrder, $constraintMock);
    }

    public function testDoesNothingIfOrderCanBeCompleted(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->checkoutCompletionValidator->initialize($executionContextMock);
        $completeOrder = new CompleteOrder('xxx');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'xxx'])->willReturn($orderMock);
        $this->stateMachineMock->expects($this->once())->method('can')->with($orderMock, 'sylius_order_checkout', OrderCheckoutTransitions::TRANSITION_COMPLETE)->willReturn(true);
        $executionContextMock->expects($this->never())->method('addViolation')->with($this->any())
        ;
        $this->checkoutCompletionValidator->validate($completeOrder, new CheckoutCompletion());
    }

    public function testAddsViolationIfOrderCannotBeCompleted(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->checkoutCompletionValidator->initialize($executionContextMock);
        $completeOrder = new CompleteOrder('xxx');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'xxx'])->willReturn($orderMock);
        $this->stateMachineMock->expects($this->once())->method('can')->with($orderMock, 'sylius_order_checkout', OrderCheckoutTransitions::TRANSITION_COMPLETE)->willReturn(false);
        $this->stateMachineMock->expects($this->once())->method('getEnabledTransitions')->with($orderMock, 'sylius_order_checkout')->willReturn([
            new Transition('some_possible_transition', [], []),
            new Transition('another_possible_transition', [], []),
        ]);
        $orderMock->expects($this->once())->method('getCheckoutState')->willReturn('some_state_that_does_not_allow_to_complete_order');
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.order.invalid_state_transition', [
            '%currentState%' => 'some_state_that_does_not_allow_to_complete_order',
            '%possibleTransitions%' => 'some_possible_transition, another_possible_transition',
        ])
        ;
        $this->checkoutCompletionValidator->validate($completeOrder, new CheckoutCompletion());
    }
}
