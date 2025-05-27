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

use DateInterval;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Validator\Constraints\AdminResetPasswordTokenNonExpired;
use Sylius\Bundle\ApiBundle\Validator\Constraints\AdminResetPasswordTokenNonExpiredValidator;
use Sylius\Bundle\CoreBundle\Command\Admin\Account\ResetPassword;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class AdminResetPasswordTokenNonExpiredValidatorTest extends TestCase
{
    /** @var UserRepositoryInterface|MockObject */
    private MockObject $userRepositoryMock;

    private AdminResetPasswordTokenNonExpiredValidator $adminResetPasswordTokenNonExpiredValidator;

    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->adminResetPasswordTokenNonExpiredValidator = new AdminResetPasswordTokenNonExpiredValidator($this->userRepositoryMock, 'P5D');
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->adminResetPasswordTokenNonExpiredValidator);
    }

    public function testThrowsExceptionWhenValueIsNotAResetPassword(): void
    {
        $constraint = new AdminResetPasswordTokenNonExpired();
        $this->expectException(InvalidArgumentException::class);
        $this->adminResetPasswordTokenNonExpiredValidator->validate('', $constraint);
    }

    public function testThrowsExceptionWhenConstraintIsNotAdminResetPasswordTokenNonExpired(): void
    {
        /** @var Constraint|MockObject $constraintMock */
        $constraintMock = $this->createMock(Constraint::class);
        $value = new ResetPassword('token', 'newPassword');
        $this->expectException(InvalidArgumentException::class);
        $this->adminResetPasswordTokenNonExpiredValidator->validate($value, $constraintMock);
    }

    public function testDoesNothingWhenAUserForGivenTokenDoesNotExist(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $value = new ResetPassword('token', 'newPassword');
        $constraint = new AdminResetPasswordTokenNonExpired();
        $this->adminResetPasswordTokenNonExpiredValidator->initialize($executionContextMock);
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['passwordResetToken' => 'token'])->willReturn(null);
        $executionContextMock->expects($this->never())->method('addViolation');
        $this->adminResetPasswordTokenNonExpiredValidator->validate($value, $constraint);
    }

    public function testDoesNothingWhenUserPasswordResetTokenIsNonExpired(): void
    {
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $value = new ResetPassword('token', 'newPassword');
        $constraint = new AdminResetPasswordTokenNonExpired();
        $this->adminResetPasswordTokenNonExpiredValidator->initialize($executionContextMock);
        $adminUserMock->expects($this->once())->method('isPasswordRequestNonExpired')->willReturn(static fn (DateInterval $dateInterval) => $dateInterval->expects($this->once())->method('format')->with('%d') === '5')->willReturn(true);
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['passwordResetToken' => 'token'])->willReturn($adminUserMock);
        $executionContextMock->expects($this->never())->method('addViolation');
        $this->adminResetPasswordTokenNonExpiredValidator->validate($value, $constraint);
    }

    public function testAddsAViolationWhenUserPasswordResetTokenIsExpired(): void
    {
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $value = new ResetPassword('token', 'newPassword');
        $constraint = new AdminResetPasswordTokenNonExpired();
        $this->adminResetPasswordTokenNonExpiredValidator->initialize($executionContextMock);
        $adminUserMock->expects($this->once())->method('isPasswordRequestNonExpired')->willReturn(static fn (DateInterval $dateInterval) => $dateInterval->expects($this->once())->method('format')->with('%d') === '5')->willReturn(false);
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['passwordResetToken' => 'token'])->willReturn($adminUserMock);
        $executionContextMock->expects($this->once())->method('addViolation')->with($constraint->message);
        $this->adminResetPasswordTokenNonExpiredValidator->validate($value, $constraint);
    }
}
