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
use Sylius\Bundle\ApiBundle\Command\Cart\BlameCart;
use Sylius\Bundle\ApiBundle\CommandHandler\Cart\BlameCartHandler;
use Sylius\Bundle\ApiBundle\spec\CommandHandler\MessageHandlerAttributeTrait;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;

final class BlameCartHandlerTest extends TestCase
{
    private MockObject&UserRepositoryInterface $shopUserRepository;

    private MockObject&OrderRepositoryInterface $orderRepository;

    private MockObject&OrderProcessorInterface $orderProcessor;

    private BlameCartHandler $handler;

    use MessageHandlerAttributeTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shopUserRepository = $this->createMock(UserRepositoryInterface::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->orderProcessor = $this->createMock(OrderProcessorInterface::class);
        $this->handler = new BlameCartHandler($this->shopUserRepository, $this->orderRepository, $this->orderProcessor);
    }

    public function testBlamesCartWithGivenData(): void
    {
        /** @var OrderInterface|MockObject $cart */
        $cart = $this->createMock(OrderInterface::class);
        /** @var ShopUserInterface|MockObject $user */
        $user = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        $this->shopUserRepository->expects(self::once())
            ->method('findOneByEmail')
            ->with('sylius@example.com')
            ->willReturn($user);
        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('TOKEN')
            ->willReturn($cart);
        $cart->expects(self::once())->method('getCustomer')->willReturn(null);
        $user->expects(self::once())->method('getCustomer')->willReturn($customerMock);
        $cart->expects(self::once())->method('setCustomerWithAuthorization')->with($customerMock);
        $this->orderProcessor->expects(self::once())->method('process')->with($cart);
        $this->handler->__invoke(new BlameCart('sylius@example.com', 'TOKEN'));
    }

    public function testThrowsAnExceptionIfCartIsOccupied(): void
    {
        /** @var OrderInterface|MockObject $cart */
        $cart = $this->createMock(OrderInterface::class);
        /** @var ShopUserInterface|MockObject $user */
        $user = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        $this->shopUserRepository->expects(self::once())
            ->method('findOneByEmail')
            ->with('sylius@example.com')
            ->willReturn($user);
        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('TOKEN')->willReturn($cart);
        $cart->expects(self::once())->method('getCustomer')->willReturn($customerMock);
        self::expectException(\InvalidArgumentException::class);
        $this->handler->__invoke(new BlameCart('sylius@example.com', 'TOKEN'));
    }

    public function testThrowsAnExceptionIfCartHasNotBeenFound(): void
    {
        /** @var ShopUserInterface|MockObject $user */
        $user = $this->createMock(ShopUserInterface::class);
        $this->shopUserRepository->expects(self::once())->method('findOneByEmail')->with('sylius@example.com')->willReturn($user);
        self::expectException(\InvalidArgumentException::class);
        $this->handler->__invoke(new BlameCart('sylius@example.com', 'TOKEN'));
    }

    public function testThrowsAnExceptionIfUserHasNotBeenFound(): void
    {
        self::expectException(\InvalidArgumentException::class);
        $this->handler->__invoke(new BlameCart('sylius@example.com', 'TOKEN'));
    }
}
