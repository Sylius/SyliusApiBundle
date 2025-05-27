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
use Sylius\Bundle\ApiBundle\Context\TokenBasedUserContext;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class TokenBasedUserContextTest extends TestCase
{
    /** @var TokenStorageInterface|MockObject */
    private MockObject $tokenStorageMock;

    private TokenBasedUserContext $tokenBasedUserContext;

    protected function setUp(): void
    {
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->tokenBasedUserContext = new TokenBasedUserContext($this->tokenStorageMock);
    }

    public function testImplementsUserContextInterface(): void
    {
        $this->assertInstanceOf(UserContextInterface::class, $this->tokenBasedUserContext);
    }

    public function testReturnsUserFromToken(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var UserInterface|MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $this->tokenStorageMock->expects($this->once())->method('getToken')->willReturn($tokenMock);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->assertSame($userMock, $this->tokenBasedUserContext->getUser());
    }

    public function testReturnsNullIfUserFromTokenIsAnonymous(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        $this->tokenStorageMock->expects($this->once())->method('getToken')->willReturn($tokenMock);
        $tokenMock->expects($this->once())->method('getUser')->willReturn(null);
        $this->assertNull($this->tokenBasedUserContext->getUser());
    }

    public function testReturnsNullIfUserFromTokenIsNull(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        $this->tokenStorageMock->expects($this->once())->method('getToken')->willReturn($tokenMock);
        $tokenMock->expects($this->once())->method('getUser')->willReturn(null);
        $this->assertNull($this->tokenBasedUserContext->getUser());
    }

    public function testReturnsNullIfNoTokenIsSetInTokenStorage(): void
    {
        $this->tokenStorageMock->expects($this->once())->method('getToken')->willReturn(null);
        $this->assertNull($this->tokenBasedUserContext->getUser());
    }
}
