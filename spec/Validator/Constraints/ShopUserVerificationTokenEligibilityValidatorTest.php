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

use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Command\Account\VerifyShopUser;
use Sylius\Bundle\ApiBundle\Validator\Constraints\OrderPaymentMethodEligibility;
use Sylius\Bundle\ApiBundle\Validator\Constraints\ShopUserVerificationTokenEligibility;
use Sylius\Bundle\ApiBundle\Validator\Constraints\ShopUserVerificationTokenEligibilityValidator;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class ShopUserVerificationTokenEligibilityValidatorTest extends TestCase
{
    /** @var RepositoryInterface|MockObject */
    private MockObject $shopUserRepositoryMock;

    private ShopUserVerificationTokenEligibilityValidator $shopUserVerificationTokenEligibilityValidator;

    protected function setUp(): void
    {
        $this->shopUserRepositoryMock = $this->createMock(RepositoryInterface::class);
        $this->shopUserVerificationTokenEligibilityValidator = new ShopUserVerificationTokenEligibilityValidator($this->shopUserRepositoryMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->shopUserVerificationTokenEligibilityValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotTypeOfVerifyShopUser(): void
    {
        $constraint = new ShopUserVerificationTokenEligibility();
        $this->expectException(InvalidArgumentException::class);
        $this->shopUserVerificationTokenEligibilityValidator->validate('', $constraint);
    }

    public function testThrowsAnExceptionIfConstraintIsNotTypeOfShopUserVerificationEligibility(): void
    {
        $value = new VerifyShopUser('TOKEN', 'en_US', 'WEB');
        $constraint = new OrderPaymentMethodEligibility();
        $this->expectException(InvalidArgumentException::class);
        $this->shopUserVerificationTokenEligibilityValidator->validate($value, $constraint);
    }

    public function testAddsViolationIfShopUserIsNull(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $constraint = new ShopUserVerificationTokenEligibility();
        $value = new VerifyShopUser(
            channelCode: 'WEB',
            localeCode: 'en_US',
            token: 'TOKEN',
        );
        $this->shopUserVerificationTokenEligibilityValidator->initialize($executionContextMock);
        $this->shopUserRepositoryMock->expects($this->once())->method('findOneBy')->with(['emailVerificationToken' => 'TOKEN'])->willReturn(null);
        $executionContextMock->expects($this->once())->method('addViolation')->with('sylius.account.invalid_verification_token', ['%verificationToken%' => 'TOKEN'])
        ;
        $this->shopUserVerificationTokenEligibilityValidator->validate($value, $constraint);
    }

    public function testDoesNothingIfShopUserHasBeenFound(): void
    {
        /** @var ExecutionContextInterface|MockObject $executionContextMock */
        $executionContextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ShopUserInterface|MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        $constraint = new ShopUserVerificationTokenEligibility();
        $value = new VerifyShopUser(
            channelCode: 'WEB',
            localeCode: 'en_US',
            token: 'TOKEN',
        );
        $this->shopUserVerificationTokenEligibilityValidator->initialize($executionContextMock);
        $this->shopUserRepositoryMock->expects($this->once())->method('findOneBy')->with(['emailVerificationToken' => 'TOKEN'])->willReturn($userMock);
        $executionContextMock->expects($this->never())->method('addViolation')->with('sylius.account.invalid_verification_token', ['%verificationToken%' => 'TOKEN'])
        ;
        $this->shopUserVerificationTokenEligibilityValidator->validate($value, $constraint);
    }
}
