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

namespace Tests\Sylius\Bundle\ApiBundle\StateProcessor\Admin\Customer;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\StateProcessor\Admin\Customer\PersistProcessor;
use Sylius\Component\Core\Model\Customer;
use Sylius\Component\Core\Model\ShopUser;
use Sylius\Component\User\Security\PasswordUpdaterInterface;

final class PersistProcessorTest extends TestCase
{
    /** @var ProcessorInterface|MockObject */
    private MockObject $persistProcessorMock;

    /** @var PasswordUpdaterInterface|MockObject */
    private MockObject $passwordUpdaterMock;

    private PersistProcessor $persistProcessor;

    protected function setUp(): void
    {
        $this->persistProcessorMock = $this->createMock(ProcessorInterface::class);
        $this->passwordUpdaterMock = $this->createMock(PasswordUpdaterInterface::class);
        $this->persistProcessor = new PersistProcessor($this->persistProcessorMock, $this->passwordUpdaterMock);
    }

    public function testDoesNotProcessDeleteOperation(): void
    {
        /** @var Customer|MockObject $customerMock */
        $customerMock = $this->createMock(Customer::class);
        /** @var ShopUser|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUser::class);
        $operation = new Delete();
        $this->passwordUpdaterMock->expects($this->never())->method('updatePassword')->with($shopUserMock);
        $this->persistProcessorMock->expects($this->never())->method('process')->with($customerMock, $operation, [], []);
        $this->expectException(InvalidArgumentException::class);
        $this->persistProcessor->process($customerMock, $operation, [], []);
    }

    public function testProcessesPostOperation(): void
    {
        /** @var Customer|MockObject $customerMock */
        $customerMock = $this->createMock(Customer::class);
        /** @var ShopUser|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUser::class);
        $operation = new Post();
        $customerMock->expects($this->once())->method('getUser')->willReturn($shopUserMock);
        $this->passwordUpdaterMock->expects($this->once())->method('updatePassword')->with($shopUserMock);
        $this->persistProcessorMock->expects($this->once())->method('process')->with($customerMock, $operation, [], []);
        $this->persistProcessor->process($customerMock, $operation, [], []);
    }

    public function testProcessesPutOperation(): void
    {
        /** @var Customer|MockObject $customerMock */
        $customerMock = $this->createMock(Customer::class);
        /** @var ShopUser|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUser::class);
        $operation = new Put();
        $customerMock->expects($this->once())->method('getUser')->willReturn($shopUserMock);
        $this->passwordUpdaterMock->expects($this->once())->method('updatePassword')->with($shopUserMock);
        $this->persistProcessorMock->expects($this->once())->method('process')->with($customerMock, $operation, [], []);
        $this->persistProcessor->process($customerMock, $operation, [], []);
    }

    public function testDoesNotUpdatePasswordOnPatchOperation(): void
    {
        /** @var Customer|MockObject $customerMock */
        $customerMock = $this->createMock(Customer::class);
        /** @var ShopUser|MockObject $shopUserMock */
        $shopUserMock = $this->createMock(ShopUser::class);
        $operation = new Patch();
        $this->passwordUpdaterMock->expects($this->never())->method('updatePassword')->with($shopUserMock);
        $this->persistProcessorMock->expects($this->once())->method('process')->with($customerMock, $operation, [], []);
        $this->persistProcessor->process($customerMock, $operation, [], []);
    }
}
