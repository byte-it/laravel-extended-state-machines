<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\Guards;

use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Guards;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\Guard;
use byteit\LaravelExtendedStateMachines\StateMachines\PendingTransition;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;

#[Guards(to: StatusStates::Approved)]
class ApproveGuard implements Guard
{

    /**
     * @inheritDoc
     */
    public function guard(PendingTransition $transition): bool
    {
        return true;
    }

}
