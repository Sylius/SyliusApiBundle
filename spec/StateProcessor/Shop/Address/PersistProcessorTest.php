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

namespace Tests\Sylius\Bundle\ApiBundle\StateProcessor\Shop\Address;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;
use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\Bundle\ApiBundle\StateProcessor\Shop\Address\PersistProcessor;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\ShopUserInterface;

final class PersistProcessorTest extends TestCase
{
    /** @var ProcessorInterface|MockObject */
    private MockObject $persistProcessorMock;

    /** @var UserContextInterface|MockObject */
    private MockObject $userContextMock;

    private PersistProcessor $persistProcessor;

    protected function setUp(): void
    {
        $this->persistProcessorMock = $this->createMock(ProcessorInterface::class);
        $this->userContextMock = $this->createMock(UserContextInterface::class);
        $this->persistProcessor = new PersistProcessor($this->persistProcessorMock, $this->userContextMock);
    }

    public function testThrowsAnExceptionIfObjectIsNotAnAddress(): void
    {
        $this->persistProcessorMock->expects($this->never())->method('process')->with($this->any());
        $this->userContextMock->expects($this->never())->method('getUser');
        $this->expectException(InvalidArgumentException::class);
        $this->persistProcessor->process(new stdClass(), new Post());
    }

    public function testThrowsAnExceptionIfOperationIsNotPost(): void
    {
        $this->persistProcessorMock->expects($this->never())->method('process')->with($this->any());
        $this->userContextMock->expects($this->never())->method('getUser');
        $this->expectException(InvalidArgumentException::class);
        $this->persistProcessor->process(new stdClass(), new Delete());
    }

    public function testSetsCustomerAndDefaultAddressIfUserIsShopUser(): void
    {
        /** @var AddressInterface|MockObject $addressMock */
        $addressMock = $this->createMock(AddressInterface::class);
        /** @var ShopUserInterface|MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        $operation = new Post();
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getDefaultAddress')->willReturn(null);
        $this->persistProcessor->process($addressMock, $operation);
        $this->persistProcessorMock->expects($this->once())->method('process')->with($addressMock, $operation, [], [])->shouldHaveBeenCalledOnce();
        $addressMock->expects($this->once())->method('setCustomer')->with($customerMock)->shouldHaveBeenCalledOnce();
        $customerMock->expects($this->once())->method('setDefaultAddress')->with($addressMock)->shouldHaveBeenCalledOnce();
    }

    public function testSetsCustomerAndDefaultAddressIfUserIsShopUserAndCustomerHasDefaultAddress(): void
    {
        /** @var AddressInterface|MockObject $addressMock */
        $addressMock = $this->createMock(AddressInterface::class);
        /** @var ShopUserInterface|MockObject $userMock */
        $userMock = $this->createMock(ShopUserInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var AddressInterface|MockObject $defaultAddressMock */
        $defaultAddressMock = $this->createMock(AddressInterface::class);
        $operation = new Post();
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn($userMock);
        $userMock->expects($this->once())->method('getCustomer')->willReturn($customerMock);
        $customerMock->expects($this->once())->method('getDefaultAddress')->willReturn($defaultAddressMock);
        $this->persistProcessor->process($addressMock, $operation);
        $this->persistProcessorMock->expects($this->once())->method('process')->with($addressMock, $operation, [], [])->shouldHaveBeenCalledOnce();
        $addressMock->expects($this->once())->method('setCustomer')->with($customerMock)->shouldHaveBeenCalledOnce();
        $customerMock->expects($this->once())->method('setDefaultAddress')->with($addressMock)->shouldNotHaveBeenCalled();
    }

    public function testDoesNotSetCustomerAndDefaultAddressIfUserIsNotShopUser(): void
    {
        /** @var AddressInterface|MockObject $addressMock */
        $addressMock = $this->createMock(AddressInterface::class);
        $operation = new Post();
        $this->userContextMock->expects($this->once())->method('getUser')->willReturn(null);
        $this->persistProcessor->process($addressMock, $operation);
        $this->persistProcessorMock->expects($this->once())->method('process')->with($addressMock, $operation, [], [])->shouldHaveBeenCalledOnce();
        $addressMock->expects($this->once())->method('setCustomer')->shouldNotHaveBeenCalled();
    }
}
