<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\Guards;

use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Guards;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\Guard;
use byteit\LaravelExtendedStateMachines\StateMachines\Transition;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;

#[Guards(to: StatusStates::Approved)]
class ApproveGuard implements Guard
{

    /**
     * @inheritDoc
     */
    public function guard(Transition $transition): void
    {
        // TODO: Implement guard() method.
    }

}
