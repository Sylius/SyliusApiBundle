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

namespace Sylius\Bundle\ApiBundle\Validator\Constraints;

use Sylius\Bundle\ApiBundle\Command\Account\ChangeShopUserPassword;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class CorrectChangeShopUserConfirmPasswordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($value, ChangeShopUserPassword::class);

        /** @var CorrectChangeShopUserConfirmPassword $constraint */
        Assert::isInstanceOf($constraint, CorrectChangeShopUserConfirmPassword::class);

        if ($value->getConfirmNewPassword() !== $value->getNewPassword()) {
            $this->context->buildViolation($constraint->message)
                ->atPath('newPassword')
                ->addViolation()
            ;
        }
    }
}
