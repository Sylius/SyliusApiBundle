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

namespace Tests\Sylius\Bundle\ApiBundle\CommandHandler\Cart;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Command\Cart\RemoveItemFromCart;
use Sylius\Bundle\ApiBundle\CommandHandler\Cart\RemoveItemFromCartHandler;
use Sylius\Bundle\ApiBundle\spec\CommandHandler\MessageHandlerAttributeTrait;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Sylius\Component\Order\Repository\OrderItemRepositoryInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;

final class RemoveItemFromCartHandlerTest extends TestCase
{
    private MockObject&OrderItemRepositoryInterface $orderItemRepository;

    private MockObject&OrderModifierInterface $orderModifier;

    private MockObject&ProductVariantResolverInterface $variantResolver;

    private RemoveItemFromCartHandler $handler;

    use MessageHandlerAttributeTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderItemRepository = $this->createMock(OrderItemRepositoryInterface::class);
        $this->orderModifier = $this->createMock(OrderModifierInterface::class);
        $this->variantResolver = $this->createMock(ProductVariantResolverInterface::class);
        $this->handler = new RemoveItemFromCartHandler($this->orderItemRepository, $this->orderModifier, $this->variantResolver);
    }

    public function testRemovesOrderItemFromCart(): void
    {
        /** @var OrderInterface|MockObject $cart */
        $cart = $this->createMock(OrderInterface::class);
        /** @var OrderItemInterface|MockObject $cartItem */
        $cartItem = $this->createMock(OrderItemInterface::class);
        $this->orderItemRepository->expects(self::once())
            ->method('findOneByIdAndCartTokenValue')
            ->with('ORDER_ITEM_ID', 'TOKEN_VALUE')
            ->willReturn($cartItem);
        $cartItem->expects(self::once())->method('getOrder')->willReturn($cart);
        $cart->expects(self::once())->method('getTokenValue')->willReturn('TOKEN_VALUE');
        $this->orderModifier->expects(self::once())->method('removeFromOrder')->with($cart, $cartItem);
        self::assertSame($cart, $this->handler->__invoke(
            new RemoveItemFromCart(orderTokenValue: 'TOKEN_VALUE', itemId: 'ORDER_ITEM_ID'),
        ));
    }

    public function testThrowsAnExceptionIfOrderItemWasNotFound(): void
    {
        /** @var OrderItemInterface|MockObject $cartItem */
        $cartItem = $this->createMock(OrderItemInterface::class);
        $this->orderItemRepository->expects(self::once())
            ->method('findOneByIdAndCartTokenValue')
            ->with('ORDER_ITEM_ID', 'TOKEN_VALUE')->willReturn(null);
        $cartItem->expects(self::never())->method('getOrder');
        self::expectException(\InvalidArgumentException::class);
        $this->handler->__invoke(new RemoveItemFromCart(orderTokenValue: 'TOKEN_VALUE', itemId: 'ORDER_ITEM_ID'));
    }

    public function testThrowsAnExceptionIfCartTokenValueWasNotProperly(): void
    {
        /** @var OrderItemInterface|MockObject $cartItem */
        $cartItem = $this->createMock(OrderItemInterface::class);
        /** @var OrderInterface|MockObject $cart */
        $cart = $this->createMock(OrderInterface::class);
        $this->orderItemRepository->expects(self::once())
            ->method('findOneByIdAndCartTokenValue')
            ->with('ORDER_ITEM_ID', 'TOKEN_VALUE')
            ->willReturn($cartItem);
        $cartItem->expects(self::once())->method('getOrder')->willReturn($cart);
        $cart->expects(self::once())->method('getTokenValue')->willReturn('WRONG_TOKEN_VALUE_');
        $this->orderModifier->expects(self::never())->method('removeFromOrder')->with(null, $cartItem);
        self::expectException(\InvalidArgumentException::class);
        $this->handler->__invoke(new RemoveItemFromCart(orderTokenValue: 'TOKEN_VALUE', itemId: 'ORDER_ITEM_ID'));
    }
}
