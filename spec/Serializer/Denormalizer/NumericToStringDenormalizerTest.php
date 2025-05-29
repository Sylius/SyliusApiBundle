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
use stdClass;
use Sylius\Bundle\ApiBundle\Serializer\Denormalizer\NumericToStringDenormalizer;
use Sylius\Component\Core\Model\TaxRateInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class NumericToStringDenormalizerTest extends TestCase
{
    /** @var DenormalizerInterface|MockObject */
    private MockObject $denormalizerMock;

    private NumericToStringDenormalizer $numericToStringDenormalizer;

    public const ALREADY_CALLED = 'sylius_numeric_to_string_denormalizer_already_called_for_Sylius\Component\Core\Model\TaxRateInterface';

    protected function setUp(): void
    {
        $this->denormalizerMock = $this->createMock(DenormalizerInterface::class);

        // Initialize NumericToStringDenormalizer
        $this->numericToStringDenormalizer = new NumericToStringDenormalizer(
            TaxRateInterface::class,
            'amount'
        );

        // Set the denormalizer mock on the test subject
        $this->numericToStringDenormalizer->setDenormalizer($this->denormalizerMock);
    }

    public function testSupportsDenormalizationOfTaxRateWithAmountSet(): void
    {
        $this->assertFalse(
            $this->numericToStringDenormalizer->supportsDenormalization(['amount' => 0.23], \stdClass::class)
        );

        $this->assertFalse(
            $this->numericToStringDenormalizer->supportsDenormalization(0.23, TaxRateInterface::class)
        );

        $this->assertFalse(
            $this->numericToStringDenormalizer->supportsDenormalization([], TaxRateInterface::class)
        );

        $this->assertFalse(
            $this->numericToStringDenormalizer->supportsDenormalization(
                ['amount' => 0.23],
                TaxRateInterface::class,
                null,
                [self::ALREADY_CALLED => true]
            )
        );

        $this->assertTrue(
            $this->numericToStringDenormalizer->supportsDenormalization(['amount' => 0.23], TaxRateInterface::class)
        );
    }

    public function testDenormalizesTaxRateChangingFloatAmountToString(): void
    {
        /** @var TaxRateInterface|MockObject $taxRateMock */
        $taxRateMock = $this->createMock(TaxRateInterface::class);

        $this->denormalizerMock
            ->expects($this->once())
            ->method('denormalize')
            ->with(['amount' => '0.23'], TaxRateInterface::class, null, [self::ALREADY_CALLED => true])
            ->willReturn($taxRateMock);

        $this->assertSame(
            $taxRateMock,
            $this->numericToStringDenormalizer->denormalize(['amount' => 0.23], TaxRateInterface::class)
        );
    }

    public function testDenormalizesTaxRateChangingIntAmountToString(): void
    {
        /** @var TaxRateInterface|MockObject $taxRateMock */
        $taxRateMock = $this->createMock(TaxRateInterface::class);

        $this->denormalizerMock
            ->expects($this->once())
            ->method('denormalize')
            ->with(['amount' => '12'], TaxRateInterface::class, null, [self::ALREADY_CALLED => true])
            ->willReturn($taxRateMock);

        $this->assertSame(
            $taxRateMock,
            $this->numericToStringDenormalizer->denormalize(['amount' => 12], TaxRateInterface::class)
        );
    }
}
