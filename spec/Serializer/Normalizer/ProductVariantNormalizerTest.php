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
use ApiPlatform\Metadata\UrlGeneratorInterface;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\ApiBundle\Serializer\ContextKeys;
use Sylius\Bundle\ApiBundle\Serializer\Normalizer\ProductVariantNormalizer;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Calculator\ProductVariantPricesCalculatorInterface;
use Sylius\Component\Core\Exception\MissingChannelConfigurationException;
use Sylius\Component\Core\Model\CatalogPromotionInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ProductVariantNormalizerTest extends TestCase
{
    /** @var ProductVariantPricesCalculatorInterface|MockObject */
    private MockObject $pricesCalculatorMock;

    /** @var AvailabilityCheckerInterface|MockObject */
    private MockObject $availabilityCheckerMock;

    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionProviderMock;

    /** @var IriConverterInterface|MockObject */
    private MockObject $iriConverterMock;

    private ProductVariantNormalizer $productVariantNormalizer;

    private const ALREADY_CALLED = 'sylius_product_variant_normalizer_already_called';

    protected function setUp(): void
    {
        $this->pricesCalculatorMock = $this->createMock(ProductVariantPricesCalculatorInterface::class);
        $this->availabilityCheckerMock = $this->createMock(AvailabilityCheckerInterface::class);
        $this->sectionProviderMock = $this->createMock(SectionProviderInterface::class);
        $this->iriConverterMock = $this->createMock(IriConverterInterface::class);
        $this->productVariantNormalizer = new ProductVariantNormalizer($this->pricesCalculatorMock, $this->availabilityCheckerMock, $this->sectionProviderMock, $this->iriConverterMock, ['sylius:product_variant:index']);
    }

    public function testSupportsOnlyProductVariantInterface(): void
    {
        /** @var ShopApiSection|MockObject $shopApiSectionMock */
        $shopApiSectionMock = $this->createMock(ShopApiSection::class);
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($shopApiSectionMock);
        $this->assertTrue($this->productVariantNormalizer->supportsNormalization($variantMock, null, ['groups' => ['sylius:product_variant:index']]));
        $this->assertFalse($this->productVariantNormalizer->supportsNormalization($orderMock, null, ['groups' => ['sylius:product_variant:index']]));
    }

    public function testSupportsNormalizationIfSectionIsNotAdminGet(): void
    {
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ShopApiSection|MockObject $shopApiSectionMock */
        $shopApiSectionMock = $this->createMock(ShopApiSection::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($shopApiSectionMock);
        $this->assertTrue($this->productVariantNormalizer->supportsNormalization($variantMock, null, [
                'groups' => ['sylius:product_variant:index'],
            ]))
        ;
    }

    public function testDoesNotSupportIfSectionIsAdminGet(): void
    {
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        /** @var AdminApiSection|MockObject $adminApiSectionMock */
        $adminApiSectionMock = $this->createMock(AdminApiSection::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($adminApiSectionMock);
        $this->assertFalse($this->productVariantNormalizer->supportsNormalization($variantMock, null, [
                'groups' => ['sylius:product_variant:index'],
            ]))
        ;
    }

    public function testDoesNotSupportIfSerializationGroupIsNotSupported(): void
    {
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ShopApiSection|MockObject $shopApiSectionMock */
        $shopApiSectionMock = $this->createMock(ShopApiSection::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($shopApiSectionMock);
        $this->assertFalse($this->productVariantNormalizer->supportsNormalization($variantMock, null, [
                'groups' => ['sylius:product_variant:show'],
            ]))
        ;
    }

    public function testDoesNotSupportIfTheNormalizerHasBeenAlreadyCalled(): void
    {
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        $this->assertFalse($this->productVariantNormalizer
            ->supportsNormalization($variantMock, null, [
                'sylius_product_variant_normalizer_already_called' => true,
                'groups' => ['sylius:product_variant:index'],
            ]))
        ;
    }

    public function testSerializesProductVariantIfItemOperationNameIsDifferentThatAdminGet(): void
    {
        /** @var ShopApiSection|MockObject $shopApiSectionMock */
        $shopApiSectionMock = $this->createMock(ShopApiSection::class);
        /** @var NormalizerInterface|MockObject $normalizerMock */
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        $this->productVariantNormalizer->setNormalizer($normalizerMock);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($shopApiSectionMock);
        $normalizerMock->expects($this->once())->method('normalize')->with($variantMock, null, [
            'sylius_product_variant_normalizer_already_called' => true,
            ContextKeys::CHANNEL => $channelMock,
            'groups' => ['sylius:product_variant:index'],
        ])->willReturn([]);
        $this->pricesCalculatorMock->expects($this->once())->method('calculate')->with($variantMock, ['channel' => $channelMock])->willReturn(1000);
        $this->pricesCalculatorMock->expects($this->once())->method('calculateOriginal')->with($variantMock, ['channel' => $channelMock])->willReturn(1000);
        $this->pricesCalculatorMock->expects($this->once())->method('calculateLowestPriceBeforeDiscount')->with($variantMock, ['channel' => $channelMock])->willReturn(500);
        $variantMock->expects($this->once())->method('getAppliedPromotionsForChannel')->with($channelMock)->willReturn(new ArrayCollection());
        $this->availabilityCheckerMock->expects($this->once())->method('isStockAvailable')->with($variantMock)->willReturn(true);
        $this->productVariantNormalizer->expects($this->once())->method('normalize')->with($variantMock, null, [
            ContextKeys::CHANNEL => $channelMock,
            'groups' => ['sylius:product_variant:index'],
        ])
            ->shouldBeLike(['price' => 1000, 'originalPrice' => 1000, 'lowestPriceBeforeDiscount' => 500, 'inStock' => true])
        ;
    }

    public function testReturnsOriginalPriceIfIsDifferentThanPrice(): void
    {
        /** @var ShopApiSection|MockObject $shopApiSectionMock */
        $shopApiSectionMock = $this->createMock(ShopApiSection::class);
        /** @var NormalizerInterface|MockObject $normalizerMock */
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        $this->productVariantNormalizer->setNormalizer($normalizerMock);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($shopApiSectionMock);
        $normalizerMock->expects($this->once())->method('normalize')->with($variantMock, null, [
            'sylius_product_variant_normalizer_already_called' => true,
            ContextKeys::CHANNEL => $channelMock,
            'groups' => ['sylius:product_variant:index'],
        ])->willReturn([]);
        $this->pricesCalculatorMock->expects($this->once())->method('calculate')->with($variantMock, ['channel' => $channelMock])->willReturn(500);
        $this->pricesCalculatorMock->expects($this->once())->method('calculateOriginal')->with($variantMock, ['channel' => $channelMock])->willReturn(1000);
        $this->pricesCalculatorMock->expects($this->once())->method('calculateLowestPriceBeforeDiscount')->with($variantMock, ['channel' => $channelMock])->willReturn(100);
        $variantMock->expects($this->once())->method('getAppliedPromotionsForChannel')->with($channelMock)->willReturn(new ArrayCollection());
        $this->availabilityCheckerMock->expects($this->once())->method('isStockAvailable')->with($variantMock)->willReturn(true);
        $this->productVariantNormalizer->expects($this->once())->method('normalize')->with($variantMock, null, [
            ContextKeys::CHANNEL => $channelMock,
            'groups' => ['sylius:product_variant:index'],
        ])
            ->shouldBeLike(['price' => 500, 'originalPrice' => 1000, 'lowestPriceBeforeDiscount' => 100, 'inStock' => true])
        ;
    }

    public function testReturnsCatalogPromotionsIfApplied(): void
    {
        /** @var ShopApiSection|MockObject $shopApiSectionMock */
        $shopApiSectionMock = $this->createMock(ShopApiSection::class);
        /** @var NormalizerInterface|MockObject $normalizerMock */
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        /** @var CatalogPromotionInterface|MockObject $catalogPromotionMock */
        $catalogPromotionMock = $this->createMock(CatalogPromotionInterface::class);
        $this->productVariantNormalizer->setNormalizer($normalizerMock);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($shopApiSectionMock);
        $normalizerMock->expects($this->once())->method('normalize')->with($variantMock, null, [
            'sylius_product_variant_normalizer_already_called' => true,
            ContextKeys::CHANNEL => $channelMock,
            'groups' => ['sylius:product_variant:index'],
        ])->willReturn([]);
        $this->pricesCalculatorMock->expects($this->once())->method('calculate')->with($variantMock, ['channel' => $channelMock])->willReturn(500);
        $this->pricesCalculatorMock->expects($this->once())->method('calculateOriginal')->with($variantMock, ['channel' => $channelMock])->willReturn(1000);
        $this->pricesCalculatorMock->expects($this->once())->method('calculateLowestPriceBeforeDiscount')->with($variantMock, ['channel' => $channelMock])->willReturn(100);
        $catalogPromotionMock->expects($this->once())->method('getCode')->willReturn('winter_sale');
        $variantMock->expects($this->once())->method('getAppliedPromotionsForChannel')->with($channelMock)->willReturn(new ArrayCollection([$catalogPromotionMock]));
        $this->availabilityCheckerMock->expects($this->once())->method('isStockAvailable')->with($variantMock)->willReturn(true);
        $this->iriConverterMock->expects($this->once())->method('getIriFromResource')->with($catalogPromotionMock, UrlGeneratorInterface::ABS_PATH, null, [ContextKeys::CHANNEL => $channelMock, self::ALREADY_CALLED => true, 'groups' => ['sylius:product_variant:index']])
            ->willReturn('/api/v2/shop/catalog-promotions/winter_sale')
        ;
        $this->productVariantNormalizer->expects($this->once())->method('normalize')->with($variantMock, null, [
            ContextKeys::CHANNEL => $channelMock,
            'groups' => ['sylius:product_variant:index'],
        ])
            ->shouldBeLike([
                'price' => 500,
                'originalPrice' => 1000,
                'lowestPriceBeforeDiscount' => 100,
                'appliedPromotions' => ['/api/v2/shop/catalog-promotions/winter_sale'],
                'inStock' => true,
            ])
        ;
    }

    public function testDoesntReturnPricesAndPromotionsWhenChannelKeyIsNotInTheContext(): void
    {
        /** @var ShopApiSection|MockObject $shopApiSectionMock */
        $shopApiSectionMock = $this->createMock(ShopApiSection::class);
        /** @var NormalizerInterface|MockObject $normalizerMock */
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        $this->productVariantNormalizer->setNormalizer($normalizerMock);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($shopApiSectionMock);
        $normalizerMock->expects($this->once())->method('normalize')->with($variantMock, null, [
            'sylius_product_variant_normalizer_already_called' => true,
            'groups' => ['sylius:product_variant:index'],
        ])->willReturn([]);
        $this->pricesCalculatorMock->expects($this->never())->method('calculate')->with($this->any());
        $this->pricesCalculatorMock->expects($this->never())->method('calculateOriginal')->with($this->any());
        $variantMock->expects($this->never())->method('getAppliedPromotionsForChannel');
        $this->availabilityCheckerMock->expects($this->once())->method('isStockAvailable')->with($variantMock)->willReturn(true);
        $this->assertSame(['inStock' => true], $this->productVariantNormalizer->normalize($variantMock, null, ['groups' => ['sylius:product_variant:index']]));
    }

    public function testDoesntReturnPricesAndPromotionsWhenChannelFromContextIsNull(): void
    {
        /** @var ShopApiSection|MockObject $shopApiSectionMock */
        $shopApiSectionMock = $this->createMock(ShopApiSection::class);
        /** @var NormalizerInterface|MockObject $normalizerMock */
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        $this->productVariantNormalizer->setNormalizer($normalizerMock);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($shopApiSectionMock);
        $normalizerMock->expects($this->once())->method('normalize')->with($variantMock, null, [
            'sylius_product_variant_normalizer_already_called' => true,
            ContextKeys::CHANNEL => null,
            'groups' => ['sylius:product_variant:index'],
        ])->willReturn([]);
        $this->pricesCalculatorMock->expects($this->never())->method('calculate')->with($this->any());
        $this->pricesCalculatorMock->expects($this->never())->method('calculateOriginal')->with($this->any());
        $variantMock->expects($this->never())->method('getAppliedPromotionsForChannel');
        $this->availabilityCheckerMock->expects($this->once())->method('isStockAvailable')->with($variantMock)->willReturn(true);
        $this->assertSame(['inStock' => true], $this->productVariantNormalizer->normalize($variantMock, null, [
            ContextKeys::CHANNEL => null,
            'groups' => ['sylius:product_variant:index'],
        ]));
    }

    public function testDoesntReturnPricesIfChannelConfigurationIsNotFound(): void
    {
        /** @var ShopApiSection|MockObject $shopApiSectionMock */
        $shopApiSectionMock = $this->createMock(ShopApiSection::class);
        /** @var NormalizerInterface|MockObject $normalizerMock */
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        $this->productVariantNormalizer->setNormalizer($normalizerMock);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($shopApiSectionMock);
        $normalizerMock->expects($this->once())->method('normalize')->with($variantMock, null, [
            'sylius_product_variant_normalizer_already_called' => true,
            ContextKeys::CHANNEL => $channelMock,
            'groups' => ['sylius:product_variant:index'],
        ])->willReturn([]);
        $this->pricesCalculatorMock->expects($this->once())->method('calculate')->with($variantMock, ['channel' => $channelMock])->willThrowException(MissingChannelConfigurationException::class);
        $this->pricesCalculatorMock->expects($this->once())->method('calculateOriginal')->with($variantMock, ['channel' => $channelMock])->willThrowException(MissingChannelConfigurationException::class);
        $variantMock->expects($this->once())->method('getAppliedPromotionsForChannel')->with($channelMock)->willReturn(new ArrayCollection());
        $this->availabilityCheckerMock->expects($this->once())->method('isStockAvailable')->with($variantMock)->willReturn(true);
        $this->assertSame(['inStock' => true], $this->productVariantNormalizer->normalize($variantMock, null, [
            ContextKeys::CHANNEL => $channelMock,
            'groups' => ['sylius:product_variant:index'],
        ]));
    }

    public function testThrowsAnExceptionIfTheNormalizerHasBeenAlreadyCalled(): void
    {
        /** @var ShopApiSection|MockObject $shopApiSectionMock */
        $shopApiSectionMock = $this->createMock(ShopApiSection::class);
        /** @var NormalizerInterface|MockObject $normalizerMock */
        $normalizerMock = $this->createMock(NormalizerInterface::class);
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        $this->productVariantNormalizer->setNormalizer($normalizerMock);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($shopApiSectionMock);
        $normalizerMock->expects($this->never())->method('normalize')->with($variantMock, null, [
            'sylius_product_variant_normalizer_already_called' => true,
            'groups' => ['sylius:product_variant:index'],
        ]);
        $this->expectException(InvalidArgumentException::class);
        $this->productVariantNormalizer->normalize($variantMock, null, [
            'sylius_product_variant_normalizer_already_called' => true,
            'groups' => ['sylius:product_variant:index'],
        ]);
    }
}
