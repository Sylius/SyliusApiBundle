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

namespace Sylius\Bundle\ApiBundle\Applicator;

use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\PaymentTransitions;

final readonly class PaymentStateMachineTransitionApplicator implements PaymentStateMachineTransitionApplicatorInterface
{
    public function __construct(private StateMachineInterface $stateMachineFactory)
    {
    }

    public function complete(PaymentInterface $data): PaymentInterface
    {
        $this->stateMachineFactory->apply($data, PaymentTransitions::GRAPH, PaymentTransitions::TRANSITION_COMPLETE);

        return $data;
    }
}
