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

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Bundle\ApiBundle\Serializer\Denormalizer\CommandArgumentsDenormalizer;
use Sylius\Bundle\ApiBundle\Command\Catalog\AddProductReview;
use Sylius\Bundle\ApiBundle\Command\IriToIdentifierConversionAwareInterface;
use Sylius\Bundle\ApiBundle\Converter\IriToIdentifierConverterInterface;
use Sylius\Component\Core\Model\Order;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CommandArgumentsDenormalizerTest extends TestCase
{
    /** @var DenormalizerInterface|MockObject */
    private MockObject $commandDenormalizerMock;
    /** @var IriToIdentifierConverterInterface|MockObject */
    private MockObject $iriToIdentifierConverterMock;
    private CommandArgumentsDenormalizer $commandArgumentsDenormalizer;
    protected function setUp(): void
    {
        $this->commandDenormalizerMock = $this->createMock(DenormalizerInterface::class);
        $this->iriToIdentifierConverterMock = $this->createMock(IriToIdentifierConverterInterface::class);
        $this->commandArgumentsDenormalizer = new CommandArgumentsDenormalizer($this->commandDenormalizerMock, $this->iriToIdentifierConverterMock);
    }

    public function testSupportsDenormalizationAddProductReview(): void
    {
        $context = ['input' => ['class' => AddProductReview::class]];

        $this->assertTrue($this->commandArgumentsDenormalizer
            ->supportsDenormalization(
                new AddProductReview('Cap', 5, 'ok', 'cap_code', 'john@example.com'),
                AddProductReview::class,
                null,
                $context,
            ))
        ;
    }

    public function testDoesNotSupportDenormalizationForNotSupportedClass(): void
    {
        $context = ['input' => ['class' => Order::class]];

        $this->assertFalse($this->commandArgumentsDenormalizer
            ->supportsDenormalization(
                new Order(),
                AddProductReview::class,
                null,
                $context,
            ))
        ;
    }

    public function testDenormalizesAddProductReviewAndConvertsProductFieldFromIriToCode(): void
    {
        $context = ['input' => ['class' => AddProductReview::class]];
        $addProductReview = new AddProductReview('Cap', 5, 'ok', 'cap_code', 'john@example.com');
        $this->iriToIdentifierConverterMock->expects($this->once())->method('isIdentifier')->with('Cap')->willReturn(false);
        $this->iriToIdentifierConverterMock->expects($this->once())->method('isIdentifier')->with(5)->willReturn(false);
        $this->iriToIdentifierConverterMock->expects($this->once())->method('isIdentifier')->with('ok')->willReturn(false);
        $this->iriToIdentifierConverterMock->expects($this->once())->method('isIdentifier')->with('john@example.com')->willReturn(false);
        $this->iriToIdentifierConverterMock->expects($this->once())->method('isIdentifier')->with('/api/v2/shop/products/cap_code')->willReturn(true);
        $this->iriToIdentifierConverterMock->expects($this->once())->method('getIdentifier')->with('/api/v2/shop/products/cap_code')->willReturn('cap_code');
        $this->commandDenormalizerMock->expects($this->once())->method('denormalize')->with([
            'title' => 'Cap',
            'rating' => 5,
            'comment' => 'ok',
            'product' => 'cap_code',
            'email' => 'john@example.com',
        ], AddProductReview::class, null, $context)
            ->willReturn($addProductReview)
        ;
        $this->assertSame($addProductReview, $this->commandArgumentsDenormalizer
            ->denormalize(
                [
                    'title' => 'Cap',
                    'rating' => 5,
                    'comment' => 'ok',
                    'product' => '/api/v2/shop/products/cap_code',
                    'email' => 'john@example.com',
                ],
                AddProductReview::class,
                null,
                $context,
            ))
        ;
    }

    public function testDenormalizesACommandWithAnArrayOfIris(): void
    {
        $command = new class() implements IriToIdentifierConversionAwareInterface {
            public string $iri = '/api/v2/iri';

            public array $arrayIris = [
                '/api/v2/first-iri',
                '/api/v2/second-iri',
            ];

            public array $arrayField = ['array'];
        };
        $context = ['input' => ['class' => $command::class]];
        $this->iriToIdentifierConverterMock->expects($this->once())->method('isIdentifier')->with('array')->willReturn(false);
        $this->iriToIdentifierConverterMock->expects($this->once())->method('isIdentifier')->with('/api/v2/iri')->willReturn(true);
        $this->iriToIdentifierConverterMock->expects($this->once())->method('getIdentifier')->with('/api/v2/iri')->willReturn('iri');
        $this->iriToIdentifierConverterMock->expects($this->once())->method('isIdentifier')->with('/api/v2/first-iri')->willReturn(true);
        $this->iriToIdentifierConverterMock->expects($this->once())->method('getIdentifier')->with('/api/v2/first-iri')->willReturn('first-iri');
        $this->iriToIdentifierConverterMock->expects($this->once())->method('isIdentifier')->with('')->willReturn(false);
        $this->iriToIdentifierConverterMock->expects($this->never())->method('getIdentifier')->with('');
        $this->iriToIdentifierConverterMock->expects($this->once())->method('isIdentifier')->with('/api/v2/second-iri')->willReturn(true);
        $this->iriToIdentifierConverterMock->expects($this->once())->method('getIdentifier')->with('/api/v2/second-iri')->willReturn('second-iri');
        $this->commandDenormalizerMock->expects($this->once())->method('denormalize')->with([
            'iri' => 'iri',
            'arrayIris' => [
                'first-iri',
                'second-iri',
                '',
            ],
            'arrayField' => ['array'],
        ], $command::class, null, $context)
            ->willReturn($command)
        ;
        $this->assertSame($command, $this->commandArgumentsDenormalizer
            ->denormalize(
                [
                    'iri' => '/api/v2/iri',
                    'arrayIris' => [
                        '/api/v2/first-iri',
                        '/api/v2/second-iri',
                        '',
                    ],
                    'arrayField' => ['array'],
                ],
                $command::class,
                null,
                $context,
            ))
        ;
    }
}
