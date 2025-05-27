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
use Sylius\Bundle\ApiBundle\Validator\Constraints\UniqueShopUserEmailValidator;
use InvalidArgumentException;
use Sylius\Bundle\ApiBundle\Validator\Constraints\UniqueShopUserEmail;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\User\Canonicalizer\CanonicalizerInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class UniqueShopUserEmailValidatorTest extends TestCase
{
    /** @var CanonicalizerInterface|MockObject */
    private MockObject $canonicalizerMock;
    /** @var UserRepositoryInterface|MockObject */
    private MockObject $shopUserRepositoryMock;
    /** @var ExecutionContextInterface|MockObject */
    private MockObject $executionContextMock;
    private UniqueShopUserEmailValidator $uniqueShopUserEmailValidator;
    protected function setUp(): void
    {
        $this->canonicalizerMock = $this->createMock(CanonicalizerInterface::class);
        $this->shopUserRepositoryMock = $this->createMock(UserRepositoryInterface::class);
        $this->executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->uniqueShopUserEmailValidator = new UniqueShopUserEmailValidator($this->canonicalizerMock, $this->shopUserRepositoryMock);
        $this->initialize($this->executionContextMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->uniqueShopUserEmailValidator);
    }

    public function testDoesNothingIfValueIsNull(): void
    {
        $this->executionContextMock->expects($this->never())->method('addViolation')->with($this->any());
        $this->uniqueShopUserEmailValidator->validate(null, new UniqueShopUserEmail());
    }

    public function testThrowsAnExceptionIfConstraintIsNotOfExpectedType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->uniqueShopUserEmailValidator->validate('', final class() extends TestCase {
        });
    }

    public function testDoesNotAddViolationIfAUserWithGivenEmailIsNotFound(): void
    {
        $this->canonicalizerMock->expects($this->once())->method('canonicalize')->with('eMaIl@example.com')->willReturn('email@example.com');
        $this->shopUserRepositoryMock->expects($this->once())->method('findOneByEmail')->with('email@example.com')->willReturn(null);
        $this->executionContextMock->expects($this->never())->method('addViolation')->with($this->any());
        $this->uniqueShopUserEmailValidator->validate('eMaIl@example.com', new UniqueShopUserEmail());
    }

    public function testAddsViolationIfAUserWithGivenEmailIsFound(): void
    {
        /** @var ShopUserInterface|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUserInterface::class);
        $this->canonicalizerMock->expects($this->once())->method('canonicalize')->with('eMaIl@example.com')->willReturn('email@example.com');
        $this->shopUserRepositoryMock->expects($this->once())->method('findOneByEmail')->with('email@example.com')->willReturn($shopUserMock);
        $this->executionContextMock->expects($this->once())->method('addViolation')->with($this->any());
        $this->uniqueShopUserEmailValidator->validate('eMaIl@example.com', new UniqueShopUserEmail());
    }
}
