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

namespace Sylius\Bundle\ApiBundle\StateProvider\Input;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Sylius\Bundle\ApiBundle\Command\Account\VerifyCustomerAccount;
use Webmozart\Assert\Assert;

/**
 * @experimental
 *
 * @implements ProviderInterface<VerifyCustomerAccount>
 */
final class VerifyCustomerAccountStateProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (!is_a($operation->getClass(), VerifyCustomerAccount::class, true)) {
            return null;
        }

        Assert::stringNotEmpty($uriVariables['token'] ?? null, 'Token is required.');

        return new VerifyCustomerAccount($uriVariables['token']);
    }
}
