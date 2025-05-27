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

namespace Tests\Sylius\Bundle\ApiBundle\Applicator;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Sylius\Bundle\ApiBundle\Applicator\ArchivingPromotionApplicator;
use Sylius\Component\Core\Model\PromotionInterface;

final class ArchivingPromotionApplicatorTest extends TestCase
{
    /** @var ClockInterface|MockObject */
    private MockObject $clockMock;

    private ArchivingPromotionApplicator $archivingPromotionApplicator;

    protected function setUp(): void
    {
        $this->clockMock = $this->createMock(ClockInterface::class);
        $this->archivingPromotionApplicator = new ArchivingPromotionApplicator($this->clockMock);
    }

    public function testArchivesPromotion(): void
    {
        /** @var PromotionInterface|MockObject $promotionMock */
        $promotionMock = $this->createMock(PromotionInterface::class);
        $now = new DateTimeImmutable();
        $this->clockMock->expects($this->once())->method('now')->willReturn($now);
        $promotionMock->expects($this->once())->method('setArchivedAt')->with($now)->shouldBeCalledOnce();
        $this->assertSame($promotionMock, $this->archivingPromotionApplicator->archive($promotionMock));
    }

    public function testRestoresPromotion(): void
    {
        /** @var PromotionInterface|MockObject $promotionMock */
        $promotionMock = $this->createMock(PromotionInterface::class);
        $promotionMock->expects($this->once())->method('setArchivedAt')->with(null)->shouldBeCalledOnce();
        $this->assertSame($promotionMock, $this->archivingPromotionApplicator->restore($promotionMock));
    }
}
