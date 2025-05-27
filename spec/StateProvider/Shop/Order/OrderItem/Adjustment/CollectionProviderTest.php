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

namespace Tests\Sylius\Bundle\ApiBundle\StateProvider\Shop\Order\OrderItem\Adjustment;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\ApiBundle\StateProvider\Shop\Order\OrderItem\Adjustment\CollectionProvider;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderItem;
use Sylius\Component\Core\Repository\OrderItemRepositoryInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;

final class CollectionProviderTest extends TestCase
{
    /** @var OrderItemRepositoryInterface|MockObject */
    private MockObject $orderItemRepositoryMock;

    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionProviderMock;

    private CollectionProvider $collectionProvider;

    protected function setUp(): void
    {
        $this->orderItemRepositoryMock = $this->createMock(OrderItemRepositoryInterface::class);
        $this->sectionProviderMock = $this->createMock(SectionProviderInterface::class);
        $this->collectionProvider = new CollectionProvider($this->orderItemRepositoryMock, $this->sectionProviderMock);
    }

    public function testAStateProvider(): void
    {
        $this->assertInstanceOf(ProviderInterface::class, $this->collectionProvider);
    }

    public function testReturnsAnEmptyArrayWhenUriVariablesHaveNoId(): void
    {
        $operation = new GetCollection(class: AdjustmentInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->orderItemRepositoryMock->expects($this->never())->method('findOneByIdAndOrderTokenValue')->with($this->any());
        $this->assertSame([], $this->collectionProvider->provide($operation, ['tokenValue' => '42']));
    }

    public function testReturnsAnEmptyArrayWhenUriVariablesHaveNoTokenValue(): void
    {
        $operation = new GetCollection(class: AdjustmentInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->orderItemRepositoryMock->expects($this->never())->method('findOneByIdAndOrderTokenValue')->with($this->any());
        $this->assertSame([], $this->collectionProvider->provide($operation, ['id' => 42]));
    }

    public function testReturnsAnEmptyArrayWhenNoOrderItemCanBeFound(): void
    {
        $operation = new GetCollection(class: AdjustmentInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->orderItemRepositoryMock->expects($this->once())->method('findOneByIdAndOrderTokenValue')->with(42, 'token')->willReturn(null);
        $this->assertSame([], $this->collectionProvider->provide($operation, ['id' => '42', 'tokenValue' => 'token']));
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
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $requestMock->query = new InputBag(['type' => 'type']);
        $adjustments = new ArrayCollection([
            $firstAdjustmentMock,
            $secondAdjustmentMock,
        ]);
        $orderItemMock->expects($this->once())->method('getAdjustmentsRecursively')->with('type')->willReturn($adjustments);
        $this->orderItemRepositoryMock->expects($this->once())->method('findOneByIdAndOrderTokenValue')->with(42, 'token')->willReturn($orderItemMock);
        $this->assertSame($adjustments, $this->collectionProvider->provide($operation, ['id' => '42', 'tokenValue' => 'token'], ['request' => $requestMock]));
    }

    public function testThrowsAnExceptionWhenOperationClassIsNotAdjustment(): void
    {
        /** @var Operation|MockObject $operationMock */
        $operationMock = $this->createMock(Operation::class);
        $operationMock->expects($this->once())->method('getClass')->willReturn(stdClass::class);
        $this->expectException(InvalidArgumentException::class);
        $this->collectionProvider->provide($operationMock);
    }

    public function testThrowsAnExceptionWhenOperationIsNotGetCollection(): void
    {
        /** @var Operation|MockObject $operationMock */
        $operationMock = $this->createMock(Operation::class);
        $operationMock->expects($this->once())->method('getClass')->willReturn(AdjustmentInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->expectException(InvalidArgumentException::class);
        $this->collectionProvider->provide($operationMock);
    }

    public function testThrowsAnExceptionWhenOperationIsNotInShopApiSection(): void
    {
        $operation = new GetCollection(class: AdjustmentInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $this->expectException(InvalidArgumentException::class);
        $this->collectionProvider->provide($operation);
    }
}
