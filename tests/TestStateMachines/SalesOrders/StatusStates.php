<?php


namespace byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders;

use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\DefaultState;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\Guards;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\HasGuards;
use byteit\LaravelExtendedStateMachines\StateMachines\Attributes\RecordHistory;
use byteit\LaravelExtendedStateMachines\StateMachines\Contracts\States;
use byteit\LaravelExtendedStateMachines\StateMachines\Transition;
use byteit\LaravelExtendedStateMachines\Tests\TestStateMachines\SalesOrders\Guards\ApproveGuard;

#[
  DefaultState(StatusStates::Pending),
  RecordHistory,
  HasGuards([ApproveGuard::class]),
]
enum StatusStates: string implements States
{

    case Pending = 'pending';

    case Approved = 'approved';

    case Processed = 'processed';

    public function transitions(): array
    {
        return match ($this) {
            self::Pending => [self::Approved],
            self::Approved => [self::Processed],
            default => []
        };
    }

    #[Guards(from: self::Approved, to: self::Processed)]
    public static function guardProcessing(Transition $transition): bool
    {
        return $transition->model->fulfillment === FulfillmentStates::Complete;
    }

}
