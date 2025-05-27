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
use Sylius\Bundle\ApiBundle\Exception\TranslationLocaleMismatchException;
use Sylius\Bundle\ApiBundle\Serializer\Denormalizer\TranslatableLocaleKeyDenormalizer;
use Sylius\Resource\Model\TranslatableInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class TranslatableLocaleKeyDenormalizerTest extends TestCase
{
    private TranslatableLocaleKeyDenormalizer $translatableLocaleKeyDenormalizer;

    protected function setUp(): void
    {
        $this->translatableLocaleKeyDenormalizer = new TranslatableLocaleKeyDenormalizer();
    }

    public function testDoesNotSupportDenormalizationWhenTheDenormalizerHasAlreadyBeenCalled(): void
    {
        $this->assertFalse($this->translatableLocaleKeyDenormalizer
            ->supportsDenormalization([], TranslatableInterface::class, context: [
                'sylius_translatable_locale_key_denormalizer_already_called_for_Sylius\Resource\Model\TranslatableInterface' => true,
            ]))
        ;
    }

    public function testDoesNotSupportDenormalizationWhenDataIsNotAnArray(): void
    {
        $this->assertFalse($this->translatableLocaleKeyDenormalizer->supportsDenormalization('string', TranslatableInterface::class));
    }

    public function testDoesNotSupportDenormalizationWhenTypeIsNotATranslatable(): void
    {
        $this->assertFalse($this->translatableLocaleKeyDenormalizer->supportsDenormalization([], 'string'));
    }

    public function testDoesNothingIfThereIsNoTranslationKey(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        $this->translatableLocaleKeyDenormalizer->setDenormalizer($denormalizerMock);
        $this->translatableLocaleKeyDenormalizer->denormalize([], TranslatableInterface::class);
        $denormalizerMock->expects($this->once())->method('denormalize')->with([], TranslatableInterface::class, null, [
            'sylius_translatable_locale_key_denormalizer_already_called_for_Sylius\Resource\Model\TranslatableInterface' => true,
        ])->shouldHaveBeenCalledOnce();
    }

    public function testChangesKeysOfTranslationsToLocale(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        $this->translatableLocaleKeyDenormalizer->setDenormalizer($denormalizerMock);
        $originalData = ['translations' => ['en_US' => ['locale' => 'en_US'], 'de_DE' => []]];
        $updatedData = ['translations' => ['en_US' => ['locale' => 'en_US'], 'de_DE' => ['locale' => 'de_DE']]];
        $this->translatableLocaleKeyDenormalizer->denormalize($originalData, TranslatableInterface::class);
        $denormalizerMock->expects($this->once())->method('denormalize')->with($updatedData, TranslatableInterface::class, null, ['sylius_translatable_locale_key_denormalizer_already_called_for_Sylius\Resource\Model\TranslatableInterface' => true])->shouldHaveBeenCalledOnce();
    }

    public function testThrowsAnExceptionIfLocaleIsNotTheSameAsKey(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        $this->translatableLocaleKeyDenormalizer->setDenormalizer($denormalizerMock);
        $this->expectException(TranslationLocaleMismatchException::class);
        $this->translatableLocaleKeyDenormalizer->denormalize(['translations' => ['de_DE' => ['locale' => 'en_US']]], TranslatableInterface::class);
    }
}
