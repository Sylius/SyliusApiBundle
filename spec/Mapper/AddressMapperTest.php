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

namespace Tests\Sylius\Bundle\ApiBundle\Mapper;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Mapper\AddressMapper;
use Sylius\Component\Core\Model\AddressInterface;

final class AddressMapperTest extends TestCase
{
    private AddressMapper $addressMapper;

    protected function setUp(): void
    {
        $this->addressMapper = new AddressMapper();
    }

    public function testUpdatesAnAddressWithAProvince(): void
    {
        /** @var AddressInterface|MockObject $currentAddressMock */
        $currentAddressMock = $this->createMock(AddressInterface::class);
        /** @var AddressInterface|MockObject $targetAddressMock */
        $targetAddressMock = $this->createMock(AddressInterface::class);
        $targetAddressMock->expects($this->once())->method('getFirstName')->willReturn('John');
        $targetAddressMock->expects($this->once())->method('getLastName')->willReturn('Doe');
        $targetAddressMock->expects($this->once())->method('getCompany')->willReturn('CocaCola');
        $targetAddressMock->expects($this->once())->method('getStreet')->willReturn('Green Avenue');
        $targetAddressMock->expects($this->once())->method('getCountryCode')->willReturn('US');
        $targetAddressMock->expects($this->once())->method('getCity')->willReturn('New York');
        $targetAddressMock->expects($this->once())->method('getPostcode')->willReturn('00000');
        $targetAddressMock->expects($this->once())->method('getPhoneNumber')->willReturn('123456789');
        $targetAddressMock->expects($this->once())->method('getProvinceCode')->willReturn('999');
        $targetAddressMock->expects($this->once())->method('getProvinceName')->willReturn('east');
        $currentAddressMock->expects($this->once())->method('setFirstName')->with('John');
        $currentAddressMock->expects($this->once())->method('setLastName')->with('Doe');
        $currentAddressMock->expects($this->once())->method('setCompany')->with('CocaCola');
        $currentAddressMock->expects($this->once())->method('setStreet')->with('Green Avenue');
        $currentAddressMock->expects($this->once())->method('setCountryCode')->with('US');
        $currentAddressMock->expects($this->once())->method('setCity')->with('New York');
        $currentAddressMock->expects($this->once())->method('setPostcode')->with('00000');
        $currentAddressMock->expects($this->once())->method('setPhoneNumber')->with('123456789');
        $currentAddressMock->expects($this->once())->method('setProvinceCode')->with('999');
        $currentAddressMock->expects($this->once())->method('setProvinceName')->with('east');
        $this->assertSame($currentAddressMock, $this->addressMapper->mapExisting($currentAddressMock, $targetAddressMock));
    }

    public function testUpdatesAnAddressWithoutAProvince(): void
    {
        /** @var AddressInterface|MockObject $currentAddressMock */
        $currentAddressMock = $this->createMock(AddressInterface::class);
        /** @var AddressInterface|MockObject $targetAddressMock */
        $targetAddressMock = $this->createMock(AddressInterface::class);
        $targetAddressMock->expects($this->once())->method('getFirstName')->willReturn('John');
        $targetAddressMock->expects($this->once())->method('getLastName')->willReturn('Doe');
        $targetAddressMock->expects($this->once())->method('getCompany')->willReturn('CocaCola');
        $targetAddressMock->expects($this->once())->method('getStreet')->willReturn('Green Avenue');
        $targetAddressMock->expects($this->once())->method('getCountryCode')->willReturn('US');
        $targetAddressMock->expects($this->once())->method('getCity')->willReturn('New York');
        $targetAddressMock->expects($this->once())->method('getPostcode')->willReturn('00000');
        $targetAddressMock->expects($this->once())->method('getPhoneNumber')->willReturn('123456789');
        $targetAddressMock->expects($this->once())->method('getProvinceCode')->willReturn(null);
        $targetAddressMock->expects($this->never())->method('getProvinceName')->willReturn('east');
        $currentAddressMock->expects($this->once())->method('setFirstName')->with('John');
        $currentAddressMock->expects($this->once())->method('setLastName')->with('Doe');
        $currentAddressMock->expects($this->once())->method('setCompany')->with('CocaCola');
        $currentAddressMock->expects($this->once())->method('setStreet')->with('Green Avenue');
        $currentAddressMock->expects($this->once())->method('setCountryCode')->with('US');
        $currentAddressMock->expects($this->once())->method('setCity')->with('New York');
        $currentAddressMock->expects($this->once())->method('setPostcode')->with('00000');
        $currentAddressMock->expects($this->once())->method('setPhoneNumber')->with('123456789');
        $currentAddressMock->expects($this->never())->method('setProvinceCode')->with('999');
        $currentAddressMock->expects($this->never())->method('setProvinceName')->with('east');
        $this->assertSame($currentAddressMock, $this->addressMapper->mapExisting($currentAddressMock, $targetAddressMock));
    }
}
