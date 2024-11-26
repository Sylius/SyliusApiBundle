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

namespace Sylius\Bundle\ApiBundle\Tests\Serializer\ContextBuilder;

use ApiPlatform\State\SerializerContextBuilderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Serializer\ContextBuilder\PaymentMethodCodeContextBuilder;
use Sylius\Bundle\ApiBundle\Serializer\ContextKeys;
use Symfony\Component\HttpFoundation\Request;

final class PaymentMethodCodeContextBuilderTest extends TestCase
{
    private SerializerContextBuilderInterface|MockObject $decoratedSerializerContextBuilderMock;
    private PaymentMethodCodeContextBuilder $paymentMethodCodeContextBuilder;

    protected function setUp(): void
    {
        $this->decoratedSerializerContextBuilderMock = $this->createMock(SerializerContextBuilderInterface::class);
        $this->paymentMethodCodeContextBuilder = new PaymentMethodCodeContextBuilder($this->decoratedSerializerContextBuilderMock);
    }

    public function test_it_does_not_updates_a_context_when_the_request_does_not_contain_a_payment_method_code(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())->method('toArray')->willReturn([]);

        $this->decoratedSerializerContextBuilderMock
            ->expects($this->once())
            ->method('createFromRequest')
            ->with($requestMock, true, [])
        ;

        $this->assertEquals([], $this->paymentMethodCodeContextBuilder->createFromRequest($requestMock, true, []));
    }

    public function test_it_updates_a_context_when_the_request_contains_a_payment_method_code(): void
    {
        /** @var Request|MockObject $requestMock */
        $requestMock = $this->createMock(Request::class);
        $requestMock->expects($this->once())->method('toArray')->willReturn([
            'paymentMethodCode' => 'cash_on_delivery',
        ]);

        $this->decoratedSerializerContextBuilderMock
            ->expects($this->once())
            ->method('createFromRequest')
            ->with($requestMock, true, [])
        ;

        $this->assertEquals([
            ContextKeys::PAYMENT_METHOD_CODE => 'cash_on_delivery'
        ], $this->paymentMethodCodeContextBuilder->createFromRequest($requestMock, true, []));
    }
}
