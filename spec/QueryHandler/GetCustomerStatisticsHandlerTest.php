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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Query\GetCustomerStatistics;
use Sylius\Bundle\ApiBundle\QueryHandler\GetCustomerStatisticsHandler;
use Sylius\Component\Core\Customer\Statistics\CustomerStatistics;
use Sylius\Component\Core\Customer\Statistics\CustomerStatisticsProviderInterface;
use Sylius\Component\Core\Exception\CustomerNotFoundException;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;

final class GetCustomerStatisticsHandlerTest extends TestCase
{
    /** @var CustomerRepositoryInterface|MockObject */
    private MockObject $customerRepositoryMock;

    /** @var CustomerStatisticsProviderInterface|MockObject */
    private MockObject $customerStatisticsProviderMock;

    private GetCustomerStatisticsHandler $getCustomerStatisticsHandler;

    protected function setUp(): void
    {
        $this->customerRepositoryMock = $this->createMock(CustomerRepositoryInterface::class);
        $this->customerStatisticsProviderMock = $this->createMock(CustomerStatisticsProviderInterface::class);
        $this->getCustomerStatisticsHandler = new GetCustomerStatisticsHandler($this->customerRepositoryMock, $this->customerStatisticsProviderMock);
    }

    public function testReturnsStatisticsForAGivenCustomer(): void
    {
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        $customerStatistics = new CustomerStatistics([]);

        $this->customerRepositoryMock
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($customerMock);

        $this->customerStatisticsProviderMock
            ->expects($this->once())
            ->method('getCustomerStatistics')
            ->with($customerMock)
            ->willReturn($customerStatistics);

        $query = new GetCustomerStatistics(1);

        // This line needs to be fixed - call the handler instead of $this
        $result = $this->getCustomerStatisticsHandler->__invoke($query);
        $this->assertSame($customerStatistics, $result);
    }

    public function testThrowsAnExceptionWhenCustomerWithAGivenIdDoesntExist(): void
    {
        $this->customerRepositoryMock->expects($this->once())->method('find')->with(1)->willReturn(null);
        $query = new GetCustomerStatistics(1);
        $this->expectException(CustomerNotFoundException::class);
        $this->getCustomerStatisticsHandler->__invoke($query);
    }
}
