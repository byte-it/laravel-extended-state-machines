<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions;

use byteit\LaravelExtendedStateMachines\Events\TransitionStarted;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Before;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;

class ApproveAction
{
    #[Before(StatusStates::class, to: StatusStates::Approved)]
    public function before(SalesOrder $order, TransitionStarted $transition): void
    {
        $order->total = 100;
    }
}
