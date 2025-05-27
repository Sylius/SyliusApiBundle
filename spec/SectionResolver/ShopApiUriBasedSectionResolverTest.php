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

namespace Tests\Sylius\Bundle\ApiBundle\SectionResolver;

use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiOrdersSubSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiUriBasedSectionResolver;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionCannotBeResolvedException;
use Sylius\Bundle\CoreBundle\SectionResolver\UriBasedSectionResolverInterface;

final class ShopApiUriBasedSectionResolverTest extends TestCase
{
    private ShopApiUriBasedSectionResolver $shopApiUriBasedSectionResolver;

    protected function setUp(): void
    {
        $this->shopApiUriBasedSectionResolver = new ShopApiUriBasedSectionResolver('/api/v2/shop', 'orders');
    }

    public function testUriBasedSectionResolver(): void
    {
        $this->assertInstanceOf(UriBasedSectionResolverInterface::class, $this->shopApiUriBasedSectionResolver);
    }

    public function testReturnsShopApiSectionIfPathStartsWithApiV2Shop(): void
    {
        $this->shopApiUriBasedSectionResolver->expects($this->once())->method('getSection')->with('/api/v2/shop/something')->shouldBeLike(new ShopApiSection());
        $this->shopApiUriBasedSectionResolver->expects($this->once())->method('getSection')->with('/api/v2/shop')->shouldBeLike(new ShopApiSection());
    }

    public function testReturnsShopApiOrdersSubsectionIfPathContainsOrders(): void
    {
        $this->shopApiUriBasedSectionResolver->expects($this->once())->method('getSection')->with('/api/v2/shop/orders')->shouldBeLike(new ShopApiOrdersSubSection());
    }

    public function testThrowsAnExceptionIfPathDoesNotStartWithApiV2Shop(): void
    {
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->shopApiUriBasedSectionResolver->getSection('/api/v2');
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->shopApiUriBasedSectionResolver->getSection('/api/v2');
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->shopApiUriBasedSectionResolver->getSection('/api/v2');
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->shopApiUriBasedSectionResolver->getSection('/api/v2');
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->shopApiUriBasedSectionResolver->getSection('/api/v2');
    }
}
