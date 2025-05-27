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

namespace Tests\Sylius\Bundle\ApiBundle\EventSubscriber;

use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\EventSubscriber\TaxonDeletionEventSubscriber;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Exception\ResourceDeleteException;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Promotion\Checker\TaxonInPromotionRuleCheckerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class TaxonDeletionEventSubscriberTest extends TestCase
{
    /** @var ChannelRepositoryInterface|MockObject */
    private MockObject $channelRepositoryMock;

    /** @var TaxonInPromotionRuleCheckerInterface|MockObject */
    private MockObject $taxonInPromotionRuleCheckerMock;

    private TaxonDeletionEventSubscriber $taxonDeletionEventSubscriber;

    protected function setUp(): void
    {
        $this->channelRepositoryMock = $this->createMock(ChannelRepositoryInterface::class);
        $this->taxonInPromotionRuleCheckerMock = $this->createMock(TaxonInPromotionRuleCheckerInterface::class);
        $this->taxonDeletionEventSubscriber = new TaxonDeletionEventSubscriber($this->channelRepositoryMock, $this->taxonInPromotionRuleCheckerMock);
    }

    public function testAllowsToRemoveTaxonIfAnyChannelHasNotItAsAMenuTaxon(): void
    {
        /** @var TaxonInterface|MockObject $taxonMock */
        $taxonMock = $this->createMock(TaxonInterface::class);
        /** @var HttpKernelInterface|MockObject $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())->method('getMethod')->willReturn(Request::METHOD_DELETE);
        $taxonMock->expects($this->once())->method('getCode')->willReturn('WATCHES');
        $this->channelRepositoryMock->expects($this->once())->method('findOneBy')->with(['menuTaxon' => $taxonMock])->willReturn(null);
        $this->taxonDeletionEventSubscriber->protectFromRemovingMenuTaxon(new ViewEvent(
            $kernelMock,
            $requestMock,
            HttpKernelInterface::MAIN_REQUEST,
            $taxonMock,
        ));
    }

    public function testDoesNothingAfterWritingOtherEntity(): void
    {
        /** @var HttpKernelInterface|MockObject $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())->method('getMethod')->willReturn(Request::METHOD_DELETE);
        $this->taxonDeletionEventSubscriber->protectFromRemovingMenuTaxon(new ViewEvent(
            $kernelMock,
            $requestMock,
            HttpKernelInterface::MAIN_REQUEST,
            new stdClass(),
        ));
    }

    public function testThrowsAnExceptionIfASubjectIsMenuTaxon(): void
    {
        /** @var TaxonInterface|MockObject $taxonMock */
        $taxonMock = $this->createMock(TaxonInterface::class);
        /** @var HttpKernelInterface|MockObject $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $requestMock->expects($this->once())->method('getMethod')->willReturn(Request::METHOD_DELETE);
        $taxonMock->expects($this->once())->method('getCode')->willReturn('WATCHES');
        $this->channelRepositoryMock->expects($this->once())->method('findOneBy')->with(['menuTaxon' => $taxonMock])->willReturn($channelMock);
        $this->expectException(Exception::class);
        $this->taxonDeletionEventSubscriber->protectFromRemovingMenuTaxon(new ViewEvent(
            $kernelMock,
            $requestMock,
            HttpKernelInterface::MAIN_REQUEST,
            $taxonMock,
        ));
    }

    public function testDoesNotThrowExceptionWhenTaxonIsNotBeingDeleted(): void
    {
        /** @var TaxonInterface|MockObject $taxonMock */
        $taxonMock = $this->createMock(TaxonInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var HttpKernelInterface|MockObject $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $requestMock->expects($this->once())->method('getMethod')->willReturn(Request::METHOD_POST);
        $event = new ViewEvent(
            $kernelMock,
            $requestMock,
            HttpKernelInterface::MAIN_REQUEST,
            $taxonMock,
        );
        $this->taxonInPromotionRuleCheckerMock->expects($this->never())->method('isInUse')->with($taxonMock);
        /** should not throw exception */
        $this->taxonDeletionEventSubscriber->protectFromRemovingTaxonInUseByPromotionRule($event);
    }

    public function testDoesNotThrowExceptionWhenTaxonIsNotInUseByAPromotionRule(): void
    {
        /** @var TaxonInterface|MockObject $taxonMock */
        $taxonMock = $this->createMock(TaxonInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var HttpKernelInterface|MockObject $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $requestMock->expects($this->once())->method('getMethod')->willReturn(Request::METHOD_POST);
        $event = new ViewEvent(
            $kernelMock,
            $requestMock,
            HttpKernelInterface::MAIN_REQUEST,
            $taxonMock,
        );
        $this->taxonInPromotionRuleCheckerMock->expects($this->once())->method('isInUse')->with($taxonMock)->willReturn(false);
        /** should not throw exception */
        $this->taxonDeletionEventSubscriber->protectFromRemovingTaxonInUseByPromotionRule($event);
    }

    public function testThrowsAnExceptionWhenTryingToDeleteTaxonThatIsInUseByAPromotionRule(): void
    {
        /** @var TaxonInterface|MockObject $taxonMock */
        $taxonMock = $this->createMock(TaxonInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var HttpKernelInterface|MockObject $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        $requestMock->expects($this->once())->method('getMethod')->willReturn(Request::METHOD_DELETE);
        $event = new ViewEvent(
            $kernelMock,
            $requestMock,
            HttpKernelInterface::MAIN_REQUEST,
            $taxonMock,
        );
        $this->taxonInPromotionRuleCheckerMock->expects($this->once())->method('isInUse')->with($taxonMock)->willReturn(true);
        $this->expectException(ResourceDeleteException::class);
        $this->taxonDeletionEventSubscriber->protectFromRemovingTaxonInUseByPromotionRule($event);
    }
}
