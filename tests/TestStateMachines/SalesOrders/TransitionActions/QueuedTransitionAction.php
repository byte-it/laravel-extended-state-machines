<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions;

use byteit\LaravelExtendedStateMachines\Jobs\Concerns\InteractsWithTransition;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Before;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StatusWithBeforeTransitionHookStates;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

#[Before(to: StatusWithBeforeTransitionHookStates::Processed)]
class QueuedTransitionAction implements ShouldQueue
{
    use InteractsWithQueue, InteractsWithTransition, Queueable;

    public function __invoke(SalesOrderWithBeforeTransitionHook $order)
    {
        $order->notes = 'processed in queued';
    }

    public function label(): string{
        return 'Processing';
    }

}
