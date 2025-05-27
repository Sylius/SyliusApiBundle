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

namespace Tests\Sylius\Bundle\ApiBundle\Checker;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Checker\AppliedCouponEligibilityChecker;
use Sylius\Bundle\ApiBundle\Checker\AppliedCouponEligibilityCheckerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PromotionCouponInterface;
use Sylius\Component\Core\Model\PromotionInterface;
use Sylius\Component\Promotion\Checker\Eligibility\PromotionCouponEligibilityCheckerInterface;
use Sylius\Component\Promotion\Checker\Eligibility\PromotionEligibilityCheckerInterface;

final class AppliedCouponEligibilityCheckerTest extends TestCase
{
    /** @var PromotionEligibilityCheckerInterface|MockObject */
    private MockObject $promotionCheckerMock;

    /** @var PromotionCouponEligibilityCheckerInterface|MockObject */
    private MockObject $promotionCouponCheckerMock;

    private AppliedCouponEligibilityChecker $appliedCouponEligibilityChecker;

    protected function setUp(): void
    {
        $this->promotionCheckerMock = $this->createMock(PromotionEligibilityCheckerInterface::class);
        $this->promotionCouponCheckerMock = $this->createMock(PromotionCouponEligibilityCheckerInterface::class);
        $this->appliedCouponEligibilityChecker = new AppliedCouponEligibilityChecker($this->promotionCheckerMock, $this->promotionCouponCheckerMock);
    }

    public function testImplementsPromotionCouponEligibilityCheckerInterface(): void
    {
        $this->assertInstanceOf(AppliedCouponEligibilityCheckerInterface::class, $this->appliedCouponEligibilityChecker);
    }

    public function testReturnsFalseIfPromotionCouponIsNull(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        /** @var PromotionInterface|MockObject $promotionMock */
        $promotionMock = $this->createMock(PromotionInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        $promotionCouponMock->expects($this->never())->method('getPromotion');
        $promotionMock->expects($this->never())->method('getChannels');
        $this->promotionCheckerMock->expects($this->never())->method('isEligible');
        $this->promotionCouponCheckerMock->expects($this->never())->method('isEligible');
        $this->assertFalse($this->appliedCouponEligibilityChecker->isEligible(null, $cartMock));
    }

    public function testReturnsFalseIfCartChannelIsNotOneOfPromotionChannels(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        /** @var PromotionInterface|MockObject $promotionMock */
        $promotionMock = $this->createMock(PromotionInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $firstChannelMock */
        $firstChannelMock = $this->createMock(ChannelInterface::class);
        /** @var ChannelInterface|MockObject $secondChannelMock */
        $secondChannelMock = $this->createMock(ChannelInterface::class);
        /** @var ChannelInterface|MockObject $thirdChannelMock */
        $thirdChannelMock = $this->createMock(ChannelInterface::class);
        $promotionCouponMock->expects($this->once())->method('getPromotion')->willReturn($promotionMock);
        $promotionMock->expects($this->once())->method('getChannels')->willReturn(new ArrayCollection([
            $secondChannelMock,
            $thirdChannelMock,
        ]));
        $cartMock->expects($this->once())->method('getChannel')->willReturn($firstChannelMock);
        $this->promotionCheckerMock->expects($this->never())->method('isEligible');
        $this->promotionCouponCheckerMock->expects($this->never())->method('isEligible');
        $this->assertFalse($this->appliedCouponEligibilityChecker->isEligible($promotionCouponMock, $cartMock));
    }

    public function testReturnsFalseIfCouponIsNotEligible(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        /** @var PromotionInterface|MockObject $promotionMock */
        $promotionMock = $this->createMock(PromotionInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $firstChannelMock */
        $firstChannelMock = $this->createMock(ChannelInterface::class);
        /** @var ChannelInterface|MockObject $secondChannelMock */
        $secondChannelMock = $this->createMock(ChannelInterface::class);
        $promotionCouponMock->expects($this->once())->method('getPromotion')->willReturn($promotionMock);
        $promotionMock->expects($this->once())->method('getChannels')->willReturn(new ArrayCollection([
            $firstChannelMock,
            $secondChannelMock,
        ]));
        $cartMock->expects($this->once())->method('getChannel')->willReturn($firstChannelMock);
        $this->promotionCouponCheckerMock->expects($this->once())->method('isEligible')->with($cartMock, $promotionCouponMock)->willReturn(false);
        $this->promotionCheckerMock->expects($this->never())->method('isEligible');
        $this->assertFalse($this->appliedCouponEligibilityChecker->isEligible($promotionCouponMock, $cartMock));
    }

    public function testReturnsFalseIfPromotionIsNotEligible(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        /** @var PromotionInterface|MockObject $promotionMock */
        $promotionMock = $this->createMock(PromotionInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $firstChannelMock */
        $firstChannelMock = $this->createMock(ChannelInterface::class);
        /** @var ChannelInterface|MockObject $secondChannelMock */
        $secondChannelMock = $this->createMock(ChannelInterface::class);
        $promotionCouponMock->expects($this->once())->method('getPromotion')->willReturn($promotionMock);
        $promotionMock->expects($this->once())->method('getChannels')->willReturn(new ArrayCollection([
            $firstChannelMock,
            $secondChannelMock,
        ]));
        $cartMock->expects($this->once())->method('getChannel')->willReturn($firstChannelMock);
        $this->promotionCouponCheckerMock->expects($this->once())->method('isEligible')->with($cartMock, $promotionCouponMock)->willReturn(true);
        $this->promotionCheckerMock->expects($this->once())->method('isEligible')->with($cartMock, $promotionMock)->willReturn(false);
        $this->assertFalse($this->appliedCouponEligibilityChecker->isEligible($promotionCouponMock, $cartMock));
    }

    public function testReturnsTrueIfPromotionAndCouponAreEligible(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        /** @var PromotionInterface|MockObject $promotionMock */
        $promotionMock = $this->createMock(PromotionInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $firstChannelMock */
        $firstChannelMock = $this->createMock(ChannelInterface::class);
        /** @var ChannelInterface|MockObject $secondChannelMock */
        $secondChannelMock = $this->createMock(ChannelInterface::class);
        $promotionCouponMock->expects($this->once())->method('getPromotion')->willReturn($promotionMock);
        $promotionMock->expects($this->once())->method('getChannels')->willReturn(new ArrayCollection([
            $firstChannelMock,
            $secondChannelMock,
        ]));
        $cartMock->expects($this->once())->method('getChannel')->willReturn($firstChannelMock);
        $this->promotionCouponCheckerMock->expects($this->once())->method('isEligible')->with($cartMock, $promotionCouponMock)->willReturn(true);
        $this->promotionCheckerMock->expects($this->once())->method('isEligible')->with($cartMock, $promotionMock)->willReturn(true);
        $this->assertTrue($this->appliedCouponEligibilityChecker->isEligible($promotionCouponMock, $cartMock));
    }
}
