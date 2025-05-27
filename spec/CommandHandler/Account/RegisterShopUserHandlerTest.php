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

namespace Tests\Sylius\Bundle\ApiBundle\CommandHandler\Account;

use Doctrine\Persistence\ObjectManager;
use DomainException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use spec\Sylius\Bundle\ApiBundle\CommandHandler\MessageHandlerAttributeTrait;
use Sylius\Bundle\ApiBundle\Command\Account\RegisterShopUser;
use Sylius\Bundle\ApiBundle\Command\Account\SendAccountRegistrationEmail;
use Sylius\Bundle\ApiBundle\Command\Account\SendShopUserVerificationEmail;
use Sylius\Bundle\ApiBundle\CommandHandler\Account\RegisterShopUserHandler;
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
    /** @var FactoryInterface|MockObject */
    private MockObject $shopUserFactoryMock;

    /** @var ObjectManager|MockObject */
    private MockObject $shopUserManagerMock;

    /** @var CustomerResolverInterface|MockObject */
    private MockObject $customerResolverMock;

    /** @var ChannelRepositoryInterface|MockObject */
    private MockObject $channelRepositoryMock;

    /** @var GeneratorInterface|MockObject */
    private MockObject $generatorMock;

    /** @var MessageBusInterface|MockObject */
    private MockObject $commandBusMock;

    private RegisterShopUserHandler $registerShopUserHandler;

    use MessageHandlerAttributeTrait;

    protected function setUp(): void
    {
        $this->shopUserFactoryMock = $this->createMock(FactoryInterface::class);
        $this->shopUserManagerMock = $this->createMock(ObjectManager::class);
        $this->customerResolverMock = $this->createMock(CustomerResolverInterface::class);
        $this->channelRepositoryMock = $this->createMock(ChannelRepositoryInterface::class);
        $this->generatorMock = $this->createMock(GeneratorInterface::class);
        $this->commandBusMock = $this->createMock(MessageBusInterface::class);
        $this->registerShopUserHandler = new RegisterShopUserHandler($this->shopUserFactoryMock, $this->shopUserManagerMock, $this->customerResolverMock, $this->channelRepositoryMock, $this->generatorMock, $this->commandBusMock);
    }

    public function testCreatesAShopUserWithGivenData(): void
    {
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $command = new RegisterShopUser(
            channelCode: 'CHANNEL_CODE',
            localeCode: 'en_US',
            firstName: 'Will',
            lastName: 'Smith',
            email: 'WILL.SMITH@example.com',
            password: 'iamrobot',
            subscribedToNewsletter: true,
        );
        $this->shopUserFactoryMock->expects($this->once())->method('createNew')->willReturn($shopUserMock);
        $this->customerResolverMock->expects($this->once())->method('resolve')->with('WILL.SMITH@example.com')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn(null);
        $shopUserMock->expects($this->once())->method('setPlainPassword')->with('iamrobot');
        $customerMock->expects($this->once())->method('setFirstName')->with('Will');
        $customerMock->expects($this->once())->method('setLastName')->with('Smith');
        $customerMock->expects($this->once())->method('setSubscribedToNewsletter')->with(true);
        $customerMock->expects($this->once())->method('setUser')->with($shopUserMock);
        $this->channelRepositoryMock->expects($this->once())->method('findOneByCode')->with('CHANNEL_CODE')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isAccountVerificationRequired')->willReturn(true);
        $this->generatorMock->expects($this->once())->method('generate')->willReturn('TOKEN');
        $shopUserMock->expects($this->once())->method('setEmailVerificationToken')->with('TOKEN');
        $this->shopUserManagerMock->expects($this->once())->method('persist')->with($shopUserMock);
        $sendRegistrationEmailCommand = new SendAccountRegistrationEmail('WILL.SMITH@example.com', 'en_US', 'CHANNEL_CODE');
        $this->commandBusMock->expects($this->once())->method('dispatch')->with($sendRegistrationEmailCommand, [new DispatchAfterCurrentBusStamp()])
            ->willReturn(new Envelope($sendRegistrationEmailCommand))
        ;
        $sendVerificationEmailCommand = new SendShopUserVerificationEmail('WILL.SMITH@example.com', 'en_US', 'CHANNEL_CODE');
        $this->commandBusMock->expects($this->once())->method('dispatch')->with($sendVerificationEmailCommand, [new DispatchAfterCurrentBusStamp()])
            ->willReturn(new Envelope($sendVerificationEmailCommand))
        ;
        $this->assertSame($shopUserMock, $this($command));
    }

    public function testCreatesAShopUserWithGivenDataAndVerifiesIt(): void
    {
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $command = new RegisterShopUser(
            channelCode: 'CHANNEL_CODE',
            localeCode: 'en_US',
            firstName: 'Will',
            lastName: 'Smith',
            email: 'WILL.SMITH@example.com',
            password: 'iamrobot',
            subscribedToNewsletter: true,
        );
        $this->shopUserFactoryMock->expects($this->once())->method('createNew')->willReturn($shopUserMock);
        $this->customerResolverMock->expects($this->once())->method('resolve')->with('WILL.SMITH@example.com')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn(null);
        $shopUserMock->expects($this->once())->method('setPlainPassword')->with('iamrobot');
        $customerMock->expects($this->once())->method('setFirstName')->with('Will');
        $customerMock->expects($this->once())->method('setLastName')->with('Smith');
        $customerMock->expects($this->once())->method('setSubscribedToNewsletter')->with(true);
        $customerMock->expects($this->once())->method('setUser')->with($shopUserMock);
        $this->shopUserManagerMock->expects($this->once())->method('persist')->with($shopUserMock);
        $sendRegistrationEmailCommand = new SendAccountRegistrationEmail('WILL.SMITH@example.com', 'en_US', 'CHANNEL_CODE');
        $this->commandBusMock->expects($this->once())->method('dispatch')->with($sendRegistrationEmailCommand, [new DispatchAfterCurrentBusStamp()])
            ->willReturn(new Envelope($sendRegistrationEmailCommand))
        ;
        $this->channelRepositoryMock->expects($this->once())->method('findOneByCode')->with('CHANNEL_CODE')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('isAccountVerificationRequired')->willReturn(false);
        $shopUserMock->setEnabled(true);
        $this->assertSame($shopUserMock, $this($command));
    }

    public function testThrowsAnExceptionIfCustomerWithUserAlreadyExists(): void
    {
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ShopUserInterface|MockObject $existingShopUserMock */
        $existingShopUserMock = $this->createMock(ShopUserInterface::class);
        $this->shopUserFactoryMock->expects($this->once())->method('createNew')->willReturn($shopUserMock);
        $this->customerResolverMock->expects($this->once())->method('resolve')->with('WILL.SMITH@example.com')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getUser')->willReturn($existingShopUserMock);
        $this->shopUserManagerMock->expects($this->never())->method('persist')->with($shopUserMock);
        $sendRegistrationEmailCommand = new SendAccountRegistrationEmail('WILL.SMITH@example.com', 'en_US', 'CHANNEL_CODE');
        $this->commandBusMock->expects($this->never())->method('dispatch')->with($sendRegistrationEmailCommand)->willReturn(new Envelope($sendRegistrationEmailCommand));
        $this->expectException(DomainException::class);
        $this->registerShopUserHandler->__invoke(new RegisterShopUser(
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
