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
use Sylius\Bundle\ApiBundle\Validator\Constraints\ShopUserResetPasswordTokenExistsValidator;
use InvalidArgumentException;
use Sylius\Bundle\ApiBundle\Validator\Constraints\ShopUserResetPasswordTokenExists;
use Sylius\Component\User\Model\UserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ShopUserResetPasswordTokenExistsValidatorTest extends TestCase
{
    /** @var UserRepositoryInterface|MockObject */
    private MockObject $userRepositoryMock;
    private ShopUserResetPasswordTokenExistsValidator $shopUserResetPasswordTokenExistsValidator;
    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->shopUserResetPasswordTokenExistsValidator = new ShopUserResetPasswordTokenExistsValidator($this->userRepositoryMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->shopUserResetPasswordTokenExistsValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotAString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->shopUserResetPasswordTokenExistsValidator->validate(null, new ShopUserResetPasswordTokenExists());
    }

    public function testThrowsAnExceptionIfConstraintIsNotAResetPasswordTokenExistsConstraint(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->shopUserResetPasswordTokenExistsValidator->validate('', final class() extends TestCase {
        });
    }

    public function testDoesNotAddViolationIfUserExists(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var UserInterface|MockObject $userMock */
        $userMock = $this->createMock(UserInterface::class);
        $this->shopUserResetPasswordTokenExistsValidator->initialize($executionContextMock);
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['passwordResetToken' => 'token'])->willReturn($userMock);
        $executionContextMock->expects($this->never())->method('addViolation')->with('sylius.reset_password.invalid_token', ['%token%' => 'token']);
        $this->shopUserResetPasswordTokenExistsValidator->validate('token', new ShopUserResetPasswordTokenExists());
    }

    public function testAddsViolationIfResetPasswordTokenDoesNotExist(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->shopUserResetPasswordTokenExistsValidator->initialize($executionContextMock);
        $this->userRepositoryMock->expects($this->once())->method('findOneBy')->with(['passwordResetToken' => 'token'])->willReturn(null);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.reset_password.invalid_token', ['%token%' => 'token']);
        $this->shopUserResetPasswordTokenExistsValidator->validate('token', new ShopUserResetPasswordTokenExists());
    }
}
