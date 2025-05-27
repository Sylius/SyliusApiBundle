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

namespace Tests\Sylius\Bundle\ApiBundle\Doctrine\ORM\QueryExtension\Shop\OrderItem;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Bundle\ApiBundle\Doctrine\ORM\QueryExtension\Shop\OrderItem\ShopUserBasedExtension;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Resource\Model\ResourceInterface;

final class ShopUserBasedExtensionTest extends TestCase
{
    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionProviderMock;

    /** @var UserContextInterface|MockObject */
    private MockObject $userContextMock;

    private ShopUserBasedExtension $shopUserBasedExtension;

    protected function setUp(): void
    {
        $this->sectionProviderMock = $this->createMock(SectionProviderInterface::class);
        $this->userContextMock = $this->createMock(UserContextInterface::class);
        $this->shopUserBasedExtension = new ShopUserBasedExtension($this->sectionProviderMock, $this->userContextMock);
    }

    public function testDoesNotApplyConditionsToCollectionForUnsupportedResource(): void
    {
        /** @var QueryBuilder|MockObject $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        /** @var QueryNameGeneratorInterface|MockObject $queryNameGeneratorMock */
        $queryNameGeneratorMock = $this->createMock(QueryNameGeneratorInterface::class);
        $this->userContextMock->expects($this->never())->method('getUser');
        $queryBuilderMock->expects($this->never())->method('getRootAliases');
        $this->shopUserBasedExtension->applyToCollection($queryBuilderMock, $queryNameGeneratorMock, ResourceInterface::class, new Get());
    }

    public function testDoesNotApplyConditionsToCollectionForAdminApiSection(): void
    {
        /** @var QueryBuilder|MockObject $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        /** @var QueryNameGeneratorInterface|MockObject $queryNameGeneratorMock */
        $queryNameGeneratorMock = $this->createMock(QueryNameGeneratorInterface::class);
        /** @var AdminApiSection|MockObject $sectionMock */
        $sectionMock = $this->createMock(AdminApiSection::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $this->userContextMock->expects($this->never())->method('getUser');
        $queryBuilderMock->expects($this->never())->method('getRootAliases');
        $this->shopUserBasedExtension->applyToCollection($queryBuilderMock, $queryNameGeneratorMock, OrderInterface::class, new Get());
    }

    public function testDoesNotApplyConditionsToCollectionIfUserIsNull(): void
    {
        /** @var QueryBuilder|MockObject $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        /** @var QueryNameGeneratorInterface|MockObject $queryNameGeneratorMock */
        $queryNameGeneratorMock = $this->createMock(QueryNameGeneratorInterface::class);
        /** @var ShopApiSection|MockObject $sectionMock */
        $sectionMock = $this->createMock(ShopApiSection::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn(null);
        $queryBuilderMock->expects($this->never())->method('getRootAliases');
        $this->shopUserBasedExtension->applyToCollection($queryBuilderMock, $queryNameGeneratorMock, OrderInterface::class, new Get());
    }

    public function testDoesNotApplyConditionsToCollectionIfUserIsNotShopUser(): void
    {
        /** @var QueryBuilder|MockObject $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        /** @var QueryNameGeneratorInterface|MockObject $queryNameGeneratorMock */
        $queryNameGeneratorMock = $this->createMock(QueryNameGeneratorInterface::class);
        /** @var ShopApiSection|MockObject $sectionMock */
        $sectionMock = $this->createMock(ShopApiSection::class);
        /** @var AdminUserInterface|MockObject $userMock */
        $userMock = $this->createMock(AdminUserInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $queryBuilderMock->expects($this->never())->method('getRootAliases');
        $this->shopUserBasedExtension->applyToCollection($queryBuilderMock, $queryNameGeneratorMock, OrderInterface::class, new Get());
    }

    public function testAppliesConditionsToCollection(): void
    {
        /** @var QueryBuilder|MockObject $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        /** @var QueryNameGeneratorInterface|MockObject $queryNameGeneratorMock */
        $queryNameGeneratorMock = $this->createMock(QueryNameGeneratorInterface::class);
        /** @var ShopApiSection|MockObject $sectionMock */
        $sectionMock = $this->createMock(ShopApiSection::class);
        /** @var ShopUserInterface|MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var Expr|MockObject $exprMock */
        $exprMock = $this->createMock(Expr::class);
        /** @var Comparison|MockObject $exprEqMock */
        $exprEqMock = $this->createMock(Comparison::class);
        $userMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $queryBuilderMock->expects($this->once())->method('getRootAliases')->willReturn(['o']);
        $queryNameGeneratorMock->expects($this->once())->method('generateJoinAlias')->with('order')->willReturn('order');
        $queryNameGeneratorMock->expects($this->once())->method('generateParameterName')->with('customer')->willReturn('customer');
        $queryBuilderMock->expects($this->once())->method('leftJoin')->with('o.order', 'order')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->once())->method('expr')->willReturn($exprMock);
        $exprMock->expects($this->once())->method('eq')->with('order.customer', ':customer')->willReturn($exprEqMock);
        $queryBuilderMock->expects($this->once())->method('andWhere')->with($exprEqMock)->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->once())->method('setParameter')->with('customer', $customerMock)->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->once())->method('addOrderBy')->with('o.id', 'ASC')->willReturn($queryBuilderMock);
        $this->shopUserBasedExtension->applyToCollection($queryBuilderMock, $queryNameGeneratorMock, OrderItemInterface::class, new Get());
    }

