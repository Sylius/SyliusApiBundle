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

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\ApiBundle\Serializer\Denormalizer\ZoneDenormalizer;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Addressing\Model\ZoneInterface;
use Sylius\Component\Addressing\Model\ZoneMemberInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ZoneDenormalizerTest extends TestCase
{
    /** @var DenormalizerInterface|MockObject */
    private MockObject $denormalizerMock;

    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionProviderMock;

    private ZoneDenormalizer $zoneDenormalizer;

    protected function setUp(): void
    {
        $this->denormalizerMock = $this->createMock(DenormalizerInterface::class);
        $this->sectionProviderMock = $this->createMock(SectionProviderInterface::class);
        $this->zoneDenormalizer = new ZoneDenormalizer($this->denormalizerMock, $this->sectionProviderMock);
    }

    public function testSupportsOnlyAdminSection(): void
    {
        /** @var ZoneInterface|MockObject $zoneMock */
        $zoneMock = $this->createMock(ZoneInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $this->assertTrue($this->zoneDenormalizer
            ->supportsDenormalization([], ZoneInterface::class, null, [AbstractNormalizer::OBJECT_TO_POPULATE => $zoneMock]))
        ;
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertFalse($this->zoneDenormalizer
            ->supportsDenormalization([], ZoneInterface::class, null, [AbstractNormalizer::OBJECT_TO_POPULATE => $zoneMock]))
        ;
    }

    public function testSupportsOnlyObjectsToPopulateIsZoneInterface(): void
    {
        /** @var ZoneInterface|MockObject $zoneMock */
        $zoneMock = $this->createMock(ZoneInterface::class);
        /** @var ProductInterface|MockObject $productMock */
        $productMock = $this->createMock(ProductInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $this->assertTrue($this->zoneDenormalizer
            ->supportsDenormalization([], ZoneInterface::class, null, [AbstractNormalizer::OBJECT_TO_POPULATE => $zoneMock]))
        ;
        $this->assertFalse($this->zoneDenormalizer
            ->supportsDenormalization([], ZoneInterface::class))
        ;
        $this->assertFalse($this->zoneDenormalizer
            ->supportsDenormalization([], ZoneInterface::class, null, [AbstractNormalizer::OBJECT_TO_POPULATE => null]))
        ;
        $this->assertFalse($this->zoneDenormalizer
            ->supportsDenormalization([], ZoneInterface::class, null, [AbstractNormalizer::OBJECT_TO_POPULATE => []]))
        ;
        $this->assertFalse($this->zoneDenormalizer
            ->supportsDenormalization([], ZoneInterface::class, null, [AbstractNormalizer::OBJECT_TO_POPULATE => $productMock]))
        ;
    }

    public function testSupportsOnlyZoneInterface(): void
    {
        /** @var ZoneInterface|MockObject $zoneMock */
        $zoneMock = $this->createMock(ZoneInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $this->assertTrue($this->zoneDenormalizer
            ->supportsDenormalization([], ZoneInterface::class, null, [AbstractNormalizer::OBJECT_TO_POPULATE => $zoneMock]))
        ;
        $this->assertFalse($this->zoneDenormalizer
            ->supportsDenormalization([], ProductInterface::class, null, [AbstractNormalizer::OBJECT_TO_POPULATE => $zoneMock]))
        ;
    }

    public function testDoesNotReplaceMembersIfCurentZoneMembersAreNotPresent(): void
    {
        /** @var ZoneInterface|MockObject $zoneMock */
        $zoneMock = $this->createMock(ZoneInterface::class);
        /** @var ZoneInterface|MockObject $objectToPopulateMock */
        $objectToPopulateMock = $this->createMock(ZoneInterface::class);
        /** @var ZoneMemberInterface|MockObject $memberUSMock */
        $memberUSMock = $this->createMock(ZoneMemberInterface::class);
        /** @var ZoneMemberInterface|MockObject $memberUKMock */
        $memberUKMock = $this->createMock(ZoneMemberInterface::class);
        /** @var ZoneMemberInterface|MockObject $memberPLMock */
        $memberPLMock = $this->createMock(ZoneMemberInterface::class);
        /** @var ZoneMemberInterface|MockObject $memberDEMock */
        $memberDEMock = $this->createMock(ZoneMemberInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $memberUSMock->expects($this->once())->method('getCode')->willReturn('US');
        $memberUKMock->expects($this->once())->method('getCode')->willReturn('UK');
        $memberPLMock->expects($this->once())->method('getCode')->willReturn('PL');
        $memberDEMock->expects($this->once())->method('getCode')->willReturn('DE');
        $objectToPopulateMock->expects($this->once())->method('getMembers')->willReturn(new ArrayCollection([
            $memberUSMock,
            $memberUKMock,
        ]));
        $zoneMock->expects($this->once())->method('getMembers')->willReturn(new ArrayCollection([
            $memberPLMock,
            $memberDEMock,
        ]));
        $context = [
            AbstractNormalizer::OBJECT_TO_POPULATE => $objectToPopulateMock,
            'sylius_zone_denormalizer_already_called' => true,
        ];
        $this->denormalizerMock->expects($this->once())->method('denormalize')->with([], ZoneInterface::class, null, $context)->willReturn($zoneMock);
        $zoneMock->expects($this->never())->method('removeMember');
        $zoneMock->expects($this->never())->method('addMember');
        $this->zoneDenormalizer->denormalize([], ZoneInterface::class, null, [AbstractNormalizer::OBJECT_TO_POPULATE => $objectToPopulateMock]);
    }

    public function testReplaceMembersIfCurentZoneMembersArePresent(): void
    {
        /** @var ZoneInterface|MockObject $zoneMock */
        $zoneMock = $this->createMock(ZoneInterface::class);
        /** @var ZoneInterface|MockObject $objectToPopulateMock */
        $objectToPopulateMock = $this->createMock(ZoneInterface::class);
        /** @var ZoneMemberInterface|MockObject $memberUSMock */
        $memberUSMock = $this->createMock(ZoneMemberInterface::class);
        /** @var ZoneMemberInterface|MockObject $memberUKMock */
        $memberUKMock = $this->createMock(ZoneMemberInterface::class);
        /** @var ZoneMemberInterface|MockObject $memberPLMock */
        $memberPLMock = $this->createMock(ZoneMemberInterface::class);
        /** @var ZoneMemberInterface|MockObject $newMemberUSMock */
        $newMemberUSMock = $this->createMock(ZoneMemberInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $memberUSMock->expects($this->once())->method('getCode')->willReturn('US');
        $memberUKMock->expects($this->once())->method('getCode')->willReturn('UK');
        $memberPLMock->expects($this->once())->method('getCode')->willReturn('PL');
        $newMemberUSMock->expects($this->once())->method('getCode')->willReturn('US');
        $objectToPopulateMock->expects($this->once())->method('getMembers')->willReturn(new ArrayCollection([
            $memberUSMock,
            $memberUKMock,
        ]));
        $zoneMock->expects($this->once())->method('getMembers')->willReturn(new ArrayCollection([
            $memberPLMock,
            $newMemberUSMock,
        ]));
        $context = [
            AbstractNormalizer::OBJECT_TO_POPULATE => $objectToPopulateMock,
            'sylius_zone_denormalizer_already_called' => true,
        ];
        $this->denormalizerMock->expects($this->once())->method('denormalize')->with([], ZoneInterface::class, null, $context)->willReturn($zoneMock);
        $zoneMock->expects($this->once())->method('removeMember')->with($newMemberUSMock);
        $zoneMock->expects($this->once())->method('addMember')->with($memberUSMock);
        $this->zoneDenormalizer->denormalize([], ZoneInterface::class, null, [AbstractNormalizer::OBJECT_TO_POPULATE => $objectToPopulateMock]);
    }
}
