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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Command\Account\SendAccountRegistrationEmail;
use Sylius\Bundle\ApiBundle\CommandHandler\Account\SendAccountRegistrationEmailHandler;
use Sylius\Bundle\ApiBundle\spec\CommandHandler\MessageHandlerAttributeTrait;
use Sylius\Bundle\CoreBundle\Mailer\AccountRegistrationEmailManagerInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;

final class SendAccountRegistrationEmailHandlerTest extends TestCase
{
    private MockObject&UserRepositoryInterface $shopUserRepository;

    private ChannelRepositoryInterface&MockObject $channelRepository;

    private AccountRegistrationEmailManagerInterface&MockObject $accountRegistrationEmailManager;

    private SendAccountRegistrationEmailHandler $handler;

    use MessageHandlerAttributeTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shopUserRepository = $this->createMock(UserRepositoryInterface::class);
        $this->channelRepository = $this->createMock(ChannelRepositoryInterface::class);
        $this->accountRegistrationEmailManager = $this->createMock(AccountRegistrationEmailManagerInterface::class);
        $this->handler = new SendAccountRegistrationEmailHandler(
            $this->shopUserRepository,
            $this->channelRepository,
            $this->accountRegistrationEmailManager,
        );
    }

    public function testSendsUserAccountRegistrationEmailWhenAccountVerificationIsNotRequired(): void
    {
        /** @var ShopUserInterface|MockObject $shopUser */
        $shopUser = $this->createMock(ShopUserInterface::class);
        /** @var ChannelInterface|MockObject $channel */
        $channel = $this->createMock(ChannelInterface::class);
        $this->shopUserRepository->expects(self::once())
            ->method('findOneByEmail')
            ->with('shop@example.com')
            ->willReturn($shopUser);
        $this->channelRepository->expects(self::once())
            ->method('findOneByCode')
            ->with('WEB')
            ->willReturn($channel);
        $channel->expects(self::once())->method('isAccountVerificationRequired')->willReturn(false);
        $this->accountRegistrationEmailManager->expects(self::once())
            ->method('sendAccountRegistrationEmail')
            ->with($shopUser, $channel, 'en_US');
        $this->handler->__invoke(new SendAccountRegistrationEmail('shop@example.com', 'en_US', 'WEB'));
    }

    public function testSendsUserRegistrationEmailWhenAccountVerificationRequiredAndUserIsEnabled(): void
    {
        /** @var ShopUserInterface|MockObject $shopUser */
        $shopUser = $this->createMock(ShopUserInterface::class);
        /** @var ChannelInterface|MockObject $channel */
        $channel = $this->createMock(ChannelInterface::class);
        $this->shopUserRepository->expects(self::once())
            ->method('findOneByEmail')
            ->with('shop@example.com')
            ->willReturn($shopUser);
        $this->channelRepository->expects(self::once())
            ->method('findOneByCode')
            ->with('WEB')
            ->willReturn($channel);
        $channel->expects(self::once())->method('isAccountVerificationRequired')->willReturn(true);
        $shopUser->expects(self::once())->method('isEnabled')->willReturn(true);
        $this->accountRegistrationEmailManager->expects(self::once())
            ->method('sendAccountRegistrationEmail')
            ->with($shopUser, $channel, 'en_US');
        $this->handler->__invoke(new SendAccountRegistrationEmail('shop@example.com', 'en_US', 'WEB'));
    }

    public function testDoesNothingWhenAccountVerificationIsRequiredAndUserIsDisabled(): void
    {
        /** @var ShopUserInterface|MockObject $shopUser */
        $shopUser = $this->createMock(ShopUserInterface::class);
        /** @var ChannelInterface|MockObject $channel */
        $channel = $this->createMock(ChannelInterface::class);
        $this->shopUserRepository->expects(self::once())
            ->method('findOneByEmail')
            ->with('shop@example.com')
            ->willReturn($shopUser);
        $this->channelRepository->expects(self::once())
            ->method('findOneByCode')
            ->with('WEB')
            ->willReturn($channel);
        $channel->expects(self::once())->method('isAccountVerificationRequired')->willReturn(true);
        $shopUser->expects(self::once())->method('isEnabled')->willReturn(false);
        $this->accountRegistrationEmailManager->expects(self::never())
            ->method('sendAccountRegistrationEmail')
            ->with($this->any());
        $this->handler->__invoke(new SendAccountRegistrationEmail('shop@example.com', 'en_US', 'WEB'));
    }
}
