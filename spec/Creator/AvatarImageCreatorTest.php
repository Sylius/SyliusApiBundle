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

namespace Tests\Sylius\Bundle\ApiBundle\Creator;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SplFileInfo;
use Sylius\Bundle\ApiBundle\Creator\AvatarImageCreator;
use Sylius\Bundle\ApiBundle\Exception\AdminUserNotFoundException;
use Sylius\Bundle\ApiBundle\Exception\NoFileUploadedException;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\AvatarImageInterface;
use Sylius\Component\Core\Uploader\ImageUploaderInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Sylius\Resource\Factory\FactoryInterface;

final class AvatarImageCreatorTest extends TestCase
{
    /** @var FactoryInterface|MockObject */
    private MockObject $avatarImageFactoryMock;

    /** @var RepositoryInterface|MockObject */
    private MockObject $adminUserRepositoryMock;

    /** @var ImageUploaderInterface|MockObject */
    private MockObject $imageUploaderMock;

    private AvatarImageCreator $avatarImageCreator;

    protected function setUp(): void
    {
        $this->avatarImageFactoryMock = $this->createMock(FactoryInterface::class);
        $this->adminUserRepositoryMock = $this->createMock(RepositoryInterface::class);
        $this->imageUploaderMock = $this->createMock(ImageUploaderInterface::class);
        $this->avatarImageCreator = new AvatarImageCreator($this->avatarImageFactoryMock, $this->adminUserRepositoryMock, $this->imageUploaderMock);
    }

    public function testCreatesAnAvatarImage(): void
    {
        /** @var AdminUserInterface|MockObject $adminUserMock */
        $adminUserMock = $this->createMock(AdminUserInterface::class);
        /** @var AvatarImageInterface|MockObject $avatarImageMock */
        $avatarImageMock = $this->createMock(AvatarImageInterface::class);
        $file = new SplFileInfo(__FILE__);
        $this->adminUserRepositoryMock->expects($this->once())->method('find')->with('1')->willReturn($adminUserMock);
        $this->avatarImageFactoryMock->expects($this->once())->method('createNew')->willReturn($avatarImageMock);
        $avatarImageMock->expects($this->once())->method('setFile')->with($file);
        $adminUserMock->expects($this->once())->method('setImage')->with($avatarImageMock);
        $this->imageUploaderMock->expects($this->once())->method('upload')->with($avatarImageMock);
        $this->assertSame($avatarImageMock, $this->avatarImageCreator->create('1', $file, null));
    }

    public function testThrowsAnExceptionIfAdminUserIsNotFound(): void
    {
        $file = new SplFileInfo(__FILE__);
        $this->adminUserRepositoryMock->expects($this->once())->method('find')->with('1')->willReturn(null);
        $this->avatarImageFactoryMock->expects($this->never())->method('createNew');
        $this->imageUploaderMock->expects($this->never())->method('upload');
        $this->expectException(AdminUserNotFoundException::class);
        $this->avatarImageCreator->create('1', $file, null);
    }

    public function testThrowsAnExceptionIfThereIsNoUploadedFile(): void
    {
        $this->adminUserRepositoryMock->expects($this->never())->method('find')->with('1');
        $this->avatarImageFactoryMock->expects($this->never())->method('createNew');
        $this->imageUploaderMock->expects($this->never())->method('upload');
        $this->expectException(NoFileUploadedException::class);
        $this->avatarImageCreator->create('1', null, null);
    }
}
