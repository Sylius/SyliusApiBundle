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
use Sylius\Bundle\ApiBundle\Checker\AppliedCouponEligibilityCheckerInterface;
use Sylius\Bundle\ApiBundle\Command\Checkout\UpdateCart;
use Sylius\Bundle\ApiBundle\Validator\Constraints\PromotionCouponEligibility;
use Sylius\Bundle\ApiBundle\Validator\Constraints\PromotionCouponEligibilityValidator;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PromotionCouponInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Promotion\Repository\PromotionCouponRepositoryInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

final class PromotionCouponEligibilityValidatorTest extends TestCase
{
    /** @var PromotionCouponRepositoryInterface|MockObject */
    private MockObject $promotionCouponRepositoryMock;

    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;

    /** @var AppliedCouponEligibilityCheckerInterface|MockObject */
    private MockObject $appliedCouponEligibilityCheckerMock;

    private PromotionCouponEligibilityValidator $promotionCouponEligibilityValidator;

    protected function setUp(): void
    {
        $this->promotionCouponRepositoryMock = $this->createMock(PromotionCouponRepositoryInterface::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->appliedCouponEligibilityCheckerMock = $this->createMock(AppliedCouponEligibilityCheckerInterface::class);
        $this->promotionCouponEligibilityValidator = new PromotionCouponEligibilityValidator($this->promotionCouponRepositoryMock, $this->orderRepositoryMock, $this->appliedCouponEligibilityCheckerMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->promotionCouponEligibilityValidator);
    }

    public function testThrowsAnExceptionIfConstraintIsNotOfExpectedType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->promotionCouponEligibilityValidator->validate('', new NotNull());
    }

    public function testDoesNotAddViolationIfPromotionCouponIsEligible(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->promotionCouponEligibilityValidator->initialize($executionContextMock);
        $constraint = new PromotionCouponEligibility();
        $value = new UpdateCart(couponCode: 'couponCode', orderTokenValue: 'token');
        $this->promotionCouponRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'couponCode'])->willReturn($promotionCouponMock);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('token')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('setPromotionCoupon')->with($promotionCouponMock);
        $this->appliedCouponEligibilityCheckerMock->expects($this->once())->method('isEligible')->with($promotionCouponMock, $cartMock)->willReturn(true);
        $executionContextMock->expects($this->never())->method('buildViolation');
        $this->promotionCouponEligibilityValidator->validate($value, $constraint);
    }

    public function testAddsViolationIfPromotionCouponIsNotEligible(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ConstraintViolationBuilderInterface|MockObject $constraintViolationBuilderMock */
        $constraintViolationBuilderMock = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->promotionCouponEligibilityValidator->initialize($executionContextMock);
        $constraint = new PromotionCouponEligibility();
        $constraint->message = 'message';
        $value = new UpdateCart(couponCode: 'couponCode', orderTokenValue: 'token');
        $this->promotionCouponRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'couponCode'])->willReturn($promotionCouponMock);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('token')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('setPromotionCoupon')->with($promotionCouponMock);
        $this->appliedCouponEligibilityCheckerMock->expects($this->once())->method('isEligible')->with($promotionCouponMock, $cartMock)->willReturn(false);
        $executionContextMock->expects($this->once())->method('buildViolation')->with($constraint->message)->willReturn($constraintViolationBuilderMock);
        $constraintViolationBuilderMock->expects($this->once())->method('atPath')->with('couponCode')->willReturn($constraintViolationBuilderMock);
        $constraintViolationBuilderMock->expects($this->once())->method('addViolation');
        $this->promotionCouponEligibilityValidator->validate($value, $constraint);
    }

    public function testAddsViolationIfPromotionCouponIsNotInstanceOfPromotionCouponInterface(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ConstraintViolationBuilderInterface|MockObject $constraintViolationBuilderMock */
        $constraintViolationBuilderMock = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->promotionCouponEligibilityValidator->initialize($executionContextMock);
        $constraint = new PromotionCouponEligibility();
        $constraint->message = 'message';
        $value = new UpdateCart(couponCode: 'couponCode', orderTokenValue: 'token');
        $this->promotionCouponRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'couponCode'])->willReturn(null);
        $executionContextMock->expects($this->once())->method('buildViolation')->with($constraint->message)->willReturn($constraintViolationBuilderMock);
        $constraintViolationBuilderMock->expects($this->once())->method('atPath')->with('couponCode')->willReturn($constraintViolationBuilderMock);
        $constraintViolationBuilderMock->expects($this->once())->method('addViolation');
        $this->orderRepositoryMock->expects($this->never())->method('findCartByTokenValue')->with('token');
        $this->promotionCouponEligibilityValidator->validate($value, $constraint);
    }
}
