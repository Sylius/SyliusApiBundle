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

namespace Sylius\Bundle\ApiBundle\CommandHandler\Cart;

use Sylius\Bundle\ApiBundle\Command\Cart\InformAboutCartRecalculate;
use Sylius\Bundle\ApiBundle\Exception\OrderNoLongerEligibleForPromotion;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class InformAboutCartRecalculateHandler implements MessageHandlerInterface
{
    public function __invoke(InformAboutCartRecalculate $command): void
    {
        throw new OrderNoLongerEligibleForPromotion();
    }
}
