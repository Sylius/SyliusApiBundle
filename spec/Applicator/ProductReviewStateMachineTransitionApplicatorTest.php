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
use Sylius\Bundle\ApiBundle\Applicator\ProductReviewStateMachineTransitionApplicator;
use Sylius\Bundle\ApiBundle\Exception\StateMachineTransitionFailedException;
use Sylius\Component\Core\ProductReviewTransitions;
use Sylius\Component\Review\Model\ReviewInterface;

final class ProductReviewStateMachineTransitionApplicatorTest extends TestCase
{
    /** @var StateMachineInterface|MockObject */
    private MockObject $stateMachineMock;

    private ProductReviewStateMachineTransitionApplicator $productReviewStateMachineTransitionApplicator;

    protected function setUp(): void
    {
        $this->stateMachineMock = $this->createMock(StateMachineInterface::class);
        $this->productReviewStateMachineTransitionApplicator = new ProductReviewStateMachineTransitionApplicator($this->stateMachineMock);
    }

    public function testAcceptsProductReview(): void
    {
        /** @var ReviewInterface|MockObject $reviewMock */
        $reviewMock = $this->createMock(ReviewInterface::class);
        $this->stateMachineMock->expects($this->once())->method('can')->with($reviewMock, ProductReviewTransitions::GRAPH, ProductReviewTransitions::TRANSITION_ACCEPT)->willReturn(true);
        $this->stateMachineMock->expects($this->once())->method('apply')->with($reviewMock, ProductReviewTransitions::GRAPH, ProductReviewTransitions::TRANSITION_ACCEPT);
        $this->productReviewStateMachineTransitionApplicator->accept($reviewMock);
    }

    public function testThrowsExceptionIfCannotAcceptProductReview(): void
    {
        /** @var ReviewInterface|MockObject $reviewMock */
        $reviewMock = $this->createMock(ReviewInterface::class);
        $this->stateMachineMock->expects($this->once())->method('can')->with($reviewMock, ProductReviewTransitions::GRAPH, ProductReviewTransitions::TRANSITION_ACCEPT)->willReturn(false);
        $this->stateMachineMock->expects($this->never())->method('apply')->with($reviewMock, ProductReviewTransitions::GRAPH, ProductReviewTransitions::TRANSITION_ACCEPT);
        $this->expectException(StateMachineTransitionFailedException::class);
        $this->productReviewStateMachineTransitionApplicator->accept($reviewMock);
    }

    public function testRejectsProductReview(): void
    {
        /** @var ReviewInterface|MockObject $reviewMock */
        $reviewMock = $this->createMock(ReviewInterface::class);
        $this->stateMachineMock->expects($this->once())->method('can')->with($reviewMock, ProductReviewTransitions::GRAPH, ProductReviewTransitions::TRANSITION_REJECT)->willReturn(true);
        $this->stateMachineMock->expects($this->once())->method('apply')->with($reviewMock, ProductReviewTransitions::GRAPH, ProductReviewTransitions::TRANSITION_REJECT);
        $this->productReviewStateMachineTransitionApplicator->reject($reviewMock);
    }

    public function testThrowsExceptionIfCannotRejectProductReview(): void
    {
        /** @var ReviewInterface|MockObject $reviewMock */
        $reviewMock = $this->createMock(ReviewInterface::class);
        $this->stateMachineMock->expects($this->once())->method('can')->with($reviewMock, ProductReviewTransitions::GRAPH, ProductReviewTransitions::TRANSITION_REJECT)->willReturn(false);
        $this->stateMachineMock->expects($this->never())->method('apply')->with($reviewMock, ProductReviewTransitions::GRAPH, ProductReviewTransitions::TRANSITION_REJECT);
        $this->expectException(StateMachineTransitionFailedException::class);
        $this->productReviewStateMachineTransitionApplicator->reject($reviewMock);
    }
}
