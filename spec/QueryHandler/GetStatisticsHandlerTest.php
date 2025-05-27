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

namespace Tests\Sylius\Bundle\ApiBundle\QueryHandler;

use DateInterval;
use DatePeriod;
use DateTime;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Exception\ChannelNotFoundException;
use Sylius\Bundle\ApiBundle\Query\GetStatistics;
use Sylius\Bundle\ApiBundle\QueryHandler\GetStatisticsHandler;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Statistics\Provider\StatisticsProviderInterface;
use Sylius\Component\Core\Statistics\ValueObject\Statistics;

final class GetStatisticsHandlerTest extends TestCase
{
    /** @var StatisticsProviderInterface|MockObject */
    private MockObject $statisticsProviderMock;

    /** @var ChannelRepositoryInterface|MockObject */
    private MockObject $channelRepositoryMock;

    private GetStatisticsHandler $getStatisticsHandler;

    protected function setUp(): void
    {
        $this->statisticsProviderMock = $this->createMock(StatisticsProviderInterface::class);
        $this->channelRepositoryMock = $this->createMock(ChannelRepositoryInterface::class);
        $this->getStatisticsHandler = new GetStatisticsHandler($this->statisticsProviderMock, $this->channelRepositoryMock);
    }

    public function testGetsStatistics(): void
    {
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var GetStatistics|MockObject $queryMock */
        $queryMock = $this->createMock(GetStatistics::class);
        /** @var DatePeriod|MockObject $datePeriodMock */
        $datePeriodMock = $this->createMock(DatePeriod::class);
        /** @var Statistics|MockObject $statisticsMock */
        $statisticsMock = $this->createMock(Statistics::class);
        $queryMock->expects($this->once())->method('getChannelCode')->willReturn('CHANNEL_CODE');
        $queryMock->expects($this->once())->method('getDatePeriod')->willReturn($datePeriodMock);
        $queryMock->expects($this->once())->method('getIntervalType')->willReturn('day');
        $this->channelRepositoryMock->expects($this->once())->method('findOneByCode')->with('CHANNEL_CODE')->willReturn($channelMock);
        $this->statisticsProviderMock->expects($this->once())->method('provide')->with('day', $datePeriodMock, $channelMock)->willReturn($statisticsMock);
        $this->assertSame($statisticsMock, $this->getStatisticsHandler->__invoke($queryMock));
    }

    public function testThrowsChannelNotFoundExceptionWhenChannelIsNull(): void
    {
        $datePeriod = new DatePeriod(
            new DateTime('2022-01-01'),
            new DateInterval('P1D'),
            new DateTime('2022-12-31'),
        );
        $this->channelRepositoryMock->expects($this->once())->method('findOneByCode')->with('NON_EXISTING_CHANNEL_CODE')->willReturn(null);
        $this->expectException(ChannelNotFoundException::class);
        $this->getStatisticsHandler->__invoke(new GetStatistics('day', $datePeriod, 'NON_EXISTING_CHANNEL_CODE'));
    }
}
