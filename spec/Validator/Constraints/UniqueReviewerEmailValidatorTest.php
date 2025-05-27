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
use Sylius\Bundle\ApiBundle\Validator\Constraints\UniqueReviewerEmailValidator;
use InvalidArgumentException;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Bundle\ApiBundle\Validator\Constraints\UniqueReviewerEmail;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class UniqueReviewerEmailValidatorTest extends TestCase
{
    /** @var UserRepositoryInterface|MockObject */
    private MockObject $shopUserRepositoryMock;
    /** @var UserContextInterface|MockObject */
    private MockObject $userContextMock;
    /** @var ExecutionContextInterface|MockObject */
    private MockObject $executionContextMock;
    private UniqueReviewerEmailValidator $uniqueReviewerEmailValidator;
    protected function setUp(): void
    {
        $this->shopUserRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->userContextMock = $this->createMock(UserContextInterface::class);
        $this->executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->uniqueReviewerEmailValidator = new UniqueReviewerEmailValidator($this->shopUserRepositoryMock, $this->userContextMock);
        $this->initialize($this->executionContextMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->uniqueReviewerEmailValidator);
    }

    public function testAddsViolationIfUserWithGivenEmailIsAlreadyRegistered(): void
    {
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn(null);
        $this->shopUserRepositoryMock->expects($this->once())->method('findOneByEmail')->with('email@example.com')->willReturn($shopUserMock);
        $this->executionContextMock->expects($this->once())->method('addViolation')->with('sylius.review.author.already_exists');
        $this->uniqueReviewerEmailValidator->validate('email@example.com', new UniqueReviewerEmail());
    }

    public function testDoesNothingIfValueIsNull(): void
    {
        $this->executionContextMock->expects($this->never())->method('addViolation');
        $this->uniqueReviewerEmailValidator->validate(null, new UniqueReviewerEmail());
    }

    public function testThrowsAnExceptionIfConstraintIsNotOfExpectedType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->uniqueReviewerEmailValidator->validate('', final class() extends TestCase {
        });
    }

    public function testDoesNotAddViolationIfTheGivenEmailIsTheSameAsLoggedInShopUser(): void
    {
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn($shopUserMock);
        $shopUserMock->expects($this->once())->method('getEmail')->willReturn('email@example.com');
        $this->executionContextMock->expects($this->never())->method('addViolation');
        $this->uniqueReviewerEmailValidator->validate('email@example.com', new UniqueReviewerEmail());
    }
}
