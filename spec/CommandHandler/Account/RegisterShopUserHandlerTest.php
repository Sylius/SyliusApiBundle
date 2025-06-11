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

namespace Sylius\Bundle\ApiBundle\spec\CommandHandler\Account;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Command\Account\RegisterShopUser;
use Sylius\Bundle\ApiBundle\Command\Account\SendAccountRegistrationEmail;
use Sylius\Bundle\ApiBundle\Command\Account\SendShopUserVerificationEmail;
use Sylius\Bundle\ApiBundle\CommandHandler\Account\RegisterShopUserHandler;
use Sylius\Bundle\ApiBundle\spec\CommandHandler\MessageHandlerAttributeTrait;
use Sylius\Bundle\CoreBundle\Resolver\CustomerResolverInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\User\Security\Generator\GeneratorInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

final class RegisterShopUserHandlerTest extends TestCase
{
    private FactoryInterface&MockObject $shopUserFactory;

    private MockObject&ObjectManager $shopUserManager;

    private CustomerResolverInterface&MockObject $customerResolver;

    private ChannelRepositoryInterface&MockObject $channelRepository;

    private GeneratorInterface&MockObject $generator;

    private MessageBusInterface&MockObject $commandBus;

    private RegisterShopUserHandler $handler;

    use MessageHandlerAttributeTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shopUserFactory = $this->createMock(FactoryInterface::class);
        $this->shopUserManager = $this->createMock(ObjectManager::class);
        $this->customerResolver = $this->createMock(CustomerResolverInterface::class);
        $this->channelRepository = $this->createMock(ChannelRepositoryInterface::class);
        $this->generator = $this->createMock(GeneratorInterface::class);
        $this->commandBus = $this->createMock(MessageBusInterface::class);
        $this->handler = new RegisterShopUserHandler(
            $this->shopUserFactory,
            $this->shopUserManager,
            $this->customerResolver,
            $this->channelRepository,
            $this->generator,
            $this->commandBus,
        );
    }

    public function testCreatesAShopUserWithGivenData(): void
    {
        /** @var ShopUserInterface|MockObject $shopUser */
        $shopUser = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customer */
        $customer = $this->createMock(CustomerInterface::class);
        /** @var ChannelInterface|MockObject $channel */
        $channel = $this->createMock(ChannelInterface::class);
        $command = new RegisterShopUser(
            channelCode: 'CHANNEL_CODE',
            localeCode: 'en_US',
            firstName: 'Will',
            lastName: 'Smith',
            email: 'WILL.SMITH@example.com',
            password: 'iamrobot',
            subscribedToNewsletter: true,
        );
        $this->shopUserFactory->expects(self::once())->method('createNew')->willReturn($shopUser);
        $this->customerResolver->expects(self::once())
            ->method('resolve')
            ->with('WILL.SMITH@example.com')
            ->willReturn($customer);
        $customer->expects(self::once())->method('getUser')->willReturn(null);
        $shopUser->expects(self::once())->method('setPlainPassword')->with('iamrobot');
        $customer->expects(self::once())->method('setFirstName')->with('Will');
        $customer->expects(self::once())->method('setLastName')->with('Smith');
        $customer->expects(self::once())->method('setSubscribedToNewsletter')->with(true);
        $customer->expects(self::once())->method('setUser')->with($shopUser);
        $this->channelRepository
            ->expects(self::once())
            ->method('findOneByCode')
            ->with('CHANNEL_CODE')
            ->willReturn($channel);
        $channel->expects(self::once())->method('isAccountVerificationRequired')->willReturn(true);
        $this->generator->expects(self::once())->method('generate')->willReturn('TOKEN');
        $shopUser->expects(self::once())->method('setEmailVerificationToken')->with('TOKEN');
        $this->shopUserManager->expects(self::once())->method('persist')->with($shopUser);
        $sendRegistrationEmailCommand = new SendAccountRegistrationEmail(
            'WILL.SMITH@example.com',
            'en_US',
            'CHANNEL_CODE',
        );
        $sendVerificationEmailCommand = new SendShopUserVerificationEmail(
            'WILL.SMITH@example.com',
            'en_US',
            'CHANNEL_CODE',
        );
        $this->commandBus->expects(self::exactly(2))
            ->method('dispatch')
            ->with(
                self::callback(function ($command) use ($sendRegistrationEmailCommand, $sendVerificationEmailCommand) {
                    return $command == $sendRegistrationEmailCommand || $command == $sendVerificationEmailCommand;
                }),
                [new DispatchAfterCurrentBusStamp()],
            )
            ->willReturnOnConsecutiveCalls(
                new Envelope($sendRegistrationEmailCommand),
                new Envelope($sendVerificationEmailCommand),
            );
        self::assertSame($shopUser, $this->handler->__invoke($command));
    }

    public function testCreatesAShopUserWithGivenDataAndVerifiesIt(): void
    {
        /** @var ShopUserInterface|MockObject $shopUser */
        $shopUser = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customer */
        $customer = $this->createMock(CustomerInterface::class);
        /** @var ChannelInterface|MockObject $channel */
        $channel = $this->createMock(ChannelInterface::class);
        $command = new RegisterShopUser(
            channelCode: 'CHANNEL_CODE',
            localeCode: 'en_US',
            firstName: 'Will',
            lastName: 'Smith',
            email: 'WILL.SMITH@example.com',
            password: 'iamrobot',
            subscribedToNewsletter: true,
        );
        $this->shopUserFactory->expects(self::once())->method('createNew')->willReturn($shopUser);
        $this->customerResolver->expects(self::once())
            ->method('resolve')
            ->with('WILL.SMITH@example.com')
            ->willReturn($customer);
        $customer->expects(self::once())->method('getUser')->willReturn(null);
        $shopUser->expects(self::once())->method('setPlainPassword')->with('iamrobot');
        $customer->expects(self::once())->method('setFirstName')->with('Will');
        $customer->expects(self::once())->method('setLastName')->with('Smith');
        $customer->expects(self::once())->method('setSubscribedToNewsletter')->with(true);
        $customer->expects(self::once())->method('setUser')->with($shopUser);
        $this->shopUserManager->expects(self::once())->method('persist')->with($shopUser);
        $sendRegistrationEmailCommand = new SendAccountRegistrationEmail(
            'WILL.SMITH@example.com',
            'en_US',
            'CHANNEL_CODE',
        );
        $this->commandBus->expects(self::once())
            ->method('dispatch')
            ->with($sendRegistrationEmailCommand, [new DispatchAfterCurrentBusStamp()])
            ->willReturn(new Envelope($sendRegistrationEmailCommand));
        $this->channelRepository->expects(self::once())
            ->method('findOneByCode')
            ->with('CHANNEL_CODE')
            ->willReturn($channel);
        $channel->expects(self::once())->method('isAccountVerificationRequired')->willReturn(false);
        $shopUser->setEnabled(true);
        self::assertSame($shopUser, $this->handler->__invoke($command));
    }

    public function testThrowsAnExceptionIfCustomerWithUserAlreadyExists(): void
    {
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject $existingShopUserMock */
        $existingShopUserMock = $this->createMock(ShopUserInterface::class);
        $this->shopUserFactory->expects(self::once())->method('createNew')->willReturn($shopUserMock);
        $this->customerResolver->expects(self::once())->method('resolve')->with('WILL.SMITH@example.com')->willReturn($customerMock);
        $customerMock->expects(self::once())->method('getUser')->willReturn($existingShopUserMock);
        $this->shopUserManager->expects(self::never())->method('persist')->with($shopUserMock);
        $sendRegistrationEmailCommand = new SendAccountRegistrationEmail('WILL.SMITH@example.com', 'en_US', 'CHANNEL_CODE');
        $this->commandBus->expects(self::never())->method('dispatch')->with($sendRegistrationEmailCommand)->willReturn(new Envelope($sendRegistrationEmailCommand));
        self::expectException(\DomainException::class);
        $this->handler->__invoke(new RegisterShopUser(
            channelCode: 'CHANNEL_CODE',
            localeCode: 'en_US',
            firstName: 'Will',
            lastName: 'Smith',
            email: 'WILL.SMITH@example.com',
            password: 'iamrobot',
            subscribedToNewsletter: true,
        ));
    }
}
