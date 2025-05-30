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

namespace Tests\Sylius\Bundle\ApiBundle\StateProvider\Shop\Product\ProductAttributeValue;

use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ArrayIterator;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\ApiBundle\StateProvider\Shop\Product\ProductAttributeValue\CollectionProvider;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Locale\Provider\LocaleProviderInterface;
use Sylius\Component\Product\Model\ProductAttributeValue;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Product\Repository\ProductAttributeValueRepositoryInterface;

final class CollectionProviderTest extends TestCase
{
    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionProviderMock;

    /** @var ProductAttributeValueRepositoryInterface|MockObject */
    private MockObject $attributeValueRepositoryMock;

    /** @var LocaleContextInterface|MockObject */
    private MockObject $localeContextMock;

    /** @var LocaleProviderInterface|MockObject */
    private MockObject $localeProviderMock;

    /** @var QueryResultCollectionExtensionInterface|MockObject */
    private MockObject $extensionMock;

    private CollectionProvider $collectionProvider;

    protected function setUp(): void
    {
        $this->sectionProviderMock = $this->createMock(SectionProviderInterface::class);
        $this->attributeValueRepositoryMock = $this->createMock(ProductAttributeValueRepositoryInterface::class);
        $this->localeContextMock = $this->createMock(LocaleContextInterface::class);
        $this->localeProviderMock = $this->createMock(LocaleProviderInterface::class);
        $this->extensionMock = $this->createMock(QueryResultCollectionExtensionInterface::class);
        $defaultLocaleCode = 'en_US';
        $collectionExtensions = [$this->extensionMock];
        $this->collectionProvider = new CollectionProvider($collectionExtensions, $this->sectionProviderMock, $this->attributeValueRepositoryMock, $this->localeContextMock, $this->localeProviderMock, $defaultLocaleCode);
    }

    public function testImplementsProviderInterface(): void
    {
        $this->assertInstanceOf(ProviderInterface::class, $this->collectionProvider);
    }

    public function testProvidesProductAttributes(): void
    {
        /** @var QueryBuilder|MockObject $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $operation = new GetCollection(class: ProductAttributeValueInterface::class);
        $this->sectionProviderMock->expects(self::once())->method('getSection')->willReturn(new ShopApiSection());
        $this->localeContextMock->expects(self::once())->method('getLocaleCode')->willReturn('en_US');
        $this->localeProviderMock->expects(self::once())->method('getDefaultLocaleCode')->willReturn('en_US');
        $this->attributeValueRepositoryMock->expects(self::once())->method('createByProductCodeAndLocaleQueryBuilder')->with('PRODUCT_CODE', 'en_US', 'en_US', 'en_US')
            ->willReturn($queryBuilderMock);
        $this->extensionMock->expects(self::once())->method('applyToCollection')->with($queryBuilderMock, new QueryNameGenerator(), ProductAttributeValueInterface::class, $operation, []);
        $this->extensionMock->expects(self::once())->method('supportsResult')->with(ProductAttributeValueInterface::class, $operation)
            ->willReturn(true);
        $this->extensionMock->expects(self::once())->method('getResult')->with($queryBuilderMock)
            ->willReturn(new ArrayIterator([new ProductAttributeValue()]));
        $this->assertInstanceOf(ArrayIterator::class, $this->collectionProvider->provide($operation, ['code' => 'PRODUCT_CODE'], []));
    }

    public function testReturnsQueryResultWhenNoExtensionsSupportResult(): void
    {
        /** @var QueryBuilder|MockObject $queryBuilderMock */
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        /** @var Query|MockObject $queryMock */
        $queryMock = $this->createMock(Query::class);
        $operation = new GetCollection(class: ProductAttributeValueInterface::class);
        $this->sectionProviderMock->expects(self::once())->method('getSection')->willReturn(new ShopApiSection());
        $this->localeContextMock->expects(self::once())->method('getLocaleCode')->willReturn('en_US');
        $this->localeProviderMock->expects(self::once())->method('getDefaultLocaleCode')->willReturn('en_US');
        $this->attributeValueRepositoryMock->expects(self::once())->method('createByProductCodeAndLocaleQueryBuilder')->with('PRODUCT_CODE', 'en_US', 'en_US', 'en_US')
            ->willReturn($queryBuilderMock);
        $this->extensionMock->expects(self::once())->method('applyToCollection')->with($queryBuilderMock, new QueryNameGenerator(), ProductAttributeValueInterface::class, $operation, []);
        $this->extensionMock->expects(self::once())->method('supportsResult')->with(ProductAttributeValueInterface::class, $operation)
            ->willReturn(false);
        $queryBuilderMock->expects(self::once())->method('getQuery')->willReturn($queryMock);
        $queryMock->expects(self::once())->method('getResult')->willReturn([$productAttributeValue = new ProductAttributeValue()]);
        self::assertSame([$productAttributeValue], $this->collectionProvider->provide($operation, ['code' => 'PRODUCT_CODE'], []));
    }

    public function testThrowsAnExceptionWhenOperationClassIsNotProductAttributeValue(): void
    {
        /** @var Operation|MockObject $operationMock */
        $operationMock = $this->createMock(Operation::class);
        $operationMock->expects(self::once())->method('getClass')->willReturn(stdClass::class);
        $this->expectException(InvalidArgumentException::class);
        $this->collectionProvider->provide($operationMock);
    }

    public function testThrowsAnExceptionWhenOperationIsNotGetCollection(): void
    {
        /** @var Operation|MockObject $operationMock */
        $operationMock = $this->createMock(Operation::class);
        $operationMock->expects(self::once())->method('getClass')->willReturn(ProductAttributeValueInterface::class);
        $this->sectionProviderMock->expects(self::once())->method('getSection')->willReturn(new ShopApiSection());
        $this->expectException(InvalidArgumentException::class);
        $this->collectionProvider->provide($operationMock);
    }

    public function testThrowsAnExceptionWhenOperationIsNotInShopApiSection(): void
    {
        $operation = new GetCollection(class: ProductAttributeValueInterface::class);
        $this->sectionProviderMock->expects(self::once())->method('getSection')->willReturn(new AdminApiSection());
        $this->expectException(InvalidArgumentException::class);
        $this->collectionProvider->provide($operation);
    }
}
