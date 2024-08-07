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

namespace Sylius\Bundle\ApiBundle\DataTransformer;

use Sylius\Bundle\ApiBundle\Command\PaymentRequestHashAwareInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;

/** @experimental */
final class PaymentRequestHashAwareInputCommandDataTransformer implements CommandDataTransformerInterface
{
    /**
     * @param PaymentRequestHashAwareInterface $object
     * @param array<string, mixed> $context
     *
     * @return PaymentRequestHashAwareInterface
     */
    public function transform($object, string $to, array $context = [])
    {
        /** @var PaymentRequestInterface $paymentRequest */
        $paymentRequest = $context['object_to_populate'];

        $hash = $paymentRequest->getHash();
        if (null === $hash) {
            return $object;
        }

        $object->setHash($hash->toBinary());

        return $object;
    }

    /** @param mixed $object */
    public function supportsTransformation($object): bool
    {
        return $object instanceof PaymentRequestHashAwareInterface;
    }
}
