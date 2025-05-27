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

namespace Tests\Sylius\Bundle\ApiBundle\StateProcessor\Admin\AdminUser;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\State\ProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\StateProcessor\Admin\AdminUser\RemoveProcessor;
use Sylius\Component\Core\Exception\ResourceDeleteException;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class RemoveProcessorTest extends TestCase
{
    /** @var ProcessorInterface|MockObject */
    private MockObject $removeProcessorMock;

    /** @var TokenStorageInterface|MockObject */
    private MockObject $tokenStorageMock;

    private RemoveProcessor $removeProcessor;

    protected function setUp(): void
    {
        $this->removeProcessorMock = $this->createMock(ProcessorInterface::class);
        $this->tokenStorageMock = $this->createMock(TokenStorageInterface::class);
        $this->removeProcessor = new RemoveProcessor($this->removeProcessorMock, $this->tokenStorageMock);
    }

    public function testProcessesDeleteOperation(): void
    {
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var AdminUserInterface|MockObject $loggedUserMock */
        $loggedUserMock = $this->createMock(AdminUserInterface::class);
        $operation = new Delete();
        $this->tokenStorageMock->expects($this->once())->method('getToken')->willReturn($tokenMock);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($loggedUserMock);
        $loggedUserMock->expects($this->once())->method('getId')->willReturn(2);
        $adminUserMock->expects($this->once())->method('getId')->willReturn(1);
        $this->removeProcessorMock->expects($this->once())->method('process')->with($adminUserMock, $operation, [], []);
        $this->removeProcessor->process($adminUserMock, $operation, [], []);
    }

    public function testThrowsExceptionWhenTryingToDeleteLoggedInUser(): void
    {
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        /** @var TokenInterface|MockObject $tokenMock */
        $tokenMock = $this->createMock(TokenInterface::class);
        /** @var AdminUserInterface|MockObject $loggedUserMock */
        $loggedUserMock = $this->createMock(AdminUserInterface::class);
        $this->tokenStorageMock->expects($this->once())->method('getToken')->willReturn($tokenMock);
        $tokenMock->expects($this->once())->method('getUser')->willReturn($loggedUserMock);
        $loggedUserMock->expects($this->once())->method('getId')->willReturn(1);
        $adminUserMock->expects($this->once())->method('getId')->willReturn(1);
        $this->expectException(ResourceDeleteException::class);
        $this->removeProcessor->process($adminUserMock, new Delete(), [], []);
    }

    public function testProcessesDeleteIfNoUserTokenFound(): void
    {
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        $operation = new Delete();
        $this->tokenStorageMock->expects($this->once())->method('getToken')->willReturn(null);
        $this->removeProcessorMock->expects($this->once())->method('process')->with($adminUserMock, $operation, [], []);
        $this->removeProcessor->process($adminUserMock, $operation, [], []);
    }
}
