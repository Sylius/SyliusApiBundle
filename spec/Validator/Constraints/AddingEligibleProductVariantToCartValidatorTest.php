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
use Sylius\Bundle\ApiBundle\Validator\Constraints\AddingEligibleProductVariantToCartValidator;
use InvalidArgumentException;
use ArrayIterator;
use Doctrine\Common\Collections\Collection;
use Sylius\Bundle\ApiBundle\Command\Cart\AddItemToCart;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Bundle\ApiBundle\Validator\Constraints\AddingEligibleProductVariantToCart;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\Order;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class AddingEligibleProductVariantToCartValidatorTest extends TestCase
{
    /** @var ProductVariantRepositoryInterface|MockObject */
    private MockObject $productVariantRepositoryMock;
    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;
    /** @var AvailabilityCheckerInterface|MockObject */
    private MockObject $availabilityCheckerMock;
    private AddingEligibleProductVariantToCartValidator $addingEligibleProductVariantToCartValidator;
    protected function setUp(): void
    {
        $this->productVariantRepositoryMock = $this->createMock(ProductVariantRepositoryInterface::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->availabilityCheckerMock = $this->createMock(AvailabilityCheckerInterface::class);
        $this->addingEligibleProductVariantToCartValidator = new AddingEligibleProductVariantToCartValidator($this->productVariantRepositoryMock, $this->orderRepositoryMock, $this->availabilityCheckerMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->addingEligibleProductVariantToCartValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotAnInstanceOfAddItemToCartCommand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->addingEligibleProductVariantToCartValidator->validate(new CompleteOrder('TOKEN'), new AddingEligibleProductVariantToCart());
    }

    public function testThrowsAnExceptionIfConstraintIsNotAnInstanceOfAddingEligibleProductVariantToCart(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->addingEligibleProductVariantToCartValidator->validate(new AddItemToCart(orderTokenValue: 'TOKEN', productVariantCode: 'productVariantCode', quantity: 1), final class() extends TestCase {
        });
    }

    public function testDoesNothingIfOrderIsAlreadyPlaced(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->addingEligibleProductVariantToCartValidator->initialize($executionContextMock);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn(null);
        $this->productVariantRepositoryMock->expects($this->never())->method('findOneBy')->with(['code' => 'productVariantCode']);
        $executionContextMock->expects($this->never())->method('addViolation')->with($this->any());
        $this->addingEligibleProductVariantToCartValidator->validate(
            new AddItemToCart(orderTokenValue: 'TOKEN', productVariantCode: 'productVariantCode', quantity: 1),
            new AddingEligibleProductVariantToCart(),
        );
    }

    public function testAddsViolationIfProductVariantDoesNotExist(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->addingEligibleProductVariantToCartValidator->initialize($executionContextMock);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn(new Order());
        $this->productVariantRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'productVariantCode'])->willReturn(null);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product_variant.not_exist', ['%productVariantCode%' => 'productVariantCode'])
        ;
        $this->addingEligibleProductVariantToCartValidator->validate(
            new AddItemToCart(orderTokenValue: 'TOKEN', productVariantCode: 'productVariantCode', quantity: 1),
            new AddingEligibleProductVariantToCart(),
        );
    }

    public function testAddsViolationIfProductIsDisabled(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        $this->addingEligibleProductVariantToCartValidator->initialize($executionContextMock);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn(new Order());
        $this->productVariantRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'productVariantCode'])->willReturn($productVariantMock);
        $productVariantMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $productMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $productMock->expects($this->once())->method('getName')->willReturn('PRODUCT NAME');
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product.not_exist', ['%productName%' => 'PRODUCT NAME'])
        ;
        $this->addingEligibleProductVariantToCartValidator->validate(
            new AddItemToCart(orderTokenValue: 'TOKEN', productVariantCode: 'productVariantCode', quantity: 1),
            new AddingEligibleProductVariantToCart(),
        );
    }

    public function testAddsViolationIfProductVariantIsDisabled(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        $this->addingEligibleProductVariantToCartValidator->initialize($executionContextMock);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn(new Order());
        $this->productVariantRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'productVariantCode'])->willReturn($productVariantMock);
        $productVariantMock->expects($this->once())->method('getCode')->willReturn('productVariantCode');
        $productVariantMock->expects($this->once())->method('isEnabled')->willReturn(false);
        $productVariantMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $productMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product_variant.not_exist', ['%productVariantCode%' => 'productVariantCode'])
        ;
        $this->addingEligibleProductVariantToCartValidator->validate(
            new AddItemToCart(orderTokenValue: 'TOKEN', productVariantCode: 'productVariantCode', quantity: 1),
            new AddingEligibleProductVariantToCart(),
        );
    }

    public function testAddsViolationIfProductVariantStockIsNotSufficientAndCartHasSameUnits(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var Collection|MockObject $itemsMock */
        $itemsMock = $this->createMock(Collection::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var ProductVariantInterface|MockObject $itemProductVariantMock */
        $itemProductVariantMock = $this->createMock(ProductVariantInterface::class);
        $this->addingEligibleProductVariantToCartValidator->initialize($executionContextMock);
        $command = new AddItemToCart(
            orderTokenValue: 'TOKEN',
            productVariantCode: 'productVariantCode',
            quantity: 1,
        );
        $this->productVariantRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'productVariantCode'])->willReturn($productVariantMock);
        $productVariantMock->expects($this->once())->method('getCode')->willReturn('productVariantCode');
        $productVariantMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $productVariantMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $productMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getItems')->willReturn($itemsMock);
        $productVariantMock->expects($this->once())->method('isTracked')->willReturn(true);
        $itemsMock->expects($this->once())->method('getIterator')->willReturn(new ArrayIterator([$orderItemMock]));
        $orderItemMock->expects($this->once())->method('getVariant')->willReturn($itemProductVariantMock);
        $orderItemMock->expects($this->once())->method('getQuantity')->willReturn(1);
        $itemProductVariantMock->expects($this->once())->method('getCode')->willReturn('productVariantCode');
        $this->availabilityCheckerMock->expects($this->once())->method('isStockSufficient')->with($productVariantMock, 2)->willReturn(false);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product_variant.not_sufficient', ['%productVariantCode%' => 'productVariantCode'])
        ;
        $this->addingEligibleProductVariantToCartValidator->validate(
            $command,
            new AddingEligibleProductVariantToCart(),
        );
    }

    public function testAddsViolationIfProductVariantStockIsNotSufficientAndCartHasNotSameUnits(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var Collection|MockObject $itemsMock */
        $itemsMock = $this->createMock(Collection::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var ProductVariantInterface|MockObject $orderItemVariantMock */
        $orderItemVariantMock = $this->createMock(ProductVariantInterface::class);
        $this->addingEligibleProductVariantToCartValidator->initialize($executionContextMock);
        $command = new AddItemToCart(
            orderTokenValue: 'TOKEN',
            productVariantCode: 'productVariantCode',
            quantity: 1,
        );
        $this->productVariantRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'productVariantCode'])->willReturn($productVariantMock);
        $productVariantMock->expects($this->once())->method('getCode')->willReturn('productVariantCode');
        $productVariantMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $productVariantMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $productMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn($cartMock);
        $productVariantMock->expects($this->once())->method('isTracked')->willReturn(true);
        $cartMock->expects($this->once())->method('getItems')->willReturn($itemsMock);
        $orderItemMock->expects($this->once())->method('getVariant')->willReturn($orderItemVariantMock);
        $orderItemVariantMock->expects($this->once())->method('getCode')->willReturn('otherProductVariantCode');
        $itemsMock->expects($this->once())->method('getIterator')->willReturn(new ArrayIterator([]));
        $this->availabilityCheckerMock->expects($this->once())->method('isStockSufficient')->with($productVariantMock, 1)->willReturn(false);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product_variant.not_sufficient', ['%productVariantCode%' => 'productVariantCode'])
        ;
        $this->addingEligibleProductVariantToCartValidator->validate(
            $command,
            new AddingEligibleProductVariantToCart(),
        );
    }

    public function testAddsViolationIfProductIsNotAvailableInChannel(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var Collection|MockObject $itemsMock */
        $itemsMock = $this->createMock(Collection::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var ProductVariantInterface|MockObject $itemProductVariantMock */
        $itemProductVariantMock = $this->createMock(ProductVariantInterface::class);
        $this->addingEligibleProductVariantToCartValidator->initialize($executionContextMock);
        $command = new AddItemToCart(
            orderTokenValue: 'TOKEN',
            productVariantCode: 'productVariantCode',
            quantity: 1,
        );
        $this->productVariantRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'productVariantCode'])->willReturn($productVariantMock);
        $productVariantMock->expects($this->once())->method('getCode')->willReturn('productVariantCode');
        $productVariantMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $productVariantMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getItems')->willReturn($itemsMock);
        $productVariantMock->expects($this->once())->method('isTracked')->willReturn(true);
        $itemsMock->expects($this->once())->method('getIterator')->willReturn(new ArrayIterator([$orderItemMock]));
        $orderItemMock->expects($this->once())->method('getVariant')->willReturn($itemProductVariantMock);
        $orderItemMock->expects($this->once())->method('getQuantity')->willReturn(1);
        $productMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->availabilityCheckerMock->expects($this->once())->method('isStockSufficient')->with($productVariantMock, 1)->willReturn(true);
        $productMock->expects($this->once())->method('hasChannel')->with($channelMock)->willReturn(false);
        $productMock->expects($this->once())->method('getName')->willReturn('PRODUCT NAME');
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product.not_exist', ['%productName%' => 'PRODUCT NAME'])
        ;
        $this->addingEligibleProductVariantToCartValidator->validate($command, new AddingEligibleProductVariantToCart());
    }

    public function testDoesNothingIfProductAndVariantAreEnabledAndAvailableInChannel(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var Collection|MockObject $itemsMock */
        $itemsMock = $this->createMock(Collection::class);
        /** @var OrderItemInterface|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItemInterface::class);
        /** @var ProductVariantInterface|MockObject $itemProductVariantMock */
        $itemProductVariantMock = $this->createMock(ProductVariantInterface::class);
        $this->addingEligibleProductVariantToCartValidator->initialize($executionContextMock);
        $command = new AddItemToCart(
            orderTokenValue: 'TOKEN',
            productVariantCode: 'productVariantCode',
            quantity: 1,
        );
        $this->productVariantRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'productVariantCode'])->willReturn($productVariantMock);
        $productVariantMock->expects($this->once())->method('getCode')->willReturn('productVariantCode');
        $productVariantMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $productVariantMock->expects($this->once())->method('getProduct')->willReturn($productMock);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getItems')->willReturn($itemsMock);
        $productVariantMock->expects($this->once())->method('isTracked')->willReturn(true);
        $itemsMock->expects($this->once())->method('getIterator')->willReturn(new ArrayIterator([$orderItemMock]));
        $orderItemMock->expects($this->once())->method('getVariant')->willReturn($itemProductVariantMock);
        $orderItemMock->expects($this->once())->method('getQuantity')->willReturn(1);
        $itemsMock->expects($this->once())->method('isEmpty')->willReturn(true);
        $productMock->expects($this->once())->method('isEnabled')->willReturn(true);
        $this->availabilityCheckerMock->expects($this->once())->method('isStockSufficient')->with($productVariantMock, 1)->willReturn(true);
        $productMock->expects($this->once())->method('hasChannel')->with($channelMock)->willReturn(true);
        $productMock->expects($this->once())->method('getName')->willReturn('PRODUCT NAME');
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $executionContextMock->expects($this->exactly(3))->method('addViolation')->willReturnMap([['sylius.product_variant.not_exist', ['%productVariantCode%' => 'productVariantCode']], ['sylius.product.not_exist', ['%productName%' => 'PRODUCT NAME']], ['sylius.product_variant.not_sufficient', ['%productVariantCode%' => 'productVariantCode']]]);
        $this->addingEligibleProductVariantToCartValidator->validate($command, new AddingEligibleProductVariantToCart());
    }
}
