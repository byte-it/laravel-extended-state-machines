<?php


namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders;


use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\HasActions;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\RecordHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\QueuedTransitionAction;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\TransitionActions\SyncTransitionAction;

#[
  RecordHistory,
  HasActions([QueuedTransitionAction::class, SyncTransitionAction::class])
]
enum StatusWithBeforeTransitionHookStates: string implements States
{
    case Pending = 'pending';
    case Approved =  'approved';
    case Processed = 'processed';

    public function transitions(): array
    {
        return match($this){
            self::Pending => [self::Approved],
            self::Approved => [self::Processed],
            default => []
        };
    }
}
