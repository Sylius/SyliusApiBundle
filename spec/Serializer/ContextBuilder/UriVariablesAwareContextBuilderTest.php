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

namespace Tests\Sylius\Bundle\ApiBundle\Serializer\ContextBuilder;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Link;
use ApiPlatform\State\SerializerContextBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\Attribute\OrderItemIdAware;
use Sylius\Bundle\ApiBundle\Attribute\OrderTokenValueAware;
use Sylius\Bundle\ApiBundle\Attribute\ShipmentIdAware;
use Sylius\Bundle\ApiBundle\Command\Cart\ChangeItemQuantityInCart;
use Sylius\Bundle\ApiBundle\Command\Checkout\ChooseShippingMethod;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Bundle\ApiBundle\Serializer\ContextBuilder\UriVariablesAwareContextBuilder;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Symfony\Component\HttpFoundation\Request;

final class UriVariablesAwareContextBuilderTest extends TestCase
{
    /** @var SerializerContextBuilderInterface|MockObject */
    private MockObject $decoratedContextBuilderMock;

    private UriVariablesAwareContextBuilder $uriVariablesAwareContextBuilder;

    protected function setUp(): void
    {
        $this->decoratedContextBuilderMock = $this->createMock(SerializerContextBuilderInterface::class);
        $this->uriVariablesAwareContextBuilder = new UriVariablesAwareContextBuilder($this->decoratedContextBuilderMock, ShipmentIdAware::class, 'shipmentId', ShipmentInterface::class);
    }

    public function testDoesNothingIfThereIsNoInputClass(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, [])
            ->willReturn([])
        ;
        $this->assertSame([], $this->uriVariablesAwareContextBuilder->createFromRequest($requestMock, true, []));
    }

    public function testDoesNothingIfInputClassIsNoSupportedAttribute(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, [])
            ->willReturn(['input' => ['class' => stdClass::class]])
        ;
        $this->assertSame(['input' => ['class' => stdClass::class]], $this->uriVariablesAwareContextBuilder
            ->createFromRequest($requestMock, true, []))
        ;
    }

    public function testDoesNothingIfThereIsNoUriVariable(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var HttpOperation|MockObject $operationMock */
        $operationMock = $this->createMock(HttpOperation::class);
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, ['operation' => $operationMock])
            ->willReturn(['input' => ['class' => ChooseShippingMethod::class]])
        ;
        $operationMock->expects($this->once())->method('getUriVariables')->willReturn([]);
        $this->assertSame(['input' => ['class' => ChooseShippingMethod::class]], $this->uriVariablesAwareContextBuilder
            ->createFromRequest($requestMock, true, ['operation' => $operationMock]))
        ;
    }

    public function testDoesNothingIfThereIsDifferentUriVariable(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var HttpOperation|MockObject $operationMock */
        $operationMock = $this->createMock(HttpOperation::class);
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, ['operation' => $operationMock])
            ->willReturn(['input' => ['class' => ChooseShippingMethod::class]])
        ;
        $uriVariable = new Link(fromClass: 'stdClass');
        $operationMock->expects($this->once())->method('getUriVariables')->willReturn([$uriVariable]);
        $this->assertSame(['input' => ['class' => ChooseShippingMethod::class]], $this->uriVariablesAwareContextBuilder
            ->createFromRequest($requestMock, true, ['operation' => $operationMock]))
        ;
    }

    public function testSetShipmentIdAsAConstructorArgument(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var HttpOperation|MockObject $operationMock */
        $operationMock = $this->createMock(HttpOperation::class);
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, ['operation' => $operationMock])
            ->willReturn(['input' => ['class' => ChooseShippingMethod::class], 'uri_variables' => ['shipmentId' => '123']])
        ;
        $uriVariable = new Link(fromClass: ShipmentInterface::class, parameterName: 'shipmentId');
        $operationMock->expects($this->atLeastOnce())->method('getUriVariables')->willReturn([$uriVariable]);
        $this->assertSame([
            'input' => ['class' => ChooseShippingMethod::class],
            'uri_variables' => ['shipmentId' => '123'],
            'default_constructor_arguments' => [
                ChooseShippingMethod::class => ['shipmentId' => '123'],
            ],
        ], $this->uriVariablesAwareContextBuilder
            ->createFromRequest($requestMock, true, ['operation' => $operationMock]))
        ;
    }

    public function testSetOrderTokenValueAsAConstructorArgument(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var HttpOperation|MockObject $operationMock */
        $operationMock = $this->createMock(HttpOperation::class);
        $this->uriVariablesAwareContextBuilder = new UriVariablesAwareContextBuilder($this->decoratedContextBuilderMock, OrderTokenValueAware::class, 'orderTokenValue', OrderInterface::class);
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, ['operation' => $operationMock])
            ->willReturn(['input' => ['class' => CompleteOrder::class], 'uri_variables' => ['orderToken' => 'token123']])
        ;
        $uriVariable = new Link(fromClass: OrderInterface::class, parameterName: 'orderToken');
        $operationMock->expects($this->atLeastOnce())->method('getUriVariables')->willReturn([$uriVariable]);
        $this->assertSame([
            'input' => ['class' => CompleteOrder::class],
            'uri_variables' => ['orderToken' => 'token123'],
            'default_constructor_arguments' => [
                CompleteOrder::class => ['orderTokenValue' => 'token123'],
            ],
        ], $this->uriVariablesAwareContextBuilder
            ->createFromRequest($requestMock, true, ['operation' => $operationMock]))
        ;
    }

    public function testSetOrderItemIdAsAConstructorArgument(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var HttpOperation|MockObject $operationMock */
        $operationMock = $this->createMock(HttpOperation::class);
        $this->uriVariablesAwareContextBuilder = new UriVariablesAwareContextBuilder($this->decoratedContextBuilderMock, OrderItemIdAware::class, 'orderItemId', OrderItemInterface::class);
        $this->decoratedContextBuilderMock->expects($this->once())->method('createFromRequest')->with($requestMock, true, ['operation' => $operationMock])
            ->willReturn(['input' => ['class' => ChangeItemQuantityInCart::class], 'uri_variables' => ['orderItemId' => '23']])
        ;
        $uriVariable = new Link(fromClass: OrderItemInterface::class, parameterName: 'orderItemId');
        $operationMock->expects($this->atLeastOnce())->method('getUriVariables')->willReturn([$uriVariable]);
        $this->assertSame([
            'input' => ['class' => ChangeItemQuantityInCart::class],
            'uri_variables' => ['orderItemId' => '23'],
            'default_constructor_arguments' => [
                ChangeItemQuantityInCart::class => ['orderItemId' => '23'],
            ],
        ], $this->uriVariablesAwareContextBuilder
            ->createFromRequest($requestMock, true, ['operation' => $operationMock]))
        ;
    }
}
