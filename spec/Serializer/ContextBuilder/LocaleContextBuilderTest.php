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

namespace Tests\Sylius\Bundle\ApiBundle\Serializer\ContextBuilder;

use ApiPlatform\State\SerializerContextBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Serializer\ContextBuilder\LocaleContextBuilder;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\HttpFoundation\Request;

final class LocaleContextBuilderTest extends TestCase
{
    /** @var SerializerContextBuilderInterface|MockObject */
    private MockObject $decoratedSerializerContextBuilderMock;

    /** @var LocaleContextInterface|MockObject */
    private MockObject $localeContextMock;

    private LocaleContextBuilder $localeContextBuilder;

    protected function setUp(): void
    {
        $this->decoratedSerializerContextBuilderMock = $this->createMock(SerializerContextBuilderInterface::class);
        $this->localeContextMock = $this->createMock(LocaleContextInterface::class);
        $this->localeContextBuilder = new LocaleContextBuilder($this->decoratedSerializerContextBuilderMock, $this->localeContextMock);
    }

    public function testUpdatesAnContextWhenLocaleContextHasLocale(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $this->decoratedSerializerContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, []);
        $this->localeContextMock->expects($this->once())->method('getLocaleCode')->willReturn('en_US');
        $this->localeContextBuilder->createFromRequest($requestMock, true, []);
    }
}
