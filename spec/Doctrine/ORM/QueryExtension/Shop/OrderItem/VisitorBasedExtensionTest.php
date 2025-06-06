<?php

declare(strict_types=1);

namespace Tests\Sylius\Bundle\ApiBundle\Doctrine\ORM\QueryExtension\Shop\OrderItem;

use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\Expr\Andx;
use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Bundle\ApiBundle\Doctrine\ORM\QueryExtension\Shop\OrderItem\VisitorBasedExtension;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Resource\Model\ResourceInterface;

final class VisitorBasedExtensionTest extends TestCase
{
    private VisitorBasedExtension $extension;
    private SectionProviderInterface&MockObject $sectionProvider;
    private UserContextInterface&MockObject $userContext;

    protected function setUp(): void
    {
        $this->sectionProvider = $this->createMock(SectionProviderInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);
        $this->extension = new VisitorBasedExtension($this->sectionProvider, $this->userContext);
    }

    public function test_does_not_apply_conditions_to_collection_for_unsupported_resource(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $nameGenerator = $this->createMock(QueryNameGeneratorInterface::class);

        $this->userContext->expects($this->never())->method('getUser');
        $queryBuilder->expects($this->never())->method('getRootAliases');

        $this->extension->applyToCollection($queryBuilder, $nameGenerator, ResourceInterface::class, new Get());
    }

    public function test_does_not_apply_conditions_to_collection_for_admin_api_section(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $nameGenerator = $this->createMock(QueryNameGeneratorInterface::class);
        $section = $this->createMock(AdminApiSection::class);

        $this->sectionProvider->expects($this->once())->method('getSection')->willReturn($section);
        $this->userContext->expects($this->never())->method('getUser');
        $queryBuilder->expects($this->never())->method('getRootAliases');

        $this->extension->applyToCollection($queryBuilder, $nameGenerator, OrderInterface::class, new Get());
    }

    public function test_does_not_apply_conditions_to_collection_if_user_is_not_null(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $nameGenerator = $this->createMock(QueryNameGeneratorInterface::class);
        $section = $this->createMock(ShopApiSection::class);
        $user = $this->createMock(ShopUserInterface::class);

        $this->sectionProvider->expects($this->once())->method('getSection')->willReturn($section);
        $this->userContext->expects($this->once())->method('getUser')->willReturn($user);
        $queryBuilder->expects($this->never())->method('getRootAliases');

        $this->extension->applyToCollection($queryBuilder, $nameGenerator, OrderInterface::class, new Get());
    }

    public function test_applies_conditions_to_collection(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $nameGenerator = $this->createMock(QueryNameGeneratorInterface::class);
        $section = $this->createMock(ShopApiSection::class);
        $expr = $this->createMock(Expr::class);
        $exprEq = $this->createMock(Comparison::class);
        $exprAndx = $this->createMock(Andx::class);
        $exprOrx = $this->createMock(Orx::class);

        $this->sectionProvider->expects($this->once())->method('getSection')->willReturn($section);
        $this->userContext->expects($this->once())->method('getUser')->willReturn(null);

        $queryBuilder->expects($this->once())->method('getRootAliases')->willReturn(['o']);

        // Callbacks for sequential join alias and leftJoin calls
        $nameGenerator->expects($this->exactly(3))
            ->method('generateJoinAlias')
            ->with($this->logicalOr('order', 'customer', 'user'))
            ->willReturnCallback(fn($arg) => $arg);
        $nameGenerator->expects($this->once())
            ->method('generateParameterName')
            ->with('createdByGuest')
            ->willReturn('createdByGuest');

        $queryBuilder->expects($this->exactly(3))
            ->method('leftJoin')
            ->withConsecutive(
                ['o.order', 'order'],
                ['order.customer', 'customer'],
                ['customer.user', 'user']
            )
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())->method('expr')->willReturn($expr);

        $expr->expects($this->once())->method('isNull')->with('user')->willReturn('user IS NULL');
        $expr->expects($this->once())->method('isNull')->with('order.customer')->willReturn('order.customer IS NULL');
        $expr->expects($this->once())->method('isNotNull')->with('user')->willReturn('user IS NOT NULL');
        $expr->expects($this->once())->method('eq')->with('order.createdByGuest', ':createdByGuest')->willReturn($exprEq);
        $expr->expects($this->once())->method('andX')->with('user IS NOT NULL', $exprEq)->willReturn($exprAndx);
        $expr->expects($this->once())->method('orX')->with('user IS NULL', 'order.customer IS NULL', $exprAndx)->willReturn($exprOrx);