    public function testDoesNotApplyConditionsToItemForUnsupportedResource(): void
    {
        /** @var QueryBuilder|MockObject $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        /** @var QueryNameGeneratorInterface|MockObject $queryNameGeneratorMock */
        $queryNameGeneratorMock = $this->createMock(QueryNameGeneratorInterface::class);
        $this->userContextMock->expects($this->never())->method('getUser');
        $queryBuilderMock->expects($this->never())->method('getRootAliases');
        $this->shopUserBasedExtension->applyToItem($queryBuilderMock, $queryNameGeneratorMock, ResourceInterface::class, [], new Get());
    }

    public function testDoesNotApplyConditionsToItemForAdminApiSection(): void
    {
        /** @var QueryBuilder|MockObject $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        /** @var QueryNameGeneratorInterface|MockObject $queryNameGeneratorMock */
        $queryNameGeneratorMock = $this->createMock(QueryNameGeneratorInterface::class);
        /** @var AdminApiSection|MockObject $sectionMock */
        $sectionMock = $this->createMock(AdminApiSection::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $this->userContextMock->expects($this->never())->method('getUser');
        $queryBuilderMock->expects($this->never())->method('getRootAliases');
        $this->shopUserBasedExtension->applyToItem($queryBuilderMock, $queryNameGeneratorMock, OrderInterface::class, [], new Get());
    }

    public function testDoesNotApplyConditionsToItemIfUserIsNull(): void
    {
        /** @var QueryBuilder|MockObject $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        /** @var QueryNameGeneratorInterface|MockObject $queryNameGeneratorMock */
        $queryNameGeneratorMock = $this->createMock(QueryNameGeneratorInterface::class);
        /** @var ShopApiSection|MockObject $sectionMock */
        $sectionMock = $this->createMock(ShopApiSection::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn(null);
        $queryBuilderMock->expects($this->never())->method('getRootAliases');
        $this->shopUserBasedExtension->applyToItem($queryBuilderMock, $queryNameGeneratorMock, OrderInterface::class, [], new Get());
    }

    public function testDoesNotApplyConditionsToItemIfUserIsNotShopUser(): void
    {
        /** @var QueryBuilder|MockObject $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        /** @var QueryNameGeneratorInterface|MockObject $queryNameGeneratorMock */
        $queryNameGeneratorMock = $this->createMock(QueryNameGeneratorInterface::class);
        /** @var ShopApiSection|MockObject $sectionMock */
        $sectionMock = $this->createMock(ShopApiSection::class);
        /** @var AdminUserInterface|MockObject $userMock */
        $userMock = $this->createMock(AdminUserInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $queryBuilderMock->expects($this->never())->method('getRootAliases');
        $this->shopUserBasedExtension->applyToItem($queryBuilderMock, $queryNameGeneratorMock, OrderInterface::class, [], new Get());
    }

    public function testAppliesConditionsToItem(): void
    {
        /** @var QueryBuilder|MockObject $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        /** @var QueryNameGeneratorInterface|MockObject $queryNameGeneratorMock */
        $queryNameGeneratorMock = $this->createMock(QueryNameGeneratorInterface::class);
        /** @var ShopApiSection|MockObject $sectionMock */
        $sectionMock = $this->createMock(ShopApiSection::class);
        /** @var ShopUserInterface|MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var Expr|MockObject $exprMock */
        $exprMock = $this->createMock(Expr::class);
        /** @var Comparison|MockObject $exprEqMock */
        $exprEqMock = $this->createMock(Comparison::class);
        $userMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn($sectionMock);
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $queryBuilderMock->expects($this->once())->method('getRootAliases')->willReturn(['o']);
        $queryNameGeneratorMock->expects($this->once())->method('generateJoinAlias')->with('order')->willReturn('order');
        $queryNameGeneratorMock->expects($this->once())->method('generateParameterName')->with('customer')->willReturn('customer');
        $queryBuilderMock->expects($this->once())->method('leftJoin')->with('o.order', 'order')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->once())->method('expr')->willReturn($exprMock);
        $exprMock->expects($this->once())->method('eq')->with('order.customer', ':customer')->willReturn($exprEqMock);
        $queryBuilderMock->expects($this->once())->method('andWhere')->with($exprEqMock)->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->once())->method('setParameter')->with('customer', $customerMock)->willReturn($queryBuilderMock);
        $queryBuilderMock->expects($this->once())->method('addOrderBy')->with('o.id', 'ASC')->willReturn($queryBuilderMock);
        $this->shopUserBasedExtension->applyToItem($queryBuilderMock, $queryNameGeneratorMock, OrderItemInterface::class, [], new Get());
    }
}
