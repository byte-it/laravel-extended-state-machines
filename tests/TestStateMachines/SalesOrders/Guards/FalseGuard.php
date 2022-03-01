<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\Guards;

use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Guards;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\Guard;
use byteit\LaravelExtendedStateMachines\StateMachines\PendingTransition;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\State;

#[Guards(to: State::Guarded)]
class FalseGuard implements Guard
{

    /**
     * @inheritDoc
     */
    public function guard(PendingTransition $transition): bool
    {
        return false;
    }

}
