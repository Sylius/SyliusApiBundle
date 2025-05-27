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

namespace Tests\Sylius\Bundle\ApiBundle\Serializer\Normalizer;

use ApiPlatform\Metadata\IriConverterInterface;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\ApiBundle\Serializer\Normalizer\ProductNormalizer;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ProductNormalizerTest extends TestCase
{
    /** @var ProductVariantResolverInterface|MockObject */
    private MockObject $defaultProductVariantResolverMock;

    /** @var IriConverterInterface|MockObject */
    private MockObject $iriConverterMock;

    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionProviderMock;

    /** @var NormalizerInterface|MockObject */
    private MockObject $normalizerMock;

    private ProductNormalizer $productNormalizer;

    protected function setUp(): void
    {
        $this->defaultProductVariantResolverMock = $this->createMock(ProductVariantResolverInterface::class);
        $this->iriConverterMock = $this->createMock(IriConverterInterface::class);
        $this->sectionProviderMock = $this->createMock(SectionProviderInterface::class);
        $this->normalizerMock = $this->createMock(NormalizerInterface::class);
        $this->productNormalizer = new ProductNormalizer($this->defaultProductVariantResolverMock, $this->iriConverterMock, $this->sectionProviderMock, ['sylius:product:index']);
        $this->setNormalizer($this->normalizerMock);
    }

    public function testSupportsOnlyProductInterfaceAndShopApiSection(): void
    {
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->assertFalse($this->productNormalizer->supportsNormalization($orderMock, null, ['groups' => ['sylius:product:index']]));
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertTrue($this->productNormalizer->supportsNormalization($productMock, null, ['groups' => ['sylius:product:index']]));
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertFalse($this->productNormalizer->supportsNormalization($productMock, null, ['groups' => ['sylius:product:show']]));
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $this->assertFalse($this->productNormalizer->supportsNormalization($productMock, null, ['groups' => ['sylius:product:index']]));
    }

    public function testDoesNotSupportIfTheNormalizerHasBeenAlreadyCalled(): void
    {
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        $this->assertFalse($this->productNormalizer
            ->supportsNormalization($productMock, null, [
                'sylius_product_normalizer_already_called' => true,
                'groups' => ['sylius:product:index'],
            ]))
        ;
    }

    public function testAddsDefaultVariantIriToSerializedProduct(): void
    {
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->normalizerMock->expects($this->once())->method('normalize')->with($productMock, null, [
            'sylius_product_normalizer_already_called' => true,
            'groups' => ['sylius:product:index'],
        ])->willReturn([]);
        $productMock->expects($this->once())->method('getEnabledVariants')->willReturn(new ArrayCollection([$variantMock]));
        $this->defaultProductVariantResolverMock->expects($this->once())->method('getVariant')->with($productMock)->willReturn($variantMock);
        $this->iriConverterMock->expects($this->once())->method('getIriFromResource')->with($variantMock)->willReturn('/api/v2/shop/product-variants/CODE');
        $this->assertSame([
            'variants' => ['/api/v2/shop/product-variants/CODE'],
            'defaultVariant' => '/api/v2/shop/product-variants/CODE',
        ], $this->productNormalizer->normalize($productMock, null, ['groups' => ['sylius:product:index']]));
    }

    public function testAddsDefaultVariantFieldWithNullValueToSerializedProductIfThereIsNoDefaultVariant(): void
    {
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->normalizerMock->expects($this->once())->method('normalize')->with($productMock, null, [
            'sylius_product_normalizer_already_called' => true,
            'groups' => ['sylius:product:index'],
        ])->willReturn([]);
        $this->iriConverterMock->expects($this->once())->method('getIriFromResource')->with($variantMock)->willReturn('/api/v2/shop/product-variants/CODE');
        $productMock->expects($this->once())->method('getEnabledVariants')->willReturn(new ArrayCollection([$variantMock]));
        $this->defaultProductVariantResolverMock->expects($this->once())->method('getVariant')->with($productMock)->willReturn(null);
        $this->assertSame([
            'variants' => ['/api/v2/shop/product-variants/CODE'],
            'defaultVariant' => null,
        ], $this->productNormalizer->normalize($productMock, null, ['groups' => ['sylius:product:index']]));
    }

    public function testThrowsAnExceptionIfTheNormalizerHasBeenAlreadyCalled(): void
    {
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        $this->normalizerMock->expects($this->never())->method('normalize')->with($productMock, null, [
            'sylius_product_normalizer_already_called' => true,
            'groups' => ['sylius:product:index'],
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->productNormalizer->normalize($productMock, null, [
            'sylius_product_normalizer_already_called' => true,
            'groups' => ['sylius:product:index'],
        ]);
    }

    public function testThrowsAnExceptionIfSerializationGroupIsNotSupported(): void
    {
        /** @var ShopApiSection|MockObject $shopApiSectionMock */
        $shopApiSectionMock = $this->createMock(ShopApiSection::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($shopApiSectionMock);
        $this->normalizerMock->expects($this->never())->method('normalize')->with($productMock, null, [
            'groups' => ['sylius:product:show'],
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->productNormalizer->normalize($productMock, null, [
            'groups' => ['sylius:product:show'],
        ]);
    }
}
