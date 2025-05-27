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

namespace Tests\Sylius\Bundle\ApiBundle\ApiPlatform\Routing;

use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\UrlGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\ApiPlatform\Routing\IriConverter;
use Sylius\Bundle\ApiBundle\Provider\PathPrefixProviderInterface;
use Sylius\Bundle\ApiBundle\Resolver\OperationResolverInterface;
use Sylius\Component\Addressing\Model\Country;
use Sylius\Component\Addressing\Model\CountryInterface;
use Symfony\Component\Routing\RouterInterface;

final class IriConverterTest extends TestCase
{
    /** @var IriConverterInterface|MockObject */
    private MockObject $decoratedIriConverterMock;

    /** @var PathPrefixProviderInterface|MockObject */
    private MockObject $pathPrefixProviderMock;

    /** @var OperationResolverInterface|MockObject */
    private MockObject $operationResolverMock;

    /** @var RouterInterface|MockObject */
    private MockObject $routerMock;

    private IriConverter $iriConverter;

    protected function setUp(): void
    {
        $this->decoratedIriConverterMock = $this->createMock(IriConverterInterface::class);
        $this->pathPrefixProviderMock = $this->createMock(PathPrefixProviderInterface::class);
        $this->operationResolverMock = $this->createMock(OperationResolverInterface::class);
        $this->routerMock = $this->createMock(RouterInterface::class);
        $this->iriConverter = new IriConverter($this->decoratedIriConverterMock, $this->pathPrefixProviderMock, $this->operationResolverMock, $this->routerMock);
    }

    public function testImplementsTheIriConverterInterface(): void
    {
        $this->assertInstanceOf(IriConverterInterface::class, $this->iriConverter);
    }

    public function testUsesInnerIriConverterToGetResourceFromIri(): void
    {
        /** @var CountryInterface|MockObject $countryMock */
        $countryMock = $this->createMock(CountryInterface::class);
        $this->decoratedIriConverterMock->expects($this->once())->method('getResourceFromIri')->with('api/v2/admin/countries/CODE', [], null)->willReturn($countryMock);
        $this->assertSame($countryMock, $this->iriConverter->getResourceFromIri('api/v2/admin/countries/CODE'));
    }

    public function testUsesOperationResolverToGetProperIriFromResource(): void
    {
        /** @var CountryInterface|MockObject $countryMock */
        $countryMock = $this->createMock(CountryInterface::class);
        /** @var Operation|MockObject $operationMock */
        $operationMock = $this->createMock(Operation::class);
        $this->pathPrefixProviderMock->expects($this->once())->method('getPathPrefix')->with('api/v2/admin/countries')->willReturn('admin');
        $this->operationResolverMock->expects($this->once())->method('resolve')->with(Country::class, 'admin', null)
            ->willReturn($operationMock)
        ;
        $this->decoratedIriConverterMock->expects($this->once())->method('getIriFromResource')->with($countryMock, UrlGeneratorInterface::ABS_PATH, $operationMock, [
            'request_uri' => 'api/v2/admin/countries',
            'force_resource_class' => Country::class,
        ])
            ->willReturn('api/v2/admin/countries/CODE')
        ;
        $this->assertSame('api/v2/admin/countries/CODE', $this->iriConverter
            ->getIriFromResource(
                $countryMock,
                UrlGeneratorInterface::ABS_PATH,
                null,
                [
                    'request_uri' => 'api/v2/admin/countries',
                    'force_resource_class' => Country::class,
                ],
            ))
        ;
    }
}
