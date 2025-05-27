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

namespace Tests\Sylius\Bundle\ApiBundle\Context;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Context\TokenValueBasedCartContext;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class TokenValueBasedCartContextTest extends TestCase
{
    /** @var RequestStack|MockObject */
    private MockObject $requestStackMock;

    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;

    private TokenValueBasedCartContext $tokenValueBasedCartContext;

    protected function setUp(): void
    {
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->tokenValueBasedCartContext = new TokenValueBasedCartContext($this->requestStackMock, $this->orderRepositoryMock, '/api/v2');
    }

    public function testImplementsCartContextInterface(): void
    {
        $this->assertInstanceOf(CartContextInterface::class, $this->tokenValueBasedCartContext);
    }

    public function testReturnsCartByTokenValue(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        $requestMock->attributes = new ParameterBag(['tokenValue' => 'TOKEN_VALUE']);
        $requestMock->expects($this->once())->method('getRequestUri')->willReturn('/api/v2/orders/TOKEN_VALUE');
        $this->requestStackMock->expects($this->once())->method('getMainRequest')->willReturn($requestMock);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN_VALUE')->willReturn($cartMock);
        $this->assertSame($cartMock, $this->tokenValueBasedCartContext->getCart());
    }

    public function testThrowsAnExceptionIfThereIsNoMasterRequestOnRequestStack(): void
    {
        $this->requestStackMock->expects($this->once())->method('getMainRequest')->willReturn(null);
        $this->expectException(CartNotFoundException::class);
        $this->tokenValueBasedCartContext->expectExceptionMessage('There is no main request on request stack.');
        $this->tokenValueBasedCartContext->getCart();
    }

    public function testThrowsAnExceptionIfTheRequestIsNotAnApiRequest(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->attributes = new ParameterBag([]);
        $requestMock->expects($this->once())->method('getRequestUri')->willReturn('/orders');
        $this->requestStackMock->expects($this->once())->method('getMainRequest')->willReturn($requestMock);
        $this->expectException(CartNotFoundException::class);
        $this->tokenValueBasedCartContext->expectExceptionMessage('The main request is not an API request.');
        $this->tokenValueBasedCartContext->getCart();
    }

    public function testThrowsAnExceptionIfThereIsNoTokenValue(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->attributes = new ParameterBag([]);
        $requestMock->expects($this->once())->method('getRequestUri')->willReturn('/api/v2/orders');
        $this->requestStackMock->expects($this->once())->method('getMainRequest')->willReturn($requestMock);
        $this->expectException(CartNotFoundException::class);
        $this->tokenValueBasedCartContext->expectExceptionMessage('Sylius was not able to find the cart, as there is no passed token value.');
        $this->tokenValueBasedCartContext->getCart();
    }

    public function testThrowsAnExceptionIfThereIsNoCartWithGivenTokenValue(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->attributes = new ParameterBag(['tokenValue' => 'TOKEN_VALUE']);
        $requestMock->expects($this->once())->method('getRequestUri')->willReturn('/api/v2/orders/TOKEN_VALUE');
        $this->requestStackMock->expects($this->once())->method('getMainRequest')->willReturn($requestMock);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValue')->with('TOKEN_VALUE')->willReturn(null);
        $this->expectException(CartNotFoundException::class);
        $this->tokenValueBasedCartContext->expectExceptionMessage('Sylius was not able to find the cart for passed token value.');
        $this->tokenValueBasedCartContext->getCart();
    }
}
