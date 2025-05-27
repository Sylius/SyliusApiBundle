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

use ApiPlatform\Metadata\IriConverterInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Sylius\Bundle\ApiBundle\Creator\ProductImageCreator;
use Sylius\Bundle\ApiBundle\Exception\NoFileUploadedException;
use Sylius\Bundle\ApiBundle\Exception\ProductNotFoundException;
use Sylius\Component\Core\Model\ProductImageInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Resource\Factory\FactoryInterface;

final class ProductImageCreatorTest extends TestCase
{
    /** @var FactoryInterface|MockObject */
    private MockObject $productImageFactoryMock;

    /** @var ProductRepositoryInterface|MockObject */
    private MockObject $productRepositoryMock;

    /** @var ImageUploaderInterface|MockObject */
    private MockObject $imageUploaderMock;

    /** @var IriConverterInterface|MockObject */
    private MockObject $iriConverterMock;

    private ProductImageCreator $productImageCreator;

    protected function setUp(): void
    {
        $this->productImageFactoryMock = $this->createMock(FactoryInterface::class);
        $this->productRepositoryMock = $this->createMock(ProductRepositoryInterface::class);
        $this->imageUploaderMock = $this->createMock(ImageUploaderInterface::class);
        $this->iriConverterMock = $this->createMock(IriConverterInterface::class);
        $this->productImageCreator = new ProductImageCreator($this->productImageFactoryMock, $this->productRepositoryMock, $this->imageUploaderMock, $this->iriConverterMock);
    }

    public function testCreatesAProductImage(): void
    {
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        /** @var ProductImageInterface|MockObject $productImageMock */
        $productImageMock = $this->createMock(ProductImageInterface::class);
        /** @var ProductVariantInterface|MockObject $productVariantMock */
        $productVariantMock = $this->createMock(ProductVariantInterface::class);
        $file = new SplFileInfo(__FILE__);
        $this->productRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'CODE'])->willReturn($productMock);
        $this->iriConverterMock->expects($this->once())->method('getResourceFromIri')->with('/api/v2/product-variants/CODE')->willReturn($productVariantMock);
        $this->productImageFactoryMock->expects($this->once())->method('createNew')->willReturn($productImageMock);
        $productImageMock->expects($this->once())->method('setFile')->with($file);
        $productImageMock->expects($this->once())->method('setType')->with('banner');
        $productImageMock->expects($this->once())->method('addProductVariant')->with($productVariantMock);
        $productMock->expects($this->once())->method('addImage')->with($productImageMock);
        $this->imageUploaderMock->expects($this->once())->method('upload')->with($productImageMock);
        $this->assertSame($productImageMock, $this->productImageCreator
            ->create(
                'CODE',
                $file,
                'banner',
                ['productVariants' => ['/api/v2/product-variants/CODE']],
            ))
        ;
    }

    public function testThrowsAnExceptionIfProductIsNotFound(): void
    {
        $file = new SplFileInfo(__FILE__);
        $this->productRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'CODE'])->willReturn(null);
        $this->productImageFactoryMock->expects($this->never())->method('createNew');
        $this->iriConverterMock->expects($this->never())->method('getResourceFromIri');
        $this->imageUploaderMock->expects($this->never())->method('upload');
        $this->expectException(ProductNotFoundException::class);
        $this->productImageCreator->create('CODE', $file, 'banner', []);
    }

    public function testThrowsAnExceptionIfThereIsNoUploadedFile(): void
    {
        $this->productRepositoryMock->expects($this->never())->method('findOneBy')->with(['code' => 'CODE']);
        $this->productImageFactoryMock->expects($this->never())->method('createNew');
        $this->iriConverterMock->expects($this->never())->method('getResourceFromIri');
        $this->imageUploaderMock->expects($this->never())->method('upload');
        $this->expectException(NoFileUploadedException::class);
        $this->productImageCreator->create('CODE', null, 'banner', []);
    }

    public function testThrowsAnExceptionIfThereIsAnIriToDifferentResourceThanProductVariant(): void
    {
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        /** @var ProductImageInterface|MockObject $productImageMock */
        $productImageMock = $this->createMock(ProductImageInterface::class);
        $file = new SplFileInfo(__FILE__);
        $this->productRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'CODE'])->willReturn($productMock);
        $this->iriConverterMock->expects($this->once())->method('getResourceFromIri')->with('/api/v2/products/CODE')->willReturn($productMock);
        $this->productImageFactoryMock->expects($this->once())->method('createNew')->willReturn($productImageMock);
        $productImageMock->expects($this->once())->method('setFile')->with($file);
        $productImageMock->expects($this->once())->method('setType')->with('banner');
        $productImageMock->expects($this->never())->method('addProductVariant');
        $this->imageUploaderMock->expects($this->never())->method('upload');
        $this->expectException(InvalidArgumentException::class);
        $this->productImageCreator->create('CODE', $file, 'banner', ['productVariants' => ['/api/v2/products/CODE']]);
    }
}
