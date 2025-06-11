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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Command\Cart\PickupCart;
use Sylius\Bundle\ApiBundle\CommandHandler\Cart\PickupCartHandler;
use Sylius\Bundle\ApiBundle\spec\CommandHandler\MessageHandlerAttributeTrait;
use Sylius\Bundle\CoreBundle\Factory\OrderFactoryInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Resource\Generator\RandomnessGeneratorInterface;

final class PickupCartHandlerTest extends TestCase
{
    private MockObject&OrderFactoryInterface $cartFactory;

    private MockObject&OrderRepositoryInterface $cartRepository;

    private ChannelRepositoryInterface&MockObject $channelRepository;

    private MockObject&ObjectManager $orderManager;

    private MockObject&RandomnessGeneratorInterface $generator;

    private CustomerRepositoryInterface&MockObject $customerRepository;

    private PickupCartHandler $handler;

    use MessageHandlerAttributeTrait;

    private const TOKEN_LENGTH = 20;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartFactory = $this->createMock(OrderFactoryInterface::class);
        $this->cartRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->channelRepository = $this->createMock(ChannelRepositoryInterface::class);
        $this->orderManager = $this->createMock(ObjectManager::class);
        $this->generator = $this->createMock(RandomnessGeneratorInterface::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->handler = new PickupCartHandler(
            $this->cartFactory,
            $this->cartRepository,
            $this->channelRepository,
            $this->orderManager,
            $this->generator,
            $this->customerRepository,
            self::TOKEN_LENGTH,
        );
    }

