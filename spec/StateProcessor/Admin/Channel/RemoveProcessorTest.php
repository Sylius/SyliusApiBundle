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

namespace Tests\Sylius\Bundle\ApiBundle\StateProcessor\Admin\Channel;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\StateProcessor\Admin\Channel\RemoveProcessor;
use Sylius\Component\Channel\Checker\ChannelDeletionCheckerInterface;
use Sylius\Component\Core\Exception\ResourceDeleteException;
use Sylius\Component\Core\Model\ChannelInterface;

final class RemoveProcessorTest extends TestCase
{
    /** @var ProcessorInterface|MockObject */
    private MockObject $removeProcessorMock;

    /** @var ChannelDeletionCheckerInterface|MockObject */
    private MockObject $channelDeletionCheckerMock;

    private RemoveProcessor $removeProcessor;

    protected function setUp(): void
    {
        $this->removeProcessorMock = $this->createMock(ProcessorInterface::class);
        $this->channelDeletionCheckerMock = $this->createMock(ChannelDeletionCheckerInterface::class);
        $this->removeProcessor = new RemoveProcessor($this->removeProcessorMock, $this->channelDeletionCheckerMock);
    }

    public function testThrowsAnExceptionIfObjectIsNotAChannel(): void
    {
        $this->channelDeletionCheckerMock->expects(self::never())->method('isDeletable');
        $this->removeProcessorMock->expects(self::never())->method('process')->with($this->any());
        $this->expectException(InvalidArgumentException::class);
        $this->removeProcessor->process(new stdClass(), new Delete(), [], []);
    }

    public function testThrowsExceptionIfChannelIsNotDeletable(): void
    {
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $uriVariables = [];
        $context = [];
        $this->channelDeletionCheckerMock->expects(self::once())->method('isDeletable')->with($channelMock)->willReturn(false);
        $this->removeProcessorMock->expects(self::never())->method('process')->with($this->any());
        $this->expectException(ResourceDeleteException::class);
        $this->removeProcessor->process($channelMock, new Delete(), $uriVariables, $context);
    }

    public function testUsesDecoratedDataPersisterToRemoveChannel(): void
    {
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $operation = new Delete();
        $uriVariables = [];
        $context = [];
        $this->channelDeletionCheckerMock->expects(self::once())->method('isDeletable')->with($channelMock)->willReturn(true);
        $this->removeProcessorMock->expects(self::once())->method('process')->with($channelMock, $operation, $uriVariables, $context)->willReturn($channelMock);
        $this->removeProcessor->process($channelMock, $operation, $uriVariables, $context);
    }
}
