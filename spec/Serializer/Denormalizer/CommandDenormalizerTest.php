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
use Sylius\Bundle\ApiBundle\Command\Account\RegisterShopUser;
use Sylius\Bundle\ApiBundle\Exception\InvalidRequestArgumentException;
use Sylius\Bundle\ApiBundle\Serializer\Denormalizer\CommandDenormalizer;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\NameConverter\AdvancedNameConverterInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CommandDenormalizerTest extends TestCase
{
    /** @var DenormalizerInterface|MockObject */
    private MockObject $baseNormalizerMock;

    /** @var AdvancedNameConverterInterface|MockObject */
    private MockObject $nameConverterMock;

    private CommandDenormalizer $commandDenormalizer;

    protected function setUp(): void
    {
        $this->baseNormalizerMock = $this->createMock(DenormalizerInterface::class);
        $this->nameConverterMock = $this->createMock(AdvancedNameConverterInterface::class);
        $this->commandDenormalizer = new CommandDenormalizer($this->baseNormalizerMock, $this->nameConverterMock);
    }

    public function testImplementsContextAwareDenormalizerInterface(): void
    {
        $this->assertInstanceOf(DenormalizerInterface::class, $this->commandDenormalizer);
    }

    public function testSupportsDenormalizationForSpecifiedInputClass(): void
    {
        $this->assertTrue($this->commandDenormalizer->supportsDenormalization(null, '', context: ['input' => ['class' => 'Class']]));
    }

    public function testDoesNotSupportDenormalizationForNotSpecifiedInputClass(): void
    {
        $this->assertFalse($this->commandDenormalizer->supportsDenormalization(null, ''));
    }

    public function testThrowsExceptionIfNotAllRequiredParametersArePresentInTheContext(): void
    {
        $exception = new MissingConstructorArgumentsException('', 400, null, ['firstName', 'lastName']);
        $context = ['input' => ['class' => RegisterShopUser::class]];
        $data = ['email' => 'test@example.com', 'password' => 'pa$$word'];
        $this->nameConverterMock->expects($this->once())->method('normalize')->with('firstName', class: RegisterShopUser::class)->willReturn('first_name');
        $this->nameConverterMock->expects($this->once())->method('normalize')->with('lastName', class: RegisterShopUser::class)->willReturn('lastName');
        $this->baseNormalizerMock->expects($this->once())->method('denormalize')->with($data, '', null, $context)->willThrowException($exception);
        $this->expectException(MissingConstructorArgumentsException::class);
        $this->commandDenormalizer->expectExceptionMessage('Request does not have the following required fields specified: first_name, lastName.');
        $this->commandDenormalizer->denormalize($data, '', null, $context);
    }

    public function testThrowsExceptionForMismatchedArgumentType(): void
    {
        $previousException = NotNormalizableValueException::createForUnexpectedDataType('', 1, ['string'], 'firstName');
        $exception = new UnexpectedValueException('', 400, $previousException);
        $context = ['input' => ['class' => RegisterShopUser::class]];
        $data = ['firstName' => 1];
        $this->nameConverterMock->expects($this->once())->method('normalize')->with('firstName', class: RegisterShopUser::class)->willReturn('first_name');
        $this->baseNormalizerMock->expects($this->once())->method('denormalize')->with($data, '', null, $context)->willThrowException($exception);
        $this->expectException(InvalidRequestArgumentException::class);
        $this->commandDenormalizer->expectExceptionMessage('Request field "first_name" should be of type "string".');
        $this->commandDenormalizer->denormalize($data, '', null, $context);
    }

    public function testThrowsTheSameExceptionIfPreviousExceptionIsNotNormalizableValueException(): void
    {
        $exception = new UnexpectedValueException('Unexpected value');
        $context = ['input' => ['class' => RegisterShopUser::class]];
        $data = ['firstName' => '1'];
        $this->baseNormalizerMock->expects($this->once())->method('denormalize')->with($data, '', null, $context)->willThrowException($exception);
        $this->expectException(UnexpectedValueException::class);
        $this->commandDenormalizer->expectExceptionMessage('Unexpected value');
        $this->commandDenormalizer->denormalize($data, '', null, $context);
    }
}
