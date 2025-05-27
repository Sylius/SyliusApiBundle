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
use Sylius\Bundle\ApiBundle\Validator\Constraints\ShopUserNotVerifiedValidator;
use InvalidArgumentException;
use Sylius\Bundle\ApiBundle\Command\Account\RequestShopUserVerification;
use Sylius\Bundle\ApiBundle\Command\Checkout\CompleteOrder;
use Sylius\Bundle\ApiBundle\Validator\Constraints\ShopUserNotVerified;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ShopUserNotVerifiedValidatorTest extends TestCase
{
    /** @var UserRepositoryInterface|MockObject */
    private MockObject $userRepositoryMock;
    private ShopUserNotVerifiedValidator $shopUserNotVerifiedValidator;
    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->shopUserNotVerifiedValidator = new ShopUserNotVerifiedValidator($this->userRepositoryMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->shopUserNotVerifiedValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotAnInstanceOfRequestShopUserVerification(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->shopUserNotVerifiedValidator->validate(new CompleteOrder('TOKEN'), final class() extends TestCase {
        });
    }

    public function testThrowsAnExceptionIfConstraintIsNotAnInstanceOfShopUserExists(): void
    {
        /** @var Constraint|MockObject $constraintMock */
        $constraintMock = $this->createMock(Constraint::class);
        $this->expectException(InvalidArgumentException::class);
        $this->shopUserNotVerifiedValidator->validate(new RequestShopUserVerification(42, '', ''), $constraintMock);
    }

    public function testThrowsAnExceptionIfShopUserDoesNotExist(): void
    {
        $this->userRepositoryMock->expects($this->once())->method('find')->with(42)->willReturn(null);
        $this->expectException(InvalidArgumentException::class);
        $this->shopUserNotVerifiedValidator->validate(new RequestShopUserVerification(42, '', ''), new ShopUserNotVerified());
    }

    public function testAddsViolationIfUserHasBeenVerified(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        $this->shopUserNotVerifiedValidator->initialize($executionContextMock);
        $this->userRepositoryMock->expects($this->once())->method('find')->with(42)->willReturn($shopUserMock);
        $shopUserMock->expects($this->once())->method('isVerified')->willReturn(true);
        $shopUserMock->expects($this->once())->method('getEmail')->willReturn('test@sylius.com');
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.account.is_verified', ['%email%' => 'test@sylius.com'])
        ;
        $this->shopUserNotVerifiedValidator->validate(new RequestShopUserVerification(42, '', ''), new ShopUserNotVerified());
    }

    public function testDoesNotAddViolationIfShopUserExists(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        $this->shopUserNotVerifiedValidator->initialize($executionContextMock);
        $this->userRepositoryMock->expects($this->once())->method('find')->with(42)->willReturn($shopUserMock);
        $shopUserMock->expects($this->once())->method('isVerified')->willReturn(false);
        $executionContextMock->expects($this->never())->method('addViolation')->with('sylius.account.is_verified', ['%email%' => 'test@sylius.com'])
        ;
        $this->shopUserNotVerifiedValidator->validate(new RequestShopUserVerification(42, '', ''), new ShopUserNotVerified());
    }
}
