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

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Bundle\ApiBundle\Serializer\Normalizer\CommandNormalizer;
use stdClass;
use Exception;
use Sylius\Bundle\ApiBundle\Exception\InvalidRequestArgumentException;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CommandNormalizerTest extends TestCase
{
    /** @var NormalizerInterface|MockObject */
    private MockObject $baseNormalizerMock;
    private CommandNormalizer $commandNormalizer;
    protected function setUp(): void
    {
        $this->baseNormalizerMock = $this->createMock(NormalizerInterface::class);
        $this->commandNormalizer = new CommandNormalizer($this->baseNormalizerMock);
    }

    public function testImplementsContextAwareNormalizerInterface(): void
    {
        $this->assertInstanceOf(NormalizerInterface::class, $this->commandNormalizer);
    }

    public function testSupportsNormalizationIfDataHasGetClassMethodAndItIsMissingConstructorArgumentsException(): void
    {
        $this->assertTrue($this->commandNormalizer->supportsNormalization(
            final  extends TestCaseclass() {
                public function testGetClass(): void
                {
                    return MissingConstructorArgumentsException::class;
                }
            },
        ));

        $this->assertTrue($this->commandNormalizer->supportsNormalization(
            final  extends TestCaseclass() {
                public function testGetClass(): void
                {
                    return InvalidRequestArgumentException::class;
                }
            },
        ));
    }

    public function testDoesNotSupportNormalizationIfDataHasNoGetClassMethod(): void
    {
        $this->assertFalse($this->commandNormalizer->supportsNormalization(new stdClass()));
    }

    public function testDoesNotSupportNormalizationIfDataClassIsNotMissingConstructorArgumentsException(): void
    {
        $this->assertFalse($this->commandNormalizer
            ->supportsNormalization(final  extends TestCaseclass() {
                public function testGetClass(): void
                {
                    return Exception::class;
                }
            }))
        ;
    }

    public function testDoesNotSupportNormalizationIfNormalizerHasAlreadyBeenCalled(): void
    {
        $this->assertFalse($this->commandNormalizer
            ->supportsNormalization(
                final  extends TestCaseclass() {
                    public function testGetClass(): void
                    {
                        return MissingConstructorArgumentsException::class;
                    }
                },
                null,
                ['sylius_command_normalizer_already_called' => true],
            ))
        ;
    }

    public function testDoesNotSupportNormalizationIfDataIsNotAnObject(): void
    {
        $this->assertFalse($this->commandNormalizer->supportsNormalization('test'));
    }

    public function testNormalizesResponseForMissingConstructorArgumentsException(): void
    {
        /** @var stdClass|MockObject $objectMock */
        $objectMock = $this->createMock(stdClass::class);
        $this->baseNormalizerMock->expects($this->once())->method('normalize')->with($objectMock, null, ['sylius_command_normalizer_already_called' => true])
            ->willReturn(['message' => 'Message'])
        ;
        $this->assertSame(['code' => 400, 'message' => 'Message'], $this->commandNormalizer->normalize($objectMock));
    }
}
