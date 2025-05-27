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

namespace Tests\Sylius\Bundle\ApiBundle\StateProcessor\Admin\Country;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\StateProcessor\Admin\Country\PersistProcessor;
use Sylius\Component\Addressing\Checker\CountryProvincesDeletionCheckerInterface;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Core\Exception\ResourceDeleteException;

final class PersistProcessorTest extends TestCase
{
    /** @var ProcessorInterface|MockObject */
    private MockObject $persistProcessorMock;

    /** @var CountryProvincesDeletionCheckerInterface|MockObject */
    private MockObject $countryProvincesDeletionCheckerMock;

    private PersistProcessor $persistProcessor;

    protected function setUp(): void
    {
        $this->persistProcessorMock = $this->createMock(ProcessorInterface::class);
        $this->countryProvincesDeletionCheckerMock = $this->createMock(CountryProvincesDeletionCheckerInterface::class);
        $this->persistProcessor = new PersistProcessor($this->persistProcessorMock, $this->countryProvincesDeletionCheckerMock);
    }

    public function testThrowsAnExceptionIfObjectIsNotACountry(): void
    {
        /** @var HttpOperation|MockObject $operationMock */
        $operationMock = $this->createMock(HttpOperation::class);
        $this->countryProvincesDeletionCheckerMock->expects($this->never())->method('isDeletable');
        $this->persistProcessorMock->expects($this->never())->method('process')->with($this->any());
        $this->expectException(InvalidArgumentException::class);
        $this->persistProcessor->process(new stdClass(), $operationMock, [], []);
    }

    public function testUsesDecoratedDataPersisterToPersistCountry(): void
    {
        /** @var CountryInterface|MockObject $countryMock */
        $countryMock = $this->createMock(CountryInterface::class);
        $operation = new Post();
        $uriVariables = [];
        $context = [];
        $this->countryProvincesDeletionCheckerMock->expects($this->once())->method('isDeletable')->with($countryMock)->willReturn(true);
        $this->persistProcessorMock->expects($this->once())->method('process')->with($countryMock, $operation, $uriVariables, $context)->willReturn($countryMock);
        $this->assertSame($countryMock, $this->persistProcessor->process($countryMock, $operation, $uriVariables, $context));
    }

    public function testThrowsAnErrorIfTheProvinceWithinACountryIsInUse(): void
    {
        /** @var CountryInterface|MockObject $countryMock */
        $countryMock = $this->createMock(CountryInterface::class);
        /** @var HttpOperation|MockObject $operationMock */
        $operationMock = $this->createMock(HttpOperation::class);
        $uriVariables = [];
        $context = [];
        $this->countryProvincesDeletionCheckerMock->expects($this->once())->method('isDeletable')->with($countryMock)->willReturn(false);
        $this->persistProcessorMock->expects($this->never())->method('process')->with($this->any());
        $this->expectException(ResourceDeleteException::class);
        $this->persistProcessor->process($countryMock, $operationMock, $uriVariables, $context);
    }
}
