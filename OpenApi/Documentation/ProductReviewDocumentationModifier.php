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

namespace Sylius\Bundle\ApiBundle\OpenApi\Documentation;

use ApiPlatform\Core\OpenApi\Model\Parameter;
use ApiPlatform\OpenApi\OpenApi;
use Sylius\Component\Review\Model\ReviewInterface;

/** @experimental */
final class ProductReviewDocumentationModifier implements DocumentationModifierInterface
{
    public const PATH = '%s/admin/product-reviews';

    public function __construct(
        private string $apiRoute,
    ) {
    }

    public function modify(OpenApi $docs): OpenApi
    {
        $paths = $docs->getPaths();

        $path = sprintf(self::PATH, $this->apiRoute);
        $pathItem = $paths->getPath($path);
        $operation = $pathItem->getGet();

        $parameters = $operation->getParameters();
        $parameters = array_filter(
            $parameters,
            fn (Parameter $parameter) => $parameter->getName() !== 'status' && $parameter->getName() !== 'status[]',
        );
        $parameters[] = new Parameter(
            name: 'status',
            in: 'query',
            description: 'Status of product reviews you want to get',
            schema: [
                'type' => 'string',
                'enum' => [ReviewInterface::STATUS_NEW, ReviewInterface::STATUS_ACCEPTED, ReviewInterface::STATUS_REJECTED],
                'nullable' => true,
                'default' => null,
            ],
        );
        $parameters = array_values($parameters);

        $operation = $operation->withParameters($parameters);
        $pathItem = $pathItem->withGet($operation);
        $paths->addPath($path, $pathItem);

        return $docs->withPaths($paths);
    }
}
