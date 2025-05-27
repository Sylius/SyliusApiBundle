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

namespace Tests\Sylius\Bundle\ApiBundle\Serializer\Denormalizer;

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\Serializer\Denormalizer\ChannelDenormalizer;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\ChannelPriceHistoryConfigInterface;
use Sylius\Component\Core\Model\ShopBillingDataInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ChannelDenormalizerTest extends TestCase
{
    /** @var FactoryInterface|MockObject */
    private MockObject $configFactoryMock;

    /** @var FactoryInterface|MockObject */
    private MockObject $shopBillingDataFactoryMock;

    private ChannelDenormalizer $channelDenormalizer;

    private const ALREADY_CALLED = 'sylius_channel_denormalizer_already_called';

    protected function setUp(): void
    {
        $this->configFactoryMock = $this->createMock(FactoryInterface::class);
        $this->shopBillingDataFactoryMock = $this->createMock(FactoryInterface::class);
        $this->channelDenormalizer = new ChannelDenormalizer($this->configFactoryMock, $this->shopBillingDataFactoryMock);
    }

    public function testDoesNotSupportDenormalizationWhenTheDenormalizerHasAlreadyBeenCalled(): void
    {
        $this->assertFalse($this->channelDenormalizer->supportsDenormalization([], ChannelInterface::class, context: [self::ALREADY_CALLED => true]));
    }

    public function testDoesNotSupportDenormalizationWhenDataIsNotAnArray(): void
    {
        $this->assertFalse($this->channelDenormalizer->supportsDenormalization('string', ChannelInterface::class));
    }

    public function testDoesNotSupportDenormalizationWhenTypeIsNotAChannel(): void
    {
        $this->assertFalse($this->channelDenormalizer->supportsDenormalization([], 'string'));
    }

    public function testThrowsAnExceptionWhenDenormalizingAnObjectThatIsNotAChannel(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        $this->channelDenormalizer->setDenormalizer($denormalizerMock);
        $denormalizerMock->expects($this->once())->method('denormalize')->with([], 'string', null, [self::ALREADY_CALLED => true])->willReturn(new stdClass());
        $this->expectException(InvalidArgumentException::class);
        $this->channelDenormalizer->denormalize([], 'string');
    }

    public function testReturnsChannelAsIsWhenShopBillingDataAndChannelPriceHistoryConfigHasAlreadyBeenSet(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        /** @var ShopBillingDataInterface|MockObject $shopBillingDataMock */
        $shopBillingDataMock = $this->createMock(ShopBillingDataInterface::class);
        /** @var ChannelPriceHistoryConfigInterface|MockObject $configMock */
        $configMock = $this->createMock(ChannelPriceHistoryConfigInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $this->channelDenormalizer->setDenormalizer($denormalizerMock);
        $channelMock->expects($this->once())->method('getChannelPriceHistoryConfig')->willReturn($configMock);
        $channelMock->expects($this->once())->method('getShopBillingData')->willReturn($shopBillingDataMock);
        $channelMock->expects($this->never())->method('setChannelPriceHistoryConfig');
        $this->configFactoryMock->expects($this->never())->method('createNew');
        $denormalizerMock->expects($this->once())->method('denormalize')->with([], ChannelInterface::class, null, [self::ALREADY_CALLED => true])->willReturn($channelMock);
        $this->assertSame($channelMock, $this->channelDenormalizer->denormalize([], ChannelInterface::class));
    }

    public function testAddsANewChannelPriceHistoryConfigWhenChannelHasNone(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        /** @var ShopBillingDataInterface|MockObject $shopBillingDataMock */
        $shopBillingDataMock = $this->createMock(ShopBillingDataInterface::class);
        /** @var ChannelPriceHistoryConfigInterface|MockObject $configMock */
        $configMock = $this->createMock(ChannelPriceHistoryConfigInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $this->channelDenormalizer->setDenormalizer($denormalizerMock);
        $channelMock->expects($this->once())->method('getChannelPriceHistoryConfig')->willReturn(null);
        $channelMock->expects($this->once())->method('getShopBillingData')->willReturn($shopBillingDataMock);
        $this->configFactoryMock->expects($this->once())->method('createNew')->willReturn($configMock);
        $channelMock->expects($this->once())->method('setChannelPriceHistoryConfig')->with($configMock);
        $denormalizerMock->expects($this->once())->method('denormalize')->with([], ChannelInterface::class, null, [self::ALREADY_CALLED => true])->willReturn($channelMock);
        $this->assertSame($channelMock, $this->channelDenormalizer->denormalize([], ChannelInterface::class));
    }

    public function testAddsANewShopBillingDataWhenChannelHasNone(): void
    {
        /** @var DenormalizerInterface|MockObject $denormalizerMock */
        $denormalizerMock = $this->createMock(DenormalizerInterface::class);
        /** @var ShopBillingDataInterface|MockObject $shopBillingDataMock */
        $shopBillingDataMock = $this->createMock(ShopBillingDataInterface::class);
        /** @var ChannelPriceHistoryConfigInterface|MockObject $configMock */
        $configMock = $this->createMock(ChannelPriceHistoryConfigInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $this->channelDenormalizer->setDenormalizer($denormalizerMock);
        $channelMock->expects($this->once())->method('getChannelPriceHistoryConfig')->willReturn($configMock);
        $channelMock->expects($this->once())->method('getShopBillingData')->willReturn(null);
        $this->shopBillingDataFactoryMock->expects($this->once())->method('createNew')->willReturn($shopBillingDataMock);
        $channelMock->expects($this->once())->method('setShopBillingData')->with($shopBillingDataMock);
        $denormalizerMock->expects($this->once())->method('denormalize')->with([], ChannelInterface::class, null, [self::ALREADY_CALLED => true])->willReturn($channelMock);
        $this->assertSame($channelMock, $this->channelDenormalizer->denormalize([], ChannelInterface::class));
    }
}
