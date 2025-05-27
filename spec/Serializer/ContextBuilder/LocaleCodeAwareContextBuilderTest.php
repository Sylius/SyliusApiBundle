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
use stdClass;
use Sylius\Bundle\ApiBundle\Attribute\LocaleCodeAware;
use Sylius\Bundle\ApiBundle\Command\SendContactRequest;
use Sylius\Bundle\ApiBundle\Serializer\ContextBuilder\LocaleCodeAwareContextBuilder;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class LocaleCodeAwareContextBuilderTest extends TestCase
{
    /** @var SerializerContextBuilderInterface|MockObject */
    private MockObject $decoratedContextBuilderMock;

    /** @var LocaleContextInterface|MockObject */
    private MockObject $localeContextMock;

    private LocaleCodeAwareContextBuilder $localeCodeAwareContextBuilder;

    protected function setUp(): void
    {
        $this->decoratedContextBuilderMock = $this->createMock(SerializerContextBuilderInterface::class);
        $this->localeContextMock = $this->createMock(LocaleContextInterface::class);
        $this->localeCodeAwareContextBuilder = new LocaleCodeAwareContextBuilder($this->decoratedContextBuilderMock, LocaleCodeAware::class, 'localeCode', $this->localeContextMock);
    }

    public function testSetsLocaleCodeAsAConstructorArgument(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, [])
            ->willReturn(['input' => ['class' => SendContactRequest::class]])
        ;
        $this->localeContextMock->expects($this->once())->method('getLocaleCode')->willReturn('en_US');
        $this->assertSame([
            'input' => ['class' => SendContactRequest::class],
            AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                SendContactRequest::class => ['localeCode' => 'en_US'],
            ],
        ], $this->localeCodeAwareContextBuilder
            ->createFromRequest($requestMock, true, []))
        ;
    }

    public function testDoesNothingIfThereIsNoInputClass(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, [])
            ->willReturn([])
        ;
        $this->localeContextMock->expects($this->never())->method('getLocaleCode');
        $this->assertSame([], $this->localeCodeAwareContextBuilder->createFromRequest($requestMock, true, []));
    }

    public function testDoesNothingIfInputClassIsNoChannelAware(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, [])
            ->willReturn(['input' => ['class' => stdClass::class]])
        ;
        $this->localeContextMock->expects($this->never())->method('getLocaleCode');
        $this->assertSame(['input' => ['class' => stdClass::class]], $this->localeCodeAwareContextBuilder
            ->createFromRequest($requestMock, true, []))
        ;
    }
}
