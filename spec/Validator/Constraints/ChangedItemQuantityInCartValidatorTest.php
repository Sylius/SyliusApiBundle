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
use Sylius\Bundle\ApiBundle\Validator\Constraints\ChangedItemQuantityInCartValidator;
use InvalidArgumentException;
use Sylius\Bundle\ApiBundle\Command\Cart\ChangeItemQuantityInCart;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Bundle\ApiBundle\Exception\OrderItemNotFoundException;
use Sylius\Bundle\ApiBundle\Validator\Constraints\AddingEligibleProductVariantToCart;
use Sylius\Bundle\ApiBundle\Validator\Constraints\ChangedItemQuantityInCart;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\OrderItemRepositoryInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ChangedItemQuantityInCartValidatorTest extends TestCase
{
    /** @var OrderItemRepositoryInterface|MockObject */
    private MockObject $orderItemRepositoryMock;
    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;
    /** @var AvailabilityCheckerInterface|MockObject */
    private MockObject $availabilityCheckerMock;
    private ChangedItemQuantityInCartValidator $changedItemQuantityInCartValidator;
    protected function setUp(): void
    {
        $this->orderItemRepositoryMock = $this->createMock(OrderItemRepositoryInterface::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->availabilityCheckerMock = $this->createMock(AvailabilityCheckerInterface::class);
        $this->changedItemQuantityInCartValidator = new ChangedItemQuantityInCartValidator($this->orderItemRepositoryMock, $this->orderRepositoryMock, $this->availabilityCheckerMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->changedItemQuantityInCartValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotAnInstanceOfChangeItemQuantityInCart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->changedItemQuantityInCartValidator->validate(new CompleteOrder('TOKEN'), new AddingEligibleProductVariantToCart());
    }

    public function testThrowsAnExceptionIfConstraintIsNotAnInstanceOfChangedItemQuantityInCart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->changedItemQuantityInCartValidator->validate(new ChangeItemQuantityInCart(orderTokenValue: 'token', orderItemId: 11, quantity: 2), final class() extends TestCase {
        });
    }

    public function testThrowsAnExceptionIfOrderItemDoesNotExist(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->changedItemQuantityInCartValidator->initialize($executionContextMock);
        $this->orderItemRepositoryMock->expects($this->once())->method('findOneByIdAndCartTokenValue')->with('11', 'token')->willReturn(null);
        $this->expectException(OrderItemNotFoundException::class);
        $this->changedItemQuantityInCartValidator->validate(new ChangeItemQuantityInCart(orderTokenValue: 'token', orderItemId: 11, quantity: 2), new ChangedItemQuantityInCart());
    }

    public function testAddsViolationIfProductVariantDoesNotExist(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        $this->changedItemQuantityInCartValidator->initialize($executionContextMock);
        $this->orderItemRepositoryMock->expects($this->once())->method('findOneByIdAndCartTokenValue')->with('11', 'token')->willReturn($orderItemMock);
        $orderItemMock->expects($this->once())->method('getVariant')->willReturn(null);
        $orderItemMock->expects($this->once())->method('getVariantName')->willReturn('MacPro');
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product_variant.not_longer_available', ['%productVariantName%' => 'MacPro'])
        ;
        $this->changedItemQuantityInCartValidator->validate(
            new ChangeItemQuantityInCart(orderTokenValue: 'token', orderItemId: 11, quantity: 2),
            new ChangedItemQuantityInCart(),
        );
    }

    public function testAddsViolationIfProductIsDisabled(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        $this->changedItemQuantityInCartValidator->initialize($executionContextMock);
        $this->orderItemRepositoryMock->expects($this->once())->method('findOneByIdAndCartTokenValue')->with('11', 'token')->willReturn($orderItemMock);
        $orderItemMock->expects($this->once())->method('getVariant')->willReturn($productVariantMock);
        $orderItemMock->expects($this->once())->method('getVariantName')->willReturn('Variant Name');
        $productVariantMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $productVariantMock->expects($this->once())->method('getCode')->willReturn('VARIANT_CODE');
        $productMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $productMock->expects($this->once())->method('getName')->willReturn('PRODUCT NAME');
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product.not_exist', ['%productName%' => 'PRODUCT NAME'])
        ;
        $this->changedItemQuantityInCartValidator->validate(
            new ChangeItemQuantityInCart(orderTokenValue: 'token', orderItemId: 11, quantity: 2),
            new ChangedItemQuantityInCart(),
        );
    }

    public function testAddsViolationIfProductVariantIsDisabled(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        $this->changedItemQuantityInCartValidator->initialize($executionContextMock);
        $this->orderItemRepositoryMock->expects($this->once())->method('findOneByIdAndCartTokenValue')->with('11', 'token')->willReturn($orderItemMock);
        $orderItemMock->expects($this->once())->method('getVariant')->willReturn($productVariantMock);
        $orderItemMock->expects($this->once())->method('getVariantName')->willReturn('Variant Name');
        $productVariantMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $productVariantMock->expects($this->once())->method('getCode')->willReturn('VARIANT_CODE');
        $productMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $productMock->expects($this->once())->method('getName')->willReturn('PRODUCT NAME');
        $productVariantMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product_variant.not_longer_available', ['%productVariantName%' => 'Variant Name'])
        ;
        $this->changedItemQuantityInCartValidator->validate(
            new ChangeItemQuantityInCart(orderTokenValue: 'token', orderItemId: 11, quantity: 2),
            new ChangedItemQuantityInCart(),
        );
    }

    public function testAddsViolationIfProductVariantStockIsNotSufficient(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        $this->changedItemQuantityInCartValidator->initialize($executionContextMock);
        $this->orderItemRepositoryMock->expects($this->once())->method('findOneByIdAndCartTokenValue')->with('11', 'token')->willReturn($orderItemMock);
        $orderItemMock->expects($this->once())->method('getVariant')->willReturn($productVariantMock);
        $orderItemMock->expects($this->once())->method('getVariantName')->willReturn('Variant Name');
        $productVariantMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $productVariantMock->expects($this->once())->method('getCode')->willReturn('VARIANT_CODE');
        $productMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $productMock->expects($this->once())->method('getName')->willReturn('PRODUCT NAME');
        $productVariantMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->availabilityCheckerMock->expects($this->once())->method('isStockSufficient')->with($productVariantMock, 2)->willReturn(false);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product_variant.not_sufficient', ['%productVariantCode%' => 'VARIANT_CODE'])
        ;
        $this->changedItemQuantityInCartValidator->validate(
            new ChangeItemQuantityInCart(orderTokenValue: 'token', orderItemId: 11, quantity: 2),
            new ChangedItemQuantityInCart(),
        );
    }

    public function testAddsViolationIfProductIsNotAvailableInChannel(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        $this->changedItemQuantityInCartValidator->initialize($executionContextMock);
        $this->orderItemRepositoryMock->expects($this->once())->method('findOneByIdAndCartTokenValue')->with('11', 'token')->willReturn($orderItemMock);
        $orderItemMock->expects($this->once())->method('getVariant')->willReturn($productVariantMock);
        $orderItemMock->expects($this->once())->method('getVariantName')->willReturn('Variant Name');
        $productVariantMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $productVariantMock->expects($this->once())->method('getCode')->willReturn('VARIANT_CODE');
        $productMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $productMock->expects($this->exactly(2))->method('getName')->willReturnMap([['PRODUCT NAME'], ['PRODUCT NAME']]);
        $this->availabilityCheckerMock->expects($this->once())->method('isStockSufficient')->with($productVariantMock, 2)->willReturn(true);
        $productMock->expects($this->once())->method('getName')->willReturn('PRODUCT NAME');
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('token')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $productMock->expects($this->once())->method('hasChannel')->with($channelMock)->willReturn(false);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product.not_exist', ['%productName%' => 'PRODUCT NAME'])
        ;
        $this->changedItemQuantityInCartValidator->validate(
            new ChangeItemQuantityInCart(orderTokenValue: 'token', orderItemId: 11, quantity: 2),
            new ChangedItemQuantityInCart(),
        );
    }

    public function testDoesNothingIfProductAndVariantAreEnabledAndAvailableInChannel(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        $this->changedItemQuantityInCartValidator->initialize($executionContextMock);
        $this->orderItemRepositoryMock->expects($this->once())->method('findOneByIdAndCartTokenValue')->with('11', 'token')->willReturn($orderItemMock);
        $orderItemMock->expects($this->once())->method('getVariant')->willReturn($productVariantMock);
        $orderItemMock->expects($this->once())->method('getVariantName')->willReturn('Variant Name');
        $productVariantMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $productVariantMock->expects($this->once())->method('getCode')->willReturn('VARIANT_CODE');
        $productMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $productMock->expects($this->exactly(2))->method('getName')->willReturnMap([['PRODUCT NAME'], ['PRODUCT NAME']]);
        $this->availabilityCheckerMock->expects($this->once())->method('isStockSufficient')->with($productVariantMock, 2)->willReturn(true);
        $productMock->expects($this->once())->method('getName')->willReturn('PRODUCT NAME');
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('token')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $productMock->expects($this->once())->method('hasChannel')->with($channelMock)->willReturn(true);
        $executionContextMock->expects($this->never())->method('addViolation')->with('sylius.product_variant.not_exist', ['%productVariantCode%' => 'productVariantCode'])
        ;
        $executionContextMock->expects($this->exactly(3))->method('addViolation')->willReturnMap([['sylius.product_variant.not_exist', ['%productVariantCode%' => 'productVariantCode']], ['sylius.product.not_exist', ['%productName%' => 'PRODUCT NAME']], ['sylius.product_variant.not_sufficient', ['%productVariantCode%' => 'productVariantCode']]]);
    }
}
