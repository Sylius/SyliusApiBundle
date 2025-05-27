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

namespace Tests\Sylius\Bundle\ApiBundle\EventListener;

use ApiPlatform\Metadata\IriConverterInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\EventListener\AdminAuthenticationSuccessListener;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\HttpFoundation\Response;

final class AdminAuthenticationSuccessListenerTest extends TestCase
{
    /** @var IriConverterInterface|MockObject */
    private MockObject $iriConverterMock;

    private AdminAuthenticationSuccessListener $adminAuthenticationSuccessListener;

    protected function setUp(): void
    {
        $this->iriConverterMock = $this->createMock(IriConverterInterface::class);
        $this->adminAuthenticationSuccessListener = new AdminAuthenticationSuccessListener($this->iriConverterMock);
    }

    public function testAddsAdminsToAdminAuthenticationTokenResponse(): void
    {
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        $event = new AuthenticationSuccessEvent([], $adminUserMock, new Response());
        $this->iriConverterMock->expects($this->once())->method('getIriFromResource')->with($adminUserMock);
        $this->adminAuthenticationSuccessListener->onAuthenticationSuccessResponse($event);
    }

    public function testDoesNotAddAnythingToShopAuthenticationTokenResponse(): void
    {
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        $event = new AuthenticationSuccessEvent([], $shopUserMock, new Response());
        $this->iriConverterMock->expects($this->never())->method('getIriFromResource')->with($adminUserMock);
        $this->adminAuthenticationSuccessListener->onAuthenticationSuccessResponse($event);
    }
}
