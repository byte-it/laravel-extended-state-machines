<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders;

use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Before;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\HasActions;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\RecordHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\QueuedTransitionAction;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\SyncTransitionAction;

#[
  RecordHistory,
  HasActions([QueuedTransitionAction::class])
]
enum StateWithAsyncAction: string implements States
{
    case Created = 'created';

    case AsyncAction = 'asyncAction';


    public function transitions(): array
    {
        return match ($this) {
            self::Created => [self::AsyncAction],
            default => [],
        };
    }


}
