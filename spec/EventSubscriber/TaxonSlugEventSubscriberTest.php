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

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\EventSubscriber\TaxonSlugEventSubscriber;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Generator\TaxonSlugGeneratorInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class TaxonSlugEventSubscriberTest extends TestCase
{
    /** @var TaxonSlugGeneratorInterface|MockObject */
    private MockObject $taxonSlugGeneratorMock;

    private TaxonSlugEventSubscriber $taxonSlugEventSubscriber;

    protected function setUp(): void
    {
        $this->taxonSlugGeneratorMock = $this->createMock(TaxonSlugGeneratorInterface::class);
        $this->taxonSlugEventSubscriber = new TaxonSlugEventSubscriber($this->taxonSlugGeneratorMock);
    }

    public function testGeneratesSlugForTaxonWithNameAndEmptySlug(): void
    {
        /** @var TaxonInterface|MockObject $taxonMock */
        $taxonMock = $this->createMock(TaxonInterface::class);
        /** @var TaxonTranslationInterface|MockObject $taxonTranslationMock */
        $taxonTranslationMock = $this->createMock(TaxonTranslationInterface::class);
        /** @var HttpKernelInterface|MockObject $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())->method('getMethod')->willReturn(Request::METHOD_POST);
        $taxonMock->expects($this->once())->method('getTranslations')->willReturn(new ArrayCollection([$taxonTranslationMock]));
        $taxonTranslationMock->expects($this->once())->method('getSlug')->willReturn(null);
        $taxonTranslationMock->expects($this->atLeastOnce())->method('getName')->willReturn('PHP Mug');
        $taxonTranslationMock->expects($this->once())->method('getLocale')->willReturn('en_US');
        $this->taxonSlugGeneratorMock->expects($this->once())->method('generate')->with($taxonMock, 'en_US')->willReturn('php-mug');
        $taxonTranslationMock->expects($this->once())->method('setSlug')->with('php-mug');
        $this->taxonSlugEventSubscriber->generateSlug(new ViewEvent(
            $kernelMock,
            $requestMock,
            HttpKernelInterface::MAIN_REQUEST,
            $taxonMock,
        ));
    }

    public function testDoesNothingIfTheTaxonHasSlug(): void
    {
        /** @var TaxonInterface|MockObject $taxonMock */
        $taxonMock = $this->createMock(TaxonInterface::class);
        /** @var TaxonTranslationInterface|MockObject $taxonTranslationMock */
        $taxonTranslationMock = $this->createMock(TaxonTranslationInterface::class);
        /** @var HttpKernelInterface|MockObject $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())->method('getMethod')->willReturn(Request::METHOD_POST);
        $taxonMock->expects($this->once())->method('getTranslations')->willReturn(new ArrayCollection([$taxonTranslationMock]));
        $taxonTranslationMock->expects($this->atLeastOnce())->method('getSlug')->willReturn('php-mug');
        $taxonTranslationMock->expects($this->never())->method('getName');
        $this->taxonSlugGeneratorMock->expects($this->never())->method('generate');
        $taxonTranslationMock->expects($this->never())->method('setSlug');
        $this->taxonSlugEventSubscriber->generateSlug(new ViewEvent(
            $kernelMock,
            $requestMock,
            HttpKernelInterface::MAIN_REQUEST,
            $taxonMock,
        ));
    }

    public function testDoesNothingIfTheTaxonHasNoName(): void
    {
        /** @var TaxonInterface|MockObject $taxonMock */
        $taxonMock = $this->createMock(TaxonInterface::class);
        /** @var TaxonTranslationInterface|MockObject $taxonTranslationMock */
        $taxonTranslationMock = $this->createMock(TaxonTranslationInterface::class);
        /** @var HttpKernelInterface|MockObject $kernelMock */
        $kernelMock = $this->createMock(HttpKernelInterface::class);
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())->method('getMethod')->willReturn(Request::METHOD_POST);
        $taxonMock->expects($this->once())->method('getTranslations')->willReturn(new ArrayCollection([$taxonTranslationMock]));
        $taxonTranslationMock->expects($this->once())->method('getSlug')->willReturn(null);
        $taxonTranslationMock->expects($this->once())->method('getName')->willReturn(null);
        $this->taxonSlugGeneratorMock->expects($this->never())->method('generate');
        $taxonTranslationMock->expects($this->never())->method('setSlug');
        $this->taxonSlugEventSubscriber->generateSlug(new ViewEvent(
            $kernelMock,
            $requestMock,
            HttpKernelInterface::MAIN_REQUEST,
            $taxonMock,
        ));
    }
}
