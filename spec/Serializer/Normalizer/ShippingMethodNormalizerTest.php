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

namespace Tests\Sylius\Bundle\ApiBundle\Serializer\Normalizer;

use ApiPlatform\Metadata\GetCollection;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\ApiBundle\Serializer\Normalizer\ShippingMethodNormalizer;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\ShipmentRepositoryInterface;
use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Shipping\Calculator\CalculatorInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ShippingMethodNormalizerTest extends TestCase
{
    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionProviderMock;

    /** @var OrderRepositoryInterface|MockObject */
    private MockObject $orderRepositoryMock;

    /** @var ShipmentRepositoryInterface|MockObject */
    private MockObject $shipmentRepositoryMock;

    /** @var ServiceRegistryInterface|MockObject */
    private MockObject $shippingCalculatorsMock;

    /** @var RequestStack|MockObject */
    private MockObject $requestStackMock;

    /** @var NormalizerInterface|MockObject */
    private MockObject $normalizerMock;

    private ShippingMethodNormalizer $shippingMethodNormalizer;

    protected function setUp(): void
    {
        $this->sectionProviderMock = $this->createMock(SectionProviderInterface::class);
        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);
        $this->shipmentRepositoryMock = $this->createMock(ShipmentRepositoryInterface::class);
        $this->shippingCalculatorsMock = $this->createMock(ServiceRegistryInterface::class);
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->normalizerMock = $this->createMock(NormalizerInterface::class);
        $this->shippingMethodNormalizer = new ShippingMethodNormalizer($this->sectionProviderMock, $this->orderRepositoryMock, $this->shipmentRepositoryMock, $this->shippingCalculatorsMock, $this->requestStackMock, ['sylius:shipping_method:index']);
        $this->setNormalizer($this->normalizerMock);
    }

    public function testSupportsOnlyShippingMethodInterfaceInShopSectionWithProperData(): void
    {
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertTrue($this->shippingMethodNormalizer
            ->supportsNormalization($shippingMethodMock, null, [
                'root_operation' => new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]),
                'groups' => ['sylius:shipping_method:index'],
            ]))
        ;
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertFalse($this->shippingMethodNormalizer
            ->supportsNormalization(new stdClass(), null, [
                'root_operation' => new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]),
                'groups' => ['sylius:shipping_method:index'],
            ]))
        ;
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $this->assertFalse($this->shippingMethodNormalizer
            ->supportsNormalization($shippingMethodMock, null, [
                'root_operation' => new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]),
                'groups' => ['sylius:shipping_method:index'],
            ]))
        ;
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertFalse($this->shippingMethodNormalizer
            ->supportsNormalization($shippingMethodMock, null, [
                'root_operation' => new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]),
                'groups' => ['sylius:shipping_method:show'],
            ]))
        ;
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertFalse($this->shippingMethodNormalizer->supportsNormalization($shippingMethodMock));
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertFalse($this->shippingMethodNormalizer
            ->supportsNormalization($shippingMethodMock, null, [
                'root_operation' => new GetCollection(uriVariables: ['tokenValue' => []]),
                'groups' => ['sylius:shipping_method:index'],
            ]))
        ;
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertFalse($this->shippingMethodNormalizer
            ->supportsNormalization($shippingMethodMock, null, [
                'root_operation' => new GetCollection(uriVariables: ['shipmentId' => []]),
                'groups' => ['sylius:shipping_method:index'],
            ]))
        ;
    }

    public function testDoesNotSupportIfTheNormalizerHasBeenAlreadyCalled(): void
    {
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertFalse($this->shippingMethodNormalizer
            ->supportsNormalization($shippingMethodMock, null, [
                'sylius_shipping_method_normalizer_already_called' => true,
                'root_operation' => new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]),
            ]))
        ;
    }

    public function testAddsCalculatedPriceOfShippingMethod(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var ShipmentInterface|MockObject $shipmentMock */
        $shipmentMock = $this->createMock(ShipmentInterface::class);
        /** @var CalculatorInterface|MockObject $calculatorMock */
        $calculatorMock = $this->createMock(CalculatorInterface::class);
        $operation = new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->requestStackMock->expects($this->once())->method('getCurrentRequest')->willReturn($requestMock);
        $requestMock->attributes = new ParameterBag(['tokenValue' => 'TOKEN', 'shipmentId' => '123']);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValueAndChannel')->with('TOKEN', $channelMock)->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getId')->willReturn('321');
        $this->shipmentRepositoryMock->expects($this->once())->method('findOneByOrderId')->with('123', '321')->willReturn($shipmentMock);
        $cartMock->expects($this->once())->method('hasShipment')->with($shipmentMock)->willReturn(true);
        $this->normalizerMock->expects($this->once())->method('normalize')->with($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'sylius_shipping_method_normalizer_already_called' => true,
            'groups' => ['sylius:shipping_method:index'],
        ])
            ->willReturn([])
        ;
        $shippingMethodMock->expects($this->once())->method('getCalculator')->willReturn('default_calculator');
        $shippingMethodMock->expects($this->once())->method('getConfiguration')->willReturn([]);
        $this->shippingCalculatorsMock->expects($this->once())->method('get')->with('default_calculator')->willReturn($calculatorMock);
        $calculatorMock->expects($this->once())->method('calculate')->with($shipmentMock, [])->willReturn(1000);
        $this->assertSame(['price' => 1000], $this->shippingMethodNormalizer
            ->normalize($shippingMethodMock, null, [
                'root_operation' => $operation,
                'sylius_api_channel' => $channelMock,
                'groups' => ['sylius:shipping_method:index'],
            ]))
        ;
    }

    public function testThrowsAnExceptionIfTheGivenResourceIsNotAnInstanceOfShippingMethodInterface(): void
    {
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $operation = new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]);
        $this->sectionProviderMock->expects($this->never())->method('getSection');
        $this->requestStackMock->expects($this->never())->method('getCurrentRequest');
        $this->normalizerMock->expects($this->never())->method('normalize')
        ;
        $this->expectException(InvalidArgumentException::class);
        $this->shippingMethodNormalizer->normalize(new stdClass(), null, [
            'root_operation' => new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]),
            'sylius_api_channel' => $channelMock,
            'groups' => ['sylius:shipping_method:index'],
        ]);
    }

    public function testThrowsAnExceptionIfSerializerHasAlreadyBeenCalled(): void
    {
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $operation = new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]);
        $this->sectionProviderMock->expects($this->never())->method('getSection');
        $this->requestStackMock->expects($this->never())->method('getCurrentRequest');
        $this->normalizerMock->expects($this->never())->method('normalize')->with($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'sylius_shipping_method_normalizer_already_called' => true,
            'groups' => ['sylius:shipping_method:index'],
        ])
        ;
        $this->expectException(InvalidArgumentException::class);
        $this->shippingMethodNormalizer->normalize($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'sylius_shipping_method_normalizer_already_called' => true,
            'groups' => ['sylius:shipping_method:index'],
        ]);
    }

    public function testThrowsAnExceptionIfItIsNotShopSection(): void
    {
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $operation = new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $this->requestStackMock->expects($this->never())->method('getCurrentRequest');
        $this->normalizerMock->expects($this->never())->method('normalize')->with($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'sylius_shipping_method_normalizer_already_called' => true,
            'groups' => ['sylius:shipping_method:index'],
        ])
        ;
        $this->expectException(InvalidArgumentException::class);
        $this->shippingMethodNormalizer->normalize($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'groups' => ['sylius:shipping_method:index'],
        ]);
    }

    public function testThrowsAnExceptionIfSerializationGroupIsNotSupported(): void
    {
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $operation = new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->requestStackMock->expects($this->never())->method('getCurrentRequest');
        $this->normalizerMock->expects($this->never())->method('normalize')->with($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'sylius_shipping_method_normalizer_already_called' => true,
            'groups' => ['sylius:shipping_method:shop'],
        ])
        ;
        $this->expectException(InvalidArgumentException::class);
        $this->shippingMethodNormalizer->normalize($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'groups' => ['sylius:shipping_method:shop'],
        ]);
    }

    public function testThrowsAnExceptionIfThereIsNoCartForGivenTokenValue(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $operation = new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->requestStackMock->expects($this->once())->method('getCurrentRequest')->willReturn($requestMock);
        $requestMock->attributes = new ParameterBag(['tokenValue' => 'TOKEN', 'shipmentId' => '123']);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValueAndChannel')->with('TOKEN', $channelMock)->willReturn(null);
        $this->normalizerMock->expects($this->never())->method('normalize')->with($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'sylius_shipping_method_normalizer_already_called' => true,
            'groups' => ['sylius:shipping_method:index'],
        ])
        ;
        $this->expectException(InvalidArgumentException::class);
        $this->shippingMethodNormalizer->normalize($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'groups' => ['sylius:shipping_method:index'],
        ]);
    }

    public function testThrowsAnExceptionIfThereIsNoShipmentForGivenIdAndCart(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        $operation = new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->requestStackMock->expects($this->once())->method('getCurrentRequest')->willReturn($requestMock);
        $requestMock->attributes = new ParameterBag(['tokenValue' => 'TOKEN', 'shipmentId' => '123']);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValueAndChannel')->with('TOKEN', $channelMock)->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getId')->willReturn('321');
        $this->shipmentRepositoryMock->expects($this->once())->method('findOneByOrderId')->with('123', '321')->willReturn(null);
        $this->normalizerMock->expects($this->never())->method('normalize')->with($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'sylius_shipping_method_normalizer_already_called' => true,
            'groups' => ['sylius:shipping_method:index'],
        ])
        ;
        $this->expectException(InvalidArgumentException::class);
        $this->shippingMethodNormalizer->normalize($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'groups' => ['sylius:shipping_method:index'],
        ]);
    }

    public function testThrowsAnExceptionIfShipmentDoesNotMatchForOrder(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var ShippingMethodInterface|MockObject $shippingMethodMock */
        $shippingMethodMock = $this->createMock(ShippingMethodInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var OrderInterface|MockObject $cartMock */
        $cartMock = $this->createMock(OrderInterface::class);
        /** @var ShipmentInterface|MockObject $shipmentMock */
        $shipmentMock = $this->createMock(ShipmentInterface::class);
        $operation = new GetCollection(uriVariables: ['tokenValue' => [], 'shipmentId' => []]);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->requestStackMock->expects($this->once())->method('getCurrentRequest')->willReturn($requestMock);
        $requestMock->attributes = new ParameterBag(['tokenValue' => 'TOKEN', 'shipmentId' => '123']);
        $this->orderRepositoryMock->expects($this->once())->method('findCartByTokenValueAndChannel')->with('TOKEN', $channelMock)->willReturn($cartMock);
        $cartMock->expects($this->once())->method('getId')->willReturn('321');
        $this->shipmentRepositoryMock->expects($this->once())->method('findOneByOrderId')->with('123', '321')->willReturn($shipmentMock);
        $cartMock->expects($this->once())->method('hasShipment')->with($shipmentMock)->willReturn(false);
        $this->normalizerMock->expects($this->once())->method('normalize')->with($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'sylius_shipping_method_normalizer_already_called' => true,
            'groups' => ['sylius:shipping_method:index'],
        ])
            ->willReturn([])
        ;
        $shippingMethodMock->expects($this->never())->method('getCalculator');
        $shippingMethodMock->expects($this->never())->method('getConfiguration');
        $this->expectException(InvalidArgumentException::class);
        $this->shippingMethodNormalizer->normalize($shippingMethodMock, null, [
            'root_operation' => $operation,
            'sylius_api_channel' => $channelMock,
            'groups' => ['sylius:shipping_method:index'],
        ]);
    }
}
