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
use Sylius\Bundle\ApiBundle\Validator\Constraints\OrderItemAvailabilityValidator;
use InvalidArgumentException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Bundle\ApiBundle\Validator\Constraints\OrderItemAvailability;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class OrderItemAvailabilityValidatorTest extends TestCase
{
    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;
    /** @var AvailabilityCheckerInterface|MockObject */
    private MockObject $availabilityCheckerMock;
    /** @var ExecutionContextInterface|MockObject */
    private MockObject $executionContextMock;
    private OrderItemAvailabilityValidator $orderItemAvailabilityValidator;
    protected function setUp(): void
    {
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->availabilityCheckerMock = $this->createMock(AvailabilityCheckerInterface::class);
        $this->executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->orderItemAvailabilityValidator = new OrderItemAvailabilityValidator($this->orderRepositoryMock, $this->availabilityCheckerMock);
        $this->initialize($this->executionContextMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->orderItemAvailabilityValidator);
    }

    public function testThrowsExceptionIfConstraintIsNotAnInstanceOfOrderProductInStockEligibility(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->orderItemAvailabilityValidator->validate(new CompleteOrder('TOKEN'), final class() extends TestCase {
        });
    }

    public function testAddsViolationIfProductVariantDoesNotHaveSufficientStock(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var Collection|MockObject $orderItemsMock */
        $orderItemsMock = $this->createMock(Collection::class);
        $command = new CompleteOrder(orderTokenValue: 'cartToken');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'cartToken'])->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getItems')->willReturn($orderItemsMock);
        $orderItemsMock->expects($this->once())->method('getIterator')->willReturn(new ArrayCollection([$orderItemMock]));
        $orderItemMock->expects($this->once())->method('getVariant')->willReturn($productVariantMock);
        $orderItemMock->expects($this->once())->method('getQuantity')->willReturn(1);
        $this->availabilityCheckerMock->expects($this->once())->method('isStockSufficient')->with($productVariantMock, 1)->willReturn(false);
        $productVariantMock->expects($this->once())->method('getName')->willReturn('variant name');
        $this->executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product_variant.product_variant_with_name_not_sufficient', ['%productVariantName%' => 'variant name'])
        ;
        $this->orderItemAvailabilityValidator->validate($command, new OrderItemAvailability());
    }

    public function testDoesNothingIfProductVariantHasSufficientStock(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var Collection|MockObject $orderItemsMock */
        $orderItemsMock = $this->createMock(Collection::class);
        $command = new CompleteOrder(orderTokenValue: 'cartToken');
        $this->orderRepositoryMock->expects($this->once())->method('findOneBy')->with(['tokenValue' => 'cartToken'])->willReturn($orderMock);
        $orderMock->expects($this->once())->method('getItems')->willReturn($orderItemsMock);
        $orderItemsMock->expects($this->once())->method('getIterator')->willReturn(new ArrayCollection([$orderItemMock]));
        $orderItemMock->expects($this->once())->method('getVariant')->willReturn($productVariantMock);
        $orderItemMock->expects($this->once())->method('getQuantity')->willReturn(1);
        $this->availabilityCheckerMock->expects($this->once())->method('isStockSufficient')->with($productVariantMock, 1)->willReturn(true);
        $productVariantMock->expects($this->never())->method('getName');
        $this->executionContextMock->expects($this->never())->method('addViolation')->with('sylius.product_variant.product_variant_with_name_not_sufficient', ['%productVariantName%' => 'variant name'])
        ;
        $this->orderItemAvailabilityValidator->validate($command, new OrderItemAvailability());
    }
}
