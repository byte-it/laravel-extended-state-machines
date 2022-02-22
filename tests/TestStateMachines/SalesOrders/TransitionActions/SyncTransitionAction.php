<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions;

use byteit\LaravelExtendedStateMachines\Jobs\Concerns\InteractsWithTransition;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Before;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithBeforeTransitionHookStates;

#[Before(to: StatusWithBeforeTransitionHookStates::Approved)]
class SyncTransitionAction
{
    use InteractsWithTransition;

    public function __construct()
    {
    }

    public function __invoke(SalesOrderWithBeforeTransitionHook $order)
    {
        $order->notes = 'approved sync';
        $order->total = 100;
    }

}