    public function testPicksUpANewCartForLoggedInShopUser(): void
    {
        /** @var CustomerInterface|MockObject $customer */
        $customer = $this->createMock(CustomerInterface::class);
        /** @var OrderInterface|MockObject $cart */
        $cart = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channel */
        $channel = $this->createMock(ChannelInterface::class);
        /** @var LocaleInterface|MockObject $localeMock */
        $localeMock = $this->createMock(LocaleInterface::class);
        $pickupCart = new PickupCart(channelCode: 'code', localeCode: 'en_US', email: 'sample@email.com');
        $this->channelRepository->expects(self::once())
            ->method('findOneByCode')
            ->with('code')
            ->willReturn($channel);
        $this->customerRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'sample@email.com'])
            ->willReturn($customer);
        $this->cartRepository->expects(self::once())
            ->method('findLatestNotEmptyCartByChannelAndCustomer')
            ->with($channel, $customer)
            ->willReturn(null);
        $this->generator->expects(self::once())
            ->method('generateUriSafeString')
            ->with(self::TOKEN_LENGTH)
            ->willReturn('urisafestr');
        $localeMock->expects(self::once())->method('getCode')->willReturn('en_US');
        $channel->expects(self::once())->method('getLocales')->willReturn(new ArrayCollection([$localeMock]));
        $this->cartFactory->expects(self::once())
            ->method('createNewCart')
            ->with($channel, $customer, 'en_US', 'urisafestr')
            ->willReturn($cart);
        $this->orderManager->expects(self::once())->method('persist')->with($cart);
        $this->handler->__invoke($pickupCart);
    }

    public function testPicksUpANewCartForLoggedInShopUserWhenTheUserHasNoDefaultAddress(): void
    {
        /** @var CustomerInterface|MockObject $customer */
        $customer = $this->createMock(CustomerInterface::class);
        /** @var OrderInterface|MockObject $cart */
        $cart = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channel */
        $channel = $this->createMock(ChannelInterface::class);
        /** @var LocaleInterface|MockObject $localeMock */
        $localeMock = $this->createMock(LocaleInterface::class);
        $pickupCart = new PickupCart(channelCode: 'code', localeCode: 'en_US', email: 'sample@email.com');
        $this->channelRepository->expects(self::once())
            ->method('findOneByCode')
            ->with('code')
            ->willReturn($channel);
        $channel->method('getDefaultLocale')->willReturn($localeMock);
        $this->customerRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'sample@email.com'])
            ->willReturn($customer);
        $this->cartRepository->expects(self::once())
            ->method('findLatestNotEmptyCartByChannelAndCustomer')
            ->with($channel, $customer)
            ->willReturn(null);
        $this->generator->expects(self::once())
            ->method('generateUriSafeString')
            ->with(self::TOKEN_LENGTH)
            ->willReturn('urisafestr');
        $localeMock->expects(self::once())->method('getCode')->willReturn('en_US');
        $channel->expects(self::once())->method('getLocales')->willReturn(new ArrayCollection([$localeMock]));
        $this->cartFactory->expects(self::once())
            ->method('createNewCart')
            ->with($channel, $customer, 'en_US', 'urisafestr')
            ->willReturn($cart);
        $this->orderManager->expects(self::once())->method('persist')->with($cart);
        $this->handler->__invoke($pickupCart);
    }

    public function testPicksUpAnExistingCartWithTokenForLoggedInShopUser(): void
    {
        /** @var CustomerInterface|MockObject $customer */
        $customer = $this->createMock(CustomerInterface::class);
        /** @var OrderInterface|MockObject $cart */
        $cart = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channel */
        $channel = $this->createMock(ChannelInterface::class);
        $pickupCart = new PickupCart(channelCode: 'code', localeCode: 'en_US', email: 'sample@email.com');
        $this->channelRepository->expects(self::once())
            ->method('findOneByCode')
            ->with('code')
            ->willReturn($channel);
        $this->customerRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'sample@email.com'])
            ->willReturn($customer);
        $this->cartRepository->expects(self::once())
            ->method('findLatestNotEmptyCartByChannelAndCustomer')
            ->with($channel, $customer)
            ->willReturn($cart);
        $cart->expects(self::once())->method('getTokenValue')->willReturn('token');
        $this->orderManager->expects(self::never())->method('persist');
        $this->handler->__invoke($pickupCart);
    }

    public function testPicksUpAnExistingCartWithoutTokenForLoggedInShopUser(): void
    {
        /** @var CustomerInterface|MockObject $customer */
        $customer = $this->createMock(CustomerInterface::class);
        /** @var OrderInterface|MockObject $cart */
        $cart = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channel */
        $channel = $this->createMock(ChannelInterface::class);
        $pickupCart = new PickupCart(channelCode: 'code', localeCode: 'en_US', email: 'sample@email.com');
        $this->channelRepository->expects(self::once())
            ->method('findOneByCode')
            ->with('code')
            ->willReturn($channel);
        $this->customerRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'sample@email.com'])
            ->willReturn($customer);
        $this->generator->expects(self::once())
            ->method('generateUriSafeString')
            ->with(self::TOKEN_LENGTH)
            ->willReturn('urisafestr');
        $this->cartRepository->expects(self::once())
            ->method('findLatestNotEmptyCartByChannelAndCustomer')
            ->with($channel, $customer)
            ->willReturn($cart);
        $this->orderManager->persist($cart);
        $this->handler->__invoke($pickupCart);
    }

    public function testPicksUpACartForVisitor(): void
    {
        /** @var OrderInterface|MockObject $cart */
        $cart = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channel */
        $channel = $this->createMock(ChannelInterface::class);
        /** @var LocaleInterface|MockObject $locale */
        $locale = $this->createMock(LocaleInterface::class);
        $pickupCart = new PickupCart(channelCode: 'code', localeCode: 'en_US');
        $this->channelRepository->expects(self::once())
            ->method('findOneByCode')
            ->with('code')
            ->willReturn($channel);
        $channel->method('getDefaultLocale')->willReturn($locale);
        $this->cartRepository->expects(self::never())->method('findLatestNotEmptyCartByChannelAndCustomer');
        $this->generator->expects(self::once())
            ->method('generateUriSafeString')
            ->with(self::TOKEN_LENGTH)
            ->willReturn('urisafestr');
        $locale->expects(self::once())->method('getCode')->willReturn('en_US');
        $channel->expects(self::once())->method('getLocales')->willReturn(new ArrayCollection([$locale]));
        $this->cartFactory->expects(self::once())
            ->method('createNewCart')
            ->with($channel, null, 'en_US', 'urisafestr')
            ->willReturn($cart);
        $this->orderManager->expects(self::once())->method('persist')->with($cart);
        $this->handler->__invoke($pickupCart);
    }

    public function testPicksUpACartWithLocaleCodeForVisitor(): void
    {
        /** @var OrderInterface|MockObject $cart */
        $cart = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channel */
        $channel = $this->createMock(ChannelInterface::class);
        /** @var LocaleInterface|MockObject $locale */
        $locale = $this->createMock(LocaleInterface::class);
        $pickupCart = new PickupCart(channelCode: 'code', localeCode: 'en_US');
        $this->channelRepository->expects(self::once())
            ->method('findOneByCode')
            ->with('code')
            ->willReturn($channel);
        $channel->method('getDefaultLocale')->willReturn($locale);
        $locale->method('getCode')->willReturn('en_US');
        $this->cartRepository->expects(self::never())->method('findLatestNotEmptyCartByChannelAndCustomer');
        $this->generator->expects(self::once())
            ->method('generateUriSafeString')
            ->with(self::TOKEN_LENGTH)
            ->willReturn('urisafestr');
        $channel->expects(self::once())
            ->method('getLocales')
            ->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$locale]));
        $this->cartFactory->expects(self::once())
            ->method('createNewCart')
            ->with($channel, null, 'en_US', 'urisafestr')
            ->willReturn($cart);
        $this->orderManager->expects(self::once())->method('persist')->with($cart);
        $this->handler->__invoke($pickupCart);
    }

    public function testThrowsExceptionIfLocaleCodeIsNotCorrect(): void
    {
        /** @var OrderInterface|MockObject $cart */
        $cart = $this->createMock(OrderInterface::class);
        /** @var ChannelInterface|MockObject $channel */
        $channel = $this->createMock(ChannelInterface::class);
        /** @var LocaleInterface|MockObject $locale */
        $locale = $this->createMock(LocaleInterface::class);
        $pickupCart = new PickupCart(channelCode: 'code', localeCode: 'ru_RU');
        $this->channelRepository->expects(self::once())
            ->method('findOneByCode')
            ->with('code')
            ->willReturn($channel);
        $channel->method('getDefaultLocale')->willReturn($locale);
        $locale->method('getCode')->willReturn('en_US');
        $locales = new ArrayCollection([]);
        $channel->expects(self::once())->method('getLocales')->willReturn($locales);
        $this->cartRepository->expects(self::never())->method('findLatestNotEmptyCartByChannelAndCustomer');
        $this->generator->method('generateUriSafeString')
            ->with(self::TOKEN_LENGTH)
            ->willReturn('urisafestr');
        $this->cartFactory->method('createNewCart')
            ->with($channel, null, 'en_US', 'urisafestr')
            ->willReturn($cart);
        self::expectException(\InvalidArgumentException::class);
        $this->handler->__invoke($pickupCart);
    }
}
