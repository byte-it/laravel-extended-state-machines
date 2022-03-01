<?php

namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders;

use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Before;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\HasActions;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\RecordHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use byteit\LaravelExtendedStateMachines\Tests\TestModels\SalesOrderWithBeforeTransitionHook;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\SyncTransitionAction;

#[
  RecordHistory,
  HasActions([SyncTransitionAction::class])
]
enum StateWithSyncAction: string implements States
{
    case Created = 'created';

    case SyncAction = 'syncAction';

    case InlineSyncAction = 'inlineSyncAction';

    public function transitions(): array
    {
        return match ($this) {
            self::Created => [self::SyncAction, self::InlineSyncAction],
            default => [],
        };
    }

    #[Before(to: self::InlineSyncAction)]
    public function inlineSyncActionHandler(SalesOrderWithBeforeTransitionHook $order): void{
        $order->notes = 'inlineSync';
    }

}
