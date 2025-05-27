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
use Sylius\Bundle\ApiBundle\EventListener\AuthenticationSuccessListener;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticationSuccessListenerTest extends TestCase
{
    /** @var IriConverterInterface|MockObject */
    private MockObject $iriConverterMock;

    private AuthenticationSuccessListener $authenticationSuccessListener;

    protected function setUp(): void
    {
        $this->iriConverterMock = $this->createMock(IriConverterInterface::class);
        $this->authenticationSuccessListener = new AuthenticationSuccessListener($this->iriConverterMock);
    }

    public function testAddsCustomersToShopAuthenticationTokenResponse(): void
    {
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        $event = new AuthenticationSuccessEvent([], $shopUserMock, new Response());
        $shopUserMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->iriConverterMock->expects($this->once())->method('getIriFromResource')->with($customerMock);
        $this->authenticationSuccessListener->onAuthenticationSuccessResponse($event);
    }

    public function testDoesNotAddAnythingToAdminAuthenticationTokenResponse(): void
    {
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        $event = new AuthenticationSuccessEvent([], $adminUserMock, new Response());
        $this->iriConverterMock->expects($this->never())->method('getIriFromResource')->with($customerMock);
        $this->authenticationSuccessListener->onAuthenticationSuccessResponse($event);
    }
}
