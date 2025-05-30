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
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\StateProcessor\Admin\AdminUser\PersistProcessor;
use Sylius\Component\Core\Model\AdminUserInterface;
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
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        $operation = new Delete();
        $this->passwordUpdaterMock->expects(self::never())->method('updatePassword')->with($adminUserMock);
        $this->persistProcessorMock->expects(self::never())->method('process')->with($adminUserMock, $operation, [], []);
        $this->expectException(InvalidArgumentException::class);
        $this->persistProcessor->process($adminUserMock, $operation, [], []);
    }

    public function testDoesNotUpdatePasswordOnPostOperation(): void
    {
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        $operation = new Post();
        $this->passwordUpdaterMock->expects(self::never())->method('updatePassword')->with($adminUserMock);
        $this->persistProcessorMock->expects(self::once())->method('process')->with($adminUserMock, $operation, [], []);
        $this->persistProcessor->process($adminUserMock, $operation, [], []);
    }

    public function testDoesNotUpdatePasswordOnPatchOperation(): void
    {
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        $operation = new Patch();
        $this->passwordUpdaterMock->expects(self::never())->method('updatePassword')->with($adminUserMock);
        $this->persistProcessorMock->expects(self::once())->method('process')->with($adminUserMock, $operation, [], []);
        $this->persistProcessor->process($adminUserMock, $operation, [], []);
    }

    public function testProcessesPutOperation(): void
    {
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        $operation = new Put();
        $this->passwordUpdaterMock->expects(self::once())->method('updatePassword')->with($adminUserMock);
        $this->persistProcessorMock->expects(self::once())->method('process')->with($adminUserMock, $operation, [], []);
        $this->persistProcessor->process($adminUserMock, $operation, [], []);
    }
}
