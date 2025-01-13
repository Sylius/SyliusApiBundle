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

namespace Sylius\Bundle\ApiBundle\ApiPlatform\Metadata\Resource\Factory;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;

final class DuplicateOperationReplacerResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $decorated,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $resourceMetadataCollection = $this->decorated->create($resourceClass);

        /** @var array<string, bool> $duplicatedOperationNames */
        $duplicatedOperationNames = [];

        foreach ($resourceMetadataCollection as $key => $resourceMetadata) {
            if ($resourceMetadata->getOperations()) {
                $resourceMetadata = $resourceMetadata->withOperations(
                    $this->getTransformedOperations(
                        $resourceMetadata->getOperations(),
                        $resourceMetadataCollection,
                        $duplicatedOperationNames,
                    ),
                );
            }

            if ($resourceMetadata->getGraphQlOperations()) {
                $resourceMetadata = $resourceMetadata->withGraphQlOperations(
                    $this->getTransformedOperations(
                        $resourceMetadata->getGraphQlOperations(),
                        $resourceMetadataCollection,
                        $duplicatedOperationNames,
                    ),
                );
            }

            $resourceMetadataCollection[$key] = $resourceMetadata;
        }

        return $resourceMetadataCollection;
    }

    /**
     * @param Operations<Operation>|Operation[] $operations
     * @param array<string, bool> $duplicatedOperationNames
     *
     * @return Operations<Operation>|Operation[]
     */
    private function getTransformedOperations(
        array|Operations $operations,
        ResourceMetadataCollection $resourceMetadataCollection,
        array &$duplicatedOperationNames,
    ): array|Operations {
        foreach ($operations as $name => $operation) {
            if (isset($duplicatedOperationNames[$name])) {
                continue;
            }

            $duplicatedOperationNames[$name] = true;

            foreach ($this->findOperations($name, $resourceMetadataCollection) as $duplicatedOperation) {
                if ($operations instanceof Operations) {
                    $operations->add($name, $duplicatedOperation);

                    continue;
                }

                $operations[$name] = $duplicatedOperation;
            }
        }

        return $operations;
    }

    /**
     * @return iterable<Operation>
     */
    private function findOperations(string $key, ResourceMetadataCollection $resourceMetadataCollection): iterable
    {
        foreach ($resourceMetadataCollection as $resourceMetadata) {
            foreach ($resourceMetadata->getOperations() as $name => $operation) {
                if ($name === $key) {
                    yield $operation;
                }
            }
        }
    }
}
