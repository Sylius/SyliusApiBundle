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

namespace Sylius\Bundle\ApiBundle\Provider;

/** @experimental */
final readonly class PathPrefixProvider implements PathPrefixProviderInterface
{
    public function __construct(private string $apiRoute)
    {
    }

    public function getPathPrefix(string $path): ?string
    {
        if (!str_contains($path, $this->apiRoute)) {
            return null;
        }

        /** @var array<int, string> $pathElements */
        $pathElements = array_values(array_filter(explode('/', str_replace($this->apiRoute, '', $path))));

        if ($pathElements[0] === PathPrefixes::SHOP_PREFIX) {
            return PathPrefixes::SHOP_PREFIX;
        }

        if ($pathElements[0] === PathPrefixes::ADMIN_PREFIX) {
            return PathPrefixes::ADMIN_PREFIX;
        }

        return null;
    }
}
