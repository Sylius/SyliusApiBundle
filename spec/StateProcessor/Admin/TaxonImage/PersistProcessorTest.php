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

namespace Tests\Sylius\Bundle\ApiBundle\StateProcessor\Admin\TaxonImage;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Sylius\Bundle\ApiBundle\Creator\ImageCreatorInterface;
use Sylius\Bundle\ApiBundle\StateProcessor\Admin\TaxonImage\PersistProcessor;
use Sylius\Component\Core\Model\TaxonImageInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

final class PersistProcessorTest extends TestCase
{
    /** @var ProcessorInterface|MockObject */
    private MockObject $processorMock;

    /** @var ImageCreatorInterface|MockObject */
    private MockObject $taxonImageCreatorMock;

    private PersistProcessor $persistProcessor;

    protected function setUp(): void
    {
        $this->processorMock = $this->createMock(ProcessorInterface::class);
        $this->taxonImageCreatorMock = $this->createMock(ImageCreatorInterface::class);
        $this->persistProcessor = new PersistProcessor($this->processorMock, $this->taxonImageCreatorMock);
    }

    public function testImplementsProcessorInterface(): void
    {
        $this->assertInstanceOf(ProcessorInterface::class, $this->persistProcessor);
    }

    public function testCreatesAndProcessesATaxonImage(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var ParameterBag|MockObject $attributesMock */
        $attributesMock = $this->createMock(ParameterBag::class);
        /** @var FileBag|MockObject $filesMock */
        $filesMock = $this->createMock(FileBag::class);
        /** @var TaxonImageInterface|MockObject $taxonImageMock */
        $taxonImageMock = $this->createMock(TaxonImageInterface::class);
        $operation = new Post();
        $attributesMock->expects(self::once())->method('get')->with('code', '')->willReturn('code');
        $requestMock->attributes = $attributesMock;
        $file = new SplFileInfo(__FILE__);
        $filesMock->expects(self::once())->method('get')->with('file')->willReturn($file);
        $requestMock->files = $filesMock;
        $requestMock->request = new InputBag(['type' => 'type']);
        $this->taxonImageCreatorMock->expects(self::once())->method('create')->with('code', $file, 'type')->willReturn($taxonImageMock);
        $this->processorMock->expects(self::once())->method('process')->with($taxonImageMock, $operation, [], ['request' => $requestMock])
        ;
        $this->persistProcessor->process(null, $operation, [], ['request' => $requestMock]);
    }

    public function testThrowsAnExceptionForDeleteOperation(): void
    {
        /** @var DeleteOperationInterface|MockObject $deleteOperationMock */
        $deleteOperationMock = $this->createMock(DeleteOperationInterface::class);
        /** @var TaxonImageInterface|MockObject $taxonImageMock */
        $taxonImageMock = $this->createMock(TaxonImageInterface::class);
        $deleteOperationMock->beADoubleOf(Operation::class);
        $this->expectException(InvalidArgumentException::class);
        $this->persistProcessor->process($taxonImageMock, $deleteOperationMock, [], []);
    }
}
