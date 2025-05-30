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

namespace Tests\Sylius\Bundle\ApiBundle\StateProvider\Shop\Channel;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\ApiBundle\StateProvider\Shop\Channel\CollectionProvider;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Model\Channel;
use Sylius\Component\Core\Model\ChannelInterface;

final class CollectionProviderTest extends TestCase
{
    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionProviderMock;

    private CollectionProvider $collectionProvider;

    protected function setUp(): void
    {
        $this->sectionProviderMock = $this->createMock(SectionProviderInterface::class);
        $this->collectionProvider = new CollectionProvider($this->sectionProviderMock);
    }

    public function testProvidesChannel(): void
    {
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $operation = new GetCollection(class: Channel::class);
        $this->sectionProviderMock->expects(self::once())->method('getSection')->willReturn(new ShopApiSection());
        $this->collectionProvider->expects(self::once())->method('provide')->with($operation, [], ['sylius_api_channel' => $channelMock])
            ->shouldBeLike([$channelMock])
        ;
    }

    public function testThrowsAnExceptionWhenOperationClassIsNotChannel(): void
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
        $operationMock->expects(self::once())->method('getClass')->willReturn(Channel::class);
        $this->sectionProviderMock->expects(self::once())->method('getSection')->willReturn(new ShopApiSection());
        $this->expectException(InvalidArgumentException::class);
        $this->collectionProvider->provide($operationMock);
    }

    public function testThrowsAnExceptionWhenOperationIsNotInShopApiSection(): void
    {
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        $operation = new GetCollection(class: Channel::class);
        $this->sectionProviderMock->expects(self::once())->method('getSection')->willReturn(new AdminApiSection());
        $this->expectException(InvalidArgumentException::class);
        $this->collectionProvider->provide($operation, [], ['sylius_api_channel' => $channelMock]);
    }

    public function testThrowsAnExceptionWhenContextDoesNotHaveChannel(): void
    {
        $operation = new GetCollection(class: Channel::class);
        $this->sectionProviderMock->expects(self::once())->method('getSection')->willReturn(new ShopApiSection());
        $this->expectException(InvalidArgumentException::class);
        $this->collectionProvider->provide($operation, [], []);
    }
}
