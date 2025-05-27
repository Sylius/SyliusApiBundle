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

namespace Tests\Sylius\Bundle\ApiBundle\StateProvider\Common\Adjustment;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\State\ProviderInterface;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Sylius\Bundle\ApiBundle\StateProvider\Common\Adjustment\CollectionProvider;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderItem;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

final class CollectionProviderTest extends TestCase
{
    /** @var RepositoryInterface|MockObject */
    private MockObject $repositoryMock;

    private CollectionProvider $collectionProvider;

    private const IDENTIFIER = 'id';

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(RepositoryInterface::class);
        $this->repositoryMock->expects($this->once())->method('getClassName')->willReturn(OrderItem::class);
        $this->collectionProvider = new CollectionProvider($this->repositoryMock, self::IDENTIFIER);
    }

    public function testAStateProvider(): void
    {
        $this->assertInstanceOf(ProviderInterface::class, $this->collectionProvider);
    }

    public function testThrowsLogicExceptionWhenRepositoryIsNotForARecursiveAdjustmentsAwareResource(): void
    {
        $this->repositoryMock->expects($this->once())->method('getClassName')->willReturn(stdClass::class);
        $this->expectException(LogicException::class);
        $this->collectionProvider->instantiation();
    }

    public function testThrowsExceptionWhenIdentifierIsMissingFromUriVariables(): void
    {
        $operation = new GetCollection(class: AdjustmentInterface::class);
        $this->repositoryMock->expects($this->never())->method('findOneBy');
        $this->expectException(InvalidArgumentException::class);
        $this->collectionProvider->provide($operation, []);
    }

    public function testThrowsExceptionWhenResourceCannotBeFound(): void
    {
        $operation = new GetCollection(class: AdjustmentInterface::class);
        $this->repositoryMock->expects($this->once())->method('findOneBy')->with([self::IDENTIFIER => 1])->willReturn(null);
        $this->expectException(RuntimeException::class);
        $this->collectionProvider->provide($operation, [self::IDENTIFIER => 1]);
    }

    public function testReturnsAdjustmentsRecursively(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var OrderItem|MockObject $orderItemMock */
        $orderItemMock = $this->createMock(OrderItem::class);
        /** @var AdjustmentInterface|MockObject $firstAdjustmentMock */
        $firstAdjustmentMock = $this->createMock(AdjustmentInterface::class);
        /** @var AdjustmentInterface|MockObject $secondAdjustmentMock */
        $secondAdjustmentMock = $this->createMock(AdjustmentInterface::class);
        $operation = new GetCollection(class: AdjustmentInterface::class);
        $requestMock->query = new InputBag(['type' => 'type']);
        $adjustments = new ArrayCollection([
            $firstAdjustmentMock,
            $secondAdjustmentMock,
        ]);
        $orderItemMock->expects($this->once())->method('getAdjustmentsRecursively')->with('type')->willReturn($adjustments);
        $this->repositoryMock->expects($this->once())->method('findOneBy')->with([self::IDENTIFIER => 1])->willReturn($orderItemMock);
        $this->assertSame($adjustments, $this->collectionProvider->provide($operation, [self::IDENTIFIER => 1], ['request' => $requestMock]));
    }
}
