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
use Sylius\Bundle\ApiBundle\Attribute\ChannelCodeAware;
use Sylius\Bundle\ApiBundle\Command\SendContactRequest;
use Sylius\Bundle\ApiBundle\Serializer\ContextBuilder\ChannelCodeAwareContextBuilder;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class ChannelCodeAwareContextBuilderTest extends TestCase
{
    private MockObject&SerializerContextBuilderInterface $decoratedContextBuilder;

    private ChannelContextInterface&MockObject $channelContext;

    private ChannelCodeAwareContextBuilder $channelCodeAwareContextBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->decoratedContextBuilder = $this->createMock(SerializerContextBuilderInterface::class);
        $this->channelContext = $this->createMock(ChannelContextInterface::class);
        $this->channelCodeAwareContextBuilder = new ChannelCodeAwareContextBuilder($this->decoratedContextBuilder, ChannelCodeAware::class, 'channelCode', $this->channelContext);
    }

    public function testSetsChannelCodeAsAConstructorArgument(): void
    {
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $this->decoratedContextBuilder->expects($this->once())->method('createFromRequest')->with($requestMock, true, [])
            ->willReturn(['input' => ['class' => SendContactRequest::class]])
        ;
        $this->channelContext->expects($this->once())->method('getChannel')->willReturn($channelMock);
        $channelMock->expects($this->once())->method('getCode')->willReturn('CODE');
        $this->assertSame([
            'input' => ['class' => SendContactRequest::class],
            AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                SendContactRequest::class => ['channelCode' => 'CODE'],
            ],
        ], $this->channelCodeAwareContextBuilder
            ->createFromRequest($requestMock, true, []))
        ;
    }

    public function testDoesNothingIfThereIsNoInputClass(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $this->decoratedContextBuilder->expects($this->once())->method('createFromRequest')->with($requestMock, true, [])
            ->willReturn([])
        ;
        $this->channelContext->expects($this->never())->method('getChannel');
        $this->assertSame([], $this->channelCodeAwareContextBuilder->createFromRequest($requestMock, true, []));
    }

    public function testDoesNothingIfInputClassIsNoChannelAware(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $this->decoratedContextBuilder->expects($this->once())->method('createFromRequest')->with($requestMock, true, [])
            ->willReturn(['input' => ['class' => \stdClass::class]])
        ;
        $this->channelContext->expects($this->never())->method('getChannel');
        $this->assertSame(['input' => ['class' => \stdClass::class]], $this->channelCodeAwareContextBuilder
            ->createFromRequest($requestMock, true, []))
        ;
    }
}
