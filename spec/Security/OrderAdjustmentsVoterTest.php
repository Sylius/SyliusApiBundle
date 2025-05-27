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

namespace Tests\Sylius\Bundle\ApiBundle\Security;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Provider\AdjustmentOrderProviderInterface;
use Sylius\Bundle\ApiBundle\Security\OrderAdjustmentsVoter;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShopUser;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class OrderAdjustmentsVoterTest extends TestCase
{
    /** @var AdjustmentOrderProviderInterface|MockObject */
    private MockObject $adjustmentOrderProviderMock;

    private OrderAdjustmentsVoter $orderAdjustmentsVoter;

    protected function setUp(): void
    {
        $this->adjustmentOrderProviderMock = $this->createMock(AdjustmentOrderProviderInterface::class);
        $this->orderAdjustmentsVoter = new OrderAdjustmentsVoter($this->adjustmentOrderProviderMock);
    }

    public function testOnlySupportsSyliusOrderAdjustmentAttribute(): void
    {
        $this->assertTrue($this->orderAdjustmentsVoter->supportsAttribute(OrderAdjustmentsVoter::SYLIUS_ORDER_ADJUSTMENT));
        $this->assertFalse($this->orderAdjustmentsVoter->supportsAttribute('OTHER_ATTRIBUTE'));
    }

    public function testVotesGrantedWhenCollectionIsEmpty(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->orderAdjustmentsVoter->vote($tokenMock, new ArrayCollection(), [OrderAdjustmentsVoter::SYLIUS_ORDER_ADJUSTMENT]));
    }

    public function testVotesGrantedWhenCollectionIsAnEmptyArray(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->orderAdjustmentsVoter->vote($tokenMock, [], [OrderAdjustmentsVoter::SYLIUS_ORDER_ADJUSTMENT]));
    }

    public function testVotesGrantedWhenOrderUserMatchesTokenUser(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var AdjustmentInterface|MockObject $adjustmentMock */
        $adjustmentMock = $this->createMock(AdjustmentInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $user = new ShopUser();
        $collection = new ArrayCollection([$adjustmentMock]);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($user);
        $this->adjustmentOrderProviderMock->expects($this->once())->method('provide')->with($adjustmentMock)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getUser')->willReturn($user);
        $orderMock->expects($this->once())->method('isCreatedByGuest')->willReturn(false);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->orderAdjustmentsVoter->vote($tokenMock, $collection, [OrderAdjustmentsVoter::SYLIUS_ORDER_ADJUSTMENT]));
    }

    public function testVotesDeniedWhenOrderUserDoesNotMatchTokenUser(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var AdjustmentInterface|MockObject $adjustmentMock */
        $adjustmentMock = $this->createMock(AdjustmentInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $tokenUser = new ShopUser();
        $orderUser = new ShopUser();
        $collection = new ArrayCollection([$adjustmentMock]);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($tokenUser);
        $this->adjustmentOrderProviderMock->expects($this->once())->method('provide')->with($adjustmentMock)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getUser')->willReturn($orderUser);
        $orderMock->expects($this->once())->method('isCreatedByGuest')->willReturn(false);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->orderAdjustmentsVoter->vote($tokenMock, $collection, [OrderAdjustmentsVoter::SYLIUS_ORDER_ADJUSTMENT]));
    }

    public function testVotesGrantedWhenBothOrderAndTokenHaveNoUser(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var AdjustmentInterface|MockObject $adjustmentMock */
        $adjustmentMock = $this->createMock(AdjustmentInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $collection = new ArrayCollection([$adjustmentMock]);
        $tokenMock->expects($this->once())->method('getUser')->willReturn(null);
        $this->adjustmentOrderProviderMock->expects($this->once())->method('provide')->with($adjustmentMock)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getUser')->willReturn(null);
        $orderMock->expects($this->once())->method('isCreatedByGuest')->willReturn(true);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->orderAdjustmentsVoter->vote($tokenMock, $collection, [OrderAdjustmentsVoter::SYLIUS_ORDER_ADJUSTMENT]));
    }

    public function testVotesGrantedWhenAdjustmentHasNoOrder(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var AdjustmentInterface|MockObject $adjustmentMock */
        $adjustmentMock = $this->createMock(AdjustmentInterface::class);
        $collection = new ArrayCollection([$adjustmentMock]);
        $tokenMock->expects($this->once())->method('getUser')->willReturn(null);
        $this->adjustmentOrderProviderMock->expects($this->once())->method('provide')->with($adjustmentMock)->willReturn(null);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->orderAdjustmentsVoter->vote($tokenMock, $collection, [OrderAdjustmentsVoter::SYLIUS_ORDER_ADJUSTMENT]));
    }

    public function testVotesGrantedWhenAdjustmentHasOrderCreatedByGuestAndTokenHasUser(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var AdjustmentInterface|MockObject $adjustmentMock */
        $adjustmentMock = $this->createMock(AdjustmentInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $collection = new ArrayCollection([$adjustmentMock]);
        $user = new ShopUser();
        $tokenMock->expects($this->once())->method('getUser')->willReturn($user);
        $this->adjustmentOrderProviderMock->expects($this->once())->method('provide')->with($adjustmentMock)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getUser')->willReturn(null);
        $orderMock->expects($this->once())->method('isCreatedByGuest')->willReturn(true);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->orderAdjustmentsVoter->vote($tokenMock, $collection, [OrderAdjustmentsVoter::SYLIUS_ORDER_ADJUSTMENT]));
    }

    public function testVotesDeniedWhenOrderHasUserAndTokenHasNoUser(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var AdjustmentInterface|MockObject $adjustmentMock */
        $adjustmentMock = $this->createMock(AdjustmentInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $collection = new ArrayCollection([$adjustmentMock]);
        $user = new ShopUser();
        $tokenMock->expects($this->once())->method('getUser')->willReturn(null);
        $this->adjustmentOrderProviderMock->expects($this->once())->method('provide')->with($adjustmentMock)->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getUser')->willReturn($user);
        $orderMock->expects($this->once())->method('isCreatedByGuest')->willReturn(true);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->orderAdjustmentsVoter->vote($tokenMock, $collection, [OrderAdjustmentsVoter::SYLIUS_ORDER_ADJUSTMENT]));
    }
}
