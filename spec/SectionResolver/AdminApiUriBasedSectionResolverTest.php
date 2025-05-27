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
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiUriBasedSectionResolver;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionCannotBeResolvedException;
use Sylius\Bundle\CoreBundle\SectionResolver\UriBasedSectionResolverInterface;

final class AdminApiUriBasedSectionResolverTest extends TestCase
{
    private AdminApiUriBasedSectionResolver $adminApiUriBasedSectionResolver;

    protected function setUp(): void
    {
        $this->adminApiUriBasedSectionResolver = new AdminApiUriBasedSectionResolver('/api/v2/admin');
    }

    public function testUriBasedSectionResolver(): void
    {
        $this->assertInstanceOf(UriBasedSectionResolverInterface::class, $this->adminApiUriBasedSectionResolver);
    }

    public function testReturnsAdminApiSectionIfPathStartsWithApiV2Admin(): void
    {
        $this->adminApiUriBasedSectionResolver->expects($this->once())->method('getSection')->with('/api/v2/admin/something')->shouldBeLike(new AdminApiSection());
        $this->adminApiUriBasedSectionResolver->expects($this->once())->method('getSection')->with('/api/v2/admin')->shouldBeLike(new AdminApiSection());
    }

    public function testThrowsAnExceptionIfPathDoesNotStartWithApiV2Admin(): void
    {
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->adminApiUriBasedSectionResolver->getSection('/api/v2');
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->adminApiUriBasedSectionResolver->getSection('/api/v2');
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->adminApiUriBasedSectionResolver->getSection('/api/v2');
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->adminApiUriBasedSectionResolver->getSection('/api/v2');
        $this->expectException(SectionCannotBeResolvedException::class);
        $this->adminApiUriBasedSectionResolver->getSection('/api/v2');
    }
}
