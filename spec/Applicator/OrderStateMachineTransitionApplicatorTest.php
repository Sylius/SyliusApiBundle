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
use Sylius\Bundle\ApiBundle\Applicator\OrderStateMachineTransitionApplicator;
use Sylius\Bundle\ApiBundle\Exception\StateMachineTransitionFailedException;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\OrderTransitions;

final class OrderStateMachineTransitionApplicatorTest extends TestCase
{
    /** @var StateMachineInterface|MockObject */
    private MockObject $stateMachineMock;

    private OrderStateMachineTransitionApplicator $orderStateMachineTransitionApplicator;

    protected function setUp(): void
    {
        $this->stateMachineMock = $this->createMock(StateMachineInterface::class);
        $this->orderStateMachineTransitionApplicator = new OrderStateMachineTransitionApplicator($this->stateMachineMock);
    }

    public function testCancelsOrder(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->stateMachineMock->expects($this->once())->method('can')->with($orderMock, OrderTransitions::GRAPH, OrderTransitions::TRANSITION_CANCEL)->willReturn(true);
        $this->stateMachineMock->expects($this->once())->method('apply')->with($orderMock, OrderTransitions::GRAPH, OrderTransitions::TRANSITION_CANCEL);
        $this->orderStateMachineTransitionApplicator->cancel($orderMock);
    }

    public function testThrowExceptionIfCannotCancelOrder(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->stateMachineMock->expects($this->once())->method('can')->with($orderMock, OrderTransitions::GRAPH, OrderTransitions::TRANSITION_CANCEL)->willReturn(false);
        $this->stateMachineMock->expects($this->never())->method('apply')->with($orderMock, OrderTransitions::GRAPH, OrderTransitions::TRANSITION_CANCEL);
        $this->expectException(StateMachineTransitionFailedException::class);
        $this->orderStateMachineTransitionApplicator->cancel($orderMock);
    }
}
