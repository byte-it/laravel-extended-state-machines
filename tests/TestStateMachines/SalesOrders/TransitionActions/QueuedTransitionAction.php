<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions;

use byteit\LaravelExtendedStateMachines\Jobs\Concerns\InteractsWithTransition;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Before;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\StateWithAsyncAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

#[Before(to: StateWithAsyncAction::AsyncAction)]
class QueuedTransitionAction implements ShouldQueue
{
    use InteractsWithQueue, InteractsWithTransition, Queueable;

    public static bool $invoked = false;

    public function __construct()
    {
//        $this->onConnection('default');
//        $this->onQueue('default');
    }

    public function __invoke(SalesOrderWithBeforeTransitionHook $order)
    {
        self::$invoked = true;
    }

    public function label(): string{
        return 'Processing';
    }

}
