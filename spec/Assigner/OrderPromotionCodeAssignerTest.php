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

namespace Tests\Sylius\Bundle\ApiBundle\Assigner;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Assigner\OrderPromotionCodeAssigner;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PromotionCouponInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Promotion\Repository\PromotionCouponRepositoryInterface;

final class OrderPromotionCodeAssignerTest extends TestCase
{
    /** @var PromotionCouponRepositoryInterface|MockObject */
    private MockObject $promotionCouponRepositoryMock;

    /** @var OrderProcessorInterface|MockObject */
    private MockObject $orderProcessorMock;

    private OrderPromotionCodeAssigner $orderPromotionCodeAssigner;

    protected function setUp(): void
    {
        $this->promotionCouponRepositoryMock = $this->createMock(PromotionCouponRepositoryInterface::class);
        $this->orderProcessorMock = $this->createMock(OrderProcessorInterface::class);
        $this->orderPromotionCodeAssigner = new OrderPromotionCodeAssigner($this->promotionCouponRepositoryMock, $this->orderProcessorMock);
    }

    public function testAppliesCouponToCart(): void
    {
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        $this->promotionCouponRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'couponCode'])->willReturn($promotionCouponMock);
        $cartMock->expects($this->once())->method('setPromotionCoupon')->with($promotionCouponMock);
        $this->orderProcessorMock->expects($this->once())->method('process')->with($cartMock);
        $this->orderPromotionCodeAssigner->assign($cartMock, 'couponCode');
    }

    public function testDoesNotApplyCouponIfPromotionCouponCodeIsEmpty(): void
    {
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        $this->promotionCouponRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => '']);
        $cartMock->expects($this->never())->method('setPromotionCoupon');
        $this->orderProcessorMock->expects($this->never())->method('process')->with($cartMock);
        $this->orderPromotionCodeAssigner->assign($cartMock, '');
    }

    public function testDoesNotApplyCouponIfPromotionCouponCodeIsInvalid(): void
    {
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        $this->promotionCouponRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'invalidCode'])->willReturn(null);
        $cartMock->expects($this->never())->method('setPromotionCoupon');
        $this->orderProcessorMock->expects($this->never())->method('process')->with($cartMock);
        $this->orderPromotionCodeAssigner->assign($cartMock, 'invalidCode');
    }

    public function testRemovesCouponIfPassedPromotionCouponCodeIsNull(): void
    {
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        $this->promotionCouponRepositoryMock->expects($this->never())->method('findOneBy')->with(['code' => null]);
        $cartMock->expects($this->once())->method('setPromotionCoupon')->with(null);
        $this->orderProcessorMock->expects($this->once())->method('process')->with($cartMock);
        $this->orderPromotionCodeAssigner->assign($cartMock, null);
    }
}
