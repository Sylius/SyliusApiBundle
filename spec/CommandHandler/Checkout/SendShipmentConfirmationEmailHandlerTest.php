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

namespace Tests\Sylius\Bundle\ApiBundle\CommandHandler\Checkout;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ApiBundle\Command\Checkout\SendShipmentConfirmationEmail;
use Sylius\Bundle\ApiBundle\CommandHandler\Checkout\SendShipmentConfirmationEmailHandler;
use Sylius\Bundle\ApiBundle\spec\CommandHandler\MessageHandlerAttributeTrait;
use Sylius\Bundle\CoreBundle\Mailer\ShipmentEmailManagerInterface;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Component\Core\Repository\ShipmentRepositoryInterface;

final class SendShipmentConfirmationEmailHandlerTest extends TestCase
{
    /** @var ShipmentRepositoryInterface|MockObject */
    private MockObject $shipmentRepositoryMock;

    /** @var ShipmentEmailManagerInterface|MockObject */
    private MockObject $shipmentEmailManagerMock;

    private SendShipmentConfirmationEmailHandler $handler;

    use MessageHandlerAttributeTrait;

    protected function setUp(): void
    {
        $this->shipmentRepositoryMock = $this->createMock(ShipmentRepositoryInterface::class);
        $this->shipmentEmailManagerMock = $this->createMock(ShipmentEmailManagerInterface::class);
        $this->handler = new SendShipmentConfirmationEmailHandler($this->shipmentRepositoryMock, $this->shipmentEmailManagerMock);
    }

    public function testSendsShipmentConfirmationMessage(): void
    {
        /** @var ShipmentInterface|MockObject $shipmentMock */
        $shipmentMock = $this->createMock(ShipmentInterface::class);
        /** @var CustomerInterface|MockObject $customerMock */
        $customerMock = $this->createMock(CustomerInterface::class);
        /** @var ChannelInterface|MockObject $channelMock */
        $channelMock = $this->createMock(ChannelInterface::class);
        /** @var OrderInterface|MockObject $orderMock */
        $orderMock = $this->createMock(OrderInterface::class);
        $this->shipmentRepositoryMock->expects(self::once())->method('find')->with(123)->willReturn($shipmentMock);
        $shipmentMock->expects(self::once())->method('getOrder')->willReturn($orderMock);
        $orderMock->method('getChannel')->willReturn($channelMock);
        $orderMock->method('getLocaleCode')->willReturn('pl_PL');
        $orderMock->method('getCustomer')->willReturn($customerMock);
        $customerMock->expects(self::once())->method('getEmail')->willReturn('johnny.bravo@email.com');
        $this->shipmentEmailManagerMock->expects(self::once())->method('sendConfirmationEmail')->with($shipmentMock);
        $this->handler->__invoke(new SendShipmentConfirmationEmail(123));
    }
}
