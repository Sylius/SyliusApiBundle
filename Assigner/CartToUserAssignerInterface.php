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

namespace Sylius\Bundle\ApiBundle\Assigner;

use Sylius\Component\Customer\Model\CustomerInterface;

interface CartToUserAssignerInterface
{
    public function assignByCustomer(CustomerInterface $customer): void;
}
