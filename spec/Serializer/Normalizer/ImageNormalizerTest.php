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

use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use Liip\ImagineBundle\Exception\Imagine\Filter\NonExistingFilterException;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use OutOfBoundsException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use stdClass;
use Sylius\Bundle\ApiBundle\Serializer\Normalizer\ImageNormalizer;
use Sylius\Component\Core\Model\ImageInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class ImageNormalizerTest extends TestCase
{
    /** @var CacheManager|MockObject */
    private MockObject $cacheManagerMock;

    /** @var RequestStack|MockObject */
    private MockObject $requestStackMock;

    /** @var NormalizerInterface|MockObject */
    private MockObject $normalizerMock;

    private ImageNormalizer $imageNormalizer;

    private const DEFAULT_FILTER = 'default';

    public function testAContextAwareNormalizer(): void
    {
        $this->assertInstanceOf(NormalizerInterface::class, $this->imageNormalizer);
        $this->assertInstanceOf(NormalizerAwareInterface::class, $this->imageNormalizer);
    }

    protected function setUp(): void
    {
        $this->cacheManagerMock = $this->createMock(CacheManager::class);
        $this->requestStackMock = $this->createMock(RequestStack::class);
        $this->normalizerMock = $this->createMock(NormalizerInterface::class);
        $this->imageNormalizer = new ImageNormalizer($this->cacheManagerMock, $this->requestStackMock, self::DEFAULT_FILTER);
        $this->setNormalizer($this->normalizerMock);
    }

    public function testSupportsOnlyImages(): void
    {
        /** @var ImageInterface|MockObject $imageMock */
        $imageMock = $this->createMock(ImageInterface::class);
        $this->assertFalse($this->imageNormalizer->supportsNormalization(new stdClass(), Argument::any()));
        $this->assertTrue($this->imageNormalizer->supportsNormalization($imageMock, Argument::any()));
    }

    public function testDoesNotSupportIfTheNormalizerHasBeenAlreadyCalled(): void
    {
        /** @var ImageInterface|MockObject $imageMock */
        $imageMock = $this->createMock(ImageInterface::class);
        $this->assertFalse($this->imageNormalizer->supportsNormalization(
            $imageMock,
            Argument::any(),
            ['sylius_image_normalizer_already_called' => true],
        ));
    }

    public function testResolvesImagePathBasedOnDefaultFilterIfThereIsNoRequest(): void
    {
        /** @var ImageInterface|MockObject $imageMock */
        $imageMock = $this->createMock(ImageInterface::class);
        $this->normalizerMock->expects($this->once())->method('normalize')->with($imageMock, null, ['sylius_image_normalizer_already_called' => true])
            ->willReturn(['path' => 'some_path'])
        ;
        $this->requestStackMock->expects($this->once())->method('getCurrentRequest')->willReturn(null);
        $this->cacheManagerMock->expects($this->once())->method('getBrowserPath')->with(parse_url('some_path', \PHP_URL_PATH), self::DEFAULT_FILTER)
            ->willReturn('default_filter_path')
        ;
        $this->assertSame(['path' => 'default_filter_path'], $this->imageNormalizer->normalize($imageMock));
    }

    public function testResolvesImagePathBasedOnDefaultFilterIfThereNoImageFilterHasBeenPassedViaRequest(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var ImageInterface|MockObject $imageMock */
        $imageMock = $this->createMock(ImageInterface::class);
        $this->normalizerMock->expects($this->once())->method('normalize')->with($imageMock, null, ['sylius_image_normalizer_already_called' => true])
            ->willReturn(['path' => 'some_path'])
        ;
        $requestMock->query = new InputBag([ImageNormalizer::FILTER_QUERY_PARAMETER => '']);
        $this->requestStackMock->expects($this->once())->method('getCurrentRequest')->willReturn($requestMock);
        $this->cacheManagerMock->expects($this->once())->method('getBrowserPath')->with(parse_url('some_path', \PHP_URL_PATH), self::DEFAULT_FILTER)
            ->willReturn('default_filter_path')
        ;
        $this->assertSame(['path' => 'default_filter_path'], $this->imageNormalizer->normalize($imageMock));
    }

    public function testThrowsValidationExceptionWhenPassingAnInvalidImageFilter(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var ImageInterface|MockObject $imageMock */
        $imageMock = $this->createMock(ImageInterface::class);
        $this->normalizerMock->expects($this->once())->method('normalize')->with($imageMock, null, ['sylius_image_normalizer_already_called' => true])
            ->willReturn(['path' => 'some_path'])
        ;
        $requestMock->query = new InputBag([ImageNormalizer::FILTER_QUERY_PARAMETER => 'invalid']);
        $this->requestStackMock->expects($this->once())->method('getCurrentRequest')->willReturn($requestMock);
        $this->cacheManagerMock->expects($this->once())->method('getBrowserPath')->with(parse_url('some_path', \PHP_URL_PATH), 'invalid')
            ->willThrowException(NonExistingFilterException::class)
        ;
        $this->expectException(InvalidArgumentException::class);
        $this->imageNormalizer->normalize($imageMock, null, []);
    }

    public function testThrowsValidationExceptionWhenResolverForPassedFilterCouldNotBeenFound(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var ImageInterface|MockObject $imageMock */
        $imageMock = $this->createMock(ImageInterface::class);
        $this->normalizerMock->expects($this->once())->method('normalize')->with($imageMock, null, ['sylius_image_normalizer_already_called' => true])
            ->willReturn(['path' => 'some_path'])
        ;
        $requestMock->query = new InputBag([ImageNormalizer::FILTER_QUERY_PARAMETER => 'no-resolver-filter']);
        $this->requestStackMock->expects($this->once())->method('getCurrentRequest')->willReturn($requestMock);
        $this->cacheManagerMock->expects($this->once())->method('getBrowserPath')->with(parse_url('some_path', \PHP_URL_PATH), 'no-resolver-filter')
            ->willThrowException(OutOfBoundsException::class)
        ;
        $this->expectException(InvalidArgumentException::class);
        $this->imageNormalizer->normalize($imageMock, null, []);
    }

    public function testDoesNotResolveImagePathIfPathIsNotSerialized(): void
    {
        /** @var ImageInterface|MockObject $imageMock */
        $imageMock = $this->createMock(ImageInterface::class);
        $this->normalizerMock->expects($this->once())->method('normalize')->with($imageMock, null, ['sylius_image_normalizer_already_called' => true])
            ->willReturn(['id' => 1])
        ;
        $this->assertSame(['id' => 1], $this->imageNormalizer->normalize($imageMock, null, []));
        $this->cacheManagerMock->expects($this->once())->method('getBrowserPath')->shouldNotHaveBeenCalled();
        $this->requestStackMock->expects($this->once())->method('getCurrentRequest')->shouldNotHaveBeenCalled();
    }

    public function testAppliesGivenImageFilter(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        /** @var ImageInterface|MockObject $imageMock */
        $imageMock = $this->createMock(ImageInterface::class);
        $this->normalizerMock->expects($this->once())->method('normalize')->with($imageMock, null, ['sylius_image_normalizer_already_called' => true])
            ->willReturn(['path' => 'some_path'])
        ;
        $requestMock->query = new InputBag([ImageNormalizer::FILTER_QUERY_PARAMETER => 'sylius_large']);
        $this->requestStackMock->expects($this->once())->method('getCurrentRequest')->willReturn($requestMock);
        $this->cacheManagerMock->expects($this->once())->method('getBrowserPath')->with(parse_url('some_path', \PHP_URL_PATH), 'sylius_large')
            ->willReturn('large_filter_path')
        ;
        $this->assertSame(['path' => 'large_filter_path'], $this->imageNormalizer->normalize($imageMock));
    }
}
