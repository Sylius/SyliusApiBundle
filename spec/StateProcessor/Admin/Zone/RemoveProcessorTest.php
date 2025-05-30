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

namespace Tests\Sylius\Bundle\ApiBundle\StateProcessor\Admin\Zone;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\StateProcessor\Admin\Zone\RemoveProcessor;
use Sylius\Component\Addressing\Checker\ZoneDeletionCheckerInterface;
use Sylius\Component\Addressing\Model\ZoneInterface;
use Sylius\Component\Core\Exception\ResourceDeleteException;

final class RemoveProcessorTest extends TestCase
{
    /** @var ProcessorInterface|MockObject */
    private MockObject $removeProcessorMock;

    /** @var ZoneDeletionCheckerInterface|MockObject */
    private MockObject $zoneDeletionCheckerMock;

    private RemoveProcessor $removeProcessor;

    protected function setUp(): void
    {
        $this->removeProcessorMock = $this->createMock(ProcessorInterface::class);
        $this->zoneDeletionCheckerMock = $this->createMock(ZoneDeletionCheckerInterface::class);
        $this->removeProcessor = new RemoveProcessor($this->removeProcessorMock, $this->zoneDeletionCheckerMock);
    }

    public function testThrowsAnExceptionIfObjectIsNotAZone(): void
    {
        /** @var Operation|MockObject $operationMock */
        $operationMock = $this->createMock(Operation::class);
        $operationMock->implement(DeleteOperationInterface::class);
        $this->zoneDeletionCheckerMock->expects(self::never())->method('isDeletable');
        $this->removeProcessorMock->expects(self::never())->method('process')->with($this->any());
        $this->expectException(InvalidArgumentException::class);
        $this->removeProcessor->process(new stdClass(), $operationMock, [], []);
    }

    public function testThrowsExceptionIfZoneIsNotDeletable(): void
    {
        /** @var Operation|MockObject $operationMock */
        $operationMock = $this->createMock(Operation::class);
        /** @var ZoneInterface|MockObject $zoneMock */
        $zoneMock = $this->createMock(ZoneInterface::class);
        $operationMock->implement(DeleteOperationInterface::class);
        $this->zoneDeletionCheckerMock->expects(self::once())->method('isDeletable')->with($zoneMock)->willReturn(false);
        $this->removeProcessorMock->expects(self::never())->method('process')->with($this->any());
        $this->expectException(ResourceDeleteException::class);
        $this->removeProcessor->process($zoneMock, $operationMock, [], []);
    }

    public function testUsesDecoratedDataPersisterToRemoveChannel(): void
    {
        /** @var Operation|MockObject $operationMock */
        $operationMock = $this->createMock(Operation::class);
        /** @var ZoneInterface|MockObject $zoneMock */
        $zoneMock = $this->createMock(ZoneInterface::class);
        $operationMock->implement(DeleteOperationInterface::class);
        $this->zoneDeletionCheckerMock->expects(self::once())->method('isDeletable')->with($zoneMock)->willReturn(true);
        $this->removeProcessorMock->expects(self::once())->method('process')->with($zoneMock, $operationMock, [], [])->willReturn($zoneMock);
        $this->removeProcessor->process($zoneMock, $operationMock);
    }
}
