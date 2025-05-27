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
use Sylius\Bundle\ApiBundle\Validator\Constraints\ShopUserResetPasswordTokenNotExpiredValidator;
use InvalidArgumentException;
use DateInterval;
use Sylius\Bundle\ApiBundle\Validator\Constraints\ShopUserResetPasswordTokenNotExpired;
use Sylius\Component\User\Model\UserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ShopUserResetPasswordTokenNotExpiredValidatorTest extends TestCase
{
    /** @var UserRepositoryInterface|MockObject */
    private MockObject $userRepositoryMock;
    private ShopUserResetPasswordTokenNotExpiredValidator $shopUserResetPasswordTokenNotExpiredValidator;
    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->shopUserResetPasswordTokenNotExpiredValidator = new ShopUserResetPasswordTokenNotExpiredValidator($this->userRepositoryMock, 'P1D');
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->shopUserResetPasswordTokenNotExpiredValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotAString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->shopUserResetPasswordTokenNotExpiredValidator->validate(null, new ShopUserResetPasswordTokenNotExpired());
    }

    public function testThrowsAnExceptionIfConstraintIsNotAResetPasswordTokenNotExpiredConstraint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->shopUserResetPasswordTokenNotExpiredValidator->validate('', final class() extends TestCase {
        });
    }

    public function testDoesNotAddViolationIfResetPasswordTokenDoesNotExist(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->shopUserResetPasswordTokenNotExpiredValidator->initialize($executionContextMock);
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['passwordResetToken' => 'token'])->willReturn(null);
        $executionContextMock->expects($this->never())->method('addViolation')->with('sylius.reset_password.token_expired');
        $this->shopUserResetPasswordTokenNotExpiredValidator->validate('token', new ShopUserResetPasswordTokenNotExpired());
    }

    public function testDoesNotAddViolationIfResetPasswordTokenIsNotExpired(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var UserInterface|MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $this->shopUserResetPasswordTokenNotExpiredValidator->initialize($executionContextMock);
        $userMock->expects($this->once())->method('isPasswordRequestNonExpired')->with($this->callback(function (DateInterval $dateInterval) {
            $this->assertSame('1', $dateInterval->format('%d'));
            return true;
        }))->willReturn(true);
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['passwordResetToken' => 'token'])->willReturn($userMock);
        $executionContextMock->expects($this->never())->method('addViolation')->with('sylius.reset_password.token_expired');
        $this->shopUserResetPasswordTokenNotExpiredValidator->validate('token', new ShopUserResetPasswordTokenNotExpired());
    }

    public function testAddsViolationIfResetPasswordTokenIsExpired(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var UserInterface|MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $this->shopUserResetPasswordTokenNotExpiredValidator->initialize($executionContextMock);
        $userMock->expects($this->once())->method('isPasswordRequestNonExpired')->with($this->callback(function (DateInterval $dateInterval) {
            $this->assertSame('1', $dateInterval->format('%d'));
            return true;
        }))->willReturn(false);
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['passwordResetToken' => 'token'])->willReturn($userMock);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.reset_password.token_expired');
        $this->shopUserResetPasswordTokenNotExpiredValidator->validate('token', new ShopUserResetPasswordTokenNotExpired());
    }
}
