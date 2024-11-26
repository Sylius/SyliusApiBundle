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

namespace Sylius\Bundle\ApiBundle\Serializer\ContextBuilder;

use ApiPlatform\State\SerializerContextBuilderInterface;
use Sylius\Bundle\ApiBundle\Attribute\PaymentRequestActionAware;
use Sylius\Bundle\ApiBundle\Serializer\ContextKeys;
use Sylius\Bundle\PaymentBundle\Provider\DefaultActionProviderInterface;
use Symfony\Component\HttpFoundation\Request;

final class PaymentRequestActionAwareContextBuilder extends AbstractInputContextBuilder
{
    public function __construct(
        SerializerContextBuilderInterface $decoratedContextBuilder,
        string $attributeClass,
        string $defaultConstructorArgumentName,
        private readonly DefaultActionProviderInterface $defaultActionProvider,
    ) {
        parent::__construct($decoratedContextBuilder, $attributeClass, $defaultConstructorArgumentName);
    }

    protected function supports(Request $request, array $context, ?array $extractedAttributes): bool
    {
        $data = $request->toArray();

        return null === ($data[PaymentRequestActionAware::DEFAULT_ARGUMENT_NAME] ?? null) && isset($context[ContextKeys::PAYMENT_METHOD_CODE]);
    }

    protected function resolveValue(array $context, ?array $extractedAttributes): mixed
    {
        return $this->defaultActionProvider->getActionFromPaymentMethodCode($context[ContextKeys::PAYMENT_METHOD_CODE]);
    }
}
