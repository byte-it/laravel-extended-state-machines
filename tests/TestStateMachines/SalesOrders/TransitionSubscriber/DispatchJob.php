<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionSubscriber;

use byteit\LaravelExtendedStateMachines\Events\TransitionCompleted;
use byteit\LaravelExtendedStateMachines\Events\TransitionStarted;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\After;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Before;
use byteit\LaravelExtendedStateMachines\Tests\TestJobs\AfterTransitionJob;
use byteit\LaravelExtendedStateMachines\Tests\TestJobs\BeforeTransitionJob;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrder;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrderWithAfterTransitionHook;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusStates;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithAfterTransitionHookStates;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithBeforeTransitionHookStates;

class DispatchJob
{
    #[Before(to: StatusWithBeforeTransitionHookStates::Approved)]
    public function before(SalesOrderWithBeforeTransitionHook $order, TransitionStarted $transition): void
    {
        BeforeTransitionJob::dispatch();
    }

    #[After(to: StatusWithAfterTransitionHookStates::Approved)]
    public function after(SalesOrderWithAfterTransitionHook $order, TransitionCompleted $transition): void
    {
        AfterTransitionJob::dispatch();
        $order->save();

    }
}
