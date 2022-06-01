<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace spec\Sylius\Bundle\ApiBundle\CommandHandler;

use PhpSpec\ObjectBehavior;
use Sylius\Bundle\ApiBundle\Command\SendContactRequest;
use Sylius\Bundle\ShopBundle\EmailManager\ContactEmailManagerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;

final class SendContactRequestHandlerSpec extends ObjectBehavior
{
    function let(ContactEmailManagerInterface $contactEmailManager, ChannelRepositoryInterface $channelRepository,): void
    {
        $this->beConstructedWith($contactEmailManager, $channelRepository);
    }

    function it_sends_contact_request(
        ChannelInterface $channel,
        ChannelRepositoryInterface $channelRepository,
        ContactEmailManagerInterface $contactEmailManager
    ): void {
        $command = new SendContactRequest('adam@sylius.com','message');
        $command->setChannelCode('CODE');
        $command->setLocaleCode('en_US');

        $channelRepository->findOneByCode('CODE')->willReturn($channel);

        $channel->getContactEmail()->willReturn('channel@contact.com');

        $contactEmailManager->sendContactRequest(
            ['message' => 'message', 'email' => 'adam@sylius.com'],
            ['channel@contact.com'],
            $channel,
            'en_US'
        );

        $this($command);
    }

    function it_throws_an_exception_if_channel_has_not_been_found(ChannelRepositoryInterface $channelRepository): void
    {
        $command = new SendContactRequest('adam@sylius.com','message');
        $command->setChannelCode('CODE');

        $channelRepository->findOneByCode('CODE')->willReturn(null);

        $this
            ->shouldThrow(\InvalidArgumentException::class)
            ->during('__invoke', [$command])
        ;
    }
}
