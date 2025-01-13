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

namespace Sylius\Bundle\ApiBundle\Application\Command;

use Symfony\Component\Serializer\Attribute\Groups;

class BarCommand
{
    public function __construct(
        #[Groups('sylius:shop:bar:create')]
        public readonly string $email,
        #[Groups('sylius:shop:bar:create')]
        public readonly string $password,
    ) {
    }
}
