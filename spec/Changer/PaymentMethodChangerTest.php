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

namespace Tests\Sylius\Bundle\ApiBundle\Changer;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Changer\PaymentMethodChanger;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;

final class PaymentMethodChangerTest extends TestCase
{
    /** @var PaymentRepositoryInterface|MockObject */
    private MockObject $paymentRepositoryMock;

    /** @var PaymentMethodRepositoryInterface|MockObject */
    private MockObject $paymentMethodRepositoryMock;

    private PaymentMethodChanger $paymentMethodChanger;

    protected function setUp(): void
    {
        $this->paymentRepositoryMock = $this->createMock(PaymentRepositoryInterface::class);
        $this->paymentMethodRepositoryMock = $this->createMock(PaymentMethodRepositoryInterface::class);
        $this->paymentMethodChanger = new PaymentMethodChanger($this->paymentRepositoryMock, $this->paymentMethodRepositoryMock);
    }

    public function testThrowsAnExceptionIfPaymentMethodWithGivenCodeHasNotBeenFound(): void
    {
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->paymentMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'CASH_ON_DELIVERY_METHOD'])->willReturn(null);
        $orderMock->expects($this->once())->method('getId')->willReturn('100');
        $this->paymentRepositoryMock->expects($this->never())->method('findOneByOrderId')->with('123', '100');
        $this->expectException(InvalidArgumentException::class);
        $this->paymentMethodChanger->changePaymentMethod('CASH_ON_DELIVERY_METHOD', '123', $orderMock);
    }

    public function testThrowsAnExceptionIfPaymentWithGivenIdHasNotBeenFound(): void
    {
        /** @var PaymentMethodInterface|MockObject $paymentMethodMock */
        $paymentMethodMock = $this->createMock(PaymentMethodInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->paymentMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'CASH_ON_DELIVERY_METHOD'])->willReturn($paymentMethodMock);
        $orderMock->expects($this->once())->method('getId')->willReturn('444');
        $this->paymentRepositoryMock->expects($this->once())->method('findOneByOrderId')->with('123', '444')->willReturn(null);
        $orderMock->expects($this->never())->method('getState');
        $this->expectException(InvalidArgumentException::class);
        $this->paymentMethodChanger->changePaymentMethod('CASH_ON_DELIVERY_METHOD', '123', $orderMock);
    }

    public function testThrowsAnExceptionIfPaymentIsInDifferentStateThanNew(): void
    {
        /** @var PaymentMethodInterface|MockObject $paymentMethodMock */
        $paymentMethodMock = $this->createMock(PaymentMethodInterface::class);
        /** @var PaymentInterface|MockObject $paymentMock */
        $paymentMock = $this->createMock(PaymentInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->paymentMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'CASH_ON_DELIVERY_METHOD'])->willReturn($paymentMethodMock);
        $orderMock->expects($this->once())->method('getId')->willReturn('444');
        $this->paymentRepositoryMock->expects($this->once())->method('findOneByOrderId')->with('123', '444')->willReturn(null);
        $orderMock->expects($this->once())->method('getState')->willReturn(OrderInterface::STATE_FULFILLED);
        $paymentMock->expects($this->never())->method('setMethod')->with($paymentMethodMock);
        $this->expectException(InvalidArgumentException::class);
        $this->paymentMethodChanger->changePaymentMethod('CASH_ON_DELIVERY_METHOD', '123', $orderMock);
    }

    public function testChangesPaymentMethodToSpecifiedPayment(): void
    {
        /** @var PaymentMethodInterface|MockObject $paymentMethodMock */
        $paymentMethodMock = $this->createMock(PaymentMethodInterface::class);
        /** @var PaymentInterface|MockObject $paymentMock */
        $paymentMock = $this->createMock(PaymentInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->paymentMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'CASH_ON_DELIVERY_METHOD'])->willReturn($paymentMethodMock);
        $orderMock->expects($this->once())->method('getId')->willReturn('444');
        $this->paymentRepositoryMock->expects($this->once())->method('findOneByOrderId')->with('123', '444')->willReturn($paymentMock);
        $orderMock->expects($this->once())->method('getState')->willReturn(OrderInterface::STATE_NEW);
        $paymentMock->expects($this->once())->method('getState')->willReturn(PaymentInterface::STATE_NEW);
        $paymentMock->expects($this->once())->method('setMethod')->with($paymentMethodMock);
        $this->assertSame($orderMock, $this->paymentMethodChanger->changePaymentMethod('CASH_ON_DELIVERY_METHOD', '123', $orderMock));
    }
}
