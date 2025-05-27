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
use Sylius\Bundle\ApiBundle\Attribute\ChannelCodeAware;
use Sylius\Bundle\ApiBundle\Command\SendContactRequest;
use Sylius\Bundle\ApiBundle\Serializer\ContextBuilder\ChannelCodeAwareContextBuilder;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class ChannelCodeAwareContextBuilderTest extends TestCase
{
    /** @var SerializerContextBuilderInterface|MockObject */
    private MockObject $decoratedContextBuilderMock;

    /** @var ChannelContextInterface|MockObject */
    private MockObject $channelContextMock;

    private ChannelCodeAwareContextBuilder $channelCodeAwareContextBuilder;

    protected function setUp(): void
    {
        $this->decoratedContextBuilderMock = $this->createMock(SerializerContextBuilderInterface::class);
        $this->channelContextMock = $this->createMock(ChannelContextInterface::class);
        $this->channelCodeAwareContextBuilder = new ChannelCodeAwareContextBuilder($this->decoratedContextBuilderMock, ChannelCodeAware::class, 'channelCode', $this->channelContextMock);
    }

    public function testSetsChannelCodeAsAConstructorArgument(): void
    {
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, [])
            ->willReturn(['input' => ['class' => SendContactRequest::class]])
        ;
        $this->channelContextMock->expects($this->once())->method('getChannel')->willReturn($channelMock);
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
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, [])
            ->willReturn([])
        ;
        $this->channelContextMock->expects($this->never())->method('getChannel');
        $this->assertSame([], $this->channelCodeAwareContextBuilder->createFromRequest($requestMock, true, []));
    }

    public function testDoesNothingIfInputClassIsNoChannelAware(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, [])
            ->willReturn(['input' => ['class' => stdClass::class]])
        ;
        $this->channelContextMock->expects($this->never())->method('getChannel');
        $this->assertSame(['input' => ['class' => stdClass::class]], $this->channelCodeAwareContextBuilder
            ->createFromRequest($requestMock, true, []))
        ;
    }
}
