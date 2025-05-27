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

namespace Tests\Sylius\Bundle\ApiBundle\Creator;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Sylius\Bundle\ApiBundle\Creator\TaxonImageCreator;
use Sylius\Bundle\ApiBundle\Exception\NoFileUploadedException;
use Sylius\Bundle\ApiBundle\Exception\TaxonNotFoundException;
use Sylius\Component\Core\Model\TaxonImageInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;

final class TaxonImageCreatorTest extends TestCase
{
    /** @var FactoryInterface|MockObject */
    private MockObject $taxonImageFactoryMock;

    /** @var TaxonRepositoryInterface|MockObject */
    private MockObject $taxonRepositoryMock;

    /** @var ImageUploaderInterface|MockObject */
    private MockObject $imageUploaderMock;

    private TaxonImageCreator $taxonImageCreator;

    protected function setUp(): void
    {
        $this->taxonImageFactoryMock = $this->createMock(FactoryInterface::class);
        $this->taxonRepositoryMock = $this->createMock(TaxonRepositoryInterface::class);
        $this->imageUploaderMock = $this->createMock(ImageUploaderInterface::class);
        $this->taxonImageCreator = new TaxonImageCreator($this->taxonImageFactoryMock, $this->taxonRepositoryMock, $this->imageUploaderMock);
    }

    public function testCreatesTaxonImage(): void
    {
        /** @var TaxonInterface|MockObject $taxonMock */
        $taxonMock = $this->createMock(TaxonInterface::class);
        /** @var TaxonImageInterface|MockObject $taxonImageMock */
        $taxonImageMock = $this->createMock(TaxonImageInterface::class);
        $file = new SplFileInfo(__FILE__);
        $this->taxonRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'CODE'])->willReturn($taxonMock);
        $this->taxonImageFactoryMock->expects($this->once())->method('createNew')->willReturn($taxonImageMock);
        $taxonImageMock->expects($this->once())->method('setFile')->with($file);
        $taxonImageMock->expects($this->once())->method('setType')->with('banner');
        $taxonMock->expects($this->once())->method('addImage')->with($taxonImageMock);
        $this->imageUploaderMock->expects($this->once())->method('upload')->with($taxonImageMock);
        $this->assertSame($taxonImageMock, $this->taxonImageCreator->create('CODE', $file, 'banner'));
    }

    public function testThrowsAnExceptionIfTaxonIsNotFound(): void
    {
        $file = new SplFileInfo(__FILE__);
        $this->taxonRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'CODE'])->willReturn(null);
        $this->taxonImageFactoryMock->expects($this->never())->method('createNew');
        $this->imageUploaderMock->expects($this->never())->method('upload');
        $this->expectException(TaxonNotFoundException::class);
        $this->taxonImageCreator->create('CODE', $file, 'banner');
    }

    public function testThrowsAnExceptionIfThereIsNoUploadedFile(): void
    {
        $this->taxonRepositoryMock->expects($this->never())->method('findOneBy')->with(['code' => 'CODE']);
        $this->taxonImageFactoryMock->expects($this->never())->method('createNew');
        $this->imageUploaderMock->expects($this->never())->method('upload');
        $this->expectException(NoFileUploadedException::class);
        $this->taxonImageCreator->create('CODE', null, 'banner');
    }
}
