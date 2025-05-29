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

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Serializer\Denormalizer\CustomerDenormalizer;
use Sylius\Component\Customer\Model\CustomerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class CustomerDenormalizerTest extends TestCase
{
    /** @var ClockInterface|MockObject */
    private MockObject $clockMock;

    private CustomerDenormalizer $customerDenormalizer;

    private const ALREADY_CALLED = 'sylius_customer_denormalizer_already_called';

    protected function setUp(): void
    {
        $this->clockMock = $this->createMock(ClockInterface::class);
        $this->customerDenormalizer = new CustomerDenormalizer($this->clockMock);
    }

    public function testDoesNotSupportDenormalizationWhenTheDenormalizerHasAlreadyBeenCalled(): void
    {
        $this->assertFalse($this->customerDenormalizer->supportsDenormalization([], CustomerInterface::class, context: [self::ALREADY_CALLED => true]));
    }

    public function testDoesNotSupportDenormalizationWhenDataIsNotAnArray(): void
    {
        $this->assertFalse($this->customerDenormalizer->supportsDenormalization('string', CustomerInterface::class));
    }

    public function testDoesNotSupportDenormalizationWhenTypeIsNotACustomer(): void
    {
        $this->assertFalse($this->customerDenormalizer->supportsDenormalization([], 'string'));
    }

    public function testDoesNothingIfUserVerifiedIsNotSet(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);

        $this->customerDenormalizer->setDenormalizer($denormalizerMock);

        $denormalizerMock->expects($this->once())
            ->method('denormalize')
            ->with([], CustomerInterface::class, null, [self::ALREADY_CALLED => true])
            ->willReturn($customerMock);

        $this->assertSame($customerMock, $this->customerDenormalizer->denormalize([], CustomerInterface::class));

        // Replace shouldNotHaveBeenCalled
        $this->clockMock->expects($this->never())->method('now');
    }

    public function testChangesUserVerifiedFromFalseToNull(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        $this->customerDenormalizer->setDenormalizer($denormalizerMock);
        $denormalizerMock->expects($this->once())->method('denormalize')->with(['user' => ['verified' => null]], CustomerInterface::class, null, [self::ALREADY_CALLED => true])->willReturn($customerMock);
        $this->assertSame($customerMock, $this->customerDenormalizer->denormalize(['user' => ['verified' => false]], CustomerInterface::class));
        $this->clockMock->expects($this->never())->method('now');
    }

    public function testChangesUserVerifiedFromTrueToDatetime(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        $this->customerDenormalizer->setDenormalizer($denormalizerMock);
        $dateTime = new DateTimeImmutable('2021-01-01T00:00:00+00:00');
        $this->clockMock->expects($this->once())->method('now')->willReturn($dateTime);
        $denormalizerMock->expects($this->once())->method('denormalize')->with(['user' => ['verified' => '2021-01-01T00:00:00+00:00']], CustomerInterface::class, null, [self::ALREADY_CALLED => true])->willReturn($customerMock);
        $this->assertSame($customerMock, $this->customerDenormalizer->denormalize(['user' => ['verified' => true]], CustomerInterface::class));
    }
}
