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

namespace Tests\Sylius\Bundle\ApiBundle\Security;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Security\ShopUserVoter;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class ShopUserVoterTest extends TestCase
{
    private ShopUserVoter $shopUserVoter;

    protected function setUp(): void
    {
        $this->shopUserVoter = new ShopUserVoter();
    }

    public function testDoesNotSupportWrongAttribute(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $this->shopUserVoter->vote($tokenMock, null, ['WRONG_ATTRIBUTE']));
    }

    public function testDeniesAccessWhenUserIsNull(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        $tokenMock->expects($this->once())->method('getUser')->willReturn(null);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->shopUserVoter->vote($tokenMock, null, [ShopUserVoter::SYLIUS_SHOP_USER]));
    }

    public function testDeniesAccessWhenUserIsNotShopUser(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($adminUserMock);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->shopUserVoter->vote($tokenMock, null, [ShopUserVoter::SYLIUS_SHOP_USER]));
    }

    public function testDeniesAccessWhenUserDoesNotHaveRoleUser(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        $shopUserMock->expects($this->once())->method('getRoles')->willReturn(['ROLE_TEST']);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($shopUserMock);
        $this->assertSame(VoterInterface::ACCESS_DENIED, $this->shopUserVoter->vote($tokenMock, null, [ShopUserVoter::SYLIUS_SHOP_USER]));
    }

    public function testGrantsAccessWhenUserHasRoleUser(): void
    {
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        $shopUserMock->expects($this->once())->method('getRoles')->willReturn(['ROLE_USER']);
        $shopUserMock->expects($this->once())->method('getCustomer')->willReturn(null);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($shopUserMock);
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $this->shopUserVoter->vote($tokenMock, null, [ShopUserVoter::SYLIUS_SHOP_USER]));
    }
}
