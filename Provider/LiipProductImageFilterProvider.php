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

namespace Sylius\Bundle\ApiBundle\Provider;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/** @experimental */
class LiipProductImageFilterProvider implements ProductImageFilterProviderInterface
{
    use ContainerAwareTrait;

    public function provideAllFilters(): array
    {
        return $this->container->getParameter('liip_imagine.filter_sets');
    }

    public function provideShopFilters(): array
    {
        $filters = $this->container->getParameter('liip_imagine.filter_sets');

        return $this->removeAdminFilters($filters);
    }

    private function removeAdminFilters(array $filters): array {
        /** @var string $filter */
        foreach ($filters as $key => $filter) {
            if (str_contains($key, 'admin')) {
                unset($filters[$key]);
            }
        }

        return $filters;
    }
}
