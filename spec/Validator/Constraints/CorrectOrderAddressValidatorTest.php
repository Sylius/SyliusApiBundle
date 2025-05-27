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
use Sylius\Bundle\ApiBundle\Validator\Constraints\CorrectOrderAddressValidator;
use InvalidArgumentException;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Bundle\ApiBundle\Command\Checkout\UpdateCart;
use Sylius\Bundle\ApiBundle\Validator\Constraints\CorrectOrderAddress;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class CorrectOrderAddressValidatorTest extends TestCase
{
    /** @var RepositoryInterface|MockObject */
    private MockObject $countryRepositoryMock;
    private CorrectOrderAddressValidator $correctOrderAddressValidator;
    protected function setUp(): void
    {
        $this->countryRepositoryMock = $this->createMock(RepositoryInterface::class);
        $this->correctOrderAddressValidator = new CorrectOrderAddressValidator($this->countryRepositoryMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->correctOrderAddressValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotAnInstanceOfAddressOrderCommand(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->correctOrderAddressValidator->validate(new CompleteOrder('TOKEN'), new CorrectOrderAddress());
    }

    public function testThrowsAnExceptionIfConstraintIsNotAnInstanceOfAddingEligibleProductVariantToCart(): void
    {
        /** @var AddressInterface|MockObject $billingAddressMock */
        $billingAddressMock = $this->createMock(AddressInterface::class);
        /** @var AddressInterface|MockObject $shippingAddressMock */
        $shippingAddressMock = $this->createMock(AddressInterface::class);
        $this->expectException(InvalidArgumentException::class);
        $this->correctOrderAddressValidator->validate(new UpdateCart(
            orderTokenValue: 'TOKEN',
            email: 'john@doe.com',
            billingAddress: $billingAddressMock,
            shippingAddress: $shippingAddressMock,
        ), final class() extends TestCase {
        });
    }

    public function testAddsViolationIfBillingAddressHasIncorrectCountryCode(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var AddressInterface|MockObject $billingAddressMock */
        $billingAddressMock = $this->createMock(AddressInterface::class);
        $this->correctOrderAddressValidator->initialize($executionContextMock);
        $billingAddressMock->expects($this->once())->method('getCountryCode')->willReturn('united_russia');
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.country.not_exist', ['%countryCode%' => 'united_russia'])
        ;
        $this->correctOrderAddressValidator->validate(
            new UpdateCart(
                orderTokenValue: 'TOKEN',
                email: 'john@doe.com',
                billingAddress: $billingAddressMock,
            ),
            new CorrectOrderAddress(),
        );
    }

    public function testAddsViolationIfBillingAddressHasNotCountryCode(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var AddressInterface|MockObject $billingAddressMock */
        $billingAddressMock = $this->createMock(AddressInterface::class);
        $this->correctOrderAddressValidator->initialize($executionContextMock);
        $billingAddressMock->expects($this->once())->method('getCountryCode')->willReturn(null);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.address.without_country')
        ;
        $this->correctOrderAddressValidator->validate(
            new UpdateCart(
                orderTokenValue: 'TOKEN',
                email: 'john@doe.com',
                billingAddress: $billingAddressMock,
            ),
            new CorrectOrderAddress(),
        );
    }

    public function testAddsViolationIfShippingAddressHasIncorrectCountryCode(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var AddressInterface|MockObject $billingAddressMock */
        $billingAddressMock = $this->createMock(AddressInterface::class);
        /** @var AddressInterface|MockObject $shippingAddressMock */
        $shippingAddressMock = $this->createMock(AddressInterface::class);
        /** @var CountryInterface|MockObject $usaMock */
        $usaMock = $this->createMock(CountryInterface::class);
        $this->correctOrderAddressValidator->initialize($executionContextMock);
        $billingAddressMock->expects($this->once())->method('getCountryCode')->willReturn('US');
        $shippingAddressMock->expects($this->once())->method('getCountryCode')->willReturn('united_russia');
        $this->countryRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'US'])->willReturn($usaMock);
        $this->countryRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'united_russia'])->willReturn(null);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.country.not_exist', ['%countryCode%' => 'united_russia'])
        ;
        $this->correctOrderAddressValidator->validate(
            new UpdateCart(
                orderTokenValue: 'TOKEN',
                email: 'john@doe.com',
                billingAddress: $billingAddressMock,
                shippingAddress: $shippingAddressMock,
            ),
            new CorrectOrderAddress(),
        );
    }

    public function testAddsViolationIfShippingAddressAndBillingAddressHaveIncorrectCountryCode(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var AddressInterface|MockObject $billingAddressMock */
        $billingAddressMock = $this->createMock(AddressInterface::class);
        /** @var AddressInterface|MockObject $shippingAddressMock */
        $shippingAddressMock = $this->createMock(AddressInterface::class);
        $this->correctOrderAddressValidator->initialize($executionContextMock);
        $billingAddressMock->expects($this->once())->method('getCountryCode')->willReturn('euroland');
        $shippingAddressMock->expects($this->once())->method('getCountryCode')->willReturn('united_russia');
        $this->countryRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'euroland'])->willReturn(null);
        $this->countryRepositoryMock->expects($this->once())->method('findOneBy')->with(['code' => 'united_russia'])->willReturn(null);
        $executionContextMock->expects($this->exactly(2))->method('addViolation')->willReturnMap([['sylius.country.not_exist', ['%countryCode%' => 'euroland']], ['sylius.country.not_exist', ['%countryCode%' => 'united_russia']]]);
        $this->correctOrderAddressValidator->validate(
            new UpdateCart(
                orderTokenValue: 'TOKEN',
                email: 'john@doe.com',
                billingAddress: $billingAddressMock,
                shippingAddress: $shippingAddressMock,
            ),
            new CorrectOrderAddress(),
        );
    }
}