        $queryBuilder->expects($this->once())->method('andWhere')->with($exprOrx)->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())->method('setParameter')->with('createdByGuest', true)->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())->method('addOrderBy')->with('o.id', 'ASC')->willReturn($queryBuilder);

        $this->extension->applyToCollection($queryBuilder, $nameGenerator, OrderItemInterface::class, new Get());
    }

    public function test_does_not_apply_conditions_to_item_for_unsupported_resource(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $nameGenerator = $this->createMock(QueryNameGeneratorInterface::class);

        $this->userContext->expects($this->never())->method('getUser');
        $queryBuilder->expects($this->never())->method('getRootAliases');

        $this->extension->applyToItem($queryBuilder, $nameGenerator, ResourceInterface::class, [], new Get());
    }

    public function test_does_not_apply_conditions_to_item_for_admin_api_section(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $nameGenerator = $this->createMock(QueryNameGeneratorInterface::class);
        $section = $this->createMock(AdminApiSection::class);

        $this->sectionProvider->expects($this->once())->method('getSection')->willReturn($section);
        $this->userContext->expects($this->never())->method('getUser');
        $queryBuilder->expects($this->never())->method('getRootAliases');

        $this->extension->applyToItem($queryBuilder, $nameGenerator, OrderInterface::class, [], new Get());
    }

    public function test_does_not_apply_conditions_to_item_if_user_is_not_null(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $nameGenerator = $this->createMock(QueryNameGeneratorInterface::class);
        $section = $this->createMock(ShopApiSection::class);
        $user = $this->createMock(ShopUserInterface::class);

        $this->sectionProvider->expects($this->once())->method('getSection')->willReturn($section);
        $this->userContext->expects($this->once())->method('getUser')->willReturn($user);
        $queryBuilder->expects($this->never())->method('getRootAliases');

        $this->extension->applyToItem($queryBuilder, $nameGenerator, OrderInterface::class, [], new Get());
    }

    public function test_applies_conditions_to_item(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $nameGenerator = $this->createMock(QueryNameGeneratorInterface::class);
        $section = $this->createMock(ShopApiSection::class);
        $expr = $this->createMock(Expr::class);
        $exprEq = $this->createMock(Comparison::class);
        $exprAndx = $this->createMock(Andx::class);
        $exprOrx = $this->createMock(Orx::class);

        $this->sectionProvider->expects($this->once())->method('getSection')->willReturn($section);
        $this->userContext->expects($this->once())->method('getUser')->willReturn(null);

        $queryBuilder->expects($this->once())->method('getRootAliases')->willReturn(['o']);

        $nameGenerator->expects($this->exactly(3))
            ->method('generateJoinAlias')
            ->with($this->logicalOr('order', 'customer', 'user'))
            ->willReturnCallback(fn($arg) => $arg);
        $nameGenerator->expects($this->once())
            ->method('generateParameterName')
            ->with('createdByGuest')
            ->willReturn('createdByGuest');

        $queryBuilder->expects($this->exactly(3))
            ->method('leftJoin')
            ->withConsecutive(
                ['o.order', 'order'],
                ['order.customer', 'customer'],
                ['customer.user', 'user']
            )
            ->willReturn($queryBuilder);

        $queryBuilder->expects($this->once())->method('expr')->willReturn($expr);

        $expr->expects($this->once())->method('isNull')->with('user')->willReturn('user IS NULL');
        $expr->expects($this->once())->method('isNull')->with('order.customer')->willReturn('order.customer IS NULL');
        $expr->expects($this->once())->method('isNotNull')->with('user')->willReturn('user IS NOT NULL');
        $expr->expects($this->once())->method('eq')->with('order.createdByGuest', ':createdByGuest')->willReturn($exprEq);
        $expr->expects($this->once())->method('andX')->with('user IS NOT NULL', $exprEq)->willReturn($exprAndx);
        $expr->expects($this->once())->method('orX')->with('user IS NULL', 'order.customer IS NULL', $exprAndx)->willReturn($exprOrx);

        $queryBuilder->expects($this->once())->method('andWhere')->with($exprOrx)->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())->method('setParameter')->with('createdByGuest', true)->willReturn($queryBuilder);
        $queryBuilder->expects($this->once())->method('addOrderBy')->with('o.id', 'ASC')->willReturn($queryBuilder);

        $this->extension->applyToItem($queryBuilder, $nameGenerator, OrderItemInterface::class, [], new Get());
    }
}
