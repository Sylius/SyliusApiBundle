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

namespace Sylius\Bundle\ApiBundle\CommandHandler\Account;

use Sylius\Bundle\ApiBundle\Command\Account\ResendVerificationEmail;
use Sylius\Bundle\ApiBundle\Command\Account\SendAccountVerificationEmail;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\User\Model\UserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Sylius\Component\User\Security\Generator\GeneratorInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Webmozart\Assert\Assert;

/** @experimental  */
final class ResendVerificationEmailHandler implements MessageHandlerInterface
{
    /** @var UserRepositoryInterface */
    private $shopUserRepository;

    /** @var GeneratorInterface */
    private $tokenGenerator;

    /** @var MessageBusInterface */
    private $commandBus;

    public function __construct(
        UserRepositoryInterface $shopUserRepository,
        GeneratorInterface $tokenGenerator,
        MessageBusInterface $commandBus
    ) {
        $this->shopUserRepository = $shopUserRepository;
        $this->tokenGenerator = $tokenGenerator;
        $this->commandBus = $commandBus;
    }

    public function __invoke(ResendVerificationEmail $command): void
    {
        /** @var ShopUserInterface|null $user */
        Assert::notNull($user = $this->shopUserRepository->find($command->getShopUserId()));

        $token = $this->tokenGenerator->generate();
        $user->setEmailVerificationToken($token);
        /** @var CustomerInterface $customer */
        $customer = $user->getCustomer();

        $this->commandBus->dispatch(new SendAccountVerificationEmail(
            $customer->getEmail(),
            $command->getLocaleCode(),
            $command->getChannelCode()
        ), [new DispatchAfterCurrentBusStamp()]);
    }
}
