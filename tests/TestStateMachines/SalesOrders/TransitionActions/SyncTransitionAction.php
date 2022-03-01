<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions;

use byteit\LaravelExtendedStateMachines\Jobs\Concerns\InteractsWithTransition;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Before;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StateWithSyncAction;

#[Before(to: StateWithSyncAction::SyncAction)]
class SyncTransitionAction
{
    use InteractsWithTransition;

    static public bool $invoked = false;

    public function __construct()
    {
    }

    public function __invoke(SalesOrderWithBeforeTransitionHook|SalesOrder $order)
    {
        self::$invoked = true;
    }

}
