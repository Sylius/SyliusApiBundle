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

namespace spec\Sylius\Bundle\ApiBundle\Validator\Constraints;

use PhpSpec\ObjectBehavior;
use \Sylius\Bundle\ApiBundle\Command\Payment\AddPaymentRequest;
use Sylius\Bundle\ApiBundle\Validator\Constraints\ChosenPaymentRequestActionEligibility;
use Sylius\Bundle\PaymentBundle\CommandProvider\PaymentRequestCommandProviderInterface;
use Sylius\Bundle\PaymentBundle\CommandProvider\ServiceProviderAwareCommandProviderInterface;
use Sylius\Bundle\PaymentBundle\Provider\GatewayFactoryNameProviderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Core\Repository\PaymentRepositoryInterface;
use Sylius\Component\Payment\Model\GatewayConfigInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ChosenPaymentRequestActionEligibilityValidatorSpec extends ObjectBehavior
{
    function let(
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        ServiceProviderAwareCommandProviderInterface $gatewayFactoryCommandProvider,
        GatewayFactoryNameProviderInterface $gatewayFactoryNameProvider,
        ExecutionContextInterface $executionContext,
    ): void {
        $this->beConstructedWith($paymentMethodRepository, $gatewayFactoryCommandProvider, $gatewayFactoryNameProvider);

        $this->initialize($executionContext);
    }

    function it_is_a_constraint_validator(): void
    {
        $this->shouldImplement(ConstraintValidatorInterface::class);
    }

    function it_throws_an_exception_if_value_is_not_a_add_payment_request(): void
    {
        $this
            ->shouldThrow(\InvalidArgumentException::class)
            ->during('validate', ['', new ChosenPaymentRequestActionEligibility()])
        ;
    }

    function it_throws_an_exception_if_constraint_is_not_an_instance_of_chosen_payment_request_action_eligibility(): void
    {
        $this
            ->shouldThrow(\InvalidArgumentException::class)
            ->during('validate', [
                new AddPaymentRequest('ORDER_TOKEN', 123, 'PAYMENT_METHOD_CODE'),
                new class() extends Constraint {}
            ])
        ;
    }

    function it_does_nothing_if_there_no_action_given(
        ExecutionContextInterface $executionContext,
    ): void {
        $command = new AddPaymentRequest(
            orderTokenValue: 'ORDER_TOKEN',
            paymentId: 123,
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
        );

        $executionContext
            ->addViolation('sylius.payment_method.not_exist', ['%code%' => 'PAYMENT_METHOD_CODE'])
            ->shouldNotBeCalled()
        ;

        $this->validate($command, new ChosenPaymentRequestActionEligibility());
    }

    function it_adds_violation_if_the_payment_method_does_not_exist(
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        ExecutionContextInterface $executionContext,
    ): void {
        $command = new AddPaymentRequest(
            orderTokenValue: 'ORDER_TOKEN',
            paymentId: 123,
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            action: 'capture',
        );

        $paymentMethodRepository->findOneBy(['code' => 'PAYMENT_METHOD_CODE'])->willReturn(null);

        $executionContext
            ->addViolation('sylius.payment_method.not_exist', ['%code%' => 'PAYMENT_METHOD_CODE'])
            ->shouldBeCalled()
        ;

        $this->validate($command, new ChosenPaymentRequestActionEligibility());
    }

    function it_adds_violation_if_there_is_no_gateway_config_on_the_payment_method(
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        ExecutionContextInterface $executionContext,
        PaymentMethodInterface $paymentMethod,
    ): void {
        $command = new AddPaymentRequest(
            orderTokenValue: 'ORDER_TOKEN',
            paymentId: 123,
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            action: 'capture',
        );

        $paymentMethodRepository->findOneBy(['code' => 'PAYMENT_METHOD_CODE'])->willReturn($paymentMethod);
        $paymentMethod->getGatewayConfig()->willReturn(null);

        $executionContext
            ->addViolation('sylius.payment_method.not_exist', ['%code%' => 'PAYMENT_METHOD_CODE'])
            ->shouldBeCalled()
        ;

        $this->validate($command, new ChosenPaymentRequestActionEligibility());
    }

    function it_adds_violation_if_there_is_no_gateway_command_provider_available(
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        ServiceProviderAwareCommandProviderInterface $gatewayFactoryCommandProvider,
        GatewayFactoryNameProviderInterface $gatewayFactoryNameProvider,
        ExecutionContextInterface $executionContext,
        PaymentMethodInterface $paymentMethod,
        GatewayConfigInterface $gatewayConfig,
    ): void {
        $command = new AddPaymentRequest(
            orderTokenValue: 'ORDER_TOKEN',
            paymentId: 123,
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            action: 'invalid_action',
        );

        $paymentMethodRepository->findOneBy(['code' => 'PAYMENT_METHOD_CODE'])->willReturn($paymentMethod);
        $paymentMethod->getGatewayConfig()->willReturn($gatewayConfig);

        $factoryName = 'offline';
        $gatewayFactoryNameProvider->provide($paymentMethod)->willReturn($factoryName);
        $gatewayFactoryCommandProvider->getCommandProvider($factoryName)->willReturn(null);

        $executionContext
            ->addViolation('sylius.payment_request.action_not_available', [
                '%code%' => 'PAYMENT_METHOD_CODE',
                '%id%' => 123,
            ])
            ->shouldBeCalled()
        ;

        $this->validate($command, new ChosenPaymentRequestActionEligibility());
    }

    function it_does_nothing_if_the_command_provider_is_not_an_action_command_provider(
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        ServiceProviderAwareCommandProviderInterface $gatewayFactoryCommandProvider,
        GatewayFactoryNameProviderInterface $gatewayFactoryNameProvider,
        ExecutionContextInterface $executionContext,
        PaymentMethodInterface $paymentMethod,
        GatewayConfigInterface $gatewayConfig,
        PaymentRequestCommandProviderInterface $commandProvider,
    ): void {
        $command = new AddPaymentRequest(
            orderTokenValue: 'ORDER_TOKEN',
            paymentId: 123,
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            action: 'capture',
        );

        $paymentMethodRepository->findOneBy(['code' => 'PAYMENT_METHOD_CODE'])->willReturn($paymentMethod);
        $paymentMethod->getGatewayConfig()->willReturn($gatewayConfig);

        $factoryName = 'offline';
        $gatewayFactoryNameProvider->provide($paymentMethod)->willReturn($factoryName);
        $gatewayFactoryCommandProvider->getCommandProvider($factoryName)->willReturn($commandProvider);

        $executionContext
            ->addViolation('sylius.payment_request.action_not_available', [
                '%code%' => 'PAYMENT_METHOD_CODE',
                '%id%' => 123,
            ])
            ->shouldNotBeCalled()
        ;

        $this->validate($command, new ChosenPaymentRequestActionEligibility());
    }

    function it_does_nothing_if_payment_request_action_is_eligible(
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        ServiceProviderAwareCommandProviderInterface $gatewayFactoryCommandProvider,
        GatewayFactoryNameProviderInterface $gatewayFactoryNameProvider,
        ExecutionContextInterface $executionContext,
        PaymentMethodInterface $paymentMethod,
        GatewayConfigInterface $gatewayConfig,
        ServiceProviderAwareCommandProviderInterface $actionsCommandProvider,
        PaymentRequestCommandProviderInterface $commandProvider,
    ): void {
        $command = new AddPaymentRequest(
            orderTokenValue: 'ORDER_TOKEN',
            paymentId: 123,
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            action: 'capture',
        );

        $paymentMethodRepository->findOneBy(['code' => 'PAYMENT_METHOD_CODE'])->willReturn($paymentMethod);
        $paymentMethod->getGatewayConfig()->willReturn($gatewayConfig);

        $factoryName = 'offline';
        $gatewayFactoryNameProvider->provide($paymentMethod)->willReturn($factoryName);
        $gatewayFactoryCommandProvider->getCommandProvider($factoryName)->willReturn($actionsCommandProvider);

        $actionsCommandProvider->getCommandProvider('capture')->willReturn($commandProvider);

        $executionContext
            ->addViolation('sylius.payment_request.action_not_available', [
                '%code%' => 'PAYMENT_METHOD_CODE',
                '%id%' => 123,
            ])
            ->shouldNotBeCalled()
        ;

        $this->validate(
            $command,
            new ChosenPaymentRequestActionEligibility(),
        );
    }

    function it_adds_violation_if_the_command_provider_does_not_provide_any_command(
        PaymentMethodRepositoryInterface $paymentMethodRepository,
        ServiceProviderAwareCommandProviderInterface $gatewayFactoryCommandProvider,
        GatewayFactoryNameProviderInterface $gatewayFactoryNameProvider,
        ExecutionContextInterface $executionContext,
        PaymentMethodInterface $paymentMethod,
        GatewayConfigInterface $gatewayConfig,
        ServiceProviderAwareCommandProviderInterface $commandProvider,
    ): void {
        $command = new AddPaymentRequest(
            orderTokenValue: 'ORDER_TOKEN',
            paymentId: 123,
            paymentMethodCode: 'PAYMENT_METHOD_CODE',
            action: 'capture',
        );

        $paymentMethodRepository->findOneBy(['code' => 'PAYMENT_METHOD_CODE'])->willReturn($paymentMethod);
        $paymentMethod->getGatewayConfig()->willReturn($gatewayConfig);

        $factoryName = 'offline';
        $gatewayFactoryNameProvider->provide($paymentMethod)->willReturn($factoryName);
        $gatewayFactoryCommandProvider->getCommandProvider($factoryName)->willReturn($commandProvider);

        $commandProvider->getCommandProvider('capture')->willReturn(null);

        $executionContext
            ->addViolation('sylius.payment_request.action_not_available', [
                '%code%' => 'PAYMENT_METHOD_CODE',
                '%id%' => 123,
            ])
            ->shouldBeCalled()
        ;

        $this->validate($command, new ChosenPaymentRequestActionEligibility());
    }
}
