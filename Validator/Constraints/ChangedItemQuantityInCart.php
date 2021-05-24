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

namespace Sylius\Bundle\ApiBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

final class ChangedItemQuantityInCart extends Constraint
{
    /** @var string */
    public $productNotExistMessage = 'sylius.product.not_exist';

    /** @var string */
    public $productVariantNotLongerAvailable = 'sylius.product_variant.not_longer_available';

    /** @var string */
    public $productVariantNotSufficient = 'sylius.product_variant.not_sufficient';

    public function validatedBy(): string
    {
        return 'sylius_api_validator_changed_item_guantity_in_cart';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
