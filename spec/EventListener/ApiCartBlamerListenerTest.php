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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Command\Cart\BlameCart;
use Sylius\Bundle\ApiBundle\EventListener\ApiCartBlamerListener;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiOrdersSubSection;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionInterface;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Context\CartNotFoundException;
use Sylius\Resource\Exception\UnexpectedTypeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class ApiCartBlamerListenerTest extends TestCase
{
    /** @var CartContextInterface|MockObject */
    private MockObject $cartContextMock;

    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionResolverMock;

    /** @var MessageBusInterface|MockObject */
    private MockObject $commandBusMock;

    private ApiCartBlamerListener $apiCartBlamerListener;

    protected function setUp(): void
    {
        $this->cartContextMock = $this->createMock(CartContextInterface::class);
        $this->sectionResolverMock = $this->createMock(SectionProviderInterface::class);
        $this->commandBusMock = $this->createMock(MessageBusInterface::class);
        $this->apiCartBlamerListener = new ApiCartBlamerListener($this->cartContextMock, $this->sectionResolverMock, $this->commandBusMock);
    }

    public function testThrowsAnExceptionWhenCartDoesNotImplementCoreOrderInterfaceOnInteractiveLogin(): void
    {
        /** @var \Sylius\Component\Order\Model\OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(\Sylius\Component\Order\Model\OrderInterface::class);
        /** @var ShopUserInterface|MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var ShopApiOrdersSubSection|MockObject $shopApiOrdersSubSectionSectionMock */
        $shopApiOrdersSubSectionSectionMock = $this->createMock(ShopApiOrdersSubSection::class);
        /** @var AuthenticatorInterface|MockObject $authenticatorMock */
        $authenticatorMock = $this->createMock(AuthenticatorInterface::class);
        /** @var Passport|MockObject $passportMock */
        $passportMock = $this->createMock(Passport::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopApiOrdersSubSectionSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willReturn($orderMock);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->expectException(UnexpectedTypeException::class);
        $this->apiCartBlamerListener->onLoginSuccess(new LoginSuccessEvent(
            $authenticatorMock,
            $passportMock,
            $tokenMock,
            $requestMock,
            null,
            'api_shop',
        ));
    }

    public function testBlamesCartOnUserOnInteractiveLogin(): void
    {
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var ShopUserInterface|MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopApiOrdersSubSection|MockObject $shopApiOrdersSubSectionSectionMock */
        $shopApiOrdersSubSectionSectionMock = $this->createMock(ShopApiOrdersSubSection::class);
        /** @var AuthenticatorInterface|MockObject $authenticatorMock */
        $authenticatorMock = $this->createMock(AuthenticatorInterface::class);
        /** @var Passport|MockObject $passportMock */
        $passportMock = $this->createMock(Passport::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopApiOrdersSubSectionSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('isCreatedByGuest')->willReturn(true);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $userMock->expects($this->once())->method('getEmail')->willReturn('email@sylius.com');
        $cartMock->expects($this->once())->method('getTokenValue')->willReturn('TOKEN');
        $blameCart = new BlameCart('email@sylius.com', 'TOKEN');
        $this->commandBusMock->expects($this->once())->method('dispatch')->with($blameCart)
            ->willReturn(new Envelope($blameCart))
        ;
        $this->apiCartBlamerListener->onLoginSuccess(
            new LoginSuccessEvent(
                $authenticatorMock,
                $passportMock,
                $tokenMock,
                $requestMock,
                null,
                'api_shop',
            ),
        );
    }

    public function testDoesNothingIfGivenCartHasBeenBlamedInPast(): void
    {
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopApiOrdersSubSection|MockObject $shopApiOrdersSubSectionSectionMock */
        $shopApiOrdersSubSectionSectionMock = $this->createMock(ShopApiOrdersSubSection::class);
        /** @var AuthenticatorInterface|MockObject $authenticatorMock */
        $authenticatorMock = $this->createMock(AuthenticatorInterface::class);
        /** @var Passport|MockObject $passportMock */
        $passportMock = $this->createMock(Passport::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopApiOrdersSubSectionSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willReturn($cartMock);
        $cartMock->expects($this->once())->method('isCreatedByGuest')->willReturn(false);
        $cartMock->expects($this->never())->method('setCustomer');
        $this->apiCartBlamerListener->onLoginSuccess(
            new LoginSuccessEvent(
                $authenticatorMock,
                $passportMock,
                $tokenMock,
                $requestMock,
                null,
                'api_shop',
            ),
        );
    }

    public function testDoesNothingIfGivenUserIsInvalidOnInteractiveLogin(): void
    {
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var ShopApiOrdersSubSection|MockObject $shopApiOrdersSubSectionSectionMock */
        $shopApiOrdersSubSectionSectionMock = $this->createMock(ShopApiOrdersSubSection::class);
        /** @var AuthenticatorInterface|MockObject $authenticatorMock */
        $authenticatorMock = $this->createMock(AuthenticatorInterface::class);
        /** @var Passport|MockObject $passportMock */
        $passportMock = $this->createMock(Passport::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopApiOrdersSubSectionSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willReturn($cartMock);
        $tokenMock->expects($this->once())->method('getUser')->willReturn(null);
        $cartMock->expects($this->never())->method('setCustomer');
        $this->apiCartBlamerListener->onLoginSuccess(
            new LoginSuccessEvent(
                $authenticatorMock,
                $passportMock,
                $tokenMock,
                $requestMock,
                null,
                'api_shop',
            ),
        );
    }

    public function testDoesNothingIfThereIsNoExistingCartOnInteractiveLogin(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var ShopUserInterface|MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var ShopApiOrdersSubSection|MockObject $shopApiOrdersSubSectionMock */
        $shopApiOrdersSubSectionMock = $this->createMock(ShopApiOrdersSubSection::class);
        /** @var AuthenticatorInterface|MockObject $authenticatorMock */
        $authenticatorMock = $this->createMock(AuthenticatorInterface::class);
        /** @var Passport|MockObject $passportMock */
        $passportMock = $this->createMock(Passport::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($shopApiOrdersSubSectionMock);
        $this->cartContextMock->expects($this->once())->method('getCart')->willThrowException(CartNotFoundException::class);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $this->apiCartBlamerListener->onLoginSuccess(
            new LoginSuccessEvent(
                $authenticatorMock,
                $passportMock,
                $tokenMock,
                $requestMock,
                null,
                'api_shop',
            ),
        );
    }

    public function testDoesNothingIfTheCurrentSectionIsNotShopOnInteractiveLogin(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var SectionInterface|MockObject $sectionMock */
        $sectionMock = $this->createMock(SectionInterface::class);
        /** @var AuthenticatorInterface|MockObject $authenticatorMock */
        $authenticatorMock = $this->createMock(AuthenticatorInterface::class);
        /** @var Passport|MockObject $passportMock */
        $passportMock = $this->createMock(Passport::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $tokenMock->expects($this->never())->method('getUser');
        $this->cartContextMock->expects($this->never())->method('getCart');
        $this->apiCartBlamerListener->onLoginSuccess(
            new LoginSuccessEvent(
                $authenticatorMock,
                $passportMock,
                $tokenMock,
                $requestMock,
                null,
                'api_shop',
            ),
        );
    }

    public function testDoesNothingIfTheCurrentSectionIsNotOrdersSubsection(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var AdminApiSection|MockObject $sectionMock */
        $sectionMock = $this->createMock(AdminApiSection::class);
        /** @var AuthenticatorInterface|MockObject $authenticatorMock */
        $authenticatorMock = $this->createMock(AuthenticatorInterface::class);
        /** @var Passport|MockObject $passportMock */
        $passportMock = $this->createMock(Passport::class);
        $this->sectionResolverMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $tokenMock->expects($this->never())->method('getUser');
        $this->cartContextMock->expects($this->never())->method('getCart');
        $this->apiCartBlamerListener->onLoginSuccess(
            new LoginSuccessEvent(
                $authenticatorMock,
                $passportMock,
                $tokenMock,
                $requestMock,
                null,
                'api_shop',
            ),
        );
    }
}
