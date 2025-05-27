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

namespace Tests\Sylius\Bundle\ApiBundle\Provider;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Payment\PaymentConfigurationProviderInterface;
use Sylius\Bundle\ApiBundle\Provider\CompositePaymentConfigurationProvider;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;

final class CompositePaymentConfigurationProviderTest extends TestCase
{
    /** @var PaymentConfigurationProviderInterface|MockObject */
    private MockObject $apiPaymentMethodMock;

    private CompositePaymentConfigurationProvider $compositePaymentConfigurationProvider;

    protected function setUp(): void
    {
        $this->apiPaymentMethodMock = $this->createMock(PaymentConfigurationProviderInterface::class);
        $this->compositePaymentConfigurationProvider = new CompositePaymentConfigurationProvider([$this->apiPaymentMethodMock]);
    }

    public function testProvidesPaymentDataIfPaymentIsSupported(): void
    {
        /** @var PaymentInterface|MockObject $paymentMock */
        $paymentMock = $this->createMock(PaymentInterface::class);
        /** @var PaymentMethodInterface|MockObject $paymentMethodMock */
        $paymentMethodMock = $this->createMock(PaymentMethodInterface::class);
        $paymentMock->expects($this->once())->method('getMethod')->willReturn($paymentMethodMock);
        $this->apiPaymentMethodMock->expects($this->once())->method('supports')->with($paymentMethodMock)->willReturn(true);
        $this->apiPaymentMethodMock->expects($this->once())->method('provideConfiguration')->with($paymentMock)->willReturn(['payment_data' => 'PAYMENT_DATA']);
        $this->assertSame(['payment_data' => 'PAYMENT_DATA'], $this->compositePaymentConfigurationProvider->provide($paymentMock));
    }

    public function testReturnsEmptyArrayIfPaymentIsNotSupported(): void
    {
        /** @var PaymentInterface|MockObject $paymentMock */
        $paymentMock = $this->createMock(PaymentInterface::class);
        /** @var PaymentMethodInterface|MockObject $paymentMethodMock */
        $paymentMethodMock = $this->createMock(PaymentMethodInterface::class);
        $paymentMock->expects($this->once())->method('getMethod')->willReturn($paymentMethodMock);
        $this->apiPaymentMethodMock->expects($this->once())->method('supports')->with($paymentMethodMock)->willReturn(false);
        $this->apiPaymentMethodMock->expects($this->never())->method('provideConfiguration')->with($paymentMock);
        $this->assertSame([], $this->compositePaymentConfigurationProvider->provide($paymentMock));
    }

    public function testSupportsMoreThanOnePaymentMethod(): void
    {
        /** @var PaymentInterface|MockObject $paymentMock */
        $paymentMock = $this->createMock(PaymentInterface::class);
        /** @var PaymentMethodInterface|MockObject $paymentMethodMock */
        $paymentMethodMock = $this->createMock(PaymentMethodInterface::class);
        /** @var PaymentConfigurationProviderInterface|MockObject $apiPaymentMethodOneMock */
        $apiPaymentMethodOneMock = $this->createMock(PaymentConfigurationProviderInterface::class);
        /** @var PaymentConfigurationProviderInterface|MockObject $apiPaymentMethodTwoMock */
        $apiPaymentMethodTwoMock = $this->createMock(PaymentConfigurationProviderInterface::class);
        $this->compositePaymentConfigurationProvider = new CompositePaymentConfigurationProvider([$apiPaymentMethodOneMock, $apiPaymentMethodTwoMock]);
        $paymentMock->expects($this->once())->method('getMethod')->willReturn($paymentMethodMock);
        $apiPaymentMethodOneMock->expects($this->once())->method('supports')->with($paymentMethodMock)->willReturn(false);
        $apiPaymentMethodTwoMock->expects($this->once())->method('supports')->with($paymentMethodMock)->willReturn(true);
        $apiPaymentMethodOneMock->expects($this->never())->method('provideConfiguration')->with($paymentMock);
        $apiPaymentMethodTwoMock->expects($this->once())->method('provideConfiguration')->with($paymentMock)->willReturn(['payment_data_two' => 'PAYMENT_DATA_TWO']);
        $this->assertSame(['payment_data_two' => 'PAYMENT_DATA_TWO'], $this->compositePaymentConfigurationProvider->provide($paymentMock));
    }
}
