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

namespace Tests\Sylius\Bundle\ApiBundle\StateProvider\Shop\Payment\PaymentRequest;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProviderInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\ApiBundle\StateProvider\Shop\Payment\PaymentRequest\ItemProvider;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Bundle\PaymentBundle\Checker\FinalizedPaymentRequestCheckerInterface;
use Sylius\Component\Payment\Model\PaymentRequestInterface;
use Sylius\Component\Payment\Repository\PaymentRequestRepositoryInterface;

final class ItemProviderTest extends TestCase
{
    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionProviderMock;

    /** @var PaymentRequestRepositoryInterface|MockObject */
    private MockObject $paymentRequestRepositoryMock;

    /** @var FinalizedPaymentRequestCheckerInterface|MockObject */
    private MockObject $finalizedPaymentRequestCheckerMock;

    private ItemProvider $itemProvider;

    protected function setUp(): void
    {
        $this->sectionProviderMock = $this->createMock(SectionProviderInterface::class);
        $this->paymentRequestRepositoryMock = $this->createMock(PaymentRequestRepositoryInterface::class);
        $this->finalizedPaymentRequestCheckerMock = $this->createMock(FinalizedPaymentRequestCheckerInterface::class);
        $this->itemProvider = new ItemProvider($this->sectionProviderMock, $this->paymentRequestRepositoryMock, $this->finalizedPaymentRequestCheckerMock);
    }

    public function testAStateProvider(): void
    {
        $this->assertInstanceOf(ProviderInterface::class, $this->itemProvider);
    }

    public function testThrowsAnExceptionIfOperationClassIsNotPayment(): void
    {
        /** @var Operation|MockObject $operationMock */
        $operationMock = $this->createMock(Operation::class);
        $operationMock->expects(self::once())->method('getClass')->willReturn(stdClass::class);
        $this->expectException(InvalidArgumentException::class);
        $this->itemProvider->provide($operationMock);
    }

    public function testThrowsAnExceptionIfOperationIsNotPut(): void
    {
        /** @var Operation|MockObject $operationMock */
        $operationMock = $this->createMock(Operation::class);
        $operationMock->expects(self::once())->method('getClass')->willReturn(PaymentRequestInterface::class);
        $this->expectException(InvalidArgumentException::class);
        $this->itemProvider->provide($operationMock);
    }

    public function testThrowsAnExceptionIfSectionIsNotShopApiSection(): void
    {
        $operation = new Put(class: PaymentRequestInterface::class, name: 'put');
        $this->sectionProviderMock->expects(self::once())->method('getSection')->willReturn(new AdminApiSection());
        $this->expectException(InvalidArgumentException::class);
        $this->itemProvider->provide($operation, [], []);
    }

    public function testReturnsNothingIfPaymentRequestIsNotFound(): void
    {
        $hash = 'hash';
        $operation = new Put(class: PaymentRequestInterface::class, name: 'put');
        $this->sectionProviderMock->expects(self::once())->method('getSection')->willReturn(new ShopApiSection());
        $this->paymentRequestRepositoryMock->expects(self::once())->method('find')->with($hash)->willReturn(null);
        $this->finalizedPaymentRequestCheckerMock->expects(self::never())->method('isFinal');
        $this->assertNull($this->itemProvider->provide($operation, ['hash' => $hash], []));
    }

    public function testReturnsNothingIfPaymentRequestIsInFinalState(): void
    {
        /** @var PaymentRequestInterface|MockObject $paymentRequestMock */
        $paymentRequestMock = $this->createMock(PaymentRequestInterface::class);
        $hash = 'hash';
        $operation = new Put(class: PaymentRequestInterface::class, name: 'put');
        $this->sectionProviderMock->expects(self::once())->method('getSection')->willReturn(new ShopApiSection());
        $this->paymentRequestRepositoryMock->expects(self::once())->method('find')->with($hash)->willReturn($paymentRequestMock);
        $this->finalizedPaymentRequestCheckerMock->expects(self::once())->method('isFinal')->with($paymentRequestMock)->willReturn(true);
        $this->assertNull($this->itemProvider->provide($operation, ['hash' => $hash], []));
    }

    public function testReturnsPaymentRequestByHash(): void
    {
        /** @var PaymentRequestInterface|MockObject $paymentRequestMock */
        $paymentRequestMock = $this->createMock(PaymentRequestInterface::class);
        $hash = 'hash';
        $operation = new Put(class: PaymentRequestInterface::class, name: 'put');
        $this->sectionProviderMock->expects(self::once())->method('getSection')->willReturn(new ShopApiSection());
        $this->paymentRequestRepositoryMock->expects(self::once())->method('find')->with($hash)->willReturn($paymentRequestMock);
        $this->finalizedPaymentRequestCheckerMock->expects(self::once())->method('isFinal')->with($paymentRequestMock)->willReturn(false);
        self::assertSame($paymentRequestMock, $this->itemProvider->provide($operation, ['hash' => $hash], []));
    }
}
