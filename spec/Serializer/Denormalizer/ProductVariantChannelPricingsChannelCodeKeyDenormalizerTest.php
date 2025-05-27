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

namespace Tests\Sylius\Bundle\ApiBundle\Serializer\Denormalizer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Exception\ChannelPricingChannelCodeMismatchException;
use Sylius\Bundle\ApiBundle\Serializer\Denormalizer\ProductVariantChannelPricingsChannelCodeKeyDenormalizer;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ProductVariantChannelPricingsChannelCodeKeyDenormalizerTest extends TestCase
{
    private ProductVariantChannelPricingsChannelCodeKeyDenormalizer $productVariantChannelPricingsChannelCodeKeyDenormalizer;

    protected function setUp(): void
    {
        $this->productVariantChannelPricingsChannelCodeKeyDenormalizer = new ProductVariantChannelPricingsChannelCodeKeyDenormalizer();
    }

    public function testDoesNotSupportDenormalizationWhenTheDenormalizerHasAlreadyBeenCalled(): void
    {
        $this->assertFalse($this->productVariantChannelPricingsChannelCodeKeyDenormalizer
            ->supportsDenormalization([], ProductVariantInterface::class, context: [
                'sylius_product_variant_channel_pricings_channel_code_key_denormalizer_already_called' => true,
            ]))
        ;
    }

    public function testDoesNotSupportDenormalizationWhenDataIsNotAnArray(): void
    {
        $this->assertFalse($this->productVariantChannelPricingsChannelCodeKeyDenormalizer->supportsDenormalization('string', ProductVariantInterface::class));
    }

    public function testDoesNotSupportDenormalizationWhenTypeIsNotAProductVariant(): void
    {
        $this->assertFalse($this->productVariantChannelPricingsChannelCodeKeyDenormalizer->supportsDenormalization([], 'string'));
    }

    public function testDoesNothingIfThereIsNoChannelPricingsKey(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        $this->productVariantChannelPricingsChannelCodeKeyDenormalizer->setDenormalizer($denormalizerMock);
        $this->productVariantChannelPricingsChannelCodeKeyDenormalizer->denormalize([], ProductVariantInterface::class);
        $denormalizerMock->expects($this->once())->method('denormalize')->with([], ProductVariantInterface::class, null, [
            'sylius_product_variant_channel_pricings_channel_code_key_denormalizer_already_called' => true,
        ])->shouldHaveBeenCalledOnce();
    }

    public function testChangesKeysOfChannelPricingsToChannelCode(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        $this->productVariantChannelPricingsChannelCodeKeyDenormalizer->setDenormalizer($denormalizerMock);
        $originalData = ['channelPricings' => ['WEB' => ['channelCode' => 'WEB'], 'MOBILE' => []]];
        $updatedData = ['channelPricings' => ['WEB' => ['channelCode' => 'WEB'], 'MOBILE' => ['channelCode' => 'MOBILE']]];
        $this->productVariantChannelPricingsChannelCodeKeyDenormalizer->denormalize($originalData, ProductVariantInterface::class);
        $denormalizerMock->expects($this->once())->method('denormalize')->with($updatedData, ProductVariantInterface::class, null, ['sylius_product_variant_channel_pricings_channel_code_key_denormalizer_already_called' => true])->shouldHaveBeenCalledOnce();
    }

    public function testThrowsAnExceptionIfChannelCodeIsNotTheSameAsKey(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        $this->productVariantChannelPricingsChannelCodeKeyDenormalizer->setDenormalizer($denormalizerMock);
        $this->expectException(ChannelPricingChannelCodeMismatchException::class);
        $this->productVariantChannelPricingsChannelCodeKeyDenormalizer->denormalize(['channelPricings' => ['WEB' => ['channelCode' => 'MOBILE']]], ProductVariantInterface::class);
    }
}
