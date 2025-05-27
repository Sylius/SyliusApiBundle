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

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\Validator\Constraints\SingleValueForProductVariantOption;
use Sylius\Bundle\ApiBundle\Validator\Constraints\SingleValueForProductVariantOptionValidator;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class SingleValueForProductVariantOptionValidatorTest extends TestCase
{
    /** @var ExecutionContextInterface|MockObject */
    private MockObject $executionContextMock;

    private SingleValueForProductVariantOptionValidator $singleValueForProductVariantOptionValidator;

    protected function setUp(): void
    {
        $this->executionContextMock = $this->createMock(ExecutionContextInterface::class);
        $this->singleValueForProductVariantOptionValidator = new SingleValueForProductVariantOptionValidator($this->executionContextMock);
        $this->initialize($this->executionContextMock);
    }

    public function testAConstraintValidator(): void
    {
        $this->assertInstanceOf(ConstraintValidatorInterface::class, $this->singleValueForProductVariantOptionValidator);
    }

    public function testThrowsAnExceptionIfValueIsNotAProductVariant(): void
    {
        /** @var ExecutionContextInterface|MockObject $contextMock */
        $contextMock = $this->createMock(ExecutionContextInterface::class);
        $contextMock->expects($this->never())->method('buildViolation');
        $this->expectException(InvalidArgumentException::class);
        $this->singleValueForProductVariantOptionValidator->validate(new stdClass(), new SingleValueForProductVariantOption());
    }

    public function testThrowsAnExceptionIfConstraintIsNotASingleValueForProductVariantOption(): void
    {
        /** @var ExecutionContextInterface|MockObject $contextMock */
        $contextMock = $this->createMock(ExecutionContextInterface::class);
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        /** @var Constraint|MockObject $constraintMock */
        $constraintMock = $this->createMock(Constraint::class);
        $contextMock->expects($this->never())->method('buildViolation');
        $this->expectException(InvalidArgumentException::class);
        $this->singleValueForProductVariantOptionValidator->validate($variantMock, $constraintMock);
    }

    public function testAddsViolationIfThereIsMoreThanOneOptionValueToASingleOption(): void
    {
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductOptionValueInterface|MockObject $firstProductOptionValueMock */
        $firstProductOptionValueMock = $this->createMock(ProductOptionValueInterface::class);
        /** @var ProductOptionValueInterface|MockObject $secondProductOptionValueMock */
        $secondProductOptionValueMock = $this->createMock(ProductOptionValueInterface::class);
        $constraint = new SingleValueForProductVariantOption();
        $firstProductOptionValueMock->expects($this->once())->method('getOptionCode')->willReturn('OPTION');
        $secondProductOptionValueMock->expects($this->once())->method('getOptionCode')->willReturn('OPTION');
        $variantMock->expects($this->once())->method('getOptionValues')->willReturn(new ArrayCollection([
            $firstProductOptionValueMock,
            $secondProductOptionValueMock,
        ]));
        $this->executionContextMock->expects($this->once())->method('addViolation')->with('sylius.product_variant.option_values.single_value');
        $this->singleValueForProductVariantOptionValidator->validate($variantMock, $constraint);
    }

    public function testDoesNothingIfEachOptionHasOnlyOneOptionValue(): void
    {
        /** @var ProductVariantInterface|MockObject $variantMock */
        $variantMock = $this->createMock(ProductVariantInterface::class);
        /** @var ProductOptionValueInterface|MockObject $firstProductOptionValueMock */
        $firstProductOptionValueMock = $this->createMock(ProductOptionValueInterface::class);
        /** @var ProductOptionValueInterface|MockObject $secondProductOptionValueMock */
        $secondProductOptionValueMock = $this->createMock(ProductOptionValueInterface::class);
        $constraint = new SingleValueForProductVariantOption();
        $firstProductOptionValueMock->expects($this->once())->method('getOptionCode')->willReturn('OPTION');
        $secondProductOptionValueMock->expects($this->once())->method('getOptionCode')->willReturn('DIFFERENT_OPTION');
        $variantMock->expects($this->once())->method('getOptionValues')->willReturn(new ArrayCollection([
            $firstProductOptionValueMock,
            $secondProductOptionValueMock,
        ]));
        $this->executionContextMock->expects($this->never())->method('addViolation')->with($constraint->message);
        $this->singleValueForProductVariantOptionValidator->validate($variantMock, $constraint);
    }
}
