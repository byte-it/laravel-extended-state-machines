<?php


namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders;

use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\DefaultState;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\RecordHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;

#[DefaultState(StatusStates::Pending)]
#[RecordHistory]
enum StatusStates: string implements States
{
    case Pending = 'pending';
    case Approved =  'approved';
    case Processed = 'processed';

    public function transition(): array
    {
        return match($this){
            self::Pending => [self::Approved],
            self::Approved => [self::Processed],
            default => []
        };
    }
}
