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

namespace Tests\Sylius\Bundle\ApiBundle\Resolver;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Bundle\ApiBundle\Resolver\UriTemplateParentResourceResolver;
use RuntimeException;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Sylius\Bundle\ApiBundle\Resolver\UriTemplateParentResourceResolverInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Resource\Model\ResourceInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UriTemplateParentResourceResolverTest extends TestCase
{
    /** @var EntityManagerInterface|MockObject */
    private MockObject $entityManagerMock;
    private UriTemplateParentResourceResolver $uriTemplateParentResourceResolver;
    protected function setUp(): void
    {
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->uriTemplateParentResourceResolver = new UriTemplateParentResourceResolver($this->entityManagerMock);
    }

    public function testImpliesUriTemplateParentResourceResolverInterface(): void
    {
        $this->assertInstanceOf(UriTemplateParentResourceResolverInterface::class, $this->uriTemplateParentResourceResolver);
    }

    public function testThrowsAnExceptionIfNoUriVariablesArePassed(): void
    {
        /** @var ResourceInterface|MockObject $itemMock */
        $itemMock = $this->createMock(ResourceInterface::class);
        $this->entityManagerMock->expects($this->never())->method('getRepository');
        $this->expectException(RuntimeException::class);
        $this->uriTemplateParentResourceResolver->resolve($itemMock, new Post(), [], []);
    }

    public function testThrowsAnExceptionIfAnyUriVariableDoesNotMatch(): void
    {
        /** @var ResourceInterface|MockObject $itemMock */
        $itemMock = $this->createMock(ResourceInterface::class);
        /** @var ResourceInterface|MockObject $parentItemMock */
        $parentItemMock = $this->createMock(ResourceInterface::class);
        $this->entityManagerMock->expects($this->never())->method('getRepository');
        $operation = new Post(uriVariables: [
            'variable' => new Link(fromClass: $parentItemMock::class),
        ]);
        $this->expectException(RuntimeException::class);
        $this->uriTemplateParentResourceResolver->resolve($itemMock, $operation, ['uri_variables' => ['variable' => 'value']], []);
    }

    public function testThrowsAnExceptionIfUriVariableClassIsNotDefined(): void
    {
        /** @var ResourceInterface|MockObject $itemMock */
        $itemMock = $this->createMock(ResourceInterface::class);
        $this->entityManagerMock->expects($this->never())->method('getRepository');
        $operation = new Post(uriVariables: [
            'variable' => new Link(),
        ]);
        $this->expectException(RuntimeException::class);
        $this->uriTemplateParentResourceResolver->resolve($itemMock, $operation, ['uri_variables' => ['variable' => 'value']], []);
    }

    public function testThrowsAnExceptionIfParentResourceIsNotFound(): void
    {
        /** @var ResourceInterface|MockObject $itemMock */
        $itemMock = $this->createMock(ResourceInterface::class);
        /** @var UnitOfWork|MockObject $unitOfWorkMock */
        $unitOfWorkMock = $this->createMock(UnitOfWork::class);
        /** @var EntityPersister|MockObject $entityPersisterMock */
        $entityPersisterMock = $this->createMock(EntityPersister::class);
        $parentItem = final  extends TestCaseclass() implements ResourceInterface {
            public function testGetId(): void
            {
                return null;
            }
        };
        $operation = new Post(uriVariables: [
            'variable' => new Link(parameterName: 'variable', fromClass: $parentItem::class),
        ]);
        $repository = new EntityRepository($this->entityManagerMock, new ClassMetadata($parentItem::class));
        $this->entityManagerMock->expects($this->once())->method('getUnitOfWork')->willReturn($unitOfWorkMock);
        $this->entityManagerMock->expects($this->once())->method('getRepository')->with($parentItem::class)->willReturn($repository);
        $unitOfWorkMock->expects($this->once())->method('getEntityPersister')->with($parentItem::class)->willReturn($entityPersisterMock);
        $entityPersisterMock->expects($this->once())->method('load')->with(['code' => 'value'], null, null, [], null, 1, null)->willReturn(null);
        $this->expectException(NotFoundHttpException::class);
        $this->uriTemplateParentResourceResolver->resolve($itemMock, $operation, ['uri_variables' => ['variable' => 'value']], []);
    }

    public function testResolvesParentResource(): void
    {
        /** @var ResourceInterface|MockObject $itemMock */
        $itemMock = $this->createMock(ResourceInterface::class);
        /** @var ResourceInterface|MockObject $parentItemMock */
        $parentItemMock = $this->createMock(ResourceInterface::class);
        /** @var UnitOfWork|MockObject $unitOfWorkMock */
        $unitOfWorkMock = $this->createMock(UnitOfWork::class);
        /** @var EntityPersister|MockObject $entityPersisterMock */
        $entityPersisterMock = $this->createMock(EntityPersister::class);
        $repository = new EntityRepository($this->entityManagerMock, new ClassMetadata($parentItemMock::class));
        $this->entityManagerMock->expects($this->once())->method('getUnitOfWork')->willReturn($unitOfWorkMock);
        $this->entityManagerMock->expects($this->once())->method('getRepository')->with($parentItemMock::class)->willReturn($repository);
        $unitOfWorkMock->expects($this->once())->method('getEntityPersister')->with($parentItemMock::class)->willReturn($entityPersisterMock);
        $entityPersisterMock->expects($this->once())->method('load')->with(['code' => 'value'], null, null, [], null, 1, null)->willReturn($parentItemMock);
        $operation = new Post(uriVariables: [
            'variable' => new Link(parameterName: 'variable', fromClass: $parentItemMock::class),
        ]);
        $this->assertSame($parentItemMock, $this->uriTemplateParentResourceResolver->resolve($itemMock, $operation, ['uri_variables' => ['variable' => 'value']]));
    }
}
