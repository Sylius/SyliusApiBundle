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

namespace Tests\Sylius\Bundle\ApiBundle\Validator\Constraints;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Bundle\ApiBundle\Validator\Constraints\ChosenPaymentMethodEligibilityValidator;
use InvalidArgumentException;
use Sylius\Bundle\ApiBundle\Command\Checkout\ChoosePaymentMethod;
use Sylius\Bundle\ApiBundle\Validator\Constraints\ChosenPaymentMethodEligibility;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ChosenPaymentMethodEligibilityValidatorTest extends TestCase
{
    /** @var PaymentRepositoryInterface|MockObject */
    private MockObject $paymentRepositoryMock;
    /** @var PaymentMethodRepositoryInterface|MockObject */
    private MockObject $paymentMethodRepositoryMock;
    /** @var PaymentMethodsResolverInterface|MockObject */
    private MockObject $paymentMethodsResolverMock;
    /** @var ExecutionContextInterface|MockObject */
    private MockObject $executionContextMock;
    private ChosenPaymentMethodEligibilityValidator $chosenPaymentMethodEligibilityValidator;
    protected function setUp(): void
    {
        $this->paymentRepositoryMock = $this->createMock(PaymentRepositoryInterface::class);
        $this->paymentMethodRepositoryMock = $this->createMock(PaymentMethodRepositoryInterface::class);
        $this->paymentMethodsResolverMock = $this->createMock(PaymentMethodsResolverInterface::class);
        $this->executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->chosenPaymentMethodEligibilityValidator = new ChosenPaymentMethodEligibilityValidator($this->paymentRepositoryMock, $this->paymentMethodRepositoryMock, $this->paymentMethodsResolverMock);
        $this->initialize($this->executionContextMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->chosenPaymentMethodEligibilityValidator);
    }

    public function testThrowsAnExceptionIfValueDoesNotExtendPaymentMethodCodeAwareInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->chosenPaymentMethodEligibilityValidator->validate('', new ChosenPaymentMethodEligibility());
    }

    public function testThrowsAnExceptionIfConstraintIsNotAnInstanceOfChosenPaymentMethodEligibility(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->chosenPaymentMethodEligibilityValidator->validate(new ChoosePaymentMethod('code', 123, 'ORDER_TOKEN'), final class() extends TestCase {
        });
    }

    public function testAddsViolationIfChosenPaymentMethodDoesNotMatchSupportedMethods(): void
    {
        /** @var PaymentMethodInterface|MockObject $firstPaymentMethodMock */
        $firstPaymentMethodMock = $this->createMock(PaymentMethodInterface::class);
        /** @var PaymentMethodInterface|MockObject $secondPaymentMethodMock */
        $secondPaymentMethodMock = $this->createMock(PaymentMethodInterface::class);
        /** @var PaymentMethodInterface|MockObject $thirdPaymentMethodMock */
        $thirdPaymentMethodMock = $this->createMock(PaymentMethodInterface::class);
        /** @var PaymentInterface|MockObject $paymentMock */
        $paymentMock = $this->createMock(PaymentInterface::class);
        $command = new ChoosePaymentMethod(
            orderTokenValue: 'ORDER_TOKEN',
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            paymentId: 123,
        );
        $this->paymentMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'PAYMENT_METHOD_CODE'])->willReturn($firstPaymentMethodMock);
        $firstPaymentMethodMock->expects($this->once())->method('getName')->willReturn('offline');
        $this->paymentRepositoryMock->expects($this->once())->method('find')->with('123')->willReturn($paymentMock);
        $this->paymentMethodsResolverMock->expects($this->once())->method('getSupportedMethods')->with($paymentMock)->willReturn([$secondPaymentMethodMock, $thirdPaymentMethodMock]);
        $this->executionContextMock->expects($this->once())->method('addViolation')->with('sylius.payment_method.not_available', ['%name%' => 'offline'])
        ;
        $this->chosenPaymentMethodEligibilityValidator->validate($command, new ChosenPaymentMethodEligibility());
    }

    public function testAddsViolationIfPaymentDoesNotExist(): void
    {
        /** @var PaymentMethodInterface|MockObject $paymentMethodMock */
        $paymentMethodMock = $this->createMock(PaymentMethodInterface::class);
        $command = new ChoosePaymentMethod(
            orderTokenValue: 'ORDER_TOKEN',
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            paymentId: 123,
        );
        $this->paymentMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'PAYMENT_METHOD_CODE'])->willReturn($paymentMethodMock);
        $this->paymentRepositoryMock->expects($this->once())->method('find')->with('123')->willReturn(null);
        $this->executionContextMock->expects($this->once())->method('addViolation')->with('sylius.payment.not_found')
        ;
        $this->chosenPaymentMethodEligibilityValidator->validate($command, new ChosenPaymentMethodEligibility());
    }

    public function testAddsViolationIfPaymentMethodDoesNotExist(): void
    {
        $command = new ChoosePaymentMethod(
            orderTokenValue: 'ORDER_TOKEN',
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            paymentId: 123,
        );
        $this->paymentMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'PAYMENT_METHOD_CODE'])->willReturn(null);
        $this->executionContextMock->expects($this->once())->method('addViolation')->with('sylius.payment_method.not_exist', ['%code%' => 'PAYMENT_METHOD_CODE'])
        ;
        $this->chosenPaymentMethodEligibilityValidator->validate($command, new ChosenPaymentMethodEligibility());
    }

    public function testDoesNothingIfPaymentMethodIsEligible(): void
    {
        /** @var PaymentMethodInterface|MockObject $firstPaymentMethodMock */
        $firstPaymentMethodMock = $this->createMock(PaymentMethodInterface::class);
        /** @var PaymentMethodInterface|MockObject $secondPaymentMethodMock */
        $secondPaymentMethodMock = $this->createMock(PaymentMethodInterface::class);
        /** @var PaymentInterface|MockObject $paymentMock */
        $paymentMock = $this->createMock(PaymentInterface::class);
        $command = new ChoosePaymentMethod(
            orderTokenValue: 'ORDER_TOKEN',
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            paymentId: 123,
        );
        $this->paymentMethodRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'PAYMENT_METHOD_CODE'])->willReturn($secondPaymentMethodMock);
        $firstPaymentMethodMock->expects($this->once())->method('getName')->willReturn('offline');
        $this->paymentRepositoryMock->expects($this->once())->method('find')->with('123')->willReturn($paymentMock);
        $this->paymentMethodsResolverMock->expects($this->once())->method('getSupportedMethods')->with($paymentMock)->willReturn([$firstPaymentMethodMock, $secondPaymentMethodMock]);
        $this->executionContextMock->expects($this->never())->method('addViolation')->with('sylius.payment_method.not_exist', ['%code%' => 'PAYMENT_METHOD_CODE'])
        ;
        $this->chosenPaymentMethodEligibilityValidator->validate(
            $command,
            new ChosenPaymentMethodEligibility(),
        );
    }
}
