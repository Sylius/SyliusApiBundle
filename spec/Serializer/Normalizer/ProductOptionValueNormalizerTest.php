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

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\Serializer\Normalizer\ProductOptionValueNormalizer;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Resource\Translation\TranslatableEntityLocaleAssignerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ProductOptionValueNormalizerTest extends TestCase
{
    /** @var NormalizerInterface|MockObject */
    private MockObject $normalizerMock;

    /** @var TranslatableEntityLocaleAssignerInterface|MockObject */
    private MockObject $translatableEntityLocaleAssignerMock;

    private ProductOptionValueNormalizer $productOptionValueNormalizer;

    protected function setUp(): void
    {
        $this->normalizerMock = $this->createMock(NormalizerInterface::class);
        $this->translatableEntityLocaleAssignerMock = $this->createMock(TranslatableEntityLocaleAssignerInterface::class);
        $this->productOptionValueNormalizer = new ProductOptionValueNormalizer($this->translatableEntityLocaleAssignerMock);
        $this->setNormalizer($this->normalizerMock);
    }

    public function testAnAwareNormalizer(): void
    {
        $this->assertInstanceOf(NormalizerAwareInterface::class, $this->productOptionValueNormalizer);
    }

    public function testSupportsOnlyProductOptionValueInterface(): void
    {
        /** @var ProductOptionValueInterface|MockObject $productOptionValueMock */
        $productOptionValueMock = $this->createMock(ProductOptionValueInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->assertTrue($this->productOptionValueNormalizer->supportsNormalization($productOptionValueMock));
        $this->assertFalse($this->productOptionValueNormalizer->supportsNormalization($orderMock));
    }

    public function testSupportsTheNormalizerHasNotCalledYet(): void
    {
        /** @var ProductOptionValueInterface|MockObject $productOptionValueMock */
        $productOptionValueMock = $this->createMock(ProductOptionValueInterface::class);
        $this->assertTrue($this->productOptionValueNormalizer
            ->supportsNormalization($productOptionValueMock, null, []))
        ;
        $this->assertFalse($this->productOptionValueNormalizer
            ->supportsNormalization($productOptionValueMock, null, ['sylius_product_option_value_normalizer_already_called' => true]))
        ;
    }

    public function testAssignsLocaleToTranslatableEntity(): void
    {
        /** @var ProductOptionValueInterface|MockObject $productOptionValueMock */
        $productOptionValueMock = $this->createMock(ProductOptionValueInterface::class);
        $this->normalizerMock->expects($this->once())->method('normalize')->with($productOptionValueMock, null, ['sylius_product_option_value_normalizer_already_called' => true])
            ->willReturn([])
        ;
        $this->translatableEntityLocaleAssignerMock->expects($this->once())->method('assignLocale')->with($productOptionValueMock);
        $this->assertSame([], $this->productOptionValueNormalizer->normalize($productOptionValueMock, null, []));
    }

    public function testThrowsAnExceptionIfTheGivenObjectIsNotAProductOptionValueInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->productOptionValueNormalizer->normalize(new stdClass());
    }

    public function testThrowsAnExceptionIfTheNormalizerWasAlreadyCalled(): void
    {
        /** @var ProductOptionValueInterface|MockObject $productOptionValueMock */
        $productOptionValueMock = $this->createMock(ProductOptionValueInterface::class);
        $this->expectException(InvalidArgumentException::class);
        $this->productOptionValueNormalizer->normalize($productOptionValueMock, null, ['sylius_product_option_value_normalizer_already_called' => true]);
    }
}
