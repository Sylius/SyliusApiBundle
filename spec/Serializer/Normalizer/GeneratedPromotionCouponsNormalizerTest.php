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

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\SectionResolver\AdminApiSection;
use Sylius\Bundle\ApiBundle\SectionResolver\ShopApiSection;
use Sylius\Bundle\ApiBundle\Serializer\Normalizer\GeneratedPromotionCouponsNormalizer;
use Sylius\Bundle\CoreBundle\SectionResolver\SectionProviderInterface;
use Sylius\Component\Core\Model\PromotionCouponInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class GeneratedPromotionCouponsNormalizerTest extends TestCase
{
    /** @var SectionProviderInterface|MockObject */
    private MockObject $sectionProviderMock;

    /** @var NormalizerInterface|MockObject */
    private MockObject $normalizerMock;

    private GeneratedPromotionCouponsNormalizer $generatedPromotionCouponsNormalizer;

    protected function setUp(): void
    {
        $this->sectionProviderMock = $this->createMock(SectionProviderInterface::class);
        $this->normalizerMock = $this->createMock(NormalizerInterface::class);
        $this->generatedPromotionCouponsNormalizer = new GeneratedPromotionCouponsNormalizer($this->sectionProviderMock, ['sylius:promotion_coupon:index']);
        $this->setNormalizer($this->normalizerMock);
    }

    public function testSupportsOnlyArrayCollectionThatContainsCouponsInAdminSectionWithProperData(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $this->assertTrue($this->generatedPromotionCouponsNormalizer
            ->supportsNormalization(new ArrayCollection([$promotionCouponMock]), null, ['groups' => ['sylius:promotion_coupon:index']]))
        ;
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $this->assertFalse($this->generatedPromotionCouponsNormalizer
            ->supportsNormalization(new stdClass(), null, ['groups' => ['sylius:promotion_coupon:index']]))
        ;
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $this->assertFalse($this->generatedPromotionCouponsNormalizer
            ->supportsNormalization(new ArrayCollection([$promotionCouponMock]), null, ['groups' => ['sylius:promotion_coupon:shop']]))
        ;
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertFalse($this->generatedPromotionCouponsNormalizer
            ->supportsNormalization(new ArrayCollection([$promotionCouponMock]), null, ['groups' => ['sylius:promotion_coupon:index']]))
        ;
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertFalse($this->generatedPromotionCouponsNormalizer->supportsNormalization($promotionCouponMock, null, ['groups' => ['sylius:promotion_coupon:index']]));
    }

    public function testDoesNotSupportIfTheNormalizerHasBeenAlreadyCalled(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->assertFalse($this->generatedPromotionCouponsNormalizer
            ->supportsNormalization(new ArrayCollection([$promotionCouponMock]), null, [
                'sylius_generated_promotion_coupons_normalizer_already_called' => true,
                'groups' => ['sylius:promotion_coupon:index'],
            ]))
        ;
    }

    public function testCallsDefaultNormalizerWhenGivenResourceIsNotAnInstanceOfArrayCollectionContainingPromotionCouponInterface(): void
    {
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $this->normalizerMock->expects($this->once())->method('normalize')->with(new ArrayCollection([new stdClass()]), null, [
            'sylius_generated_promotion_coupons_normalizer_already_called' => true,
            'groups' => ['sylius:promotion_coupon:index'],
        ])
            ->willReturn([])
        ;
        $this->generatedPromotionCouponsNormalizer->normalize(new ArrayCollection([new stdClass()]), null, ['groups' => ['sylius:promotion_coupon:index']]);
    }

    public function testThrowsAnExceptionIfTheGivenResourceIsNotAnInstanceOfArrayCollection(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        $this->sectionProviderMock->expects($this->never())->method('getSection');
        $this->normalizerMock->expects($this->never())->method('normalize')->with($promotionCouponMock, null, [
            'sylius_generated_promotion_coupons_normalizer_already_called' => true,
            'groups' => ['sylius:promotion_coupon:index'],
        ])
        ;
        $this->expectException(InvalidArgumentException::class);
        $this->generatedPromotionCouponsNormalizer->normalize($promotionCouponMock, null, ['groups' => ['sylius:promotion_coupon:index']]);
    }

    public function testThrowsAnExceptionIfSerializerHasAlreadyBeenCalled(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        $this->sectionProviderMock->expects($this->never())->method('getSection');
        $this->normalizerMock->expects($this->never())->method('normalize')->with(new ArrayCollection([$promotionCouponMock]), null, [
            'sylius_generated_promotion_coupons_normalizer_already_called' => true,
            'groups' => ['sylius:promotion_coupon:index'],
        ])
        ;
        $this->expectException(InvalidArgumentException::class);
        $this->generatedPromotionCouponsNormalizer->normalize(new ArrayCollection([$promotionCouponMock]), null, [
            'sylius_generated_promotion_coupons_normalizer_already_called' => true,
            'groups' => ['sylius:promotion_coupon:index'],
        ]);
    }

    public function testThrowsAnExceptionIfItIsNotAdminSection(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new ShopApiSection());
        $this->normalizerMock->expects($this->never())->method('normalize')->with(new ArrayCollection([$promotionCouponMock]), null, [
            'sylius_generated_promotion_coupons_normalizer_already_called' => true,
            'groups' => ['sylius:promotion_coupon:index'],
        ])
        ;
        $this->expectException(InvalidArgumentException::class);
        $this->generatedPromotionCouponsNormalizer->normalize(new ArrayCollection([$promotionCouponMock]), null, ['groups' => ['sylius:promotion_coupon:index']]);
    }

    public function testThrowsAnExceptionIfSerializationGroupIsNotSupported(): void
    {
        /** @var PromotionCouponInterface|MockObject $promotionCouponMock */
        $promotionCouponMock = $this->createMock(PromotionCouponInterface::class);
        $this->sectionProviderMock->expects($this->once())->method('getSection')->willReturn(new AdminApiSection());
        $this->normalizerMock->expects($this->never())->method('normalize')->with(new ArrayCollection([$promotionCouponMock]), null, [
            'sylius_generated_promotion_coupons_normalizer_already_called' => true,
            'groups' => ['sylius:promotion_coupon:show'],
        ])
        ;
        $this->expectException(InvalidArgumentException::class);
        $this->generatedPromotionCouponsNormalizer->normalize(new ArrayCollection([$promotionCouponMock]), null, ['groups' => ['sylius:promotion_coupon:show']]);
    }
}
