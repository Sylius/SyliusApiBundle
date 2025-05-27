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

namespace Tests\Sylius\Bundle\ApiBundle\Applicator;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\ApiBundle\Applicator\PaymentStateMachineTransitionApplicator;
use Sylius\Bundle\ApiBundle\Exception\StateMachineTransitionFailedException;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;

final class PaymentStateMachineTransitionApplicatorTest extends TestCase
{
    /** @var StateMachineInterface|MockObject */
    private MockObject $stateMachineMock;

    private PaymentStateMachineTransitionApplicator $paymentStateMachineTransitionApplicator;

    protected function setUp(): void
    {
        $this->stateMachineMock = $this->createMock(StateMachineInterface::class);
        $this->paymentStateMachineTransitionApplicator = new PaymentStateMachineTransitionApplicator($this->stateMachineMock);
    }

    public function testCompletesPayment(): void
    {
        /** @var PaymentInterface|MockObject $paymentMock */
        $paymentMock = $this->createMock(PaymentInterface::class);
        $this->stateMachineMock->expects($this->once())->method('can')->with($paymentMock, PaymentTransitions::GRAPH, PaymentTransitions::TRANSITION_COMPLETE)->willReturn(true);
        $this->stateMachineMock->expects($this->once())->method('apply')->with($paymentMock, PaymentTransitions::GRAPH, PaymentTransitions::TRANSITION_COMPLETE);
        $this->paymentStateMachineTransitionApplicator->complete($paymentMock);
    }

    public function testThrowsExceptionIfCannotCompletePayment(): void
    {
        /** @var PaymentInterface|MockObject $paymentMock */
        $paymentMock = $this->createMock(PaymentInterface::class);
        $this->stateMachineMock->expects($this->once())->method('can')->with($paymentMock, PaymentTransitions::GRAPH, PaymentTransitions::TRANSITION_COMPLETE)->willReturn(false);
        $this->stateMachineMock->expects($this->never())->method('apply')->with($paymentMock, PaymentTransitions::GRAPH, PaymentTransitions::TRANSITION_COMPLETE);
        $this->expectException(StateMachineTransitionFailedException::class);
        $this->paymentStateMachineTransitionApplicator->complete($paymentMock);
    }

    public function testRefundsPayment(): void
    {
        /** @var PaymentInterface|MockObject $paymentMock */
        $paymentMock = $this->createMock(PaymentInterface::class);
        $this->stateMachineMock->expects($this->once())->method('can')->with($paymentMock, PaymentTransitions::GRAPH, PaymentTransitions::TRANSITION_REFUND)->willReturn(true);
        $this->stateMachineMock->expects($this->once())->method('apply')->with($paymentMock, PaymentTransitions::GRAPH, PaymentTransitions::TRANSITION_REFUND);
        $this->paymentStateMachineTransitionApplicator->refund($paymentMock);
    }

    public function testThrowsAnExceptionIfCannotRefundPayment(): void
    {
        /** @var PaymentInterface|MockObject $paymentMock */
        $paymentMock = $this->createMock(PaymentInterface::class);
        $this->stateMachineMock->expects($this->once())->method('can')->with($paymentMock, PaymentTransitions::GRAPH, PaymentTransitions::TRANSITION_REFUND)->willReturn(false);
        $this->stateMachineMock->expects($this->never())->method('apply')->with($paymentMock, PaymentTransitions::GRAPH, PaymentTransitions::TRANSITION_REFUND);
        $this->expectException(StateMachineTransitionFailedException::class);
        $this->paymentStateMachineTransitionApplicator->refund($paymentMock);
    }
}
